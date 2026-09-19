<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * asignaciones.php
 *
 * NOTA IMPORTANTE: en el proyecto original esta vista era solo
 * una maqueta visual (asignaciones.html): no tenía JavaScript
 * propio, ni Modelo, ni Controlador, ni tabla "asignaciones" en
 * la base de datos — los contadores estaban fijos en "0" y la
 * tabla decía "Cargando..." para siempre. Nunca llegó a funcionar.
 *
 * Aquí se conserva 100% en PHP (con el mismo guard de sesión que
 * el resto del panel) y se honestidad: se avisa que el módulo de
 * asignaciones todavía no está implementado, en vez de simular
 * datos falsos. Cuando quieras la función real (crear la tabla
 * "asignaciones" + su Modelo/Controlador/CRUD), lo armamos aparte.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::requireAdminView("../authentication/login.php");

$activeView = "asignaciones";
$pageTitle = "Asignaciones";
$extraCss = ["css/asignaciones.css"];
require __DIR__ . "/partials/_header.php";
?>

<div class="dashboard-heading">
    <div>
        <span class="section-label">GESTIÓN DE CAPACITACIÓN</span>
        <h2>Asignaciones</h2>
        <p>Asigna cursos y evaluaciones a los usuarios de la plataforma.</p>
    </div>
</div>

<div class="admin-alert admin-alert-error">
    Este módulo todavía no está implementado: falta una tabla "asignaciones" en la base de datos
    (con su Modelo y Controlador) para poder asignar cursos/evaluaciones a usuarios y guardar el
    resultado real. Por ahora esta pantalla es solo informativa.
</div>

<div class="dashboard-cards">

    <div class="dashboard-card">
        <div class="card-icon">✓</div>
        <div>
            <span>ASIGNACIONES</span>
            <strong>0</strong>
            <small>Asignaciones registradas</small>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-icon">◉</div>
        <div>
            <span>ACTIVAS</span>
            <strong>0</strong>
            <small>Asignaciones activas</small>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-icon">★</div>
        <div>
            <span>COMPLETADAS</span>
            <strong>0</strong>
            <small>Capacitaciones completadas</small>
        </div>
    </div>

</div>

<div class="dashboard-card users-table-card">

    <div class="card-header">
        <div>
            <span class="section-label">DIRECTORIO</span>
            <h3>Asignaciones registradas</h3>
        </div>
        <span class="card-period">0 registro(s)</span>
    </div>

    <div class="table-wrapper">
        <table class="users-table">
            <thead>
                <tr>
                    <th>USUARIO</th>
                    <th>CURSO</th>
                    <th>TIPO</th>
                    <th>ESTADO</th>
                    <th>FECHA DE ASIGNACIÓN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" class="loading-users">
                        Módulo pendiente de implementar — todavía no hay asignaciones que mostrar.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<?php require __DIR__ . "/partials/_footer.php"; ?>