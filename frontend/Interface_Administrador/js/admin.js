document.addEventListener("DOMContentLoaded", () => {

    const content = document.getElementById("admin-content");

    const navItems = document.querySelectorAll(
        ".nav-item[data-view]"
    );


    // =====================================================
    // CARGAR UNA VISTA
    // =====================================================

    async function loadView(view, activeItem = null) {

        if (!content || !view) {
            return;
        }


        // =================================================
        // DASHBOARD
        // =================================================
        // El dashboard ya está cargado.
        // NO debemos hacer fetch de dashboard.html
        // dentro de dashboard.html.

        if (view === "dashboard") {

            window.location.hash = "dashboard";

            return;
        }


        try {

            console.log(
                `Cargando vista: ${view}.html`
            );


            const response = await fetch(
                `${view}.html`
            );


            if (!response.ok) {

                throw new Error(
                    `No se pudo cargar ${view}.html`
                );

            }


            const html = await response.text();


            content.innerHTML = html;


            // =============================================
            // QUITAR ACTIVE
            // =============================================

            navItems.forEach(item => {

                item.classList.remove("active");

            });


            // =============================================
            // ACTIVAR ELEMENTO
            // =============================================

            if (activeItem) {

                activeItem.classList.add("active");

            }


            // =============================================
            // CAMBIAR HASH
            // =============================================

            window.location.hash = view;


            console.log(
                `Vista ${view}.html cargada correctamente`
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
                        <strong>${view}.html</strong>
                        en Interface_Administrador.
                    </p>

                    <p>
                        Verifica que el archivo tenga exactamente
                        ese nombre.
                    </p>

                </div>

            `;

        }

    }



    // =====================================================
    // NAVEGACIÓN
    // =====================================================

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
                    view,
                    item
                );

            }
        );

    });



    // =====================================================
    // CERRAR SESIÓN
    // =====================================================

    const logoutButton =
        document.getElementById(
            "logoutButton"
        );


    if (logoutButton) {

        logoutButton.addEventListener(
            "click",
            event => {

                event.preventDefault();


                // Eliminar sesión guardada

                localStorage.removeItem(
                    "sah_user"
                );

                sessionStorage.removeItem(
                    "sah_user"
                );


                // Volver al login

                window.location.href =
                    "../authentication/login.html";

            }
        );

    }



    // =====================================================
    // VISTA INICIAL
    // =====================================================

    console.log(
        "Panel administrativo iniciado correctamente."
    );

});