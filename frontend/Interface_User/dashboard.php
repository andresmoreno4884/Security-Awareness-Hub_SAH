<?php

session_start();

require_once __DIR__ . '/../../backend/Core/Auth.php';
require_once __DIR__ . '/../../backend/Models/UsuarioDashboardModel.php';

Auth::requireLoginView('../authentication/login.php');

if (Auth::isAdmin()) {
    header('Location: ../Interface_Administrador/panel.php');
    exit;
}

$usuarioId = Auth::userId();

$nombres = $_SESSION['nombres'] ?? 'Usuario';
$apellidos = $_SESSION['apellidos'] ?? '';

$dashboard = new UsuarioDashboardModel();

$resumen = $dashboard->obtenerResumen($usuarioId);
$cursos = $dashboard->obtenerCursos($usuarioId);

function e($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function estadoTexto(string $estado): string
{
    return match ($estado) {
        'completado' => 'Completado',
        'progreso' => 'En progreso',
        default => 'Pendiente'
    };
}

function nivelTexto(string $nivel): string
{
    return match (strtolower($nivel)) {
        'basico', 'básico' => 'Básico',
        'intermedio' => 'Intermedio',
        'avanzado' => 'Avanzado',
        default => $nivel ?: 'Básico'
    };
}

/*
|--------------------------------------------------------------------------
| Curso principal
|--------------------------------------------------------------------------
*/

$cursoPrincipal = null;

foreach ($cursos as $curso) {

    if (($curso['estado'] ?? '') === 'progreso') {
        $cursoPrincipal = $curso;
        break;
    }
}

if ($cursoPrincipal === null && !empty($cursos)) {
    $cursoPrincipal = $cursos[0];
}


/*
|--------------------------------------------------------------------------
| Datos visuales del curso
|--------------------------------------------------------------------------
*/

$progresoCurso = 0;

if ($cursoPrincipal) {

    $estadoPrincipal =
        $cursoPrincipal['estado'] ?? 'pendiente';

    $progresoCurso = match ($estadoPrincipal) {
        'completado' => 100,
        'progreso' => 50,
        default => 0
    };
}


/*
|--------------------------------------------------------------------------
| Cursos de la ruta
|--------------------------------------------------------------------------
*/

$cursosRuta = array_slice($cursos, 0, 4);

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
        Inicio | Security Awareness Hub
    </title>

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >

</head>

<body>

<div class="user-app">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="user-sidebar">

        <div class="sidebar-brand">

            <div class="brand-logo">
                SAH
            </div>

            <div class="brand-text">

                <strong>
                    SECURITY
                </strong>

                <span>
                    AWARENESS HUB
                </span>

            </div>

        </div>


        <!-- PERFIL -->

        <div class="user-profile">

            <div class="profile-avatar">

                <?= e(
                    strtoupper(
                        substr(
                            $nombres,
                            0,
                            1
                        )
                    )
                ) ?>

            </div>

            <div>

                <strong>
                    <?= e($nombres . ' ' . $apellidos) ?>
                </strong>

                <span>
                    Empleado
                </span>

            </div>

        </div>


        <!-- NAVEGACIÓN -->

        <nav class="user-navigation">

            <span class="nav-title">
                MENÚ
            </span>


            <a
                href="dashboard.php"
                class="user-nav-link active"
            >

                <span class="nav-icon">
                    ⌂
                </span>

                Inicio

            </a>


            <a
                href="#mis-cursos"
                class="user-nav-link"
            >

                <span class="nav-icon">
                    ▣
                </span>

                Mis cursos

            </a>


            <a
                href="#ruta"
                class="user-nav-link"
            >

                <span class="nav-icon">
                    ◈
                </span>

                Ruta de aprendizaje

            </a>


            <a
                href="#evaluaciones"
                class="user-nav-link"
            >

                <span class="nav-icon">
                    ✓
                </span>

                Evaluaciones

            </a>


            <a
                href="#progreso"
                class="user-nav-link"
            >

                <span class="nav-icon">
                    ◒
                </span>

                Mi progreso

            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="security-message">

                <span class="security-symbol">
                    ◇
                </span>

                <p>
                    Tu conocimiento
                    también protege.
                </p>

            </div>


            <a
                href="../authentication/logout.php"
                class="logout-link"
            >

                <span>
                    ⇥
                </span>

                Cerrar sesión

            </a>

        </div>

    </aside>


    <!-- =====================================================
         CONTENIDO PRINCIPAL
    ====================================================== -->

    <main class="user-main">


        <!-- TOPBAR -->

        <header class="user-topbar">

            <div>

                <span>
                    INICIO
                </span>

            </div>


            <div class="topbar-right">

                <div class="search-box">

                    <span>
                        ⌕
                    </span>

                    <input
                        type="text"
                        placeholder="Buscar cursos, temas..."
                    >

                </div>


                <div class="notification">
                    ♧
                </div>

            </div>

        </header>


        <!-- =================================================
             BIENVENIDA
        ================================================== -->

        <section class="welcome-area">

            <div>

                <h1>

                    Hola,

                    <span>
                        <?= e($nombres) ?>
                    </span>

                </h1>


                <p>
                    Sigue aprendiendo.
                    Un entorno más seguro también depende de ti.
                </p>

            </div>


            <div class="welcome-date">

                <strong>
                    Security Awareness Hub
                </strong>

                <span>
                    Capacita · Concientiza · Protege
                </span>

            </div>

        </section>


        <!-- =================================================
             GRID PRINCIPAL
        ================================================== -->

        <div class="dashboard-grid">


            <!-- =============================================
                 COLUMNA CENTRAL
            ============================================== -->

            <section class="dashboard-center">


                <!-- CURSO DESTACADO -->

                <?php if ($cursoPrincipal): ?>

                    <article class="featured-course">

                        <div class="featured-content">

                            <span class="featured-label">
                                CURSO DESTACADO
                            </span>


                            <h2>

                                <?= e(
                                    $cursoPrincipal['titulo']
                                ) ?>

                            </h2>


                            <p>

                                <?= e(
                                    $cursoPrincipal['descripcion']
                                    ?? 'Fortalece tus conocimientos en seguridad digital.'
                                ) ?>

                            </p>


                            <div class="course-meta">

                                <span>
                                    ◈
                                    <?= e(
                                        nivelTexto(
                                            $cursoPrincipal[
                                                'nivel_dificultad'
                                            ] ?? ''
                                        )
                                    ) ?>
                                </span>

                                <span>
                                    ▦
                                    <?= (int)
                                        (
                                            $cursoPrincipal[
                                                'numero_modulos'
                                            ] ?? 0
                                        )
                                    ?>
                                    módulos
                                </span>

                                <span>
                                    ◷
                                    <?= (int)
                                        (
                                            $cursoPrincipal[
                                                'duracion'
                                            ] ?? 0
                                        )
                                    ?>
                                    horas
                                </span>

                            </div>


                            <div class="featured-progress">

                                <div>

                                    <span>
                                        Progreso del curso
                                    </span>

                                    <strong>
                                        <?= $progresoCurso ?>%
                                    </strong>

                                </div>


                                <div class="progress-track">

                                    <div
                                        class="progress-fill"
                                        style="width: <?= $progresoCurso ?>%;"
                                    ></div>

                                </div>

                            </div>


                            <a
                                href="Curse/course.php?curso_id=<?= (int) $cursoPrincipal['curso_id'] ?>"
                                class="primary-button"
                            >

                                <?= $progresoCurso > 0
                                    ? 'Continuar curso'
                                    : 'Comenzar curso'
                                ?>

                                <span>
                                    →
                                </span>

                            </a>

                        </div>


                        <div class="featured-visual">

                            <div class="cyber-glow"></div>

                            <div class="email-icon">
                                ✉
                            </div>

                            <div class="warning-icon">
                                !
                            </div>

                        </div>

                    </article>

                <?php endif; ?>


                <!-- RUTA -->

                <section
                    class="learning-section"
                    id="ruta"
                >

                    <div class="section-heading">

                        <div>

                            <span>
                                CAPACITACIÓN
                            </span>

                            <h2>
                                Mi ruta de aprendizaje
                            </h2>

                        </div>

                    </div>


                    <div class="learning-cards">

                        <?php foreach ($cursosRuta as $curso): ?>

                            <?php

                            $estado =
                                $curso['estado'] ?? 'pendiente';

                            $progreso = match ($estado) {
                                'completado' => 100,
                                'progreso' => 50,
                                default => 0
                            };

                            ?>

                            <article
                                class="
                                    learning-card
                                    <?= $estado === 'progreso'
                                        ? 'selected'
                                        : ''
                                    ?>
                                "
                            >

                                <div class="learning-image">

                                    <span>
                                        SAH
                                    </span>

                                </div>


                                <div class="learning-body">

                                    <span class="learning-level">

                                        <?= e(
                                            nivelTexto(
                                                $curso[
                                                    'nivel_dificultad'
                                                ] ?? ''
                                            )
                                        ) ?>

                                    </span>


                                    <h3>

                                        <?= e(
                                            $curso['titulo']
                                        ) ?>

                                    </h3>


                                    <div class="mini-progress">

                                        <div>

                                            <div
                                                class="mini-progress-bar"
                                                style="width: <?= $progreso ?>%;"
                                            ></div>

                                        </div>

                                        <strong>
                                            <?= $progreso ?>%
                                        </strong>

                                    </div>


                                    <span class="learning-status">

                                        <?= e(
                                            estadoTexto($estado)
                                        ) ?>

                                    </span>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </section>

            </section>


            <!-- =============================================
                 COLUMNA DERECHA
            ============================================== -->

            <aside class="dashboard-right">


                <!-- PROGRESO -->

                <section
                    class="progress-card"
                    id="progreso"
                >

                    <h2>
                        Tu progreso
                    </h2>


                    <div class="progress-summary">

                        <div class="progress-circle">

                            <strong>
                                <?= (int)
                                    (
                                        $resumen[
                                            'progreso_general'
                                        ] ?? 0
                                    )
                                ?>%
                            </strong>

                            <span>
                                Progreso general
                            </span>

                        </div>


                        <div class="progress-data">

                            <div>

                                <strong>
                                    <?= (int)
                                        (
                                            $resumen[
                                                'cursos_completados'
                                            ] ?? 0
                                        )
                                    ?>
                                </strong>

                                <span>
                                    Cursos completados
                                </span>

                            </div>


                            <div>

                                <strong>
                                    <?= (int)
                                        (
                                            $resumen[
                                                'evaluaciones_pendientes'
                                            ] ?? 0
                                        )
                                    ?>
                                </strong>

                                <span>
                                    Evaluaciones pendientes
                                </span>

                            </div>


                            <div>

                                <strong>
                                    <?= (int)
                                        (
                                            $resumen[
                                                'cursos_asignados'
                                            ] ?? 0
                                        )
                                    ?>
                                </strong>

                                <span>
                                    Cursos asignados
                                </span>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =========================================
                     TOMAR CURSO
                ========================================== -->

                <?php if ($cursoPrincipal): ?>

                    <section
                        class="take-course-card"
                        id="mis-cursos"
                    >

                        <div class="take-course-header">

                            <div>

                                <span>
                                    CONTINUAR CURSO
                                </span>

                                <h2>

                                    <?= e(
                                        $cursoPrincipal['titulo']
                                    ) ?>

                                </h2>

                            </div>


                            <a
                                href="Curse/course.php?curso_id=<?= (int) $cursoPrincipal['curso_id'] ?>"
                                class="arrow-link"
                            >
                                →
                            </a>

                        </div>


                        <div class="module-list">

                            <?php

                            $modulosDemo = [
                                '¿Qué es el phishing?',
                                'Cómo identificar un correo sospechoso',
                                'Enlaces y archivos maliciosos',
                                'Técnicas de suplantación de identidad',
                                'Cómo reportar un correo fraudulento'
                            ];

                            $modulosMostrar =
                                array_slice(
                                    $modulosDemo,
                                    0,
                                    (int) (
                                        $cursoPrincipal[
                                            'numero_modulos'
                                        ] ?? 5
                                    )
                                );

                            ?>


                            <?php foreach (
                                $modulosMostrar
                                as $indice => $modulo
                            ): ?>

                                <div class="module-item">

                                    <div
                                        class="
                                            module-number
                                            <?= $indice === 0
                                                ? 'done'
                                                : ''
                                            ?>
                                        "
                                    >

                                        <?= $indice === 0
                                            ? '✓'
                                            : $indice + 1
                                        ?>

                                    </div>


                                    <div class="module-info">

                                        <strong>
                                            <?= e($modulo) ?>
                                        </strong>

                                        <span>
                                            Módulo <?= $indice + 1 ?>
                                        </span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <a
                            href="Curse/course.php?curso_id=<?= (int) $cursoPrincipal['curso_id'] ?>"
                            class="continue-module-button"
                        >

                            Continuar curso

                            <span>
                                →
                            </span>

                        </a>

                    </section>

                <?php endif; ?>


            </aside>

        </div>

    </main>

</div>

</body>

</html>