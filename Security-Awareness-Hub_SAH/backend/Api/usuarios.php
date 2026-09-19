<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / usuarios.php
 * Front controller: CRUD de usuarios (solo administradores).
 * GET / POST / PUT / PATCH / DELETE -> UsuarioController::handle()
 * ============================================================
 */

require_once __DIR__ . "/../bootstrap.php";

(new UsuarioController())->handle();
