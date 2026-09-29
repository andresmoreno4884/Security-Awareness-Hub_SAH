<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * empresas.php
 * CRUD de empresas, 100% PHP (sin JavaScript, sin fetch).
 * Mismo patrón que usuarios.php: formularios POST normales
 * y redirect después de guardar (Post/Redirect/Get).
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::requireAdminView("../authentication/login.php");

$model = new EmpresaModel();

$TIPOS = ["privada", "publica", "mixta"];

/* =====================================================
   PROCESAR ACCIONES (POST)
   ===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar()) {
        header("Location: empresas.php?msg=" . urlencode("Tu formulario expiró, inténtalo de nuevo.") . "&tipo=error");
        exit;
    }

    $accion = $_POST["accion"] ?? "";

    if ($accion === "crear" || $accion === "editar") {

        $id = (int) ($_POST["id"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");
        $nit = trim($_POST["nit"] ?? "");
        $correo = strtolower(trim($_POST["correo"] ?? ""));
        $telefono = trim($_POST["telefono"] ?? "");
        $direccion = trim($_POST["direccion"] ?? "");
        $tipoEmpresa = $_POST["tipo"] ?? "privada";
        $estado = $_POST["estado"] ?? "activo";

        $error = "";

        if ($nombre === "" || $nit === "" || $correo === "") {
            $error = "Todos los campos obligatorios deben completarse.";
        } elseif (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 150) {
            $error = "El nombre de la empresa debe tener entre 2 y 150 caracteres.";
        } elseif (mb_strlen($nit) > 30) {
            $error = "El NIT no puede superar los 30 caracteres.";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo electrónico no es válido.";
        } elseif (!in_array($tipoEmpresa, $TIPOS, true)) {
            $error = "El tipo de empresa no es válido.";
        } elseif (!in_array($estado, ["activo", "inactivo"], true)) {
            $error = "El estado seleccionado no es válido.";
        }

        if ($error === "") {

            try {

                if ($accion === "crear") {

                    if ($model->nitExiste($nit)) {
                        $error = "Ya existe una empresa registrada con ese NIT.";
                    } else {
                        $model->crear([
                            "nombre" => $nombre, "nit" => $nit, "correo" => $correo,
                            "telefono" => $telefono, "direccion" => $direccion,
                            "tipo" => $tipoEmpresa, "estado" => $estado
                        ]);
                    }

                } else {

                    if ($id <= 0 || !$model->existe($id)) {
                        $error = "La empresa no existe.";
                    } elseif ($model->nitExiste($nit, $id)) {
                        $error = "Ya existe otra empresa registrada con ese NIT.";
                    } else {
                        $model->actualizar($id, [
                            "nombre" => $nombre, "nit" => $nit, "correo" => $correo,
                            "telefono" => $telefono, "direccion" => $direccion,
                            "tipo" => $tipoEmpresa, "estado" => $estado
                        ]);
                    }
                }

            } catch (PDOException $e) {
                $error = $e->getCode() === "23000"
                    ? "Ya existe una empresa con ese NIT."
                    : "No fue posible guardar la empresa.";
            } catch (Throwable $e) {
                $error = "No fue posible guardar la empresa.";
            }
        }

        if ($error !== "") {
            header("Location: empresas.php?" . ($id > 0 ? "editar={$id}&" : "nuevo=1&") . "msg=" . urlencode($error) . "&tipo=error");
            exit;
        }

        header("Location: empresas.php?msg=" . urlencode($accion === "crear" ? "Empresa creada correctamente." : "Empresa actualizada correctamente."));
        exit;

    } elseif ($accion === "estado") {

        $id = (int) ($_POST["id"] ?? 0);
        $nuevoEstado = $_POST["estado"] ?? "";

        if ($id > 0 && in_array($nuevoEstado, ["activo", "inactivo"], true) && $model->existe($id)) {
            $model->cambiarEstado($id, $nuevoEstado);
        }

        header("Location: empresas.php?msg=" . urlencode($nuevoEstado === "activo" ? "Empresa activada." : "Empresa desactivada."));
        exit;

    } elseif ($accion === "eliminar") {

        $id = (int) ($_POST["id"] ?? 0);

        if ($id > 0 && $model->existe($id)) {
            try {
                $model->eliminar($id);
                $msg = "Empresa eliminada correctamente.";
                $tipoMsg = "exito";
            } catch (PDOException $e) {
                $msg = $e->getCode() === "23000"
                    ? "No se puede eliminar la empresa porque tiene registros relacionados. Se recomienda desactivarla."
                    : "No fue posible eliminar la empresa.";
                $tipoMsg = "error";
            }
        } else {
            $msg = "La empresa no existe.";
            $tipoMsg = "error";
        }

        header("Location: empresas.php?msg=" . urlencode($msg) . ($tipoMsg === "error" ? "&tipo=error" : ""));
        exit;
    }
}

/* =====================================================
   BÚSQUEDA / FILTROS (GET)
   ===================================================== */

$search = trim($_GET["search"] ?? "");
$estadoFiltro = $_GET["estado"] ?? "todos";
$tipoFiltro = $_GET["tipo"] ?? "todos";

$todos = $model->listar($search, $estadoFiltro === "todos" ? "" : $estadoFiltro, $tipoFiltro === "todos" ? "" : $tipoFiltro);

/* PAGINACIÓN SIMPLE */
$porPagina = 10;
$paginaActual = max(1, (int) ($_GET["pagina"] ?? 1));
$totalPaginas = max(1, (int) ceil(count($todos) / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$empresasPagina = array_slice($todos, ($paginaActual - 1) * $porPagina, $porPagina);

/* =====================================================
   ¿MOSTRAR FORMULARIO DE CREAR/EDITAR?
   ===================================================== */

$editando = null;

if (isset($_GET["editar"])) {
    foreach ($model->listar() as $e) {
        if ((int) $e["id"] === (int) $_GET["editar"]) {
            $editando = $e;
            break;
        }
    }
}

$mostrarFormulario = isset($_GET["nuevo"]) || $editando !== null;

/* Confirmación de eliminación sin JavaScript */
$confirmandoEliminar = null;
if (isset($_GET["confirmar_eliminar"])) {
    foreach ($model->listar() as $e) {
        if ((int) $e["id"] === (int) $_GET["confirmar_eliminar"]) {
            $confirmandoEliminar = $e;
            break;
        }
    }
}

$activeView = "empresas";
$pageTitle = "Empresas";
$extraCss = ["css/empresas.css"];
require __DIR__ . "/partials/_header.php";
require __DIR__ . "/partials/_flash.php";
?>

<div class="dashboard-heading">
    <div>
        <span class="section-label">GESTIÓN DE EMPRESAS</span>
        <h2>Empresas</h2>
        <p>Administra las empresas registradas dentro de la plataforma.</p>
    </div>

    <?php if (!$mostrarFormulario && !$confirmandoEliminar): ?>
        <a href="empresas.php?nuevo=1" class="admin-button">+ Nueva empresa</a>
    <?php endif; ?>
</div>

<?php if ($confirmandoEliminar): ?>

    <!-- =================================================
         CONFIRMACIÓN DE ELIMINACIÓN (sin JavaScript)
    ================================================== -->

    <div class="dashboard-card users-table-card" style="padding:24px;">
        <div class="card-header">
            <div>
                <span class="section-label">CONFIRMAR</span>
                <h3>Eliminar empresa</h3>
            </div>
        </div>

        <p>¿Seguro que deseas eliminar a
            <strong><?= htmlspecialchars($confirmandoEliminar["nombre"], ENT_QUOTES, "UTF-8") ?></strong>
            (NIT <?= htmlspecialchars($confirmandoEliminar["nit"], ENT_QUOTES, "UTF-8") ?>)? Esta acción no se puede deshacer.
        </p>

        <div class="form-actions">
            <a href="empresas.php" class="admin-secondary-button">Cancelar</a>
            <form method="POST" action="empresas.php">
                <?php Csrf::campo(); ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $confirmandoEliminar["id"] ?>">
                <button type="submit" class="admin-button">Sí, eliminar</button>
            </form>
        </div>
    </div>

<?php elseif ($mostrarFormulario): ?>

    <!-- =================================================
         FORMULARIO CREAR / EDITAR
    ================================================== -->

    <div class="dashboard-card users-table-card" style="padding:24px;">

        <div class="card-header">
            <div>
                <span class="section-label">ADMINISTRACIÓN</span>
                <h3><?= $editando ? "Editar empresa" : "Nueva empresa" ?></h3>
            </div>
        </div>

        <form method="POST" action="empresas.php">

            <?php Csrf::campo(); ?>
            <input type="hidden" name="accion" value="<?= $editando ? "editar" : "crear" ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= (int) $editando["id"] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="empresaNombre">Nombre</label>
                <input type="text" id="empresaNombre" name="nombre" maxlength="150"
                    value="<?= htmlspecialchars($editando["nombre"] ?? "", ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="empresaNit">NIT</label>
                <input type="text" id="empresaNit" name="nit" maxlength="30"
                    value="<?= htmlspecialchars($editando["nit"] ?? "", ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="empresaCorreo">Correo electrónico</label>
                <input type="email" id="empresaCorreo" name="correo" maxlength="150"
                    value="<?= htmlspecialchars($editando["correo"] ?? "", ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="empresaTelefono">Teléfono</label>
                <input type="text" id="empresaTelefono" name="telefono" maxlength="30"
                    value="<?= htmlspecialchars($editando["telefono"] ?? "", ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="empresaDireccion">Dirección</label>
                <input type="text" id="empresaDireccion" name="direccion" maxlength="200"
                    value="<?= htmlspecialchars($editando["direccion"] ?? "", ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="empresaTipo">Tipo</label>
                <select id="empresaTipo" name="tipo" required>
                    <option value="privada" <?= ($editando["tipo"] ?? "privada") === "privada" ? "selected" : "" ?>>Privada</option>
                    <option value="publica" <?= ($editando["tipo"] ?? "") === "publica" ? "selected" : "" ?>>Pública</option>
                    <option value="mixta" <?= ($editando["tipo"] ?? "") === "mixta" ? "selected" : "" ?>>Mixta</option>
                </select>
            </div>

            <div class="form-group">
                <label for="empresaEstado">Estado</label>
                <select id="empresaEstado" name="estado" required>
                    <option value="activo" <?= ($editando["estado"] ?? "activo") === "activo" ? "selected" : "" ?>>Activo</option>
                    <option value="inactivo" <?= ($editando["estado"] ?? "") === "inactivo" ? "selected" : "" ?>>Inactivo</option>
                </select>
            </div>

            <div class="form-actions">
                <a href="empresas.php" class="admin-secondary-button">Cancelar</a>
                <button type="submit" class="admin-button"><?= $editando ? "Guardar cambios" : "Crear empresa" ?></button>
            </div>

        </form>

    </div>

<?php else: ?>

    <!-- =================================================
         FILTROS (recargan la página con ?search=&estado=&tipo=)
    ================================================== -->

    <form method="GET" action="empresas.php" class="users-toolbar">

        <div class="search-box">
            <span class="search-icon">⌕</span>
            <input type="search" name="search" placeholder="Buscar por nombre, NIT o correo..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>">
        </div>

        <select class="users-filter" name="estado">
            <option value="todos" <?= $estadoFiltro === "todos" ? "selected" : "" ?>>Todos los estados</option>
            <option value="activo" <?= $estadoFiltro === "activo" ? "selected" : "" ?>>Activos</option>
            <option value="inactivo" <?= $estadoFiltro === "inactivo" ? "selected" : "" ?>>Inactivos</option>
        </select>

        <select class="users-filter" name="tipo">
            <option value="todos" <?= $tipoFiltro === "todos" ? "selected" : "" ?>>Todos los tipos</option>
            <option value="privada" <?= $tipoFiltro === "privada" ? "selected" : "" ?>>Privada</option>
            <option value="publica" <?= $tipoFiltro === "publica" ? "selected" : "" ?>>Pública</option>
            <option value="mixta" <?= $tipoFiltro === "mixta" ? "selected" : "" ?>>Mixta</option>
        </select>

        <button type="submit" class="admin-button">Buscar</button>

    </form>

    <div class="dashboard-card users-table-card">

        <div class="card-header">
            <div>
                <span class="section-label">DIRECTORIO</span>
                <h3>Empresas registradas</h3>
            </div>
            <span class="card-period"><?= count($todos) ?> empresa(s)</span>
        </div>

        <div class="table-wrapper">
            <table class="users-table companies-table">
                <thead>
                    <tr>
                        <th>EMPRESA</th>
                        <th>NIT</th>
                        <th>TIPO</th>
                        <th>ESTADO</th>
                        <th>FECHA DE REGISTRO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($empresasPagina)): ?>
                        <tr><td colspan="6" class="loading-users">No se encontraron empresas.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($empresasPagina as $e): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($e["nombre"], ENT_QUOTES, "UTF-8") ?></strong><br>
                                <small><?= htmlspecialchars($e["correo"], ENT_QUOTES, "UTF-8") ?></small>
                            </td>
                            <td><?= htmlspecialchars($e["nit"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= ucfirst(htmlspecialchars($e["tipo"], ENT_QUOTES, "UTF-8")) ?></td>
                            <td>
                                <span class="badge badge-<?= $e["estado"] ?>"><?= ucfirst($e["estado"]) ?></span>
                            </td>
                            <td><?= htmlspecialchars($e["fecha_registro"], ENT_QUOTES, "UTF-8") ?></td>
                            <td class="table-actions">

                                <a href="empresas.php?editar=<?= (int) $e["id"] ?>" class="link-action">Editar</a>

                                <form method="POST" action="empresas.php">
                                    <?php Csrf::campo(); ?>
                                    <input type="hidden" name="accion" value="estado">
                                    <input type="hidden" name="id" value="<?= (int) $e["id"] ?>">
                                    <input type="hidden" name="estado" value="<?= $e["estado"] === "activo" ? "inactivo" : "activo" ?>">
                                    <button type="submit" class="link-action">
                                        <?= $e["estado"] === "activo" ? "Desactivar" : "Activar" ?>
                                    </button>
                                </form>

                                <a href="empresas.php?confirmar_eliminar=<?= (int) $e["id"] ?>" class="link-action">Eliminar</a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span>Página <?= $paginaActual ?> de <?= $totalPaginas ?> — <?= count($todos) ?> empresa(s) en total</span>

            <div class="pagination">
                <a class="pagination-button <?= $paginaActual <= 1 ? "disabled" : "" ?>"
                    href="empresas.php?pagina=<?= max(1, $paginaActual - 1) ?>&search=<?= urlencode($search) ?>&estado=<?= urlencode($estadoFiltro) ?>&tipo=<?= urlencode($tipoFiltro) ?>">←</a>
                <span class="pagination-button active"><?= $paginaActual ?></span>
                <a class="pagination-button <?= $paginaActual >= $totalPaginas ? "disabled" : "" ?>"
                    href="empresas.php?pagina=<?= min($totalPaginas, $paginaActual + 1) ?>&search=<?= urlencode($search) ?>&estado=<?= urlencode($estadoFiltro) ?>&tipo=<?= urlencode($tipoFiltro) ?>">→</a>
            </div>
        </div>

    </div>

<?php endif; ?>

<?php require __DIR__ . "/partials/_footer.php"; ?>