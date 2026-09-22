<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Controllers / ReporteController.php
 * ============================================================
 */

class ReporteController
{
    private ReporteModel $reportes;

    public function __construct()
    {
        Auth::requireAdmin();

        $this->reportes = new ReporteModel();
    }

    public function handle(): void
    {
        if (Request::method() !== "GET") {
            Response::json(
                false,
                "Método HTTP no permitido.",
                [],
                405
            );
        }

        $tipo = Request::query("tipo", "");

        $periodo = Request::query(
            "periodo",
            "all"
        );

        if (
            !in_array(
                $periodo,
                [
                    "all",
                    "month",
                    "quarter",
                    "year"
                ],
                true
            )
        ) {
            $periodo = "all";
        }

        try {

            /*
             * ====================================================
             * INDICADORES GENERALES
             * ====================================================
             */

            if ($tipo === "") {

                Response::json(
                    true,
                    "Indicadores obtenidos correctamente.",
                    [
                        "indicadores" =>
                            $this->reportes->indicadores()
                    ]
                );
            }


            /*
             * ====================================================
             * REPORTE DE USUARIOS
             * ====================================================
             */

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
                        $this->reportes
                            ->reporteUsuarios(
                                $periodo
                            );

                    break;


                /*
                 * =================================================
                 * REPORTE DE CURSOS
                 * =================================================
                 */

                case "cursos":

                    $columnas = [
                        "Título",
                        "Nivel",
                        "Duración (h)",
                        "Módulos",
                        "% Aprobación",
                        "Estado",
                        "Fecha de creación",
                        "Asignaciones"
                    ];

                    $filas =
                        $this->reportes
                            ->reporteCursos(
                                $periodo
                            );

                    break;


                /*
                 * =================================================
                 * REPORTE DE EVALUACIONES
                 * =================================================
                 */

                case "evaluaciones":

                    $columnas = [
                        "Título",
                        "Curso",
                        "% Aprobación",
                        "Estado",
                        "Fecha de creación",
                        "N° Preguntas",
                        "Resultados"
                    ];

                    $filas =
                        $this->reportes
                            ->reporteEvaluaciones(
                                $periodo
                            );

                    break;


                /*
                 * =================================================
                 * REPORTE DE EMPRESAS
                 * =================================================
                 */

                case "empresas":

                    $columnas = [
                        "Nombre",
                        "NIT",
                        "Correo",
                        "Teléfono",
                        "Tipo",
                        "Estado",
                        "Fecha de registro",
                        "Usuarios"
                    ];

                    $filas =
                        $this->reportes
                            ->reporteEmpresas(
                                $periodo
                            );

                    break;


                /*
                 * =================================================
                 * REPORTE DE RESULTADOS
                 * =================================================
                 */

                case "resultados":

                    $columnas = [
                        "Usuario",
                        "Correo",
                        "Empresa",
                        "Curso",
                        "Evaluación",
                        "Puntuación",
                        "Porcentaje",
                        "Preguntas totales",
                        "Correctas",
                        "Incorrectas",
                        "Resultado",
                        "Fecha"
                    ];

                    $filas =
                        $this->reportes
                            ->reporteResultados(
                                $periodo
                            );

                    break;


                /*
                 * =================================================
                 * REPORTE DE CAPACITACIÓN
                 * =================================================
                 */

                case "capacitacion":

                    $columnas = [
                        "Usuario",
                        "Empresa",
                        "Curso",
                        "Tipo",
                        "Estado",
                        "Fecha asignación"
                    ];

                    $filas =
                        $this->reportes
                            ->reporteCapacitacion(
                                $periodo
                            );

                    break;


                /*
                 * =================================================
                 * TIPO NO VÁLIDO
                 * =================================================
                 */

                default:

                    Response::json(
                        false,
                        "Tipo de reporte no válido.",
                        [],
                        400
                    );

                    return;
            }


            /*
             * ====================================================
             * RESPUESTA FINAL
             * ====================================================
             */

            Response::json(
                true,
                "Reporte generado correctamente.",
                [
                    "columnas" =>
                        $columnas,

                    "filas" =>
                        $filas,

                    "indicadores" =>
                        $this->reportes->indicadores()
                ]
            );

        } catch (Throwable $e) {

            Response::json(
                false,
                "No fue posible generar el reporte.",
                [],
                500
            );
        }
    }
}