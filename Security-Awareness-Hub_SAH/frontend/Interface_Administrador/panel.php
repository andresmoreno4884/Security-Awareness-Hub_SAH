<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * panel.php
 * Dashboard principal del administrador. 100% PHP: los datos
 * se consultan aquí mismo con los Modelos, sin fetch ni JSON.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::requireAdminView("../authentication/login.php");

$usuarios = (new UsuarioModel())->listar();
$cursos = (new CursoModel())->listar();
$evaluaciones = (new EvaluacionModel())->listar();

$totalUsuarios = count($usuarios);
$usuariosActivos = count(array_filter($usuarios, fn($u) => $u["estado"] === "activo"));

$totalCursos = count($cursos);
$cursosActivos = count(array_filter($cursos, fn($c) => $c["estado"] === "activo"));

$totalEvaluaciones = count($evaluaciones);
$evaluacionesActivas = count(array_filter($evaluaciones, fn($e) => $e["estado"] === "activo"));

$porcentajes = array_column($evaluaciones, "porcentaje_aprobacion");
$promedio = count($porcentajes) > 0 ? round(array_sum($porcentajes) / count($porcentajes)) : 0;

$activeView = "dashboard";
$pageTitle = "Dashboard";
require __DIR__ . "/partials/_header.php";
?>

<div class="dashboard-heading">
    <div>
        <span class="section-label">PANEL ADMINISTRATIVO</span>
        <h2>Dashboard</h2>
        <p>Consulta y administra la información general de Security Awareness Hub.</p>
    </div>
</div>

<div class="dashboard-cards">

    <div class="dashboard-card">
        <div class="card-icon">♙</div>
        <div>
            <span>USUARIOS</span>
            <strong><?= $totalUsuarios ?></strong>
            <small>Usuarios registrados</small>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-icon">▣</div>
        <div>
            <span>CURSOS</span>
            <strong><?= $totalCursos ?></strong>
            <small>Cursos disponibles</small>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-icon">✓</div>
        <div>
            <span>EVALUACIONES</span>
            <strong><?= $totalEvaluaciones ?></strong>
            <small>Evaluaciones creadas</small>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-icon">%</div>
        <div>
            <span>PROMEDIO</span>
            <strong><?= $promedio ?>%</strong>
            <small>% de aprobación configurado</small>
        </div>
    </div>

</div>

<div class="dashboard-grid">

    <div class="dashboard-panel">

        <div class="panel-header">
            <div>
                <span class="section-label">RESUMEN</span>
                <h3>Actividad general</h3>
            </div>
            <span class="card-period">Actual</span>
        </div>

        <div class="dashboard-summary">

            <div class="summary-item">
                <div class="summary-icon">✓</div>
                <div>
                    <span>Usuarios activos</span>
                    <strong><?= $usuariosActivos ?></strong>
                </div>
            </div>

            <div class="summary-item">
                <div class="summary-icon">▣</div>
                <div>
                    <span>Cursos activos</span>
                    <strong><?= $cursosActivos ?></strong>
                </div>
            </div>

            <div class="summary-item">
                <div class="summary-icon">◉</div>
                <div>
                    <span>Evaluaciones activas</span>
                    <strong><?= $evaluacionesActivas ?></strong>
                </div>
            </div>

            <div class="summary-item">
                <div class="summary-icon">%</div>
                <div>
                    <span>Promedio general</span>
                    <strong><?= $promedio ?>%</strong>
                </div>
            </div>

        </div>

    </div>

    <div class="dashboard-panel">

        <div class="panel-header">
            <div>
                <span class="section-label">ADMINISTRACIÓN</span>
                <h3>Acciones rápidas</h3>
            </div>
        </div>

        <div class="quick-actions">

            <a href="usuarios.php" class="quick-action">
                <span class="quick-action-icon">♙</span>
                <span>
                    <strong>Gestionar usuarios</strong>
                    <small>Administrar usuarios registrados</small>
                </span>
                <span class="quick-action-arrow">→</span>
            </a>

            <a href="courses.php" class="quick-action">
                <span class="quick-action-icon">▣</span>
                <span>
                    <strong>Gestionar cursos</strong>
                    <small>Administrar cursos disponibles</small>
                </span>
                <span class="quick-action-arrow">→</span>
            </a>

            <a href="evaluaciones.php" class="quick-action">
                <span class="quick-action-icon">✓</span>
                <span>
                    <strong>Gestionar evaluaciones</strong>
                    <small>Administrar evaluaciones</small>
                </span>
                <span class="quick-action-arrow">→</span>
            </a>

            <a href="reportes.php" class="quick-action">
                <span class="quick-action-icon">▤</span>
                <span>
                    <strong>Ver reportes</strong>
                    <small>Consultar reportes del sistema</small>
                </span>
                <span class="quick-action-arrow">→</span>
            </a>

        </div>

    </div>

</div>

<div class="dashboard-panel">

    <div class="panel-header">
        <div>
            <span class="section-label">SISTEMA</span>
            <h3>Información general</h3>
        </div>
        <span class="card-period">Security Awareness Hub</span>
    </div>

    <div class="system-info">

        <div class="system-info-item">
            <span>Plataforma</span>
            <strong>Security Awareness Hub</strong>
        </div>

        <div class="system-info-item">
            <span>Módulo</span>
            <strong>Administración</strong>
        </div>

        <div class="system-info-item">
            <span>Gestión</span>
            <strong>Usuarios, cursos y evaluaciones</strong>
        </div>

        <div class="system-info-item">
            <span>Base de datos</span>
            <strong>MySQL</strong>
        </div>

    </div>

</div>

<?php require __DIR__ . "/partials/_footer.php"; ?>