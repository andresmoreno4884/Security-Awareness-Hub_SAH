<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * logout.php
 * Cierra la sesión y regresa al login. Se accede con un simple
 * enlace (<a href="...logout.php">), no necesita JavaScript.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::logout();

header("Location: login.php");
exit;