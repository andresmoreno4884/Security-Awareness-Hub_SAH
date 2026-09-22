<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * authentication / register.php
 * ============================================================
 *
 * El registro público está deshabilitado.
 *
 * Las cuentas de empleados son creadas por:
 *
 * - admin
 * - admin_empresa
 *
 * La creación de usuarios se realiza desde los módulos
 * administrativos correspondientes.
 *
 * No se utiliza JavaScript.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

/**
 * ============================================================
 * USUARIO NO AUTENTICADO
 * ============================================================
 *
 * Si alguien intenta acceder directamente al registro,
 * lo enviamos al inicio de sesión.
 */

if (!Auth::isAuthenticated()) {

    header("Location: login.php");
    exit;
}

/**
 * ============================================================
 * ADMIN GENERAL
 * ============================================================
 *
 * El administrador general debe utilizar el módulo
 * de gestión de usuarios.
 */

if (Auth::isAdmin()) {

    header("Location: ../Interface_Administrador/usuarios.php");
    exit;
}

/**
 * ============================================================
 * ADMIN DE EMPRESA
 * ============================================================
 *
 * La interfaz específica para admin_empresa se implementará
 * posteriormente.
 *
 * Por ahora no permitimos utilizar este registro.
 */

if (Auth::isAdminEmpresa()) {

    http_response_code(403);

    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>
            Acceso denegado | Security Awareness Hub
        </title>

        <link
            rel="stylesheet"
            href="css/register.css"
        >

    </head>

    <body>

        <main class="register-container">

            <section class="register-card">

                <div class="logo">

                    <span>
                        SECURITY
                    </span>

                    <strong>
                        AWARENESS
                    </strong>

                </div>

                <div class="register-header">

                    <p class="tag">
                        ACCESO RESTRINGIDO
                    </p>

                    <h1>
                        Registro no disponible
                    </h1>

                    <p>
                        La creación de empleados se realizará
                        desde el módulo de administración de empresa.
                    </p>

                </div>

                <div class="login-link">

                    <a href="../Interface_User/dashboard.php">
                        ← Volver al panel
                    </a>

                </div>

            </section>

        </main>

    </body>

    </html>

    <?php

    exit;
}

/**
 * ============================================================
 * EMPLEADO
 * ============================================================
 */

if (Auth::isEmpleado()) {

    header("Location: ../Interface_User/dashboard.php");
    exit;
}

/**
 * ============================================================
 * ROL NO VÁLIDO
 * ============================================================
 */

Auth::logout();

header("Location: login.php");
exit;