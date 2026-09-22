<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Controllers / CursoController.php
 * CRUD de cursos, protegido para administradores.
 *
 * NOTA DE CORRECCIÓN: en la versión anterior del proyecto,
 * "estado" se guardaba forzando un (int) sobre "activo"/"inactivo",
 * lo que siempre daba 0 y dañaba la columna ENUM. Aquí se maneja
 * "estado" como texto ('activo' | 'inactivo'), igual que en el
 * resto de módulos y que en la base de datos.
 * ============================================================
 */

class CursoController
{
    private CursoModel $cursos;

    public function __construct()
    {
        Auth::requireAdmin("No tienes permisos para administrar cursos.");

        $this->cursos = new CursoModel();
    }

    public function handle(): void
    {
        switch (Request::method()) {
            case "GET":
                $this->index();
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

    private function index(): void
    {
        try {

            $cursos = $this->cursos->listar();

            Response::json(true, "Cursos obtenidos correctamente.", ["cursos" => $cursos]);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible obtener los cursos.", [], 500);
        }
    }

    /** Lee y valida los campos comunes de creación/edición. */
    private function datosDelFormulario(): array
    {
        $titulo = trim(Request::input("titulo", ""));
        $descripcion = trim(Request::input("descripcion", ""));
        $nivel = trim(Request::input("nivel_dificultad", "basico"));
        $duracion = trim((string) Request::input("duracion", ""));
        $numeroModulos = (int) Request::input("numero_modulos", 1);
        $porcentaje = (int) Request::input("porcentaje_aprobacion", 70);
        $estado = trim(Request::input("estado", "activo"));

        if ($titulo === "") {
            Response::json(false, "El título del curso es obligatorio.", [], 400);
        }

        if ($descripcion === "") {
            Response::json(false, "La descripción del curso es obligatoria.", [], 400);
        }

        if ($duracion === "") {
            Response::json(false, "La duración del curso es obligatoria.", [], 400);
        }

        if ($numeroModulos < 1) {
            Response::json(false, "El número de módulos debe ser mayor que 0.", [], 400);
        }

        if ($porcentaje < 0 || $porcentaje > 100) {
            Response::json(false, "El porcentaje de aprobación debe estar entre 0 y 100.", [], 400);
        }

        if (!in_array($estado, ["activo", "inactivo"], true)) {
            $estado = "activo";
        }

        return [
            "titulo" => $titulo,
            "descripcion" => $descripcion,
            "nivel_dificultad" => $nivel,
            "duracion" => $duracion,
            "numero_modulos" => $numeroModulos,
            "porcentaje_aprobacion" => $porcentaje,
            "estado" => $estado
        ];
    }

    private function store(): void
    {
        $datos = $this->datosDelFormulario();

        try {

            if ($this->cursos->tituloExiste($datos["titulo"])) {
                Response::json(false, "Ya existe un curso con ese título.", [], 409);
            }

            $id = $this->cursos->crear($datos);

            Response::json(true, "Curso creado correctamente.", ["id" => $id], 201);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible crear el curso.", [], 500);
        }
    }

    private function update(): void
    {
        $id = (int) Request::input("id", 0);

        if ($id <= 0) {
            Response::json(false, "ID de curso inválido.", [], 400);
        }

        $datos = $this->datosDelFormulario();

        try {

            if (!$this->cursos->existe($id)) {
                Response::json(false, "El curso no existe.", [], 404);
            }

            if ($this->cursos->tituloExiste($datos["titulo"], $id)) {
                Response::json(false, "Ya existe otro curso con ese título.", [], 409);
            }

            $this->cursos->actualizar($id, $datos);

            Response::json(true, "Curso actualizado correctamente.");

        } catch (Throwable $e) {

            Response::json(false, "No fue posible actualizar el curso.", [], 500);
        }
    }

    private function patchEstado(): void
    {
        $id = (int) Request::input("id", 0);
        $estado = trim(Request::input("estado", ""));

        if ($id <= 0) {
            Response::json(false, "ID de curso inválido.", [], 400);
        }

        if (!in_array($estado, ["activo", "inactivo"], true)) {
            Response::json(false, "Estado inválido.", [], 400);
        }

        try {

            if (!$this->cursos->existe($id)) {
                Response::json(false, "El curso no existe.", [], 404);
            }

            $this->cursos->cambiarEstado($id, $estado);

            Response::json(
                true,
                $estado === "activo" ? "Curso activado correctamente." : "Curso desactivado correctamente."
            );

        } catch (Throwable $e) {

            Response::json(false, "No fue posible cambiar el estado del curso.", [], 500);
        }
    }

    private function destroy(): void
    {
        $id = (int) Request::input("id", 0);

        if ($id <= 0) {
            Response::json(false, "ID de curso inválido.", [], 400);
        }

        try {

            if (!$this->cursos->existe($id)) {
                Response::json(false, "El curso no existe.", [], 404);
            }

            $this->cursos->eliminar($id);

            Response::json(true, "Curso eliminado correctamente.");

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                Response::json(
                    false,
                    "No se puede eliminar este curso porque tiene información relacionada. Puedes desactivarlo.",
                    [],
                    409
                );
            }

            Response::json(false, "No fue posible eliminar el curso.", [], 500);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible eliminar el curso.", [], 500);
        }
    }
}
