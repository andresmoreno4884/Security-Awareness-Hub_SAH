<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / CursoModel.php
 *
 * Gestión de cursos.
 *
 * Este modelo corresponde al esquema actual de la tabla
 * "cursos" de la base de datos security_awareness_hub.
 *
 * ============================================================
 */

class CursoModel
{
    private PDO $db;

    /**
     * ========================================================
     * CONSTRUCTOR
     * ========================================================
     */
    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * ========================================================
     * LISTAR CURSOS
     * ========================================================
     */
    public function listar(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                titulo,
                descripcion,
                nivel_dificultad,
                duracion,
                numero_modulos,
                porcentaje_aprobacion,
                estado,
                fecha_creacion
            FROM cursos
            ORDER BY fecha_creacion DESC, id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ========================================================
     * VERIFICAR SI EXISTE UN CURSO
     * ========================================================
     */
    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM cursos
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        return (bool) $stmt->fetch();
    }

    /**
     * ========================================================
     * VERIFICAR SI YA EXISTE UN TÍTULO
     * ========================================================
     *
     * $excluirId se utiliza cuando estamos editando un curso.
     * Permite comprobar que otro curso no tenga el mismo título.
     *
     * ========================================================
     */
    public function tituloExiste(
        string $titulo,
        int $excluirId = 0
    ): bool {

        $sql = "
            SELECT id
            FROM cursos
            WHERE titulo = ?
        ";

        $params = [$titulo];

        if ($excluirId > 0) {

            $sql .= "
                AND id <> ?
            ";

            $params[] = $excluirId;
        }

        $sql .= "
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    /**
     * ========================================================
     * OBTENER CURSO POR ID
     * ========================================================
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                titulo,
                descripcion,
                nivel_dificultad,
                duracion,
                numero_modulos,
                porcentaje_aprobacion,
                estado,
                fecha_creacion
            FROM cursos
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        return $curso ?: null;
    }

    /**
     * ========================================================
     * CREAR CURSO
     * ========================================================
     */
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
                estado
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
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

    /**
     * ========================================================
     * ACTUALIZAR CURSO
     * ========================================================
     */
    public function actualizar(
        int $id,
        array $datos
    ): void {

        $stmt = $this->db->prepare("
            UPDATE cursos
            SET
                titulo = ?,
                descripcion = ?,
                nivel_dificultad = ?,
                duracion = ?,
                numero_modulos = ?,
                porcentaje_aprobacion = ?,
                estado = ?
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

    /**
     * ========================================================
     * CAMBIAR ESTADO
     * ========================================================
     */
    public function cambiarEstado(
        int $id,
        string $estado
    ): void {

        $stmt = $this->db->prepare("
            UPDATE cursos
            SET estado = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $estado,
            $id
        ]);
    }

    /**
     * ========================================================
     * ELIMINAR CURSO
     * ========================================================
     */
    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM cursos
            WHERE id = ?
        ");

        $stmt->execute([$id]);
    }
}