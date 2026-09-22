<?php

require_once __DIR__ . '/../../backend/Config/database.php';

$mensaje = '';
$tipoMensaje = '';

$empresa = '';
$nombre = '';
$correo = '';
$telefono = '';
$cantidad = '';
$tema = '';
$modalidad = '';
$mensajeTexto = '';


/* ============================================================
   PROCESAR FORMULARIO
============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $empresa = trim($_POST['empresa'] ?? '');
    $nombre = trim($_POST['nombre_contacto'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $cantidad = (int) ($_POST['cantidad_personas'] ?? 0);
    $tema = trim($_POST['tema_interes'] ?? '');
    $modalidad = trim($_POST['modalidad'] ?? '');
    $mensajeTexto = trim($_POST['mensaje'] ?? '');


    /* ========================================================
       VALIDACIONES
    ======================================================== */

    if (
        $empresa === '' ||
        $nombre === '' ||
        $correo === '' ||
        $telefono === '' ||
        $cantidad < 1 ||
        $tema === '' ||
        $modalidad === ''
    ) {

        $mensaje = 'Completa todos los campos obligatorios.';
        $tipoMensaje = 'error';

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = 'Ingresa un correo electrónico válido.';
        $tipoMensaje = 'error';

    } elseif (
        !in_array(
            $modalidad,
            ['Virtual', 'Presencial', 'Híbrida'],
            true
        )
    ) {

        $mensaje = 'Selecciona una modalidad válida.';
        $tipoMensaje = 'error';

    } else {

        try {

            /* =================================================
               CONEXIÓN A BASE DE DATOS
            ================================================= */

            $pdo = Database::connect();


            /* =================================================
               INSERTAR SOLICITUD
            ================================================= */

            $sql = "
                INSERT INTO solicitudes_capacitacion
                (
                    empresa,
                    nombre_contacto,
                    correo,
                    telefono,
                    cantidad_personas,
                    tema_interes,
                    modalidad,
                    mensaje
                )
                VALUES
                (
                    :empresa,
                    :nombre,
                    :correo,
                    :telefono,
                    :cantidad,
                    :tema,
                    :modalidad,
                    :mensaje
                )
            ";


            $stmt = $pdo->prepare($sql);


            $stmt->execute([

                ':empresa' => $empresa,

                ':nombre' => $nombre,

                ':correo' => $correo,

                ':telefono' => $telefono,

                ':cantidad' => $cantidad,

                ':tema' => $tema,

                ':modalidad' => $modalidad,

                ':mensaje' => $mensajeTexto

            ]);


            /* =================================================
               ÉXITO
            ================================================= */

            $mensaje =
                'Solicitud enviada correctamente. Nuestro equipo se pondrá en contacto contigo.';

            $tipoMensaje = 'success';


            /* Limpiar formulario */

            $empresa = '';
            $nombre = '';
            $correo = '';
            $telefono = '';
            $cantidad = '';
            $tema = '';
            $modalidad = '';
            $mensajeTexto = '';


        } catch (Throwable $e) {

            $mensaje =
                'No fue posible enviar la solicitud. Verifica la conexión con la base de datos.';

            $tipoMensaje = 'error';

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


    <title>
        Solicita una capacitación |
        Security Awareness Hub
    </title>


    <link
        rel="stylesheet"
        href="css/styles.css"
    >


    <style>

        /* ====================================================
           PÁGINA
        ==================================================== */

        .request-page {

            min-height: 100vh;

            padding: 60px 20px;

            background:

                radial-gradient(
                    circle at top right,
                    rgba(0, 245, 160, .12),
                    transparent 35%
                ),

                #020806;

        }


        .request-container {

            width: 100%;

            max-width: 850px;

            margin: 0 auto;

        }


        /* ====================================================
           ENCABEZADO
        ==================================================== */

        .request-header {

            text-align: center;

            margin-bottom: 35px;

        }


        .request-header span {

            color: #00f5a0;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 2px;

        }


        .request-header h1 {

            margin: 12px 0;

            color: #ffffff;

            font-size: 38px;

            line-height: 1.1;

        }


        .request-header p {

            max-width: 650px;

            margin: 0 auto;

            color: #8fa29c;

            line-height: 1.6;

        }


        /* ====================================================
           TARJETA
        ==================================================== */

        .request-card {

            padding: 35px;

            border:

                1px solid
                rgba(0, 245, 160, .20);

            border-radius: 18px;

            background: #071410;

            box-shadow:

                0 20px 60px
                rgba(0, 0, 0, .30);

        }


        /* ====================================================
           GRID
        ==================================================== */

        .request-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 20px;

        }


        .request-field {

            display: flex;

            flex-direction: column;

            gap: 8px;

        }


        .request-field.full {

            grid-column:
                1 / -1;

        }


        /* ====================================================
           LABELS
        ==================================================== */

        .request-field label {

            color: #dce8e4;

            font-size: 12px;

            font-weight: 700;

        }


        /* ====================================================
           INPUTS
        ==================================================== */

        .request-field input,
        .request-field select,
        .request-field textarea {

            width: 100%;

            padding: 13px 14px;

            border:

                1px solid
                rgba(0, 245, 160, .15);

            border-radius: 9px;

            outline: none;

            background: #03100c;

            color: #ffffff;

            font-family: inherit;

            font-size: 13px;

        }


        .request-field input::placeholder,
        .request-field textarea::placeholder {

            color: #60736e;

        }


        .request-field textarea {

            min-height: 130px;

            resize: vertical;

        }


        .request-field input:focus,
        .request-field select:focus,
        .request-field textarea:focus {

            border-color:
                #00f5a0;

            box-shadow:
                0 0 0 2px
                rgba(0, 245, 160, .05);

        }


        /* ====================================================
           SELECT
        ==================================================== */

        .request-field select {

            cursor: pointer;

        }


        .request-field select option {

            background: #071410;

            color: #ffffff;

        }


        /* ====================================================
           BOTÓN
        ==================================================== */

        .request-button {

            width: 100%;

            margin-top: 25px;

            padding: 15px;

            border: 0;

            border-radius: 9px;

            background: #00f5a0;

            color: #00140d;

            font-weight: 900;

            font-size: 13px;

            cursor: pointer;

            transition:
                .2s ease;

        }


        .request-button:hover {

            transform:
                translateY(-1px);

            box-shadow:

                0 10px 30px
                rgba(0, 245, 160, .18);

        }


        /* ====================================================
           MENSAJES
        ==================================================== */

        .request-message {

            margin-bottom: 20px;

            padding: 14px;

            border-radius: 9px;

            font-size: 13px;

            line-height: 1.5;

        }


        .request-message.success {

            color: #00f5a0;

            background:
                rgba(0, 245, 160, .08);

            border:
                1px solid
                rgba(0, 245, 160, .20);

        }


        .request-message.error {

            color: #ff6878;

            background:
                rgba(255, 82, 99, .08);

            border:
                1px solid
                rgba(255, 82, 99, .20);

        }


        /* ====================================================
           VOLVER
        ==================================================== */

        .back-link {

            display: inline-block;

            margin-top: 25px;

            color: #00f5a0;

            text-decoration: none;

            font-size: 13px;

        }


        .back-link:hover {

            text-decoration:
                underline;

        }


        /* ====================================================
           RESPONSIVE
        ==================================================== */

        @media (max-width: 650px) {

            .request-page {

                padding:
                    35px 15px;

            }


            .request-grid {

                grid-template-columns:
                    1fr;

            }


            .request-field.full {

                grid-column:
                    auto;

            }


            .request-card {

                padding:
                    22px;

            }


            .request-header h1 {

                font-size:
                    29px;

            }

        }

    </style>

</head>


<body>


<main class="request-page">


    <div class="request-container">


        <!-- =================================================
             ENCABEZADO
        ================================================== -->

        <header class="request-header">

            <span>
                CAPACITACIÓN EMPRESARIAL
            </span>


            <h1>
                Solicita una capacitación
            </h1>


            <p>

                Cuéntanos sobre tu organización y
                nuestro equipo se pondrá en contacto
                contigo.

            </p>

        </header>


        <!-- =================================================
             FORMULARIO
        ================================================== -->

        <section class="request-card">


            <?php if ($mensaje !== ''): ?>

                <div
                    class="request-message <?= htmlspecialchars(
                        $tipoMensaje,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                    <?= htmlspecialchars(
                        $mensaje,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
            >


                <div class="request-grid">


                    <!-- EMPRESA -->

                    <div class="request-field">

                        <label for="empresa">

                            Empresa *

                        </label>


                        <input
                            type="text"
                            id="empresa"
                            name="empresa"
                            required
                            maxlength="150"
                            value="<?= htmlspecialchars(
                                $empresa,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Nombre de la empresa"
                        >

                    </div>


                    <!-- CONTACTO -->

                    <div class="request-field">

                        <label for="nombre_contacto">

                            Nombre del contacto *

                        </label>


                        <input
                            type="text"
                            id="nombre_contacto"
                            name="nombre_contacto"
                            required
                            maxlength="150"
                            value="<?= htmlspecialchars(
                                $nombre,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Nombre completo"
                        >

                    </div>


                    <!-- CORREO -->

                    <div class="request-field">

                        <label for="correo">

                            Correo empresarial *

                        </label>


                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            required
                            maxlength="150"
                            value="<?= htmlspecialchars(
                                $correo,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="correo@empresa.com"
                        >

                    </div>


                    <!-- TELÉFONO -->

                    <div class="request-field">

                        <label for="telefono">

                            Teléfono *

                        </label>


                        <input
                            type="tel"
                            id="telefono"
                            name="telefono"
                            required
                            maxlength="30"
                            value="<?= htmlspecialchars(
                                $telefono,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="300 000 0000"
                        >

                    </div>


                    <!-- CANTIDAD -->

                    <div class="request-field">

                        <label for="cantidad_personas">

                            Cantidad de personas *

                        </label>


                        <input
                            type="number"
                            id="cantidad_personas"
                            name="cantidad_personas"
                            min="1"
                            max="100000"
                            required
                            value="<?= htmlspecialchars(
                                (string) $cantidad,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Ej: 50"
                        >

                    </div>


                    <!-- MODALIDAD -->

                    <div class="request-field">

                        <label for="modalidad">

                            Modalidad *

                        </label>


                        <select
                            id="modalidad"
                            name="modalidad"
                            required
                        >

                            <option value="">
                                Selecciona una modalidad
                            </option>


                            <option
                                value="Virtual"
                                <?= $modalidad === 'Virtual'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Virtual
                            </option>


                            <option
                                value="Presencial"
                                <?= $modalidad === 'Presencial'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Presencial
                            </option>


                            <option
                                value="Híbrida"
                                <?= $modalidad === 'Híbrida'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Híbrida
                            </option>

                        </select>

                    </div>


                    <!-- TEMA -->

                    <div class="request-field full">

                        <label for="tema_interes">

                            Tema de interés *

                        </label>


                        <select
                            id="tema_interes"
                            name="tema_interes"
                            required
                        >

                            <option value="">
                                Selecciona un tema
                            </option>


                            <option
                                value="Fundamentos de ciberseguridad"
                                <?= $tema === 'Fundamentos de ciberseguridad'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Fundamentos de ciberseguridad
                            </option>


                            <option
                                value="Correos fraudulentos y phishing"
                                <?= $tema === 'Correos fraudulentos y phishing'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Correos fraudulentos y phishing
                            </option>


                            <option
                                value="Seguridad de contraseñas"
                                <?= $tema === 'Seguridad de contraseñas'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Seguridad de contraseñas
                            </option>


                            <option
                                value="Ingeniería social"
                                <?= $tema === 'Ingeniería social'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Ingeniería social
                            </option>


                            <option
                                value="Capacitación personalizada"
                                <?= $tema === 'Capacitación personalizada'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Capacitación personalizada
                            </option>

                        </select>

                    </div>


                    <!-- MENSAJE -->

                    <div class="request-field full">

                        <label for="mensaje">

                            Mensaje

                        </label>


                        <textarea
                            id="mensaje"
                            name="mensaje"
                            maxlength="2000"
                            placeholder="Cuéntanos qué necesita tu organización..."
                        ><?= htmlspecialchars(
                            $mensajeTexto,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>

                    </div>


                </div>


                <!-- BOTÓN -->

                <button
                    type="submit"
                    class="request-button"
                >

                    Solicitar capacitación

                </button>


            </form>


            <!-- VOLVER -->

            <a
                href="index.html"
                class="back-link"
            >

                ← Volver al inicio

            </a>


        </section>


    </div>


</main>


</body>

</html>