<?php

require_once __DIR__ . '/../Config/database.php';

class AsignacionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * ============================================================
     * OBTENER TODAS LAS ASIGNACIONES
     * ============================================================
     */
    public function obtenerTodas(): array
    {
        $sql = "
            SELECT
                a.id,
                a.usuario_id,
                a.curso_id,
                a.evaluacion_id,
                a.tipo,
                a.fecha_asignacion,
                a.estado,

                CONCAT(u.nombres, ' ', u.apellidos) AS usuario,

                CASE
                    WHEN a.tipo = 'curso' THEN c.titulo
                    WHEN a.tipo = 'evaluacion' THEN e.titulo
                    ELSE 'Sin asignación'
                END AS contenido,

                c.titulo AS curso_titulo,
                e.titulo AS evaluacion_titulo

            FROM asignaciones a

            INNER JOIN usuarios u
                ON u.id = a.usuario_id

            LEFT JOIN cursos c
                ON c.id = a.curso_id

            LEFT JOIN evaluaciones e
                ON e.id = a.evaluacion_id

            ORDER BY a.fecha_asignacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * ============================================================
     * OBTENER UNA ASIGNACIÓN
     * ============================================================
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "
            SELECT
                a.id,
                a.usuario_id,
                a.curso_id,
                a.evaluacion_id,
                a.tipo,
                a.fecha_asignacion,
                a.estado,

                CONCAT(u.nombres, ' ', u.apellidos) AS usuario,

                c.titulo AS curso_titulo,
                e.titulo AS evaluacion_titulo

            FROM asignaciones a

            INNER JOIN usuarios u
                ON u.id = a.usuario_id

            LEFT JOIN cursos c
                ON c.id = a.curso_id

            LEFT JOIN evaluaciones e
                ON e.id = a.evaluacion_id

            WHERE a.id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * ============================================================
     * CREAR ASIGNACIÓN
     * ============================================================
     */
    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO asignaciones (
                usuario_id,
                curso_id,
                evaluacion_id,
                tipo,
                estado
            )
            VALUES (
                :usuario_id,
                :curso_id,
                :evaluacion_id,
                :tipo,
                :estado
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $datos['usuario_id'],
            ':curso_id' => $datos['curso_id'],
            ':evaluacion_id' => $datos['evaluacion_id'],
            ':tipo' => $datos['tipo'],
            ':estado' => $datos['estado']
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * ============================================================
     * ACTUALIZAR ASIGNACIÓN
     * ============================================================
     */
    public function actualizar(int $id, array $datos): bool
    {
        $sql = "
            UPDATE asignaciones
            SET
                usuario_id = :usuario_id,
                curso_id = :curso_id,
                evaluacion_id = :evaluacion_id,
                tipo = :tipo,
                estado = :estado
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $datos['usuario_id'],
            ':curso_id' => $datos['curso_id'],
            ':evaluacion_id' => $datos['evaluacion_id'],
            ':tipo' => $datos['tipo'],
            ':estado' => $datos['estado']
        ]);
    }

    /**
     * ============================================================
     * ACTUALIZAR SOLO EL ESTADO
     * ============================================================
     */
    public function actualizarEstado(int $id, string $estado): bool
    {
        $sql = "
            UPDATE asignaciones
            SET estado = :estado
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':estado' => $estado
        ]);
    }

    /**
     * ============================================================
     * ELIMINAR ASIGNACIÓN
     * ============================================================
     */
    public function eliminar(int $id): bool
    {
        $sql = "
            DELETE FROM asignaciones
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    /**
     * ============================================================
     * OBTENER USUARIOS ACTIVOS
     * ============================================================
     */
    public function obtenerUsuarios(): array
    {
        $sql = "
            SELECT
                id,
                nombres,
                apellidos,
                correo
            FROM usuarios
            WHERE estado = 'activo'
            ORDER BY nombres ASC, apellidos ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * ============================================================
     * OBTENER CURSOS ACTIVOS
     * ============================================================
     */
    public function obtenerCursos(): array
    {
        $sql = "
            SELECT
                id,
                titulo,
                nivel_dificultad,
                duracion
            FROM cursos
            WHERE estado = 'activo'
            ORDER BY titulo ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * ============================================================
     * OBTENER EVALUACIONES ACTIVAS
     * ============================================================
     */
    public function obtenerEvaluaciones(): array
    {
        $sql = "
            SELECT
                e.id,
                e.curso_id,
                e.titulo,
                e.porcentaje_aprobacion,
                c.titulo AS curso_titulo
            FROM evaluaciones e

            INNER JOIN cursos c
                ON c.id = e.curso_id

            WHERE e.estado = 'activo'

            ORDER BY e.titulo ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * ============================================================
     * CONTADORES DEL DASHBOARD
     * ============================================================
     */
    public function obtenerEstadisticas(): array
    {
        $sql = "
            SELECT

                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN estado IN ('pendiente', 'progreso')
                        THEN 1
                        ELSE 0
                    END
                ) AS activas,

                SUM(
                    CASE
                        WHEN estado = 'completado'
                        THEN 1
                        ELSE 0
                    END
                ) AS completadas

            FROM asignaciones
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $resultado = $stmt->fetch();

        return [
            'total' => (int) ($resultado['total'] ?? 0),
            'activas' => (int) ($resultado['activas'] ?? 0),
            'completadas' => (int) ($resultado['completadas'] ?? 0)
        ];
    }
}