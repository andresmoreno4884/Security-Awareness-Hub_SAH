<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * PANEL ADMINISTRADOR DE EMPRESA
 * ============================================================
 *
 * Este panel es exclusivo para usuarios con rol:
 * admin_empresa
 *
 * La empresa se obtiene desde la sesión mediante:
 * Auth::empresaId()
 *
 * NO se recibe empresa_id desde la URL.
 * ============================================================
 */

require_once __DIR__ . '/../../backend/view_bootstrap.php';

require_once __DIR__ . '/../../backend/Models/EmpresaModel.php';
require_once __DIR__ . '/../../backend/Models/UsuarioModel.php';
require_once __DIR__ . '/../../backend/Models/AsignacionModel.php';


/**
 * ============================================================
 * SEGURIDAD
 * ============================================================
 */

Auth::requireAdminEmpresaView(
    "../authentication/login.php"
);


/**
 * ============================================================
 * DATOS DE SESIÓN
 * ============================================================
 */

$usuarioId = Auth::userId();

$empresaId = Auth::empresaId();


/**
 * Si por alguna razón el administrador empresarial
 * no tiene empresa asociada, no permitimos continuar.
 */

if (!$empresaId) {

    Auth::logout();

    header(
        "Location: ../authentication/login.php"
    );

    exit;
}


/**
 * ============================================================
 * MODELOS
 * ============================================================
 */

$empresaModel =
    new EmpresaModel();

$usuarioModel =
    new UsuarioModel();

$asignacionModel =
    new AsignacionModel();


/**
 * ============================================================
 * VARIABLES
 * ============================================================
 */

$empresa = null;

$usuarios = [];

$asignaciones = [];

$error = '';


/**
 * ============================================================
 * CARGAR INFORMACIÓN DE LA EMPRESA
 * ============================================================
 */

try {

    /**
     * Buscar empresa.
     *
     * El modelo debe devolver la empresa correspondiente
     * al ID almacenado en sesión.
     */
    if (method_exists($empresaModel, 'buscarPorId')) {

        $empresa =
            $empresaModel->buscarPorId(
                $empresaId
            );
    }


    /**
     * ========================================================
     * USUARIOS
     * ========================================================
     *
     * Obtenemos los usuarios y posteriormente filtramos
     * solamente los pertenecientes a la empresa actual.
     */

    if (method_exists($usuarioModel, 'listar')) {

        $todosUsuarios =
            $usuarioModel->listar();

        foreach (
            $todosUsuarios as $usuario
        ) {

            if (
                isset($usuario['empresa_id']) &&
                (int) $usuario['empresa_id']
                === (int) $empresaId
            ) {

                $usuarios[] =
                    $usuario;
            }
        }
    }


    /**
     * ========================================================
     * ASIGNACIONES
     * ========================================================
     */

    if (
        method_exists(
            $asignacionModel,
            'obtenerTodas'
        )
    ) {

        $todasAsignaciones =
            $asignacionModel->obtenerTodas();


        /**
         * Primero identificamos los usuarios
         * pertenecientes a la empresa.
         */

        $usuariosEmpresaIds = [];

        foreach (
            $usuarios as $usuario
        ) {

            if (isset($usuario['id'])) {

                $usuariosEmpresaIds[] =
                    (int) $usuario['id'];
            }
        }


        /**
         * Filtramos las asignaciones.
         */

        foreach (
            $todasAsignaciones as $asignacion
        ) {

            if (
                isset($asignacion['usuario_id']) &&
                in_array(
                    (int) $asignacion['usuario_id'],
                    $usuariosEmpresaIds,
                    true
                )
            ) {

                $asignaciones[] =
                    $asignacion;
            }
        }
    }


} catch (
    Throwable $exception
) {

    $error =
        'No fue posible cargar la información del panel.';
}


/**
 * ============================================================
 * ESTADÍSTICAS
 * ============================================================
 */

$totalUsuarios =
    count($usuarios);


/**
 * Empleados activos.
 */

$usuariosActivos = 0;

foreach (
    $usuarios as $usuario
) {

    if (
        isset($usuario['estado']) &&
        strtolower(
            (string) $usuario['estado']
        ) === 'activo'
    ) {

        $usuariosActivos++;
    }
}


/**
 * Asignaciones.
 */

$totalAsignaciones =
    count($asignaciones);


$asignacionesCompletadas = 0;

$asignacionesEnProgreso = 0;

$asignacionesPendientes = 0;


foreach (
    $asignaciones as $asignacion
) {

    $estado =
        strtolower(
            (string) (
                $asignacion['estado']
                ?? ''
            )
        );


    if (
        $estado === 'completado'
    ) {

        $asignacionesCompletadas++;

    } elseif (
        $estado === 'progreso'
    ) {

        $asignacionesEnProgreso++;

    } else {

        $asignacionesPendientes++;
    }
}


/**
 * ============================================================
 * DATOS DE EMPRESA
 * ============================================================
 */

$nombreEmpresa =
    $empresa['nombre']
    ?? 'Empresa';


$nitEmpresa =
    $empresa['nit']
    ?? 'No registrado';


$correoEmpresa =
    $empresa['correo']
    ?? 'No registrado';


/**
 * ============================================================
 * FECHA
 * ============================================================
 */

$fechaActual =
    date(
        'd/m/Y'
    );

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
        Panel empresarial | Security Awareness Hub
    </title>


    <link
        rel="stylesheet"
        href="css/empresa.css"
    >

</head>


<body>


<!-- =========================================================
     CONTENEDOR PRINCIPAL
========================================================== -->

<div class="empresa-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="empresa-sidebar">


        <div class="empresa-brand">

            <div class="empresa-brand-icon">
                SA
            </div>

            <div>

                <strong>
                    SECURITY
                </strong>

                <span>
                    AWARENESS HUB
                </span>

            </div>

        </div>


        <!-- =================================================
             INFORMACIÓN EMPRESA
        ================================================== -->

        <div class="empresa-sidebar-company">

            <span>
                EMPRESA
            </span>

            <strong>
                <?= htmlspecialchars(
                    $nombreEmpresa,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

        </div>


        <!-- =================================================
             NAVEGACIÓN
        ================================================== -->

        <nav class="empresa-navigation">


            <a
                href="panel.php"
                class="empresa-nav-link active"
            >

                <span class="nav-icon">
                    ▦
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="usuarios.php"
                class="empresa-nav-link"
            >

                <span class="nav-icon">
                    ♙
                </span>

                <span>
                    Empleados
                </span>

            </a>


            <a
                href="asignaciones.php"
                class="empresa-nav-link"
            >

                <span class="nav-icon">
                    ▣
                </span>

                <span>
                    Asignaciones
                </span>

            </a>


            <a
                href="resultados.php"
                class="empresa-nav-link"
            >

                <span class="nav-icon">
                    %
                </span>

                <span>
                    Resultados
                </span>

            </a>


        </nav>


        <!-- =================================================
             CERRAR SESIÓN
        ================================================== -->

        <div class="empresa-sidebar-footer">

            <a
                href="../authentication/logout.php"
                class="empresa-logout"
            >

                <span>
                    ↪
                </span>

                Cerrar sesión

            </a>

        </div>


    </aside>



    <!-- =====================================================
         CONTENIDO
    ====================================================== -->

    <main class="empresa-main">


        <!-- =================================================
             HEADER
        ================================================== -->

        <header class="empresa-header">


            <div>

                <span class="section-label">
                    ADMINISTRACIÓN EMPRESARIAL
                </span>

                <h1>
                    Panel de control
                </h1>

                <p>
                    Gestiona la capacitación y el progreso
                    de los colaboradores de tu organización.
                </p>

            </div>


            <div class="empresa-header-date">

                <span>
                    FECHA
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $fechaActual,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>


        </header>



        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if ($error !== ''): ?>

            <div class="empresa-alert empresa-alert-error">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             IDENTIDAD DE EMPRESA
        ================================================== -->

        <section class="empresa-welcome">


            <div>

                <span class="section-label">
                    ORGANIZACIÓN
                </span>

                <h2>
                    <?= htmlspecialchars(
                        $nombreEmpresa,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <p>
                    NIT:
                    <?= htmlspecialchars(
                        $nitEmpresa,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

            </div>


            <div class="empresa-contact">

                <span>
                    CORREO EMPRESARIAL
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $correoEmpresa,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>


        </section>



        <!-- =================================================
             INDICADORES
        ================================================== -->

        <section class="empresa-stat-grid">


            <!-- EMPLEADOS -->

            <article class="empresa-stat-card">

                <div class="stat-icon">
                    ♙
                </div>

                <div>

                    <span>
                        EMPLEADOS
                    </span>

                    <strong>
                        <?= $totalUsuarios ?>
                    </strong>

                    <small>
                        Usuarios asociados
                    </small>

                </div>

            </article>



            <!-- ACTIVOS -->

            <article class="empresa-stat-card">

                <div class="stat-icon">
                    ✓
                </div>

                <div>

                    <span>
                        ACTIVOS
                    </span>

                    <strong>
                        <?= $usuariosActivos ?>
                    </strong>

                    <small>
                        Empleados activos
                    </small>

                </div>

            </article>



            <!-- ASIGNACIONES -->

            <article class="empresa-stat-card">

                <div class="stat-icon">
                    ▣
                </div>

                <div>

                    <span>
                        ASIGNACIONES
                    </span>

                    <strong>
                        <?= $totalAsignaciones ?>
                    </strong>

                    <small>
                        Capacitaciones asignadas
                    </small>

                </div>

            </article>



            <!-- COMPLETADAS -->

            <article class="empresa-stat-card">

                <div class="stat-icon">
                    %
                </div>

                <div>

                    <span>
                        COMPLETADAS
                    </span>

                    <strong>
                        <?= $asignacionesCompletadas ?>
                    </strong>

                    <small>
                        Asignaciones finalizadas
                    </small>

                </div>

            </article>


        </section>



        <!-- =================================================
             RESUMEN
        ================================================== -->

        <section class="empresa-grid">


            <!-- =================================================
                 ESTADO DE CAPACITACIÓN
            ================================================== -->

            <article class="empresa-panel">


                <div class="empresa-panel-header">

                    <div>

                        <span class="section-label">
                            CAPACITACIÓN
                        </span>

                        <h3>
                            Estado de las asignaciones
                        </h3>

                    </div>

                </div>


                <div class="empresa-progress-list">


                    <div class="empresa-progress-item">

                        <div>

                            <span>
                                Pendientes
                            </span>

                            <strong>
                                <?= $asignacionesPendientes ?>
                            </strong>

                        </div>

                        <div class="progress-bar">

                            <div
                                class="progress-fill pending"
                                style="width: <?= $totalAsignaciones > 0
                                    ? ($asignacionesPendientes / $totalAsignaciones) * 100
                                    : 0 ?>%;"
                            ></div>

                        </div>

                    </div>



                    <div class="empresa-progress-item">

                        <div>

                            <span>
                                En progreso
                            </span>

                            <strong>
                                <?= $asignacionesEnProgreso ?>
                            </strong>

                        </div>

                        <div class="progress-bar">

                            <div
                                class="progress-fill progress"
                                style="width: <?= $totalAsignaciones > 0
                                    ? ($asignacionesEnProgreso / $totalAsignaciones) * 100
                                    : 0 ?>%;"
                            ></div>

                        </div>

                    </div>



                    <div class="empresa-progress-item">

                        <div>

                            <span>
                                Completadas
                            </span>

                            <strong>
                                <?= $asignacionesCompletadas ?>
                            </strong>

                        </div>

                        <div class="progress-bar">

                            <div
                                class="progress-fill completed"
                                style="width: <?= $totalAsignaciones > 0
                                    ? ($asignacionesCompletadas / $totalAsignaciones) * 100
                                    : 0 ?>%;"
                            ></div>

                        </div>

                    </div>


                </div>


            </article>



            <!-- =================================================
                 INFORMACIÓN DE CUENTA
            ================================================== -->

            <article class="empresa-panel">


                <div class="empresa-panel-header">

                    <div>

                        <span class="section-label">
                            CUENTA
                        </span>

                        <h3>
                            Administrador empresarial
                        </h3>

                    </div>

                </div>


                <div class="empresa-account">


                    <div class="account-row">

                        <span>
                            Usuario
                        </span>

                        <strong>
                            ID #<?= (int) $usuarioId ?>
                        </strong>

                    </div>


                    <div class="account-row">

                        <span>
                            Empresa
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $nombreEmpresa,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                    </div>


                    <div class="account-row">

                        <span>
                            Rol
                        </span>

                        <strong>
                            Administrador empresarial
                        </strong>

                    </div>


                </div>


            </article>


        </section>



        <!-- =================================================
             ACCIONES RÁPIDAS
        ================================================== -->

        <section class="empresa-panel empresa-quick-actions">


            <div class="empresa-panel-header">

                <div>

                    <span class="section-label">
                        ADMINISTRACIÓN
                    </span>

                    <h3>
                        Acciones rápidas
                    </h3>

                </div>

            </div>


            <div class="quick-actions-grid">


                <a
                    href="usuarios.php"
                    class="quick-action"
                >

                    <span class="quick-action-icon">
                        ♙
                    </span>

                    <div>

                        <strong>
                            Gestionar empleados
                        </strong>

                        <span>
                            Consulta los colaboradores
                            de tu empresa.
                        </span>

                    </div>

                </a>



                <a
                    href="asignaciones.php"
                    class="quick-action"
                >

                    <span class="quick-action-icon">
                        ▣
                    </span>

                    <div>

                        <strong>
                            Asignar capacitación
                        </strong>

                        <span>
                            Asigna cursos y evaluaciones
                            a tus empleados.
                        </span>

                    </div>

                </a>



                <a
                    href="resultados.php"
                    class="quick-action"
                >

                    <span class="quick-action-icon">
                        %
                    </span>

                    <div>

                        <strong>
                            Consultar resultados
                        </strong>

                        <span>
                            Revisa el progreso y resultados
                            de capacitación.
                        </span>

                    </div>

                </a>


            </div>


        </section>


    </main>

</div>


</body>

</html>