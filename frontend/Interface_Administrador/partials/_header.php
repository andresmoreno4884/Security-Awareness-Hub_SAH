<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * partials/_header.php
 *
 * Encabezado + barra lateral comunes a todas las páginas del
 * panel de administrador. Cada página define $activeView y
 * $pageTitle antes de incluir este archivo, y hace
 * require_once "partials/_footer.php" al final.
 *
 * No usa JavaScript: la navegación son enlaces <a> normales,
 * cada uno recarga la página PHP correspondiente.
 * ============================================================
 */

$activeView = $activeView ?? "dashboard";
$pageTitle = $pageTitle ?? "Panel Administrador";

function navClase(string $view, string $activa): string
{
    return $view === $activa ? "nav-item active" : "nav-item";
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8") ?> | Security Awareness Hub</title>

    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin-views-common.css">
    <?php foreach (($extraCss ?? []) as $hoja): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($hoja, ENT_QUOTES, "UTF-8") ?>">
    <?php endforeach; ?>

</head>

<body>

    <div class="admin-layout">

        <!-- BARRA LATERAL -->
        <aside class="admin-sidebar">

            <div class="admin-brand">
                <div class="admin-brand-icon">SAH</div>
                <div>
                    <strong>Security</strong>
                    <span>Awareness Hub</span>
                </div>
            </div>

            <div class="admin-profile">
                <div class="profile-avatar">
                    <?= htmlspecialchars(mb_substr($_SESSION["nombres"] ?? "A", 0, 1), ENT_QUOTES, "UTF-8") ?>
                </div>
                <div>
                    <strong><?= htmlspecialchars(($_SESSION["nombres"] ?? "Administrador") . " " . ($_SESSION["apellidos"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                    <span>Panel administrativo</span>
                </div>
            </div>

            <nav class="admin-navigation">

                <span class="navigation-title">PRINCIPAL</span>

                <a href="panel.php" class="<?= navClase('dashboard', $activeView) ?>">
                    <span class="nav-icon">▦</span>
                    <span>Dashboard</span>
                </a>

                <span class="navigation-title">ADMINISTRACIÓN</span>

                <a href="usuarios.php" class="<?= navClase('usuarios', $activeView) ?>">
                    <span class="nav-icon">♙</span>
                    <span>Usuarios</span>
                </a>

                <a href="empresas.php" class="<?= navClase('empresas', $activeView) ?>">
                    <span class="nav-icon">▤</span>
                    <span>Empresas</span>
                </a>

                <a href="courses.php" class="<?= navClase('courses_admin', $activeView) ?>">
                    <span class="nav-icon">▣</span>
                    <span>Cursos</span>
                </a>

                <a href="evaluaciones.php" class="<?= navClase('evaluaciones', $activeView) ?>">
                    <span class="nav-icon">✓</span>
                    <span>Evaluaciones</span>
                </a>

                <a href="evaluaciones.php" class="<?= navClase('evaluaciones', $activeView) ?>">
                    <span class="nav-icon">✓</span>
                    <span>Evaluaciones</span>
                </a>

                <a href="asignaciones.php" class="<?= navClase('asignaciones', $activeView) ?>">
                    <span class="nav-icon">◉</span>
                    <span>Asignaciones</span>
                </a>

                <a href="resultados.php" class="<?= navClase('resultados', $activeView) ?>">
                    <span class="nav-icon">★</span>
                    <span>Resultados</span>
                </a>

                <a href="reportes.php" class="<?= navClase('reportes', $activeView) ?>">

            </nav>

            <div class="sidebar-footer">
                <a href="../authentication/logout.php" class="logout-button">
                    <span>⇥</span>
                    <span>Cerrar sesión</span>
                </a>
            </div>

        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="admin-main">

            <header class="admin-topbar">
                <div>
                    <span class="topbar-label">ADMINISTRACIÓN</span>
                    <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8") ?></h1>
                </div>
                <div class="topbar-status">
                    <span class="status-dot"></span>
                    <span>Administrador</span>
                </div>
            </header>

            <section class="admin-content">

                <div class="admin-view">