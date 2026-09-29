<?php

class ProgresoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Buscar el progreso de una asignación.
     */
    public function buscarPorAsignacion(int $asignacionId): ?array
    {
        $sql = "
            SELECT
                p.id,
                p.asignacion_id,
                p.porcentaje,
                p.modulos_completados,
                p.ultima_actividad,
                p.fecha_actualizacion
            FROM progreso p
            WHERE p.asignacion_id = :asignacion_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':asignacion_id' => $asignacionId
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtener el progreso junto con la información
     * del usuario y del curso.
     */
    public function buscarDetalle(int $asignacionId): ?array
    {
        $sql = "
            SELECT
                p.id,
                p.asignacion_id,
                p.porcentaje,
                p.modulos_completados,
                p.ultima_actividad,
                p.fecha_actualizacion,

                a.usuario_id,
                a.curso_id,
                a.estado AS asignacion_estado,

                u.nombres,
                u.apellidos,
                u.correo,
                u.empresa_id,

                e.nombre AS empresa_nombre,

                c.titulo AS curso_titulo,
                c.numero_modulos,
                c.estado AS curso_estado

            FROM progreso p

            INNER JOIN asignaciones a
                ON a.id = p.asignacion_id

            INNER JOIN usuarios u
                ON u.id = a.usuario_id

            LEFT JOIN empresas e
                ON e.id = u.empresa_id

            INNER JOIN cursos c
                ON c.id = a.curso_id

            WHERE p.asignacion_id = :asignacion_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':asignacion_id' => $asignacionId
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Crear el progreso inicial de una asignación.
     */
    public function crear(int $asignacionId): int
    {
        $sql = "
            INSERT INTO progreso (
                asignacion_id,
                porcentaje,
                modulos_completados,
                ultima_actividad
            )
            VALUES (
                :asignacion_id,
                0,
                0,
                CURRENT_TIMESTAMP
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':asignacion_id' => $asignacionId
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Verificar si una asignación ya tiene
     * un registro de progreso.
     */
    public function existe(int $asignacionId): bool
    {
        $sql = "
            SELECT id
            FROM progreso
            WHERE asignacion_id = :asignacion_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':asignacion_id' => $asignacionId
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Actualizar el porcentaje y módulos completados.
     */
    public function actualizar(
        int $asignacionId,
        int $porcentaje,
        int $modulosCompletados
    ): bool {
        $sql = "
            UPDATE progreso
            SET
                porcentaje = :porcentaje,
                modulos_completados = :modulos_completados,
                ultima_actividad = CURRENT_TIMESTAMP
            WHERE asignacion_id = :asignacion_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':porcentaje' => $porcentaje,
            ':modulos_completados' => $modulosCompletados,
            ':asignacion_id' => $asignacionId
        ]);
    }

    /**
     * Actualizar únicamente la última actividad.
     */
    public function registrarActividad(int $asignacionId): bool
    {
        $sql = "
            UPDATE progreso
            SET
                ultima_actividad = CURRENT_TIMESTAMP
            WHERE asignacion_id = :asignacion_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':asignacion_id' => $asignacionId
        ]);
    }

    /**
     * Listar el progreso de un empleado.
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "
            SELECT
                p.id,
                p.asignacion_id,
                p.porcentaje,
                p.modulos_completados,
                p.ultima_actividad,
                p.fecha_actualizacion,

                a.curso_id,
                a.estado AS asignacion_estado,
                a.fecha_asignacion,
                a.fecha_finalizacion,

                c.titulo AS curso_titulo,
                c.descripcion AS curso_descripcion,
                c.nivel_dificultad,
                c.duracion,
                c.numero_modulos,
                c.porcentaje_aprobacion,
                c.estado AS curso_estado

            FROM progreso p

            INNER JOIN asignaciones a
                ON a.id = p.asignacion_id

            INNER JOIN cursos c
                ON c.id = a.curso_id

            WHERE a.usuario_id = :usuario_id

            ORDER BY p.fecha_actualizacion DESC, p.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Listar progreso de una empresa.
     */
    public function listarPorEmpresa(int $empresaId): array
    {
        $sql = "
            SELECT
                p.id,
                p.asignacion_id,
                p.porcentaje,
                p.modulos_completados,
                p.ultima_actividad,
                p.fecha_actualizacion,

                a.usuario_id,
                a.curso_id,
                a.estado AS asignacion_estado,

                u.nombres,
                u.apellidos,
                u.correo,

                e.nombre AS empresa_nombre,

                c.titulo AS curso_titulo,
                c.numero_modulos

            FROM progreso p

            INNER JOIN asignaciones a
                ON a.id = p.asignacion_id

            INNER JOIN usuarios u
                ON u.id = a.usuario_id

            INNER JOIN empresas e
                ON e.id = u.empresa_id

            INNER JOIN cursos c
                ON c.id = a.curso_id

            WHERE u.empresa_id = :empresa_id

            ORDER BY p.fecha_actualizacion DESC, p.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}