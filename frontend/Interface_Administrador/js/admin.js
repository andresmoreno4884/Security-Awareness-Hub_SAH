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
       
       IMPORTANTE:
       Como las vistas se cargan mediante innerHTML,
       sus <script> no se ejecutan automáticamente.
    ======================================================== */

    const viewScripts = {

        usuarios:
            "js/usuarios.js"

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


        // Esta vista no necesita JS
        if (!scriptPath) {

            return;
        }


        // Evitar cargar el mismo archivo nuevamente
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
       CARGAR UNA VISTA
    ======================================================== */

    async function loadView(
        view,
        activeItem = null
    ) {

        if (
            !content ||
            !view
        ) {

            return;
        }


        /* ====================================================
           DASHBOARD
        ==================================================== */

        if (
            view === "dashboard"
        ) {

            window.location.hash =
                "dashboard";

            return;
        }


        try {

            console.log(
                `Cargando vista: ${view}.html`
            );


            /* ================================================
               SOLICITAR HTML
            ================================================ */

            const response =
                await fetch(
                    `${view}.html`
                );


            if (
                !response.ok
            ) {

                throw new Error(
                    `No se pudo cargar ${view}.html`
                );
            }


            const html =
                await response.text();


            /* ================================================
               INSERTAR HTML
            ================================================ */

            content.innerHTML =
                html;


            /* ================================================
               QUITAR ACTIVE
            ================================================ */

            navItems.forEach(
                item => {

                    item.classList.remove(
                        "active"
                    );

                }
            );


            /* ================================================
               ACTIVAR ELEMENTO
            ================================================ */

            if (
                activeItem
            ) {

                activeItem.classList.add(
                    "active"
                );
            }


            /* ================================================
               CAMBIAR HASH
            ================================================ */

            window.location.hash =
                view;


            /* ================================================
               CARGAR JAVASCRIPT
            ================================================ */

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
                            ${view}.html
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
    }


    /* ========================================================
       NAVEGACIÓN
    ======================================================== */

    navItems.forEach(
        item => {

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
                        view,
                        item
                    );

                }
            );

        }
    );


    /* ========================================================
       CERRAR SESIÓN
    ======================================================== */

    const logoutButton =
        document.getElementById(
            "logoutButton"
        );


    if (
        logoutButton
    ) {

        logoutButton.addEventListener(
            "click",
            event => {

                event.preventDefault();


                /* ============================================
                   ELIMINAR SESIÓN
                ============================================ */

                localStorage.removeItem(
                    "sah_user"
                );


                sessionStorage.removeItem(
                    "sah_user"
                );


                /* ============================================
                   VOLVER AL LOGIN
                ============================================ */

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


    if (
        hash &&
        hash !== "dashboard"
    ) {

        const item =
            document.querySelector(
                `.nav-item[data-view="${hash}"]`
            );


        if (item) {

            loadView(
                hash,
                item
            );

        }

    }


    /* ========================================================
       MENSAJE DE INICIO
    ======================================================== */

    console.log(
        "Panel administrativo iniciado correctamente."
    );

});