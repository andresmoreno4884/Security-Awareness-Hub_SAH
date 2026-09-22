<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * course.php
 * Página del curso para usuarios autenticados. Antes era
 * course.html sin ninguna protección; ahora exige sesión
 * iniciada (cualquier rol) antes de mostrar el contenido.
 * ============================================================
 */

require_once __DIR__ . "/../../../backend/view_bootstrap.php";

Auth::requireLoginView("../../authentication/login.php");

require_once __DIR__ . "/../../../backend/Models/CursoModel.php";

$model = new CursoModel();
$usuarioId = Auth::userId();

if (!$usuarioId) {
    header("Location: ../../authentication/login.php");
    exit;
}

$cursoId = filter_input(INPUT_GET, "curso_id", FILTER_VALIDATE_INT);

if (!$cursoId || $cursoId < 1) {
    $cursoAsignado = $model->obtenerPrimerCursoAsignado($usuarioId);

    if (!$cursoAsignado) {
        http_response_code(404);
        exit("No tienes cursos activos asignados.");
    }

    $cursoId = (int) $cursoAsignado["id"];
}

$curso = $model->obtenerPorId($cursoId);

if (!$curso || $curso["estado"] !== "activo") {
    http_response_code(404);
    exit("El curso no existe o está inactivo.");
}

if (!$model->estaAsignado($usuarioId, $cursoId)) {
    http_response_code(403);
    exit("Este curso no está asignado a tu usuario.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["accion"] ?? "") === "completar_modulo") {

    $moduloId = filter_input(
        INPUT_POST,
        "modulo_id",
        FILTER_VALIDATE_INT
    );

    if ($moduloId && $moduloId > 0) {
        $model->completarModulo(
            $usuarioId,
            $cursoId,
            $moduloId
        );
    }

    header(
        "Location: course.php?curso_id=" . $cursoId . "#contenido"
    );
    exit;
}

$modulos = $model->obtenerModulosConProgreso(
    $cursoId,
    $usuarioId
);

$progreso = $model->obtenerProgreso(
    $cursoId,
    $usuarioId
);

$porcentaje = $progreso["porcentaje"];
$totalModulos = $progreso["total"];
$modulosCompletados = $progreso["completados"];
$estadoCurso = $progreso["estado"];

$nombreCurso = $curso["titulo"];
$descripcionCurso = $curso["descripcion"]
    ?: "Completa los módulos de este curso para fortalecer tus conocimientos en seguridad digital.";

$duracionCurso = $curso["duracion"]
    ? $curso["duracion"] . " horas"
    : "Por definir";

$aprobacionCurso = $curso["porcentaje_aprobacion"] . "%";

$rutaNombre = $curso["ruta_nombre"]
    ?: "Ruta de aprendizaje en ciberseguridad";

$nombreUsuario = trim(
    ($_SESSION["nombres"] ?? "Usuario") . " " .
    ($_SESSION["apellidos"] ?? "")
);

$estadoTexto = $estadoCurso === "completado"
    ? "COMPLETADO"
    : ($estadoCurso === "progreso" ? "EN CURSO" : "NO INICIADO");

$imagenCurso = "/frontend/Landing_Page/assets/imagenes/curso-phishing.png";

if (stripos($nombreCurso, "contraseña") !== false) {
    $imagenCurso = "/frontend/Landing_Page/assets/imagenes/curso-password.png";
} elseif (stripos($nombreCurso, "fundamento") !== false) {
    $imagenCurso = "/frontend/Landing_Page/assets/imagenes/curso-ciberseguridad.png";
} elseif (stripos($nombreCurso, "ingenier") !== false) {
    $imagenCurso = "/frontend/Landing_Page/assets/imagenes/curso-ingenieria-social.png";
}

$imagenesModulos = [
    1 => "/frontend/Landing_Page/assets/imagenes/curso-ciberseguridad.png",
    2 => "/frontend/Landing_Page/assets/imagenes/curso-password.png",
    3 => "/frontend/Landing_Page/assets/imagenes/curso-phishing.png",
    4 => "/frontend/Landing_Page/assets/imagenes/curso-ingenieria-social.png",
    5 => "/frontend/Landing_Page/assets/imagenes/curso-phishing.png"
];
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($nombreCurso, ENT_QUOTES, "UTF-8") ?> | Security Awareness Hub</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Iconos Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS DEL CURSO -->
    <link rel="stylesheet" href="css/curse.css">

</head>


<body>


    <!-- =========================================================
     BARRA DE NAVEGACIÓN
========================================================= -->

    <header class="course-navbar">

        <nav class="course-nav-container">

            <!-- LOGO -->

            <a href="index.html" class="course-logo">

                <span>SECURITY</span>

                <span>AWARENESS</span>

            </a>


            <!-- MENÚ -->

            <ul class="course-menu">

                <li>
                    <a href="../../Landing_Page/index.html">
                        Inicio
                    </a>
                </li>

                <li>
                    <a href="#mi-curso" class="active">
                        Mi Curso
                    </a>
                </li>

                <li>
                    <a href="#contenido">
                        Contenido
                    </a>
                </li>

                <li>
                    <a href="#evaluacion">
                        Evaluación
                    </a>
                </li>

                <li>
                    <a href="#progreso">
                        Mi Progreso
                    </a>
                </li>

            </ul>


            <!-- USUARIO -->

            <div class="course-user">

                <div class="user-icon">

                    <i class="bi bi-person"></i>

                </div>

                <div class="user-info">

                    <strong>
                        <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, "UTF-8") ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(ucfirst($_SESSION["rol"] ?? "empleado"), ENT_QUOTES, "UTF-8") ?>
                    </span>

                </div>

            </div>

        </nav>

    </header>



    <!-- =========================================================
     CONTENIDO PRINCIPAL
========================================================= -->

    <main>


        <!-- =========================================================
     MIGAS DE PAN
========================================================= -->

        <section class="breadcrumb-section">

            <div class="page-container">

                <div class="breadcrumbs">

                    <a href="index.html">
                        <i class="bi bi-house"></i>
                        Inicio
                    </a>

                    <span>
                        <i class="bi bi-chevron-right"></i>
                    </span>

                    <a href="#mi-curso">
                        <?= htmlspecialchars($rutaNombre, ENT_QUOTES, "UTF-8") ?>
                    </a>

                    <span>
                        <i class="bi bi-chevron-right"></i>
                    </span>

                    <strong>
                        <?= htmlspecialchars($nombreCurso, ENT_QUOTES, "UTF-8") ?>
                    </strong>

                </div>

            </div>

        </section>



        <!-- =========================================================
     HERO DEL CURSO
========================================================= -->

        <section id="mi-curso" class="course-hero">


            <!-- IMAGEN DE FONDO -->

            <div class="course-hero-image">

                <img src="<?= htmlspecialchars($imagenCurso, ENT_QUOTES, "UTF-8") ?>"
                    alt="<?= htmlspecialchars($nombreCurso, ENT_QUOTES, "UTF-8") ?>">

            </div>


            <!-- OSCURECIMIENTO -->

            <div class="course-hero-overlay"></div>


            <!-- CONTENIDO -->

            <div class="page-container course-hero-content">


                <div class="course-hero-left">

                    <span class="course-label">
                        CURSO ASIGNADO
                    </span>


                    <h1>
                        <?= htmlspecialchars($nombreCurso, ENT_QUOTES, "UTF-8") ?>
                    </h1>


                    <p class="course-description">
                        <?= nl2br(htmlspecialchars($descripcionCurso, ENT_QUOTES, "UTF-8")) ?>
                    </p>


                    <!-- INFORMACIÓN DEL CURSO -->

                    <div class="course-info">


                        <div class="info-item">

                            <div class="info-icon">

                                <i class="bi bi-clock"></i>

                            </div>

                            <div>

                                <span>
                                    DURACIÓN
                                </span>

                                <strong>
                                    <?= htmlspecialchars($duracionCurso, ENT_QUOTES, "UTF-8") ?>
                                </strong>

                            </div>

                        </div>



                        <div class="info-item">

                            <div class="info-icon">

                                <i class="bi bi-book"></i>

                            </div>

                            <div>

                                <span>
                                    MÓDULOS
                                </span>

                                <strong>
                                    <?= $totalModulos ?> módulos
                                </strong>

                            </div>

                        </div>



                        <div class="info-item">

                            <div class="info-icon">

                                <i class="bi bi-award"></i>

                            </div>

                            <div>

                                <span>
                                    APROBACIÓN
                                </span>

                                <strong>
                                    <?= htmlspecialchars($aprobacionCurso, ENT_QUOTES, "UTF-8") ?>
                                </strong>

                            </div>

                        </div>


                    </div>

                </div>



                <!-- TARJETA DE PROGRESO -->

                <div class="course-progress-card">


                    <div class="progress-card-header">

                        <span>
                            PROGRESO DEL CURSO
                        </span>

                        <strong><?= $porcentaje ?>%</strong>

                    </div>


                    <div class="progress-bar">

                        <div class="progress-value" style="width: <?= $porcentaje ?>%;"></div>

                    </div>


                    <p>
                        <?= $modulosCompletados ?> de <?= $totalModulos ?> módulos completados
                    </p>


                    <a href="#contenido" class="continue-button">

                        Continuar curso

                        <i class="bi bi-arrow-right"></i>

                    </a>


                </div>

            </div>

        </section>



        <!-- =========================================================
     CONTENIDO DEL CURSO
========================================================= -->

        <section id="contenido" class="content-section">

            <div class="page-container">


                <!-- ENCABEZADO -->

                <div class="section-heading">

                    <div class="section-number">
                        01
                    </div>

                    <div>

                        <span>
                            CONTENIDO DEL CURSO
                        </span>

                        <h2>
                            Aprende y
                            <strong>
                                protege.
                            </strong>
                        </h2>

                        <p>

                            Completa cada módulo para fortalecer
                            tus conocimientos en seguridad digital.

                        </p>

                    </div>

                </div>



                <!-- MÓDULOS -->

                <div class="modules-grid">

<?php if (empty($modulos)): ?>

    <article class="module-card">
        <div class="module-content">
            <span class="module-status pending">SIN MÓDULOS</span>
            <h3>Este curso todavía no tiene módulos</h3>
            <p>El administrador debe configurar el contenido del curso.</p>
        </div>
    </article>

<?php else: ?>

    <?php foreach ($modulos as $indice => $modulo): ?>

        <?php
        $numero = (int) $modulo["orden"];
        if ($numero < 1) {
            $numero = $indice + 1;
        }

        $completado = (int) ($modulo["completado"] ?? 0) === 1;

        $imagenModulo = $imagenesModulos[$numero] ?? $imagenCurso;

        $descripcionModulo = $modulo["descripcion"]
            ?: "Completa este módulo para avanzar en tu ruta de aprendizaje.";
        ?>

        <article class="module-card <?= $completado ? "completed-module" : "active-module" ?>">

            <div class="module-image">

                <img
                    src="<?= htmlspecialchars($imagenModulo, ENT_QUOTES, "UTF-8") ?>"
                    alt="<?= htmlspecialchars($modulo["titulo"], ENT_QUOTES, "UTF-8") ?>"
                >

                <span class="module-number">
                    <?= str_pad((string) $numero, 2, "0", STR_PAD_LEFT) ?>
                </span>

            </div>

            <div class="module-content">

                <?php if ($completado): ?>

                    <span class="module-status completed">
                        <i class="bi bi-check-circle"></i>
                        COMPLETADO
                    </span>

                <?php else: ?>

                    <span class="module-status current">
                        <i class="bi bi-play-circle"></i>
                        PENDIENTE
                    </span>

                <?php endif; ?>

                <h3>
                    <?= htmlspecialchars($modulo["titulo"], ENT_QUOTES, "UTF-8") ?>
                </h3>

                <p>
                    <?= htmlspecialchars($descripcionModulo, ENT_QUOTES, "UTF-8") ?>
                </p>

                <div class="module-footer">

                    <span>
                        <i class="bi bi-book"></i>
                        MÓDULO <?= $numero ?>
                    </span>

                    <?php if ($completado): ?>

                        <span>
                            <i class="bi bi-check2-circle"></i>
                            Completado
                        </span>

                    <?php else: ?>

                        <form
                            method="POST"
                            action="course.php?curso_id=<?= $cursoId ?>#contenido"
                        >

                            <input
                                type="hidden"
                                name="accion"
                                value="completar_modulo"
                            >

                            <input
                                type="hidden"
                                name="modulo_id"
                                value="<?= (int) $modulo["id"] ?>"
                            >

                            <button
                                type="submit"
                                class="module-complete-button"
                            >
                                Completar
                                <i class="bi bi-check2"></i>
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

        </article>

    <?php endforeach; ?>

<?php endif; ?>

</div>

            </div>
        </section>



        <!-- =========================================================
     ACTIVIDAD
========================================================= -->

        <section id="actividad" class="activity-section">

            <div class="page-container">


                <div class="activity-box">


                    <div class="activity-left">

                        <span class="course-label">
                            ACTIVIDAD
                        </span>

                        <h2>
                            ¿Reconocerías un
                            <span>
                                correo de phishing?
                            </span>
                        </h2>

                        <p>

                            Completa los módulos del curso y continúa con
                            la evaluación correspondiente cuando esté disponible.

                        </p>

                        <div class="activity-meta">

                            <span>
                                <i class="bi bi-list-check"></i>
                                5 preguntas
                            </span>

                            <span>
                                <i class="bi bi-clock"></i>
                                10 minutos
                            </span>

                        </div>

                    </div>



                    <div class="activity-right">

                        <div class="activity-icon">

                            <i class="bi bi-envelope-exclamation"></i>

                        </div>

                        <a href="#evaluacion" class="activity-button">

                            Comenzar actividad

                            <i class="bi bi-arrow-right"></i>

                        </a>

                    </div>


                </div>

            </div>

        </section>



        <!-- =========================================================
     EVALUACIÓN
========================================================= -->

        <section id="evaluacion" class="evaluation-section">

            <div class="page-container">


                <div class="section-heading">

                    <div class="section-number">
                        02
                    </div>

                    <div>

                        <span>
                            EVALUACIÓN
                        </span>

                        <h2>
                            Comprueba lo
                            <strong>
                                aprendido.
                            </strong>
                        </h2>

                        <p>

                            Al finalizar el contenido deberás
                            presentar la evaluación del curso.

                        </p>

                    </div>

                </div>



                <div class="evaluation-card">


                    <div class="evaluation-icon">

                        <i class="bi bi-clipboard-check"></i>

                    </div>


                    <div class="evaluation-info">

                        <span>
                            EVALUACIÓN FINAL
                        </span>

                        <h3>
                            Evaluación de Ciberseguridad
                        </h3>

                        <p>

                            Responde las preguntas relacionadas
                            con los módulos estudiados.

                        </p>


                        <div class="evaluation-details">

                            <span>
                                <i class="bi bi-question-circle"></i>
                                10 preguntas
                            </span>

                            <span>
                                <i class="bi bi-clock"></i>
                                15 minutos
                            </span>

                            <span>
                                <i class="bi bi-check2-circle"></i>
                                Aprobación: 70%
                            </span>

                        </div>

                    </div>


                    <button class="evaluation-button">

                        Presentar evaluación

                        <i class="bi bi-arrow-right"></i>

                    </button>


                </div>

            </div>

        </section>



        <!-- =========================================================
     RESULTADOS
========================================================= -->

        <section class="results-section">

            <div class="page-container">


                <div class="results-grid">


                    <!-- RESULTADO -->

                    <div class="result-card">

                        <div class="result-icon">

                            <i class="bi bi-award"></i>

                        </div>

                        <div>

                            <span>
                                CALIFICACIÓN
                            </span>

                            <strong><?= $porcentaje ?>%</strong>

                            <p>
                                Pendiente de evaluación
                            </p>

                        </div>

                    </div>



                    <!-- ESTADO -->

                    <div class="result-card">

                        <div class="result-icon">

                            <i class="bi bi-graph-up-arrow"></i>

                        </div>

                        <div>

                            <span>
                                ESTADO
                            </span>

                            <strong class="<?= $estadoCurso === "completado" ? "green-text" : "status-pending" ?>">
                                <?= $estadoTexto ?>
                            </strong>

                            <p>
                                <?= $estadoCurso === "completado" ? "Curso completado" : "Completa los módulos pendientes" ?>
                            </p>

                        </div>

                    </div>



                    <!-- PROGRESO -->

                    <div class="result-card">

                        <div class="result-icon">

                            <i class="bi bi-bar-chart"></i>

                        </div>

                        <div>

                            <span>
                                PROGRESO
                            </span>

                            <strong><?= $porcentaje ?>%</strong>

                            <p><?= $modulosCompletados ?> de <?= $totalModulos ?> módulos</p>

                        </div>

                    </div>


                </div>

            </div>

        </section>



        <!-- =========================================================
     MI PROGRESO
========================================================= -->

        <section id="progreso" class="progress-section">

            <div class="page-container">


                <div class="progress-panel">


                    <div class="progress-panel-header">

                        <div>

                            <span>
                                MI PROGRESO
                            </span>

                            <h2>
                                Tu avance en el curso
                            </h2>

                        </div>

                        <strong><?= $porcentaje ?>%</strong>

                    </div>


                    <div class="large-progress">

                        <div class="large-progress-value" style="width: <?= $porcentaje ?>%;"></div>

                    </div>


                    <div class="progress-stats">


                        <div>

                            <span>
                                ACTIVIDADES
                            </span>

                            <strong><?= $modulosCompletados ?> / <?= $totalModulos ?></strong>

                        </div>


                        <div>

                            <span>
                                MÓDULOS
                            </span>

                            <strong><?= $modulosCompletados ?> / <?= $totalModulos ?></strong>

                        </div>


                        <div>

                            <span>
                                EVALUACIÓN
                            </span>

                            <strong>
                                PENDIENTE
                            </strong>

                        </div>


                        <div>

                            <span>
                                ESTADO
                            </span>

                            <strong class="green-text"><?= $estadoTexto ?></strong>

                        </div>


                    </div>

                </div>

            </div>

        </section>


    </main>



    <!-- =========================================================
     FOOTER
========================================================= -->

    <footer class="course-footer">

        <div class="page-container footer-content">

            <div class="course-logo">

                <span>
                    SECURITY
                </span>

                <span>
                    AWARENESS
                </span>

            </div>

            <p>
                Plataforma de capacitación en ciberseguridad
            </p>

            <span>
                © 2026 Security Awareness Hub
            </span>

        </div>

    </footer>


</body>

</html>