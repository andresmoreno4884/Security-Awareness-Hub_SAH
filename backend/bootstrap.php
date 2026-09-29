<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

// Configuración
require_once __DIR__ . "/Config/database.php";

// Core
require_once __DIR__ . "/Core/Response.php";
require_once __DIR__ . "/Core/Request.php";
require_once __DIR__ . "/Core/Auth.php";
require_once __DIR__ . "/Core/Validator.php";

// Models
require_once __DIR__ . "/Models/UsuarioModel.php";
require_once __DIR__ . "/Models/EmpresaModel.php";
require_once __DIR__ . "/Models/CursoModel.php";
require_once __DIR__ . "/Models/EvaluacionModel.php";
require_once __DIR__ . "/Models/ReporteModel.php";
require_once __DIR__ . "/Models/AsignacionModel.php";

// Controllers
require_once __DIR__ . "/Controllers/AuthController.php";
require_once __DIR__ . "/Controllers/UsuarioController.php";
require_once __DIR__ . "/Controllers/EmpresaController.php";
require_once __DIR__ . "/Controllers/CursoController.php";
require_once __DIR__ . "/Controllers/EvaluacionController.php";
require_once __DIR__ . "/Controllers/ReporteController.php";
require_once __DIR__ . "/Controllers/AsignacionController.php";