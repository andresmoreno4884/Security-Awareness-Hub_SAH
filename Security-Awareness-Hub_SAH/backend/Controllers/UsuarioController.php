<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Controllers / UsuarioController.php
 *
 * CRUD de usuarios, protegido para administradores.
 * Un solo método público, handle(), reparte el trabajo según
 * el verbo HTTP (GET, POST, PUT, PATCH, DELETE).
 * ============================================================
 */

class UsuarioController
{
    private UsuarioModel $usuarios;

    public function __construct()
    {
        Auth::requireAdmin("No tienes permisos para administrar usuarios.");

        $this->usuarios = new UsuarioModel();
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
                Response::json(false, "Método no permitido.", [], 405);
        }
    }

    /** GET: lista / busca usuarios */
    private function index(): void
    {
        $search = Request::query("search", "");
        $estado = Request::query("estado", "");

        try {

            $usuarios = $this->usuarios->listar($search, $estado);

            Response::json(true, "", [
                "usuarios" => $usuarios,
                "total" => count($usuarios)
            ]);

        } catch (Throwable $e) {

            Response::json(false, "Error al consultar los usuarios.", [], 500);
        }
    }

    /** POST: crea un usuario */
    private function store(): void
    {
        $nombres = trim(Request::input("nombres", ""));
        $apellidos = trim(Request::input("apellidos", ""));
        $correo = strtolower(trim(Request::input("correo", "")));
        $password = Request::input("password", "");
        $rol = trim(Request::input("rol", "usuario"));
        $estado = trim(Request::input("estado", "activo"));

        if ($nombres === "" || $apellidos === "" || $correo === "" || $password === "") {
            Response::json(false, "Todos los campos obligatorios deben completarse.", [], 400);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Response::json(false, "El correo electrónico no es válido.", [], 400);
        }

        if (strlen($password) < 6) {
            Response::json(false, "La contraseña debe tener mínimo 6 caracteres.", [], 400);
        }

        if (!in_array($rol, ["usuario", "admin"], true)) {
            Response::json(false, "El rol seleccionado no es válido.", [], 400);
        }

        if (!in_array($estado, ["activo", "inactivo"], true)) {
            Response::json(false, "El estado seleccionado no es válido.", [], 400);
        }

        try {

            if ($this->usuarios->correoExiste($correo)) {
                Response::json(false, "Ya existe un usuario con ese correo.", [], 409);
            }

            $id = $this->usuarios->crear([
                "nombres" => $nombres,
                "apellidos" => $apellidos,
                "correo" => $correo,
                "password" => $password,
                "rol" => $rol,
                "estado" => $estado
            ]);

            Response::json(true, "Usuario creado correctamente.", [
                "usuario" => [
                    "id" => $id,
                    "nombres" => $nombres,
                    "apellidos" => $apellidos,
                    "correo" => $correo,
                    "rol" => $rol,
                    "estado" => $estado
                ]
            ]);

        } catch (Throwable $e) {

            Response::json(false, "No fue posible crear el usuario.", [], 500);
        }
    }

    /** PUT: edita un usuario */
    private function update(): void
    {
        $id = (int) Request::input("id", 0);
        $nombres = trim(Request::input("nombres", ""));
        $apellidos = trim(Request::input("apellidos", ""));
        $correo = strtolower(trim(Request::input("correo", "")));
        $rol = trim(Request::input("rol", ""));
        $estado = trim(Request::input("estado", ""));

        if ($id <= 0 || $nombres === "" || $apellidos === "" || $correo === "") {
            Response::json(false, "Los datos del usuario son incompletos.", [], 400);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Response::json(false, "El correo electrónico no es válido.", [], 400);
        }

        if (!in_array($rol, ["usuario", "admin"], true)) {
            Response::json(false, "El rol no es válido.", [], 400);
        }

        if (!in_array($estado, ["activo", "inactivo"], true)) {
            Response::json(false, "El estado no es válido.", [], 400);
        }

        try {

            if (!$this->usuarios->buscarPorId($id)) {
                Response::json(false, "El usuario no existe.", [], 404);
            }

            if ($this->usuarios->correoExiste($correo, $id)) {
                Response::json(false, "El correo ya pertenece a otro usuario.", [], 409);
            }

            $this->usuarios->actualizar($id, [
                "nombres" => $nombres,
                "apellidos" => $apellidos,
                "correo" => $correo,
                "rol" => $rol,
                "estado" => $estado
            ]);

            Response::json(true, "Usuario actualizado correctamente.");

        } catch (Throwable $e) {

            Response::json(false, "No fue posible actualizar el usuario.", [], 500);
        }
    }

    /** PATCH: activa / desactiva un usuario */
    private function patchEstado(): void
    {
        $id = (int) Request::input("id", 0);
        $estado = trim(Request::input("estado", ""));

        if ($id <= 0) {
            Response::json(false, "ID de usuario inválido.", [], 400);
        }

        if (!in_array($estado, ["activo", "inactivo"], true)) {
            Response::json(false, "Estado inválido.", [], 400);
        }

        if ($id === Auth::userId() && $estado === "inactivo") {
            Response::json(false, "No puedes desactivar tu propia cuenta.", [], 400);
        }

        try {

            $filas = $this->usuarios->cambiarEstado($id, $estado);

            if ($filas === 0) {
                Response::json(false, "No se encontró el usuario.", [], 404);
            }

            Response::json(
                true,
                $estado === "activo" ? "Usuario activado correctamente." : "Usuario desactivado correctamente.",
                ["estado" => $estado]
            );

        } catch (Throwable $e) {

            Response::json(false, "No fue posible cambiar el estado del usuario.", [], 500);
        }
    }

    /** DELETE: elimina un usuario */
    private function destroy(): void
    {
        $id = (int) Request::input("id", 0);

        if ($id <= 0) {
            Response::json(false, "ID de usuario inválido.", [], 400);
        }

        if ($id === Auth::userId()) {
            Response::json(false, "No puedes eliminar tu propia cuenta.", [], 400);
        }

        try {

            $filas = $this->usuarios->eliminar($id);

            if ($filas === 0) {
                Response::json(false, "El usuario no existe.", [], 404);
            }

            Response::json(true, "Usuario eliminado correctamente.");

        } catch (Throwable $e) {

            Response::json(false, "No fue posible eliminar el usuario.", [], 500);
        }
    }
}
