<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * login.php
 *
 * Página de inicio de sesión, 100% PHP (sin JavaScript).
 * El formulario se envía con POST a esta misma página; PHP
 * valida los datos, verifica la contraseña contra la base de
 * datos y redirige según el rol del usuario.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

// Si ya hay sesión activa, no tiene sentido ver el login de nuevo.
if (Auth::isAuthenticated()) {
    $destino = Auth::isAdmin()
        ? "../Interface_Administrador/panel.php"
        : "../Interface_User/Curse/course.php";
    header("Location: {$destino}");
    exit;
}

$error = "";

if (isset($_GET["error"]) && $_GET["error"] === "permisos") {
    $error = "No tienes permisos para acceder a esa sección.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar()) {

        $error = "Tu formulario expiró, inténtalo de nuevo.";

    } else {

        $email = strtolower(trim($_POST["email"] ?? ""));
        $password = $_POST["password"] ?? "";

        if ($email === "" || $password === "") {

            $error = "Correo y contraseña son obligatorios.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "El correo electrónico no es válido.";

        } else {

            $usuarios = new UsuarioModel();
            $usuario = $usuarios->buscarPorCorreo($email);

            if (!$usuario || !password_verify($password, $usuario["password"])) {

                $error = "Correo o contraseña incorrectos.";

            } elseif ($usuario["estado"] !== "activo") {

                $error = "Tu cuenta está inactiva. Contacta al administrador.";

            } else {

                Auth::login($usuario);

                $destino = $usuario["rol"] === "admin"
                    ? "../Interface_Administrador/panel.php"
                    : "../Interface_User/Curse/course.php";

                header("Location: {$destino}");
                exit;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Security Awareness Hub - Iniciar sesión</title>

    <link rel="stylesheet" href="../authentication/css/login.css">
</head>

<body>

    <div class="login-background"></div>

    <main class="login-container">

        <section class="login-card">

            <!-- =========================================
                 PANEL IZQUIERDO - CARRUSEL
            ========================================== -->
            <section class="login-visual">
                <section class="login-visual">

                    <div class="carousel">

                        <div class="carousel-slide slide-1"></div>
                        <div class="carousel-slide slide-2"></div>
                        <div class="carousel-slide slide-3"></div>

                    </div>

                    <div class="carousel-indicators">

                        <span class="indicator active"></span>
                        <span class="indicator"></span>
                        <span class="indicator"></span>

                    </div>

                </section>
            </section>


            <!-- =========================================
                 PANEL DERECHO - LOGIN
            ========================================== -->
            <section class="login-form-panel">

                <div class="login-form-content">

                    <div class="mobile-logo">
                        <span>SECURITY</span>
                        <strong>AWARENESS</strong>
                    </div>

                    <div class="login-heading">

                        <span class="welcome-small">
                            SECURITY AWARENESS HUB
                        </span>

                        <h1>
                            Bienvenido<span>.</span>
                        </h1>

                        <p class="login-description">
                            Inicia sesión para continuar con tu
                            capacitación en seguridad digital.
                        </p>

                    </div>


                    <form method="POST" action="login.php">

                        <?php Csrf::campo(); ?>

                        <?php if ($error !== ""): ?>
                            <div class="login-message error">
                                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">

                            <label for="email">
                                Correo electrónico
                            </label>

                            <div class="input-wrapper">

                                <span class="input-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                        stroke-linecap="round" stroke-linejoin="round">

                                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                        <polyline points="3,7 12,13 21,7"></polyline>

                                    </svg>
                                </span>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    placeholder="Ingresa tu correo"
                                    value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                    required>

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="password">
                                Contraseña
                            </label>

                            <div class="input-wrapper">

                                <span class="input-icon">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                        stroke-linecap="round" stroke-linejoin="round">

                                        <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                                        <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>

                                    </svg>

                                </span>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Ingresa tu contraseña"
                                    required>

                            </div>

                        </div>


                        <div class="login-options">

                            <label class="remember">

                                <input type="checkbox" id="remember">
                                <span>Recordarme</span>

                            </label>

                            <a href="forgot-password.php" class="forgot-link">
                                ¿Olvidaste tu contraseña?
                            </a>

                        </div>

                        <button type="submit" class="login-button">
                            Iniciar sesión
                        </button>

                    </form>

                    <div class="register-link">
                        <span>¿No tienes una cuenta?</span>
                        <a href="register.php">Crear cuenta</a>
                    </div>

                    <a href="../Landing_Page/index.html" class="back-link">
                        ← Volver al inicio
                    </a>

                </div>

            </section>

        </section>

    </main>

</body>

</html>