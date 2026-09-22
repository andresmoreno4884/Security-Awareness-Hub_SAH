<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / UsuarioModel.php
 * ============================================================
 *
 * Modelo de usuarios.
 *
 * Responsabilidad:
 * - Comunicación con la tabla usuarios mediante PDO.
 * - Consultas preparadas.
 * - Gestión de empleados, administradores de empresa
 *   y administradores generales.
 *
 * Roles:
 * - admin
 * - admin_empresa
 * - empleado
 *
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
     * ========================================================
     * LISTAR USUARIOS
     * ========================================================
     *
     * Permite:
     * - Buscar por nombre, apellido o correo.
     * - Filtrar por estado.
     * - Consultar todos los roles.
     * - Obtener el nombre de la empresa asociada.
     *
     * ========================================================
     */
    public function listar(
        string $search = "",
        string $estado = ""
    ): array {

        $sql = "
            SELECT
                u.id,
                u.nombres,
                u.apellidos,
                u.correo,
                u.rol,
                u.empresa_id,
                e.nombre AS empresa_nombre,
                u.estado,
                u.fecha_registro
            FROM usuarios u
            LEFT JOIN empresas e
                ON e.id = u.empresa_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($search !== "") {

            $sql .= "
                AND (
                    u.nombres LIKE :search
                    OR u.apellidos LIKE :search
                    OR u.correo LIKE :search
                    OR e.nombre LIKE :search
                )
            ";

            $params[":search"] = "%" . $search . "%";
        }

        if (
            $estado !== ""
            && in_array(
                $estado,
                ["activo", "inactivo"],
                true
            )
        ) {

            $sql .= "
                AND u.estado = :estado
            ";

            $params[":estado"] = $estado;
        }

        $sql .= "
            ORDER BY u.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * ========================================================
     * LISTAR EMPRESAS
     * ========================================================
     *
     * Obtiene las empresas activas para asociarlas
     * a empleados o administradores de empresa.
     *
     * ========================================================
     */
    public function listarEmpresas(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                nombre,
                estado
            FROM empresas
            WHERE estado = :estado
            ORDER BY nombre ASC
        ");

        $stmt->execute([
            ":estado" => "activo"
        ]);

        return $stmt->fetchAll();
    }

    /**
     * ========================================================
     * BUSCAR USUARIO POR CORREO
     * ========================================================
     */
    public function buscarPorCorreo(
        string $correo
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                id,
                nombres,
                apellidos,
                correo,
                password,
                rol,
                empresa_id,
                estado
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1
        ");

        $stmt->execute([
            ":correo" => $correo
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * ========================================================
     * BUSCAR USUARIO POR ID
     * ========================================================
     */
    public function buscarPorId(
        int $id
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.nombres,
                u.apellidos,
                u.correo,
                u.rol,
                u.empresa_id,
                e.nombre AS empresa_nombre,
                u.estado,
                u.fecha_registro
            FROM usuarios u
            LEFT JOIN empresas e
                ON e.id = u.empresa_id
            WHERE u.id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ":id" => $id
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * ========================================================
     * VERIFICAR CORREO
     * ========================================================
     */
    public function correoExiste(
        string $correo,
        int $excluirId = 0
    ): bool {

        $sql = "
            SELECT id
            FROM usuarios
            WHERE correo = :correo
        ";

        $params = [
            ":correo" => $correo
        ];

        if ($excluirId > 0) {

            $sql .= "
                AND id <> :id
            ";

            $params[":id"] = $excluirId;
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
     * CREAR USUARIO
     * ========================================================
     *
     * empresa_id puede ser NULL para:
     * - administrador general
     *
     * empresa_id debe tener valor para:
     * - admin_empresa
     * - empleado
     *
     * La validación de esta regla se realiza en la capa
     * de administración antes de llamar a este método.
     *
     * ========================================================
     */
    public function crear(
        array $datos
    ): int {

        $stmt = $this->db->prepare("
            INSERT INTO usuarios (
                nombres,
                apellidos,
                correo,
                password,
                rol,
                empresa_id,
                estado
            )
            VALUES (
                :nombres,
                :apellidos,
                :correo,
                :password,
                :rol,
                :empresa_id,
                :estado
            )
        ");

        $stmt->execute([

            ":nombres" => $datos["nombres"],

            ":apellidos" => $datos["apellidos"],

            ":correo" => $datos["correo"],

            ":password" => password_hash(
                $datos["password"],
                PASSWORD_DEFAULT
            ),

            ":rol" => $datos["rol"],

            ":empresa_id" => $datos["empresa_id"] ?? null,

            ":estado" => $datos["estado"]
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * ========================================================
     * ACTUALIZAR CONTRASEÑA POR CORREO
     * ========================================================
     */
    public function actualizarPasswordPorCorreo(
        string $correo,
        string $passwordNueva
    ): void {

        $stmt = $this->db->prepare("
            UPDATE usuarios
            SET password = :password
            WHERE correo = :correo
        ");

        $stmt->execute([

            ":password" => password_hash(
                $passwordNueva,
                PASSWORD_DEFAULT
            ),

            ":correo" => $correo
        ]);
    }

    /**
     * ========================================================
     * ACTUALIZAR USUARIO
     * ========================================================
     */
    public function actualizar(
        int $id,
        array $datos
    ): void {

        $stmt = $this->db->prepare("
            UPDATE usuarios
            SET
                nombres = :nombres,
                apellidos = :apellidos,
                correo = :correo,
                rol = :rol,
                empresa_id = :empresa_id,
                estado = :estado
            WHERE id = :id
        ");

        $stmt->execute([

            ":nombres" => $datos["nombres"],

            ":apellidos" => $datos["apellidos"],

            ":correo" => $datos["correo"],

            ":rol" => $datos["rol"],

            ":empresa_id" => $datos["empresa_id"] ?? null,

            ":estado" => $datos["estado"],

            ":id" => $id
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
    ): int {

        $stmt = $this->db->prepare("
            UPDATE usuarios
            SET estado = :estado
            WHERE id = :id
        ");

        $stmt->execute([
            ":estado" => $estado,
            ":id" => $id
        ]);

        return $stmt->rowCount();
    }

    /**
     * ========================================================
     * ELIMINAR USUARIO
     * ========================================================
     */
    public function eliminar(
        int $id
    ): int {

        $stmt = $this->db->prepare("
            DELETE FROM usuarios
            WHERE id = :id
        ");

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->rowCount();
    }
}