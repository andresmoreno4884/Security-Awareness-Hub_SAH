<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Models / EmpresaModel.php
 * Acceso a datos de la tabla "empresas".
 * ============================================================
 */

class EmpresaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listar(string $search = "", string $estado = "", string $tipo = ""): array
    {
        $sql = "
            SELECT id, nombre, nit, correo, telefono, direccion, tipo, estado, fecha_registro
            FROM empresas
            WHERE 1 = 1
        ";

        $params = [];

        if ($search !== "") {
            $sql .= " AND (nombre LIKE ? OR nit LIKE ? OR correo LIKE ? OR telefono LIKE ?) ";
            $like = "%" . $search . "%";
            array_push($params, $like, $like, $like, $like);
        }

        if ($estado !== "" && $estado !== "todos") {
            $sql .= " AND estado = ? ";
            $params[] = $estado;
        }

        if ($tipo !== "" && $tipo !== "todos") {
            $sql .= " AND tipo = ? ";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY fecha_registro DESC, id DESC ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function existe(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM empresas WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);

        return (bool) $stmt->fetch();
    }

    public function nitExiste(string $nit, int $excluirId = 0): bool
    {
        $sql = "SELECT id FROM empresas WHERE nit = ?";
        $params = [$nit];

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
            INSERT INTO empresas (nombre, nit, correo, telefono, direccion, tipo, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $datos["nombre"],
            $datos["nit"],
            $datos["correo"],
            $datos["telefono"] !== "" ? $datos["telefono"] : null,
            $datos["direccion"] !== "" ? $datos["direccion"] : null,
            $datos["tipo"],
            $datos["estado"]
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->db->prepare("
            UPDATE empresas
            SET nombre = ?, nit = ?, correo = ?, telefono = ?, direccion = ?, tipo = ?, estado = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $datos["nombre"],
            $datos["nit"],
            $datos["correo"],
            $datos["telefono"] !== "" ? $datos["telefono"] : null,
            $datos["direccion"] !== "" ? $datos["direccion"] : null,
            $datos["tipo"],
            $datos["estado"],
            $id
        ]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare("UPDATE empresas SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM empresas WHERE id = ?");
        $stmt->execute([$id]);
    }
}
