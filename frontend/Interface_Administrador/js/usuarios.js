/* ============================================================
   SECURITY AWARENESS HUB
   GESTIÓN DE USUARIOS
   usuarios.js
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {

    /* ========================================================
       CONFIGURACIÓN
    ======================================================== */

    const API_URL = "../../backend/Api/usuarios.php";

    /* ========================================================
       ELEMENTOS DEL DOM
    ======================================================== */

    const tablaBody = document.getElementById("usuariosTableBody");
    const contadorUsuarios = document.getElementById("contadorUsuarios");
    const usuariosMostrados = document.getElementById("usuariosMostrados");

    const buscarUsuario = document.getElementById("buscarUsuario");
    const filtroEstado = document.getElementById("filtroEstado");
    const filtroRol = document.getElementById("filtroRol");

    const btnNuevoUsuario = document.getElementById("btnNuevoUsuario");

    const usuarioModal = document.getElementById("usuarioModal");
    const cerrarUsuarioModal = document.getElementById("cerrarUsuarioModal");
    const cancelarUsuario = document.getElementById("cancelarUsuario");

    const formUsuario = document.getElementById("formUsuario");

    const usuarioId = document.getElementById("usuarioId");
    const usuarioNombres = document.getElementById("usuarioNombres");
    const usuarioApellidos = document.getElementById("usuarioApellidos");
    const usuarioCorreo = document.getElementById("usuarioCorreo");
    const usuarioPassword = document.getElementById("usuarioPassword");
    const usuarioRol = document.getElementById("usuarioRol");
    const usuarioEstado = document.getElementById("usuarioEstado");

    const usuarioFormMessage =
        document.getElementById("usuarioFormMessage");

    const modalUsuarioTitulo =
        document.getElementById("modalUsuarioTitulo");

    const guardarUsuario =
        document.getElementById("guardarUsuario");

    const paginaAnterior =
        document.getElementById("paginaAnterior");

    const paginaActual =
        document.getElementById("paginaActual");

    const paginaSiguiente =
        document.getElementById("paginaSiguiente");


    /* ========================================================
       ESTADO
    ======================================================== */

    let usuarios = [];

    let usuariosFiltrados = [];

    let pagina = 1;

    const usuariosPorPagina = 8;


    /* ========================================================
       CARGAR USUARIOS
    ======================================================== */

    async function cargarUsuarios() {

        mostrarCargando();

        try {

            const params = new URLSearchParams();

            const search = buscarUsuario.value.trim();
            const estado = filtroEstado.value;
            const rol = filtroRol.value;

            if (search !== "") {
                params.append("search", search);
            }

            if (estado !== "todos") {
                params.append("estado", estado);
            }

            if (rol !== "todos") {
                params.append("rol", rol);
            }

            const url = params.toString()
                ? `${API_URL}?${params.toString()}`
                : API_URL;

            const response = await fetch(url, {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    "Accept": "application/json"
                }
            });

            const data = await obtenerRespuestaJSON(response);

            if (!response.ok || !data.success) {

                throw new Error(
                    data.message ||
                    "No fue posible cargar los usuarios."
                );
            }

            usuarios = Array.isArray(data.usuarios)
                ? data.usuarios
                : [];

            pagina = 1;

            aplicarPaginacion();

        } catch (error) {

            console.error("Error al cargar usuarios:", error);

            tablaBody.innerHTML = `
                <tr>
                    <td colspan="5" class="users-error">
                        ${escapeHTML(error.message)}
                    </td>
                </tr>
            `;

            contadorUsuarios.textContent = "Error";
            usuariosMostrados.textContent = "No se pudieron cargar los usuarios.";

        }

    }


    /* ========================================================
       RESPUESTA JSON
    ======================================================== */

    async function obtenerRespuestaJSON(response) {

        const texto = await response.text();

        try {

            return JSON.parse(texto);

        } catch (error) {

            console.error("Respuesta recibida por el servidor:", texto);

            throw new Error(
                "El servidor no devolvió una respuesta JSON válida."
            );
        }

    }


    /* ========================================================
       MOSTRAR CARGANDO
    ======================================================== */

    function mostrarCargando() {

        tablaBody.innerHTML = `
            <tr>
                <td colspan="5" class="loading-users">
                    Cargando usuarios...
                </td>
            </tr>
        `;

        contadorUsuarios.textContent = "Cargando...";
        usuariosMostrados.textContent = "Cargando usuarios...";

    }


    /* ========================================================
       PAGINACIÓN
    ======================================================== */

    function aplicarPaginacion() {

        usuariosFiltrados = usuarios;

        const totalUsuarios = usuariosFiltrados.length;

        const totalPaginas = Math.max(
            1,
            Math.ceil(totalUsuarios / usuariosPorPagina)
        );

        if (pagina > totalPaginas) {
            pagina = totalPaginas;
        }

        const inicio =
            (pagina - 1) * usuariosPorPagina;

        const fin =
            inicio + usuariosPorPagina;

        const usuariosPagina =
            usuariosFiltrados.slice(inicio, fin);

        renderizarUsuarios(usuariosPagina);

        actualizarContadores(
            totalUsuarios,
            usuariosPagina.length,
            inicio
        );

        actualizarBotonesPaginacion(totalPaginas);

    }


    /* ========================================================
       RENDERIZAR USUARIOS
    ======================================================== */

    function renderizarUsuarios(lista) {

        if (!lista.length) {

            tablaBody.innerHTML = `
                <tr>
                    <td colspan="5" class="empty-users">
                        No se encontraron usuarios.
                    </td>
                </tr>
            `;

            return;
        }


        tablaBody.innerHTML = lista.map(usuario => {

            const nombreCompleto =
                `${usuario.nombres || ""} ${usuario.apellidos || ""}`
                    .trim();

            const iniciales =
                obtenerIniciales(
                    usuario.nombres,
                    usuario.apellidos
                );

            const rolTexto =
                usuario.rol === "admin"
                    ? "Administrador"
                    : "Usuario";

            const estadoTexto =
                usuario.estado === "activo"
                    ? "Activo"
                    : "Inactivo";

            const claseEstado =
                usuario.estado === "activo"
                    ? "status-active"
                    : "status-inactive";

            const claseRol =
                usuario.rol === "admin"
                    ? "role-admin"
                    : "role-user";

            const fecha =
                formatearFecha(usuario.fecha_registro);

            const esUsuarioActual =
                Number(usuario.id) === obtenerIdUsuarioActual();


            return `
                <tr>

                    <td>

                        <div class="user-info">

                            <div class="user-avatar">
                                ${escapeHTML(iniciales)}
                            </div>

                            <div class="user-data">

                                <strong>
                                    ${escapeHTML(nombreCompleto)}
                                </strong>

                                <span>
                                    ${escapeHTML(usuario.correo || "")}
                                </span>

                            </div>

                        </div>

                    </td>


                    <td>

                        <span class="user-role ${claseRol}">
                            ${escapeHTML(rolTexto)}
                        </span>

                    </td>


                    <td>

                        <span class="user-status ${claseEstado}">

                            <span class="status-dot"></span>

                            ${escapeHTML(estadoTexto)}

                        </span>

                    </td>


                    <td>

                        <span class="registration-date">
                            ${escapeHTML(fecha)}
                        </span>

                    </td>


                    <td>

                        <div class="user-actions">

                            <button
                                type="button"
                                class="table-action edit"
                                data-action="editar"
                                data-id="${usuario.id}">
                                Editar
                            </button>


                            <button
                                type="button"
                                class="table-action toggle"
                                data-action="estado"
                                data-id="${usuario.id}"
                                ${esUsuarioActual ? "disabled" : ""}>

                                ${
                                    usuario.estado === "activo"
                                        ? "Desactivar"
                                        : "Activar"
                                }

                            </button>


                            <button
                                type="button"
                                class="table-action delete"
                                data-action="eliminar"
                                data-id="${usuario.id}"
                                ${esUsuarioActual ? "disabled" : ""}>
                                Eliminar
                            </button>

                        </div>

                    </td>

                </tr>
            `;

        }).join("");

    }


    /* ========================================================
       CONTADORES
    ======================================================== */

    function actualizarContadores(
        total,
        cantidadPagina,
        inicio
    ) {

        if (total === 0) {

            contadorUsuarios.textContent =
                "0 usuarios";

            usuariosMostrados.textContent =
                "No hay usuarios para mostrar.";

            return;
        }

        contadorUsuarios.textContent =
            `${total} ${total === 1 ? "usuario" : "usuarios"}`;

        const desde = inicio + 1;

        const hasta = inicio + cantidadPagina;

        usuariosMostrados.textContent =
            `Mostrando ${desde}-${hasta} de ${total} usuarios`;

    }


    /* ========================================================
       BOTONES PAGINACIÓN
    ======================================================== */

    function actualizarBotonesPaginacion(totalPaginas) {

        paginaActual.textContent = pagina;

        paginaAnterior.disabled =
            pagina <= 1;

        paginaSiguiente.disabled =
            pagina >= totalPaginas;

    }


    /* ========================================================
       NUEVO USUARIO
    ======================================================== */

    btnNuevoUsuario.addEventListener("click", () => {

        abrirModalNuevoUsuario();

    });


    function abrirModalNuevoUsuario() {

        formUsuario.reset();

        usuarioId.value = "";

        usuarioRol.value = "usuario";

        usuarioEstado.value = "activo";

        usuarioPassword.required = true;

        modalUsuarioTitulo.textContent =
            "Nuevo usuario";

        guardarUsuario.textContent =
            "Crear usuario";

        limpiarMensajeFormulario();

        usuarioModal.hidden = false;

        document.body.classList.add("modal-open");

        setTimeout(() => {
            usuarioNombres.focus();
        }, 100);

    }


    /* ========================================================
       EDITAR USUARIO
    ======================================================== */

    function abrirModalEditarUsuario(usuario) {

        usuarioId.value = usuario.id;

        usuarioNombres.value =
            usuario.nombres || "";

        usuarioApellidos.value =
            usuario.apellidos || "";

        usuarioCorreo.value =
            usuario.correo || "";

        usuarioPassword.value = "";

        usuarioRol.value =
            usuario.rol || "usuario";

        usuarioEstado.value =
            usuario.estado || "activo";

        usuarioPassword.required = false;

        modalUsuarioTitulo.textContent =
            "Editar usuario";

        guardarUsuario.textContent =
            "Guardar cambios";

        limpiarMensajeFormulario();

        usuarioModal.hidden = false;

        document.body.classList.add("modal-open");

        setTimeout(() => {
            usuarioNombres.focus();
        }, 100);

    }


    /* ========================================================
       CERRAR MODAL
    ======================================================== */

    cerrarUsuarioModal.addEventListener(
        "click",
        cerrarModalUsuario
    );

    cancelarUsuario.addEventListener(
        "click",
        cerrarModalUsuario
    );


    const overlay =
        usuarioModal.querySelector(".user-modal-overlay");

    if (overlay) {

        overlay.addEventListener(
            "click",
            cerrarModalUsuario
        );

    }


    function cerrarModalUsuario() {

        usuarioModal.hidden = true;

        document.body.classList.remove("modal-open");

        limpiarMensajeFormulario();

        formUsuario.reset();

        usuarioId.value = "";

    }


    /* ========================================================
       GUARDAR USUARIO
    ======================================================== */

    formUsuario.addEventListener("submit", async (event) => {

        event.preventDefault();

        limpiarMensajeFormulario();

        const id =
            Number(usuarioId.value);

        const datos = {

            nombres:
                usuarioNombres.value.trim(),

            apellidos:
                usuarioApellidos.value.trim(),

            correo:
                usuarioCorreo.value.trim(),

            rol:
                usuarioRol.value,

            estado:
                usuarioEstado.value

        };


        /* ====================================================
           CREAR
        ==================================================== */

        if (!id) {

            const password =
                usuarioPassword.value;

            if (!password || password.length < 6) {

                mostrarMensajeFormulario(
                    "La contraseña debe tener mínimo 6 caracteres.",
                    "error"
                );

                usuarioPassword.focus();

                return;
            }

            datos.password = password;

            await crearUsuario(datos);

            return;
        }


        /* ====================================================
           EDITAR
        ==================================================== */

        await editarUsuario(id, datos);

    });


    /* ========================================================
       CREAR USUARIO
    ======================================================== */

    async function crearUsuario(datos) {

        bloquearFormulario(true);

        try {

            const response = await fetch(API_URL, {

                method: "POST",

                credentials: "same-origin",

                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },

                body: JSON.stringify(datos)

            });


            const data =
                await obtenerRespuestaJSON(response);


            if (!response.ok || !data.success) {

                throw new Error(
                    data.message ||
                    "No fue posible crear el usuario."
                );

            }


            mostrarMensajeFormulario(
                data.message ||
                "Usuario creado correctamente.",
                "success"
            );


            await cargarUsuarios();


            setTimeout(() => {

                cerrarModalUsuario();

            }, 700);


        } catch (error) {

            console.error(
                "Error al crear usuario:",
                error
            );

            mostrarMensajeFormulario(
                error.message,
                "error"
            );

        } finally {

            bloquearFormulario(false);

        }

    }


    /* ========================================================
       EDITAR USUARIO
    ======================================================== */

    async function editarUsuario(id, datos) {

        bloquearFormulario(true);

        try {

            const response = await fetch(API_URL, {

                method: "PUT",

                credentials: "same-origin",

                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },

                body: JSON.stringify({
                    id,
                    ...datos
                })

            });


            const data =
                await obtenerRespuestaJSON(response);


            if (!response.ok || !data.success) {

                throw new Error(
                    data.message ||
                    "No fue posible actualizar el usuario."
                );

            }


            mostrarMensajeFormulario(
                data.message ||
                "Usuario actualizado correctamente.",
                "success"
            );


            await cargarUsuarios();


            setTimeout(() => {

                cerrarModalUsuario();

            }, 700);


        } catch (error) {

            console.error(
                "Error al actualizar usuario:",
                error
            );

            mostrarMensajeFormulario(
                error.message,
                "error"
            );

        } finally {

            bloquearFormulario(false);

        }

    }


    /* ========================================================
       CAMBIAR ESTADO
    ======================================================== */

    async function cambiarEstado(usuario) {

        const nuevoEstado =
            usuario.estado === "activo"
                ? "inactivo"
                : "activo";


        const accion =
            nuevoEstado === "activo"
                ? "activar"
                : "desactivar";


        const confirmado =
            confirm(
                `¿Deseas ${accion} a ${usuario.nombres} ${usuario.apellidos}?`
            );


        if (!confirmado) {
            return;
        }


        try {

            const response = await fetch(API_URL, {

                method: "PATCH",

                credentials: "same-origin",

                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },

                body: JSON.stringify({

                    id: Number(usuario.id),

                    estado: nuevoEstado

                })

            });


            const data =
                await obtenerRespuestaJSON(response);


            if (!response.ok || !data.success) {

                throw new Error(
                    data.message ||
                    "No fue posible cambiar el estado."
                );

            }


            await cargarUsuarios();


        } catch (error) {

            console.error(
                "Error al cambiar estado:",
                error
            );

            alert(error.message);

        }

    }


    /* ========================================================
       ELIMINAR USUARIO
    ======================================================== */

    async function eliminarUsuario(usuario) {

        const nombre =
            `${usuario.nombres} ${usuario.apellidos}`.trim();


        const confirmado =
            confirm(
                `¿Estás seguro de eliminar a ${nombre}?\n\nEsta acción no se puede deshacer.`
            );


        if (!confirmado) {
            return;
        }


        try {

            const response = await fetch(API_URL, {

                method: "DELETE",

                credentials: "same-origin",

                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },

                body: JSON.stringify({

                    id: Number(usuario.id)

                })

            });


            const data =
                await obtenerRespuestaJSON(response);


            if (!response.ok || !data.success) {

                throw new Error(
                    data.message ||
                    "No fue posible eliminar el usuario."
                );

            }


            await cargarUsuarios();


        } catch (error) {

            console.error(
                "Error al eliminar usuario:",
                error
            );

            alert(error.message);

        }

    }


    /* ========================================================
       EVENTOS DE LA TABLA
    ======================================================== */

    tablaBody.addEventListener("click", (event) => {

        const button =
            event.target.closest("button[data-action]");

        if (!button) {
            return;
        }


        const id =
            Number(button.dataset.id);

        const usuario =
            usuarios.find(
                item => Number(item.id) === id
            );


        if (!usuario) {
            return;
        }


        const action =
            button.dataset.action;


        if (action === "editar") {

            abrirModalEditarUsuario(usuario);

        }


        if (action === "estado") {

            cambiarEstado(usuario);

        }


        if (action === "eliminar") {

            eliminarUsuario(usuario);

        }

    });


    /* ========================================================
       BUSCADOR
    ======================================================== */

    let temporizadorBusqueda = null;


    buscarUsuario.addEventListener(
        "input",
        () => {

            clearTimeout(
                temporizadorBusqueda
            );


            temporizadorBusqueda =
                setTimeout(() => {

                    cargarUsuarios();

                }, 350);

        }
    );


    /* ========================================================
       FILTRO ESTADO
    ======================================================== */

    filtroEstado.addEventListener(
        "change",
        () => {

            cargarUsuarios();

        }
    );


    /* ========================================================
       FILTRO ROL
    ======================================================== */

    filtroRol.addEventListener(
        "change",
        () => {

            cargarUsuarios();

        }
    );


    /* ========================================================
       PAGINA ANTERIOR
    ======================================================== */

    paginaAnterior.addEventListener(
        "click",
        () => {

            if (pagina > 1) {

                pagina--;

                aplicarPaginacion();

            }

        }
    );


    /* ========================================================
       PAGINA SIGUIENTE
    ======================================================== */

    paginaSiguiente.addEventListener(
        "click",
        () => {

            const totalPaginas =
                Math.max(
                    1,
                    Math.ceil(
                        usuariosFiltrados.length /
                        usuariosPorPagina
                    )
                );


            if (pagina < totalPaginas) {

                pagina++;

                aplicarPaginacion();

            }

        }
    );


    /* ========================================================
       ESCAPE PARA CERRAR MODAL
    ======================================================== */

    document.addEventListener(
        "keydown",
        (event) => {

            if (
                event.key === "Escape" &&
                !usuarioModal.hidden
            ) {

                cerrarModalUsuario();

            }

        }
    );


    /* ========================================================
       BLOQUEAR / DESBLOQUEAR FORMULARIO
    ======================================================== */

    function bloquearFormulario(bloquear) {

        const controles =
            formUsuario.querySelectorAll(
                "input, select, button"
            );


        controles.forEach(control => {

            control.disabled = bloquear;

        });


        if (!bloquear) {

            guardarUsuario.disabled = false;

        }

    }


    /* ========================================================
       MENSAJE DEL FORMULARIO
    ======================================================== */

    function mostrarMensajeFormulario(
        mensaje,
        tipo
    ) {

        usuarioFormMessage.textContent =
            mensaje;

        usuarioFormMessage.className =
            `user-form-message ${tipo}`;

    }


    function limpiarMensajeFormulario() {

        usuarioFormMessage.textContent = "";

        usuarioFormMessage.className =
            "user-form-message";

    }


    /* ========================================================
       INICIALES
    ======================================================== */

    function obtenerIniciales(
        nombres = "",
        apellidos = ""
    ) {

        const primera =
            nombres.trim().charAt(0);

        const segunda =
            apellidos.trim().charAt(0);


        return (
            primera +
            segunda
        ).toUpperCase() || "U";

    }


    /* ========================================================
       FORMATEAR FECHA
    ======================================================== */

    function formatearFecha(fecha) {

        if (!fecha) {
            return "Sin fecha";
        }


        const fechaObj =
            new Date(
                fecha.replace(" ", "T")
            );


        if (Number.isNaN(
            fechaObj.getTime()
        )) {

            return fecha;

        }


        return fechaObj.toLocaleDateString(
            "es-CO",
            {
                day: "2-digit",
                month: "2-digit",
                year: "numeric"
            }
        );

    }


    /* ========================================================
       OBTENER ID DEL ADMINISTRADOR ACTUAL
       Si no existe en localStorage devuelve 0.
    ======================================================== */

    function obtenerIdUsuarioActual() {

        try {

            const posiblesClaves = [
                "sah_session",
                "session",
                "usuario",
                "user"
            ];


            for (
                const clave of posiblesClaves
            ) {

                const valor =
                    localStorage.getItem(clave);


                if (!valor) {
                    continue;
                }


                const datos =
                    JSON.parse(valor);


                if (datos && datos.id) {

                    return Number(datos.id);

                }


                if (
                    datos &&
                    datos.user_id
                ) {

                    return Number(
                        datos.user_id
                    );

                }

            }

        } catch (error) {

            console.warn(
                "No se pudo obtener el usuario actual."
            );

        }


        return 0;

    }


    /* ========================================================
       SEGURIDAD HTML
    ======================================================== */

    function escapeHTML(valor) {

        return String(valor ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    /* ========================================================
       INICIAR
    ======================================================== */

    cargarUsuarios();

});