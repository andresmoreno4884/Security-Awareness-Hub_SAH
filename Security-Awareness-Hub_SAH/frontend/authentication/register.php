<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * register.php
 * Registro de usuarios, 100% PHP (sin JavaScript).
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

if (Auth::isAuthenticated()) {
    header("Location: " . (Auth::isAdmin() ? "../Interface_Administrador/panel.php" : "../Interface_User/Curse/course.php"));
    exit;
}

$error = "";
$exito = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar()) {

        $error = "Tu formulario expiró, inténtalo de nuevo.";

    } else {

        $nombres = trim($_POST["nombres"] ?? "");
        $apellidos = trim($_POST["apellidos"] ?? "");
        $correo = strtolower(trim($_POST["correo"] ?? ""));
        $password = $_POST["password"] ?? "";
        $confirmPassword = $_POST["confirmPassword"] ?? "";

        if ($nombres === "" || $apellidos === "" || $correo === "" || $password === "" || $confirmPassword === "") {

            $error = "Todos los campos son obligatorios.";

        } elseif (mb_strlen($nombres) < 2) {

            $error = "El nombre debe tener al menos 2 caracteres.";

        } elseif (mb_strlen($apellidos) < 2) {

            $error = "Los apellidos deben tener al menos 2 caracteres.";

        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

            $error = "El correo electrónico no es válido.";

        } elseif ($password !== $confirmPassword) {

            $error = "Las contraseñas no coinciden.";

        } elseif (($errorPassword = Validator::contrasenaSegura($password)) !== null) {

            $error = $errorPassword;

        } else {

            $usuarios = new UsuarioModel();

            if ($usuarios->correoExiste($correo)) {

                $error = "Este correo electrónico ya está registrado.";

            } else {

                try {

                    $usuarios->crear([
                        "nombres" => $nombres,
                        "apellidos" => $apellidos,
                        "correo" => $correo,
                        "password" => $password,
                        "rol" => "usuario",
                        "estado" => "activo"
                    ]);

                    $exito = true;

                } catch (Throwable $e) {

                    $error = "No fue posible registrar el usuario.";
                }
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

    <title>Crear cuenta | Security Awareness</title>

    <link rel="stylesheet" href="css/register.css">
</head>

<body>

    <!-- FONDO -->
    <div class="background"></div>

    <!-- CONTENEDOR PRINCIPAL -->
    <main class="register-container">

        <section class="register-card">

            <!-- LOGO -->
            <div class="logo">
                <span>SECURITY</span>
                <strong>AWARENESS</strong>
            </div>

            <!-- TITULO -->
            <div class="register-header">

                <p class="tag">SECURITY AWARENESS</p>

                <h1>
                    Crear una
                    <span>cuenta</span>
                </h1>

                <p>
                    Regístrate para comenzar tu capacitación
                    en seguridad digital.
                </p>

            </div>

            <?php if ($exito): ?>

                <div class="login-message success">
                    Cuenta creada correctamente. Ya puedes iniciar sesión.
                </div>

                <div class="login-link" style="margin-top:12px;">
                    <a href="login.php">Ir a iniciar sesión →</a>
                </div>

            <?php else: ?>

                <!-- FORMULARIO -->
                <form method="POST" action="register.php">

                    <?php Csrf::campo(); ?>

                    <?php if ($error !== ""): ?>
                        <div class="login-message error">
                            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                        </div>
                    <?php endif; ?>

                    <!-- NOMBRES -->
                    <div class="input-group">

                        <label for="nombres">
                            Nombres
                        </label>

                        <input
                            type="text"
                            id="nombres"
                            name="nombres"
                            placeholder="Ingresa tus nombres"
                            autocomplete="given-name"
                            value="<?= htmlspecialchars($_POST["nombres"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            minlength="2"
                            required
                        >

                    </div>

                    <!-- APELLIDOS -->
                    <div class="input-group">

                        <label for="apellidos">
                            Apellidos
                        </label>

                        <input
                            type="text"
                            id="apellidos"
                            name="apellidos"
                            placeholder="Ingresa tus apellidos"
                            autocomplete="family-name"
                            value="<?= htmlspecialchars($_POST["apellidos"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            minlength="2"
                            required
                        >

                    </div>

                    <!-- CORREO -->
                    <div class="input-group">

                        <label for="correo">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            placeholder="ejemplo@correo.com"
                            autocomplete="email"
                            value="<?= htmlspecialchars($_POST["correo"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            required
                        >

                    </div>

                    <!-- CONTRASEÑA -->
                    <div class="input-group">

                        <label for="password">
                            Contraseña
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Crea una contraseña"
                            autocomplete="new-password"
                            pattern="<?= Validator::PASSWORD_PATTERN ?>"
                            title="Mínimo 8 caracteres, 2 números y 1 carácter especial"
                            required
                        >

                        <p class="password-rules">
                            Mínimo 8 caracteres, incluyendo al menos 2 números
                            y 1 carácter especial (ej: ! @ # $ % &amp;).
                        </p>

                    </div>

                    <!-- CONFIRMAR CONTRASEÑA -->
                    <div class="input-group">

                        <label for="confirmPassword">
                            Confirmar contraseña
                        </label>

                        <input
                            type="password"
                            id="confirmPassword"
                            name="confirmPassword"
                            placeholder="Repite tu contraseña"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                    </div>

                    <!-- BOTÓN -->
                    <button
                        type="submit"
                        class="register-button"
                    >
                        Crear cuenta
                    </button>

                </form>

                <!-- LOGIN -->
                <div class="login-link">

                    <span>
                        ¿Ya tienes una cuenta?
                    </span>

                    <a href="login.php">
                        Iniciar sesión
                    </a>

                </div>

            <?php endif; ?>

            <!-- REGRESAR -->
            
                href="../Landing_Page/index.html"
                class="back-link"
            >
                ← Volver al inicio
            </a>

        </section>

    </main>

</body>

</html>