<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * courses.php
 * CRUD de cursos, 100% PHP (sin JavaScript, sin fetch).
 * Mismo patrón que usuarios.php / empresas.php.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::requireAdminView("../authentication/login.php");

$model = new CursoModel();

$NIVELES = ["basico", "intermedio", "avanzado"];

/* =====================================================
   PROCESAR ACCIONES (POST)
   ===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar()) {
        header("Location: courses.php?msg=" . urlencode("Tu formulario expiró, inténtalo de nuevo.") . "&tipo=error");
        exit;
    }

    $accion = $_POST["accion"] ?? "";

    if ($accion === "crear" || $accion === "editar") {

        $id = (int) ($_POST["id"] ?? 0);
        $titulo = trim($_POST["titulo"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $nivel = $_POST["nivel_dificultad"] ?? "basico";
        $duracion = trim($_POST["duracion"] ?? "");
        $numeroModulos = (int) ($_POST["numero_modulos"] ?? 1);
        $porcentaje = (int) ($_POST["porcentaje_aprobacion"] ?? 70);
        $estado = $_POST["estado"] ?? "activo";

        $error = "";

        if ($titulo === "" || $descripcion === "" || $duracion === "") {
            $error = "Todos los campos obligatorios deben completarse.";
        } elseif (!in_array($nivel, $NIVELES, true)) {
            $error = "El nivel de dificultad no es válido.";
        } elseif ($numeroModulos < 1) {
            $error = "El número de módulos debe ser mayor que 0.";
        } elseif ($porcentaje < 0 || $porcentaje > 100) {
            $error = "El porcentaje de aprobación debe estar entre 0 y 100.";
        } elseif (!in_array($estado, ["activo", "inactivo"], true)) {
            $error = "El estado seleccionado no es válido.";
        }

        if ($error === "") {

            try {

                if ($accion === "crear") {

                    if ($model->tituloExiste($titulo)) {
                        $error = "Ya existe un curso con ese título.";
                    } else {
                        $model->crear([
                            "titulo" => $titulo, "descripcion" => $descripcion,
                            "nivel_dificultad" => $nivel, "duracion" => $duracion,
                            "numero_modulos" => $numeroModulos, "porcentaje_aprobacion" => $porcentaje,
                            "estado" => $estado
                        ]);
                    }

                } else {

                    if ($id <= 0 || !$model->existe($id)) {
                        $error = "El curso no existe.";
                    } elseif ($model->tituloExiste($titulo, $id)) {
                        $error = "Ya existe otro curso con ese título.";
                    } else {
                        $model->actualizar($id, [
                            "titulo" => $titulo, "descripcion" => $descripcion,
                            "nivel_dificultad" => $nivel, "duracion" => $duracion,
                            "numero_modulos" => $numeroModulos, "porcentaje_aprobacion" => $porcentaje,
                            "estado" => $estado
                        ]);
                    }
                }

            } catch (Throwable $e) {
                $error = "No fue posible guardar el curso.";
            }
        }

        if ($error !== "") {
            header("Location: courses.php?" . ($id > 0 ? "editar={$id}&" : "nuevo=1&") . "msg=" . urlencode($error) . "&tipo=error");
            exit;
        }

        header("Location: courses.php?msg=" . urlencode($accion === "crear" ? "Curso creado correctamente." : "Curso actualizado correctamente."));
        exit;

    } elseif ($accion === "estado") {

        $id = (int) ($_POST["id"] ?? 0);
        $nuevoEstado = $_POST["estado"] ?? "";

        if ($id > 0 && in_array($nuevoEstado, ["activo", "inactivo"], true) && $model->existe($id)) {
            $model->cambiarEstado($id, $nuevoEstado);
        }

        header("Location: courses.php?msg=" . urlencode($nuevoEstado === "activo" ? "Curso activado." : "Curso desactivado."));
        exit;

    } elseif ($accion === "eliminar") {

        $id = (int) ($_POST["id"] ?? 0);

        if ($id > 0 && $model->existe($id)) {
            try {
                $model->eliminar($id);
                $msg = "Curso eliminado correctamente.";
                $tipoMsg = "exito";
            } catch (PDOException $e) {
                $msg = $e->getCode() === "23000"
                    ? "No se puede eliminar el curso porque tiene registros relacionados. Se recomienda desactivarlo."
                    : "No fue posible eliminar el curso.";
                $tipoMsg = "error";
            }
        } else {
            $msg = "El curso no existe.";
            $tipoMsg = "error";
        }

        header("Location: courses.php?msg=" . urlencode($msg) . ($tipoMsg === "error" ? "&tipo=error" : ""));
        exit;
    }
}

/* =====================================================
   LISTADO + FILTRO EN MEMORIA (el modelo no filtra por SQL)
   ===================================================== */

$search = trim($_GET["search"] ?? "");
$estadoFiltro = $_GET["estado"] ?? "todos";

$todos = $model->listar();

if ($search !== "") {
    $buscar = mb_strtolower($search);
    $todos = array_values(array_filter($todos, function ($c) use ($buscar) {
        return str_contains(mb_strtolower($c["titulo"]), $buscar)
            || str_contains(mb_strtolower($c["descripcion"]), $buscar);
    }));
}

if ($estadoFiltro !== "todos") {
    $todos = array_values(array_filter($todos, fn($c) => $c["estado"] === $estadoFiltro));
}

/* PAGINACIÓN SIMPLE */
$porPagina = 10;
$paginaActual = max(1, (int) ($_GET["pagina"] ?? 1));
$totalPaginas = max(1, (int) ceil(count($todos) / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$cursosPagina = array_slice($todos, ($paginaActual - 1) * $porPagina, $porPagina);

/* =====================================================
   ¿MOSTRAR FORMULARIO DE CREAR/EDITAR?
   ===================================================== */

$editando = null;

if (isset($_GET["editar"])) {
    foreach ($model->listar() as $c) {
        if ((int) $c["id"] === (int) $_GET["editar"]) {
            $editando = $c;
            break;
        }
    }
}

$mostrarFormulario = isset($_GET["nuevo"]) || $editando !== null;

/* Confirmación de eliminación sin JavaScript */
$confirmandoEliminar = null;
if (isset($_GET["confirmar_eliminar"])) {
    foreach ($model->listar() as $c) {
        if ((int) $c["id"] === (int) $_GET["confirmar_eliminar"]) {
            $confirmandoEliminar = $c;
            break;
        }
    }
}

$activeView = "courses_admin";
$pageTitle = "Cursos";
$extraCss = ["css/courses_admin.css"];
require __DIR__ . "/partials/_header.php";
require __DIR__ . "/partials/_flash.php";
?>

<div class="dashboard-heading">
    <div>
        <span class="section-label">GESTIÓN DE CURSOS</span>
        <h2>Cursos</h2>
        <p>Administra los cursos disponibles dentro de la plataforma.</p>
    </div>

    <?php if (!$mostrarFormulario && !$confirmandoEliminar): ?>
        <a href="courses.php?nuevo=1" class="admin-button">+ Nuevo curso</a>
    <?php endif; ?>
</div>

<?php if ($confirmandoEliminar): ?>

    <div class="dashboard-card users-table-card" style="padding:24px;">
        <div class="card-header">
            <div>
                <span class="section-label">CONFIRMAR</span>
                <h3>Eliminar curso</h3>
            </div>
        </div>

        <p>¿Seguro que deseas eliminar el curso
            <strong><?= htmlspecialchars($confirmandoEliminar["titulo"], ENT_QUOTES, "UTF-8") ?></strong>?
            Esta acción no se puede deshacer.
        </p>

        <div class="form-actions">
            <a href="courses.php" class="admin-secondary-button">Cancelar</a>
            <form method="POST" action="courses.php">
                <?php Csrf::campo(); ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $confirmandoEliminar["id"] ?>">
                <button type="submit" class="admin-button">Sí, eliminar</button>
            </form>
        </div>
    </div>

<?php elseif ($mostrarFormulario): ?>

    <div class="dashboard-card users-table-card" style="padding:24px;">

        <div class="card-header">
            <div>
                <span class="section-label">ADMINISTRACIÓN</span>
                <h3><?= $editando ? "Editar curso" : "Nuevo curso" ?></h3>
            </div>
        </div>

        <form method="POST" action="courses.php">

            <?php Csrf::campo(); ?>
            <input type="hidden" name="accion" value="<?= $editando ? "editar" : "crear" ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= (int) $editando["id"] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="cursoTitulo">Título</label>
                <input type="text" id="cursoTitulo" name="titulo" maxlength="150"
                    value="<?= htmlspecialchars($editando["titulo"] ?? "", ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="cursoDescripcion">Descripción</label>
                <textarea id="cursoDescripcion" name="descripcion" rows="4" required><?= htmlspecialchars($editando["descripcion"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>
            </div>

            <div class="form-group">
                <label for="cursoNivel">Nivel de dificultad</label>
                <select id="cursoNivel" name="nivel_dificultad" required>
                    <option value="basico" <?= ($editando["nivel_dificultad"] ?? "basico") === "basico" ? "selected" : "" ?>>Básico</option>
                    <option value="intermedio" <?= ($editando["nivel_dificultad"] ?? "") === "intermedio" ? "selected" : "" ?>>Intermedio</option>
                    <option value="avanzado" <?= ($editando["nivel_dificultad"] ?? "") === "avanzado" ? "selected" : "" ?>>Avanzado</option>
                </select>
            </div>

            <div class="form-group">
                <label for="cursoDuracion">Duración (horas)</label>
                <input type="number" id="cursoDuracion" name="duracion" min="1"
                    value="<?= htmlspecialchars((string) ($editando["duracion"] ?? ""), ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="cursoModulos">Número de módulos</label>
                <input type="number" id="cursoModulos" name="numero_modulos" min="1"
                    value="<?= htmlspecialchars((string) ($editando["numero_modulos"] ?? 1), ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="cursoPorcentaje">Porcentaje de aprobación (%)</label>
                <input type="number" id="cursoPorcentaje" name="porcentaje_aprobacion" min="0" max="100"
                    value="<?= htmlspecialchars((string) ($editando["porcentaje_aprobacion"] ?? 70), ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="cursoEstado">Estado</label>
                <select id="cursoEstado" name="estado" required>
                    <option value="activo" <?= ($editando["estado"] ?? "activo") === "activo" ? "selected" : "" ?>>Activo</option>
                    <option value="inactivo" <?= ($editando["estado"] ?? "") === "inactivo" ? "selected" : "" ?>>Inactivo</option>
                </select>
            </div>

            <div class="form-actions">
                <a href="courses.php" class="admin-secondary-button">Cancelar</a>
                <button type="submit" class="admin-button"><?= $editando ? "Guardar cambios" : "Crear curso" ?></button>
            </div>

        </form>

    </div>

<?php else: ?>

    <form method="GET" action="courses.php" class="users-toolbar">

        <div class="search-box">
            <span class="search-icon">⌕</span>
            <input type="search" name="search" placeholder="Buscar por título o descripción..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>">
        </div>

        <select class="users-filter" name="estado">
            <option value="todos" <?= $estadoFiltro === "todos" ? "selected" : "" ?>>Todos los estados</option>
            <option value="activo" <?= $estadoFiltro === "activo" ? "selected" : "" ?>>Activos</option>
            <option value="inactivo" <?= $estadoFiltro === "inactivo" ? "selected" : "" ?>>Inactivos</option>
        </select>

        <button type="submit" class="admin-button">Buscar</button>

    </form>

    <div class="dashboard-card users-table-card">

        <div class="card-header">
            <div>
                <span class="section-label">CATÁLOGO</span>
                <h3>Cursos disponibles</h3>
            </div>
            <span class="card-period"><?= count($todos) ?> curso(s)</span>
        </div>

        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>CURSO</th>
                        <th>NIVEL</th>
                        <th>DURACIÓN</th>
                        <th>MÓDULOS</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cursosPagina)): ?>
                        <tr><td colspan="6" class="loading-users">No se encontraron cursos.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($cursosPagina as $c): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($c["titulo"], ENT_QUOTES, "UTF-8") ?></strong>
                            </td>
                            <td><?= ucfirst(htmlspecialchars($c["nivel_dificultad"], ENT_QUOTES, "UTF-8")) ?></td>
                            <td><?= (int) $c["duracion"] ?>h</td>
                            <td><?= (int) $c["numero_modulos"] ?></td>
                            <td>
                                <span class="badge badge-<?= $c["estado"] ?>"><?= ucfirst($c["estado"]) ?></span>
                            </td>
                            <td class="table-actions">

                                <a href="courses.php?editar=<?= (int) $c["id"] ?>" class="link-action">Editar</a>

                                <form method="POST" action="courses.php">
                                    <?php Csrf::campo(); ?>
                                    <input type="hidden" name="accion" value="estado">
                                    <input type="hidden" name="id" value="<?= (int) $c["id"] ?>">
                                    <input type="hidden" name="estado" value="<?= $c["estado"] === "activo" ? "inactivo" : "activo" ?>">
                                    <button type="submit" class="link-action">
                                        <?= $c["estado"] === "activo" ? "Desactivar" : "Activar" ?>
                                    </button>
                                </form>

                                <a href="courses.php?confirmar_eliminar=<?= (int) $c["id"] ?>" class="link-action">Eliminar</a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span>Página <?= $paginaActual ?> de <?= $totalPaginas ?> — <?= count($todos) ?> curso(s) en total</span>

            <div class="pagination">
                <a class="pagination-button <?= $paginaActual <= 1 ? "disabled" : "" ?>"
                    href="courses.php?pagina=<?= max(1, $paginaActual - 1) ?>&search=<?= urlencode($search) ?>&estado=<?= urlencode($estadoFiltro) ?>">←</a>
                <span class="pagination-button active"><?= $paginaActual ?></span>
                <a class="pagination-button <?= $paginaActual >= $totalPaginas ? "disabled" : "" ?>"
                    href="courses.php?pagina=<?= min($totalPaginas, $paginaActual + 1) ?>&search=<?= urlencode($search) ?>&estado=<?= urlencode($estadoFiltro) ?>">→</a>
            </div>
        </div>

    </div>

<?php endif; ?>

<?php require __DIR__ . "/partials/_footer.php"; ?>