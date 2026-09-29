<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / test.php
 * Script de diagnóstico: solo comprueba la conexión a MySQL.
 * No es un recurso CRUD, por eso no usa un Controlador.
 * ============================================================
 */

require_once __DIR__ . "/../bootstrap.php";

try {

    $pdo = Database::connect();

    $stmt = $pdo->query("SELECT DATABASE() AS database_name");

    $result = $stmt->fetch();

    Response::json(true, "Conexión con MySQL exitosa", [
        "database" => $result["database_name"]
    ]);

} catch (Throwable $e) {

    Response::json(false, "Error al conectar con MySQL.", [], 500);
}
