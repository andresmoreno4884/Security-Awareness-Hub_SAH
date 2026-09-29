<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / reportes.php
 * Front controller: módulo de Reportes (solo lectura, admin).
 * GET -> ReporteController::handle()
 * ============================================================
 */

require_once __DIR__ . "/../bootstrap.php";

(new ReporteController())->handle();