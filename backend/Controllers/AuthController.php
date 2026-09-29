<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Controllers / AuthController.php
 *
 * Controlador MVC encargado de:
 *  - Iniciar sesión
 *  - Cerrar sesión
 *
 * La creación y administración de usuarios se realiza desde
 * UsuarioController, controlado por el administrador.
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
     * POST /Api/login.php
     *
     * Inicia sesión mediante correo y contraseña.
     */
    public function login(): void
    {
        if (Request::method() !== "POST") {
            Response::json(false, "Método no permitido.", [], 405);
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

        $usuario = $this->usuarios->buscarPorCorreo($correo);

        if (!$usuario) {
            Response::json(
                false,
                "Correo o contraseña incorrectos.",
                [],
                401
            );
        }

        if ($usuario["estado"] !== "activo") {
            Response::json(
                false,
                "Tu cuenta está inactiva. Contacta al administrador.",
                [],
                403
            );
        }

        if (!password_verify($password, $usuario["password"])) {
            Response::json(
                false,
                "Correo o contraseña incorrectos.",
                [],
                401
            );
        }

        /*
         * Guardar la información del usuario en sesión.
         * Auth::login() también guarda empresa_id.
         */
        Auth::login($usuario);

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
                    "empresa_id" => $usuario["empresa_id"] ?? null,
                    "estado" => $usuario["estado"]
                ]
            ]
        );
    }

    /**
     * Registro público deshabilitado.
     *
     * La arquitectura actual establece que los usuarios son
     * creados y administrados por el administrador mediante
     * UsuarioController.
     *
     * Esto evita que una persona pueda crear cuentas por
     * fuera del flujo administrativo.
     */
    public function register(): void
    {
        Response::json(
            false,
            "El registro público está deshabilitado. Contacta al administrador para crear tu cuenta.",
            [],
            403
        );
    }

    /**
     * POST /Api/logout.php
     *
     * Cierra la sesión actual.
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