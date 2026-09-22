<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Core / Auth.php
 *
 * Manejo de autenticación y autorización mediante sesiones.
 *
 * Roles:
 * - admin
 * - admin_empresa
 * - empleado
 * ============================================================
 */

class Auth
{
    /**
     * Inicia la sesión del usuario.
     */
    public static function login(array $usuario): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION["user_id"] = (int) $usuario["id"];

        $_SESSION["nombres"] = $usuario["nombres"];
        $_SESSION["apellidos"] = $usuario["apellidos"];
        $_SESSION["correo"] = $usuario["correo"];

        $_SESSION["rol"] = $usuario["rol"];

        /*
         * empresa_id puede ser NULL para el administrador
         * general de SAH.
         */
        $_SESSION["empresa_id"] = isset($usuario["empresa_id"])
            ? $usuario["empresa_id"]
            : null;

        $_SESSION["estado"] = $usuario["estado"];

        $_SESSION["authenticated"] = true;
    }

    /**
     * Cierra la sesión.
     */
    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

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
     * Verifica si existe una sesión autenticada.
     */
    public static function isAuthenticated(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return isset($_SESSION["authenticated"])
            && $_SESSION["authenticated"] === true
            && isset($_SESSION["user_id"]);
    }

    /**
     * Verifica si el usuario es administrador general.
     */
    public static function isAdmin(): bool
    {
        return self::isAuthenticated()
            && ($_SESSION["rol"] ?? "") === "admin";
    }

    /**
     * Verifica si el usuario es administrador de empresa.
     */
    public static function isAdminEmpresa(): bool
    {
        return self::isAuthenticated()
            && ($_SESSION["rol"] ?? "") === "admin_empresa";
    }

    /**
     * Verifica si el usuario es empleado.
     */
    public static function isEmpleado(): bool
    {
        return self::isAuthenticated()
            && ($_SESSION["rol"] ?? "") === "empleado";
    }

    /**
     * Devuelve el rol actual.
     */
    public static function role(): ?string
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return $_SESSION["rol"] ?? null;
    }

    /**
     * Devuelve el ID del usuario autenticado.
     */
    public static function userId(): ?int
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return isset($_SESSION["user_id"])
            ? (int) $_SESSION["user_id"]
            : null;
    }

    /**
     * Devuelve el ID de la empresa asociada.
     *
     * Para admin general normalmente será NULL.
     */
    public static function empresaId(): ?int
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        if (
            !isset($_SESSION["empresa_id"])
            || $_SESSION["empresa_id"] === null
            || $_SESSION["empresa_id"] === ""
        ) {
            return null;
        }

        return (int) $_SESSION["empresa_id"];
    }

    /**
     * Obliga a estar autenticado.
     */
    public static function requireLogin(): void
    {
        if (!self::isAuthenticated()) {
            Response::json(
                false,
                "Debes iniciar sesión.",
                [],
                401
            );
        }
    }

    /**
     * Solo permite admin general.
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            Response::json(
                false,
                "No tienes permisos para realizar esta acción.",
                [],
                403
            );
        }
    }

    /**
     * Solo permite admin de empresa.
     */
    public static function requireAdminEmpresa(): void
    {
        self::requireLogin();

        if (!self::isAdminEmpresa()) {
            Response::json(
                false,
                "No tienes permisos para realizar esta acción.",
                [],
                403
            );
        }
    }

    /**
     * Permite admin general o admin_empresa.
     */
    public static function requireAdminSistema(): void
    {
        self::requireLogin();

        if (
            !self::isAdmin()
            && !self::isAdminEmpresa()
        ) {
            Response::json(
                false,
                "No tienes permisos para realizar esta acción.",
                [],
                403
            );
        }
    }

    /**
     * Protección para vistas.
     *
     * Si no hay sesión, redirige al login.
     */
    public static function requireLoginView(
        string $loginUrl = "../authentication/login.php"
    ): void {
        if (!self::isAuthenticated()) {
            header("Location: " . $loginUrl);
            exit;
        }
    }

    /**
     * Vista exclusiva para admin general.
     */
    public static function requireAdminView(
        string $loginUrl = "../authentication/login.php"
    ): void {
        self::requireLoginView($loginUrl);

        if (!self::isAdmin()) {
            http_response_code(403);

            echo "Acceso denegado.";
            exit;
        }
    }

    /**
     * Vista exclusiva para admin_empresa.
     */
    public static function requireAdminEmpresaView(
        string $loginUrl = "../authentication/login.php"
    ): void {
        self::requireLoginView($loginUrl);

        if (!self::isAdminEmpresa()) {
            http_response_code(403);

            echo "Acceso denegado.";
            exit;
        }
    }

    /**
     * Vista para admin general o admin_empresa.
     */
    public static function requireAdminSistemaView(
        string $loginUrl = "../authentication/login.php"
    ): void {
        self::requireLoginView($loginUrl);

        if (
            !self::isAdmin()
            && !self::isAdminEmpresa()
        ) {
            http_response_code(403);

            echo "Acceso denegado.";
            exit;
        }
    }
}