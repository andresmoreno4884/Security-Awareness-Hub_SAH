<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * resultados.php
 *
 * NOTA IMPORTANTE: en el proyecto original esta vista era solo
 * una maqueta visual (resultados.html): no tenía JavaScript
 * propio, ni Modelo, ni Controlador, ni tabla de "intentos" o
 * resultados de evaluación en la base de datos — los contadores
 * estaban fijos en "0" y nunca llegó a funcionar (el mismo
 * módulo que reportes.php ya marca como "pendiente" al elegir
 * el reporte de tipo "resultados").
 *
 * Aquí se conserva 100% en PHP (con el mismo guard de sesión que
 * el resto del panel), mostrando los indicadores reales que sí
 * se pueden calcular hoy (evaluaciones configuradas y su
 * porcentaje de aprobación requerido) y avisando honestamente
 * que faltan los resultados por usuario, en vez de inventarlos.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::requireAdminView("../authentication/login.php");

$evaluaciones = (new EvaluacionModel())->listar();

$totalEvaluaciones = count($evaluaciones);
$porcentajes = array_column($evaluaciones, "porcentaje_aprobacion");
$promedioAprobacion = count($porcentajes) > 0 ? round(array_sum($porcentajes) / count($porcentajes)) : 0;

$activeView = "resultados";
$pageTitle = "Resultados";
$extraCss = ["css/resultados.css"];
require __DIR__ . "/partials/_header.php";
?>

<div class="dashboard-heading">
    <div>
        <span class="section-label">RESULTADOS</span>
        <h2>Resultados</h2>
        <p>Consulta y analiza los resultados obtenidos por los usuarios en las evaluaciones.</p>
    </div>
</div>

<div class="admin-alert admin-alert-error">
    Este módulo todavía no está implementado: para mostrar resultados reales por usuario hace
    falta una tabla de "intentos de evaluación" (qué usuario presentó qué evaluación y con qué
    nota) que no existe en la base de datos actual. Los indicadores de abajo son datos reales de
    las evaluaciones configuradas, no de resultados de usuarios.
</div>

<div class="dashboard-cards">

    <div class="dashboard-card">
        <div class="card-icon">✓</div>
        <div>
            <span>EVALUACIONES</span>
            <strong><?= $totalEvaluaciones ?></strong>
            <small>Evaluaciones configuradas</small>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-icon">%</div>
        <div>
            <span>PROMEDIO</span>
            <strong><?= $promedioAprobacion ?>%</strong>
            <small>% de aprobación requerido (promedio)</small>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-icon">↑</div>
        <div>
            <span>APROBADOS</span>
            <strong>—</strong>
            <small>Requiere el módulo de resultados</small>
        </div>
    </div>

</div>

<div class="dashboard-card users-table-card">

    <div class="card-header">
        <div>
            <span class="section-label">DETALLE</span>
            <h3>Resultados por usuario</h3>
        </div>
        <span class="card-period">0 registro(s)</span>
    </div>

    <div class="table-wrapper">
        <table class="users-table">
            <thead>
                <tr>
                    <th>USUARIO</th>
                    <th>EVALUACIÓN</th>
                    <th>NOTA</th>
                    <th>ESTADO</th>
                    <th>FECHA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" class="loading-users">
                        Módulo pendiente de implementar — todavía no hay resultados de usuarios que mostrar.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<?php require __DIR__ . "/partials/_footer.php"; ?>