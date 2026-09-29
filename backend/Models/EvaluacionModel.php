<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / EvaluacionModel.php
 *
 * Acceso a datos de "evaluaciones", junto con sus tablas
 * hijas "preguntas" y "opciones".
 * ============================================================
 */

class EvaluacionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Lista resumida de evaluaciones (sin preguntas/opciones),
     * con el total de preguntas y el título del curso asociado.
     */
    public function listar(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.id, e.curso_id, c.titulo AS curso_titulo, e.titulo,
                e.porcentaje_aprobacion, e.estado, e.fecha_creacion,
                (SELECT COUNT(*) FROM preguntas p WHERE p.evaluacion_id = e.id) AS total_preguntas
            FROM evaluaciones e
            INNER JOIN cursos c ON c.id = e.curso_id
            ORDER BY e.fecha_creacion DESC, e.id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtiene una evaluación completa, con sus preguntas y las
     * opciones de cada pregunta.
     */
    public function buscarCompleta(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.id, e.curso_id, c.titulo AS curso_titulo, e.titulo,
                e.descripcion, e.porcentaje_aprobacion, e.estado, e.fecha_creacion
            FROM evaluaciones e
            INNER JOIN cursos c ON c.id = e.curso_id
            WHERE e.id = :id
            LIMIT 1
        ");

        $stmt->execute([":id" => $id]);

        $evaluacion = $stmt->fetch();

        if (!$evaluacion) {
            return null;
        }

        $stmtPreguntas = $this->db->prepare("
            SELECT id, enunciado, orden
            FROM preguntas
            WHERE evaluacion_id = :evaluacion_id
            ORDER BY orden ASC, id ASC
        ");

        $stmtPreguntas->execute([":evaluacion_id" => $id]);

        $preguntas = $stmtPreguntas->fetchAll();

        $stmtOpciones = $this->db->prepare("
            SELECT id, texto, es_correcta, orden
            FROM opciones
            WHERE pregunta_id = :pregunta_id
            ORDER BY orden ASC, id ASC
        ");

        foreach ($preguntas as &$pregunta) {
            $stmtOpciones->execute([":pregunta_id" => $pregunta["id"]]);
            $pregunta["opciones"] = $stmtOpciones->fetchAll();
        }
        unset($pregunta);

        $evaluacion["preguntas"] = $preguntas;

        return $evaluacion;
    }

    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM evaluaciones WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);

        return (bool) $stmt->fetch();
    }

    /**
     * Crea una evaluación junto con sus preguntas y opciones,
     * todo dentro de una única transacción.
     */
    public function crear(int $cursoId, string $titulo, string $descripcion, int $porcentaje, array $preguntas): int
    {
        $this->db->beginTransaction();

        try {

            $stmtEval = $this->db->prepare("
                INSERT INTO evaluaciones (curso_id, titulo, descripcion, porcentaje_aprobacion, estado)
                VALUES (?, ?, ?, ?, 'activo')
            ");

            $stmtEval->execute([$cursoId, $titulo, $descripcion, $porcentaje]);

            $evaluacionId = (int) $this->db->lastInsertId();

            $this->guardarPreguntas($evaluacionId, $preguntas);

            $this->db->commit();

            return $evaluacionId;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Reemplaza los datos básicos de una evaluación y toda su
     * lista de preguntas/opciones (borra las anteriores y crea
     * las nuevas), dentro de una transacción.
     */
    public function actualizar(int $id, int $cursoId, string $titulo, string $descripcion, int $porcentaje, array $preguntas): void
    {
        $this->db->beginTransaction();

        try {

            $stmtUpdate = $this->db->prepare("
                UPDATE evaluaciones
                SET curso_id = ?, titulo = ?, descripcion = ?, porcentaje_aprobacion = ?
                WHERE id = ?
            ");

            $stmtUpdate->execute([$cursoId, $titulo, $descripcion, $porcentaje, $id]);

            // Las opciones se borran solas por ON DELETE CASCADE.
            $stmtDelete = $this->db->prepare("DELETE FROM preguntas WHERE evaluacion_id = ?");
            $stmtDelete->execute([$id]);

            $this->guardarPreguntas($id, $preguntas);

            $this->db->commit();

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Inserta el arreglo de preguntas (con sus opciones)
     * asociado a una evaluación. Método de apoyo interno.
     */
    private function guardarPreguntas(int $evaluacionId, array $preguntas): void
    {
        $stmtPregunta = $this->db->prepare("
            INSERT INTO preguntas (evaluacion_id, enunciado, orden)
            VALUES (?, ?, ?)
        ");

        $stmtOpcion = $this->db->prepare("
            INSERT INTO opciones (pregunta_id, texto, es_correcta, orden)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($preguntas as $index => $pregunta) {

            $stmtPregunta->execute([
                $evaluacionId,
                trim($pregunta["enunciado"]),
                $index + 1
            ]);

            $preguntaId = (int) $this->db->lastInsertId();

            foreach ($pregunta["opciones"] as $opIndex => $opcion) {

                $esCorrecta = (
                    isset($opcion["es_correcta"]) &&
                    (int) $opcion["es_correcta"] === 1
                ) ? 1 : 0;

                $stmtOpcion->execute([
                    $preguntaId,
                    trim($opcion["texto"]),
                    $esCorrecta,
                    $opIndex + 1
                ]);
            }
        }
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare("UPDATE evaluaciones SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM evaluaciones WHERE id = ?");
        $stmt->execute([$id]);
    }
}
