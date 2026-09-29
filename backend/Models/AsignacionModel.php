```php
<?php

class AsignacionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Listar todas las asignaciones.
     *
     * Puede recibir filtros opcionales por:
     * - usuario_id
     * - curso_id
     * - estado
     * - empresa_id
     */
    public function listar(
        int $usuarioId = 0,
        int $cursoId = 0,
        string $estado = "",
        int $empresaId = 0
    ): array {
        $sql = "
            SELECT
                a.id,
                a.usuario_id,
                a.curso_id,
                a.estado,
                a.fecha_asignacion,
                a.fecha_finalizacion,

                u.nombres,
                u.apellidos,
                u.correo,
                u.empresa_id,

                e.nombre AS empresa_nombre,

                c.titulo AS curso_titulo,
                c.descripcion AS curso_descripcion,
                c.nivel_dificultad,
                c.duracion,
                c.numero_modulos,
                c.porcentaje_aprobacion,
                c.estado AS curso_estado

            FROM asignaciones a

            INNER JOIN usuarios u
                ON u.id = a.usuario_id

            LEFT JOIN empresas e
                ON e.id = u.empresa_id

            INNER JOIN cursos c
                ON c.id = a.curso_id

            WHERE 1 = 1
        ";

        $params = [];

        if ($usuarioId > 0) {
            $sql .= " AND a.usuario_id = :usuario_id";
            $params[':usuario_id'] = $usuarioId;
        }

        if ($cursoId > 0) {
            $sql .= " AND a.curso_id = :curso_id";
            $params[':curso_id'] = $cursoId;
        }

        if ($estado !== "") {
            $sql .= " AND a.estado = :estado";
            $params[':estado'] = $estado;
        }

        if ($empresaId > 0) {
            $sql .= " AND u.empresa_id = :empresa_id";
            $params[':empresa_id'] = $empresaId;
        }

        $sql .= " ORDER BY a.fecha_asignacion DESC, a.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar una asignación por su ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                a.id,
                a.usuario_id,
                a.curso_id,
                a.estado,
                a.fecha_asignacion,
                a.fecha_finalizacion,

                u.nombres,
                u.apellidos,
                u.correo,
                u.rol,
                u.empresa_id,

                e.nombre AS empresa_nombre,

                c.titulo AS curso_titulo,
                c.descripcion AS curso_descripcion,
                c.nivel_dificultad,
                c.duracion,
                c.numero_modulos,
                c.porcentaje_aprobacion,
                c.estado AS curso_estado

            FROM asignaciones a

            INNER JOIN usuarios u
                ON u.id = a.usuario_id

            LEFT JOIN empresas e
                ON e.id = u.empresa_id

            INNER JOIN cursos c
                ON c.id = a.curso_id

            WHERE a.id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Buscar una asignación específica entre un usuario y un curso.
     *
     * La tabla tiene una restricción UNIQUE(usuario_id, curso_id),
     * por lo que solo puede existir una asignación activa/histórica
     * de ese curso para ese usuario.
     */
    public function buscarPorUsuarioYCurso(
        int $usuarioId,
        int $cursoId
    ): ?array {
        $sql = "
            SELECT
                id,
                usuario_id,
                curso_id,
                estado,
                fecha_asignacion,
                fecha_finalizacion
            FROM asignaciones
            WHERE usuario_id = :usuario_id
              AND curso_id = :curso_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':curso_id' => $cursoId
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Verificar si ya existe una asignación entre un usuario y un curso.
     */
    public function existe(
        int $usuarioId,
        int $cursoId
    ): bool {
        $sql = "
            SELECT id
            FROM asignaciones
            WHERE usuario_id = :usuario_id
              AND curso_id = :curso_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':curso_id' => $cursoId
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Crear una nueva asignación.
     */
    public function crear(
        int $usuarioId,
        int $cursoId
    ): int {
        $sql = "
            INSERT INTO asignaciones (
                usuario_id,
                curso_id,
                estado
            )
            VALUES (
                :usuario_id,
                :curso_id,
                'asignado'
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':curso_id' => $cursoId
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Cambiar el estado de una asignación.
     *
     * Estados permitidos:
     * - asignado
     * - en_progreso
     * - completado
     * - cancelado
     */
    public function cambiarEstado(
        int $id,
        string $estado
    ): bool {
        $sql = "
            UPDATE asignaciones
            SET
                estado = :estado,
                fecha_finalizacion = CASE
                    WHEN :estado_final = 'completado'
                        THEN CURRENT_TIMESTAMP
                    WHEN :estado_cancelado = 'cancelado'
                        THEN fecha_finalizacion
                    ELSE fecha_finalizacion
                END
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':estado' => $estado,
            ':estado_final' => $estado,
            ':estado_cancelado' => $estado,
            ':id' => $id
        ]);
    }

    /**
     * Cancelar una asignación.
     */
    public function cancelar(int $id): bool
    {
        $sql = "
            UPDATE asignaciones
            SET estado = 'cancelado'
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    /**
     * Obtener las asignaciones de un empleado.
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->listar($usuarioId, 0, "", 0);
    }

    /**
     * Obtener las asignaciones de todos los empleados
     * pertenecientes a una empresa.
     */
    public function listarPorEmpresa(int $empresaId): array
    {
        return $this->listar(0, 0, "", $empresaId);
    }

    /**
     * Obtener asignaciones activas de una empresa.
     *
     * Se consideran activas:
     * - asignado
     * - en_progreso
     */
    public function listarActivasPorEmpresa(int $empresaId): array
    {
        $sql = "
            SELECT
                a.id,
                a.usuario_id,
                a.curso_id,
                a.estado,
                a.fecha_asignacion,
                a.fecha_finalizacion,

                u.nombres,
                u.apellidos,
                u.correo,
                u.empresa_id,

                e.nombre AS empresa_nombre,

                c.titulo AS curso_titulo,
                c.nivel_dificultad,
                c.duracion,
                c.numero_modulos

            FROM asignaciones a

            INNER JOIN usuarios u
                ON u.id = a.usuario_id

            LEFT JOIN empresas e
                ON e.id = u.empresa_id

            INNER JOIN cursos c
                ON c.id = a.curso_id

            WHERE u.empresa_id = :empresa_id
              AND a.estado IN ('asignado', 'en_progreso')

            ORDER BY a.fecha_asignacion DESC, a.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Eliminar una asignación.
     *
     * Esta función queda disponible para el controlador.
     * La autorización se debe realizar en el Controller,
     * no dentro del Model.
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
}
```
