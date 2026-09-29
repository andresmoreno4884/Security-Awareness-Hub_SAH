<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / courses.php
 * Front controller: CRUD de cursos (solo administradores).
 * GET / POST / PUT / PATCH / DELETE -> CursoController::handle()
 * ============================================================
 */

require_once __DIR__ . "/../bootstrap.php";

(new CursoController())->handle();
