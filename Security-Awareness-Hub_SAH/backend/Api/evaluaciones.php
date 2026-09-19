<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / evaluaciones.php
 * Front controller: CRUD de evaluaciones (solo administradores).
 * GET / POST / PUT / PATCH / DELETE -> EvaluacionController::handle()
 * ============================================================
 */

require_once __DIR__ . "/../bootstrap.php";

(new EvaluacionController())->handle();
