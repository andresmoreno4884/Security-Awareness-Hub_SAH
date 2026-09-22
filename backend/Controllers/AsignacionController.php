<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * AsignacionController.php
 * ============================================================
 *
 * Controlador de la gestión de asignaciones.
 *
 * Este controlador trabaja con formularios PHP tradicionales,
 * por lo tanto NO necesita JavaScript.
 * ============================================================
 */

require_once __DIR__ . '/../Models/AsignacionModel.php';

class AsignacionController
{
    private AsignacionModel $modelo;

    public function __construct()
    {
        $this->modelo = new AsignacionModel();
    }

    /**
     * ========================================================
     * DATOS NECESARIOS PARA LA VISTA
     * ========================================================
     */
    public function obtenerDatosVista(): array
    {
        return [
            'asignaciones' => $this->modelo->obtenerTodas(),
            'usuarios' => $this->modelo->obtenerUsuarios(),
            'cursos' => $this->modelo->obtenerCursos(),
            'evaluaciones' => $this->modelo->obtenerEvaluaciones(),
            'estadisticas' => $this->modelo->obtenerEstadisticas()
        ];
    }

    /**
     * ========================================================
     * OBTENER ASIGNACIÓN PARA EDITAR
     * ========================================================
     */
    public function obtenerPorId(int $id): ?array
    {
        return $this->modelo->obtenerPorId($id);
    }

    /**
     * ========================================================
     * CREAR ASIGNACIÓN
     * ========================================================
     */
    public function crear(array $datos): array
    {
        $errores = $this->validar($datos);

        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => implode(' ', $errores)
            ];
        }

        try {

            $id = $this->modelo->crear([
                'usuario_id' => (int) $datos['usuario_id'],
                'curso_id' => $datos['tipo'] === 'curso'
                    ? (int) $datos['curso_id']
                    : null,
                'evaluacion_id' => $datos['tipo'] === 'evaluacion'
                    ? (int) $datos['evaluacion_id']
                    : null,
                'tipo' => $datos['tipo'],
                'estado' => $datos['estado']
            ]);

            return [
                'success' => true,
                'message' => 'Asignación creada correctamente.',
                'id' => $id
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'message' => 'No fue posible crear la asignación.'
            ];
        }
    }

    /**
     * ========================================================
     * ACTUALIZAR ASIGNACIÓN
     * ========================================================
     */
    public function actualizar(int $id, array $datos): array
    {
        if ($id < 1) {
            return [
                'success' => false,
                'message' => 'La asignación no es válida.'
            ];
        }

        if (!$this->modelo->obtenerPorId($id)) {
            return [
                'success' => false,
                'message' => 'La asignación no existe.'
            ];
        }

        $errores = $this->validar($datos);

        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => implode(' ', $errores)
            ];
        }

        try {

            $this->modelo->actualizar(
                $id,
                [
                    'usuario_id' => (int) $datos['usuario_id'],
                    'curso_id' => $datos['tipo'] === 'curso'
                        ? (int) $datos['curso_id']
                        : null,
                    'evaluacion_id' => $datos['tipo'] === 'evaluacion'
                        ? (int) $datos['evaluacion_id']
                        : null,
                    'tipo' => $datos['tipo'],
                    'estado' => $datos['estado']
                ]
            );

            return [
                'success' => true,
                'message' => 'Asignación actualizada correctamente.'
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'message' => 'No fue posible actualizar la asignación.'
            ];
        }
    }

    /**
     * ========================================================
     * ELIMINAR
     * ========================================================
     */
    public function eliminar(int $id): array
    {
        if ($id < 1) {
            return [
                'success' => false,
                'message' => 'La asignación no es válida.'
            ];
        }

        if (!$this->modelo->obtenerPorId($id)) {
            return [
                'success' => false,
                'message' => 'La asignación no existe.'
            ];
        }

        try {

            $this->modelo->eliminar($id);

            return [
                'success' => true,
                'message' => 'Asignación eliminada correctamente.'
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'message' => 'No fue posible eliminar la asignación.'
            ];
        }
    }

    /**
     * ========================================================
     * VALIDACIÓN
     * ========================================================
     */
    private function validar(array $datos): array
    {
        $errores = [];

        $usuarioId = filter_var(
            $datos['usuario_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $tipo = $datos['tipo'] ?? '';

        $estado = $datos['estado'] ?? '';

        if (!$usuarioId || $usuarioId < 1) {
            $errores[] = 'Debes seleccionar un usuario.';
        }

        if (!in_array(
            $tipo,
            ['curso', 'evaluacion'],
            true
        )) {
            $errores[] = 'El tipo de asignación no es válido.';
        }

        if (!in_array(
            $estado,
            ['pendiente', 'progreso', 'completado'],
            true
        )) {
            $errores[] = 'El estado seleccionado no es válido.';
        }

        if ($tipo === 'curso') {

            $cursoId = filter_var(
                $datos['curso_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$cursoId || $cursoId < 1) {
                $errores[] = 'Debes seleccionar un curso.';
            }
        }

        if ($tipo === 'evaluacion') {

            $evaluacionId = filter_var(
                $datos['evaluacion_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$evaluacionId || $evaluacionId < 1) {
                $errores[] = 'Debes seleccionar una evaluación.';
            }
        }

        return $errores;
    }
}