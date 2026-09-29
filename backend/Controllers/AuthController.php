<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Controllers / AuthController.php
 *
 * Esta es la "C" (CONTROLADOR) del patrón MVC para todo lo
 * relacionado con autenticación: iniciar sesión, registrarse
 * y cerrar sesión.
 *
 * Un Controlador:
 *  1. Lee y valida los datos que llegan del cliente (Request).
 *  2. Le pide al Modelo que consulte/modifique la base de datos.
 *  3. Usa Response para devolver el resultado en JSON (la "vista").
 * Nunca contiene SQL directamente: eso es trabajo del Modelo.
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
     */
    public function login(): void
    {
        if (Request::method() !== "POST") {
            Response::json(false, "Método no permitido.", [], 405);
        }

        $correo = strtolower(trim(Request::input("email", Request::input("correo", ""))));
        $password = Request::input("password", "");

        if ($correo === "" || $password === "") {
            Response::json(false, "Correo y contraseña son obligatorios.", [], 400);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Response::json(false, "El correo electrónico no es válido.", [], 400);
        }

        $usuario = $this->usuarios->buscarPorCorreo($correo);

        if (!$usuario) {
            Response::json(false, "Correo o contraseña incorrectos.", [], 401);
        }

        if ($usuario["estado"] !== "activo") {
            Response::json(false, "Tu cuenta está inactiva. Contacta al administrador.", [], 403);
        }

        if (!password_verify($password, $usuario["password"])) {
            Response::json(false, "Correo o contraseña incorrectos.", [], 401);
        }

        Auth::login($usuario);

        Response::json(true, "Inicio de sesión correcto.", [
            "user" => [
                "id" => $usuario["id"],
                "nombres" => $usuario["nombres"],
                "apellidos" => $usuario["apellidos"],
                "correo" => $usuario["correo"],
                "rol" => $usuario["rol"],
                "estado" => $usuario["estado"]
            ]
        ]);
    }

    /**
     * POST /Api/register.php
     * Registro público: siempre crea un usuario con rol "usuario".
     */
    public function register(): void
    {
        if (Request::method() === "OPTIONS") {
            http_response_code(200);
            exit;
        }

        if (Request::method() !== "POST") {
            Response::json(false, "Método no permitido.", [], 405);
        }

        $nombres = trim(Request::input("nombres", ""));
        $apellidos = trim(Request::input("apellidos", ""));
        $correo = strtolower(trim(Request::input("correo", "")));
        $password = Request::input("password", "");

        if ($nombres === "" || $apellidos === "" || $correo === "" || $password === "") {
            Response::json(false, "Todos los campos son obligatorios.", [], 400);
        }

        if (mb_strlen($nombres) < 2) {
            Response::json(false, "El nombre debe tener al menos 2 caracteres.", [], 400);
        }

        if (mb_strlen($apellidos) < 2) {
            Response::json(false, "Los apellidos deben tener al menos 2 caracteres.", [], 400);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Response::json(false, "El correo electrónico no es válido.", [], 400);
        }

        if (strlen($password) < 8) {
            Response::json(false, "La contraseña debe tener mínimo 8 caracteres.", [], 400);
        }

        if ($this->usuarios->correoExiste($correo)) {
            Response::json(false, "Este correo electrónico ya está registrado.", [], 409);
        }

        try {

            $id = $this->usuarios->crear([
                "nombres" => $nombres,
                "apellidos" => $apellidos,
                "correo" => $correo,
                "password" => $password,
                "rol" => "usuario",
                "estado" => "activo"
            ]);

            Response::json(true, "Usuario registrado correctamente.", [
                "user" => [
                    "id" => $id,
                    "nombres" => $nombres,
                    "apellidos" => $apellidos,
                    "correo" => $correo,
                    "rol" => "usuario",
                    "estado" => "activo"
                ]
            ], 201);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible registrar el usuario.", [], 500);
        }
    }

    /**
     * POST /Api/logout.php
     */
    public function logout(): void
    {
        Auth::logout();

        Response::json(true, "Sesión cerrada correctamente.");
    }
}
