<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * UsuarioController.php
 * ============================================================
 *
 * Controlador de gestión de usuarios.
 *
 * Roles actuales:
 * - admin
 * - admin_empresa
 * - empleado
 *
 * Funciones:
 * - Listar usuarios
 * - Crear usuarios
 * - Editar usuarios
 * - Cambiar estado
 * - Eliminar usuarios
 * - Validar empresa según el rol
 *
 * ============================================================
 */

class UsuarioController
{
    private UsuarioModel $usuarios;

    /**
     * Constructor
     */
    public function __construct()
    {
        /*
         * Solo un administrador del sistema puede
         * administrar usuarios mediante esta API.
         */
        Auth::requireAdmin(
            "No tienes permisos para administrar usuarios."
        );

        $this->usuarios = new UsuarioModel();
    }

    /**
     * Punto de entrada del controlador.
     */
    public function handle(): void
    {
        switch (Request::method()) {

            case "GET":
                $this->index();
                break;

            case "POST":
                $this->store();
                break;

            case "PUT":
                $this->update();
                break;

            case "PATCH":
                $this->patchEstado();
                break;

            case "DELETE":
                $this->destroy();
                break;

            default:
                Response::json(
                    false,
                    "Método HTTP no permitido.",
                    [],
                    405
                );
        }
    }

    /**
     * ========================================================
     * LISTAR USUARIOS
     * ========================================================
     */
    private function index(): void
    {
        $search = trim(
            Request::query("search", "")
        );

        $estado = trim(
            Request::query("estado", "")
        );

        /*
         * Solo se aceptan estados válidos.
         */
        if (
            $estado !== ""
            && !in_array(
                $estado,
                ["activo", "inactivo"],
                true
            )
        ) {
            Response::json(
                false,
                "El estado seleccionado no es válido.",
                [],
                422
            );
        }

        $usuarios = $this->usuarios->listar(
            $search,
            $estado
        );

        Response::json(
            true,
            "Usuarios obtenidos correctamente.",
            [
                "usuarios" => $usuarios,
                "total" => count($usuarios)
            ]
        );
    }

    /**
     * ========================================================
     * CREAR USUARIO
     * ========================================================
     */
    private function store(): void
    {
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

        $rol = trim(
            Request::input(
                "rol",
                "empleado"
            )
        );

        $estado = trim(
            Request::input(
                "estado",
                "activo"
            )
        );

        $empresaId = (int) Request::input(
            "empresa_id",
            0
        );

        /*
         * ------------------------------------------------------
         * VALIDACIONES BÁSICAS
         * ------------------------------------------------------
         */

        if (
            $nombres === ""
            || $apellidos === ""
            || $correo === ""
            || $password === ""
        ) {
            Response::json(
                false,
                "Todos los campos obligatorios deben completarse.",
                [],
                422
            );
        }

        if (
            !filter_var(
                $correo,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            Response::json(
                false,
                "El correo electrónico no es válido.",
                [],
                422
            );
        }

        /*
         * Roles actuales de SAH.
         */
        if (
            !in_array(
                $rol,
                [
                    "empleado",
                    "admin_empresa",
                    "admin"
                ],
                true
            )
        ) {
            Response::json(
                false,
                "El rol seleccionado no es válido.",
                [],
                422
            );
        }

        /*
         * Estados actuales.
         */
        if (
            !in_array(
                $estado,
                [
                    "activo",
                    "inactivo"
                ],
                true
            )
        ) {
            Response::json(
                false,
                "El estado seleccionado no es válido.",
                [],
                422
            );
        }

        /*
         * Validación de contraseña.
         *
         * Se utiliza el mismo Validator que utiliza
         * el resto del sistema.
         */
        $errorPassword =
            Validator::contrasenaSegura(
                $password
            );

        if ($errorPassword !== null) {
            Response::json(
                false,
                $errorPassword,
                [],
                422
            );
        }

        /*
         * ------------------------------------------------------
         * VALIDAR EMPRESA SEGÚN EL ROL
         * ------------------------------------------------------
         */

        if ($rol === "empleado") {

            if ($empresaId <= 0) {
                Response::json(
                    false,
                    "Debes seleccionar una empresa para el empleado.",
                    [],
                    422
                );
            }
        }

        elseif ($rol === "admin_empresa") {

            if ($empresaId <= 0) {
                Response::json(
                    false,
                    "Debes seleccionar una empresa para el administrador de empresa.",
                    [],
                    422
                );
            }
        }

        elseif ($rol === "admin") {

            /*
             * El administrador SAH no pertenece
             * a una empresa cliente.
             */
            $empresaId = 0;
        }

        /*
         * ------------------------------------------------------
         * VERIFICAR EMPRESA
         * ------------------------------------------------------
         *
         * El UsuarioModel actual dispone de listarEmpresas().
         * Se utiliza para comprobar que la empresa enviada
         * realmente existe.
         */

        if ($empresaId > 0) {

            $empresas = $this->usuarios->listarEmpresas();

            $empresaEncontrada = false;

            foreach ($empresas as $empresa) {

                if (
                    (int) $empresa["id"]
                    === $empresaId
                ) {
                    $empresaEncontrada = true;
                    break;
                }
            }

            if (!$empresaEncontrada) {
                Response::json(
                    false,
                    "La empresa seleccionada no es válida o no está activa.",
                    [],
                    422
                );
            }
        }

        /*
         * ------------------------------------------------------
         * CORREO DUPLICADO
         * ------------------------------------------------------
         */

        if (
            $this->usuarios->correoExiste(
                $correo
            )
        ) {
            Response::json(
                false,
                "Ya existe un usuario con ese correo.",
                [],
                409
            );
        }

        /*
         * ------------------------------------------------------
         * CREAR
         * ------------------------------------------------------
         */

        try {

            $id = $this->usuarios->crear([
                "nombres" => $nombres,
                "apellidos" => $apellidos,
                "correo" => $correo,
                "password" => $password,
                "rol" => $rol,
                "empresa_id" =>
                    $empresaId > 0
                        ? $empresaId
                        : null,
                "estado" => $estado
            ]);

            Response::json(
                true,
                "Usuario creado correctamente.",
                [
                    "id" => $id
                ],
                201
            );

        } catch (Throwable $e) {

            Response::json(
                false,
                "No fue posible crear el usuario.",
                [],
                500
            );
        }
    }

    /**
     * ========================================================
     * EDITAR USUARIO
     * ========================================================
     */
    private function update(): void
    {
        $id = (int) Request::input(
            "id",
            0
        );

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

        $rol = trim(
            Request::input(
                "rol",
                "empleado"
            )
        );

        $estado = trim(
            Request::input(
                "estado",
                "activo"
            )
        );

        $empresaId = (int) Request::input(
            "empresa_id",
            0
        );

        /*
         * ------------------------------------------------------
         * VALIDACIONES BÁSICAS
         * ------------------------------------------------------
         */

        if (
            $id <= 0
            || $nombres === ""
            || $apellidos === ""
            || $correo === ""
        ) {
            Response::json(
                false,
                "Los datos del usuario son obligatorios.",
                [],
                422
            );
        }

        if (
            !filter_var(
                $correo,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            Response::json(
                false,
                "El correo electrónico no es válido.",
                [],
                422
            );
        }

        if (
            !in_array(
                $rol,
                [
                    "empleado",
                    "admin_empresa",
                    "admin"
                ],
                true
            )
        ) {
            Response::json(
                false,
                "El rol seleccionado no es válido.",
                [],
                422
            );
        }

        if (
            !in_array(
                $estado,
                [
                    "activo",
                    "inactivo"
                ],
                true
            )
        ) {
            Response::json(
                false,
                "El estado seleccionado no es válido.",
                [],
                422
            );
        }

        /*
         * ------------------------------------------------------
         * VERIFICAR QUE EL USUARIO EXISTE
         * ------------------------------------------------------
         */

        $usuarioActual =
            $this->usuarios->buscarPorId($id);

        if (!$usuarioActual) {
            Response::json(
                false,
                "El usuario no existe.",
                [],
                404
            );
        }

        /*
         * ------------------------------------------------------
         * VALIDAR EMPRESA SEGÚN EL ROL
         * ------------------------------------------------------
         */

        if ($rol === "empleado") {

            if ($empresaId <= 0) {
                Response::json(
                    false,
                    "Debes seleccionar una empresa para el empleado.",
                    [],
                    422
                );
            }
        }

        elseif ($rol === "admin_empresa") {

            if ($empresaId <= 0) {
                Response::json(
                    false,
                    "Debes seleccionar una empresa para el administrador de empresa.",
                    [],
                    422
                );
            }
        }

        elseif ($rol === "admin") {

            /*
             * El administrador SAH no pertenece
             * a una empresa cliente.
             */
            $empresaId = 0;
        }

        /*
         * ------------------------------------------------------
         * VERIFICAR EMPRESA
         * ------------------------------------------------------
         */

        if ($empresaId > 0) {

            $empresas =
                $this->usuarios->listarEmpresas();

            $empresaEncontrada = false;

            foreach ($empresas as $empresa) {

                if (
                    (int) $empresa["id"]
                    === $empresaId
                ) {
                    $empresaEncontrada = true;
                    break;
                }
            }

            if (!$empresaEncontrada) {
                Response::json(
                    false,
                    "La empresa seleccionada no es válida o no está activa.",
                    [],
                    422
                );
            }
        }

        /*
         * ------------------------------------------------------
         * CORREO DUPLICADO
         * ------------------------------------------------------
         */

        if (
            $this->usuarios->correoExiste(
                $correo,
                $id
            )
        ) {
            Response::json(
                false,
                "El correo ya pertenece a otro usuario.",
                [],
                409
            );
        }

        /*
         * ------------------------------------------------------
         * NO CAMBIAR EL ROL DE LA PROPIA CUENTA
         * ------------------------------------------------------
         *
         * Evita que el administrador actual se quite
         * accidentalmente sus propios permisos.
         */

        if (
            $id === Auth::userId()
            && $rol !== "admin"
        ) {
            Response::json(
                false,
                "No puedes quitarte el rol de administrador de tu propia cuenta.",
                [],
                403
            );
        }

        /*
         * ------------------------------------------------------
         * ACTUALIZAR
         * ------------------------------------------------------
         */

        try {

            $this->usuarios->actualizar(
                $id,
                [
                    "nombres" => $nombres,
                    "apellidos" => $apellidos,
                    "correo" => $correo,
                    "rol" => $rol,
                    "empresa_id" =>
                        $empresaId > 0
                            ? $empresaId
                            : null,
                    "estado" => $estado
                ]
            );

            Response::json(
                true,
                "Usuario actualizado correctamente.",
                [
                    "id" => $id
                ]
            );

        } catch (Throwable $e) {

            Response::json(
                false,
                "No fue posible actualizar el usuario.",
                [],
                500
            );
        }
    }

    /**
     * ========================================================
     * CAMBIAR ESTADO
     * ========================================================
     */
    private function patchEstado(): void
    {
        $id = (int) Request::input(
            "id",
            0
        );

        $estado = trim(
            Request::input(
                "estado",
                ""
            )
        );

        /*
         * Validar ID.
         */
        if ($id <= 0) {
            Response::json(
                false,
                "El ID del usuario no es válido.",
                [],
                422
            );
        }

        /*
         * Validar estado.
         */
        if (
            !in_array(
                $estado,
                [
                    "activo",
                    "inactivo"
                ],
                true
            )
        ) {
            Response::json(
                false,
                "El estado seleccionado no es válido.",
                [],
                422
            );
        }

        /*
         * No permitir desactivar
         * la propia cuenta.
         */
        if (
            $id === Auth::userId()
            && $estado === "inactivo"
        ) {
            Response::json(
                false,
                "No puedes desactivar tu propia cuenta.",
                [],
                403
            );
        }

        /*
         * Verificar usuario.
         */
        if (
            !$this->usuarios->buscarPorId($id)
        ) {
            Response::json(
                false,
                "El usuario no existe.",
                [],
                404
            );
        }

        try {

            $this->usuarios->cambiarEstado(
                $id,
                $estado
            );

            Response::json(
                true,
                $estado === "activo"
                    ? "Usuario activado correctamente."
                    : "Usuario desactivado correctamente.",
                [
                    "id" => $id,
                    "estado" => $estado
                ]
            );

        } catch (Throwable $e) {

            Response::json(
                false,
                "No fue posible cambiar el estado del usuario.",
                [],
                500
            );
        }
    }

    /**
     * ========================================================
     * ELIMINAR USUARIO
     * ========================================================
     */
    private function destroy(): void
    {
        $id = (int) Request::input(
            "id",
            0
        );

        if ($id <= 0) {
            Response::json(
                false,
                "El ID del usuario no es válido.",
                [],
                422
            );
        }

        /*
         * No permitir eliminar la propia cuenta.
         */
        if ($id === Auth::userId()) {
            Response::json(
                false,
                "No puedes eliminar tu propia cuenta.",
                [],
                403
            );
        }

        /*
         * Verificar existencia.
         */
        if (
            !$this->usuarios->buscarPorId($id)
        ) {
            Response::json(
                false,
                "El usuario no existe.",
                [],
                404
            );
        }

        try {

            $this->usuarios->eliminar($id);

            Response::json(
                true,
                "Usuario eliminado correctamente.",
                [
                    "id" => $id
                ]
            );

        } catch (PDOException $e) {

            /*
             * 23000 normalmente indica
             * una restricción de integridad.
             */
            if (
                $e->getCode() === "23000"
            ) {
                Response::json(
                    false,
                    "No se puede eliminar el usuario porque tiene información relacionada.",
                    [],
                    409
                );
            }

            Response::json(
                false,
                "No fue posible eliminar el usuario.",
                [],
                500
            );

        } catch (Throwable $e) {

            Response::json(
                false,
                "No fue posible eliminar el usuario.",
                [],
                500
            );
        }
    }
}