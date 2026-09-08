/* ============================================================
   SECURITY AWARENESS HUB
   EVALUACIONES - PANEL ADMINISTRADOR
   evaluaciones.js
============================================================ */

(function () {

    /* ========================================================
       RUTAS DEL BACKEND
    ======================================================== */

    const EVALUACIONES_API =
        "../../../backend/Api/evaluaciones.php";

    const CURSOS_API =
        "../../../backend/Api/courses.php";


    /* ========================================================
       ESTADO EN MEMORIA
    ======================================================== */

    let evaluaciones = [];
    let cursosDisponibles = [];
    let contadorPreguntas = 0;

    let paginaActual = 1;
    const EVALUACIONES_POR_PAGINA = 8;


    /* ========================================================
       ELEMENTOS PRINCIPALES
    ======================================================== */

    const tableBody =
        document.getElementById("evaluacionesTableBody");

    const emptyState =
        document.getElementById("evaluacionesEmptyState");

    const contadorEvaluaciones =
        document.getElementById("contadorEvaluaciones");

    const evaluacionesMostradas =
        document.getElementById("evaluacionesMostradas");

    const totalEvaluacionesEl =
        document.getElementById("totalEvaluaciones");

    const totalPreguntasEl =
        document.getElementById("totalPreguntas");

    const evaluacionesActivasEl =
        document.getElementById("evaluacionesActivas");

    const buscarInput =
        document.getElementById("buscarEvaluacion");

    const filtroEstado =
        document.getElementById("filtroEstadoEvaluacion");

    const filtroCurso =
        document.getElementById("filtroCursoEvaluacion");

    const btnPaginaAnterior =
        document.getElementById("paginaAnteriorEvaluacion");

    const btnPaginaSiguiente =
        document.getElementById("paginaSiguienteEvaluacion");

    const paginaActualBtn =
        document.getElementById("paginaActualEvaluacion");


    /* ========================================================
       ELEMENTOS DEL MODAL
    ======================================================== */

    const modal =
        document.getElementById("evaluacionModal");

    const modalTitulo =
        document.getElementById("modalEvaluacionTitulo");

    const btnNueva =
        document.getElementById("btnNuevaEvaluacion");

    const btnCerrarModal =
        document.getElementById("cerrarEvaluacionModal");

    const btnCancelar =
        document.getElementById("cancelarEvaluacion");

    const form =
        document.getElementById("formEvaluacion");

    const formMessage =
        document.getElementById("evaluacionFormMessage");

    const inputId =
        document.getElementById("evaluacionId");

    const inputNombre =
        document.getElementById("evaluacionNombre");

    const selectCurso =
        document.getElementById("evaluacionCurso");

    const inputDescripcion =
        document.getElementById("evaluacionDescripcion");

    const inputNumeroPreguntas =
        document.getElementById("evaluacionPreguntas");

    const selectEstado =
        document.getElementById("evaluacionEstado");

    const btnAgregarPregunta =
        document.getElementById("btnAgregarPregunta");

    const preguntasContainer =
        document.getElementById("preguntasContainer");

    const preguntasEmptyHint =
        document.getElementById("preguntasEmptyHint");

    const btnGuardar =
        document.getElementById("guardarEvaluacion");


    /* ========================================================
       UTILIDADES
    ======================================================== */

    function escaparHtml(texto) {

        const div = document.createElement("div");

        div.textContent = texto ?? "";

        return div.innerHTML;

    }

    function formatearFecha(fechaISO) {

        if (!fechaISO) {
            return "-";
        }

        const fecha = new Date(fechaISO);

        if (isNaN(fecha.getTime())) {
            return "-";
        }

        return fecha.toLocaleDateString(
            "es-CO",
            {
                day: "2-digit",
                month: "short",
                year: "numeric"
            }
        );

    }

    function mostrarMensajeForm(texto, esError = false) {

        formMessage.textContent = texto;

        formMessage.style.color = esError
            ? "var(--danger)"
            : "var(--green)";

    }


    /* ========================================================
       CARGAR CURSOS (para los <select>)
    ======================================================== */

    async function cargarCursos() {

        try {

            const response = await fetch(CURSOS_API, {
                method: "GET"
            });

            const data = await response.json();

            if (!data.success) {
                return;
            }

            cursosDisponibles = data.cursos || [];

            /* SELECT DEL MODAL */

            selectCurso.innerHTML =
                '<option value="">Seleccione un curso</option>';

            cursosDisponibles.forEach(curso => {

                const option = document.createElement("option");

                option.value = curso.id;

                option.textContent = curso.titulo;

                selectCurso.appendChild(option);

            });

            /* SELECT DE FILTRO */

            filtroCurso.innerHTML =
                '<option value="todos">Todos los cursos</option>';

            cursosDisponibles.forEach(curso => {

                const option = document.createElement("option");

                option.value = curso.id;

                option.textContent = curso.titulo;

                filtroCurso.appendChild(option);

            });

        } catch (error) {

            console.error(
                "No se pudieron cargar los cursos:",
                error
            );

        }

    }


    /* ========================================================
       CARGAR EVALUACIONES
    ======================================================== */

    async function cargarEvaluaciones() {

        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="loading-users">
                    Cargando evaluaciones...
                </td>
            </tr>
        `;

        try {

            const response = await fetch(EVALUACIONES_API, {
                method: "GET"
            });

            const data = await response.json();

            if (!data.success) {

                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="loading-users">
                            ${escaparHtml(data.message || "No se pudieron cargar las evaluaciones.")}
                        </td>
                    </tr>
                `;

                return;

            }

            evaluaciones = data.evaluaciones || [];

            actualizarIndicadores();

            paginaActual = 1;

            renderizarTabla();

        } catch (error) {

            console.error(
                "Error al cargar evaluaciones:",
                error
            );

            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="loading-users">
                        Error de conexión con el servidor.
                    </td>
                </tr>
            `;

        }

    }


    /* ========================================================
       INDICADORES SUPERIORES
    ======================================================== */

    function actualizarIndicadores() {

        const totalPreguntas = evaluaciones.reduce(
            (acumulado, evaluacion) =>
                acumulado + (parseInt(evaluacion.total_preguntas) || 0),
            0
        );

        const activas = evaluaciones.filter(
            evaluacion => evaluacion.estado === "activo"
        ).length;

        totalEvaluacionesEl.textContent = evaluaciones.length;

        totalPreguntasEl.textContent = totalPreguntas;

        evaluacionesActivasEl.textContent = activas;

    }


    /* ========================================================
       FILTRAR EVALUACIONES (según buscador y selects)
    ======================================================== */

    function obtenerEvaluacionesFiltradas() {

        const busqueda = buscarInput.value.trim().toLowerCase();

        const estadoSeleccionado = filtroEstado.value;

        const cursoSeleccionado = filtroCurso.value;

        return evaluaciones.filter(evaluacion => {

            const coincideBusqueda =
                busqueda === "" ||
                evaluacion.titulo.toLowerCase().includes(busqueda) ||
                (evaluacion.curso_titulo || "").toLowerCase().includes(busqueda);

            const coincideEstado =
                estadoSeleccionado === "todos" ||
                evaluacion.estado === estadoSeleccionado;

            const coincideCurso =
                cursoSeleccionado === "todos" ||
                String(evaluacion.curso_id) === String(cursoSeleccionado);

            return coincideBusqueda && coincideEstado && coincideCurso;

        });

    }


    /* ========================================================
       RENDERIZAR TABLA (con paginación)
    ======================================================== */

    function renderizarTabla() {

        const filtradas = obtenerEvaluacionesFiltradas();

        const totalPaginas = Math.max(
            1,
            Math.ceil(filtradas.length / EVALUACIONES_POR_PAGINA)
        );

        if (paginaActual > totalPaginas) {
            paginaActual = totalPaginas;
        }

        const inicio = (paginaActual - 1) * EVALUACIONES_POR_PAGINA;

        const visibles = filtradas.slice(
            inicio,
            inicio + EVALUACIONES_POR_PAGINA
        );

        contadorEvaluaciones.textContent =
            `${filtradas.length} evaluaciones`;

        if (filtradas.length === 0) {

            tableBody.innerHTML = "";

            emptyState.hidden = false;

            evaluacionesMostradas.textContent =
                "No se encontraron evaluaciones.";

        } else {

            emptyState.hidden = true;

            tableBody.innerHTML = visibles.map(evaluacion => {

                const badgeClase = evaluacion.estado === "activo"
                    ? "status-active"
                    : "status-inactive";

                const badgeTexto = evaluacion.estado === "activo"
                    ? "Activa"
                    : "Inactiva";

                const accionEstadoTexto = evaluacion.estado === "activo"
                    ? "Desactivar"
                    : "Activar";

                return `
                    <tr>
                        <td>
                            <strong>${escaparHtml(evaluacion.titulo)}</strong>
                        </td>
                        <td>
                            ${escaparHtml(evaluacion.curso_titulo)}
                        </td>
                        <td>
                            ${evaluacion.total_preguntas || 0}
                        </td>
                        <td>
                            <span class="status-badge ${badgeClase}">
                                ${badgeTexto}
                            </span>
                        </td>
                        <td>
                            ${formatearFecha(evaluacion.fecha_creacion)}
                        </td>
                        <td>
                            <div class="table-actions">

                                <button
                                    type="button"
                                    class="table-action"
                                    data-accion="editar"
                                    data-id="${evaluacion.id}"
                                    title="Editar">
                                    ✎
                                </button>

                                <button
                                    type="button"
                                    class="table-action"
                                    data-accion="estado"
                                    data-id="${evaluacion.id}"
                                    data-estado-actual="${evaluacion.estado}"
                                    title="${accionEstadoTexto}">
                                    ${evaluacion.estado === "activo" ? "⏸" : "▶"}
                                </button>

                                <button
                                    type="button"
                                    class="table-action"
                                    data-accion="eliminar"
                                    data-id="${evaluacion.id}"
                                    title="Eliminar">
                                    ✕
                                </button>

                            </div>
                        </td>
                    </tr>
                `;

            }).join("");

            evaluacionesMostradas.textContent =
                `Mostrando ${visibles.length} de ${filtradas.length} evaluaciones`;

        }

        paginaActualBtn.textContent = paginaActual;

        btnPaginaAnterior.disabled = paginaActual <= 1;

        btnPaginaSiguiente.disabled = paginaActual >= totalPaginas;

    }


    /* ========================================================
       PAGINACIÓN - EVENTOS
    ======================================================== */

    btnPaginaAnterior.addEventListener("click", () => {

        if (paginaActual > 1) {

            paginaActual--;

            renderizarTabla();

        }

    });

    btnPaginaSiguiente.addEventListener("click", () => {

        paginaActual++;

        renderizarTabla();

    });


    /* ========================================================
       FILTROS - EVENTOS
    ======================================================== */

    buscarInput.addEventListener("input", () => {

        paginaActual = 1;

        renderizarTabla();

    });

    filtroEstado.addEventListener("change", () => {

        paginaActual = 1;

        renderizarTabla();

    });

    filtroCurso.addEventListener("change", () => {

        paginaActual = 1;

        renderizarTabla();

    });


    /* ========================================================
       CREAR BLOQUE DE PREGUNTA (HTML dinámico)
    ======================================================== */

    function crearBloquePregunta() {

        contadorPreguntas++;

        const grupoRadio = `correcta-pregunta-${contadorPreguntas}`;

        const bloque = document.createElement("div");

        bloque.className = "pregunta-block";

        bloque.dataset.grupoRadio = grupoRadio;

        bloque.innerHTML = `
            <div class="pregunta-block-header">

                <span class="pregunta-numero">
                    Pregunta
                </span>

                <button
                    type="button"
                    class="btn-eliminar-pregunta"
                    title="Eliminar pregunta">
                    ✕
                </button>

            </div>

            <input
                type="text"
                class="pregunta-enunciado"
                placeholder="Escribe el enunciado de la pregunta"
                maxlength="255">

            <div class="opciones-list"></div>

            <button
                type="button"
                class="btn-agregar-opcion">
                + Agregar opción
            </button>

            <div class="pregunta-error"></div>
        `;

        const opcionesList =
            bloque.querySelector(".opciones-list");

        /* Cada pregunta nueva inicia con 2 opciones vacías */

        opcionesList.appendChild(
            crearFilaOpcion(grupoRadio)
        );

        opcionesList.appendChild(
            crearFilaOpcion(grupoRadio)
        );

        /* BOTÓN ELIMINAR PREGUNTA */

        bloque
            .querySelector(".btn-eliminar-pregunta")
            .addEventListener("click", () => {

                bloque.remove();

                actualizarNumeracionPreguntas();
                actualizarNumeroPreguntasInput();

            });

        /* BOTÓN AGREGAR OPCIÓN */

        bloque
            .querySelector(".btn-agregar-opcion")
            .addEventListener("click", () => {

                opcionesList.appendChild(
                    crearFilaOpcion(grupoRadio)
                );

            });

        return bloque;

    }


    /* ========================================================
       CREAR FILA DE OPCIÓN (HTML dinámico)
    ======================================================== */

    function crearFilaOpcion(nombreGrupoRadio) {

        const fila = document.createElement("div");

        fila.className = "opcion-row";

        fila.innerHTML = `
            <input
                type="radio"
                name="${nombreGrupoRadio}"
                title="Marcar como respuesta correcta">

            <input
                type="text"
                class="opcion-texto"
                placeholder="Texto de la opción"
                maxlength="255">

            <button
                type="button"
                class="btn-eliminar-opcion"
                title="Eliminar opción">
                ✕
            </button>
        `;

        fila
            .querySelector(".btn-eliminar-opcion")
            .addEventListener("click", () => {

                const listaOpciones = fila.parentElement;

                /* No permitir menos de 2 opciones */

                if (listaOpciones.children.length <= 2) {
                    return;
                }

                fila.remove();

            });

        return fila;

    }


    /* ========================================================
       ACTUALIZAR NUMERACIÓN VISUAL DE PREGUNTAS
    ======================================================== */

    function actualizarNumeracionPreguntas() {

        const bloques =
            preguntasContainer.querySelectorAll(".pregunta-block");

        bloques.forEach((bloque, index) => {

            bloque.querySelector(".pregunta-numero").textContent =
                `Pregunta ${index + 1}`;

        });

        preguntasEmptyHint.hidden = bloques.length > 0;

    }


    /* ========================================================
       ACTUALIZAR CAMPO "NÚMERO DE PREGUNTAS" (solo lectura)
    ======================================================== */

    function actualizarNumeroPreguntasInput() {

        const total =
            preguntasContainer.querySelectorAll(".pregunta-block").length;

        inputNumeroPreguntas.value = total;

    }


    /* ========================================================
       BOTÓN "+ AGREGAR PREGUNTA"
    ======================================================== */

    btnAgregarPregunta.addEventListener("click", () => {

        const bloque = crearBloquePregunta();

        preguntasContainer.appendChild(bloque);

        actualizarNumeracionPreguntas();
        actualizarNumeroPreguntasInput();

    });


    /* ========================================================
       LIMPIAR FORMULARIO / PREGUNTAS
    ======================================================== */

    function limpiarFormulario() {

        form.reset();

        inputId.value = "";

        preguntasContainer.innerHTML = "";

        contadorPreguntas = 0;

        actualizarNumeracionPreguntas();
        actualizarNumeroPreguntasInput();

        mostrarMensajeForm("");

    }


    /* ========================================================
       ABRIR MODAL - NUEVA EVALUACIÓN
    ======================================================== */

    btnNueva.addEventListener("click", () => {

        limpiarFormulario();

        modalTitulo.textContent = "Nueva evaluación";

        btnGuardar.textContent = "Crear evaluación";

        modal.hidden = false;

    });


    /* ========================================================
       CERRAR MODAL
    ======================================================== */

    function cerrarModal() {

        modal.hidden = true;

        limpiarFormulario();

    }

    btnCerrarModal.addEventListener("click", cerrarModal);
    btnCancelar.addEventListener("click", cerrarModal);


    /* ========================================================
       ABRIR MODAL - EDITAR EVALUACIÓN
    ======================================================== */

    async function abrirModalEdicion(id) {

        try {

            const response = await fetch(
                `${EVALUACIONES_API}?id=${id}`,
                { method: "GET" }
            );

            const data = await response.json();

            if (!data.success) {

                alert(data.message || "No se pudo cargar la evaluación.");

                return;

            }

            const evaluacion = data.evaluacion;

            limpiarFormulario();

            inputId.value = evaluacion.id;
            inputNombre.value = evaluacion.titulo;
            selectCurso.value = evaluacion.curso_id;
            inputDescripcion.value = evaluacion.descripcion;
            selectEstado.value = evaluacion.estado;

            evaluacion.preguntas.forEach(pregunta => {

                const bloque = crearBloquePregunta();

                /* Reemplazar las 2 opciones vacías por defecto */

                const opcionesList =
                    bloque.querySelector(".opciones-list");

                opcionesList.innerHTML = "";

                bloque.querySelector(".pregunta-enunciado").value =
                    pregunta.enunciado;

                pregunta.opciones.forEach(opcion => {

                    const fila = crearFilaOpcion(
                        bloque.dataset.grupoRadio
                    );

                    fila.querySelector(".opcion-texto").value =
                        opcion.texto;

                    if (parseInt(opcion.es_correcta) === 1) {

                        fila.querySelector('input[type="radio"]').checked =
                            true;

                    }

                    opcionesList.appendChild(fila);

                });

                preguntasContainer.appendChild(bloque);

            });

            actualizarNumeracionPreguntas();
            actualizarNumeroPreguntasInput();

            modalTitulo.textContent = "Editar evaluación";

            btnGuardar.textContent = "Guardar cambios";

            modal.hidden = false;

        } catch (error) {

            console.error(
                "Error al cargar evaluación para editar:",
                error
            );

            alert("Error de conexión con el servidor.");

        }

    }


    /* ========================================================
       RECOLECTAR DATOS DE PREGUNTAS DESDE EL DOM
       (y validar en el frontend antes de enviar)
    ======================================================== */

    function recolectarPreguntas() {

        const bloques =
            preguntasContainer.querySelectorAll(".pregunta-block");

        const preguntas = [];

        let errorGeneral = "";

        bloques.forEach((bloque, index) => {

            const numero = index + 1;

            const errorEl = bloque.querySelector(".pregunta-error");

            errorEl.textContent = "";

            const enunciado =
                bloque.querySelector(".pregunta-enunciado").value.trim();

            if (enunciado === "") {

                errorEl.textContent =
                    "Esta pregunta necesita un enunciado.";

                errorGeneral = `La pregunta ${numero} no tiene enunciado.`;

                return;

            }

            const filasOpciones =
                bloque.querySelectorAll(".opcion-row");

            if (filasOpciones.length < 2) {

                errorEl.textContent =
                    "Se necesitan mínimo 2 opciones.";

                errorGeneral = `La pregunta ${numero} necesita mínimo 2 opciones.`;

                return;

            }

            const opciones = [];

            let correctas = 0;
            let opcionVacia = false;

            filasOpciones.forEach(fila => {

                const texto =
                    fila.querySelector(".opcion-texto").value.trim();

                const esCorrecta =
                    fila.querySelector('input[type="radio"]').checked;

                if (texto === "") {

                    opcionVacia = true;

                }

                if (esCorrecta) {

                    correctas++;

                }

                opciones.push({
                    texto: texto,
                    es_correcta: esCorrecta ? 1 : 0
                });

            });

            if (opcionVacia) {

                errorEl.textContent =
                    "Ninguna opción puede estar vacía.";

                errorGeneral = `La pregunta ${numero} tiene una opción vacía.`;

                return;

            }

            if (correctas !== 1) {

                errorEl.textContent =
                    "Marca exactamente una opción como correcta.";

                errorGeneral =
                    `La pregunta ${numero} debe tener exactamente una opción correcta.`;

                return;

            }

            preguntas.push({
                enunciado: enunciado,
                opciones: opciones
            });

        });

        if (bloques.length === 0) {

            errorGeneral =
                "Agrega al menos una pregunta antes de guardar.";

        }

        return { preguntas, errorGeneral };

    }


    /* ========================================================
       GUARDAR (CREAR O EDITAR) EVALUACIÓN
    ======================================================== */

    form.addEventListener("submit", async event => {

        event.preventDefault();

        mostrarMensajeForm("");

        const id = inputId.value;

        const cuerpo = {
            curso_id: selectCurso.value,
            titulo: inputNombre.value.trim(),
            descripcion: inputDescripcion.value.trim(),
            porcentaje_aprobacion: 70,
            estado: selectEstado.value
        };

        if (!cuerpo.curso_id) {

            mostrarMensajeForm(
                "Selecciona el curso asociado.",
                true
            );

            return;

        }

        if (cuerpo.titulo === "") {

            mostrarMensajeForm(
                "El nombre de la evaluación es obligatorio.",
                true
            );

            return;

        }

        const { preguntas, errorGeneral } = recolectarPreguntas();

        if (errorGeneral !== "") {

            mostrarMensajeForm(errorGeneral, true);

            return;

        }

        cuerpo.preguntas = preguntas;

        if (id) {
            cuerpo.id = parseInt(id);
        }

        btnGuardar.disabled = true;

        btnGuardar.textContent = "Guardando...";

        try {

            const response = await fetch(EVALUACIONES_API, {
                method: id ? "PUT" : "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(cuerpo)
            });

            const data = await response.json();

            if (!data.success) {

                mostrarMensajeForm(
                    data.message || "No fue posible guardar la evaluación.",
                    true
                );

                btnGuardar.disabled = false;

                btnGuardar.textContent = id
                    ? "Guardar cambios"
                    : "Crear evaluación";

                return;

            }

            mostrarMensajeForm(data.message);

            await cargarEvaluaciones();

            setTimeout(cerrarModal, 700);

        } catch (error) {

            console.error(
                "Error al guardar evaluación:",
                error
            );

            mostrarMensajeForm(
                "Error de conexión con el servidor.",
                true
            );

        } finally {

            btnGuardar.disabled = false;

            btnGuardar.textContent = id
                ? "Guardar cambios"
                : "Crear evaluación";

        }

    });


    /* ========================================================
       ACCIONES DE TABLA (editar / estado / eliminar)
    ======================================================== */

    tableBody.addEventListener("click", async event => {

        const boton = event.target.closest("[data-accion]");

        if (!boton) {
            return;
        }

        const accion = boton.dataset.accion;
        const id = parseInt(boton.dataset.id);

        if (accion === "editar") {

            abrirModalEdicion(id);

        }

        if (accion === "estado") {

            const estadoActual = boton.dataset.estadoActual;

            const nuevoEstado = estadoActual === "activo" ? 0 : 1;

            try {

                const response = await fetch(EVALUACIONES_API, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        id: id,
                        estado: nuevoEstado
                    })
                });

                const data = await response.json();

                if (!data.success) {

                    alert(data.message || "No fue posible cambiar el estado.");

                    return;

                }

                await cargarEvaluaciones();

            } catch (error) {

                console.error(
                    "Error al cambiar estado:",
                    error
                );

                alert("Error de conexión con el servidor.");

            }

        }

        if (accion === "eliminar") {

            const confirmar = confirm(
                "¿Seguro que deseas eliminar esta evaluación? Esta acción no se puede deshacer."
            );

            if (!confirmar) {
                return;
            }

            try {

                const response = await fetch(EVALUACIONES_API, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ id: id })
                });

                const data = await response.json();

                if (!data.success) {

                    alert(data.message || "No fue posible eliminar la evaluación.");

                    return;

                }

                await cargarEvaluaciones();

            } catch (error) {

                console.error(
                    "Error al eliminar evaluación:",
                    error
                );

                alert("Error de conexión con el servidor.");

            }

        }

    });


    /* ========================================================
       INICIO
    ======================================================== */

    cargarCursos();
    cargarEvaluaciones();

})();