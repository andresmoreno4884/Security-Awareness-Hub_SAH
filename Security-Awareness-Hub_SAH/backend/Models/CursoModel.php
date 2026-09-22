<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / CursoModel.php
 * Acceso a datos de la tabla "cursos".
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
            SELECT id, titulo, descripcion, nivel_dificultad, duracion,
                   numero_modulos, porcentaje_aprobacion, estado, fecha_creacion
            FROM cursos
            ORDER BY fecha_creacion DESC, id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM cursos WHERE id = ? LIMIT 1");
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

    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO cursos
                (titulo, descripcion, nivel_dificultad, duracion, numero_modulos, porcentaje_aprobacion, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $datos["titulo"],
            $datos["descripcion"],
            $datos["nivel_dificultad"],
            $datos["duracion"],
            $datos["numero_modulos"],
            $datos["porcentaje_aprobacion"],
            $datos["estado"]
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->db->prepare("
            UPDATE cursos
            SET titulo = ?, descripcion = ?, nivel_dificultad = ?, duracion = ?,
                numero_modulos = ?, porcentaje_aprobacion = ?, estado = ?
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
            $id
        ]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare("UPDATE cursos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM cursos WHERE id = ?");
        $stmt->execute([$id]);
    }
}
