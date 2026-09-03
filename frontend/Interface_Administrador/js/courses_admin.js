/* ============================================================
   SECURITY AWARENESS HUB
   COURSES ADMIN
   Gestión de cursos
   PHP + MySQL + JSON
============================================================ */

(() => {

    "use strict";


    /* ========================================================
       CONFIGURACIÓN
    ======================================================== */

    const API_URL =
        "../../backend/Api/courses.php";


    const COURSES_PER_PAGE = 6;


    /* ========================================================
       ESTADO
    ======================================================== */

    let cursos = [];

    let cursosFiltrados = [];

    let paginaActual = 1;


    /* ========================================================
       ELEMENTOS
    ======================================================== */

    let courseList;
    let courseSearch;
    let courseStatus;

    let courseLoadingStatus;
    let coursesShown;

    let previousPage;
    let currentPage;
    let nextPage;


    /* ========================================================
       OBTENER ELEMENTOS
    ======================================================== */

    function obtenerElementos() {

        courseList =
            document.getElementById("courseList");

        courseSearch =
            document.getElementById("courseSearch");

        courseStatus =
            document.getElementById("courseStatus");

        courseLoadingStatus =
            document.getElementById(
                "courseLoadingStatus"
            );

        coursesShown =
            document.getElementById("coursesShown");

        previousPage =
            document.getElementById("previousPage");

        currentPage =
            document.getElementById("currentPage");

        nextPage =
            document.getElementById("nextPage");

    }


    /* ========================================================
       VALIDAR ELEMENTOS
    ======================================================== */

    function validarElementos() {

        if (!courseList) {

            console.error(
                "courses_admin.js: No se encontró #courseList."
            );

            return false;
        }

        return true;
    }


    /* ========================================================
       RESPUESTA JSON
    ======================================================== */

    async function obtenerJSON(response) {

        const texto =
            await response.text();

        try {

            return JSON.parse(texto);

        } catch (error) {

            console.error(
                "Respuesta recibida del servidor:",
                texto
            );

            throw new Error(
                "El servidor no devolvió JSON válido."
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
       NORMALIZAR ESTADO
    ======================================================== */

    function normalizarEstado(estado) {

        if (
            estado === 1 ||
            estado === "1" ||
            estado === true ||
            estado === "activo"
        ) {

            return "activo";

        }

        return "inactivo";
    }


    /* ========================================================
       FORMATEAR FECHA
    ======================================================== */

    function formatearFecha(fecha) {

        if (!fecha) {

            return "-";

        }

        const fechaObjeto =
            new Date(
                String(fecha).replace(" ", "T")
            );

        if (
            Number.isNaN(
                fechaObjeto.getTime()
            )
        ) {

            return fecha;

        }

        return fechaObjeto.toLocaleDateString(
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

        courseList.innerHTML = `

            <div class="course-loading">

                <span>
                    Cargando cursos...
                </span>

            </div>

        `;


        if (courseLoadingStatus) {

            courseLoadingStatus.textContent =
                "Cargando...";

        }


        if (coursesShown) {

            coursesShown.textContent =
                "Cargando cursos...";

        }

    }


    /* ========================================================
       MOSTRAR ERROR
    ======================================================== */

    function mostrarError(mensaje) {

        courseList.innerHTML = `

            <div class="course-loading">

                <span>
                    ${escapeHTML(mensaje)}
                </span>

            </div>

        `;


        if (courseLoadingStatus) {

            courseLoadingStatus.textContent =
                "Error";

        }


        if (coursesShown) {

            coursesShown.textContent =
                "No se pudieron cargar los cursos.";

        }

    }


    /* ========================================================
       CARGAR CURSOS
    ======================================================== */

    async function cargarCursos() {

        mostrarCargando();


        try {

            const response =
                await fetch(
                    API_URL,
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
                await obtenerJSON(response);


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


            paginaActual = 1;


            aplicarFiltros();


            console.log(
                "Cursos cargados:",
                cursos
            );


        } catch (error) {

            console.error(
                "Error cargando cursos:",
                error
            );


            mostrarError(
                error.message
            );

        }

    }


    /* ========================================================
       FILTRAR CURSOS
    ======================================================== */

    function aplicarFiltros() {

        const texto =
            courseSearch
                ? courseSearch.value
                    .trim()
                    .toLowerCase()
                : "";


        const estado =
            courseStatus
                ? courseStatus.value
                : "todos";


        cursosFiltrados =
            cursos.filter(
                curso => {

                    const titulo =
                        String(
                            curso.titulo || ""
                        ).toLowerCase();


                    const descripcion =
                        String(
                            curso.descripcion || ""
                        ).toLowerCase();


                    const coincideTexto =
                        texto === "" ||
                        titulo.includes(texto) ||
                        descripcion.includes(texto);


                    const estadoCurso =
                        normalizarEstado(
                            curso.estado
                        );


                    const coincideEstado =
                        estado === "todos" ||
                        estado === estadoCurso;


                    return (
                        coincideTexto &&
                        coincideEstado
                    );

                }
            );


        paginaActual = 1;


        renderizarCursos();

    }


    /* ========================================================
       RENDERIZAR CURSOS
    ======================================================== */

    function renderizarCursos() {

        const total =
            cursosFiltrados.length;


        const totalPaginas =
            Math.max(
                1,
                Math.ceil(
                    total /
                    COURSES_PER_PAGE
                )
            );


        if (
            paginaActual >
            totalPaginas
        ) {

            paginaActual =
                totalPaginas;

        }


        const inicio =
            (
                paginaActual - 1
            ) *
            COURSES_PER_PAGE;


        const fin =
            inicio +
            COURSES_PER_PAGE;


        const cursosPagina =
            cursosFiltrados.slice(
                inicio,
                fin
            );


        if (cursosPagina.length === 0) {

            courseList.innerHTML = `

                <div class="course-loading">

                    <span>
                        No hay cursos para mostrar.
                    </span>

                </div>

            `;

        } else {

            courseList.innerHTML =
                cursosPagina
                    .map(
                        (
                            curso,
                            indice
                        ) =>
                            crearCursoHTML(
                                curso,
                                inicio +
                                indice
                            )
                    )
                    .join("");

        }


        actualizarPaginacion(
            total,
            totalPaginas,
            inicio,
            cursosPagina.length
        );

    }


    /* ========================================================
       CREAR HTML DE CURSO
    ======================================================== */

    function crearCursoHTML(
        curso,
        indice
    ) {

        const estado =
            normalizarEstado(
                curso.estado
            );


        const numero =
            String(
                indice + 1
            ).padStart(
                2,
                "0"
            );


        const titulo =
            escapeHTML(
                curso.titulo
            );


        const descripcion =
            escapeHTML(
                curso.descripcion ||
                "Sin descripción."
            );


        const nivel =
            escapeHTML(
                curso.nivel_dificultad ||
                "Básico"
            );


        const duracion =
            escapeHTML(
                curso.duracion ||
                "-"
            );


        const modulos =
            Number(
                curso.numero_modulos || 0
            );


        const aprobacion =
            Number(
                curso.porcentaje_aprobacion || 0
            );


        const fecha =
            formatearFecha(
                curso.fecha_creacion
            );


        const textoEstado =
            estado === "activo"
                ? "ACTIVO"
                : "INACTIVO";


        const textoBotonEstado =
            estado === "activo"
                ? "Desactivar"
                : "Activar";


        return `

            <article
                class="admin-course-item"
                data-id="${Number(curso.id)}"
                data-status="${estado}"
                data-name="${titulo}"
            >

                <div class="admin-course-number">
                    ${numero}
                </div>


                <div class="admin-course-info">

                    <span
                        class="course-status ${
                            estado === "activo"
                                ? "active"
                                : "inactive"
                        }"
                    >
                        ${textoEstado}
                    </span>


                    <h4>
                        ${titulo}
                    </h4>


                    <p>
                        ${descripcion}
                    </p>


                    <span class="course-meta">

                        ${nivel}

                        ·

                        ${duracion}

                        ·

                        ${modulos} módulos

                        ·

                        ${aprobacion}% aprobación

                    </span>


                    <span class="course-meta">

                        Creado:
                        ${fecha}

                    </span>

                </div>


                <div class="course-actions">

                    <button
                        type="button"
                        class="course-action-button edit"
                        data-action="editar"
                        data-id="${Number(curso.id)}"
                    >
                        Editar
                    </button>


                    <button
                        type="button"
                        class="course-action-button status"
                        data-action="estado"
                        data-id="${Number(curso.id)}"
                    >
                        ${textoBotonEstado}
                    </button>


                    <button
                        type="button"
                        class="course-action-button delete"
                        data-action="eliminar"
                        data-id="${Number(curso.id)}"
                    >
                        Eliminar
                    </button>

                </div>

            </article>

        `;

    }


    /* ========================================================
       PAGINACIÓN
    ======================================================== */

    function actualizarPaginacion(
        total,
        totalPaginas,
        inicio,
        cantidadPagina
    ) {

        if (coursesShown) {

            if (total === 0) {

                coursesShown.textContent =
                    "Mostrando 0 cursos";

            } else {

                coursesShown.textContent =
                    `Mostrando ${
                        inicio + 1
                    }-${
                        inicio +
                        cantidadPagina
                    } de ${
                        total
                    } cursos`;

            }

        }


        if (currentPage) {

            currentPage.textContent =
                paginaActual;

        }


        if (previousPage) {

            previousPage.disabled =
                paginaActual <= 1;

        }


        if (nextPage) {

            nextPage.disabled =
                paginaActual >=
                totalPaginas;

        }


        if (courseLoadingStatus) {

            courseLoadingStatus.textContent =
                `${total} ${
                    total === 1
                        ? "curso"
                        : "cursos"
                }`;

        }

    }


    /* ========================================================
       OBTENER CURSO
    ======================================================== */

    function obtenerCursoPorId(id) {

        return cursos.find(
            curso =>
                Number(curso.id) ===
                Number(id)
        );

    }


    /* ========================================================
       CREAR MODAL
    ======================================================== */

    function crearModalCurso() {

        let modal =
            document.getElementById(
                "courseModal"
            );


        if (modal) {

            return modal;

        }


        modal =
            document.createElement(
                "div"
            );


        modal.id =
            "courseModal";


        modal.className =
            "user-modal";


        modal.hidden =
            true;


        modal.innerHTML = `

            <div
                class="user-modal-overlay"
                id="courseModalOverlay"
            ></div>


            <div
                class="user-modal-content"
                role="dialog"
                aria-modal="true"
                aria-labelledby="courseModalTitle"
            >

                <div class="user-modal-header">

                    <div>

                        <span class="section-label">
                            ADMINISTRACIÓN
                        </span>

                        <h3 id="courseModalTitle">
                            Nuevo curso
                        </h3>

                    </div>


                    <button
                        type="button"
                        class="modal-close"
                        id="closeCourseModal"
                        aria-label="Cerrar"
                    >
                        ×
                    </button>

                </div>


                <form id="courseForm">

                    <input
                        type="hidden"
                        id="courseId"
                    >


                    <div class="form-group">

                        <label for="courseTitle">
                            Título del curso
                        </label>

                        <input
                            type="text"
                            id="courseTitle"
                            maxlength="150"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="courseDescription">
                            Descripción
                        </label>

                        <textarea
                            id="courseDescription"
                            rows="4"
                            required
                        ></textarea>

                    </div>


                    <div class="form-group">

                        <label for="courseLevel">
                            Nivel de dificultad
                        </label>

                        <select
                            id="courseLevel"
                            required
                        >

                            <option value="Básico">
                                Básico
                            </option>

                            <option value="Intermedio">
                                Intermedio
                            </option>

                            <option value="Avanzado">
                                Avanzado
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="courseDuration">
                            Duración
                        </label>

                        <input
                            type="text"
                            id="courseDuration"
                            placeholder="Ej: 2 horas"
                            maxlength="50"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="courseModules">
                            Número de módulos
                        </label>

                        <input
                            type="number"
                            id="courseModules"
                            min="1"
                            value="1"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="courseApproval">
                            Porcentaje de aprobación
                        </label>

                        <input
                            type="number"
                            id="courseApproval"
                            min="0"
                            max="100"
                            value="70"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="courseFormStatus">
                            Estado
                        </label>

                        <select
                            id="courseFormStatus"
                            required
                        >

                            <option value="1">
                                Activo
                            </option>

                            <option value="0">
                                Inactivo
                            </option>

                        </select>

                    </div>


                    <div
                        id="courseFormMessage"
                        class="user-form-message"
                        aria-live="polite"
                    ></div>


                    <div class="user-modal-actions">

                        <button
                            type="button"
                            class="modal-button secondary"
                            id="cancelCourse"
                        >
                            Cancelar
                        </button>


                        <button
                            type="submit"
                            class="modal-button primary"
                            id="saveCourse"
                        >
                            Crear curso
                        </button>

                    </div>

                </form>

            </div>

        `;


        document.body.appendChild(
            modal
        );


        document
            .getElementById(
                "closeCourseModal"
            )
            .addEventListener(
                "click",
                cerrarModalCurso
            );


        document
            .getElementById(
                "cancelCourse"
            )
            .addEventListener(
                "click",
                cerrarModalCurso
            );


        document
            .getElementById(
                "courseModalOverlay"
            )
            .addEventListener(
                "click",
                cerrarModalCurso
            );


        document
            .getElementById(
                "courseForm"
            )
            .addEventListener(
                "submit",
                guardarCurso
            );


        return modal;

    }


    /* ========================================================
       ABRIR MODAL NUEVO
    ======================================================== */

    function abrirNuevoCurso() {

        const modal =
            crearModalCurso();


        limpiarFormularioCurso();


        document
            .getElementById(
                "courseModalTitle"
            )
            .textContent =
                "Nuevo curso";


        document
            .getElementById(
                "saveCourse"
            )
            .textContent =
                "Crear curso";


        modal.hidden =
            false;


        document.body.style.overflow =
            "hidden";

    }


    /* ========================================================
       ABRIR MODAL EDITAR
    ======================================================== */

    function abrirEditarCurso(id) {

        const curso =
            obtenerCursoPorId(id);


        if (!curso) {

            alert(
                "No se encontró el curso."
            );

            return;

        }


        const modal =
            crearModalCurso();


        document
            .getElementById(
                "courseModalTitle"
            )
            .textContent =
                "Editar curso";


        document
            .getElementById(
                "saveCourse"
            )
            .textContent =
                "Guardar cambios";


        document
            .getElementById(
                "courseId"
            )
            .value =
                curso.id;


        document
            .getElementById(
                "courseTitle"
            )
            .value =
                curso.titulo || "";


        document
            .getElementById(
                "courseDescription"
            )
            .value =
                curso.descripcion || "";


        document
            .getElementById(
                "courseLevel"
            )
            .value =
                curso.nivel_dificultad ||
                "Básico";


        document
            .getElementById(
                "courseDuration"
            )
            .value =
                curso.duracion || "";


        document
            .getElementById(
                "courseModules"
            )
            .value =
                curso.numero_modulos || 1;


        document
            .getElementById(
                "courseApproval"
            )
            .value =
                curso.porcentaje_aprobacion ||
                70;


        document
            .getElementById(
                "courseFormStatus"
            )
            .value =
                normalizarEstado(
                    curso.estado
                ) === "activo"
                    ? "1"
                    : "0";


        mostrarMensajeFormulario(
            "",
            ""
        );


        modal.hidden =
            false;


        document.body.style.overflow =
            "hidden";

    }


    /* ========================================================
       LIMPIAR FORMULARIO
    ======================================================== */

    function limpiarFormularioCurso() {

        const form =
            document.getElementById(
                "courseForm"
            );


        if (form) {

            form.reset();

        }


        document
            .getElementById(
                "courseId"
            )
            .value =
                "";


        document
            .getElementById(
                "courseModules"
            )
            .value =
                "1";


        document
            .getElementById(
                "courseApproval"
            )
            .value =
                "70";


        document
            .getElementById(
                "courseFormStatus"
            )
            .value =
                "1";


        mostrarMensajeFormulario(
            "",
            ""
        );

    }


    /* ========================================================
       CERRAR MODAL
    ======================================================== */

    function cerrarModalCurso() {

        const modal =
            document.getElementById(
                "courseModal"
            );


        if (modal) {

            modal.hidden =
                true;

        }


        document.body.style.overflow =
            "";

    }


    /* ========================================================
       MENSAJE FORMULARIO
    ======================================================== */

    function mostrarMensajeFormulario(
        mensaje,
        tipo
    ) {

        const elemento =
            document.getElementById(
                "courseFormMessage"
            );


        if (!elemento) {

            return;

        }


        elemento.textContent =
            mensaje || "";


        elemento.className =
            "user-form-message";


        if (tipo) {

            elemento.classList.add(
                tipo
            );

        }

    }


    /* ========================================================
       OBTENER DATOS FORMULARIO
    ======================================================== */

    function obtenerDatosFormulario() {

        return {

            titulo:
                document
                    .getElementById(
                        "courseTitle"
                    )
                    .value
                    .trim(),

            descripcion:
                document
                    .getElementById(
                        "courseDescription"
                    )
                    .value
                    .trim(),

            nivel_dificultad:
                document
                    .getElementById(
                        "courseLevel"
                    )
                    .value,

            duracion:
                document
                    .getElementById(
                        "courseDuration"
                    )
                    .value
                    .trim(),

            numero_modulos:
                Number(
                    document
                        .getElementById(
                            "courseModules"
                        )
                        .value
                ),

            porcentaje_aprobacion:
                Number(
                    document
                        .getElementById(
                            "courseApproval"
                        )
                        .value
                ),

            estado:
                Number(
                    document
                        .getElementById(
                            "courseFormStatus"
                        )
                        .value
                )

        };

    }


    /* ========================================================
       VALIDAR DATOS
    ======================================================== */

    function validarDatosCurso(datos) {

        if (!datos.titulo) {

            return "El título del curso es obligatorio.";

        }


        if (!datos.descripcion) {

            return "La descripción es obligatoria.";

        }


        if (!datos.duracion) {

            return "La duración es obligatoria.";

        }


        if (
            !Number.isInteger(
                datos.numero_modulos
            ) ||
            datos.numero_modulos < 1
        ) {

            return "El número de módulos debe ser mayor que 0.";

        }


        if (
            datos.porcentaje_aprobacion < 0 ||
            datos.porcentaje_aprobacion > 100
        ) {

            return "El porcentaje de aprobación debe estar entre 0 y 100.";

        }


        return null;

    }


    /* ========================================================
       CREAR / EDITAR CURSO
    ======================================================== */

    async function guardarCurso(event) {

        event.preventDefault();


        const id =
            document
                .getElementById(
                    "courseId"
                )
                .value;


        const datos =
            obtenerDatosFormulario();


        const error =
            validarDatosCurso(
                datos
            );


        if (error) {

            mostrarMensajeFormulario(
                error,
                "error"
            );

            return;

        }


        const boton =
            document.getElementById(
                "saveCourse"
            );


        boton.disabled =
            true;


        boton.textContent =
            id
                ? "Guardando..."
                : "Creando...";


        try {

            const response =
                await fetch(
                    API_URL,
                    {

                        method:
                            id
                                ? "PUT"
                                : "POST",

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
                                id
                                    ? {
                                        id:
                                            Number(id),
                                        ...datos
                                    }
                                    : datos
                            )

                    }
                );


            const data =
                await obtenerJSON(
                    response
                );


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "No fue posible guardar el curso."
                );

            }


            mostrarMensajeFormulario(
                data.message ||
                (
                    id
                        ? "Curso actualizado correctamente."
                        : "Curso creado correctamente."
                ),
                "success"
            );


            await cargarCursos();


            setTimeout(
                () => {

                    cerrarModalCurso();

                },
                700
            );


        } catch (error) {

            console.error(
                "Error guardando curso:",
                error
            );


            mostrarMensajeFormulario(
                error.message,
                "error"
            );

        } finally {

            boton.disabled =
                false;

            boton.textContent =
                id
                    ? "Guardar cambios"
                    : "Crear curso";

        }

    }


    /* ========================================================
       CAMBIAR ESTADO
    ======================================================== */

    async function cambiarEstadoCurso(id) {

        const curso =
            obtenerCursoPorId(id);


        if (!curso) {

            return;

        }


        const estadoActual =
            normalizarEstado(
                curso.estado
            );


        const nuevoEstado =
            estadoActual === "activo"
                ? 0
                : 1;


        const accion =
            nuevoEstado === 1
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

                        method:
                            "PATCH",

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
                                    Number(id),

                                estado:
                                    nuevoEstado

                            })

                    }
                );


            const data =
                await obtenerJSON(
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
                "Error cambiando estado:",
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

    async function eliminarCurso(id) {

        const curso =
            obtenerCursoPorId(id);


        if (!curso) {

            return;

        }


        const confirmado =
            confirm(
                `¿Estás seguro de eliminar el curso "${curso.titulo}"?`
            );


        if (!confirmado) {

            return;

        }


        try {

            const response =
                await fetch(
                    API_URL,
                    {

                        method:
                            "DELETE",

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
                                    Number(id)

                            })

                        }

                    );

            const data =
                await obtenerJSON(
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
                "Error eliminando curso:",
                error
            );


            alert(
                error.message
            );

        }

    }


    /* ========================================================
       EVENTOS LISTA
    ======================================================== */

    function configurarEventosLista() {

        if (!courseList) {

            return;

        }


        courseList.addEventListener(
            "click",
            event => {

                const boton =
                    event.target.closest(
                        "[data-action]"
                    );


                if (!boton) {

                    return;

                }


                const accion =
                    boton.dataset.action;


                const id =
                    Number(
                        boton.dataset.id
                    );


                if (!id) {

                    return;

                }


                if (
                    accion ===
                    "editar"
                ) {

                    abrirEditarCurso(
                        id
                    );

                }


                if (
                    accion ===
                    "estado"
                ) {

                    cambiarEstadoCurso(
                        id
                    );

                }


                if (
                    accion ===
                    "eliminar"
                ) {

                    eliminarCurso(
                        id
                    );

                }

            }
        );

    }


    /* ========================================================
       PAGINACIÓN
    ======================================================== */

    function configurarPaginacion() {

        if (previousPage) {

            previousPage.addEventListener(
                "click",
                () => {

                    if (
                        paginaActual <= 1
                    ) {

                        return;

                    }


                    paginaActual--;

                    renderizarCursos();

                }
            );

        }


        if (nextPage) {

            nextPage.addEventListener(
                "click",
                () => {

                    const totalPaginas =
                        Math.max(
                            1,
                            Math.ceil(
                                cursosFiltrados.length /
                                COURSES_PER_PAGE
                            )
                        );


                    if (
                        paginaActual >=
                        totalPaginas
                    ) {

                        return;

                    }


                    paginaActual++;

                    renderizarCursos();

                }
            );

        }

    }


    /* ========================================================
       BOTÓN NUEVO CURSO
    ======================================================== */

    function configurarNuevoCurso() {

        const boton =
            document.getElementById(
                "newCourseButton"
            );


        if (!boton) {

            console.warn(
                "No se encontró #newCourseButton."
            );

            return;

        }


        boton.addEventListener(
            "click",
            abrirNuevoCurso
        );

    }


    /* ========================================================
       FILTROS
    ======================================================== */

    function configurarFiltros() {

        if (courseSearch) {

            courseSearch.addEventListener(
                "input",
                aplicarFiltros
            );

        }


        if (courseStatus) {

            courseStatus.addEventListener(
                "change",
                aplicarFiltros
            );

        }

    }


    /* ========================================================
       ESCAPE PARA CERRAR MODAL
    ======================================================== */

    function configurarTeclaEscape() {

        document.addEventListener(
            "keydown",
            event => {

                if (
                    event.key ===
                    "Escape"
                ) {

                    cerrarModalCurso();

                }

            }
        );

    }


    /* ========================================================
       INICIALIZAR
    ======================================================== */

    function inicializarCursos() {

        obtenerElementos();


        if (
            !validarElementos()
        ) {

            return;

        }


        configurarNuevoCurso();

        configurarFiltros();

        configurarEventosLista();

        configurarPaginacion();

        configurarTeclaEscape();


        crearModalCurso();


        cargarCursos();


        console.log(
            "courses_admin.js iniciado correctamente."
        );

    }


    /* ========================================================
       CONTROL DE CARGA
    ======================================================== */

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            inicializarCursos
        );

    } else {

        inicializarCursos();

    }


})();