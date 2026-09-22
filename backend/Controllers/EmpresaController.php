<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Controllers / EmpresaController.php
 * CRUD de empresas, protegido para administradores.
 * ============================================================
 */

class EmpresaController
{
    private EmpresaModel $empresas;

    public function __construct()
    {
        Auth::requireAdmin("No tienes permisos para administrar empresas.");

        $this->empresas = new EmpresaModel();
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
        $search = Request::query("search", "");
        $estado = Request::query("estado", "");
        $tipo = Request::query("tipo", "");

        if ($estado !== "" && $estado !== "todos" && !in_array($estado, ["activo", "inactivo"], true)) {
            Response::json(false, "Estado inválido.", [], 400);
        }

        if ($tipo !== "" && $tipo !== "todos" && !in_array($tipo, ["privada", "publica", "mixta"], true)) {
            Response::json(false, "Tipo de empresa inválido.", [], 400);
        }

        try {

            $empresas = $this->empresas->listar($search, $estado, $tipo);

            Response::json(true, "Empresas obtenidas correctamente.", ["empresas" => $empresas]);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible obtener las empresas.", [], 500);
        }
    }

    /** Valida los campos comunes de creación/edición. Corta la ejecución si algo falla. */
    private function validarDatos(string $nombre, string $nit, string $correo, string $tipo, string $estado): void
    {
        if ($nombre === "") {
            Response::json(false, "El nombre de la empresa es obligatorio.", [], 400);
        }

        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 150) {
            Response::json(false, "El nombre de la empresa debe tener entre 2 y 150 caracteres.", [], 400);
        }

        if ($nit === "") {
            Response::json(false, "El NIT es obligatorio.", [], 400);
        }

        if (mb_strlen($nit) > 30) {
            Response::json(false, "El NIT no puede superar los 30 caracteres.", [], 400);
        }

        if ($correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Response::json(false, "El correo electrónico no es válido.", [], 400);
        }

        if (!in_array($tipo, ["privada", "publica", "mixta"], true)) {
            Response::json(false, "El tipo de empresa no es válido.", [], 400);
        }

        if (!in_array($estado, ["activo", "inactivo"], true)) {
            Response::json(false, "El estado de la empresa no es válido.", [], 400);
        }
    }

    private function store(): void
    {
        $nombre = trim(Request::input("nombre", ""));
        $nit = trim(Request::input("nit", ""));
        $correo = strtolower(trim(Request::input("correo", "")));
        $telefono = trim(Request::input("telefono", ""));
        $direccion = trim(Request::input("direccion", ""));
        $tipo = trim(Request::input("tipo", "privada"));
        $estado = trim(Request::input("estado", "activo"));

        $this->validarDatos($nombre, $nit, $correo, $tipo, $estado);

        try {

            if ($this->empresas->nitExiste($nit)) {
                Response::json(false, "Ya existe una empresa registrada con ese NIT.", [], 409);
            }

            $id = $this->empresas->crear([
                "nombre" => $nombre,
                "nit" => $nit,
                "correo" => $correo,
                "telefono" => $telefono,
                "direccion" => $direccion,
                "tipo" => $tipo,
                "estado" => $estado
            ]);

            Response::json(true, "Empresa creada correctamente.", ["id" => $id], 201);

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                Response::json(false, "Ya existe una empresa con ese NIT.", [], 409);
            }

            Response::json(false, "No fue posible crear la empresa.", [], 500);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible crear la empresa.", [], 500);
        }
    }

    private function update(): void
    {
        $id = (int) Request::input("id", 0);
        $nombre = trim(Request::input("nombre", ""));
        $nit = trim(Request::input("nit", ""));
        $correo = strtolower(trim(Request::input("correo", "")));
        $telefono = trim(Request::input("telefono", ""));
        $direccion = trim(Request::input("direccion", ""));
        $tipo = trim(Request::input("tipo", "privada"));
        $estado = trim(Request::input("estado", "activo"));

        if ($id <= 0) {
            Response::json(false, "ID de empresa inválido.", [], 400);
        }

        $this->validarDatos($nombre, $nit, $correo, $tipo, $estado);

        try {

            if (!$this->empresas->existe($id)) {
                Response::json(false, "La empresa no existe.", [], 404);
            }

            if ($this->empresas->nitExiste($nit, $id)) {
                Response::json(false, "Ya existe otra empresa registrada con ese NIT.", [], 409);
            }

            $this->empresas->actualizar($id, [
                "nombre" => $nombre,
                "nit" => $nit,
                "correo" => $correo,
                "telefono" => $telefono,
                "direccion" => $direccion,
                "tipo" => $tipo,
                "estado" => $estado
            ]);

            Response::json(true, "Empresa actualizada correctamente.");

        } catch (Throwable $e) {

            Response::json(false, "No fue posible actualizar la empresa.", [], 500);
        }
    }

    private function patchEstado(): void
    {
        $id = (int) Request::input("id", 0);
        $estado = trim(Request::input("estado", ""));

        if ($id <= 0) {
            Response::json(false, "ID de empresa inválido.", [], 400);
        }

        if (!in_array($estado, ["activo", "inactivo"], true)) {
            Response::json(false, "Estado inválido.", [], 400);
        }

        try {

            if (!$this->empresas->existe($id)) {
                Response::json(false, "La empresa no existe.", [], 404);
            }

            $this->empresas->cambiarEstado($id, $estado);

            Response::json(
                true,
                $estado === "activo" ? "Empresa activada correctamente." : "Empresa desactivada correctamente."
            );

        } catch (Throwable $e) {

            Response::json(false, "No fue posible cambiar el estado de la empresa.", [], 500);
        }
    }

    private function destroy(): void
    {
        $id = (int) Request::input("id", 0);

        if ($id <= 0) {
            Response::json(false, "ID de empresa inválido.", [], 400);
        }

        try {

            if (!$this->empresas->existe($id)) {
                Response::json(false, "La empresa no existe.", [], 404);
            }

            $this->empresas->eliminar($id);

            Response::json(true, "Empresa eliminada correctamente.");

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                Response::json(
                    false,
                    "No se puede eliminar la empresa porque tiene registros relacionados. Se recomienda desactivarla.",
                    [],
                    409
                );
            }

            Response::json(false, "No fue posible eliminar la empresa.", [], 500);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible eliminar la empresa.", [], 500);
        }
    }
}
