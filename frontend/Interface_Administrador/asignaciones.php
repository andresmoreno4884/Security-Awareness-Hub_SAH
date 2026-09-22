<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Gestión de Asignaciones
 * ============================================================
 *
 * Vista administrativa.
 *
 * Funciona completamente con PHP:
 * - No utiliza JavaScript.
 * - No utiliza AJAX.
 * - No utiliza fetch().
 * - Los formularios utilizan POST.
 * ============================================================
 */

require_once __DIR__ . '/../../backend/view_bootstrap.php';
require_once __DIR__ . '/../../backend/Controllers/AsignacionController.php';

Auth::requireAdminView('../authentication/login.php');


/**
 * ============================================================
 * CONFIGURACIÓN DE LA VISTA
 * ============================================================
 */

$activeView = 'asignaciones';

$pageTitle = 'Asignaciones';

$extraCss = [
    'css/asignaciones.css'
];


/**
 * ============================================================
 * CONTROLADOR
 * ============================================================
 */

$controller = new AsignacionController();


/**
 * ============================================================
 * MENSAJES
 * ============================================================
 */

$mensaje = '';

$tipoMensaje = '';


/**
 * ============================================================
 * PROCESAR FORMULARIOS
 * ============================================================
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? '';


    /**
     * --------------------------------------------------------
     * CREAR
     * --------------------------------------------------------
     */

    if ($accion === 'crear') {

        $resultado = $controller->crear($_POST);

        $mensaje = $resultado['message'];

        $tipoMensaje = $resultado['success']
            ? 'success'
            : 'error';
    }


    /**
     * --------------------------------------------------------
     * ACTUALIZAR
     * --------------------------------------------------------
     */

    elseif ($accion === 'actualizar') {

        $id = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $resultado = $controller->actualizar(
            (int) $id,
            $_POST
        );

        $mensaje = $resultado['message'];

        $tipoMensaje = $resultado['success']
            ? 'success'
            : 'error';
    }


    /**
     * --------------------------------------------------------
     * ELIMINAR
     * --------------------------------------------------------
     */

    elseif ($accion === 'eliminar') {

        $id = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $resultado = $controller->eliminar(
            (int) $id
        );

        $mensaje = $resultado['message'];

        $tipoMensaje = $resultado['success']
            ? 'success'
            : 'error';
    }
}


/**
 * ============================================================
 * DATOS
 * ============================================================
 */

$datos = $controller->obtenerDatosVista();

$asignaciones = $datos['asignaciones'];

$usuarios = $datos['usuarios'];

$cursos = $datos['cursos'];

$evaluaciones = $datos['evaluaciones'];

$estadisticas = $datos['estadisticas'];


/**
 * ============================================================
 * EDITAR
 * ============================================================
 */

$editarId = filter_var(
    $_GET['editar'] ?? null,
    FILTER_VALIDATE_INT
);

$asignacionEditar = null;

if ($editarId && $editarId > 0) {

    $asignacionEditar =
        $controller->obtenerPorId($editarId);
}


/**
 * ============================================================
 * FILTROS
 * ============================================================
 */

$filtroEstado = $_GET['estado'] ?? 'todos';

$filtroTipo = $_GET['tipo'] ?? 'todos';

$busqueda = trim(
    $_GET['buscar'] ?? ''
);


/**
 * ============================================================
 * FILTRAR RESULTADOS
 * ============================================================
 */

$asignacionesFiltradas = array_filter(
    $asignaciones,
    function ($asignacion) use (
        $filtroEstado,
        $filtroTipo,
        $busqueda
    ) {

        if (
            $filtroEstado !== 'todos'
            && $asignacion['estado'] !== $filtroEstado
        ) {
            return false;
        }

        if (
            $filtroTipo !== 'todos'
            && $asignacion['tipo'] !== $filtroTipo
        ) {
            return false;
        }

        if ($busqueda !== '') {

            $textoBusqueda = strtolower(
                $asignacion['usuario']
                . ' '
                . ($asignacion['contenido'] ?? '')
            );

            if (
                strpos(
                    $textoBusqueda,
                    strtolower($busqueda)
                ) === false
            ) {
                return false;
            }
        }

        return true;
    }
);

?>
<?php require __DIR__ . '/partials/_header.php'; ?>


<section class="admin-view">

    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <div class="dashboard-heading">

        <div>

            <span class="section-label">
                GESTIÓN DE CAPACITACIÓN
            </span>

            <h2>
                Asignaciones
            </h2>

            <p>
                Asigna cursos y evaluaciones a los usuarios
                de la plataforma.
            </p>

        </div>

        <div class="admin-actions">

            <a
                href="asignaciones.php?nueva=1"
                class="admin-button"
            >
                + Nueva asignación
            </a>

        </div>

    </div>


    <!-- =====================================================
         MENSAJE
    ====================================================== -->

    <?php if ($mensaje !== ''): ?>

        <div
            class="admin-alert admin-alert-<?= htmlspecialchars(
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


    <!-- =====================================================
         ESTADÍSTICAS
    ====================================================== -->

    <div class="dashboard-cards">

        <div class="dashboard-card">

            <div class="card-icon">
                ✓
            </div>

            <div>

                <span>
                    ASIGNACIONES
                </span>

                <strong>
                    <?= $estadisticas['total'] ?>
                </strong>

                <small>
                    Asignaciones registradas
                </small>

            </div>

        </div>


        <div class="dashboard-card">

            <div class="card-icon">
                ◉
            </div>

            <div>

                <span>
                    ACTIVAS
                </span>

                <strong>
                    <?= $estadisticas['activas'] ?>
                </strong>

                <small>
                    Pendientes o en progreso
                </small>

            </div>

        </div>


        <div class="dashboard-card">

            <div class="card-icon">
                ★
            </div>

            <div>

                <span>
                    COMPLETADAS
                </span>

                <strong>
                    <?= $estadisticas['completadas'] ?>
                </strong>

                <small>
                    Capacitaciones completadas
                </small>

            </div>

        </div>

    </div>


    <!-- =====================================================
         FORMULARIO
    ====================================================== -->

    <?php if (
        isset($_GET['nueva'])
        || $asignacionEditar !== null
    ): ?>

        <div class="dashboard-card">

            <div class="card-header">

                <div>

                    <span class="section-label">
                        ADMINISTRACIÓN
                    </span>

                    <h3>
                        <?= $asignacionEditar
                            ? 'Editar asignación'
                            : 'Nueva asignación'
                        ?>
                    </h3>

                </div>

            </div>


            <form
                method="POST"
                action="asignaciones.php"
            >

                <?php if ($asignacionEditar): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $asignacionEditar['id'] ?>"
                    >

                    <input
                        type="hidden"
                        name="accion"
                        value="actualizar"
                    >

                <?php else: ?>

                    <input
                        type="hidden"
                        name="accion"
                        value="crear"
                    >

                <?php endif; ?>


                <!-- USUARIO -->

                <div class="form-group">

                    <label for="usuario_id">
                        Usuario
                    </label>

                    <select
                        id="usuario_id"
                        name="usuario_id"
                        required
                    >

                        <option value="">
                            Seleccione un usuario
                        </option>

                        <?php foreach ($usuarios as $usuario): ?>

                            <option
                                value="<?= (int) $usuario['id'] ?>"
                                <?= (
                                    $asignacionEditar
                                    && (int) $asignacionEditar['usuario_id']
                                        === (int) $usuario['id']
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $usuario['nombres']
                                    . ' '
                                    . $usuario['apellidos'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                —
                                <?= htmlspecialchars(
                                    $usuario['correo'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- TIPO -->

                <div class="form-group">

                    <label for="tipo">
                        Tipo de asignación
                    </label>

                    <select
                        id="tipo"
                        name="tipo"
                        required
                    >

                        <option
                            value="curso"
                            <?= (
                                !$asignacionEditar
                                || $asignacionEditar['tipo'] === 'curso'
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Curso
                        </option>

                        <option
                            value="evaluacion"
                            <?= (
                                $asignacionEditar
                                && $asignacionEditar['tipo']
                                    === 'evaluacion'
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Evaluación
                        </option>

                    </select>

                </div>


                <!-- CURSO -->

                <div class="form-group">

                    <label for="curso_id">
                        Curso
                    </label>

                    <select
                        id="curso_id"
                        name="curso_id"
                    >

                        <option value="">
                            Seleccione un curso
                        </option>

                        <?php foreach ($cursos as $curso): ?>

                            <option
                                value="<?= (int) $curso['id'] ?>"
                                <?= (
                                    $asignacionEditar
                                    && $asignacionEditar['tipo'] === 'curso'
                                    && (int) $asignacionEditar['curso_id']
                                        === (int) $curso['id']
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $curso['titulo'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                —
                                <?= htmlspecialchars(
                                    $curso['nivel_dificultad'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- EVALUACIÓN -->

                <div class="form-group">

                    <label for="evaluacion_id">
                        Evaluación
                    </label>

                    <select
                        id="evaluacion_id"
                        name="evaluacion_id"
                    >

                        <option value="">
                            Seleccione una evaluación
                        </option>

                        <?php foreach ($evaluaciones as $evaluacion): ?>

                            <option
                                value="<?= (int) $evaluacion['id'] ?>"
                                <?= (
                                    $asignacionEditar
                                    && $asignacionEditar['tipo']
                                        === 'evaluacion'
                                    && (int) $asignacionEditar['evaluacion_id']
                                        === (int) $evaluacion['id']
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $evaluacion['titulo'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                —
                                Curso:
                                <?= htmlspecialchars(
                                    $evaluacion['curso_titulo'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- ESTADO -->

                <div class="form-group">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        id="estado"
                        name="estado"
                        required
                    >

                        <?php
                        $estadoActual =
                            $asignacionEditar['estado']
                            ?? 'pendiente';
                        ?>

                        <option
                            value="pendiente"
                            <?= $estadoActual === 'pendiente'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Pendiente
                        </option>

                        <option
                            value="progreso"
                            <?= $estadoActual === 'progreso'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            En progreso
                        </option>

                        <option
                            value="completado"
                            <?= $estadoActual === 'completado'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Completado
                        </option>

                    </select>

                </div>


                <div class="admin-actions">

                    <button
                        type="submit"
                        class="admin-button"
                    >
                        <?= $asignacionEditar
                            ? 'Guardar cambios'
                            : 'Crear asignación'
                        ?>
                    </button>

                    <a
                        href="asignaciones.php"
                        class="admin-button secondary"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         FILTROS
    ====================================================== -->

    <div class="users-toolbar">

        <form
            method="GET"
            action="asignaciones.php"
            class="users-toolbar"
        >

            <div class="search-box">

                <span class="search-icon">
                    ⌕
                </span>

                <input
                    type="search"
                    name="buscar"
                    value="<?= htmlspecialchars(
                        $busqueda,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Buscar usuario o curso..."
                >

            </div>


            <select
                class="users-filter"
                name="estado"
            >

                <option value="todos">
                    Todos los estados
                </option>

                <option
                    value="pendiente"
                    <?= $filtroEstado === 'pendiente'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Pendientes
                </option>

                <option
                    value="progreso"
                    <?= $filtroEstado === 'progreso'
                        ? 'selected'
                        : ''
                    ?>
                >
                    En progreso
                </option>

                <option
                    value="completado"
                    <?= $filtroEstado === 'completado'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Completadas
                </option>

            </select>


            <select
                class="users-filter"
                name="tipo"
            >

                <option value="todos">
                    Todos los tipos
                </option>

                <option
                    value="curso"
                    <?= $filtroTipo === 'curso'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Cursos
                </option>

                <option
                    value="evaluacion"
                    <?= $filtroTipo === 'evaluacion'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Evaluaciones
                </option>

            </select>


            <button
                type="submit"
                class="admin-button"
            >
                Filtrar
            </button>

            <a
                href="asignaciones.php"
                class="admin-button secondary"
            >
                Limpiar
            </a>

        </form>

    </div>


    <!-- =====================================================
         TABLA
    ====================================================== -->

    <div class="dashboard-card users-table-card">

        <div class="card-header">

            <div>

                <span class="section-label">
                    DIRECTORIO
                </span>

                <h3>
                    Asignaciones registradas
                </h3>

            </div>

            <span class="card-period">

                <?= count($asignacionesFiltradas) ?>
                registro(s)

            </span>

        </div>


        <div class="table-wrapper">

            <table class="users-table">

                <thead>

                    <tr>

                        <th>
                            USUARIO
                        </th>

                        <th>
                            CURSO / EVALUACIÓN
                        </th>

                        <th>
                            TIPO
                        </th>

                        <th>
                            ESTADO
                        </th>

                        <th>
                            FECHA
                        </th>

                        <th>
                            ACCIONES
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (empty($asignacionesFiltradas)): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="loading-users"
                            >

                                No hay asignaciones
                                para mostrar.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $asignacionesFiltradas
                            as $asignacion
                        ): ?>

                            <tr>

                                <td>

                                    <?= htmlspecialchars(
                                        $asignacion['usuario'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $asignacion['contenido'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <td>

                                    <?= $asignacion['tipo'] === 'curso'
                                        ? 'Curso'
                                        : 'Evaluación'
                                    ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $asignacion['estado']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $asignacion[
                                                    'fecha_asignacion'
                                                ]
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <td>

                                    <a
                                        href="asignaciones.php?editar=<?= (int) $asignacion['id'] ?>"
                                        class="admin-button"
                                    >
                                        Editar
                                    </a>


                                    <form
                                        method="POST"
                                        action="asignaciones.php"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="eliminar"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $asignacion['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="admin-button danger"
                                        >
                                            Eliminar
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="table-footer">

            <span>

                Mostrando
                <?= count($asignacionesFiltradas) ?>
                asignación(es)

            </span>

        </div>

    </div>

</section>


<?php require __DIR__ . '/partials/_footer.php'; ?>