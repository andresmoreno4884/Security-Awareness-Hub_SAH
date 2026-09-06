<?php

/* ============================================================
   SECURITY AWARENESS HUB
   API DE EVALUACIONES
   PHP + MySQL + JSON
============================================================ */

header("Content-Type: application/json; charset=UTF-8");

session_start();

require_once __DIR__ . "/../Config/database.php";


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
   VERIFICAR SESIÓN
============================================================ */

if (
    !isset($_SESSION["authenticated"]) ||
    $_SESSION["authenticated"] !== true
) {

    respuesta(
        false,
        "No hay una sesión activa.",
        [],
        401
    );

}


/* ============================================================
   VERIFICAR ROL ADMINISTRADOR
============================================================ */

if (
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "admin"
) {

    respuesta(
        false,
        "No tienes permisos para administrar evaluaciones.",
        [],
        403
    );

}


/* ============================================================
   CONEXIÓN
============================================================ */

try {

    $conn = Database::connect();

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
   VALIDAR ESTRUCTURA DE PREGUNTAS
   (una sola opción correcta por pregunta, mínimo 2 opciones)
============================================================ */

function validarPreguntas($preguntas)
{

    if (
        !is_array($preguntas) ||
        count($preguntas) < 1
    ) {

        return "La evaluación debe tener al menos una pregunta.";

    }

    foreach ($preguntas as $index => $pregunta) {

        $numero = $index + 1;

        $enunciado = trim($pregunta["enunciado"] ?? "");

        if ($enunciado === "") {

            return "La pregunta {$numero} no tiene enunciado.";

        }

        $opciones = $pregunta["opciones"] ?? [];

        if (
            !is_array($opciones) ||
            count($opciones) < 2
        ) {

            return "La pregunta {$numero} debe tener al menos 2 opciones.";

        }

        $correctas = 0;

        foreach ($opciones as $opcion) {

            $texto = trim($opcion["texto"] ?? "");

            if ($texto === "") {

                return "Una opción de la pregunta {$numero} está vacía.";

            }

            if (
                isset($opcion["es_correcta"]) &&
                (int)$opcion["es_correcta"] === 1
            ) {

                $correctas++;

            }

        }

        if ($correctas !== 1) {

            return "La pregunta {$numero} debe tener exactamente una opción correcta (tiene {$correctas}).";

        }

    }

    return true;

}


/* ============================================================
   GET
   LISTAR EVALUACIONES  ->  /evaluaciones.php
   VER UNA EVALUACIÓN COMPLETA (con preguntas y opciones)
                          ->  /evaluaciones.php?id=5
============================================================ */

if ($method === "GET") {

    $id = isset($_GET["id"])
        ? (int) $_GET["id"]
        : 0;

    try {

        /* --------------------------------------------------
           DETALLE DE UNA EVALUACIÓN (con preguntas/opciones)
        -------------------------------------------------- */

        if ($id > 0) {

            $stmt = $conn->prepare("
                SELECT
                    e.id,
                    e.curso_id,
                    c.titulo AS curso_titulo,
                    e.titulo,
                    e.descripcion,
                    e.porcentaje_aprobacion,
                    e.estado,
                    e.fecha_creacion
                FROM evaluaciones e
                INNER JOIN cursos c ON c.id = e.curso_id
                WHERE e.id = :id
                LIMIT 1
            ");

            $stmt->execute([":id" => $id]);

            $evaluacion = $stmt->fetch();

            if (!$evaluacion) {

                respuesta(
                    false,
                    "La evaluación no existe.",
                    [],
                    404
                );

            }

            $stmtPreguntas = $conn->prepare("
                SELECT id, enunciado, orden
                FROM preguntas
                WHERE evaluacion_id = :evaluacion_id
                ORDER BY orden ASC, id ASC
            ");

            $stmtPreguntas->execute([":evaluacion_id" => $id]);

            $preguntas = $stmtPreguntas->fetchAll();

            $stmtOpciones = $conn->prepare("
                SELECT id, texto, es_correcta, orden
                FROM opciones
                WHERE pregunta_id = :pregunta_id
                ORDER BY orden ASC, id ASC
            ");

            foreach ($preguntas as &$pregunta) {

                $stmtOpciones->execute([
                    ":pregunta_id" => $pregunta["id"]
                ]);

                $pregunta["opciones"] = $stmtOpciones->fetchAll();

            }

            unset($pregunta);

            $evaluacion["preguntas"] = $preguntas;

            respuesta(
                true,
                "Evaluación obtenida correctamente.",
                ["evaluacion" => $evaluacion]
            );

        }

        /* --------------------------------------------------
           LISTA GENERAL (sin preguntas, solo resumen)
        -------------------------------------------------- */

        $stmt = $conn->prepare("
            SELECT
                e.id,
                e.curso_id,
                c.titulo AS curso_titulo,
                e.titulo,
                e.porcentaje_aprobacion,
                e.estado,
                e.fecha_creacion,
                (
                    SELECT COUNT(*)
                    FROM preguntas p
                    WHERE p.evaluacion_id = e.id
                ) AS total_preguntas
            FROM evaluaciones e
            INNER JOIN cursos c ON c.id = e.curso_id
            ORDER BY e.fecha_creacion DESC, e.id DESC
        ");

        $stmt->execute();

        $evaluaciones = $stmt->fetchAll();

        respuesta(
            true,
            "Evaluaciones obtenidas correctamente.",
            ["evaluaciones" => $evaluaciones]
        );

    } catch (Throwable $e) {

        respuesta(
            false,
            "No fue posible obtener las evaluaciones.",
            [],
            500
        );

    }

}


/* ============================================================
   POST
   CREAR EVALUACIÓN (con preguntas y opciones anidadas)
============================================================ */

if ($method === "POST") {

    $cursoId = (int)($input["curso_id"] ?? 0);
    $titulo = trim($input["titulo"] ?? "");
    $descripcion = trim($input["descripcion"] ?? "");
    $porcentaje = (int)($input["porcentaje_aprobacion"] ?? 70);
    $preguntas = $input["preguntas"] ?? [];

    /* --------------------------------------------------------
       VALIDACIONES BÁSICAS
    -------------------------------------------------------- */

    if ($cursoId <= 0) {

        respuesta(false, "Debes seleccionar un curso.", [], 400);

    }

    if ($titulo === "") {

        respuesta(false, "El título de la evaluación es obligatorio.", [], 400);

    }

    if ($descripcion === "") {

        respuesta(false, "La descripción es obligatoria.", [], 400);

    }

    if ($porcentaje < 0 || $porcentaje > 100) {

        respuesta(false, "El porcentaje de aprobación debe estar entre 0 y 100.", [], 400);

    }

    $errorPreguntas = validarPreguntas($preguntas);

    if ($errorPreguntas !== true) {

        respuesta(false, $errorPreguntas, [], 400);

    }

    try {

        /* ----------------------------------------------------
           VERIFICAR QUE EL CURSO EXISTA
        ---------------------------------------------------- */

        $checkCurso = $conn->prepare("
            SELECT id FROM cursos WHERE id = ? LIMIT 1
        ");

        $checkCurso->execute([$cursoId]);

        if (!$checkCurso->fetch()) {

            respuesta(false, "El curso seleccionado no existe.", [], 404);

        }

        /* ----------------------------------------------------
           TRANSACCIÓN: EVALUACIÓN + PREGUNTAS + OPCIONES
        ---------------------------------------------------- */

        $conn->beginTransaction();

        $stmtEval = $conn->prepare("
            INSERT INTO evaluaciones
                (curso_id, titulo, descripcion, porcentaje_aprobacion, estado)
            VALUES
                (?, ?, ?, ?, 'activo')
        ");

        $stmtEval->execute([
            $cursoId,
            $titulo,
            $descripcion,
            $porcentaje
        ]);

        $evaluacionId = $conn->lastInsertId();

        $stmtPregunta = $conn->prepare("
            INSERT INTO preguntas
                (evaluacion_id, enunciado, orden)
            VALUES
                (?, ?, ?)
        ");

        $stmtOpcion = $conn->prepare("
            INSERT INTO opciones
                (pregunta_id, texto, es_correcta, orden)
            VALUES
                (?, ?, ?, ?)
        ");

        foreach ($preguntas as $index => $pregunta) {

            $stmtPregunta->execute([
                $evaluacionId,
                trim($pregunta["enunciado"]),
                $index + 1
            ]);

            $preguntaId = $conn->lastInsertId();

            foreach ($pregunta["opciones"] as $opIndex => $opcion) {

                $esCorrecta = (
                    isset($opcion["es_correcta"]) &&
                    (int)$opcion["es_correcta"] === 1
                ) ? 1 : 0;

                $stmtOpcion->execute([
                    $preguntaId,
                    trim($opcion["texto"]),
                    $esCorrecta,
                    $opIndex + 1
                ]);

            }

        }

        $conn->commit();

        respuesta(
            true,
            "Evaluación creada correctamente.",
            ["id" => (int) $evaluacionId],
            201
        );

    } catch (Throwable $e) {

        if ($conn->inTransaction()) {

            $conn->rollBack();

        }

        respuesta(
            false,
            "No fue posible crear la evaluación.",
            [],
            500
        );

    }

}


/* ============================================================
   PUT
   EDITAR EVALUACIÓN
   (reemplaza datos básicos + preguntas/opciones por completo)
============================================================ */

if ($method === "PUT") {

    $id = (int)($input["id"] ?? 0);
    $cursoId = (int)($input["curso_id"] ?? 0);
    $titulo = trim($input["titulo"] ?? "");
    $descripcion = trim($input["descripcion"] ?? "");
    $porcentaje = (int)($input["porcentaje_aprobacion"] ?? 70);
    $preguntas = $input["preguntas"] ?? [];

    if ($id <= 0) {

        respuesta(false, "ID de evaluación inválido.", [], 400);

    }

    if ($cursoId <= 0) {

        respuesta(false, "Debes seleccionar un curso.", [], 400);

    }

    if ($titulo === "") {

        respuesta(false, "El título de la evaluación es obligatorio.", [], 400);

    }

    if ($descripcion === "") {

        respuesta(false, "La descripción es obligatoria.", [], 400);

    }

    if ($porcentaje < 0 || $porcentaje > 100) {

        respuesta(false, "El porcentaje de aprobación debe estar entre 0 y 100.", [], 400);

    }

    $errorPreguntas = validarPreguntas($preguntas);

    if ($errorPreguntas !== true) {

        respuesta(false, $errorPreguntas, [], 400);

    }

    try {

        $check = $conn->prepare("SELECT id FROM evaluaciones WHERE id = ? LIMIT 1");

        $check->execute([$id]);

        if (!$check->fetch()) {

            respuesta(false, "La evaluación no existe.", [], 404);

        }

        $conn->beginTransaction();

        $stmtUpdate = $conn->prepare("
            UPDATE evaluaciones
            SET curso_id = ?, titulo = ?, descripcion = ?, porcentaje_aprobacion = ?
            WHERE id = ?
        ");

        $stmtUpdate->execute([
            $cursoId,
            $titulo,
            $descripcion,
            $porcentaje,
            $id
        ]);

        /* ----------------------------------------------------
           BORRAR PREGUNTAS ANTERIORES
           (las opciones se borran solas por ON DELETE CASCADE)
        ---------------------------------------------------- */

        $stmtDelete = $conn->prepare("
            DELETE FROM preguntas WHERE evaluacion_id = ?
        ");

        $stmtDelete->execute([$id]);

        $stmtPregunta = $conn->prepare("
            INSERT INTO preguntas (evaluacion_id, enunciado, orden)
            VALUES (?, ?, ?)
        ");

        $stmtOpcion = $conn->prepare("
            INSERT INTO opciones (pregunta_id, texto, es_correcta, orden)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($preguntas as $index => $pregunta) {

            $stmtPregunta->execute([
                $id,
                trim($pregunta["enunciado"]),
                $index + 1
            ]);

            $preguntaId = $conn->lastInsertId();

            foreach ($pregunta["opciones"] as $opIndex => $opcion) {

                $esCorrecta = (
                    isset($opcion["es_correcta"]) &&
                    (int)$opcion["es_correcta"] === 1
                ) ? 1 : 0;

                $stmtOpcion->execute([
                    $preguntaId,
                    trim($opcion["texto"]),
                    $esCorrecta,
                    $opIndex + 1
                ]);

            }

        }

        $conn->commit();

        respuesta(true, "Evaluación actualizada correctamente.");

    } catch (Throwable $e) {

        if ($conn->inTransaction()) {

            $conn->rollBack();

        }

        respuesta(false, "No fue posible actualizar la evaluación.", [], 500);

    }

}


/* ============================================================
   PATCH
   ACTIVAR / DESACTIVAR
============================================================ */

if ($method === "PATCH") {

    $id = (int)($input["id"] ?? 0);
    $estado = (int)($input["estado"] ?? -1);

    if ($id <= 0) {

        respuesta(false, "ID de evaluación inválido.", [], 400);

    }

    $estadoTexto = $estado === 1 ? "activo" : "inactivo";

    if ($estado !== 0 && $estado !== 1) {

        respuesta(false, "Estado inválido.", [], 400);

    }

    try {

        $check = $conn->prepare("SELECT id FROM evaluaciones WHERE id = ? LIMIT 1");

        $check->execute([$id]);

        if (!$check->fetch()) {

            respuesta(false, "La evaluación no existe.", [], 404);

        }

        $stmt = $conn->prepare("UPDATE evaluaciones SET estado = ? WHERE id = ?");

        $stmt->execute([$estadoTexto, $id]);

        respuesta(
            true,
            $estado === 1
                ? "Evaluación activada correctamente."
                : "Evaluación desactivada correctamente."
        );

    } catch (Throwable $e) {

        respuesta(false, "No fue posible cambiar el estado.", [], 500);

    }

}


/* ============================================================
   DELETE
   ELIMINAR EVALUACIÓN
============================================================ */

if ($method === "DELETE") {

    $id = (int)($input["id"] ?? 0);

    if ($id <= 0) {

        respuesta(false, "ID de evaluación inválido.", [], 400);

    }

    try {

        $check = $conn->prepare("SELECT id FROM evaluaciones WHERE id = ? LIMIT 1");

        $check->execute([$id]);

        if (!$check->fetch()) {

            respuesta(false, "La evaluación no existe.", [], 404);

        }

        $stmt = $conn->prepare("DELETE FROM evaluaciones WHERE id = ?");

        $stmt->execute([$id]);

        respuesta(true, "Evaluación eliminada correctamente.");

    } catch (PDOException $e) {

        if ($e->getCode() === "23000") {

            respuesta(
                false,
                "No se puede eliminar esta evaluación porque tiene información relacionada. Puedes desactivarla.",
                [],
                409
            );

        }

        respuesta(false, "No fue posible eliminar la evaluación.", [], 500);

    } catch (Throwable $e) {

        respuesta(false, "No fue posible eliminar la evaluación.", [], 500);

    }

}


/* ============================================================
   MÉTODO NO SOPORTADO
============================================================ */

respuesta(false, "Método HTTP no permitido.", [], 405);