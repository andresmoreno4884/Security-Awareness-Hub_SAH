<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * MODELO - DASHBOARD DEL USUARIO
 * ============================================================
 */

require_once __DIR__ . '/../Config/database.php';

class UsuarioDashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }


    /**
     * ========================================================
     * RESUMEN DEL USUARIO
     * ========================================================
     */

    public function obtenerResumen(int $usuarioId): array
    {
        $sql = "
            SELECT

                COUNT(
                    DISTINCT CASE
                        WHEN a.tipo = 'curso'
                        AND a.curso_id IS NOT NULL
                        THEN a.curso_id
                    END
                ) AS cursos_asignados,

                COUNT(
                    DISTINCT CASE
                        WHEN a.tipo = 'curso'
                        AND a.estado = 'progreso'
                        THEN a.curso_id
                    END
                ) AS cursos_progreso,

                COUNT(
                    DISTINCT CASE
                        WHEN a.tipo = 'curso'
                        AND a.estado = 'completado'
                        THEN a.curso_id
                    END
                ) AS cursos_completados,

                COUNT(
                    DISTINCT CASE
                        WHEN a.tipo = 'evaluacion'
                        AND a.evaluacion_id IS NOT NULL
                        AND a.estado <> 'completado'
                        THEN a.evaluacion_id
                    END
                ) AS evaluaciones_pendientes,

                COALESCE(
                    ROUND(
                        AVG(
                            CASE
                                WHEN a.tipo = 'curso'
                                THEN
                                    CASE
                                        WHEN a.estado = 'completado'
                                            THEN 100

                                        WHEN a.estado = 'progreso'
                                            THEN 50

                                        ELSE 0
                                    END
                            END
                        )
                    ),
                    0
                ) AS progreso_general

            FROM asignaciones a

            WHERE a.usuario_id = :usuario_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        $resultado = $stmt->fetch();

        return $resultado ?: [
            'cursos_asignados' => 0,
            'cursos_progreso' => 0,
            'cursos_completados' => 0,
            'evaluaciones_pendientes' => 0,
            'progreso_general' => 0
        ];
    }


    /**
     * ========================================================
     * CURSOS ASIGNADOS
     * ========================================================
     */

    public function obtenerCursos(int $usuarioId): array
    {
        $sql = "
            SELECT

                a.id AS asignacion_id,

                c.id AS curso_id,

                c.titulo,

                c.descripcion,

                c.nivel_dificultad,

                c.duracion,

                c.numero_modulos,

                c.porcentaje_aprobacion,

                a.estado,

                a.fecha_asignacion

            FROM asignaciones a

            INNER JOIN cursos c
                ON c.id = a.curso_id

            WHERE
                a.usuario_id = :usuario_id

                AND a.tipo = 'curso'

                AND c.estado = 'activo'

            ORDER BY

                CASE a.estado

                    WHEN 'progreso' THEN 1

                    WHEN 'pendiente' THEN 2

                    WHEN 'completado' THEN 3

                    ELSE 4

                END,

                a.fecha_asignacion DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }
}