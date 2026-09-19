<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * bootstrap.php
 *
 * Punto de arranque de todo el backend MVC.
 * Se encarga de:
 *   1. Iniciar la sesión PHP (para login/roles).
 *   2. Cargar la clase de conexión a MySQL.
 *   3. Cargar las clases "core" (Request, Response, Auth).
 *   4. Cargar todos los Modelos.
 *   5. Cargar todos los Controladores.
 *
 * Cada archivo dentro de /Api/*.php solo necesita hacer:
 *     require_once __DIR__ . "/../bootstrap.php";
 * y luego usar el Controlador que le corresponda.
 * ============================================================
 */

session_start();

header("Content-Type: application/json; charset=UTF-8");

/* ============================================================
   CORE / CONFIG
============================================================ */

require_once __DIR__ . "/Config/database.php";
require_once __DIR__ . "/Core/Response.php";
require_once __DIR__ . "/Core/Request.php";
require_once __DIR__ . "/Core/Auth.php";

/* ============================================================
   MODELOS (M)
============================================================ */

require_once __DIR__ . "/Models/UsuarioModel.php";
require_once __DIR__ . "/Models/EmpresaModel.php";
require_once __DIR__ . "/Models/CursoModel.php";
require_once __DIR__ . "/Models/EvaluacionModel.php";
require_once __DIR__ . "/Models/ReporteModel.php";

/* ============================================================
   CONTROLADORES (C)
============================================================ */

require_once __DIR__ . "/Controllers/AuthController.php";
require_once __DIR__ . "/Controllers/UsuarioController.php";
require_once __DIR__ . "/Controllers/EmpresaController.php";
require_once __DIR__ . "/Controllers/CursoController.php";
require_once __DIR__ . "/Controllers/EvaluacionController.php";
require_once __DIR__ . "/Controllers/ReporteController.php";
