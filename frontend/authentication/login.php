<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * LOGIN
 * ============================================================
 *
 * Vista de inicio de sesión.
 *
 * Funcionalidades:
 * - Recibe el formulario mediante POST.
 * - Busca el usuario mediante UsuarioModel.
 * - Verifica la contraseña con password_verify().
 * - Crea la sesión mediante Auth.
 * - Redirige según el rol.
 *
 * Roles:
 * - admin
 * - admin_empresa
 * - empleado
 *
 * No contiene SQL.
 * ============================================================
 */


/**
 * ============================================================
 * BOOTSTRAP
 * ============================================================
 */

require_once __DIR__ . '/../../backend/view_bootstrap.php';

require_once __DIR__ . '/../../backend/Models/UsuarioModel.php';


/**
 * ============================================================
 * VARIABLES
 * ============================================================
 */

$error = '';

$correo = '';


/**
 * ============================================================
 * SI YA HAY SESIÓN
 * ============================================================
 */

if (Auth::isAuthenticated()) {

    /**
     * ========================================================
     * OBTENER ROL ACTUAL
     * ========================================================
     */

    $rol = Auth::role();


    /**
     * --------------------------------------------------------
     * ADMINISTRADOR GENERAL
     * --------------------------------------------------------
     *
     * Administrador de Security Awareness Hub.
     */

    if ($rol === 'admin') {

        header(
            'Location: ../Interface_Administrador/panel.php'
        );

        exit;
    }


    /**
     * --------------------------------------------------------
     * ADMINISTRADOR DE EMPRESA
     * --------------------------------------------------------
     *
     * Responsable de una empresa cliente.
     */

    if ($rol === 'admin_empresa') {

        header(
            'Location: ../Interface_Empresa/panel.php'
        );

        exit;
    }


    /**
     * --------------------------------------------------------
     * EMPLEADO
     * --------------------------------------------------------
     *
     * Usuario que realiza las capacitaciones.
     */

    if ($rol === 'empleado') {

        header(
            'Location: ../Interface_User/dashboard.php'
        );

        exit;
    }


    /**
     * --------------------------------------------------------
     * ROL NO RECONOCIDO
     * --------------------------------------------------------
     */

    Auth::logout();

    header(
        'Location: login.php'
    );

    exit;
}


/**
 * ============================================================
 * PROCESAR LOGIN
 * ============================================================
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /**
     * --------------------------------------------------------
     * OBTENER DATOS
     * --------------------------------------------------------
     */

    $correo = strtolower(
        trim(
            $_POST['email'] ?? ''
        )
    );

    $password =
        $_POST['password'] ?? '';


    /**
     * ========================================================
     * VALIDACIONES
     * ========================================================
     */

    if (
        $correo === '' ||
        $password === ''
    ) {

        $error =
            'Correo y contraseña son obligatorios.';

    } elseif (
        !filter_var(
            $correo,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Ingresa un correo electrónico válido.';

    } else {

        try {

            /**
             * =================================================
             * MODELO
             * =================================================
             */

            $usuarioModel =
                new UsuarioModel();


            /**
             * =================================================
             * BUSCAR USUARIO
             * =================================================
             */

            $usuario =
                $usuarioModel->buscarPorCorreo(
                    $correo
                );


            /**
             * =================================================
             * USUARIO NO EXISTE
             * =================================================
             */

            if (!$usuario) {

                $error =
                    'Correo o contraseña incorrectos.';
            }


            /**
             * =================================================
             * CUENTA INACTIVA
             * =================================================
             */

            elseif (
                $usuario['estado'] !== 'activo'
            ) {

                $error =
                    'Tu cuenta está inactiva. Contacta al administrador.';
            }


            /**
             * =================================================
             * VERIFICAR CONTRASEÑA
             * =================================================
             */

            elseif (
                !password_verify(
                    $password,
                    $usuario['password']
                )
            ) {

                $error =
                    'Correo o contraseña incorrectos.';
            }


            /**
             * =================================================
             * LOGIN CORRECTO
             * =================================================
             */

            else {

                /**
                 * ------------------------------------------------
                 * Crear sesión segura
                 * ------------------------------------------------
                 */

                Auth::login(
                    $usuario
                );


                /**
                 * =================================================
                 * REDIRECCIÓN SEGÚN ROL
                 * =================================================
                 */


                /**
                 * ------------------------------------------------
                 * ADMINISTRADOR GENERAL
                 * ------------------------------------------------
                 */

                if (
                    $usuario['rol'] === 'admin'
                ) {

                    header(
                        'Location: ../Interface_Administrador/panel.php'
                    );

                    exit;
                }


                /**
                 * ------------------------------------------------
                 * ADMINISTRADOR DE EMPRESA
                 * ------------------------------------------------
                 */

                if (
                    $usuario['rol'] === 'admin_empresa'
                ) {

                    header(
                        'Location: ../Interface_Empresa/panel.php'
                    );

                    exit;
                }


                /**
                 * ------------------------------------------------
                 * EMPLEADO
                 * ------------------------------------------------
                 */

                if (
                    $usuario['rol'] === 'empleado'
                ) {

                    header(
                        'Location: ../Interface_User/dashboard.php'
                    );

                    exit;
                }


                /**
                 * =================================================
                 * ROL NO RECONOCIDO
                 * =================================================
                 */

                Auth::logout();

                $error =
                    'El rol de usuario no es válido.';
            }


        } catch (Throwable $e) {

            /**
             * ----------------------------------------------------
             * No mostramos información interna
             * de la excepción.
             * ----------------------------------------------------
             */

            $error =
                'No fue posible iniciar sesión. Inténtalo nuevamente.';
        }
    }
}

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Inicio de sesión de Security Awareness Hub"
    >

    <title>
        Security Awareness Hub - Iniciar sesión
    </title>


    <!-- =====================================================
         CSS LOGIN
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/login.css"
    >

</head>


<body>


    <!-- =====================================================
         FONDO
    ====================================================== -->

    <div class="login-background"></div>


    <!-- =====================================================
         CONTENEDOR PRINCIPAL
    ====================================================== -->

    <main class="login-container">


        <section class="login-card">


            <!-- =================================================
                 PANEL VISUAL
            ================================================== -->

            <section
                class="login-visual"
                aria-label="Información visual"
            >

                <div class="carousel">


                    <div
                        class="carousel-slide slide-1"
                    ></div>


                    <div
                        class="carousel-slide slide-2"
                    ></div>


                    <div
                        class="carousel-slide slide-3"
                    ></div>


                </div>


                <div class="carousel-indicators">

                    <span
                        class="indicator active"
                    ></span>

                    <span
                        class="indicator"
                    ></span>

                    <span
                        class="indicator"
                    ></span>

                </div>

            </section>


            <!-- =================================================
                 PANEL FORMULARIO
            ================================================== -->

            <section class="login-form-panel">


                <div class="login-form-content">


                    <!-- =================================================
                         LOGO
                    ================================================== -->

                    <div class="login-logo">

                        <span>
                            SECURITY
                        </span>

                        <strong>
                            AWARENESS
                        </strong>

                    </div>


                    <!-- =================================================
                         TÍTULO
                    ================================================== -->

                    <div class="login-heading">

                        <span class="login-label">
                            ACCESO SEGURO
                        </span>

                        <h1>
                            Bienvenido
                        </h1>

                        <p>
                            Inicia sesión para continuar
                            con tu capacitación en
                            seguridad digital.
                        </p>

                    </div>


                    <!-- =================================================
                         MENSAJE DE ERROR
                    ================================================== -->

                    <?php if ($error !== ''): ?>

                        <div
                            class="login-message error"
                            role="alert"
                        >

                            <?= htmlspecialchars(
                                $error,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         FORMULARIO
                    ================================================== -->

                    <form
                        method="POST"
                        action=""
                        class="login-form"
                    >


                        <!-- =================================================
                             CORREO
                        ================================================== -->

                        <div class="form-group">

                            <label for="email">
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars(
                                    $correo,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                placeholder="Ingresa tu correo"
                                autocomplete="email"
                                required
                            >

                        </div>


                        <!-- =================================================
                             CONTRASEÑA
                        ================================================== -->

                        <div class="form-group">

                            <label for="password">
                                Contraseña
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Ingresa tu contraseña"
                                autocomplete="current-password"
                                required
                            >

                        </div>


                        <!-- =================================================
                             OPCIONES
                        ================================================== -->

                        <div class="login-options">


                            <label class="remember">

                                <input
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                >

                                <span>
                                    Recordarme
                                </span>

                            </label>


                            <a
                                href="forgot-password.php"
                                class="forgot-password"
                            >
                                ¿Olvidaste tu contraseña?
                            </a>


                        </div>


                        <!-- =================================================
                             BOTÓN
                        ================================================== -->

                        <button
                            type="submit"
                            class="login-button"
                        >

                            Iniciar sesión

                        </button>


                    </form>


                    <!-- =================================================
                         INFORMACIÓN
                    ================================================== -->

                    <p class="register-text">

                        ¿No tienes una cuenta?

                        <span>
                            Contacta al administrador.
                        </span>

                    </p>


                    <!-- =================================================
                         VOLVER
                    ================================================== -->

                    <a
                        href="../Landing_Page/index.html"
                        class="back-link"
                    >

                        ← Volver al inicio

                    </a>


                </div>

            </section>


        </section>


    </main>


</body>

</html>