<?php

/* =====================================================
   SECURITY AWARENESS HUB
   API - REGISTRO DE USUARIOS
   PHP + MYSQL + JSON
===================================================== */

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

/* =====================================================
   SOLICITUD OPTIONS
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

/* =====================================================
   SOLO PERMITIR POST
===================================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =====================================================
   CONEXIÓN CON MYSQL
===================================================== */

require_once "../Config/database.php";

$conn = Database::connect();

/* =====================================================
   RECIBIR JSON
===================================================== */

$input = file_get_contents("php://input");

$data = json_decode($input, true);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Los datos enviados no son válidos."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =====================================================
   OBTENER DATOS
===================================================== */

$nombres = trim($data["nombres"] ?? "");
$apellidos = trim($data["apellidos"] ?? "");
$correo = strtolower(trim($data["correo"] ?? ""));
$password = $data["password"] ?? "";

/* =====================================================
   VALIDAR CAMPOS OBLIGATORIOS
===================================================== */

if (
    $nombres === "" ||
    $apellidos === "" ||
    $correo === "" ||
    $password === ""
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Todos los campos son obligatorios."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =====================================================
   VALIDAR NOMBRES
===================================================== */

if (strlen($nombres) < 2) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "El nombre debe tener al menos 2 caracteres."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =====================================================
   VALIDAR APELLIDOS
===================================================== */

if (strlen($apellidos) < 2) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Los apellidos deben tener al menos 2 caracteres."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =====================================================
   VALIDAR CORREO
===================================================== */

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "El correo electrónico no es válido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =====================================================
   VALIDAR CONTRASEÑA
===================================================== */

if (strlen($password) < 8) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "La contraseña debe tener mínimo 8 caracteres."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =====================================================
   COMPROBAR SI EL CORREO YA EXISTE
===================================================== */

$sqlCheck = "
    SELECT id
    FROM usuarios
    WHERE correo = :correo
    LIMIT 1
";

$stmtCheck = $conn->prepare($sqlCheck);

$stmtCheck->bindValue(
    ":correo",
    $correo,
    PDO::PARAM_STR
);

$stmtCheck->execute();

$existingUser = $stmtCheck->fetch();

/* =====================================================
   CORREO YA REGISTRADO
===================================================== */

if ($existingUser) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "Este correo electrónico ya está registrado."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =====================================================
   ENCRIPTAR CONTRASEÑA
===================================================== */

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

/* =====================================================
   VALORES POR DEFECTO
===================================================== */

$rol = "usuario";
$estado = "activo";

/* =====================================================
   INSERTAR USUARIO
===================================================== */

$sql = "
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
";

$stmt = $conn->prepare($sql);

$stmt->bindValue(
    ":nombres",
    $nombres,
    PDO::PARAM_STR
);

$stmt->bindValue(
    ":apellidos",
    $apellidos,
    PDO::PARAM_STR
);

$stmt->bindValue(
    ":correo",
    $correo,
    PDO::PARAM_STR
);

$stmt->bindValue(
    ":password",
    $passwordHash,
    PDO::PARAM_STR
);

$stmt->bindValue(
    ":rol",
    $rol,
    PDO::PARAM_STR
);

$stmt->bindValue(
    ":estado",
    $estado,
    PDO::PARAM_STR
);

/* =====================================================
   EJECUTAR REGISTRO
===================================================== */

try {

    $stmt->execute();

    $userId = $conn->lastInsertId();

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Usuario registrado correctamente.",
        "user" => [
            "id" => (int) $userId,
            "nombres" => $nombres,
            "apellidos" => $apellidos,
            "correo" => $correo,
            "rol" => $rol,
            "estado" => $estado
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible registrar el usuario.",
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

?>