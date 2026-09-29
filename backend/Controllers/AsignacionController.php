<?php

class AsignacionController
{
    private AsignacionModel $model;
    private UsuarioModel $usuarioModel;
    private CursoModel $cursoModel;

    public function __construct()
    {
        $this->model = new AsignacionModel();
        $this->usuarioModel = new UsuarioModel();
        $this->cursoModel = new CursoModel();

        /*
         * Solo admin y admin_empresa pueden administrar
         * asignaciones.
         */
        if (!Auth::isAuthenticated()) {
            Response::json([
                "success" => false,
                "message" => "Debes iniciar sesión."
            ], 401);
        }

        if (!Auth::isAdmin() && !Auth::isAdminEmpresa()) {
            Response::json([
                "success" => false,
                "message" => "No tienes permisos para administrar asignaciones."
            ], 403);
        }
    }

    /**
     * Manejar las peticiones del controlador.
     */
    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        switch ($method) {
            case 'GET':
                $this->listar();
                break;

            case 'POST':
                $this->crear();
                break;

            case 'PUT':
            case 'PATCH':
                $this->actualizarEstado();
                break;

            case 'DELETE':
                $this->eliminar();
                break;

            default:
                Response::json([
                    "success" => false,
                    "message" => "Método HTTP no permitido."
                ], 405);
        }
    }

    /**
     * GET
     *
     * Admin:
     *   puede consultar todas las asignaciones.
     *
     * Admin_empresa:
     *   solamente las asignaciones de su empresa.
     */
    private function listar(): void
    {
        $usuarioId = isset($_GET['usuario_id'])
            ? (int) $_GET['usuario_id']
            : 0;

        $cursoId = isset($_GET['curso_id'])
            ? (int) $_GET['curso_id']
            : 0;

        $estado = trim($_GET['estado'] ?? '');

        $empresaId = Auth::isAdminEmpresa()
            ? Auth::empresaId()
            : (isset($_GET['empresa_id']) ? (int) $_GET['empresa_id'] : 0);

        if (Auth::isAdminEmpresa() && $empresaId <= 0) {
            Response::json([
                "success" => false,
                "message" => "El administrador de empresa no tiene una empresa asociada."
            ], 403);
        }

        $estadosPermitidos = [
            'asignado',
            'en_progreso',
            'completado',
            'cancelado'
        ];

        if ($estado !== '' && !in_array($estado, $estadosPermitidos, true)) {
            Response::json([
                "success" => false,
                "message" => "Estado de asignación no válido."
            ], 400);
        }

        /*
         * Si se solicita un usuario específico desde
         * admin_empresa, verificamos que pertenezca
         * a su empresa.
         */
        if ($usuarioId > 0 && Auth::isAdminEmpresa()) {
            $usuario = $this->usuarioModel->buscarPorId($usuarioId);

            if (!$usuario) {
                Response::json([
                    "success" => false,
                    "message" => "El usuario no existe."
                ], 404);
            }

            if (
                ($usuario['rol'] ?? '') !== 'empleado' ||
                (int) ($usuario['empresa_id'] ?? 0) !== Auth::empresaId()
            ) {
                Response::json([
                    "success" => false,
                    "message" => "No puedes consultar asignaciones de este usuario."
                ], 403);
            }
        }

        $asignaciones = $this->model->listar(
            $usuarioId,
            $cursoId,
            $estado,
            $empresaId
        );

        Response::json([
            "success" => true,
            "data" => $asignaciones
        ]);
    }

    /**
     * POST
     *
     * Crear una asignación.
     */
    private function crear(): void
    {
        $data = Request::body();

        $usuarioId = isset($data['usuario_id'])
            ? (int) $data['usuario_id']
            : 0;

        $cursoId = isset($data['curso_id'])
            ? (int) $data['curso_id']
            : 0;

        if ($usuarioId <= 0 || $cursoId <= 0) {
            Response::json([
                "success" => false,
                "message" => "El usuario y el curso son obligatorios."
            ], 400);
        }

        /*
         * Buscar usuario.
         */
        $usuario = $this->usuarioModel->buscarPorId($usuarioId);

        if (!$usuario) {
            Response::json([
                "success" => false,
                "message" => "El usuario seleccionado no existe."
            ], 404);
        }

        /*
         * Solo los empleados pueden recibir cursos
         * mediante este sistema de asignaciones.
         */
        if (($usuario['rol'] ?? '') !== 'empleado') {
            Response::json([
                "success" => false,
                "message" => "Los cursos solamente pueden asignarse a empleados."
            ], 400);
        }

        /*
         * Seguridad para admin_empresa:
         * nunca confiamos en un empresa_id enviado
         * por el frontend.
         */
        if (Auth::isAdminEmpresa()) {
            $empresaSesion = Auth::empresaId();
            $empresaUsuario = (int) ($usuario['empresa_id'] ?? 0);

            if ($empresaSesion <= 0) {
                Response::json([
                    "success" => false,
                    "message" => "El administrador de empresa no tiene una empresa asociada."
                ], 403);
            }

            if ($empresaUsuario !== $empresaSesion) {
                Response::json([
                    "success" => false,
                    "message" => "No puedes asignar cursos a empleados de otra empresa."
                ], 403);
            }
        }

        /*
         * El empleado debe estar activo.
         */
        if (($usuario['estado'] ?? '') !== 'activo') {
            Response::json([
                "success" => false,
                "message" => "No se puede asignar un curso a un empleado inactivo."
            ], 400);
        }

        /*
         * Buscar curso.
         */
        $curso = $this->cursoModel->buscarPorId($cursoId);

        if (!$curso) {
            Response::json([
                "success" => false,
                "message" => "El curso seleccionado no existe."
            ], 404);
        }

        /*
         * Solo pueden asignarse cursos activos.
         */
        if (($curso['estado'] ?? '') !== 'activo') {
            Response::json([
                "success" => false,
                "message" => "No se puede asignar un curso inactivo."
            ], 400);
        }

        /*
         * Evitar duplicados.
         */
        if ($this->model->existe($usuarioId, $cursoId)) {
            Response::json([
                "success" => false,
                "message" => "Este curso ya está asignado al empleado."
            ], 409);
        }

        try {
            $id = $this->model->crear(
                $usuarioId,
                $cursoId
            );

            Response::json([
                "success" => true,
                "message" => "Curso asignado correctamente.",
                "data" => [
                    "id" => $id,
                    "usuario_id" => $usuarioId,
                    "curso_id" => $cursoId,
                    "estado" => "asignado"
                ]
            ], 201);

        } catch (PDOException $e) {

            /*
             * La tabla asignaciones tiene una restricción
             * UNIQUE(usuario_id, curso_id).
             */
            if ($e->getCode() === '23000') {
                Response::json([
                    "success" => false,
                    "message" => "Este curso ya está asignado al empleado."
                ], 409);
            }

            Response::json([
                "success" => false,
                "message" => "No fue posible crear la asignación."
            ], 500);
        }
    }

    /**
     * PUT / PATCH
     *
     * Cambiar el estado de una asignación.
     */
    private function actualizarEstado(): void
    {
        $data = Request::body();

        $id = isset($data['id'])
            ? (int) $data['id']
            : 0;

        $estado = trim($data['estado'] ?? '');

        if ($id <= 0) {
            Response::json([
                "success" => false,
                "message" => "El ID de la asignación es obligatorio."
            ], 400);
        }

        $estadosPermitidos = [
            'asignado',
            'en_progreso',
            'completado',
            'cancelado'
        ];

        if (!in_array($estado, $estadosPermitidos, true)) {
            Response::json([
                "success" => false,
                "message" => "Estado de asignación no válido."
            ], 400);
        }

        /*
         * Buscar asignación.
         */
        $asignacion = $this->model->buscarPorId($id);

        if (!$asignacion) {
            Response::json([
                "success" => false,
                "message" => "La asignación no existe."
            ], 404);
        }

        /*
         * Seguridad por empresa.
         */
        if (Auth::isAdminEmpresa()) {
            $empresaSesion = Auth::empresaId();
            $empresaAsignacion = (int) ($asignacion['empresa_id'] ?? 0);

            if (
                $empresaSesion <= 0 ||
                $empresaAsignacion !== $empresaSesion
            ) {
                Response::json([
                    "success" => false,
                    "message" => "No puedes modificar asignaciones de otra empresa."
                ], 403);
            }
        }

        try {
            $actualizado = $this->model->cambiarEstado(
                $id,
                $estado
            );

            if (!$actualizado) {
                Response::json([
                    "success" => false,
                    "message" => "No fue posible actualizar la asignación."
                ], 500);
            }

            Response::json([
                "success" => true,
                "message" => "Estado de la asignación actualizado correctamente."
            ]);

        } catch (PDOException $e) {
            Response::json([
                "success" => false,
                "message" => "No fue posible actualizar la asignación."
            ], 500);
        }
    }

    /**
     * DELETE
     *
     * Eliminar una asignación.
     */
    private function eliminar(): void
    {
        $id = isset($_GET['id'])
            ? (int) $_GET['id']
            : 0;

        if ($id <= 0) {
            $data = Request::body();

            $id = isset($data['id'])
                ? (int) $data['id']
                : 0;
        }

        if ($id <= 0) {
            Response::json([
                "success" => false,
                "message" => "El ID de la asignación es obligatorio."
            ], 400);
        }

        /*
         * Buscar asignación antes de eliminarla.
         */
        $asignacion = $this->model->buscarPorId($id);

        if (!$asignacion) {
            Response::json([
                "success" => false,
                "message" => "La asignación no existe."
            ], 404);
        }

        /*
         * Seguridad por empresa.
         */
        if (Auth::isAdminEmpresa()) {
            $empresaSesion = Auth::empresaId();
            $empresaAsignacion = (int) ($asignacion['empresa_id'] ?? 0);

            if (
                $empresaSesion <= 0 ||
                $empresaAsignacion !== $empresaSesion
            ) {
                Response::json([
                    "success" => false,
                    "message" => "No puedes eliminar asignaciones de otra empresa."
                ], 403);
            }
        }

        try {
            $eliminado = $this->model->eliminar($id);

            if (!$eliminado) {
                Response::json([
                    "success" => false,
                    "message" => "No fue posible eliminar la asignación."
                ], 500);
            }

            Response::json([
                "success" => true,
                "message" => "Asignación eliminada correctamente."
            ]);

        } catch (PDOException $e) {
            Response::json([
                "success" => false,
                "message" => "No fue posible eliminar la asignación."
            ], 500);
        }
    }
}