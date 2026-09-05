/* ============================================================
   SECURITY AWARENESS HUB
   GESTIÓN DE CURSOS
   courses_admin.js
   ============================================================ */

function inicializarCursos() {

    /* ========================================================
       CONFIGURACIÓN
    ======================================================== */

    const API_URL = "../../backend/Api/courses.php";


    /* ========================================================
       ELEMENTOS DEL DOM
    ======================================================== */

    const tablaBody =
        document.getElementById("cursosTableBody");

    const totalCursos =
        document.getElementById("totalCursos");

    const cursosActivos =
        document.getElementById("cursosActivos");

    const cursosInactivos =
        document.getElementById("cursosInactivos");

    const contadorCursos =
        document.getElementById("contadorCursos");

    const cursosMostrados =
        document.getElementById("cursosMostrados");

    const buscarCurso =
        document.getElementById("buscarCurso");

    const filtroEstadoCurso =
        document.getElementById("filtroEstadoCurso");

    const filtroNivelCurso =
        document.getElementById("filtroNivelCurso");

    const btnNuevoCurso =
        document.getElementById("btnNuevoCurso");

    const cursoModal =
        document.getElementById("cursoModal");

    const cerrarCursoModal =
        document.getElementById("cerrarCursoModal");

    const cancelarCurso =
        document.getElementById("cancelarCurso");

    const formCurso =
        document.getElementById("formCurso");

    const cursoId =
        document.getElementById("cursoId");

    const cursoNombre =
        document.getElementById("cursoNombre");

    const cursoDescripcion =
        document.getElementById("cursoDescripcion");

    const cursoNivel =
        document.getElementById("cursoNivel");

    const cursoDuracion =
        document.getElementById("cursoDuracion");

    const cursoModulos =
        document.getElementById("cursoModulos");

    const cursoAprobacion =
        document.getElementById("cursoAprobacion");

    const cursoEstado =
        document.getElementById("cursoEstado");

    const cursoFormMessage =
        document.getElementById("cursoFormMessage");

    const modalCursoTitulo =
        document.getElementById("modalCursoTitulo");

    const guardarCurso =
        document.getElementById("guardarCurso");

    const paginaAnteriorCurso =
        document.getElementById("paginaAnteriorCurso");

    const paginaActualCurso =
        document.getElementById("paginaActualCurso");

    const paginaSiguienteCurso =
        document.getElementById("paginaSiguienteCurso");


    /* ========================================================
       VALIDAR ELEMENTOS
    ======================================================== */

    if (
        !tablaBody ||
        !totalCursos ||
        !cursosActivos ||
        !cursosInactivos ||
        !contadorCursos ||
        !cursosMostrados ||
        !buscarCurso ||
        !filtroEstadoCurso ||
        !filtroNivelCurso ||
        !btnNuevoCurso ||
        !cursoModal ||
        !cerrarCursoModal ||
        !cancelarCurso ||
        !formCurso ||
        !cursoId ||
        !cursoNombre ||
        !cursoDescripcion ||
        !cursoNivel ||
        !cursoDuracion ||
        !cursoModulos ||
        !cursoAprobacion ||
        !cursoEstado ||
        !cursoFormMessage ||
        !modalCursoTitulo ||
        !guardarCurso ||
        !paginaAnteriorCurso ||
        !paginaActualCurso ||
        !paginaSiguienteCurso
    ) {

        console.error(
            "courses_admin.js: No se encontraron todos los elementos necesarios."
        );

        return;
    }


    /* ========================================================
       ESTADO
    ======================================================== */

    let cursos = [];

    let cursosFiltrados = [];

    let pagina = 1;

    const cursosPorPagina = 8;

    let temporizadorBusqueda = null;


    /* ========================================================
       OBTENER JSON
    ======================================================== */

    async function obtenerRespuestaJSON(response) {

        const texto = await response.text();

        try {

            return JSON.parse(texto);

        } catch (error) {

            console.error(
                "Respuesta del servidor:",
                texto
            );

            throw new Error(
                "El servidor no devolvió una respuesta JSON válida."
            );
        }
    }


    /* ========================================================
       ESCAPAR HTML
    ======================================================== */

    function escapeHTML(valor) {

        if (
            valor === null ||
            valor === undefined
        ) {

            return "";
        }

        return String(valor)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }


    /* ========================================================
       FORMATEAR NIVEL
    ======================================================== */

    function formatearNivel(nivel) {

        const niveles = {

            basico: "Básico",

            intermedio: "Intermedio",

            avanzado: "Avanzado"
        };


        return niveles[nivel] ||
            nivel ||
            "Sin nivel";
    }


    /* ========================================================
       FORMATEAR ESTADO
    ======================================================== */

    function formatearEstado(estado) {

        return estado === "activo"
            ? "Activo"
            : "Inactivo";
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
                String(fecha)
                    .replace(" ", "T")
            );


        if (
            Number.isNaN(
                fechaObj.getTime()
            )
        ) {

            return String(fecha);
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
       MOSTRAR CARGANDO
    ======================================================== */

    function mostrarCargando() {

        tablaBody.innerHTML = `
            <tr>
                <td
                    colspan="9"
                    class="loading-users">

                    Cargando cursos...

                </td>
            </tr>
        `;


        contadorCursos.textContent =
            "Cargando...";


        cursosMostrados.textContent =
            "Cargando cursos...";
    }


    /* ========================================================
       CARGAR CURSOS
    ======================================================== */

    async function cargarCursos() {

        mostrarCargando();


        try {

            const params =
                new URLSearchParams();


            const search =
                buscarCurso.value.trim();


            const estado =
                filtroEstadoCurso.value;


            const nivel =
                filtroNivelCurso.value;


            if (search !== "") {

                params.append(
                    "search",
                    search
                );
            }


            if (estado !== "todos") {

                params.append(
                    "estado",
                    estado
                );
            }


            if (nivel !== "todos") {

                params.append(
                    "nivel_dificultad",
                    nivel
                );
            }


            const url =
                params.toString()
                    ? `${API_URL}?${params.toString()}`
                    : API_URL;


            const response =
                await fetch(
                    url,
                    {
                        method: "GET",

                        credentials:
                            "same-origin",

                        headers: {
                            "Accept":
                                "application/json"
                        }
                    }
                );


            const data =
                await obtenerRespuestaJSON(
                    response
                );


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "No fue posible cargar los cursos."
                );
            }


            cursos =
                Array.isArray(data.cursos)
                    ? data.cursos
                    : [];


            pagina = 1;


            actualizarIndicadores();


            aplicarPaginacion();


        } catch (error) {

            console.error(
                "Error al cargar cursos:",
                error
            );


            tablaBody.innerHTML = `
                <tr>
                    <td
                        colspan="9"
                        class="users-error">

                        ${escapeHTML(error.message)}

                    </td>
                </tr>
            `;


            contadorCursos.textContent =
                "Error";


            cursosMostrados.textContent =
                "No se pudieron cargar los cursos.";
        }
    }


    /* ========================================================
       INDICADORES
    ======================================================== */

    function actualizarIndicadores() {

        const total =
            cursos.length;


        const activos =
            cursos.filter(
                curso =>
                    curso.estado === "activo"
            ).length;


        const inactivos =
            cursos.filter(
                curso =>
                    curso.estado === "inactivo"
            ).length;


        totalCursos.textContent =
            total;


        cursosActivos.textContent =
            activos;


        cursosInactivos.textContent =
            inactivos;


        contadorCursos.textContent =
            `${total} ${
                total === 1
                    ? "curso"
                    : "cursos"
            }`;
    }


    /* ========================================================
       PAGINACIÓN
    ======================================================== */

    function aplicarPaginacion() {

        cursosFiltrados =
            [...cursos];


        const total =
            cursosFiltrados.length;


        const totalPaginas =
            Math.max(
                1,
                Math.ceil(
                    total /
                    cursosPorPagina
                )
            );


        if (
            pagina >
            totalPaginas
        ) {

            pagina =
                totalPaginas;
        }


        const inicio =
            (pagina - 1) *
            cursosPorPagina;


        const fin =
            inicio +
            cursosPorPagina;


        const cursosPagina =
            cursosFiltrados.slice(
                inicio,
                fin
            );


        renderizarCursos(
            cursosPagina
        );


        actualizarFooter(
            total,
            cursosPagina.length,
            inicio
        );


        actualizarBotonesPaginacion(
            totalPaginas
        );
    }


    /* ========================================================
       RENDERIZAR CURSOS
    ======================================================== */

    function renderizarCursos(lista) {

        if (!lista.length) {

            tablaBody.innerHTML = `
                <tr>
                    <td
                        colspan="9"
                        class="empty-users">

                        <div class="empty-state">

                            <div class="empty-icon">
                                ▣
                            </div>

                            <h3>
                                No hay cursos registrados
                            </h3>

                            <p>
                                No existen cursos que coincidan
                                con los filtros seleccionados.
                            </p>

                        </div>

                    </td>
                </tr>
            `;

            return;
        }


        tablaBody.innerHTML =
            lista.map(curso => {

                const estado =
                    curso.estado ||
                    "activo";


                return `
                    <tr>

                        <!-- CURSO -->

                        <td>

                            <div class="user-cell">

                                <div class="user-avatar">
                                    ▣
                                </div>

                                <div class="user-info">

                                    <strong>
                                        ${escapeHTML(
                                            curso.titulo
                                        )}
                                    </strong>

                                </div>

                            </div>

                        </td>


                        <!-- DESCRIPCIÓN -->

                        <td>

                            <span
                                class="course-description"
                                title="${escapeHTML(
                                    curso.descripcion || ""
                                )}">

                                ${escapeHTML(
                                    curso.descripcion ||
                                    "Sin descripción"
                                )}

                            </span>

                        </td>


                        <!-- NIVEL -->

                        <td>

                            <span class="role-badge">

                                ${escapeHTML(
                                    formatearNivel(
                                        curso.nivel_dificultad
                                    )
                                )}

                            </span>

                        </td>


                        <!-- DURACIÓN -->

                        <td>

                            ${escapeHTML(
                                curso.duracion
                            )} h

                        </td>


                        <!-- MÓDULOS -->

                        <td>

                            ${escapeHTML(
                                curso.numero_modulos
                            )}

                        </td>


                        <!-- APROBACIÓN -->

                        <td>

                            ${escapeHTML(
                                curso.porcentaje_aprobacion
                            )}%

                        </td>


                        <!-- ESTADO -->

                        <td>

                            <span
                                class="status-badge ${
                                    estado === "activo"
                                        ? "active"
                                        : "inactive"
                                }">

                                ${escapeHTML(
                                    formatearEstado(
                                        estado
                                    )
                                )}

                            </span>

                        </td>


                        <!-- FECHA -->

                        <td>

                            ${escapeHTML(
                                formatearFecha(
                                    curso.fecha_creacion
                                )
                            )}

                        </td>


                        <!-- ACCIONES -->

                        <td>

                            <div class="table-actions">

                                <button
                                    type="button"
                                    class="table-action edit"
                                    data-action="editar"
                                    data-id="${Number(curso.id)}">

                                    Editar

                                </button>


                                <button
                                    type="button"
                                    class="table-action toggle"
                                    data-action="estado"
                                    data-id="${Number(curso.id)}">

                                    ${
                                        estado === "activo"
                                            ? "Desactivar"
                                            : "Activar"
                                    }

                                </button>


                                <button
                                    type="button"
                                    class="table-action delete"
                                    data-action="eliminar"
                                    data-id="${Number(curso.id)}">

                                    Eliminar

                                </button>

                            </div>

                        </td>

                    </tr>
                `;

            }).join("");
    }


    /* ========================================================
       FOOTER
    ======================================================== */

    function actualizarFooter(
        total,
        cantidadPagina,
        inicio
    ) {

        if (total === 0) {

            cursosMostrados.textContent =
                "No hay cursos para mostrar.";

            return;
        }


        const desde =
            inicio + 1;


        const hasta =
            inicio +
            cantidadPagina;


        cursosMostrados.textContent =
            `Mostrando ${desde}-${hasta} de ${total} cursos`;
    }


    /* ========================================================
       BOTONES PAGINACIÓN
    ======================================================== */

    function actualizarBotonesPaginacion(
        totalPaginas
    ) {

        paginaActualCurso.textContent =
            pagina;


        paginaAnteriorCurso.disabled =
            pagina <= 1;


        paginaSiguienteCurso.disabled =
            pagina >= totalPaginas;
    }


    /* ========================================================
       ABRIR MODAL NUEVO
    ======================================================== */

    function abrirModalNuevoCurso() {

        formCurso.reset();


        cursoId.value =
            "";


        cursoNivel.value =
            "";


        cursoEstado.value =
            "activo";


       

        modalCursoTitulo.textContent =
            "Nuevo curso";


        guardarCurso.textContent =
            "Crear curso";


        limpiarMensajeFormulario();


        cursoModal.hidden =
            false;


        document.body.classList.add(
            "modal-open"
        );


        setTimeout(() => {

            cursoNombre.focus();

        }, 100);
    }


    /* ========================================================
       ABRIR MODAL EDITAR
    ======================================================== */

    function abrirModalEditarCurso(
        curso
    ) {

        cursoId.value =
            curso.id;


        cursoNombre.value =
            curso.titulo || "";


        cursoDescripcion.value =
            curso.descripcion || "";


        cursoNivel.value =
            curso.nivel_dificultad || "";


        cursoDuracion.value =
            curso.duracion || "";


        cursoModulos.value =
            curso.numero_modulos || "";


        cursoAprobacion.value =
            curso.porcentaje_aprobacion || "";


        cursoEstado.value =
            curso.estado || "activo";


        modalCursoTitulo.textContent =
            "Editar curso";


        guardarCurso.textContent =
            "Guardar cambios";


        limpiarMensajeFormulario();


        cursoModal.hidden =
            false;


        document.body.classList.add(
            "modal-open"
        );


        setTimeout(() => {

            cursoNombre.focus();

        }, 100);
    }


    /* ========================================================
       CERRAR MODAL
    ======================================================== */

    function cerrarModalCurso() {

        cursoModal.hidden =
            true;


        document.body.classList.remove(
            "modal-open"
        );


        limpiarMensajeFormulario();
    }


    /* ========================================================
       MENSAJES
    ======================================================== */

    function mostrarMensajeFormulario(
        mensaje,
        tipo
    ) {

        cursoFormMessage.textContent =
            mensaje;


        cursoFormMessage.className =
            `user-form-message ${tipo}`;
    }


    function limpiarMensajeFormulario() {

        cursoFormMessage.textContent =
            "";


        cursoFormMessage.className =
            "user-form-message";
    }


    /* ========================================================
       BLOQUEAR FORMULARIO
    ======================================================== */

    function bloquearFormulario(
        bloquear
    ) {

        const controles =
            formCurso.querySelectorAll(
                "input, textarea, select, button"
            );


        controles.forEach(control => {

            control.disabled =
                bloquear;

        });


        if (!bloquear) {

            guardarCurso.disabled =
                false;
        }
    }


    /* ========================================================
       CREAR CURSO
    ======================================================== */

    async function crearCurso(
        datos
    ) {

        bloquearFormulario(true);


        try {

            const response =
                await fetch(
                    API_URL,
                    {
                        method: "POST",

                        credentials:
                            "same-origin",

                        headers: {
                            "Content-Type":
                                "application/json",

                            "Accept":
                                "application/json"
                        },

                        body:
                            JSON.stringify(
                                datos
                            )
                    }
                );


            const data =
                await obtenerRespuestaJSON(
                    response
                );


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "No fue posible crear el curso."
                );
            }


            mostrarMensajeFormulario(
                data.message ||
                "Curso creado correctamente.",
                "success"
            );


            await cargarCursos();


            setTimeout(() => {

                cerrarModalCurso();

            }, 700);


        } catch (error) {

            console.error(
                "Error al crear curso:",
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
       EDITAR CURSO
    ======================================================== */

    async function editarCurso(
        id,
        datos
    ) {

        bloquearFormulario(true);


        try {

            const response =
                await fetch(
                    API_URL,
                    {
                        method: "PUT",

                        credentials:
                            "same-origin",

                        headers: {
                            "Content-Type":
                                "application/json",

                            "Accept":
                                "application/json"
                        },

                        body:
                            JSON.stringify({
                                id,
                                ...datos
                            })
                    }
                );


            const data =
                await obtenerRespuestaJSON(
                    response
                );


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "No fue posible actualizar el curso."
                );
            }


            mostrarMensajeFormulario(
                data.message ||
                "Curso actualizado correctamente.",
                "success"
            );


            await cargarCursos();


            setTimeout(() => {

                cerrarModalCurso();

            }, 700);


        } catch (error) {

            console.error(
                "Error al actualizar curso:",
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

    async function cambiarEstado(
        curso
    ) {

        const nuevoEstado =
            curso.estado === "activo"
                ? "inactivo"
                : "activo";


        const accion =
            nuevoEstado === "activo"
                ? "activar"
                : "desactivar";


        const confirmado =
            confirm(
                `¿Deseas ${accion} el curso "${curso.titulo}"?`
            );


        if (!confirmado) {

            return;
        }


        try {

            const response =
                await fetch(
                    API_URL,
                    {
                        method: "PATCH",

                        credentials:
                            "same-origin",

                        headers: {
                            "Content-Type":
                                "application/json",

                            "Accept":
                                "application/json"
                        },

                        body:
                            JSON.stringify({
                                id:
                                    Number(
                                        curso.id
                                    ),

                                estado:
                                    nuevoEstado
                            })
                    }
                );


            const data =
                await obtenerRespuestaJSON(
                    response
                );


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "No fue posible cambiar el estado."
                );
            }


            await cargarCursos();


        } catch (error) {

            console.error(
                "Error al cambiar estado:",
                error
            );


            alert(
                error.message
            );
        }
    }


    /* ========================================================
       ELIMINAR CURSO
    ======================================================== */

    async function eliminarCurso(
        curso
    ) {

        const confirmado =
            confirm(
                `¿Estás seguro de eliminar el curso "${curso.titulo}"?\n\nEsta acción no se puede deshacer.`
            );


        if (!confirmado) {

            return;
        }


        try {

            const response =
                await fetch(
                    API_URL,
                    {
                        method: "DELETE",

                        credentials:
                            "same-origin",

                        headers: {
                            "Content-Type":
                                "application/json",

                            "Accept":
                                "application/json"
                        },

                        body:
                            JSON.stringify({
                                id:
                                    Number(
                                        curso.id
                                    )
                            })
                    }
                );


            const data =
                await obtenerRespuestaJSON(
                    response
                );


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "No fue posible eliminar el curso."
                );
            }


            await cargarCursos();


        } catch (error) {

            console.error(
                "Error al eliminar curso:",
                error
            );


            alert(
                error.message
            );
        }
    }


    /* ========================================================
       EVENTOS DE TABLA
    ======================================================== */

    tablaBody.addEventListener(
        "click",
        event => {

            const button =
                event.target.closest(
                    "button[data-action]"
                );


            if (!button) {

                return;
            }


            const id =
                Number(
                    button.dataset.id
                );


            const curso =
                cursos.find(
                    item =>
                        Number(item.id) === id
                );


            if (!curso) {

                return;
            }


            const action =
                button.dataset.action;


            if (
                action === "editar"
            ) {

                abrirModalEditarCurso(
                    curso
                );
            }


            if (
                action === "estado"
            ) {

                cambiarEstado(
                    curso
                );
            }


            if (
                action === "eliminar"
            ) {

                eliminarCurso(
                    curso
                );
            }
        }
    );


    /* ========================================================
       NUEVO CURSO
    ======================================================== */

    btnNuevoCurso.addEventListener(
        "click",
        abrirModalNuevoCurso
    );


    /* ========================================================
       CERRAR MODAL
    ======================================================== */

    cerrarCursoModal.addEventListener(
        "click",
        cerrarModalCurso
    );


    cancelarCurso.addEventListener(
        "click",
        cerrarModalCurso
    );


    const modalOverlay =
        cursoModal.querySelector(
            ".user-modal-overlay"
        );


    if (modalOverlay) {

        modalOverlay.addEventListener(
            "click",
            cerrarModalCurso
        );
    }


    /* ========================================================
       FORMULARIO
    ======================================================== */

    formCurso.addEventListener(
        "submit",
        async event => {

            event.preventDefault();


            limpiarMensajeFormulario();


            const id =
                cursoId.value.trim();


            const datos = {

                titulo:
                    cursoNombre.value.trim(),

                descripcion:
                    cursoDescripcion.value.trim(),

                nivel_dificultad:
                    cursoNivel.value,

                duracion:
                    Number(
                        cursoDuracion.value
                    ),

                numero_modulos:
                    Number(
                        cursoModulos.value
                    ),

                porcentaje_aprobacion:
                    Number(
                        cursoAprobacion.value
                    ),

                estado:
                    cursoEstado.value
            };


            /* ================================================
               VALIDACIONES FRONTEND
            ================================================= */

            if (
                !datos.titulo
            ) {

                mostrarMensajeFormulario(
                    "El nombre del curso es obligatorio.",
                    "error"
                );

                cursoNombre.focus();

                return;
            }


            if (
                datos.titulo.length < 3
            ) {

                mostrarMensajeFormulario(
                    "El nombre del curso debe tener al menos 3 caracteres.",
                    "error"
                );

                cursoNombre.focus();

                return;
            }


            if (
                !datos.nivel_dificultad
            ) {

                mostrarMensajeFormulario(
                    "Selecciona el nivel de dificultad.",
                    "error"
                );

                cursoNivel.focus();

                return;
            }


            if (
                !Number.isInteger(
                    datos.duracion
                ) ||
                datos.duracion < 1
            ) {

                mostrarMensajeFormulario(
                    "La duración debe ser un número entero mayor que 0.",
                    "error"
                );

                cursoDuracion.focus();

                return;
            }


            if (
                !Number.isInteger(
                    datos.numero_modulos
                ) ||
                datos.numero_modulos < 1
            ) {

                mostrarMensajeFormulario(
                    "El número de módulos debe ser un número entero mayor que 0.",
                    "error"
                );

                cursoModulos.focus();

                return;
            }


            if (
                !Number.isInteger(
                    datos.porcentaje_aprobacion
                ) ||
                datos.porcentaje_aprobacion < 1 ||
                datos.porcentaje_aprobacion > 100
            ) {

                mostrarMensajeFormulario(
                    "El porcentaje de aprobación debe estar entre 1 y 100.",
                    "error"
                );

                cursoAprobacion.focus();

                return;
            }


            /* ================================================
               GUARDAR
            ================================================= */

            if (id) {

                await editarCurso(
                    Number(id),
                    datos
                );

            } else {

                await crearCurso(
                    datos
                );
            }

        }
    );


    /* ========================================================
       BUSCADOR
    ======================================================== */

    buscarCurso.addEventListener(
        "input",
        () => {

            clearTimeout(
                temporizadorBusqueda
            );


            temporizadorBusqueda =
                setTimeout(
                    () => {

                        cargarCursos();

                    },
                    350
                );
        }
    );


    /* ========================================================
       FILTRO ESTADO
    ======================================================== */

    filtroEstadoCurso.addEventListener(
        "change",
        cargarCursos
    );


    /* ========================================================
       FILTRO NIVEL
    ======================================================== */

    filtroNivelCurso.addEventListener(
        "change",
        cargarCursos
    );


    /* ========================================================
       PÁGINA ANTERIOR
    ======================================================== */

    paginaAnteriorCurso.addEventListener(
        "click",
        () => {

            if (pagina > 1) {

                pagina--;

                aplicarPaginacion();
            }
        }
    );


    /* ========================================================
       PÁGINA SIGUIENTE
    ======================================================== */

    paginaSiguienteCurso.addEventListener(
        "click",
        () => {

            const totalPaginas =
                Math.max(
                    1,
                    Math.ceil(
                        cursosFiltrados.length /
                        cursosPorPagina
                    )
                );


            if (
                pagina <
                totalPaginas
            ) {

                pagina++;

                aplicarPaginacion();
            }
        }
    );


    /* ========================================================
       ESCAPE
    ======================================================== */

    document.addEventListener(
        "keydown",
        event => {

            if (
                event.key === "Escape" &&
                !cursoModal.hidden
            ) {

                cerrarModalCurso();
            }
        }
    );


    /* ========================================================
       INICIO
    ======================================================== */

    cargarCursos();

}


/* ============================================================
   INICIALIZACIÓN
   COMPATIBLE CON CARGA DINÁMICA DEL PANEL
============================================================ */

if (document.readyState === "loading") {

    document.addEventListener(
        "DOMContentLoaded",
        inicializarCursos
    );

} else {

    inicializarCursos();

}