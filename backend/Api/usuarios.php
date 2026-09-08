<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * API - GESTIÓN DE USUARIOS
 * PHP + MYSQL + JSON
 * ============================================================
 */

header("Content-Type: application/json; charset=UTF-8");

session_start();

/**
 * ============================================================
 * CONEXIÓN
 * ============================================================
 */

require_once __DIR__ . "/../Config/database.php";

/**
 * ============================================================
 * VERIFICAR SESIÓN
 * ============================================================
 */

if (
    !isset($_SESSION["authenticated"]) ||
    $_SESSION["authenticated"] !== true
) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "No hay una sesión activa."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/**
 * ============================================================
 * VERIFICAR ROL ADMINISTRADOR
 * ============================================================
 */

if (
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "admin"
) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "No tienes permisos para administrar usuarios."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/**
 * ============================================================
 * CONECTAR MYSQL
 * ============================================================
 */

try {

    $pdo = Database::connect();

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No se pudo conectar con la base de datos.",
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/**
 * ============================================================
 * MÉTODO HTTP
 * ============================================================
 */

$method = $_SERVER["REQUEST_METHOD"];

/**
 * ============================================================
 * LEER JSON
 * ============================================================
 */

$input = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($input)) {
    $input = [];
}

/**
 * ============================================================
 * GET
 * LISTAR / BUSCAR USUARIOS
 * ============================================================
 */

if ($method === "GET") {

    try {

        $search = isset($_GET["search"])
            ? trim($_GET["search"])
            : "";

        $estado = isset($_GET["estado"])
            ? trim($_GET["estado"])
            : "";

        $rol = isset($_GET["rol"])
            ? trim($_GET["rol"])
            : "";

        $sql = "
            SELECT
                id,
                nombres,
                apellidos,
                correo,
                rol,
                estado,
                fecha_registro
            FROM usuarios
            WHERE rol = 'usuario'
        ";

        $params = [];

        /**
         * BUSCADOR
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
         * FILTRO ESTADO
         */

        if (
            $estado !== "" &&
            in_array($estado, ["activo", "inactivo"], true)
        ) {

            $sql .= "
                AND estado = :estado
            ";

            $params[":estado"] = $estado;
        }

        /**
         * FILTRO ROL
         */

        $sql .= "
            ORDER BY id DESC
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute($params);

        $usuarios = $stmt->fetchAll();

        echo json_encode([
            "success" => true,
            "usuarios" => $usuarios,
            "total" => count($usuarios)
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (PDOException $e) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Error al consultar los usuarios.",
            "error" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

/**
 * ============================================================
 * POST
 * CREAR USUARIO
 * ============================================================
 */

if ($method === "POST") {

    $nombres = isset($input["nombres"])
        ? trim($input["nombres"])
        : "";

    $apellidos = isset($input["apellidos"])
        ? trim($input["apellidos"])
        : "";

    $correo = isset($input["correo"])
        ? trim(strtolower($input["correo"]))
        : "";

    $password = isset($input["password"])
        ? $input["password"]
        : "";

    $rol = isset($input["rol"])
        ? trim($input["rol"])
        : "usuario";

    $estado = isset($input["estado"])
        ? trim($input["estado"])
        : "activo";

    /**
     * VALIDAR CAMPOS
     */

    if (
        $nombres === "" ||
        $apellidos === "" ||
        $correo === "" ||
        $password === ""
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Todos los campos obligatorios deben completarse."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /**
     * VALIDAR CORREO
     */

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El correo electrónico no es válido."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /**
     * VALIDAR CONTRASEÑA
     */

    if (strlen($password) < 6) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "La contraseña debe tener mínimo 6 caracteres."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /**
     * VALIDAR ROL
     */

    if (!in_array($rol, ["usuario", "admin"], true)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El rol seleccionado no es válido."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /**
     * VALIDAR ESTADO
     */

    if (!in_array($estado, ["activo", "inactivo"], true)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El estado seleccionado no es válido."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /**
     * COMPROBAR CORREO EXISTENTE
     */

    try {

        $check = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1
        ");

        $check->execute([
            ":correo" => $correo
        ]);

        if ($check->fetch()) {

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" => "Ya existe un usuario con ese correo."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        /**
         * CREAR HASH
         */

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        /**
         * INSERTAR
         */

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (
                nombres,
                apellidos,
                correo,
                password,
                rol,
                estado
            )
            VALUES (
                :nombres,
                :apellidos,
                :correo,
                :password,
                :rol,
                :estado
            )
        ");

        $stmt->execute([
            ":nombres" => $nombres,
            ":apellidos" => $apellidos,
            ":correo" => $correo,
            ":password" => $passwordHash,
            ":rol" => $rol,
            ":estado" => $estado
        ]);

        $id = $pdo->lastInsertId();

        echo json_encode([
            "success" => true,
            "message" => "Usuario creado correctamente.",
            "usuario" => [
                "id" => (int) $id,
                "nombres" => $nombres,
                "apellidos" => $apellidos,
                "correo" => $correo,
                "rol" => $rol,
                "estado" => $estado
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (PDOException $e) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "No fue posible crear el usuario.",
            "error" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

/**
 * ============================================================
 * PUT
 * EDITAR USUARIO
 * ============================================================
 */

if ($method === "PUT") {

    $id = isset($input["id"])
        ? (int) $input["id"]
        : 0;

    $nombres = isset($input["nombres"])
        ? trim($input["nombres"])
        : "";

    $apellidos = isset($input["apellidos"])
        ? trim($input["apellidos"])
        : "";

    $correo = isset($input["correo"])
        ? trim(strtolower($input["correo"]))
        : "";

    $rol = isset($input["rol"])
        ? trim($input["rol"])
        : "";

    $estado = isset($input["estado"])
        ? trim($input["estado"])
        : "";

    if (
        $id <= 0 ||
        $nombres === "" ||
        $apellidos === "" ||
        $correo === ""
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Los datos del usuario son incompletos."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El correo electrónico no es válido."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (!in_array($rol, ["usuario", "admin"], true)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El rol no es válido."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (!in_array($estado, ["activo", "inactivo"], true)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El estado no es válido."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    try {

        /**
         * COMPROBAR QUE EL USUARIO EXISTA
         */

        $find = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE id = :id
            LIMIT 1
        ");

        $find->execute([
            ":id" => $id
        ]);

        if (!$find->fetch()) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "El usuario no existe."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        /**
         * COMPROBAR CORREO REPETIDO
         */

        $check = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE correo = :correo
            AND id <> :id
            LIMIT 1
        ");

        $check->execute([
            ":correo" => $correo,
            ":id" => $id
        ]);

        if ($check->fetch()) {

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" => "El correo ya pertenece a otro usuario."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        /**
         * ACTUALIZAR
         */

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                nombres = :nombres,
                apellidos = :apellidos,
                correo = :correo,
                rol = :rol,
                estado = :estado
            WHERE id = :id
        ");

        $stmt->execute([
            ":nombres" => $nombres,
            ":apellidos" => $apellidos,
            ":correo" => $correo,
            ":rol" => $rol,
            ":estado" => $estado,
            ":id" => $id
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Usuario actualizado correctamente."
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (PDOException $e) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "No fue posible actualizar el usuario.",
            "error" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

/**
 * ============================================================
 * PATCH
 * ACTIVAR / DESACTIVAR USUARIO
 * ============================================================
 */

if ($method === "PATCH") {

    $id = isset($input["id"])
        ? (int) $input["id"]
        : 0;

    $estado = isset($input["estado"])
        ? trim($input["estado"])
        : "";

    if ($id <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "ID de usuario inválido."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (!in_array($estado, ["activo", "inactivo"], true)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Estado inválido."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /**
     * EVITAR QUE EL ADMIN SE DESACTIVE A SÍ MISMO
     */

    if (
        $id === (int) $_SESSION["user_id"] &&
        $estado === "inactivo"
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "No puedes desactivar tu propia cuenta."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    try {

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET estado = :estado
            WHERE id = :id
        ");

        $stmt->execute([
            ":estado" => $estado,
            ":id" => $id
        ]);

        if ($stmt->rowCount() === 0) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "No se encontró el usuario."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $mensaje = $estado === "activo"
            ? "Usuario activado correctamente."
            : "Usuario desactivado correctamente.";

        echo json_encode([
            "success" => true,
            "message" => $mensaje,
            "estado" => $estado
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (PDOException $e) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "No fue posible cambiar el estado del usuario.",
            "error" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

/**
 * ============================================================
 * DELETE
 * ELIMINAR USUARIO
 * ============================================================
 */

if ($method === "DELETE") {

    $id = isset($input["id"])
        ? (int) $input["id"]
        : 0;

    if ($id <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "ID de usuario inválido."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /**
     * EVITAR QUE EL ADMIN SE ELIMINE A SÍ MISMO
     */

    if ($id === (int) $_SESSION["user_id"]) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "No puedes eliminar tu propia cuenta."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    try {

        $stmt = $pdo->prepare("
            DELETE FROM usuarios
            WHERE id = :id
        ");

        $stmt->execute([
            ":id" => $id
        ]);

        if ($stmt->rowCount() === 0) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "El usuario no existe."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        echo json_encode([
            "success" => true,
            "message" => "Usuario eliminado correctamente."
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (PDOException $e) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "No fue posible eliminar el usuario.",
            "error" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

/**
 * ============================================================
 * MÉTODO NO PERMITIDO
 * ============================================================
 */

http_response_code(405);

echo json_encode([
    "success" => false,
    "message" => "Método no permitido."
], JSON_UNESCAPED_UNICODE);

exit;