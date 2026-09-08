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
            async event => {

                event.preventDefault();

                try {

                    await fetch(
                        "../../../backend/Api/logout.php",
                        {
                            method: "POST"
                        }
                    );

                } catch (error) {

                    console.error(
                        "Error al cerrar sesión en el servidor:",
                        error
                    );

                }

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

    const viewScripts = {

        usuarios:
            "js/usuarios.js",
    
        courses_admin:
            "js/courses_admin.js",
    
        evaluaciones:
            "js/evaluaciones.js"
    };