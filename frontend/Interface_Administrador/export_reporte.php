<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * export_reporte.php
 * Genera y descarga un CSV real desde PHP (fputcsv), sin
 * JavaScript ni Blob del navegador.
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

switch ($tipo) {
    case "usuarios":
        $columnas = ["Nombres", "Apellidos", "Correo", "Rol", "Estado", "Fecha de registro"];
        $filas = $model->reporteUsuarios($periodo);
        break;
    case "cursos":
        $columnas = ["Titulo", "Nivel", "Duracion (h)", "Modulos", "% Aprobacion", "Estado", "Fecha de creacion"];
        $filas = $model->reporteCursos($periodo);
        break;
    case "evaluaciones":
        $columnas = ["Titulo", "Curso", "% Aprobacion", "Estado", "Fecha de creacion", "N Preguntas"];
        $filas = $model->reporteEvaluaciones($periodo);
        break;
    case "empresas":
        $columnas = ["Nombre", "NIT", "Correo", "Tipo", "Estado", "Fecha de registro"];
        $filas = $model->reporteEmpresas($periodo);
        break;
    default:
        header("Location: reportes.php?msg=" . urlencode("Tipo de reporte no válido para exportar.") . "&tipo=error");
        exit;
}

$nombreArchivo = "reporte_{$tipo}_" . date("Y-m-d") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");

$salida = fopen("php://output", "w");

// BOM UTF-8 para que Excel reconozca los acentos correctamente.
fwrite($salida, "\xEF\xBB\xBF");

fputcsv($salida, $columnas);

foreach ($filas as $fila) {
    fputcsv($salida, array_values($fila));
}

fclose($salida);
exit;