<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Controllers / EvaluacionController.php
 *
 * CRUD de evaluaciones (con preguntas y opciones anidadas),
 * protegido para administradores.
 * ============================================================
 */

class EvaluacionController
{
    private EvaluacionModel $evaluaciones;
    private CursoModel $cursos;

    public function __construct()
    {
        Auth::requireAdmin("No tienes permisos para administrar evaluaciones.");

        $this->evaluaciones = new EvaluacionModel();
        $this->cursos = new CursoModel();
    }

    public function handle(): void
    {
        switch (Request::method()) {
            case "GET":
                $this->show();
                break;
            case "POST":
                $this->store();
                break;
            case "PUT":
                $this->update();
                break;
            case "PATCH":
                $this->patchEstado();
                break;
            case "DELETE":
                $this->destroy();
                break;
            default:
                Response::json(false, "Método HTTP no permitido.", [], 405);
        }
    }

    /**
     * GET /Api/evaluaciones.php          -> lista resumida
     * GET /Api/evaluaciones.php?id=5     -> evaluación completa
     */
    private function show(): void
    {
        $id = (int) Request::query("id", 0);

        try {

            if ($id > 0) {

                $evaluacion = $this->evaluaciones->buscarCompleta($id);

                if (!$evaluacion) {
                    Response::json(false, "La evaluación no existe.", [], 404);
                }

                Response::json(true, "Evaluación obtenida correctamente.", ["evaluacion" => $evaluacion]);
            }

            $evaluaciones = $this->evaluaciones->listar();

            Response::json(true, "Evaluaciones obtenidas correctamente.", ["evaluaciones" => $evaluaciones]);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible obtener las evaluaciones.", [], 500);
        }
    }

    /**
     * Valida que cada pregunta tenga enunciado, al menos 2 opciones
     * y exactamente una opción marcada como correcta.
     * Devuelve true si todo está bien, o un mensaje de error.
     */
    private function validarPreguntas($preguntas)
    {
        if (!is_array($preguntas) || count($preguntas) < 1) {
            return "La evaluación debe tener al menos una pregunta.";
        }

        foreach ($preguntas as $index => $pregunta) {

            $numero = $index + 1;
            $enunciado = trim($pregunta["enunciado"] ?? "");

            if ($enunciado === "") {
                return "La pregunta {$numero} no tiene enunciado.";
            }

            $opciones = $pregunta["opciones"] ?? [];

            if (!is_array($opciones) || count($opciones) < 2) {
                return "La pregunta {$numero} debe tener al menos 2 opciones.";
            }

            $correctas = 0;

            foreach ($opciones as $opcion) {

                $texto = trim($opcion["texto"] ?? "");

                if ($texto === "") {
                    return "Una opción de la pregunta {$numero} está vacía.";
                }

                if (isset($opcion["es_correcta"]) && (int) $opcion["es_correcta"] === 1) {
                    $correctas++;
                }
            }

            if ($correctas !== 1) {
                return "La pregunta {$numero} debe tener exactamente una opción correcta (tiene {$correctas}).";
            }
        }

        return true;
    }

    /** Valida los campos básicos comunes a crear/editar. */
    private function validarDatosBasicos(int $cursoId, string $titulo, string $descripcion, int $porcentaje, $preguntas): void
    {
        if ($cursoId <= 0) {
            Response::json(false, "Debes seleccionar un curso.", [], 400);
        }

        if ($titulo === "") {
            Response::json(false, "El título de la evaluación es obligatorio.", [], 400);
        }

        if ($descripcion === "") {
            Response::json(false, "La descripción es obligatoria.", [], 400);
        }

        if ($porcentaje < 0 || $porcentaje > 100) {
            Response::json(false, "El porcentaje de aprobación debe estar entre 0 y 100.", [], 400);
        }

        $errorPreguntas = $this->validarPreguntas($preguntas);

        if ($errorPreguntas !== true) {
            Response::json(false, $errorPreguntas, [], 400);
        }
    }

    private function store(): void
    {
        $cursoId = (int) Request::input("curso_id", 0);
        $titulo = trim(Request::input("titulo", ""));
        $descripcion = trim(Request::input("descripcion", ""));
        $porcentaje = (int) Request::input("porcentaje_aprobacion", 70);
        $preguntas = Request::input("preguntas", []);

        $this->validarDatosBasicos($cursoId, $titulo, $descripcion, $porcentaje, $preguntas);

        try {

            if (!$this->cursos->existe($cursoId)) {
                Response::json(false, "El curso seleccionado no existe.", [], 404);
            }

            $id = $this->evaluaciones->crear($cursoId, $titulo, $descripcion, $porcentaje, $preguntas);

            Response::json(true, "Evaluación creada correctamente.", ["id" => $id], 201);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible crear la evaluación.", [], 500);
        }
    }

    private function update(): void
    {
        $id = (int) Request::input("id", 0);
        $cursoId = (int) Request::input("curso_id", 0);
        $titulo = trim(Request::input("titulo", ""));
        $descripcion = trim(Request::input("descripcion", ""));
        $porcentaje = (int) Request::input("porcentaje_aprobacion", 70);
        $preguntas = Request::input("preguntas", []);

        if ($id <= 0) {
            Response::json(false, "ID de evaluación inválido.", [], 400);
        }

        $this->validarDatosBasicos($cursoId, $titulo, $descripcion, $porcentaje, $preguntas);

        try {

            if (!$this->evaluaciones->existe($id)) {
                Response::json(false, "La evaluación no existe.", [], 404);
            }

            $this->evaluaciones->actualizar($id, $cursoId, $titulo, $descripcion, $porcentaje, $preguntas);

            Response::json(true, "Evaluación actualizada correctamente.");

        } catch (Throwable $e) {

            Response::json(false, "No fue posible actualizar la evaluación.", [], 500);
        }
    }

    private function patchEstado(): void
    {
        $id = (int) Request::input("id", 0);
        $estadoNum = (int) Request::input("estado", -1);

        if ($id <= 0) {
            Response::json(false, "ID de evaluación inválido.", [], 400);
        }

        if ($estadoNum !== 0 && $estadoNum !== 1) {
            Response::json(false, "Estado inválido.", [], 400);
        }

        $estadoTexto = $estadoNum === 1 ? "activo" : "inactivo";

        try {

            if (!$this->evaluaciones->existe($id)) {
                Response::json(false, "La evaluación no existe.", [], 404);
            }

            $this->evaluaciones->cambiarEstado($id, $estadoTexto);

            Response::json(
                true,
                $estadoNum === 1 ? "Evaluación activada correctamente." : "Evaluación desactivada correctamente."
            );

        } catch (Throwable $e) {

            Response::json(false, "No fue posible cambiar el estado.", [], 500);
        }
    }

    private function destroy(): void
    {
        $id = (int) Request::input("id", 0);

        if ($id <= 0) {
            Response::json(false, "ID de evaluación inválido.", [], 400);
        }

        try {

            if (!$this->evaluaciones->existe($id)) {
                Response::json(false, "La evaluación no existe.", [], 404);
            }

            $this->evaluaciones->eliminar($id);

            Response::json(true, "Evaluación eliminada correctamente.");

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                Response::json(
                    false,
                    "No se puede eliminar esta evaluación porque tiene información relacionada. Puedes desactivarla.",
                    [],
                    409
                );
            }

            Response::json(false, "No fue posible eliminar la evaluación.", [], 500);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible eliminar la evaluación.", [], 500);
        }
    }
}
