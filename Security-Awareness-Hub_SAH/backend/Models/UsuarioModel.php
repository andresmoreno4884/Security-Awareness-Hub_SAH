<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / UsuarioModel.php
 *
 * Esta es la "M" (MODELO) del patrón MVC.
 * Un modelo NO valida datos de entrada ni arma respuestas JSON:
 * su única responsabilidad es hablar con la base de datos
 * (tabla "usuarios") usando PDO con consultas preparadas.
 * ============================================================
 */

class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Lista usuarios con rol "usuario" (no administradores),
     * con búsqueda y filtro de estado opcionales.
     */
    public function listar(string $search = "", string $estado = ""): array
    {
        $sql = "
            SELECT id, nombres, apellidos, correo, rol, estado, fecha_registro
            FROM usuarios
            WHERE rol = 'usuario'
        ";

        $params = [];

        if ($search !== "") {
            $sql .= " AND (nombres LIKE :search OR apellidos LIKE :search OR correo LIKE :search) ";
            $params[":search"] = "%" . $search . "%";
        }

        if ($estado !== "" && in_array($estado, ["activo", "inactivo"], true)) {
            $sql .= " AND estado = :estado ";
            $params[":estado"] = $estado;
        }

        $sql .= " ORDER BY id DESC ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Busca un usuario por su correo electrónico (usado en login).
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, nombres, apellidos, correo, password, rol, estado
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1
        ");

        $stmt->execute([":correo" => $correo]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * Busca un usuario por su ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([":id" => $id]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * ¿Ya existe un usuario con ese correo? (opcionalmente
     * excluyendo un ID, útil al editar).
     */
    public function correoExiste(string $correo, int $excluirId = 0): bool
    {
        $sql = "SELECT id FROM usuarios WHERE correo = :correo";
        $params = [":correo" => $correo];

        if ($excluirId > 0) {
            $sql .= " AND id <> :id";
            $params[":id"] = $excluirId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    /**
     * Crea un usuario y devuelve el ID generado.
     */
    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nombres, apellidos, correo, password, rol, estado)
            VALUES (:nombres, :apellidos, :correo, :password, :rol, :estado)
        ");

        $stmt->execute([
            ":nombres" => $datos["nombres"],
            ":apellidos" => $datos["apellidos"],
            ":correo" => $datos["correo"],
            ":password" => password_hash($datos["password"], PASSWORD_DEFAULT),
            ":rol" => $datos["rol"],
            ":estado" => $datos["estado"]
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Cambia la contraseña de un usuario a partir de su correo
     * (usado por el flujo de "olvidé mi contraseña").
     */
    public function actualizarPasswordPorCorreo(string $correo, string $passwordNueva): void
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET password = :password WHERE correo = :correo");

        $stmt->execute([
            ":password" => password_hash($passwordNueva, PASSWORD_DEFAULT),
            ":correo" => $correo
        ]);
    }

    /**
     * Actualiza los datos de un usuario existente.
     */
    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios
            SET nombres = :nombres, apellidos = :apellidos, correo = :correo,
                rol = :rol, estado = :estado
            WHERE id = :id
        ");

        $stmt->execute([
            ":nombres" => $datos["nombres"],
            ":apellidos" => $datos["apellidos"],
            ":correo" => $datos["correo"],
            ":rol" => $datos["rol"],
            ":estado" => $datos["estado"],
            ":id" => $id
        ]);
    }

    /**
     * Activa o desactiva un usuario. Devuelve la cantidad de
     * filas afectadas (0 si el usuario no existe).
     */
    public function cambiarEstado(int $id, string $estado): int
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET estado = :estado WHERE id = :id");
        $stmt->execute([":estado" => $estado, ":id" => $id]);

        return $stmt->rowCount();
    }

    /**
     * Elimina un usuario. Devuelve la cantidad de filas afectadas.
     */
    public function eliminar(int $id): int
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([":id" => $id]);

        return $stmt->rowCount();
    }
}