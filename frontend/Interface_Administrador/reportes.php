<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * reportes.php
 * Reportes tabulares con datos reales de MySQL, 100% PHP.
 * El filtrado (tipo/periodo) es un <form method="GET">, y la
 * exportación es un enlace a export_reporte.php que genera un
 * CSV real desde PHP (sin Blob ni JavaScript).
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::requireAdminView("../authentication/login.php");

$model = new ReporteModel();

$tipo = $_GET["tipo"] ?? "";
$periodo = $_GET["periodo"] ?? "all";

if (!in_array($periodo, ["all", "month", "quarter", "year"], true)) {
    $periodo = "all";
}

$TIPOS_VALIDOS = ["usuarios", "cursos", "evaluaciones", "empresas", "resultados", "capacitacion"];
if ($tipo !== "" && !in_array($tipo, $TIPOS_VALIDOS, true)) {
    $tipo = "";
}

$indicadores = $model->indicadores();

$columnas = [];
$filas = [];
$mensajeNoDisponible = "";

if ($tipo !== "") {
    switch ($tipo) {
        case "usuarios":
            $columnas = ["Nombres", "Apellidos", "Correo", "Rol", "Estado", "Fecha de registro"];
            $filas = $model->reporteUsuarios($periodo);
            break;
        case "cursos":
            $columnas = ["Título", "Nivel", "Duración (h)", "Módulos", "% Aprobación", "Estado", "Fecha de creación"];
            $filas = $model->reporteCursos($periodo);
            break;
        case "evaluaciones":
            $columnas = ["Título", "Curso", "% Aprobación", "Estado", "Fecha de creación", "N° Preguntas"];
            $filas = $model->reporteEvaluaciones($periodo);
            break;
        case "empresas":
            $columnas = ["Nombre", "NIT", "Correo", "Tipo", "Estado", "Fecha de registro"];
            $filas = $model->reporteEmpresas($periodo);
            break;
        case "resultados":
        case "capacitacion":
            $mensajeNoDisponible = "Este reporte necesita el módulo de resultados de evaluación, que todavía no está implementado.";
            break;
    }
}

$activeView = "reportes";
$pageTitle = "Reportes";
$extraCss = ["css/reportes.css"];
require __DIR__ . "/partials/_header.php";
?>

<div class="dashboard-heading">
    <div>
        <span class="section-label">MÓDULO DE REPORTES</span>
        <h2>Reportes</h2>
        <p>Consulta reportes tabulares y exportables con datos reales de la plataforma.</p>
    </div>
</div>

<div class="dashboard-cards">

    <div class="dashboard-card">
        <div class="card-icon">♙</div>
        <div>
            <span>USUARIOS</span>
            <strong><?= (int) $indicadores["total_usuarios"] ?></strong>
            <small>Usuarios registrados</small>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-icon">▣</div>
        <div>
            <span>CURSOS</span>
            <strong><?= (int) $indicadores["total_cursos"] ?></strong>
            <small>Cursos disponibles</small>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-icon">%</div>
        <div>
            <span>PROMEDIO</span>
            <strong><?= (int) $indicadores["promedio_aprobacion"] ?>%</strong>
            <small>% de aprobación configurado</small>
        </div>
    </div>

</div>

<div class="dashboard-panel">

    <div class="panel-header">
        <div>
            <span class="section-label">FILTROS</span>
            <h3>Generar reporte</h3>
        </div>
    </div>

    <form method="GET" action="reportes.php" class="report-filters">

        <div class="form-group">
            <label for="reporteTipo">Tipo de reporte</label>
            <select id="reporteTipo" name="tipo" required>
                <option value="">Selecciona un tipo...</option>
                <option value="usuarios" <?= $tipo === "usuarios" ? "selected" : "" ?>>Usuarios</option>
                <option value="cursos" <?= $tipo === "cursos" ? "selected" : "" ?>>Cursos</option>
                <option value="evaluaciones" <?= $tipo === "evaluaciones" ? "selected" : "" ?>>Evaluaciones</option>
                <option value="empresas" <?= $tipo === "empresas" ? "selected" : "" ?>>Empresas</option>
                <option value="resultados" <?= $tipo === "resultados" ? "selected" : "" ?>>Resultados de evaluación</option>
                <option value="capacitacion" <?= $tipo === "capacitacion" ? "selected" : "" ?>>Progreso de capacitación</option>
            </select>
        </div>

        <div class="form-group">
            <label for="reportePeriodo">Periodo</label>
            <select id="reportePeriodo" name="periodo">
                <option value="all" <?= $periodo === "all" ? "selected" : "" ?>>Todo el histórico</option>
                <option value="month" <?= $periodo === "month" ? "selected" : "" ?>>Este mes</option>
                <option value="quarter" <?= $periodo === "quarter" ? "selected" : "" ?>>Últimos 3 meses</option>
                <option value="year" <?= $periodo === "year" ? "selected" : "" ?>>Este año</option>
            </select>
        </div>

        <button type="submit" class="admin-button">Generar reporte</button>

        <?php if ($tipo !== "" && $mensajeNoDisponible === ""): ?>
            <a class="admin-secondary-button"
                href="export_reporte.php?tipo=<?= urlencode($tipo) ?>&periodo=<?= urlencode($periodo) ?>">
                Exportar CSV
            </a>
        <?php endif; ?>

    </form>

</div>

<?php if ($tipo !== ""): ?>

    <div class="dashboard-panel report-results">

        <div class="panel-header">
            <div>
                <span class="section-label">RESULTADOS</span>
                <h3>Reporte de <?= htmlspecialchars(ucfirst($tipo), ENT_QUOTES, "UTF-8") ?></h3>
            </div>
            <?php if ($mensajeNoDisponible === ""): ?>
                <span class="card-period"><?= count($filas) ?> registro(s)</span>
            <?php endif; ?>
        </div>

        <?php if ($mensajeNoDisponible !== ""): ?>

            <div class="empty-state">
                <div class="empty-icon">ⓘ</div>
                <p><?= htmlspecialchars($mensajeNoDisponible, ENT_QUOTES, "UTF-8") ?></p>
            </div>

        <?php elseif (empty($filas)): ?>

            <div class="empty-state">
                <div class="empty-icon">▤</div>
                <p>No se encontraron datos para el periodo seleccionado.</p>
            </div>

        <?php else: ?>

            <div class="table-wrapper">
                <table class="users-table reports-table">
                    <thead>
                        <tr>
                            <?php foreach ($columnas as $col): ?>
                                <th><?= htmlspecialchars($col, ENT_QUOTES, "UTF-8") ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filas as $fila): ?>
                            <tr>
                                <?php foreach ($fila as $valor): ?>
                                    <td><?= htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8") ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>

<?php endif; ?>

<?php require __DIR__ . "/partials/_footer.php"; ?>