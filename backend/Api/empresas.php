<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / empresas.php
 * Front controller: CRUD de empresas (solo administradores).
 * GET / POST / PUT / PATCH / DELETE -> EmpresaController::handle()
 * ============================================================
 */

require_once __DIR__ . "/../bootstrap.php";

(new EmpresaController())->handle();
