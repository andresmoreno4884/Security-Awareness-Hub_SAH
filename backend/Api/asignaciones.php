<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * API DE ASIGNACIONES
 * ============================================================
 *
 * Métodos disponibles:
 *
 * GET    -> Consultar asignaciones
 * POST   -> Crear asignación
 * PUT    -> Actualizar asignación
 * DELETE -> Eliminar asignación
 *
 * Todas las respuestas son JSON.
 * Solo los administradores pueden utilizar esta API.
 * ============================================================
 */

require_once __DIR__ . '/../bootstrap.php';

require_once __DIR__ . '/../Models/AsignacionModel.php';


/**
 * ============================================================
 * CONFIGURACIÓN DE RESPUESTA
 * ============================================================
 */

header('Content-Type: application/json; charset=UTF-8');


/**
 * ============================================================
 * AUTORIZACIÓN
 * ============================================================
 */

Auth::requireAdmin();


/**
 * ============================================================
 * MODELO
 * ============================================================
 */

$modelo = new AsignacionModel();


/**
 * ============================================================
 * MÉTODO HTTP
 * ============================================================
 */

$metodo = strtoupper($_SERVER['REQUEST_METHOD']);


/**
 * ============================================================
 * OBTENER DATOS JSON PARA PUT / POST
 * ============================================================
 */

function obtenerDatosEntrada(): array
{
    $contenido = file_get_contents('php://input');

    if ($contenido === false || trim($contenido) === '') {
        return [];
    }

    $datos = json_decode($contenido, true);

    if (!is_array($datos)) {
        Response::json(
            false,
            'El cuerpo de la solicitud no contiene un JSON válido.',
            [],
            400
        );
    }

    return $datos;
}


/**
 * ============================================================
 * GET
 * ============================================================
 *
 * GET /backend/Api/asignaciones.php
 *
 * Devuelve:
 * - asignaciones
 * - estadísticas
 *
 * También permite:
 *
 * ?id=1
 * ?usuarios=1
 * ?cursos=1
 * ?evaluaciones=1
 *
 * ============================================================
 */

if ($metodo === 'GET') {

    /**
     * --------------------------------------------------------
     * CONSULTAR UNA ASIGNACIÓN
     * --------------------------------------------------------
     */

    if (isset($_GET['id'])) {

        $id = filter_var(
            $_GET['id'],
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {

            Response::json(
                false,
                'El ID de la asignación no es válido.',
                [],
                400
            );
        }

        $asignacion = $modelo->obtenerPorId($id);

        if (!$asignacion) {

            Response::json(
                false,
                'La asignación no existe.',
                [],
                404
            );
        }

        Response::json(
            true,
            'Asignación encontrada.',
            [
                'asignacion' => $asignacion
            ]
        );
    }


    /**
     * --------------------------------------------------------
     * LISTAR USUARIOS
     * --------------------------------------------------------
     */

    if (isset($_GET['usuarios'])) {

        $usuarios = $modelo->obtenerUsuarios();

        Response::json(
            true,
            'Usuarios obtenidos correctamente.',
            [
                'usuarios' => $usuarios
            ]
        );
    }


    /**
     * --------------------------------------------------------
     * LISTAR CURSOS
     * --------------------------------------------------------
     */

    if (isset($_GET['cursos'])) {

        $cursos = $modelo->obtenerCursos();

        Response::json(
            true,
            'Cursos obtenidos correctamente.',
            [
                'cursos' => $cursos
            ]
        );
    }


    /**
     * --------------------------------------------------------
     * LISTAR EVALUACIONES
     * --------------------------------------------------------
     */

    if (isset($_GET['evaluaciones'])) {

        $evaluaciones = $modelo->obtenerEvaluaciones();

        Response::json(
            true,
            'Evaluaciones obtenidas correctamente.',
            [
                'evaluaciones' => $evaluaciones
            ]
        );
    }


    /**
     * --------------------------------------------------------
     * LISTAR TODAS LAS ASIGNACIONES
     * --------------------------------------------------------
     */

    $asignaciones = $modelo->obtenerTodas();

    $estadisticas = $modelo->obtenerEstadisticas();

    Response::json(
        true,
        'Asignaciones obtenidas correctamente.',
        [
            'asignaciones' => $asignaciones,
            'estadisticas' => $estadisticas
        ]
    );
}


/**
 * ============================================================
 * POST
 * ============================================================
 *
 * Crear una nueva asignación.
 *
 * ============================================================
 */

if ($metodo === 'POST') {

    $datos = obtenerDatosEntrada();


    /**
     * --------------------------------------------------------
     * DATOS OBLIGATORIOS
     * --------------------------------------------------------
     */

    $usuarioId = filter_var(
        $datos['usuario_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $tipo = strtolower(
        trim(
            $datos['tipo'] ?? ''
        )
    );

    $estado = strtolower(
        trim(
            $datos['estado'] ?? 'pendiente'
        )
    );


    if (!$usuarioId || $usuarioId < 1) {

        Response::json(
            false,
            'Debes seleccionar un usuario.',
            [],
            400
        );
    }


    if (!in_array($tipo, ['curso', 'evaluacion'], true)) {

        Response::json(
            false,
            'El tipo de asignación no es válido.',
            [],
            400
        );
    }


    if (!in_array(
        $estado,
        ['pendiente', 'progreso', 'completado'],
        true
    )) {

        Response::json(
            false,
            'El estado de la asignación no es válido.',
            [],
            400
        );
    }


    /**
     * --------------------------------------------------------
     * CURSO
     * --------------------------------------------------------
     */

    $cursoId = null;

    $evaluacionId = null;


    if ($tipo === 'curso') {

        $cursoId = filter_var(
            $datos['curso_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$cursoId || $cursoId < 1) {

            Response::json(
                false,
                'Debes seleccionar un curso.',
                [],
                400
            );
        }
    }


    /**
     * --------------------------------------------------------
     * EVALUACIÓN
     * --------------------------------------------------------
     */

    if ($tipo === 'evaluacion') {

        $evaluacionId = filter_var(
            $datos['evaluacion_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$evaluacionId || $evaluacionId < 1) {

            Response::json(
                false,
                'Debes seleccionar una evaluación.',
                [],
                400
            );
        }
    }


    /**
     * --------------------------------------------------------
     * CREAR
     * --------------------------------------------------------
     */

    try {

        $id = $modelo->crear([
            'usuario_id' => $usuarioId,
            'curso_id' => $cursoId,
            'evaluacion_id' => $evaluacionId,
            'tipo' => $tipo,
            'estado' => $estado
        ]);


        $asignacion = $modelo->obtenerPorId($id);


        Response::json(
            true,
            'Asignación creada correctamente.',
            [
                'asignacion' => $asignacion
            ],
            201
        );

    } catch (Throwable $e) {

        Response::json(
            false,
            'No fue posible crear la asignación.',
            [],
            500
        );
    }
}


/**
 * ============================================================
 * PUT
 * ============================================================
 *
 * Actualizar una asignación existente.
 *
 * ============================================================
 */

if ($metodo === 'PUT') {

    $datos = obtenerDatosEntrada();


    $id = filter_var(
        $datos['id'] ?? null,
        FILTER_VALIDATE_INT
    );


    if (!$id || $id < 1) {

        Response::json(
            false,
            'El ID de la asignación no es válido.',
            [],
            400
        );
    }


    /**
     * --------------------------------------------------------
     * COMPROBAR EXISTENCIA
     * --------------------------------------------------------
     */

    $existente = $modelo->obtenerPorId($id);

    if (!$existente) {

        Response::json(
            false,
            'La asignación no existe.',
            [],
            404
        );
    }


    /**
     * --------------------------------------------------------
     * DATOS
     * --------------------------------------------------------
     */

    $usuarioId = filter_var(
        $datos['usuario_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $tipo = strtolower(
        trim(
            $datos['tipo'] ?? ''
        )
    );

    $estado = strtolower(
        trim(
            $datos['estado'] ?? ''
        )
    );


    if (!$usuarioId || $usuarioId < 1) {

        Response::json(
            false,
            'Debes seleccionar un usuario.',
            [],
            400
        );
    }


    if (!in_array($tipo, ['curso', 'evaluacion'], true)) {

        Response::json(
            false,
            'El tipo de asignación no es válido.',
            [],
            400
        );
    }


    if (!in_array(
        $estado,
        ['pendiente', 'progreso', 'completado'],
        true
    )) {

        Response::json(
            false,
            'El estado de la asignación no es válido.',
            [],
            400
        );
    }


    $cursoId = null;
    $evaluacionId = null;


    if ($tipo === 'curso') {

        $cursoId = filter_var(
            $datos['curso_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$cursoId || $cursoId < 1) {

            Response::json(
                false,
                'Debes seleccionar un curso.',
                [],
                400
            );
        }
    }


    if ($tipo === 'evaluacion') {

        $evaluacionId = filter_var(
            $datos['evaluacion_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$evaluacionId || $evaluacionId < 1) {

            Response::json(
                false,
                'Debes seleccionar una evaluación.',
                [],
                400
            );
        }
    }


    /**
     * --------------------------------------------------------
     * ACTUALIZAR
     * --------------------------------------------------------
     */

    try {

        $modelo->actualizar(
            $id,
            [
                'usuario_id' => $usuarioId,
                'curso_id' => $cursoId,
                'evaluacion_id' => $evaluacionId,
                'tipo' => $tipo,
                'estado' => $estado
            ]
        );


        $asignacion = $modelo->obtenerPorId($id);


        Response::json(
            true,
            'Asignación actualizada correctamente.',
            [
                'asignacion' => $asignacion
            ]
        );

    } catch (Throwable $e) {

        Response::json(
            false,
            'No fue posible actualizar la asignación.',
            [],
            500
        );
    }
}


/**
 * ============================================================
 * DELETE
 * ============================================================
 *
 * Eliminar una asignación.
 *
 * ============================================================
 */

if ($metodo === 'DELETE') {

    $datos = obtenerDatosEntrada();


    $id = filter_var(
        $datos['id'] ?? null,
        FILTER_VALIDATE_INT
    );


    if (!$id || $id < 1) {

        Response::json(
            false,
            'El ID de la asignación no es válido.',
            [],
            400
        );
    }


    /**
     * --------------------------------------------------------
     * COMPROBAR EXISTENCIA
     * --------------------------------------------------------
     */

    $existente = $modelo->obtenerPorId($id);

    if (!$existente) {

        Response::json(
            false,
            'La asignación no existe.',
            [],
            404
        );
    }


    /**
     * --------------------------------------------------------
     * ELIMINAR
     * --------------------------------------------------------
     */

    try {

        $modelo->eliminar($id);

        Response::json(
            true,
            'Asignación eliminada correctamente.'
        );

    } catch (Throwable $e) {

        Response::json(
            false,
            'No fue posible eliminar la asignación.',
            [],
            500
        );
    }
}


/**
 * ============================================================
 * MÉTODO NO PERMITIDO
 * ============================================================
 */

Response::json(
    false,
    'Método HTTP no permitido.',
    [],
    405
);