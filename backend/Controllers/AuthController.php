<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Controllers / AuthController.php
 * ============================================================
 *
 * Controlador MVC encargado de:
 * - Iniciar sesión
 * - Registrar empleados
 * - Cerrar sesión
 *
 * No contiene SQL.
 * Las consultas son responsabilidad de UsuarioModel.
 *
 * Roles:
 * - admin
 * - admin_empresa
 * - empleado
 *
 * ============================================================
 */

class AuthController
{
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    /**
     * ============================================================
     * LOGIN
     * POST /Api/login.php
     * ============================================================
     */
    public function login(): void
    {
        if (Request::method() !== "POST") {
            Response::json(
                false,
                "Método no permitido.",
                [],
                405
            );
        }

        $correo = strtolower(
            trim(
                Request::input(
                    "email",
                    Request::input("correo", "")
                )
            )
        );

        $password = Request::input("password", "");

        /**
         * --------------------------------------------------------
         * VALIDACIONES
         * --------------------------------------------------------
         */

        if ($correo === "" || $password === "") {
            Response::json(
                false,
                "Correo y contraseña son obligatorios.",
                [],
                400
            );
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Response::json(
                false,
                "El correo electrónico no es válido.",
                [],
                400
            );
        }

        /**
         * --------------------------------------------------------
         * BUSCAR USUARIO
         * --------------------------------------------------------
         */

        $usuario = $this->usuarios->buscarPorCorreo($correo);

        if (!$usuario) {
            Response::json(
                false,
                "Correo o contraseña incorrectos.",
                [],
                401
            );
        }

        /**
         * --------------------------------------------------------
         * VERIFICAR ESTADO
         * --------------------------------------------------------
         */

        if ($usuario["estado"] !== "activo") {
            Response::json(
                false,
                "Tu cuenta está inactiva. Contacta al administrador.",
                [],
                403
            );
        }

        /**
         * --------------------------------------------------------
         * VERIFICAR CONTRASEÑA
         * --------------------------------------------------------
         */

        if (!password_verify($password, $usuario["password"])) {
            Response::json(
                false,
                "Correo o contraseña incorrectos.",
                [],
                401
            );
        }

        /**
         * --------------------------------------------------------
         * VALIDAR ROL
         * --------------------------------------------------------
         */

        $rolesPermitidos = [
            "admin",
            "admin_empresa",
            "empleado"
        ];

        if (!in_array($usuario["rol"], $rolesPermitidos, true)) {
            Response::json(
                false,
                "El rol de usuario no es válido.",
                [],
                403
            );
        }

        /**
         * --------------------------------------------------------
         * CREAR SESIÓN
         * --------------------------------------------------------
         */

        Auth::login($usuario);

        /**
         * --------------------------------------------------------
         * RESPUESTA
         * --------------------------------------------------------
         */

        Response::json(
            true,
            "Inicio de sesión correcto.",
            [
                "user" => [
                    "id" => $usuario["id"],
                    "nombres" => $usuario["nombres"],
                    "apellidos" => $usuario["apellidos"],
                    "correo" => $usuario["correo"],
                    "rol" => $usuario["rol"],
                    "empresa_id" => $usuario["empresa_id"],
                    "estado" => $usuario["estado"]
                ]
            ]
        );
    }

    /**
     * ============================================================
     * REGISTRAR EMPLEADO
     * POST /Api/register.php
     * ============================================================
     *
     * IMPORTANTE:
     *
     * Este endpoint NO es público.
     *
     * Solamente pueden crear empleados:
     *
     * - admin
     * - admin_empresa
     *
     * Un admin puede indicar la empresa.
     *
     * Un admin_empresa NO puede elegir la empresa.
     * Automáticamente se utiliza la empresa de su sesión.
     *
     * ============================================================
     */
    public function register(): void
    {
        if (Request::method() === "OPTIONS") {
            http_response_code(200);
            exit;
        }

        if (Request::method() !== "POST") {
            Response::json(
                false,
                "Método no permitido.",
                [],
                405
            );
        }

        /**
         * --------------------------------------------------------
         * VERIFICAR AUTENTICACIÓN
         * --------------------------------------------------------
         */

        if (!Auth::isAuthenticated()) {
            Response::json(
                false,
                "Debes iniciar sesión para crear empleados.",
                [],
                401
            );
        }

        /**
         * --------------------------------------------------------
         * VERIFICAR ROL
         * --------------------------------------------------------
         */

        if (
            !Auth::isAdmin() &&
            !Auth::isAdminEmpresa()
        ) {
            Response::json(
                false,
                "No tienes permisos para crear empleados.",
                [],
                403
            );
        }

        /**
         * --------------------------------------------------------
         * RECIBIR DATOS
         * --------------------------------------------------------
         */

        $nombres = trim(
            Request::input("nombres", "")
        );

        $apellidos = trim(
            Request::input("apellidos", "")
        );

        $correo = strtolower(
            trim(
                Request::input("correo", "")
            )
        );

        $password = Request::input(
            "password",
            ""
        );

        /**
         * --------------------------------------------------------
         * EMPRESA
         * --------------------------------------------------------
         *
         * Para admin:
         * puede seleccionar empresa.
         *
         * Para admin_empresa:
         * se utiliza SIEMPRE la empresa de su sesión.
         *
         * --------------------------------------------------------
         */

        $empresaIdInput = Request::input(
            "empresa_id",
            ""
        );

        $empresaId = null;

        if (Auth::isAdminEmpresa()) {

            $empresaId = Auth::empresaId();

            if (!$empresaId) {
                Response::json(
                    false,
                    "Tu cuenta de administrador de empresa no tiene una empresa asignada.",
                    [],
                    403
                );
            }

        } elseif (Auth::isAdmin()) {

            if ($empresaIdInput === "") {
                Response::json(
                    false,
                    "Debes seleccionar una empresa para el empleado.",
                    [],
                    400
                );
            }

            $empresaId = (int) $empresaIdInput;

            if ($empresaId <= 0) {
                Response::json(
                    false,
                    "La empresa seleccionada no es válida.",
                    [],
                    400
                );
            }
        }

        /**
         * --------------------------------------------------------
         * VALIDAR CAMPOS
         * --------------------------------------------------------
         */

        if (
            $nombres === "" ||
            $apellidos === "" ||
            $correo === "" ||
            $password === ""
        ) {
            Response::json(
                false,
                "Todos los campos son obligatorios.",
                [],
                400
            );
        }

        /**
         * --------------------------------------------------------
         * VALIDAR NOMBRES
         * --------------------------------------------------------
         */

        if (mb_strlen($nombres) < 2) {
            Response::json(
                false,
                "El nombre debe tener al menos 2 caracteres.",
                [],
                400
            );
        }

        /**
         * --------------------------------------------------------
         * VALIDAR APELLIDOS
         * --------------------------------------------------------
         */

        if (mb_strlen($apellidos) < 2) {
            Response::json(
                false,
                "Los apellidos deben tener al menos 2 caracteres.",
                [],
                400
            );
        }

        /**
         * --------------------------------------------------------
         * VALIDAR CORREO
         * --------------------------------------------------------
         */

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Response::json(
                false,
                "El correo electrónico no es válido.",
                [],
                400
            );
        }

        /**
         * --------------------------------------------------------
         * VALIDAR CONTRASEÑA
         * --------------------------------------------------------
         */

        if (strlen($password) < 8) {
            Response::json(
                false,
                "La contraseña debe tener mínimo 8 caracteres.",
                [],
                400
            );
        }

        /**
         * --------------------------------------------------------
         * VERIFICAR CORREO DUPLICADO
         * --------------------------------------------------------
         */

        if ($this->usuarios->correoExiste($correo)) {
            Response::json(
                false,
                "Este correo electrónico ya está registrado.",
                [],
                409
            );
        }

        /**
         * --------------------------------------------------------
         * CREAR EMPLEADO
         * --------------------------------------------------------
         */

        try {

            $id = $this->usuarios->crear([
                "nombres" => $nombres,
                "apellidos" => $apellidos,
                "correo" => $correo,
                "password" => $password,

                /*
                 * Nunca permitimos que este endpoint
                 * cree directamente un admin.
                 */
                "rol" => "empleado",

                "empresa_id" => $empresaId,

                "estado" => "activo"
            ]);

            /**
             * ----------------------------------------------------
             * RESPUESTA
             * ----------------------------------------------------
             */

            Response::json(
                true,
                "Empleado creado correctamente.",
                [
                    "user" => [
                        "id" => $id,
                        "nombres" => $nombres,
                        "apellidos" => $apellidos,
                        "correo" => $correo,
                        "rol" => "empleado",
                        "empresa_id" => $empresaId,
                        "estado" => "activo"
                    ]
                ],
                201
            );

        } catch (Throwable $e) {

            Response::json(
                false,
                "No fue posible crear el empleado.",
                [],
                500
            );
        }
    }

    /**
     * ============================================================
     * LOGOUT
     * POST /Api/logout.php
     * ============================================================
     */
    public function logout(): void
    {
        Auth::logout();

        Response::json(
            true,
            "Sesión cerrada correctamente."
        );
    }
}