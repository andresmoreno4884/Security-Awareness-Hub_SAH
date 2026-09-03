<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../Config/database.php";

try {

    $pdo = Database::connect();

    // =====================================================
    // DATOS DEL ADMINISTRADOR
    // =====================================================

    $nombres = "Administrador";
    $apellidos = "Sistema";
    $correo = "admin@sah.com";
    $password = "Admin123*";
    $rol = "admin";
    $estado = "activo";


    // =====================================================
    // VERIFICAR SI YA EXISTE
    // =====================================================

    $sql = "SELECT id FROM usuarios WHERE correo = :correo LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":correo" => $correo
    ]);

    if ($stmt->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "El usuario administrador ya existe."
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        exit;
    }


    // =====================================================
    // GENERAR HASH SEGURO
    // =====================================================

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    // =====================================================
    // INSERTAR ADMINISTRADOR
    // =====================================================

    $sql = "INSERT INTO usuarios
            (
                nombres,
                apellidos,
                correo,
                password,
                rol,
                estado
            )
            VALUES
            (
                :nombres,
                :apellidos,
                :correo,
                :password,
                :rol,
                :estado
            )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        ":nombres" => $nombres,

        ":apellidos" => $apellidos,

        ":correo" => $correo,

        ":password" => $passwordHash,

        ":rol" => $rol,

        ":estado" => $estado
    ]);


    // =====================================================
    // RESPUESTA
    // =====================================================

    echo json_encode([

        "success" => true,

        "message" => "Administrador creado correctamente.",

        "usuario" => [

            "nombres" => $nombres,

            "apellidos" => $apellidos,

            "correo" => $correo,

            "rol" => $rol,

            "estado" => $estado
        ]

    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" => "Error al crear el administrador.",

        "error" => $e->getMessage()

    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}