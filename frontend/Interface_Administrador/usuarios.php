<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * usuarios.php
 * ============================================================
 *
 * Gestión de usuarios.
 *
 * Roles:
 * - admin
 * - admin_empresa
 * - empleado
 *
 * Características:
 * - PHP puro.
 * - Sin JavaScript.
 * - Sin fetch.
 * - CSRF.
 * - Patrón POST / REDIRECT / GET.
 * - Asociación usuario -> empresa.
 *
 * ============================================================
 */

require_once __DIR__ . "/../../backend/view_bootstrap.php";

Auth::requireAdminView("../authentication/login.php");

$model = new UsuarioModel();

/*
|--------------------------------------------------------------------------
| EMPRESAS DISPONIBLES
|--------------------------------------------------------------------------
*/

$empresas = $model->listarEmpresas();

/*
|--------------------------------------------------------------------------
| PROCESAR ACCIONES POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | VALIDAR CSRF
    |--------------------------------------------------------------------------
    */

    if (!Csrf::validar()) {

        header(
            "Location: usuarios.php?msg="
            . urlencode("Tu formulario expiró, inténtalo de nuevo.")
            . "&tipo=error"
        );

        exit;
    }

    $accion = $_POST["accion"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | CREAR / EDITAR
    |--------------------------------------------------------------------------
    */

    if (
        $accion === "crear"
        || $accion === "editar"
    ) {

        $id = (int) (
            $_POST["id"] ?? 0
        );

        $nombres = trim(
            $_POST["nombres"] ?? ""
        );

        $apellidos = trim(
            $_POST["apellidos"] ?? ""
        );

        $correo = strtolower(
            trim(
                $_POST["correo"] ?? ""
            )
        );

        $password = $_POST["password"] ?? "";

        $rol = $_POST["rol"] ?? "empleado";

        $estado = $_POST["estado"] ?? "activo";

        $empresaId = (int) (
            $_POST["empresa_id"] ?? 0
        );

        $error = "";

        /*
        |--------------------------------------------------------------------------
        | VALIDACIONES BÁSICAS
        |--------------------------------------------------------------------------
        */

        if (
            $nombres === ""
            || $apellidos === ""
            || $correo === ""
        ) {

            $error =
                "Todos los campos obligatorios deben completarse.";

        } elseif (
            !filter_var(
                $correo,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                "El correo electrónico no es válido.";

        } elseif (
            !in_array(
                $rol,
                [
                    "empleado",
                    "admin_empresa",
                    "admin"
                ],
                true
            )
        ) {

            $error =
                "El rol seleccionado no es válido.";

        } elseif (
            !in_array(
                $estado,
                [
                    "activo",
                    "inactivo"
                ],
                true
            )
        ) {

            $error =
                "El estado seleccionado no es válido.";

        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR EMPRESA SEGÚN EL ROL
        |--------------------------------------------------------------------------
        */

        if ($error === "") {

            /*
            |----------------------------------------------------------
            | EMPLEADO
            |----------------------------------------------------------
            */

            if (
                $rol === "empleado"
                && $empresaId <= 0
            ) {

                $error =
                    "Debes seleccionar una empresa para el empleado.";
            }

            /*
            |----------------------------------------------------------
            | ADMINISTRADOR DE EMPRESA
            |----------------------------------------------------------
            */

            elseif (
                $rol === "admin_empresa"
                && $empresaId <= 0
            ) {

                $error =
                    "Debes seleccionar una empresa para el administrador de empresa.";
            }

            /*
            |----------------------------------------------------------
            | ADMINISTRADOR GENERAL
            |----------------------------------------------------------
            */

            elseif (
                $rol === "admin"
            ) {

                /*
                | Un administrador general pertenece a SAH,
                | no a una empresa cliente.
                */

                $empresaId = 0;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR EMPRESA EXISTENTE
        |--------------------------------------------------------------------------
        */

        if (
            $error === ""
            && $empresaId > 0
        ) {

            $empresaEncontrada = false;

            foreach (
                $empresas as $empresa
            ) {

                if (
                    (int) $empresa["id"]
                    === $empresaId
                ) {

                    $empresaEncontrada = true;

                    break;
                }
            }

            if (!$empresaEncontrada) {

                $error =
                    "La empresa seleccionada no es válida o no está activa.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR CONTRASEÑA
        |--------------------------------------------------------------------------
        */

        if (
            $error === ""
            && $accion === "crear"
        ) {

            $errorPass =
                Validator::contrasenaSegura(
                    $password
                );

            if ($errorPass !== null) {

                $error = $errorPass;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | GUARDAR
        |--------------------------------------------------------------------------
        */

        if ($error === "") {

            try {

                /*
                |--------------------------------------------------------------------------
                | CREAR
                |--------------------------------------------------------------------------
                */

                if ($accion === "crear") {

                    if (
                        $model->correoExiste(
                            $correo
                        )
                    ) {

                        $error =
                            "Ya existe un usuario con ese correo.";

                    } else {

                        $model->crear([

                            "nombres" =>
                                $nombres,

                            "apellidos" =>
                                $apellidos,

                            "correo" =>
                                $correo,

                            "password" =>
                                $password,

                            "rol" =>
                                $rol,

                            "empresa_id" =>
                                $empresaId > 0
                                    ? $empresaId
                                    : null,

                            "estado" =>
                                $estado
                        ]);
                    }

                }

                /*
                |--------------------------------------------------------------------------
                | EDITAR
                |--------------------------------------------------------------------------
                */

                else {

                    if (
                        $id <= 0
                        || !$model->buscarPorId($id)
                    ) {

                        $error =
                            "El usuario no existe.";

                    } elseif (
                        $model->correoExiste(
                            $correo,
                            $id
                        )
                    ) {

                        $error =
                            "El correo ya pertenece a otro usuario.";

                    } else {

                        $model->actualizar(
                            $id,
                            [

                                "nombres" =>
                                    $nombres,

                                "apellidos" =>
                                    $apellidos,

                                "correo" =>
                                    $correo,

                                "rol" =>
                                    $rol,

                                "empresa_id" =>
                                    $empresaId > 0
                                        ? $empresaId
                                        : null,

                                "estado" =>
                                    $estado
                            ]
                        );
                    }
                }

            } catch (Throwable $e) {

                $error =
                    "No fue posible guardar el usuario.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MOSTRAR ERROR
        |--------------------------------------------------------------------------
        */

        if ($error !== "") {

            header(
                "Location: usuarios.php?"
                . (
                    $id > 0
                        ? "editar={$id}&"
                        : "nuevo=1&"
                )
                . "msg="
                . urlencode($error)
                . "&tipo=error"
            );

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | REDIRECCIÓN
        |--------------------------------------------------------------------------
        */

        header(
            "Location: usuarios.php?msg="
            . urlencode(
                $accion === "crear"
                    ? "Usuario creado correctamente."
                    : "Usuario actualizado correctamente."
            )
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CAMBIAR ESTADO
    |--------------------------------------------------------------------------
    */

    elseif (
        $accion === "estado"
    ) {

        $id = (int) (
            $_POST["id"] ?? 0
        );

        $nuevoEstado =
            $_POST["estado"] ?? "";

        /*
        |--------------------------------------------------------------------------
        | NO DESACTIVARSE A SÍ MISMO
        |--------------------------------------------------------------------------
        */

        if (
            $id === Auth::userId()
            && $nuevoEstado === "inactivo"
        ) {

            header(
                "Location: usuarios.php?msg="
                . urlencode(
                    "No puedes desactivar tu propia cuenta."
                )
                . "&tipo=error"
            );

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR ESTADO
        |--------------------------------------------------------------------------
        */

        if (
            $id > 0
            && in_array(
                $nuevoEstado,
                [
                    "activo",
                    "inactivo"
                ],
                true
            )
        ) {

            $model->cambiarEstado(
                $id,
                $nuevoEstado
            );
        }

        header(
            "Location: usuarios.php?msg="
            . urlencode(
                $nuevoEstado === "activo"
                    ? "Usuario activado."
                    : "Usuario desactivado."
            )
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR
    |--------------------------------------------------------------------------
    */

    elseif (
        $accion === "eliminar"
    ) {

        $id = (int) (
            $_POST["id"] ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | NO ELIMINAR PROPIA CUENTA
        |--------------------------------------------------------------------------
        */

        if (
            $id === Auth::userId()
        ) {

            header(
                "Location: usuarios.php?msg="
                . urlencode(
                    "No puedes eliminar tu propia cuenta."
                )
                . "&tipo=error"
            );

            exit;
        }

        if ($id > 0) {

            $model->eliminar($id);
        }

        header(
            "Location: usuarios.php?msg="
            . urlencode(
                "Usuario eliminado correctamente."
            )
        );

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| BÚSQUEDA Y FILTROS
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET["search"] ?? ""
);

$estadoFiltro =
    $_GET["estado"] ?? "todos";

$todos = $model->listar(
    $search,
    $estadoFiltro === "todos"
        ? ""
        : $estadoFiltro
);

/*
|--------------------------------------------------------------------------
| PAGINACIÓN
|--------------------------------------------------------------------------
*/

$porPagina = 10;

$paginaActual = max(
    1,
    (int) (
        $_GET["pagina"] ?? 1
    )
);

$totalPaginas = max(
    1,
    (int) ceil(
        count($todos)
        / $porPagina
    )
);

$paginaActual = min(
    $paginaActual,
    $totalPaginas
);

$usuariosPagina = array_slice(
    $todos,
    ($paginaActual - 1)
        * $porPagina,
    $porPagina
);

/*
|--------------------------------------------------------------------------
| BUSCAR USUARIO PARA EDITAR
|--------------------------------------------------------------------------
*/

$editando = null;

if (
    isset(
        $_GET["editar"]
    )
) {

    $idEditar =
        (int) $_GET["editar"];

    /*
    | Primero buscamos dentro
    | del listado actual.
    */

    foreach (
        $todos as $u
    ) {

        if (
            (int) $u["id"]
            === $idEditar
        ) {

            $editando = $u;

            break;
        }
    }

    /*
    | Si no está por filtros,
    | buscamos directamente.
    */

    if (!$editando) {

        $editando =
            $model->buscarPorId(
                $idEditar
            );
    }
}

$mostrarFormulario =
    isset($_GET["nuevo"])
    || $editando !== null;

/*
|--------------------------------------------------------------------------
| CONFIRMACIÓN DE ELIMINACIÓN
|--------------------------------------------------------------------------
*/

$confirmandoEliminar = null;

if (
    isset(
        $_GET["confirmar_eliminar"]
    )
) {

    $idEliminar =
        (int) $_GET["confirmar_eliminar"];

    $confirmandoEliminar =
        $model->buscarPorId(
            $idEliminar
        );
}

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN DE VISTA
|--------------------------------------------------------------------------
*/

$activeView = "usuarios";

$pageTitle = "Usuarios";

$extraCss = [
    "css/usuarios.css"
];

require __DIR__ . "/partials/_header.php";

require __DIR__ . "/partials/_flash.php";

?>

<div class="dashboard-heading">

    <div>

        <span class="section-label">
            GESTIÓN DE USUARIOS
        </span>

        <h2>
            Usuarios
        </h2>

        <p>
            Administra los usuarios, roles y empresas
            asociadas dentro de la plataforma.
        </p>

    </div>

    <?php if (
        !$mostrarFormulario
        && !$confirmandoEliminar
    ): ?>

        <a
            href="usuarios.php?nuevo=1"
            class="admin-button"
        >
            + Nuevo usuario
        </a>

    <?php endif; ?>

</div>


<?php if ($confirmandoEliminar): ?>

    <!-- =====================================================
         CONFIRMACIÓN DE ELIMINACIÓN
    ====================================================== -->

    <div
        class="dashboard-card users-table-card"
        style="padding:24px;"
    >

        <div class="card-header">

            <div>

                <span class="section-label">
                    CONFIRMAR
                </span>

                <h3>
                    Eliminar usuario
                </h3>

            </div>

        </div>

        <p>

            ¿Seguro que deseas eliminar a

            <strong>

                <?= htmlspecialchars(
                    $confirmandoEliminar["nombres"]
                    . " "
                    . $confirmandoEliminar["apellidos"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </strong>

            (<?= htmlspecialchars(
                $confirmandoEliminar["correo"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>)?

            Esta acción no se puede deshacer.

        </p>

        <div class="form-actions">

            <a
                href="usuarios.php"
                class="admin-secondary-button"
            >
                Cancelar
            </a>

            <form
                method="POST"
                action="usuarios.php"
            >

                <?php Csrf::campo(); ?>

                <input
                    type="hidden"
                    name="accion"
                    value="eliminar"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $confirmandoEliminar["id"] ?>"
                >

                <button
                    type="submit"
                    class="admin-button"
                >
                    Sí, eliminar
                </button>

            </form>

        </div>

    </div>


<?php elseif ($mostrarFormulario): ?>

    <!-- =====================================================
         FORMULARIO CREAR / EDITAR
    ====================================================== -->

    <div
        class="dashboard-card users-table-card"
        style="padding:24px;"
    >

        <div class="card-header">

            <div>

                <span class="section-label">
                    ADMINISTRACIÓN
                </span>

                <h3>

                    <?= $editando
                        ? "Editar usuario"
                        : "Nuevo usuario"
                    ?>

                </h3>

            </div>

        </div>


        <form
            method="POST"
            action="usuarios.php"
        >

            <?php Csrf::campo(); ?>

            <input
                type="hidden"
                name="accion"
                value="<?= $editando
                    ? "editar"
                    : "crear"
                ?>"
            >

            <?php if ($editando): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $editando["id"] ?>"
                >

            <?php endif; ?>


            <!-- NOMBRES -->

            <div class="form-group">

                <label
                    for="usuarioNombres"
                >
                    Nombres
                </label>

                <input
                    type="text"
                    id="usuarioNombres"
                    name="nombres"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $editando["nombres"]
                        ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>


            <!-- APELLIDOS -->

            <div class="form-group">

                <label
                    for="usuarioApellidos"
                >
                    Apellidos
                </label>

                <input
                    type="text"
                    id="usuarioApellidos"
                    name="apellidos"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $editando["apellidos"]
                        ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>


            <!-- CORREO -->

            <div class="form-group">

                <label
                    for="usuarioCorreo"
                >
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="usuarioCorreo"
                    name="correo"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                        $editando["correo"]
                        ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>


            <!-- CONTRASEÑA -->

            <?php if (!$editando): ?>

                <div class="form-group">

                    <label
                        for="usuarioPassword"
                    >
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="usuarioPassword"
                        name="password"
                        pattern="<?= Validator::PASSWORD_PATTERN ?>"
                        title="Mínimo 8 caracteres, 2 números y 1 carácter especial"
                        required
                    >

                    <small class="field-hint">

                        Mínimo 8 caracteres,
                        con al menos 2 números
                        y 1 carácter especial.

                    </small>

                </div>

            <?php endif; ?>


            <!-- ROL -->

            <div class="form-group">

                <label
                    for="usuarioRol"
                >
                    Rol
                </label>

                <select
                    id="usuarioRol"
                    name="rol"
                    required
                >

                    <option
                        value="empleado"
                        <?= (
                            $editando["rol"]
                            ?? "empleado"
                        ) === "empleado"
                            ? "selected"
                            : ""
                        ?>
                    >
                        Empleado
                    </option>

                    <option
                        value="admin_empresa"
                        <?= (
                            $editando["rol"]
                            ?? ""
                        ) === "admin_empresa"
                            ? "selected"
                            : ""
                        ?>
                    >
                        Administrador de empresa
                    </option>

                    <option
                        value="admin"
                        <?= (
                            $editando["rol"]
                            ?? ""
                        ) === "admin"
                            ? "selected"
                            : ""
                        ?>
                    >
                        Administrador SAH
                    </option>

                </select>

                <small class="field-hint">

                    <strong>Empleado:</strong>
                    realiza las capacitaciones.

                    <br>

                    <strong>Administrador de empresa:</strong>
                    administra su organización.

                    <br>

                    <strong>Administrador SAH:</strong>
                    administra toda la plataforma.

                </small>

            </div>


            <!-- EMPRESA -->

            <div class="form-group">

                <label
                    for="usuarioEmpresa"
                >
                    Empresa
                </label>

                <select
                    id="usuarioEmpresa"
                    name="empresa_id"
                >

                    <option value="0">

                        Seleccionar empresa

                    </option>

                    <?php foreach (
                        $empresas as $empresa
                    ): ?>

                        <option
                            value="<?= (int) $empresa["id"] ?>"
                            <?= (
                                (int) (
                                    $editando["empresa_id"]
                                    ?? 0
                                )
                                === (int) $empresa["id"]
                            )
                                ? "selected"
                                : ""
                            ?>
                        >

                            <?= htmlspecialchars(
                                $empresa["nombre"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <small class="field-hint">

                    La empresa es obligatoria para
                    empleados y administradores de empresa.

                    <br>

                    El Administrador SAH no pertenece
                    a una empresa cliente.

                </small>

            </div>


            <!-- ESTADO -->

            <div class="form-group">

                <label
                    for="usuarioEstado"
                >
                    Estado
                </label>

                <select
                    id="usuarioEstado"
                    name="estado"
                    required
                >

                    <option
                        value="activo"
                        <?= (
                            $editando["estado"]
                            ?? "activo"
                        ) === "activo"
                            ? "selected"
                            : ""
                        ?>
                    >
                        Activo
                    </option>

                    <option
                        value="inactivo"
                        <?= (
                            $editando["estado"]
                            ?? ""
                        ) === "inactivo"
                            ? "selected"
                            : ""
                        ?>
                    >
                        Inactivo
                    </option>

                </select>

            </div>


            <!-- ACCIONES -->

            <div class="form-actions">

                <a
                    href="usuarios.php"
                    class="admin-secondary-button"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="admin-button"
                >

                    <?= $editando
                        ? "Guardar cambios"
                        : "Crear usuario"
                    ?>

                </button>

            </div>

        </form>

    </div>


<?php else: ?>

    <!-- =====================================================
         FILTROS
    ====================================================== -->

    <form
        method="GET"
        action="usuarios.php"
        class="users-toolbar"
    >

        <div class="search-box">

            <span class="search-icon">
                ⌕
            </span>

            <input
                type="search"
                name="search"
                placeholder="Buscar por nombre, apellido, correo o empresa..."
                value="<?= htmlspecialchars(
                    $search,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

        </div>


        <select
            class="users-filter"
            name="estado"
        >

            <option
                value="todos"
                <?= $estadoFiltro === "todos"
                    ? "selected"
                    : ""
                ?>
            >
                Todos los estados
            </option>

            <option
                value="activo"
                <?= $estadoFiltro === "activo"
                    ? "selected"
                    : ""
                ?>
            >
                Activos
            </option>

            <option
                value="inactivo"
                <?= $estadoFiltro === "inactivo"
                    ? "selected"
                    : ""
                ?>
            >
                Inactivos
            </option>

        </select>


        <button
            type="submit"
            class="admin-button"
        >
            Buscar
        </button>

    </form>


    <!-- =====================================================
         TABLA
    ====================================================== -->

    <div class="dashboard-card users-table-card">

        <div class="card-header">

            <div>

                <span class="section-label">
                    DIRECTORIO
                </span>

                <h3>
                    Usuarios registrados
                </h3>

            </div>

            <span class="card-period">

                <?= count($todos) ?>
                usuario(s)

            </span>

        </div>


        <div class="table-wrapper">

            <table class="users-table">

                <thead>

                    <tr>

                        <th>
                            USUARIO
                        </th>

                        <th>
                            ROL
                        </th>

                        <th>
                            EMPRESA
                        </th>

                        <th>
                            ESTADO
                        </th>

                        <th>
                            FECHA DE REGISTRO
                        </th>

                        <th>
                            ACCIONES
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (
                        empty($usuariosPagina)
                    ): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="loading-users"
                            >
                                No se encontraron usuarios.
                            </td>

                        </tr>

                    <?php endif; ?>


                    <?php foreach (
                        $usuariosPagina as $u
                    ): ?>

                        <tr>

                            <!-- USUARIO -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $u["nombres"]
                                        . " "
                                        . $u["apellidos"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </strong>

                                <br>

                                <small>

                                    <?= htmlspecialchars(
                                        $u["correo"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </small>

                            </td>


                            <!-- ROL -->

                            <td>

                                <?php

                                $rolTexto = match (
                                    $u["rol"]
                                ) {

                                    "admin" =>
                                        "Administrador SAH",

                                    "admin_empresa" =>
                                        "Administrador de empresa",

                                    "empleado" =>
                                        "Empleado",

                                    default =>
                                        $u["rol"]
                                };

                                ?>

                                <?= htmlspecialchars(
                                    $rolTexto,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </td>


                            <!-- EMPRESA -->

                            <td>

                                <?php if (
                                    !empty(
                                        $u["empresa_nombre"]
                                    )
                                ): ?>

                                    <?= htmlspecialchars(
                                        $u["empresa_nombre"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                <?php else: ?>

                                    <span>
                                        Sin empresa
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ESTADO -->

                            <td>

                                <span
                                    class="badge badge-<?= htmlspecialchars(
                                        $u["estado"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                >

                                    <?= ucfirst(
                                        $u["estado"]
                                    ) ?>

                                </span>

                            </td>


                            <!-- FECHA -->

                            <td>

                                <?= htmlspecialchars(
                                    $u["fecha_registro"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </td>


                            <!-- ACCIONES -->

                            <td class="table-actions">

                                <a
                                    href="usuarios.php?editar=<?= (int) $u["id"] ?>"
                                    class="link-action"
                                >
                                    Editar
                                </a>


                                <form
                                    method="POST"
                                    action="usuarios.php"
                                >

                                    <?php Csrf::campo(); ?>

                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="estado"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $u["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="estado"
                                        value="<?= $u["estado"] === "activo"
                                            ? "inactivo"
                                            : "activo"
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="link-action"
                                    >

                                        <?= $u["estado"] === "activo"
                                            ? "Desactivar"
                                            : "Activar"
                                        ?>

                                    </button>

                                </form>


                                <a
                                    href="usuarios.php?confirmar_eliminar=<?= (int) $u["id"] ?>"
                                    class="link-action"
                                >
                                    Eliminar
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <!-- =================================================
             PAGINACIÓN
        ================================================== -->

        <div class="table-footer">

            <span>

                Página
                <?= $paginaActual ?>
                de
                <?= $totalPaginas ?>

                —

                <?= count($todos) ?>
                usuario(s) en total

            </span>


            <div class="pagination">

                <a
                    class="pagination-button <?= $paginaActual <= 1
                        ? "disabled"
                        : ""
                    ?>"
                    href="usuarios.php?pagina=<?= max(
                        1,
                        $paginaActual - 1
                    ) ?>&search=<?= urlencode(
                        $search
                    ) ?>&estado=<?= urlencode(
                        $estadoFiltro
                    ) ?>"
                >
                    ←
                </a>


                <span
                    class="pagination-button active"
                >
                    <?= $paginaActual ?>
                </span>


                <a
                    class="pagination-button <?= $paginaActual >= $totalPaginas
                        ? "disabled"
                        : ""
                    ?>"
                    href="usuarios.php?pagina=<?= min(
                        $totalPaginas,
                        $paginaActual + 1
                    ) ?>&search=<?= urlencode(
                        $search
                    ) ?>&estado=<?= urlencode(
                        $estadoFiltro
                    ) ?>"
                >
                    →
                </a>

            </div>

        </div>

    </div>

<?php endif; ?>


<?php

require __DIR__ . "/partials/_footer.php";

?>