<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Core / Auth.php
 *
 * Centraliza todo lo relacionado con la sesión del usuario:
 * iniciar sesión, cerrar sesión y comprobar permisos.
 * Los Controladores llaman a Auth::requireAdmin(), por ejemplo,
 * en lugar de repetir el mismo bloque de código en cada archivo.
 * ============================================================
 */

class Auth
{
    /**
     * Guarda los datos del usuario autenticado en la sesión.
     */
    public static function login(array $usuario): void
    {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $usuario["id"];
        $_SESSION["nombres"] = $usuario["nombres"];
        $_SESSION["apellidos"] = $usuario["apellidos"];
        $_SESSION["correo"] = $usuario["correo"];
        $_SESSION["rol"] = $usuario["rol"];
        $_SESSION["estado"] = $usuario["estado"];
        $_SESSION["authenticated"] = true;
    }

    /**
     * Elimina todos los datos de sesión y la cookie asociada.
     */
    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * ¿Hay una sesión activa?
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION["authenticated"]) && $_SESSION["authenticated"] === true;
    }

    /**
     * ¿El usuario autenticado tiene rol "admin"?
     */
    public static function isAdmin(): bool
    {
        return self::isAuthenticated()
            && isset($_SESSION["rol"])
            && $_SESSION["rol"] === "admin";
    }

    /**
     * ID del usuario autenticado (0 si no hay sesión).
     */
    public static function userId(): int
    {
        return (int) ($_SESSION["user_id"] ?? 0);
    }

    /**
     * Corta la ejecución con 401 si no hay sesión activa.
     */
    public static function requireLogin(): void
    {
        if (!self::isAuthenticated()) {
            Response::json(false, "No hay una sesión activa.", [], 401);
        }
    }

    /**
     * Corta la ejecución con 401/403 si el usuario no es admin.
     */
    public static function requireAdmin(string $mensaje = "No tienes permisos para realizar esta acción."): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            Response::json(false, $mensaje, [], 403);
        }
    }

    /**
     * ============================================================
     * GUARDIAS PARA PÁGINAS RENDERIZADAS (no API JSON)
     *
     * A diferencia de requireLogin()/requireAdmin() (pensadas para
     * la API, que responden JSON), estas dos se usan al inicio de
     * cada página PHP del panel: si no hay permisos, REDIRIGEN al
     * login en vez de imprimir JSON. Así ningún archivo del panel
     * puede verse sin haber iniciado sesión, ni un usuario normal
     * puede entrar a una vista de administrador solo con la URL.
     * ============================================================
     */

    public static function requireLoginView(string $redirectTo = "/frontend/authentication/login.html"): void
    {
        if (!self::isAuthenticated()) {
            header("Location: {$redirectTo}");
            exit;
        }
    }

    public static function requireAdminView(string $redirectTo = "/frontend/authentication/login.html"): void
    {
        self::requireLoginView($redirectTo);

        if (!self::isAdmin()) {
            header("Location: {$redirectTo}?error=permisos");
            exit;
        }
    }
}