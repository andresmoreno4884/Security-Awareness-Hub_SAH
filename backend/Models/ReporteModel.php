<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / ReporteModel.php
 *
 * Módulo de reportes.
 * Consultas de solo lectura sobre MySQL.
 * ============================================================
 */

class ReporteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * ========================================================
     * INDICADORES GENERALES
     * ========================================================
     */
    public function indicadores(): array
    {
        $totalUsuarios = (int) $this->db
            ->query("
                SELECT COUNT(*)
                FROM usuarios
                WHERE rol IN ('empleado', 'admin_empresa', 'admin')
            ")
            ->fetchColumn();

        $totalEmpleados = (int) $this->db
            ->query("
                SELECT COUNT(*)
                FROM usuarios
                WHERE rol = 'empleado'
            ")
            ->fetchColumn();

        $totalCursos = (int) $this->db
            ->query("
                SELECT COUNT(*)
                FROM cursos
            ")
            ->fetchColumn();

        $totalEmpresas = (int) $this->db
            ->query("
                SELECT COUNT(*)
                FROM empresas
            ")
            ->fetchColumn();

        $totalEvaluaciones = (int) $this->db
            ->query("
                SELECT COUNT(*)
                FROM evaluaciones
            ")
            ->fetchColumn();

        $totalAsignaciones = (int) $this->db
            ->query("
                SELECT COUNT(*)
                FROM asignaciones
            ")
            ->fetchColumn();

        $totalResultados = (int) $this->db
            ->query("
                SELECT COUNT(*)
                FROM resultados
            ")
            ->fetchColumn();

        $promedioAprobacion = $this->db
            ->query("
                SELECT AVG(porcentaje)
                FROM resultados
            ")
            ->fetchColumn();

        return [
            "total_usuarios" => $totalUsuarios,
            "total_empleados" => $totalEmpleados,
            "total_cursos" => $totalCursos,
            "total_empresas" => $totalEmpresas,
            "total_evaluaciones" => $totalEvaluaciones,
            "total_asignaciones" => $totalAsignaciones,
            "total_resultados" => $totalResultados,
            "promedio_aprobacion" => $promedioAprobacion !== null
                ? round((float) $promedioAprobacion)
                : 0
        ];
    }

    /**
     * ========================================================
     * CONDICIÓN DE PERIODO
     * ========================================================
     */
    private function condicionPeriodo(
        string $columna,
        string $periodo
    ): string {

        switch ($periodo) {

            case "month":
                return "
                    AND {$columna} >= DATE_FORMAT(
                        NOW(),
                        '%Y-%m-01'
                    )
                ";

            case "quarter":
                return "
                    AND {$columna} >= DATE_SUB(
                        NOW(),
                        INTERVAL 3 MONTH
                    )
                ";

            case "year":
                return "
                    AND {$columna} >= DATE_FORMAT(
                        NOW(),
                        '%Y-01-01'
                    )
                ";

            default:
                return "";
        }
    }

    /**
     * ========================================================
     * REPORTE DE USUARIOS
     * ========================================================
     */
    public function reporteUsuarios(string $periodo): array
    {
        $sql = "
            SELECT
                u.nombres,
                u.apellidos,
                u.correo,
                u.rol,
                COALESCE(e.nombre, 'Sin empresa') AS empresa,
                u.estado,
                u.fecha_registro
            FROM usuarios u
            LEFT JOIN empresas e
                ON e.id = u.empresa_id
            WHERE 1 = 1
        ";

        $sql .= $this->condicionPeriodo(
            "u.fecha_registro",
            $periodo
        );

        $sql .= "
            ORDER BY u.fecha_registro DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll();
    }

    /**
     * ========================================================
     * REPORTE DE CURSOS
     * ========================================================
     */
    public function reporteCursos(string $periodo): array
    {
        $sql = "
            SELECT
                c.titulo,
                c.nivel_dificultad,
                c.duracion,
                c.numero_modulos,
                c.porcentaje_aprobacion,
                c.estado,
                c.fecha_creacion,
                COUNT(a.id) AS asignaciones
            FROM cursos c

            LEFT JOIN asignaciones a
                ON a.curso_id = c.id

            WHERE 1 = 1
        ";

        $sql .= $this->condicionPeriodo(
            "c.fecha_creacion",
            $periodo
        );

        $sql .= "
            GROUP BY
                c.id,
                c.titulo,
                c.nivel_dificultad,
                c.duracion,
                c.numero_modulos,
                c.porcentaje_aprobacion,
                c.estado,
                c.fecha_creacion

            ORDER BY c.fecha_creacion DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll();
    }

    /**
     * ========================================================
     * REPORTE DE EVALUACIONES
     * ========================================================
     */
    public function reporteEvaluaciones(string $periodo): array
    {
        $sql = "
            SELECT
                e.titulo,
                c.titulo AS curso_titulo,
                e.porcentaje_aprobacion,
                e.estado,
                e.fecha_creacion,

                (
                    SELECT COUNT(*)
                    FROM preguntas p
                    WHERE p.evaluacion_id = e.id
                ) AS total_preguntas,

                (
                    SELECT COUNT(*)
                    FROM resultados r
                    WHERE r.evaluacion_id = e.id
                ) AS resultados_registrados

            FROM evaluaciones e

            INNER JOIN cursos c
                ON c.id = e.curso_id

            WHERE 1 = 1
        ";

        $sql .= $this->condicionPeriodo(
            "e.fecha_creacion",
            $periodo
        );

        $sql .= "
            ORDER BY e.fecha_creacion DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll();
    }

    /**
     * ========================================================
     * REPORTE DE EMPRESAS
     * ========================================================
     */
    public function reporteEmpresas(string $periodo): array
    {
        $sql = "
            SELECT
                e.nombre,
                e.nit,
                e.correo,
                e.telefono,
                e.tipo,
                e.estado,
                e.fecha_registro,

                (
                    SELECT COUNT(*)
                    FROM usuarios u
                    WHERE u.empresa_id = e.id
                ) AS usuarios

            FROM empresas e

            WHERE 1 = 1
        ";

        $sql .= $this->condicionPeriodo(
            "e.fecha_registro",
            $periodo
        );

        $sql .= "
            ORDER BY e.fecha_registro DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll();
    }

    /**
     * ========================================================
     * REPORTE DE RESULTADOS
     * ========================================================
     */
    public function reporteResultados(string $periodo): array
    {
        $sql = "
            SELECT

                CONCAT(
                    u.nombres,
                    ' ',
                    u.apellidos
                ) AS usuario,

                u.correo,

                COALESCE(
                    emp.nombre,
                    'Sin empresa'
                ) AS empresa,

                c.titulo AS curso,

                ev.titulo AS evaluacion,

                r.puntuacion,

                r.porcentaje,

                r.preguntas_totales,

                r.preguntas_correctas,

                r.preguntas_incorrectas,

                CASE
                    WHEN r.aprobado = 1
                    THEN 'Aprobado'
                    ELSE 'No aprobado'
                END AS resultado,

                r.fecha_resultado

            FROM resultados r

            INNER JOIN usuarios u
                ON u.id = r.usuario_id

            INNER JOIN evaluaciones ev
                ON ev.id = r.evaluacion_id

            INNER JOIN cursos c
                ON c.id = ev.curso_id

            LEFT JOIN empresas emp
                ON emp.id = u.empresa_id

            WHERE 1 = 1
        ";

        $sql .= $this->condicionPeriodo(
            "r.fecha_resultado",
            $periodo
        );

        $sql .= "
            ORDER BY r.fecha_resultado DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll();
    }

    /**
     * ========================================================
     * REPORTE DE CAPACITACIÓN
     * ========================================================
     */
    public function reporteCapacitacion(string $periodo): array
    {
        $sql = "
            SELECT

                CONCAT(
                    u.nombres,
                    ' ',
                    u.apellidos
                ) AS usuario,

                COALESCE(
                    emp.nombre,
                    'Sin empresa'
                ) AS empresa,

                c.titulo AS curso,

                a.tipo,

                a.estado,

                a.fecha_asignacion

            FROM asignaciones a

            INNER JOIN usuarios u
                ON u.id = a.usuario_id

            LEFT JOIN empresas emp
                ON emp.id = u.empresa_id

            LEFT JOIN cursos c
                ON c.id = a.curso_id

            WHERE 1 = 1
        ";

        $sql .= $this->condicionPeriodo(
            "a.fecha_asignacion",
            $periodo
        );

        $sql .= "
            ORDER BY a.fecha_asignacion DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll();
    }
}