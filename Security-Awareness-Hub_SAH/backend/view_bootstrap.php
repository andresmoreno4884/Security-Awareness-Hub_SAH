<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * view_bootstrap.php
 *
 * Arranque para las páginas que RENDERIZAN HTML directamente en
 * PHP (el panel, los formularios, el login, etc.), a diferencia
 * de bootstrap.php que es para la API JSON.
 *
 * No envía header JSON: cada página decide su propio HTML.
 * ============================================================
 */

session_start();

require_once __DIR__ . "/Config/database.php";
require_once __DIR__ . "/Core/Auth.php";
require_once __DIR__ . "/Core/Csrf.php";
require_once __DIR__ . "/Core/Validator.php";

require_once __DIR__ . "/Models/UsuarioModel.php";
require_once __DIR__ . "/Models/EmpresaModel.php";
require_once __DIR__ . "/Models/CursoModel.php";
require_once __DIR__ . "/Models/EvaluacionModel.php";
require_once __DIR__ . "/Models/ReporteModel.php";