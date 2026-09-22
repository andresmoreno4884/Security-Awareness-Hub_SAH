<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / CursoModel.php
 *
 * Acceso a cursos, rutas, módulos y progreso individual.
 * ============================================================
 */

class CursoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listar(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                c.id,
                c.titulo,
                c.descripcion,
                c.nivel_dificultad,
                c.duracion,
                c.numero_modulos,
                c.porcentaje_aprobacion,
                c.estado,
                c.fecha_creacion,
                c.ruta_id,
                c.orden_ruta,
                r.nombre AS ruta_nombre
            FROM cursos c
            LEFT JOIN rutas_aprendizaje r ON r.id = c.ruta_id
            ORDER BY c.fecha_creacion DESC, c.id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM cursos WHERE id = ? LIMIT 1"
        );

        $stmt->execute([$id]);

        return (bool) $stmt->fetch();
    }

    public function tituloExiste(string $titulo, int $excluirId = 0): bool
    {
        $sql = "SELECT id FROM cursos WHERE titulo = ?";
        $params = [$titulo];

        if ($excluirId > 0) {
            $sql .= " AND id <> ?";
            $params[] = $excluirId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                c.*,
                r.nombre AS ruta_nombre
            FROM cursos c
            LEFT JOIN rutas_aprendizaje r ON r.id = c.ruta_id
            WHERE c.id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        return $curso ?: null;
    }

    public function obtenerPrimerCursoAsignado(int $usuarioId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                c.*,
                r.nombre AS ruta_nombre
            FROM asignaciones a
            INNER JOIN cursos c ON c.id = a.curso_id
            LEFT JOIN rutas_aprendizaje r ON r.id = c.ruta_id
            WHERE
                a.usuario_id = ?
                AND a.tipo = 'curso'
                AND c.estado = 'activo'
            ORDER BY
                COALESCE(c.ruta_id, 999999),
                c.orden_ruta,
                c.id
            LIMIT 1
        ");

        $stmt->execute([$usuarioId]);

        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        return $curso ?: null;
    }

    public function estaAsignado(int $usuarioId, int $cursoId): bool
    {
        $stmt = $this->db->prepare("
            SELECT a.id
            FROM asignaciones a
            INNER JOIN cursos c ON c.id = a.curso_id
            WHERE
                a.usuario_id = ?
                AND a.curso_id = ?
                AND a.tipo = 'curso'
                AND c.estado = 'activo'
            LIMIT 1
        ");

        $stmt->execute([$usuarioId, $cursoId]);

        return (bool) $stmt->fetch();
    }

    public function obtenerModulos(int $cursoId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                curso_id,
                titulo,
                descripcion,
                orden,
                estado
            FROM curso_modulos
            WHERE curso_id = ? AND estado = 'activo'
            ORDER BY orden ASC, id ASC
        ");

        $stmt->execute([$cursoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerModulosConProgreso(
        int $cursoId,
        int $usuarioId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                cm.id,
                cm.curso_id,
                cm.titulo,
                cm.descripcion,
                cm.orden,
                cm.estado,
                COALESCE(pm.completado, 0) AS completado,
                pm.fecha_inicio,
                pm.fecha_completado
            FROM curso_modulos cm
            LEFT JOIN progreso_modulos pm
                ON pm.modulo_id = cm.id
                AND pm.usuario_id = ?
            WHERE cm.curso_id = ? AND cm.estado = 'activo'
            ORDER BY cm.orden ASC, cm.id ASC
        ");

        $stmt->execute([$usuarioId, $cursoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProgreso(
        int $cursoId,
        int $usuarioId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(cm.id) AS total_modulos,
                COALESCE(
                    SUM(
                        CASE
                            WHEN pm.completado = 1 THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS modulos_completados
            FROM curso_modulos cm
            LEFT JOIN progreso_modulos pm
                ON pm.modulo_id = cm.id
                AND pm.usuario_id = ?
            WHERE cm.curso_id = ? AND cm.estado = 'activo'
        ");

        $stmt->execute([$usuarioId, $cursoId]);

        $datos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $total = (int) ($datos["total_modulos"] ?? 0);
        $completados = (int) ($datos["modulos_completados"] ?? 0);

        $porcentaje = $total > 0
            ? (int) round(($completados / $total) * 100)
            : 0;

        return [
            "total" => $total,
            "completados" => $completados,
            "porcentaje" => $porcentaje,
            "estado" => $total > 0 && $completados >= $total
                ? "completado"
                : ($completados > 0 ? "progreso" : "pendiente")
        ];
    }

    public function completarModulo(
        int $usuarioId,
        int $cursoId,
        int $moduloId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT cm.id
            FROM curso_modulos cm
            INNER JOIN asignaciones a ON a.curso_id = cm.curso_id
            WHERE
                cm.id = ?
                AND cm.curso_id = ?
                AND cm.estado = 'activo'
                AND a.usuario_id = ?
                AND a.tipo = 'curso'
            LIMIT 1
        ");

        $stmt->execute([
            $moduloId,
            $cursoId,
            $usuarioId
        ]);

        if (!$stmt->fetch()) {
            return false;
        }

        // La tabla progreso_modulos no depende de una clave UNIQUE.
        // Primero verificamos si el registro ya existe para evitar duplicados.
        $stmt = $this->db->prepare("
            SELECT id
            FROM progreso_modulos
            WHERE usuario_id = ? AND modulo_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $usuarioId,
            $moduloId
        ]);

        $progresoId = $stmt->fetchColumn();

        if ($progresoId) {

            $stmt = $this->db->prepare("
                UPDATE progreso_modulos
                SET
                    completado = 1,
                    fecha_completado = COALESCE(fecha_completado, NOW())
                WHERE id = ?
            ");

            $resultado = $stmt->execute([
                $progresoId
            ]);

        } else {

            $stmt = $this->db->prepare("
                INSERT INTO progreso_modulos
                    (
                        usuario_id,
                        modulo_id,
                        completado,
                        fecha_inicio,
                        fecha_completado
                    )
                VALUES (?, ?, 1, NOW(), NOW())
            ");

            $resultado = $stmt->execute([
                $usuarioId,
                $moduloId
            ]);
        }

        if ($resultado) {

            // Sincronizamos el estado de la asignación del curso.
            $progreso = $this->obtenerProgreso(
                $cursoId,
                $usuarioId
            );

            $estadoAsignacion = $progreso["estado"];

            $stmt = $this->db->prepare("
                UPDATE asignaciones
                SET estado = ?
                WHERE
                    usuario_id = ?
                    AND curso_id = ?
                    AND tipo = 'curso'
            ");

            $stmt->execute([
                $estadoAsignacion,
                $usuarioId,
                $cursoId
            ]);
        }

        return $resultado;
    }

    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO cursos
                (
                    titulo,
                    descripcion,
                    nivel_dificultad,
                    duracion,
                    numero_modulos,
                    porcentaje_aprobacion,
                    estado,
                    ruta_id,
                    orden_ruta
                )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $datos["titulo"],
            $datos["descripcion"],
            $datos["nivel_dificultad"],
            $datos["duracion"],
            $datos["numero_modulos"],
            $datos["porcentaje_aprobacion"],
            $datos["estado"],
            $datos["ruta_id"] ?? null,
            $datos["orden_ruta"] ?? 1
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->db->prepare("
            UPDATE cursos
            SET
                titulo = ?,
                descripcion = ?,
                nivel_dificultad = ?,
                duracion = ?,
                numero_modulos = ?,
                porcentaje_aprobacion = ?,
                estado = ?,
                ruta_id = ?,
                orden_ruta = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $datos["titulo"],
            $datos["descripcion"],
            $datos["nivel_dificultad"],
            $datos["duracion"],
            $datos["numero_modulos"],
            $datos["porcentaje_aprobacion"],
            $datos["estado"],
            $datos["ruta_id"] ?? null,
            $datos["orden_ruta"] ?? 1,
            $id
        ]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare(
            "UPDATE cursos SET estado = ? WHERE id = ?"
        );

        $stmt->execute([$estado, $id]);
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM cursos WHERE id = ?"
        );

        $stmt->execute([$id]);
    }
}
