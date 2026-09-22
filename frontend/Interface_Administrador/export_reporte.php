<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * export_reporte.php
 *
 * Exportación CSV real desde PHP.
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::requireAdminView("../authentication/login.php");

$model = new ReporteModel();

$tipo = $_GET["tipo"] ?? "";
$periodo = $_GET["periodo"] ?? "all";


$periodosValidos = [
    "all",
    "month",
    "quarter",
    "year"
];

if (!in_array($periodo, $periodosValidos, true)) {
    $periodo = "all";
}


switch ($tipo) {

    case "usuarios":

        $columnas = [
            "Nombres",
            "Apellidos",
            "Correo",
            "Rol",
            "Empresa",
            "Estado",
            "Fecha de registro"
        ];

        $filas =
            $model->reporteUsuarios($periodo);

        break;


    case "cursos":

        $columnas = [
            "Titulo",
            "Nivel",
            "Duracion (h)",
            "Modulos",
            "% Aprobacion",
            "Estado",
            "Fecha de creacion",
            "Asignaciones"
        ];

        $filas =
            $model->reporteCursos($periodo);

        break;


    case "evaluaciones":

        $columnas = [
            "Titulo",
            "Curso",
            "% Aprobacion",
            "Estado",
            "Fecha de creacion",
            "N Preguntas",
            "Resultados"
        ];

        $filas =
            $model->reporteEvaluaciones($periodo);

        break;


    case "empresas":

        $columnas = [
            "Nombre",
            "NIT",
            "Correo",
            "Telefono",
            "Tipo",
            "Estado",
            "Fecha de registro",
            "Usuarios"
        ];

        $filas =
            $model->reporteEmpresas($periodo);

        break;


    case "resultados":

        $columnas = [
            "Usuario",
            "Correo",
            "Empresa",
            "Curso",
            "Evaluacion",
            "Puntuacion",
            "Porcentaje",
            "Preguntas totales",
            "Correctas",
            "Incorrectas",
            "Resultado",
            "Fecha"
        ];

        $filas =
            $model->reporteResultados($periodo);

        break;


    case "capacitacion":

        $columnas = [
            "Usuario",
            "Empresa",
            "Curso",
            "Tipo",
            "Estado",
            "Fecha asignacion"
        ];

        $filas =
            $model->reporteCapacitacion($periodo);

        break;


    default:

        header(
            "Location: reportes.php"
        );

        exit;
}


$nombreArchivo =
    "reporte_"
    . $tipo
    . "_"
    . date("Y-m-d")
    . ".csv";


header(
    "Content-Type: text/csv; charset=UTF-8"
);

header(
    "Content-Disposition: attachment; filename=\""
    . $nombreArchivo
    . "\""
);


$salida = fopen(
    "php://output",
    "w"
);


/*
 * BOM UTF-8 para Excel
 */
fwrite(
    $salida,
    "\xEF\xBB\xBF"
);


fputcsv(
    $salida,
    $columnas
);


foreach ($filas as $fila) {

    fputcsv(
        $salida,
        array_values($fila)
    );
}


fclose($salida);

exit;