<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / ReporteModel.php
 *
 * Consultas de solo lectura (agregaciones y listados) usadas
 * por el módulo de Reportes. No modifica datos, solo los lee
 * con SQL real contra MySQL.
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
     * Indicadores generales que se muestran en las tarjetas
     * superiores del módulo de Reportes.
     */
    public function indicadores(): array
    {
        $totalUsuarios = (int) $this->db
            ->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'usuario'")
            ->fetchColumn();

        $totalCursos = (int) $this->db
            ->query("SELECT COUNT(*) FROM cursos")
            ->fetchColumn();

        $promedioAprobacion = $this->db
            ->query("SELECT ROUND(AVG(porcentaje_aprobacion)) FROM evaluaciones")
            ->fetchColumn();

        return [
            "total_usuarios" => $totalUsuarios,
            "total_cursos" => $totalCursos,
            "promedio_aprobacion" => $promedioAprobacion !== null ? (int) $promedioAprobacion : 0
        ];
    }

    /** Convierte el filtro de periodo en una condición SQL sobre una columna de fecha. */
    private function condicionPeriodo(string $columna, string $periodo): string
    {
        switch ($periodo) {
            case "month":
                return " AND {$columna} >= DATE_FORMAT(NOW(), '%Y-%m-01') ";
            case "quarter":
                return " AND {$columna} >= DATE_SUB(NOW(), INTERVAL 3 MONTH) ";
            case "year":
                return " AND {$columna} >= DATE_FORMAT(NOW(), '%Y-01-01') ";
            default:
                return "";
        }
    }

    public function reporteUsuarios(string $periodo): array
    {
        $sql = "
            SELECT nombres, apellidos, correo, rol, estado, fecha_registro
            FROM usuarios
            WHERE 1 = 1
        " . $this->condicionPeriodo("fecha_registro", $periodo) . "
            ORDER BY fecha_registro DESC
        ";

        return $this->db->query($sql)->fetchAll();
    }

    public function reporteCursos(string $periodo): array
    {
        $sql = "
            SELECT titulo, nivel_dificultad, duracion, numero_modulos,
                   porcentaje_aprobacion, estado, fecha_creacion
            FROM cursos
            WHERE 1 = 1
        " . $this->condicionPeriodo("fecha_creacion", $periodo) . "
            ORDER BY fecha_creacion DESC
        ";

        return $this->db->query($sql)->fetchAll();
    }

    public function reporteEvaluaciones(string $periodo): array
    {
        $sql = "
            SELECT
                e.titulo, c.titulo AS curso_titulo, e.porcentaje_aprobacion,
                e.estado, e.fecha_creacion,
                (SELECT COUNT(*) FROM preguntas p WHERE p.evaluacion_id = e.id) AS total_preguntas
            FROM evaluaciones e
            INNER JOIN cursos c ON c.id = e.curso_id
            WHERE 1 = 1
        " . $this->condicionPeriodo("e.fecha_creacion", $periodo) . "
            ORDER BY e.fecha_creacion DESC
        ";

        return $this->db->query($sql)->fetchAll();
    }

    public function reporteEmpresas(string $periodo): array
    {
        $sql = "
            SELECT nombre, nit, correo, tipo, estado, fecha_registro
            FROM empresas
            WHERE 1 = 1
        " . $this->condicionPeriodo("fecha_registro", $periodo) . "
            ORDER BY fecha_registro DESC
        ";

        return $this->db->query($sql)->fetchAll();
    }
}