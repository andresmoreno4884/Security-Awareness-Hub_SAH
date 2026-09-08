/* =========================================================
   SECURITY AWARENESS HUB
   LANDING PAGE - SCRIPT PRINCIPAL
   =========================================================

   Este archivo controla únicamente la Landing Page.

   Estructura:

   Landing Page
        ↓
   Login / Registro
        ↓
   Sistema de autenticación
        ↓
   Backend / API
        ↓
   Base de datos

   Actualmente la autenticación se encuentra preparada
   para trabajar con el frontend existente.
   ========================================================= */


/* =========================================================
   CONFIGURACIÓN
   ========================================================= */

const API_BASE_URL = "http://localhost:3000/api";


/* =========================================================
   INICIALIZACIÓN
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    console.log("Security Awareness Hub - Landing Page cargada.");

    inicializarNavegacion();

    inicializarScroll();

    inicializarAnimaciones();

    inicializarBotones();

});


/* =========================================================
   NAVEGACIÓN
   ========================================================= */

function inicializarNavegacion() {

    /*
     * Buscamos los enlaces que tengan la clase "nav-link".
     *
     * Si alguno apunta a una sección de la misma página,
     * hacemos desplazamiento suave.
     */

    const navLinks = document.querySelectorAll(
        'a[href^="#"]'
    );

    navLinks.forEach(function (link) {

        link.addEventListener("click", function (event) {

            const targetId =
                link.getAttribute("href");

            if (
                !targetId ||
                targetId === "#"
            ) {
                return;
            }

            const target =
                document.querySelector(targetId);

            if (!target) {
                return;
            }

            event.preventDefault();

            target.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });

        });

    });

}


/* =========================================================
   SCROLL
   ========================================================= */

function inicializarScroll() {

    /*
     * Detectamos cuando el usuario baja por la página.
     *
     * Esto permite agregar posteriormente efectos al header.
     */

    window.addEventListener("scroll", function () {

        const header =
            document.querySelector("header");

        if (!header) {
            return;
        }

        if (window.scrollY > 50) {

            header.classList.add("scrolled");

        } else {

            header.classList.remove("scrolled");

        }

    });

}


/* =========================================================
   ANIMACIONES DE ENTRADA
   ========================================================= */

function inicializarAnimaciones() {

    /*
     * Elementos que tengan la clase:
     *
     * .reveal
     *
     * podrán mostrar una animación cuando entren
     * en pantalla.
     */

    const elements =
        document.querySelectorAll(".reveal");

    if (!elements.length) {
        return;
    }


    /*
     * IntersectionObserver permite detectar cuando
     * un elemento aparece dentro de la pantalla.
     */

    const observer =
        new IntersectionObserver(

            function (entries) {

                entries.forEach(function (entry) {

                    if (entry.isIntersecting) {

                        entry.target.classList.add(
                            "visible"
                        );

                        observer.unobserve(
                            entry.target
                        );

                    }

                });

            },

            {
                threshold: 0.15
            }

        );


    elements.forEach(function (element) {

        observer.observe(element);

    });

}


/* =========================================================
   BOTONES PRINCIPALES
   ========================================================= */

function inicializarBotones() {

    /*
     * Botones que tengan alguno de estos atributos
     * pueden utilizar la navegación automáticamente.
     *
     * data-action="login"
     * data-action="register"
     */

    const loginButtons =
        document.querySelectorAll(
            '[data-action="login"]'
        );

    const registerButtons =
        document.querySelectorAll(
            '[data-action="register"]'
        );


    /* =====================================================
       BOTONES LOGIN
       ===================================================== */

    loginButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                window.location.href =
                    "../authentication/login.html";

            }
        );

    });


    /* =====================================================
       BOTONES REGISTRO
       ===================================================== */

    registerButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                window.location.href =
                    "../authentication/register.html";

            }
        );

    });

}


/* =========================================================
   SESIÓN
   ========================================================= */

function obtenerSesion() {

    /*
     * Primero intentamos obtener la sesión de
     * localStorage.
     */

    let session =
        localStorage.getItem("sah_user");


    /*
     * Si no existe, buscamos en sessionStorage.
     */

    if (!session) {

        session =
            sessionStorage.getItem("sah_user");

    }


    /*
     * Si no existe ninguna sesión:
     */

    if (!session) {
        return null;
    }


    /*
     * Intentamos convertir la información
     * almacenada a objeto JavaScript.
     */

    try {

        return JSON.parse(session);

    } catch (error) {

        console.error(
            "Error al leer la sesión:",
            error
        );

        return null;

    }

}


/* =========================================================
   VERIFICAR SESIÓN
   ========================================================= */

function usuarioEstaAutenticado() {

    const session =
        obtenerSesion();

    return session !== null;

}


/* =========================================================
   CERRAR SESIÓN
   ========================================================= */

function cerrarSesion() {

    /*
     * Eliminamos las posibles sesiones guardadas.
     */

    localStorage.removeItem(
        "sah_user"
    );

    sessionStorage.removeItem(
        "sah_user"
    );


    /*
     * Después de cerrar sesión regresamos
     * a la Landing Page.
     */

    window.location.href =
        "index.html";

}


/* =========================================================
   NAVEGAR AL LOGIN
   ========================================================= */

function irAlLogin() {

    window.location.href =
        "../authentication/login.html";

}


/* =========================================================
   NAVEGAR AL REGISTRO
   ========================================================= */

function irAlRegistro() {

    window.location.href =
        "../authentication/register.html";

}


/* =========================================================
   COMPROBAR API
   =========================================================

   Esta función NO se ejecuta automáticamente.

   Queda preparada para cuando tengamos el backend
   funcionando en:

   http://localhost:3000/api
   ========================================================= */

async function comprobarAPI() {

    try {

        const response =
            await fetch(
                `${API_BASE_URL}`,
                {
                    method: "GET"
                }
            );

        if (response.ok) {

            console.log(
                "API disponible."
            );

            return true;

        }

        console.warn(
            "La API respondió con un error."
        );

        return false;

    } catch (error) {

        console.warn(
            "La API todavía no está disponible."
        );

        return false;

    }

}


/* =========================================================
   CONTROL DE BOTONES SEGÚN SESIÓN
   ========================================================= */

function actualizarEstadoSesion() {

    const session =
        obtenerSesion();


    /*
     * Elementos opcionales.
     *
     * Si existen en el HTML, se actualizarán.
     * Si no existen, no ocurre ningún error.
     */

    const loginButtons =
        document.querySelectorAll(
            '[data-action="login"]'
        );

    const registerButtons =
        document.querySelectorAll(
            '[data-action="register"]'
        );

    const dashboardButtons =
        document.querySelectorAll(
            '[data-action="dashboard"]'
        );

    const logoutButtons =
        document.querySelectorAll(
            '[data-action="logout"]'
        );


    /*
     * Si existe sesión:
     */

    if (session) {

        dashboardButtons.forEach(
            function (button) {

                button.style.display =
                    "";

            }
        );

        logoutButtons.forEach(
            function (button) {

                button.style.display =
                    "";

            }
        );

        /*
         * Login y registro pueden ocultarse
         * cuando ya existe una sesión.
         */

        loginButtons.forEach(
            function (button) {

                button.style.display =
                    "none";

            }
        );

        registerButtons.forEach(
            function (button) {

                button.style.display =
                    "none";

            }
        );

    } else {

        /*
         * Si no existe sesión:
         */

        dashboardButtons.forEach(
            function (button) {

                button.style.display =
                    "none";

            }
        );

        logoutButtons.forEach(
            function (button) {

                button.style.display =
                    "none";

            }
        );

    }

}


/* =========================================================
   BOTÓN DASHBOARD
   ========================================================= */

document.addEventListener(
    "click",
    function (event) {

        const button =
            event.target.closest(
                '[data-action="dashboard"]'
            );

        if (!button) {
            return;
        }

        const session =
            obtenerSesion();

        if (!session) {

            event.preventDefault();

            irAlLogin();

            return;

        }

        /*
         * No inventamos todavía una ruta del dashboard.
         *
         * Cuando actualicemos Interface_User /
         * Interface_Administrador definiremos
         * la redirección correspondiente.
         */

    }
);


/* =========================================================
   BOTÓN CERRAR SESIÓN
   ========================================================= */

document.addEventListener(
    "click",
    function (event) {

        const button =
            event.target.closest(
                '[data-action="logout"]'
            );

        if (!button) {
            return;
        }

        event.preventDefault();

        cerrarSesion();

    }
);


/* =========================================================
   ACTUALIZAR ESTADO AL CARGAR
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        actualizarEstadoSesion();

    }
);


/* =========================================================
   EXPORTACIÓN GLOBAL
   =========================================================

   Estas funciones quedan disponibles para que
   posteriormente podamos utilizarlas desde HTML
   si es necesario.

   Ejemplo:

   onclick="irAlLogin()"
   onclick="irAlRegistro()"
   onclick="cerrarSesion()"
   ========================================================= */

window.irAlLogin =
    irAlLogin;

window.irAlRegistro =
    irAlRegistro;

window.cerrarSesion =
    cerrarSesion;

window.obtenerSesion =
    obtenerSesion;

window.usuarioEstaAutenticado =
    usuarioEstaAutenticado;

window.comprobarAPI =
    comprobarAPI;