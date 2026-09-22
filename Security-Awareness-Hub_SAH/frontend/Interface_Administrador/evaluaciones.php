<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * evaluaciones.php
 * CRUD de evaluaciones (con preguntas y opciones anidadas),
 * 100% PHP, sin JavaScript ni fetch.
 *
 * Cada pregunta tiene un bloque fijo de 4 opciones (las vacías
 * se descartan) y un radio para marcar cuál es la correcta.
 * Los botones "Agregar pregunta" / "Quitar pregunta" son
 * <button type="submit"> normales: recargan la página con un
 * bloque más (o menos), conservando lo ya escrito porque se
 * vuelve a mostrar con los valores de $_POST, sin perder datos
 * y sin usar JavaScript.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::requireAdminView("../authentication/login.php");

$model = new EvaluacionModel();
$cursoModel = new CursoModel();
$cursosDisponibles = $cursoModel->listar();

const MAX_OPCIONES = 4;
const MAX_PREGUNTAS = 15;

/**
 * Construye el arreglo "estado del formulario" (curso, título,
 * descripción, porcentaje y preguntas) a partir de $_POST.
 */
function leerFormularioDesdePost(): array
{
    $numPreguntas = max(1, min(MAX_PREGUNTAS, (int) ($_POST["num_preguntas"] ?? 1)));
    $preguntasPost = $_POST["preguntas"] ?? [];

    $preguntas = [];
    for ($i = 1; $i <= $numPreguntas; $i++) {
        $enunciado = trim($preguntasPost[$i]["enunciado"] ?? "");
        $correcta = (int) ($preguntasPost[$i]["correcta"] ?? 0);
        $opciones = [];
        for ($j = 1; $j <= MAX_OPCIONES; $j++) {
            $opciones[$j] = trim($preguntasPost[$i]["opciones"][$j] ?? "");
        }
        $preguntas[$i] = ["enunciado" => $enunciado, "opciones" => $opciones, "correcta" => $correcta];
    }

    return [
        "curso_id" => (int) ($_POST["curso_id"] ?? 0),
        "titulo" => trim($_POST["titulo"] ?? ""),
        "descripcion" => trim($_POST["descripcion"] ?? ""),
        "porcentaje_aprobacion" => (int) ($_POST["porcentaje_aprobacion"] ?? 70),
        "num_preguntas" => $numPreguntas,
        "preguntas" => $preguntas
    ];
}

/**
 * Convierte el "estado del formulario" (curso/título/preguntas con
 * radio de opción correcta) al arreglo que EvaluacionModel espera:
 * cada opción con su propio "es_correcta", y descarta opciones vacías.
 */
function prepararPreguntasParaGuardar(array $preguntas): array
{
    $listas = [];

    foreach ($preguntas as $pregunta) {
        $opcionesValidas = [];
        foreach ($pregunta["opciones"] as $indice => $texto) {
            if ($texto !== "") {
                $opcionesValidas[] = [
                    "texto" => $texto,
                    "es_correcta" => ((int) $pregunta["correcta"] === (int) $indice) ? 1 : 0
                ];
            }
        }

        $listas[] = ["enunciado" => $pregunta["enunciado"], "opciones" => $opcionesValidas];
    }

    return $listas;
}

/** Valida el formulario completo. Devuelve "" si es válido, o el mensaje de error. */
function validarFormulario(array $form): string
{
    if ($form["curso_id"] <= 0) {
        return "Debes seleccionar un curso.";
    }
    if ($form["titulo"] === "") {
        return "El título de la evaluación es obligatorio.";
    }
    if ($form["descripcion"] === "") {
        return "La descripción es obligatoria.";
    }
    if ($form["porcentaje_aprobacion"] < 0 || $form["porcentaje_aprobacion"] > 100) {
        return "El porcentaje de aprobación debe estar entre 0 y 100.";
    }

    foreach ($form["preguntas"] as $numero => $pregunta) {
        if ($pregunta["enunciado"] === "") {
            return "La pregunta {$numero} no tiene enunciado.";
        }
        $noVacias = array_filter($pregunta["opciones"], fn($t) => $t !== "");
        if (count($noVacias) < 2) {
            return "La pregunta {$numero} debe tener al menos 2 opciones.";
        }
        if ($pregunta["correcta"] < 1 || $pregunta["correcta"] > MAX_OPCIONES || trim($pregunta["opciones"][$pregunta["correcta"]] ?? "") === "") {
            return "La pregunta {$numero} debe tener una opción correcta seleccionada.";
        }
    }

    return "";
}

$formulario = null;
$errorFormulario = "";
$editandoId = 0;

/* =====================================================
   PROCESAR ACCIONES (POST)
   ===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar()) {
        header("Location: evaluaciones.php?msg=" . urlencode("Tu formulario expiró, inténtalo de nuevo.") . "&tipo=error");
        exit;
    }

    $accion = $_POST["accion"] ?? "";

    if ($accion === "agregar_pregunta" || $accion === "quitar_pregunta") {

        $formulario = leerFormularioDesdePost();
        if ($accion === "agregar_pregunta") {
            $formulario["num_preguntas"] = min(MAX_PREGUNTAS, $formulario["num_preguntas"] + 1);
            $formulario["preguntas"][$formulario["num_preguntas"]] = [
                "enunciado" => "", "opciones" => array_fill(1, MAX_OPCIONES, ""), "correcta" => 0
            ];
        } else {
            $formulario["num_preguntas"] = max(1, $formulario["num_preguntas"] - 1);
            unset($formulario["preguntas"][$formulario["num_preguntas"] + 1]);
        }
        $editandoId = (int) ($_POST["id"] ?? 0);

    } elseif ($accion === "crear" || $accion === "editar") {

        $formulario = leerFormularioDesdePost();
        $editandoId = (int) ($_POST["id"] ?? 0);
        $errorFormulario = validarFormulario($formulario);

        if ($errorFormulario === "") {

            try {

                if (!$cursoModel->existe($formulario["curso_id"])) {
                    $errorFormulario = "El curso seleccionado no existe.";
                } else {

                    $preguntasParaGuardar = prepararPreguntasParaGuardar($formulario["preguntas"]);

                    if ($accion === "crear") {
                        $model->crear(
                            $formulario["curso_id"], $formulario["titulo"], $formulario["descripcion"],
                            $formulario["porcentaje_aprobacion"], $preguntasParaGuardar
                        );
                    } else {
                        if ($editandoId <= 0 || !$model->existe($editandoId)) {
                            $errorFormulario = "La evaluación no existe.";
                        } else {
                            $model->actualizar(
                                $editandoId, $formulario["curso_id"], $formulario["titulo"], $formulario["descripcion"],
                                $formulario["porcentaje_aprobacion"], $preguntasParaGuardar
                            );
                        }
                    }
                }

            } catch (Throwable $e) {
                $errorFormulario = "No fue posible guardar la evaluación.";
            }
        }

        if ($errorFormulario === "") {
            header("Location: evaluaciones.php?msg=" . urlencode($accion === "crear" ? "Evaluación creada correctamente." : "Evaluación actualizada correctamente."));
            exit;
        }
        /* Si hubo error, no redirigimos: volvemos a mostrar el
           formulario con lo que el administrador ya había escrito. */

    } elseif ($accion === "estado") {

        $id = (int) ($_POST["id"] ?? 0);
        $nuevoEstado = $_POST["estado"] ?? "";

        if ($id > 0 && in_array($nuevoEstado, ["activo", "inactivo"], true) && $model->existe($id)) {
            $model->cambiarEstado($id, $nuevoEstado);
        }

        header("Location: evaluaciones.php?msg=" . urlencode($nuevoEstado === "activo" ? "Evaluación activada." : "Evaluación desactivada."));
        exit;

    } elseif ($accion === "eliminar") {

        $id = (int) ($_POST["id"] ?? 0);

        if ($id > 0 && $model->existe($id)) {
            try {
                $model->eliminar($id);
                $msg = "Evaluación eliminada correctamente.";
                $tipoMsg = "exito";
            } catch (PDOException $e) {
                $msg = $e->getCode() === "23000"
                    ? "No se puede eliminar esta evaluación porque tiene información relacionada. Puedes desactivarla."
                    : "No fue posible eliminar la evaluación.";
                $tipoMsg = "error";
            }
        } else {
            $msg = "La evaluación no existe.";
            $tipoMsg = "error";
        }

        header("Location: evaluaciones.php?msg=" . urlencode($msg) . ($tipoMsg === "error" ? "&tipo=error" : ""));
        exit;
    }
}

/* =====================================================
   GET: LISTADO, NUEVO O EDITAR
   ===================================================== */

$todos = $model->listar();

$confirmandoEliminar = null;
if (isset($_GET["confirmar_eliminar"])) {
    foreach ($todos as $e) {
        if ((int) $e["id"] === (int) $_GET["confirmar_eliminar"]) {
            $confirmandoEliminar = $e;
            break;
        }
    }
}

if ($formulario === null && isset($_GET["editar"])) {

    $completa = $model->buscarCompleta((int) $_GET["editar"]);

    if ($completa) {
        $editandoId = (int) $completa["id"];
        $preguntas = [];
        $i = 1;
        foreach ($completa["preguntas"] as $pregunta) {
            $opciones = array_fill(1, MAX_OPCIONES, "");
            $correcta = 0;
            $j = 1;
            foreach ($pregunta["opciones"] as $opcion) {
                if ($j > MAX_OPCIONES) {
                    break;
                }
                $opciones[$j] = $opcion["texto"];
                if ((int) $opcion["es_correcta"] === 1) {
                    $correcta = $j;
                }
                $j++;
            }
            $preguntas[$i] = ["enunciado" => $pregunta["enunciado"], "opciones" => $opciones, "correcta" => $correcta];
            $i++;
        }
        if (empty($preguntas)) {
            $preguntas[1] = ["enunciado" => "", "opciones" => array_fill(1, MAX_OPCIONES, ""), "correcta" => 0];
        }

        $formulario = [
            "curso_id" => (int) $completa["curso_id"],
            "titulo" => $completa["titulo"],
            "descripcion" => $completa["descripcion"],
            "porcentaje_aprobacion" => (int) $completa["porcentaje_aprobacion"],
            "num_preguntas" => count($preguntas),
            "preguntas" => $preguntas
        ];
    }
}

if ($formulario === null && isset($_GET["nuevo"])) {
    $formulario = [
        "curso_id" => 0, "titulo" => "", "descripcion" => "", "porcentaje_aprobacion" => 70,
        "num_preguntas" => 1,
        "preguntas" => [1 => ["enunciado" => "", "opciones" => array_fill(1, MAX_OPCIONES, ""), "correcta" => 0]]
    ];
}

$mostrarFormulario = $formulario !== null;

$activeView = "evaluaciones";
$pageTitle = "Evaluaciones";
$extraCss = ["css/evaluaciones.css"];
require __DIR__ . "/partials/_header.php";
require __DIR__ . "/partials/_flash.php";
?>

<div class="dashboard-heading">
    <div>
        <span class="section-label">GESTIÓN DE EVALUACIONES</span>
        <h2>Evaluaciones</h2>
        <p>Administra las evaluaciones asociadas a cada curso.</p>
    </div>

    <?php if (!$mostrarFormulario && !$confirmandoEliminar): ?>
        <a href="evaluaciones.php?nuevo=1" class="admin-button">+ Nueva evaluación</a>
    <?php endif; ?>
</div>

<?php if ($errorFormulario !== ""): ?>
    <div class="admin-alert admin-alert-error"><?= htmlspecialchars($errorFormulario, ENT_QUOTES, "UTF-8") ?></div>
<?php endif; ?>

<?php if ($confirmandoEliminar): ?>

    <div class="dashboard-card users-table-card" style="padding:24px;">
        <div class="card-header">
            <div>
                <span class="section-label">CONFIRMAR</span>
                <h3>Eliminar evaluación</h3>
            </div>
        </div>

        <p>¿Seguro que deseas eliminar la evaluación
            <strong><?= htmlspecialchars($confirmandoEliminar["titulo"], ENT_QUOTES, "UTF-8") ?></strong>?
            Esta acción no se puede deshacer.
        </p>

        <div class="form-actions">
            <a href="evaluaciones.php" class="admin-secondary-button">Cancelar</a>
            <form method="POST" action="evaluaciones.php">
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
                <h3><?= $editandoId ? "Editar evaluación" : "Nueva evaluación" ?></h3>
            </div>
        </div>

        <form method="POST" action="evaluaciones.php">

            <?php Csrf::campo(); ?>
            <input type="hidden" name="num_preguntas" value="<?= (int) $formulario["num_preguntas"] ?>">
            <?php if ($editandoId): ?>
                <input type="hidden" name="id" value="<?= (int) $editandoId ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="evalCurso">Curso</label>
                <select id="evalCurso" name="curso_id" required>
                    <option value="">Selecciona un curso...</option>
                    <?php foreach ($cursosDisponibles as $c): ?>
                        <option value="<?= (int) $c["id"] ?>" <?= $formulario["curso_id"] === (int) $c["id"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($c["titulo"], ENT_QUOTES, "UTF-8") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="evalTitulo">Título</label>
                <input type="text" id="evalTitulo" name="titulo" maxlength="150"
                    value="<?= htmlspecialchars($formulario["titulo"], ENT_QUOTES, "UTF-8") ?>" required>
            </div>

            <div class="form-group">
                <label for="evalDescripcion">Descripción</label>
                <textarea id="evalDescripcion" name="descripcion" rows="3" required><?= htmlspecialchars($formulario["descripcion"], ENT_QUOTES, "UTF-8") ?></textarea>
            </div>

            <div class="form-group">
                <label for="evalPorcentaje">Porcentaje de aprobación (%)</label>
                <input type="number" id="evalPorcentaje" name="porcentaje_aprobacion" min="0" max="100"
                    value="<?= (int) $formulario["porcentaje_aprobacion"] ?>" required>
            </div>

            <div class="form-group preguntas-section">

                <div class="preguntas-section-header">
                    <label>Preguntas</label>
                    <small class="field-hint">
                        Cada pregunta necesita mínimo 2 opciones y exactamente una marcada como correcta.
                        Las opciones que dejes vacías no se guardan.
                    </small>
                </div>

                <?php foreach ($formulario["preguntas"] as $numero => $pregunta): ?>
                    <div class="pregunta-block" style="border:1px solid var(--admin-view-border); border-radius:12px; padding:16px; margin-bottom:14px;">

                        <div class="form-group">
                            <label for="pregunta<?= $numero ?>Enunciado">Pregunta <?= $numero ?></label>
                            <input type="text" id="pregunta<?= $numero ?>Enunciado" name="preguntas[<?= $numero ?>][enunciado]"
                                value="<?= htmlspecialchars($pregunta["enunciado"], ENT_QUOTES, "UTF-8") ?>" required>
                        </div>

                        <?php for ($j = 1; $j <= MAX_OPCIONES; $j++): ?>
                            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                                <input type="radio" name="preguntas[<?= $numero ?>][correcta]" value="<?= $j ?>"
                                    id="pregunta<?= $numero ?>Correcta<?= $j ?>"
                                    <?= (int) $pregunta["correcta"] === $j ? "checked" : "" ?>
                                    <?= $j <= 2 ? "required" : "" ?>>
                                <label for="pregunta<?= $numero ?>Correcta<?= $j ?>" style="margin:0; flex:0 0 90px;">
                                    Opción <?= $j ?><?= $j > 2 ? " (opcional)" : "" ?>
                                </label>
                                <input type="text" name="preguntas[<?= $numero ?>][opciones][<?= $j ?>]"
                                    value="<?= htmlspecialchars($pregunta["opciones"][$j], ENT_QUOTES, "UTF-8") ?>"
                                    placeholder="Texto de la opción <?= $j ?>" <?= $j <= 2 ? "required" : "" ?> style="flex:1;">
                            </div>
                        <?php endfor; ?>

                    </div>
                <?php endforeach; ?>

                <div class="form-actions">
                    <?php if ($formulario["num_preguntas"] < MAX_PREGUNTAS): ?>
                        <button type="submit" name="accion" value="agregar_pregunta" class="admin-secondary-button">+ Agregar pregunta</button>
                    <?php endif; ?>
                    <?php if ($formulario["num_preguntas"] > 1): ?>
                        <button type="submit" name="accion" value="quitar_pregunta" class="admin-secondary-button">− Quitar última pregunta</button>
                    <?php endif; ?>
                </div>

            </div>

            <div class="form-actions">
                <a href="evaluaciones.php" class="admin-secondary-button">Cancelar</a>
                <button type="submit" name="accion" value="<?= $editandoId ? "editar" : "crear" ?>" class="admin-button">
                    <?= $editandoId ? "Guardar cambios" : "Crear evaluación" ?>
                </button>
            </div>

        </form>

    </div>

<?php else: ?>

    <div class="dashboard-card users-table-card">

        <div class="card-header">
            <div>
                <span class="section-label">CATÁLOGO</span>
                <h3>Evaluaciones registradas</h3>
            </div>
            <span class="card-period"><?= count($todos) ?> evaluación(es)</span>
        </div>

        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>EVALUACIÓN</th>
                        <th>CURSO</th>
                        <th>PREGUNTAS</th>
                        <th>% APROBACIÓN</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($todos)): ?>
                        <tr><td colspan="6" class="loading-users">No se encontraron evaluaciones.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($todos as $e): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($e["titulo"], ENT_QUOTES, "UTF-8") ?></strong></td>
                            <td><?= htmlspecialchars($e["curso_titulo"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= (int) $e["total_preguntas"] ?></td>
                            <td><?= (int) $e["porcentaje_aprobacion"] ?>%</td>
                            <td>
                                <span class="badge badge-<?= $e["estado"] ?>"><?= ucfirst($e["estado"]) ?></span>
                            </td>
                            <td class="table-actions">

                                <a href="evaluaciones.php?editar=<?= (int) $e["id"] ?>" class="link-action">Editar</a>

                                <form method="POST" action="evaluaciones.php">
                                    <?php Csrf::campo(); ?>
                                    <input type="hidden" name="accion" value="estado">
                                    <input type="hidden" name="id" value="<?= (int) $e["id"] ?>">
                                    <input type="hidden" name="estado" value="<?= $e["estado"] === "activo" ? "inactivo" : "activo" ?>">
                                    <button type="submit" class="link-action">
                                        <?= $e["estado"] === "activo" ? "Desactivar" : "Activar" ?>
                                    </button>
                                </form>

                                <a href="evaluaciones.php?confirmar_eliminar=<?= (int) $e["id"] ?>" class="link-action">Eliminar</a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

<?php endif; ?>

<?php require __DIR__ . "/partials/_footer.php"; ?>