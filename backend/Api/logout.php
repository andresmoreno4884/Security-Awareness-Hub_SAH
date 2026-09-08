<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * LOGOUT
 * PHP + JSON
 * ============================================================
 */

header("Content-Type: application/json; charset=UTF-8");

session_start();

/**
 * ============================================================
 * VACIAR VARIABLES DE SESIÓN
 * ============================================================
 */

$_SESSION = [];

/**
 * ============================================================
 * ELIMINAR COOKIE DE SESIÓN
 * ============================================================
 */

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

/**
 * ============================================================
 * DESTRUIR SESIÓN
 * ============================================================
 */

session_destroy();

/**
 * ============================================================
 * RESPUESTA
 * ============================================================
 */

echo json_encode([
    "success" => true,
    "message" => "Sesión cerrada correctamente."
], JSON_UNESCAPED_UNICODE);

exit;