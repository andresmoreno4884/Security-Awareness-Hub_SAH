<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * PRUEBA DE CONEXIÓN
 * ============================================================
 */

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../Config/database.php";

try {

    $pdo = Database::connect();

    $stmt = $pdo->query("SELECT DATABASE() AS database_name");

    $result = $stmt->fetch();

    echo json_encode([
        "success" => true,
        "message" => "Conexión con MySQL exitosa",
        "database" => $result["database_name"]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Error al conectar con MySQL.",
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}