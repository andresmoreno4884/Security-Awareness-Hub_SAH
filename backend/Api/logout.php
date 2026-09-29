<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / logout.php
 * Punto de entrada público (front controller) del logout.
 * ============================================================
 */

require_once __DIR__ . "/../bootstrap.php";

(new AuthController())->logout();
