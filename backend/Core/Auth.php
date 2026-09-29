<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Core / Auth.php
 *
 * Centraliza todo lo relacionado con la sesión del usuario:
 * - Iniciar sesión
 * - Cerrar sesión
 * - Comprobar autenticación
 * - Comprobar permisos
 * - Obtener usuario autenticado
 * - Obtener empresa asociada
 *
 * Roles actuales:
 * - admin
 * - admin_empresa
 * - empleado
 * ============================================================
 */

class Auth
{
    /**
     * ========================================================
     * INICIAR SESIÓN
     * ========================================================
     */
    public static function login(array $usuario): void
    {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $usuario["id"];
        $_SESSION["nombres"] = $usuario["nombres"];
        $_SESSION["apellidos"] = $usuario["apellidos"];
        $_SESSION["correo"] = $usuario["correo"];
        $_SESSION["rol"] = $usuario["rol"];
        $_SESSION["empresa_id"] = $usuario["empresa_id"] ?? null;
        $_SESSION["estado"] = $usuario["estado"];
        $_SESSION["authenticated"] = true;
    }

    /**
     * ========================================================
     * CERRAR SESIÓN
     * ========================================================
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
     * ========================================================
     * ¿ESTÁ AUTENTICADO?
     * ========================================================
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION["authenticated"])
            && $_SESSION["authenticated"] === true;
    }

    /**
     * ========================================================
     * ¿ES ADMINISTRADOR SAH?
     * ========================================================
     */
    public static function isAdmin(): bool
    {
        return self::isAuthenticated()
            && isset($_SESSION["rol"])
            && $_SESSION["rol"] === "admin";
    }

    /**
     * ========================================================
     * ¿ES ADMINISTRADOR DE EMPRESA?
     * ========================================================
     */
    public static function isAdminEmpresa(): bool
    {
        return self::isAuthenticated()
            && isset($_SESSION["rol"])
            && $_SESSION["rol"] === "admin_empresa";
    }

    /**
     * ========================================================
     * ¿ES EMPLEADO?
     * ========================================================
     */
    public static function isEmpleado(): bool
    {
        return self::isAuthenticated()
            && isset($_SESSION["rol"])
            && $_SESSION["rol"] === "empleado";
    }

    /**
     * ========================================================
     * OBTENER ID DEL USUARIO
     * ========================================================
     *
     * Devuelve 0 si no existe una sesión válida.
     */
    public static function userId(): int
    {
        return (int) ($_SESSION["user_id"] ?? 0);
    }

    /**
     * ========================================================
     * OBTENER ID DE EMPRESA
     * ========================================================
     *
     * Devuelve 0 cuando el usuario no pertenece a una empresa.
     *
     * Ejemplo:
     *
     * admin:
     * empresa_id = 0
     *
     * admin_empresa:
     * empresa_id = 5
     *
     * empleado:
     * empresa_id = 5
     */
    public static function empresaId(): int
    {
        return (int) ($_SESSION["empresa_id"] ?? 0);
    }

    /**
     * ========================================================
     * OBTENER ROL
     * ========================================================
     */
    public static function role(): string
    {
        return (string) ($_SESSION["rol"] ?? "");
    }

    /**
     * ========================================================
     * REQUERIR LOGIN
     * ========================================================
     *
     * Pensado para endpoints de la API.
     *
     * Si no hay sesión:
     * HTTP 401 + JSON.
     */
    public static function requireLogin(): void
    {
        if (!self::isAuthenticated()) {

            Response::json(
                false,
                "No hay una sesión activa.",
                [],
                401
            );
        }
    }

    /**
     * ========================================================
     * REQUERIR ADMIN SAH
     * ========================================================
     *
     * Solo permite el rol "admin".
     */
    public static function requireAdmin(
        string $mensaje = "No tienes permisos para realizar esta acción."
    ): void {

        self::requireLogin();

        if (!self::isAdmin()) {

            Response::json(
                false,
                $mensaje,
                [],
                403
            );
        }
    }

    /**
     * ========================================================
     * GUARDIAS PARA PÁGINAS RENDERIZADAS
     * ========================================================
     *
     * Estas funciones NO devuelven JSON.
     *
     * Si no existe sesión:
     * → redirigen al login.
     *
     * Si el usuario no tiene permisos:
     * → redirigen al login con error.
     */

    public static function requireLoginView(
        string $redirectTo = "/frontend/authentication/login.html"
    ): void {

        if (!self::isAuthenticated()) {

            header(
                "Location: {$redirectTo}"
            );

            exit;
        }
    }

    /**
     * ========================================================
     * REQUERIR ADMIN EN VISTA
     * ========================================================
     */
    public static function requireAdminView(
        string $redirectTo = "/frontend/authentication/login.html"
    ): void {

        self::requireLoginView($redirectTo);

        if (!self::isAdmin()) {

            header(
                "Location: {$redirectTo}?error=permisos"
            );

            exit;
        }
    }
}