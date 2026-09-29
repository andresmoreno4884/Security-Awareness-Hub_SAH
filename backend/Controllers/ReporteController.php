<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Controllers / ReporteController.php
 *
 * Módulo de Reportes: solo lectura, protegido para administradores.
 * GET /Api/reportes.php                    -> indicadores generales
 * GET /Api/reportes.php?tipo=X&periodo=Y    -> tabla del reporte
 * ============================================================
 */

class ReporteController
{
    private ReporteModel $reportes;

    public function __construct()
    {
        Auth::requireAdmin("No tienes permisos para consultar reportes.");

        $this->reportes = new ReporteModel();
    }

    public function handle(): void
    {
        if (Request::method() !== "GET") {
            Response::json(false, "Método HTTP no permitido.", [], 405);
        }

        $tipo = Request::query("tipo", "");
        $periodo = Request::query("periodo", "all");

        if (!in_array($periodo, ["all", "month", "quarter", "year"], true)) {
            $periodo = "all";
        }

        try {

            // Sin "tipo" -> solo los indicadores generales (tarjetas del dashboard de reportes).
            if ($tipo === "") {
                Response::json(true, "Indicadores obtenidos correctamente.", [
                    "indicadores" => $this->reportes->indicadores()
                ]);
            }

            switch ($tipo) {

                case "usuarios":
                    $columnas = ["Nombres", "Apellidos", "Correo", "Rol", "Estado", "Fecha de registro"];
                    $filas = $this->reportes->reporteUsuarios($periodo);
                    break;

                case "cursos":
                    $columnas = ["Título", "Nivel", "Duración (h)", "Módulos", "% Aprobación", "Estado", "Fecha de creación"];
                    $filas = $this->reportes->reporteCursos($periodo);
                    break;

                case "evaluaciones":
                    $columnas = ["Título", "Curso", "% Aprobación", "Estado", "Fecha de creación", "N° Preguntas"];
                    $filas = $this->reportes->reporteEvaluaciones($periodo);
                    break;

                case "empresas":
                    $columnas = ["Nombre", "NIT", "Correo", "Tipo", "Estado", "Fecha de registro"];
                    $filas = $this->reportes->reporteEmpresas($periodo);
                    break;

                case "resultados":
                case "capacitacion":
                    // Estos reportes requieren un módulo de intentos de evaluación
                    // (tabla de resultados por usuario) que aún no existe en la base de datos.
                    Response::json(true, "Este reporte necesita el módulo de resultados de evaluación, que todavía no está implementado.", [
                        "columnas" => [],
                        "filas" => [],
                        "indicadores" => $this->reportes->indicadores()
                    ]);
                    return;

                default:
                    Response::json(false, "Tipo de reporte no válido.", [], 400);
                    return;
            }

            Response::json(true, "Reporte generado correctamente.", [
                "columnas" => $columnas,
                "filas" => $filas,
                "indicadores" => $this->reportes->indicadores()
            ]);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible generar el reporte.", [], 500);
        }
    }
}