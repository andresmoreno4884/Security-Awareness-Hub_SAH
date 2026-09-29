<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / UsuarioModel.php
 *
 * MODELO (M) DEL PATRÓN MVC
 *
 * Responsabilidad:
 * - Comunicarse con la base de datos.
 * - Ejecutar consultas relacionadas con usuarios.
 * - Utilizar consultas preparadas mediante PDO.
 *
 * El modelo NO:
 * - Valida permisos.
 * - Genera respuestas JSON.
 * - Lee directamente la petición HTTP.
 *
 * Roles actuales:
 * - admin
 * - admin_empresa
 * - empleado
 * ============================================================
 */

class UsuarioModel
{
    private PDO $db;

    /**
     * Constructor
     */
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
     * - Buscar por nombres.
     * - Buscar por apellidos.
     * - Buscar por correo.
     * - Filtrar por estado.
     *
     * También devuelve empresa_id para que el controlador
     * y el frontend conozcan la empresa asociada.
     */
    public function listar(
        string $search = "",
        string $estado = ""
    ): array {

        $sql = "
            SELECT
                id,
                nombres,
                apellidos,
                correo,
                rol,
                empresa_id,
                estado,
                fecha_registro
            FROM usuarios
            WHERE 1 = 1
        ";

        $params = [];

        /**
         * ----------------------------------------------------
         * BÚSQUEDA
         * ----------------------------------------------------
         */
        if ($search !== "") {

            $sql .= "
                AND (
                    nombres LIKE :search
                    OR apellidos LIKE :search
                    OR correo LIKE :search
                )
            ";

            $params[":search"] = "%" . $search . "%";
        }

        /**
         * ----------------------------------------------------
         * FILTRO DE ESTADO
         * ----------------------------------------------------
         */
        if (
            $estado !== ""
            && in_array(
                $estado,
                ["activo", "inactivo"],
                true
            )
        ) {

            $sql .= "
                AND estado = :estado
            ";

            $params[":estado"] = $estado;
        }

        /**
         * Usuarios más recientes primero.
         */
        $sql .= " ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * ========================================================
     * BUSCAR POR CORREO
     * ========================================================
     *
     * Utilizado principalmente durante el inicio de sesión.
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
     * BUSCAR POR ID
     * ========================================================
     */
    public function buscarPorId(
        int $id
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                id,
                nombres,
                apellidos,
                correo,
                rol,
                empresa_id,
                estado,
                fecha_registro
            FROM usuarios
            WHERE id = :id
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
     * VERIFICAR CORREO EXISTENTE
     * ========================================================
     *
     * $excluirId permite utilizar este método al editar
     * un usuario sin considerar su propio correo como duplicado.
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

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    /**
     * ========================================================
     * LISTAR EMPRESAS
     * ========================================================
     *
     * Se utiliza para validar que la empresa enviada desde
     * UsuarioController realmente exista.
     *
     * Se asume que la tabla empresas posee:
     * - id
     * - nombre
     * - estado
     */
    public function listarEmpresas(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                nombre,
                estado
            FROM empresas
            WHERE estado = 'activo'
            ORDER BY nombre ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * ========================================================
     * CREAR USUARIO
     * ========================================================
     *
     * La contraseña se almacena utilizando password_hash().
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
     *
     * Utilizado por el flujo de recuperación de contraseña.
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