<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / register.php
 *
 * Punto de entrada público (front controller) del registro.
 * Mantiene las cabeceras CORS que ya usaba el proyecto para
 * pruebas locales con Live Server (127.0.0.1:5501).
 * ============================================================
 */

header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

require_once __DIR__ . "/../bootstrap.php";

(new AuthController())->register();
