<?php

/* ============================================================
   SECURITY AWARENESS HUB
   API DE CURSOS
   PHP + MySQL + JSON
============================================================ */

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../Config/Database.php";


/* ============================================================
   RESPUESTA JSON
============================================================ */

function respuesta($success, $message = "", $data = [], $status = 200)
{
    http_response_code($status);

    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $data
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* ============================================================
   CONEXIÓN
============================================================ */

try {

    $conn = Database::connect();

    if (!$conn) {

        respuesta(
            false,
            "No se pudo conectar con la base de datos.",
            [],
            500
        );
    }

} catch (Throwable $e) {

    respuesta(
        false,
        "Error de conexión con la base de datos.",
        [],
        500
    );
}


/* ============================================================
   MÉTODO HTTP
============================================================ */

$method = $_SERVER["REQUEST_METHOD"];


/* ============================================================
   OBTENER DATOS JSON
============================================================ */

$input = [];

$rawInput = file_get_contents("php://input");

if (!empty($rawInput)) {

    $decoded = json_decode(
        $rawInput,
        true
    );

    if (is_array($decoded)) {

        $input = $decoded;

    }

}


/* ============================================================
   GET
   LISTAR CURSOS
============================================================ */

if ($method === "GET") {

    try {

        $sql = "
            SELECT
                id,
                titulo,
                descripcion,
                nivel_dificultad,
                duracion,
                numero_modulos,
                porcentaje_aprobacion,
                estado,
                fecha_creacion
            FROM cursos
            ORDER BY fecha_creacion DESC, id DESC
        ";


        $stmt = $conn->prepare($sql);

        $stmt->execute();


        $cursos = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        respuesta(
            true,
            "Cursos obtenidos correctamente.",
            [
                "cursos" => $cursos
            ]
        );


    } catch (Throwable $e) {

        respuesta(
            false,
            "No fue posible obtener los cursos.",
            [],
            500
        );

    }

}


/* ============================================================
   POST
   CREAR CURSO
============================================================ */

if ($method === "POST") {

    $titulo =
        trim(
            $input["titulo"] ?? ""
        );


    $descripcion =
        trim(
            $input["descripcion"] ?? ""
        );


    $nivel =
        trim(
            $input["nivel_dificultad"] ?? "Básico"
        );


    $duracion =
        trim(
            $input["duracion"] ?? ""
        );


    $numeroModulos =
        (int)(
            $input["numero_modulos"] ?? 1
        );


    $porcentaje =
        (int)(
            $input["porcentaje_aprobacion"] ?? 70
        );


    $estado =
        isset($input["estado"])
            ? (int)$input["estado"]
            : 1;


    /* --------------------------------------------------------
       VALIDACIONES
    -------------------------------------------------------- */

    if ($titulo === "") {

        respuesta(
            false,
            "El título del curso es obligatorio.",
            [],
            400
        );

    }


    if ($descripcion === "") {

        respuesta(
            false,
            "La descripción del curso es obligatoria.",
            [],
            400
        );

    }


    if ($duracion === "") {

        respuesta(
            false,
            "La duración del curso es obligatoria.",
            [],
            400
        );

    }


    if ($numeroModulos < 1) {

        respuesta(
            false,
            "El número de módulos debe ser mayor que 0.",
            [],
            400
        );

    }


    if (
        $porcentaje < 0 ||
        $porcentaje > 100
    ) {

        respuesta(
            false,
            "El porcentaje de aprobación debe estar entre 0 y 100.",
            [],
            400
        );

    }


    if (
        $estado !== 0 &&
        $estado !== 1
    ) {

        $estado = 1;

    }


    try {

        /* ----------------------------------------------------
           VERIFICAR TÍTULO DUPLICADO
        ---------------------------------------------------- */

        $check =
            $conn->prepare(
                "
                SELECT id
                FROM cursos
                WHERE titulo = ?
                LIMIT 1
                "
            );


        $check->execute(
            [$titulo]
        );


        if ($check->fetch()) {

            respuesta(
                false,
                "Ya existe un curso con ese título.",
                [],
                409
            );

        }


        /* ----------------------------------------------------
           INSERTAR
        ---------------------------------------------------- */

        $sql = "
            INSERT INTO cursos
            (
                titulo,
                descripcion,
                nivel_dificultad,
                duracion,
                numero_modulos,
                porcentaje_aprobacion,
                estado
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $stmt =
            $conn->prepare($sql);


        $stmt->execute(
            [
                $titulo,
                $descripcion,
                $nivel,
                $duracion,
                $numeroModulos,
                $porcentaje,
                $estado
            ]
        );


        $id =
            $conn->lastInsertId();


        respuesta(
            true,
            "Curso creado correctamente.",
            [
                "id" => (int)$id
            ],
            201
        );


    } catch (Throwable $e) {

        respuesta(
            false,
            "No fue posible crear el curso.",
            [],
            500
        );

    }

}


/* ============================================================
   PUT
   EDITAR CURSO
============================================================ */

if ($method === "PUT") {

    $id =
        (int)(
            $input["id"] ?? 0
        );


    $titulo =
        trim(
            $input["titulo"] ?? ""
        );


    $descripcion =
        trim(
            $input["descripcion"] ?? ""
        );


    $nivel =
        trim(
            $input["nivel_dificultad"] ?? "Básico"
        );


    $duracion =
        trim(
            $input["duracion"] ?? ""
        );


    $numeroModulos =
        (int)(
            $input["numero_modulos"] ?? 1
        );


    $porcentaje =
        (int)(
            $input["porcentaje_aprobacion"] ?? 70
        );


    $estado =
        isset($input["estado"])
            ? (int)$input["estado"]
            : 1;


    /* --------------------------------------------------------
       VALIDACIONES
    -------------------------------------------------------- */

    if ($id <= 0) {

        respuesta(
            false,
            "ID de curso inválido.",
            [],
            400
        );

    }


    if ($titulo === "") {

        respuesta(
            false,
            "El título del curso es obligatorio.",
            [],
            400
        );

    }


    if ($descripcion === "") {

        respuesta(
            false,
            "La descripción del curso es obligatoria.",
            [],
            400
        );

    }


    if ($duracion === "") {

        respuesta(
            false,
            "La duración del curso es obligatoria.",
            [],
            400
        );

    }


    if ($numeroModulos < 1) {

        respuesta(
            false,
            "El número de módulos debe ser mayor que 0.",
            [],
            400
        );

    }


    if (
        $porcentaje < 0 ||
        $porcentaje > 100
    ) {

        respuesta(
            false,
            "El porcentaje de aprobación debe estar entre 0 y 100.",
            [],
            400
        );

    }


    $estado =
        $estado === 1
            ? 1
            : 0;


    try {

        /* ----------------------------------------------------
           COMPROBAR CURSO
        ---------------------------------------------------- */

        $check =
            $conn->prepare(
                "
                SELECT id
                FROM cursos
                WHERE id = ?
                LIMIT 1
                "
            );


        $check->execute(
            [$id]
        );


        if (!$check->fetch()) {

            respuesta(
                false,
                "El curso no existe.",
                [],
                404
            );

        }


        /* ----------------------------------------------------
           COMPROBAR TÍTULO DUPLICADO
        ---------------------------------------------------- */

        $duplicate =
            $conn->prepare(
                "
                SELECT id
                FROM cursos
                WHERE titulo = ?
                AND id <> ?
                LIMIT 1
                "
            );


        $duplicate->execute(
            [
                $titulo,
                $id
            ]
        );


        if ($duplicate->fetch()) {

            respuesta(
                false,
                "Ya existe otro curso con ese título.",
                [],
                409
            );

        }


        /* ----------------------------------------------------
           ACTUALIZAR
        ---------------------------------------------------- */

        $sql = "
            UPDATE cursos
            SET
                titulo = ?,
                descripcion = ?,
                nivel_dificultad = ?,
                duracion = ?,
                numero_modulos = ?,
                porcentaje_aprobacion = ?,
                estado = ?
            WHERE id = ?
        ";


        $stmt =
            $conn->prepare($sql);


        $stmt->execute(
            [
                $titulo,
                $descripcion,
                $nivel,
                $duracion,
                $numeroModulos,
                $porcentaje,
                $estado,
                $id
            ]
        );


        respuesta(
            true,
            "Curso actualizado correctamente."
        );


    } catch (Throwable $e) {

        respuesta(
            false,
            "No fue posible actualizar el curso.",
            [],
            500
        );

    }

}


/* ============================================================
   PATCH
   ACTIVAR / DESACTIVAR
============================================================ */

if ($method === "PATCH") {

    $id =
        (int)(
            $input["id"] ?? 0
        );


    $estado =
        (int)(
            $input["estado"] ?? -1
        );


    if ($id <= 0) {

        respuesta(
            false,
            "ID de curso inválido.",
            [],
            400
        );

    }


    if (
        $estado !== 0 &&
        $estado !== 1
    ) {

        respuesta(
            false,
            "Estado inválido.",
            [],
            400
        );

    }


    try {

        $check =
            $conn->prepare(
                "
                SELECT id
                FROM cursos
                WHERE id = ?
                LIMIT 1
                "
            );


        $check->execute(
            [$id]
        );


        if (!$check->fetch()) {

            respuesta(
                false,
                "El curso no existe.",
                [],
                404
            );

        }


        $stmt =
            $conn->prepare(
                "
                UPDATE cursos
                SET estado = ?
                WHERE id = ?
                "
            );


        $stmt->execute(
            [
                $estado,
                $id
            ]
        );


        respuesta(
            true,
            $estado === 1
                ? "Curso activado correctamente."
                : "Curso desactivado correctamente."
        );


    } catch (Throwable $e) {

        respuesta(
            false,
            "No fue posible cambiar el estado del curso.",
            [],
            500
        );

    }

}


/* ============================================================
   DELETE
   ELIMINAR CURSO
============================================================ */

if ($method === "DELETE") {

    $id =
        (int)(
            $input["id"] ?? 0
        );


    if ($id <= 0) {

        respuesta(
            false,
            "ID de curso inválido.",
            [],
            400
        );

    }


    try {

        /* ----------------------------------------------------
           COMPROBAR EXISTENCIA
        ---------------------------------------------------- */

        $check =
            $conn->prepare(
                "
                SELECT id
                FROM cursos
                WHERE id = ?
                LIMIT 1
                "
            );


        $check->execute(
            [$id]
        );


        if (!$check->fetch()) {

            respuesta(
                false,
                "El curso no existe.",
                [],
                404
            );

        }


        /* ----------------------------------------------------
           ELIMINAR
        ---------------------------------------------------- */

        $stmt =
            $conn->prepare(
                "
                DELETE FROM cursos
                WHERE id = ?
                "
            );


        $stmt->execute(
            [$id]
        );


        respuesta(
            true,
            "Curso eliminado correctamente."
        );


    } catch (PDOException $e) {

        /*
         * Si existen registros relacionados con el curso
         * y MySQL bloquea la eliminación por una FK,
         * devolvemos un mensaje controlado.
         */

        if (
            $e->getCode() === "23000"
        ) {

            respuesta(
                false,
                "No se puede eliminar este curso porque tiene información relacionada. Puedes desactivarlo.",
                [],
                409
            );

        }


        respuesta(
            false,
            "No fue posible eliminar el curso.",
            [],
            500
        );


    } catch (Throwable $e) {

        respuesta(
            false,
            "No fue posible eliminar el curso.",
            [],
            500
        );

    }

}


/* ============================================================
   MÉTODO NO SOPORTADO
============================================================ */

respuesta(
    false,
    "Método HTTP no permitido.",
    [],
    405
);