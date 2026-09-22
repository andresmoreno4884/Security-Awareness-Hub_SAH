<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / login.php
 *
 * Punto de entrada público (front controller) del login.
 * Solo arranca el framework MVC y delega todo el trabajo al
 * AuthController. Aquí NO va lógica de negocio ni SQL.
 * ============================================================
 */

require_once __DIR__ . "/../bootstrap.php";

(new AuthController())->login();
