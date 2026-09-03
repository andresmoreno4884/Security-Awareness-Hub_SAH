/* =========================================================
   SECURITY AWARENESS HUB
   LOGIN
   PHP + MYSQL + JSON
   ========================================================= */


/* =========================================================
   ELEMENTOS
   ========================================================= */

const loginForm = document.getElementById("loginForm");

const emailInput = document.getElementById("email");

const passwordInput = document.getElementById("password");

const rememberInput = document.getElementById("remember");


/* =========================================================
   CREAR MENSAJE
   ========================================================= */

let loginMessage = document.getElementById("loginMessage");

if (!loginMessage && loginForm) {

    loginMessage = document.createElement("div");

    loginMessage.id = "loginMessage";

    loginMessage.className = "login-message";

    const loginButton =
        loginForm.querySelector(".login-button");

    if (loginButton) {

        loginForm.insertBefore(
            loginMessage,
            loginButton
        );

    } else {

        loginForm.appendChild(loginMessage);
    }
}


/* =========================================================
   MENSAJES
   ========================================================= */

function showMessage(message, type) {

    if (!loginMessage) {
        return;
    }

    loginMessage.textContent = message;

    loginMessage.className =
        "login-message " + type;
}


function clearMessage() {

    if (!loginMessage) {
        return;
    }

    loginMessage.textContent = "";

    loginMessage.className =
        "login-message";
}


/* =========================================================
   VALIDAR CORREO
   ========================================================= */

function validEmail(email) {

    const emailRegex =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailRegex.test(email);
}


/* =========================================================
   LOGIN
   ========================================================= */

if (loginForm) {

    loginForm.addEventListener(
        "submit",
        async function (event) {

            event.preventDefault();

            clearMessage();


            /* =============================================
               OBTENER DATOS
               ============================================= */

            const email =
                emailInput.value
                    .trim()
                    .toLowerCase();

            const password =
                passwordInput.value;


            /* =============================================
               VALIDACIONES
               ============================================= */

            if (!email || !password) {

                showMessage(
                    "Por favor completa todos los campos.",
                    "error"
                );

                return;
            }


            if (!validEmail(email)) {

                showMessage(
                    "Ingresa un correo electrónico válido.",
                    "error"
                );

                emailInput.focus();

                return;
            }


            if (password.length < 6) {

                showMessage(
                    "La contraseña debe tener mínimo 6 caracteres.",
                    "error"
                );

                passwordInput.focus();

                return;
            }


            /* =============================================
               BOTÓN
               ============================================= */

            const loginButton =
                loginForm.querySelector(".login-button");

            if (loginButton) {

                loginButton.disabled = true;

                loginButton.textContent =
                    "Iniciando sesión...";
            }


            try {

                /* =========================================
                   PETICIÓN POST A PHP
                   ========================================= */

                const response = await fetch(
                    "../../backend/Api/login.php",
                    {
                        method: "POST",

                        headers: {
                            "Content-Type":
                                "application/json"
                        },

                        credentials: "include",

                        body: JSON.stringify({
                            email: email,
                            password: password
                        })
                    }
                );


                /* =========================================
                   LEER RESPUESTA
                   ========================================= */

                const responseText =
                    await response.text();

                let data;

                try {

                    data =
                        JSON.parse(responseText);

                } catch (jsonError) {

                    console.error(
                        "PHP no devolvió JSON válido:",
                        responseText
                    );

                    throw new Error(
                        "El servidor no devolvió una respuesta JSON válida."
                    );
                }


                /* =========================================
                   ERROR DE LOGIN
                   ========================================= */

                if (!response.ok || !data.success) {

                    showMessage(
                        data.message ||
                        "No fue posible iniciar sesión.",
                        "error"
                    );

                    if (loginButton) {

                        loginButton.disabled = false;

                        loginButton.textContent =
                            "Iniciar sesión";
                    }

                    return;
                }


                /* =========================================
                   USUARIO AUTENTICADO
                   ========================================= */

                const user = data.user;


                /* =========================================
                   INFORMACIÓN PARA FRONTEND
                   ========================================= */

                const sessionUser = {

                    id: user.id,

                    nombres: user.nombres,

                    apellidos: user.apellidos,

                    email: user.correo,

                    role: user.rol,

                    loginDate:
                        new Date().toISOString()
                };


                const sessionData =
                    JSON.stringify(sessionUser);


                /* =========================================
                   RECORDAR SESIÓN
                   ========================================= */

                if (
                    rememberInput &&
                    rememberInput.checked
                ) {

                    localStorage.setItem(
                        "sah_user",
                        sessionData
                    );

                    sessionStorage.removeItem(
                        "sah_user"
                    );

                } else {

                    sessionStorage.setItem(
                        "sah_user",
                        sessionData
                    );

                    localStorage.removeItem(
                        "sah_user"
                    );
                }


                /* =========================================
                   MENSAJE ÉXITO
                   ========================================= */

                showMessage(
                    "Inicio de sesión correcto. Entrando...",
                    "success"
                );


                /* =========================================
                   REDIRECCIÓN
                   ========================================= */

                setTimeout(function () {

                    if (user.rol === "admin") {

                        window.location.href =
                            "../Interface_Administrador/dashboard.html";

                    } else {

                        window.location.href =
                            "../Interface_User/Curse/course.html";
                    }

                }, 700);


            } catch (error) {

                console.error(
                    "Error al conectar con login.php:",
                    error
                );


                showMessage(
                    error.message ||
                    "No se pudo conectar con el servidor.",
                    "error"
                );


                if (loginButton) {

                    loginButton.disabled = false;

                    loginButton.textContent =
                        "Iniciar sesión";
                }
            }
        }
    );
}