<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * forgot-password.php
 *
 * Recuperación de contraseña en dos pasos, 100% PHP:
 *   1) El usuario escribe su correo. Si existe, avanza al paso 2.
 *   2) El usuario escribe la nueva contraseña (con la misma
 *      política de seguridad que el registro) y se actualiza.
 *
 * NOTA ACADÉMICA: al no haber servidor de correo configurado en
 * este proyecto, no se envía un enlace por email (como haría un
 * sistema en producción); el correo solo se usa para confirmar
 * que la cuenta existe antes de dejar cambiar la contraseña.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

$usuarios = new UsuarioModel();

$paso = "correo";
$error = "";
$exito = false;
$correoValidado = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !Csrf::validar()) {

    $error = "Tu formulario expiró, inténtalo de nuevo.";

} elseif (isset($_POST["accion"]) && $_POST["accion"] === "buscar") {

    $email = strtolower(trim($_POST["email"] ?? ""));

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Ingresa un correo electrónico válido.";

    } else {

        $usuario = $usuarios->buscarPorCorreo($email);

        if (!$usuario) {

            $error = "No encontramos ninguna cuenta con ese correo.";

        } else {

            $paso = "password";
            $correoValidado = $email;
        }
    }

} elseif (isset($_POST["accion"]) && $_POST["accion"] === "cambiar") {

    $correoValidado = strtolower(trim($_POST["correo"] ?? ""));
    $nueva = $_POST["newPassword"] ?? "";
    $confirmar = $_POST["confirmNewPassword"] ?? "";

    if (!$usuarios->buscarPorCorreo($correoValidado)) {

        $error = "No encontramos ninguna cuenta con ese correo.";
        $paso = "correo";

    } elseif ($nueva !== $confirmar) {

        $error = "Las contraseñas no coinciden.";
        $paso = "password";

    } elseif (($errorPassword = Validator::contrasenaSegura($nueva)) !== null) {

        $error = $errorPassword;
        $paso = "password";

    } else {

        $usuarios->actualizarPasswordPorCorreo($correoValidado, $nueva);
        $exito = true;
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Recuperar contraseña | Security Awareness</title>

    <link rel="stylesheet" href="css/forgot-password.css">
    <link rel="stylesheet" href="css/login.css">

</head>

<body>

    <div class="background"></div>

    <main class="recovery-container">

        <section class="recovery-card">

            <div class="logo">
                <span>SECURITY</span>
                <strong>AWARENESS</strong>
            </div>

            <div class="recovery-header">

                <p class="tag">
                    SECURITY AWARENESS
                </p>

                <h1>
                    Recupera tu
                    <span>contraseña</span>
                </h1>

                <p>
                    <?= $paso === "password"
                        ? "Escribe tu nueva contraseña."
                        : "Ingresa el correo electrónico con el que registraste tu cuenta." ?>
                </p>

            </div>

            <?php if ($exito): ?>

                <div class="login-message success">
                    Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.
                </div>

                <div class="login-link" style="margin-top:12px;">
                    <a href="login.php">Ir a iniciar sesión →</a>
                </div>

            <?php elseif ($paso === "password"): ?>

                <!-- PASO 2: NUEVA CONTRASEÑA -->
                <form method="POST" action="forgot-password.php">

                    <?php Csrf::campo(); ?>
                    <input type="hidden" name="accion" value="cambiar">
                    <input type="hidden" name="correo" value="<?= htmlspecialchars($correoValidado, ENT_QUOTES, "UTF-8") ?>">

                    <?php if ($error !== ""): ?>
                        <div class="login-message error">
                            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                        </div>
                    <?php endif; ?>

                    <div class="input-group">
                        <label for="newPassword">Nueva contraseña</label>
                        <input
                            type="password"
                            id="newPassword"
                            name="newPassword"
                            placeholder="Nueva contraseña"
                            pattern="<?= Validator::PASSWORD_PATTERN ?>"
                            title="Mínimo 8 caracteres, 2 números y 1 carácter especial"
                            required>
                        <p class="password-rules">
                            Mínimo 8 caracteres, incluyendo al menos 2 números
                            y 1 carácter especial.
                        </p>
                    </div>

                    <div class="input-group">
                        <label for="confirmNewPassword">Confirmar contraseña</label>
                        <input
                            type="password"
                            id="confirmNewPassword"
                            name="confirmNewPassword"
                            placeholder="Repite tu contraseña"
                            minlength="8"
                            required>
                    </div>

                    <button type="submit" class="recovery-button">
                        Cambiar contraseña
                    </button>

                </form>

            <?php else: ?>

                <!-- PASO 1: CORREO -->
                <form method="POST" action="forgot-password.php">

                    <?php Csrf::campo(); ?>
                    <input type="hidden" name="accion" value="buscar">

                    <?php if ($error !== ""): ?>
                        <div class="login-message error">
                            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                        </div>
                    <?php endif; ?>

                    <div class="input-group">
                        <label for="email">Correo electrónico</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="ejemplo@correo.com"
                            value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            required>
                    </div>

                    <button type="submit" class="recovery-button">
                        Buscar cuenta
                    </button>

                </form>

            <?php endif; ?>

            <div class="login-link">
                <a href="login.php">
                    ← Volver al inicio de sesión
                </a>
            </div>

            <a href="../Landing_Page/index.html" class="back-link">
                Volver al inicio
            </a>

        </section>

    </main>

</body>

</html>