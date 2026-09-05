/* ============================================================
   SECURITY AWARENESS HUB
   PANEL ADMINISTRADOR
   admin.js
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {

    /* ========================================================
       ELEMENTOS PRINCIPALES
    ======================================================== */

    const content =
        document.getElementById("admin-content");

    const navItems =
        document.querySelectorAll(
            ".nav-item[data-view]"
        );

    /* ========================================================
       VISTAS QUE NECESITAN JAVASCRIPT
    ======================================================== */

 const viewScripts = {

    usuarios:
        "js/usuarios.js",

    courses_admin:
        "js/courses_admin.js"
};

    /* ========================================================
       EVITAR CARGAR EL MISMO JS VARIAS VECES
    ======================================================== */

    const loadedScripts =
        new Set();

    /* ========================================================
       CARGAR JAVASCRIPT DE UNA VISTA
    ======================================================== */

    function loadViewScript(view) {

        const scriptPath =
            viewScripts[view];

        if (!scriptPath) {
            return;
        }

        if (
            loadedScripts.has(
                scriptPath
            )
        ) {
            return;
        }

        const script =
            document.createElement(
                "script"
            );

        script.src =
            scriptPath;

        script.async =
            false;

        script.onload = () => {

            console.log(
                `JavaScript de ${view} cargado correctamente.`
            );

            loadedScripts.add(
                scriptPath
            );
        };

        script.onerror = () => {

            console.error(
                `No se pudo cargar ${scriptPath}`
            );

        };

        document.body.appendChild(
            script
        );

    }

    /* ========================================================
       ACTIVAR ELEMENTO DEL MENÚ
    ======================================================== */

    function activateNavItem(view) {

        navItems.forEach(item => {

            item.classList.remove(
                "active"
            );

        });

        const item =
            document.querySelector(
                `.nav-item[data-view="${view}"]`
            );

        if (item) {

            item.classList.add(
                "active"
            );

        }

    }

    /* ========================================================
       CARGAR DASHBOARD
    ======================================================== */

    async function loadDashboard() {

        if (!content) {
            return;
        }

        try {

            const response =
                await fetch(
                    "dashboard.html"
                );

            if (!response.ok) {

                throw new Error(
                    "No se pudo cargar dashboard.html"
                );

            }

            const html =
                await response.text();

            content.innerHTML =
                html;

            activateNavItem(
                "dashboard"
            );

            window.location.hash =
                "dashboard";

            console.log(
                "Dashboard cargado correctamente."
            );

        } catch (error) {

            console.error(
                "Error cargando dashboard:",
                error
            );

            showError(
                "dashboard.html"
            );

        }

    }

    /* ========================================================
       CARGAR UNA VISTA
    ======================================================== */

    async function loadView(view) {

        if (
            !content ||
            !view
        ) {
            return;
        }

        if (
            view === "dashboard"
        ) {

            await loadDashboard();

            return;

        }

        try {

            console.log(
                `Cargando vista: ${view}.html`
            );

            const response =
                await fetch(
                    `${view}.html`
                );

            if (!response.ok) {

                throw new Error(
                    `No se pudo cargar ${view}.html`
                );

            }

            const html =
                await response.text();

            content.innerHTML =
                html;

            activateNavItem(
                view
            );

            window.location.hash =
                view;

            loadViewScript(
                view
            );

            console.log(
                `Vista ${view}.html cargada correctamente.`
            );

        } catch (error) {

            console.error(
                "Error cargando vista:",
                error
            );

            showError(
                `${view}.html`
            );

        }

    }

    /* ========================================================
       MOSTRAR ERROR
    ======================================================== */

    function showError(fileName) {

        if (!content) {
            return;
        }

        content.innerHTML = `

            <div class="dashboard-heading">

                <span class="section-label">
                    ERROR 404
                </span>

                <h2>
                    No se pudo cargar la sección
                </h2>

                <p>
                    No existe el archivo
                    <strong>
                        ${fileName}
                    </strong>
                    en Interface_Administrador.
                </p>

                <p>
                    Verifica que el archivo tenga
                    exactamente ese nombre.
                </p>

            </div>

        `;

    }

    /* ========================================================
       NAVEGACIÓN DEL MENÚ LATERAL
    ======================================================== */

    navItems.forEach(item => {

        item.addEventListener(
            "click",
            async event => {

                event.preventDefault();

                const view =
                    item.dataset.view;

                if (!view) {
                    return;
                }

                await loadView(
                    view
                );

            }
        );

    });

    /* ========================================================
       ACCIONES RÁPIDAS DEL DASHBOARD
       
       IMPORTANTE:
       Estos botones están dentro de dashboard.html.
       Por eso usamos delegación de eventos.
    ======================================================== */

    if (content) {

        content.addEventListener(
            "click",
            async event => {

                const quickAction =
                    event.target.closest(
                        ".quick-action[data-view]"
                    );

                if (!quickAction) {
                    return;
                }

                event.preventDefault();

                const view =
                    quickAction.dataset.view;

                if (!view) {
                    return;
                }

                console.log(
                    `Acción rápida seleccionada: ${view}`
                );

                await loadView(
                    view
                );

            }
        );

    }

    /* ========================================================
       CERRAR SESIÓN
    ======================================================== */

    const logoutButton =
        document.getElementById(
            "logoutButton"
        );

    if (logoutButton) {

        logoutButton.addEventListener(
            "click",
            event => {

                event.preventDefault();

                localStorage.removeItem(
                    "sah_user"
                );

                sessionStorage.removeItem(
                    "sah_user"
                );

                window.location.href =
                    "../authentication/login.html";

            }
        );

    }

    /* ========================================================
       CARGAR VISTA SEGÚN HASH
    ======================================================== */

    const hash =
        window.location.hash
            .replace("#", "")
            .trim();

    if (hash) {

        loadView(
            hash
        );

    } else {

        loadDashboard();

    }

    /* ========================================================
       MENSAJE DE INICIO
    ======================================================== */

    console.log(
        "Panel administrativo iniciado correctamente."
    );

});