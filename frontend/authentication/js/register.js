/* =====================================================
   SECURITY AWARENESS HUB
   REGISTRO DE USUARIO
   CONEXIÓN PHP + MYSQL + JSON
===================================================== */

const registerForm = document.getElementById("registerForm");
const message = document.getElementById("message");

registerForm.addEventListener("submit", async function (event) {

    event.preventDefault();

    /* =================================================
       OBTENER DATOS
    ================================================= */

    const nombres = document
        .getElementById("nombres")
        .value
        .trim();

    const apellidos = document
        .getElementById("apellidos")
        .value
        .trim();

    const correo = document
        .getElementById("correo")
        .value
        .trim()
        .toLowerCase();

    const password = document
        .getElementById("password")
        .value;

    const confirmPassword = document
        .getElementById("confirmPassword")
        .value;

    /* =================================================
       LIMPIAR MENSAJE
    ================================================= */

    message.textContent = "";
    message.className = "";

    /* =================================================
       VALIDAR CAMPOS
    ================================================= */

    if (
        !nombres ||
        !apellidos ||
        !correo ||
        !password ||
        !confirmPassword
    ) {

        showMessage(
            "Completa todos los campos.",
            "error"
        );

        return;
    }

    /* =================================================
       VALIDAR NOMBRES
    ================================================= */

    if (nombres.length < 2) {

        showMessage(
            "Los nombres deben tener al menos 2 caracteres.",
            "error"
        );

        return;
    }

    /* =================================================
       VALIDAR APELLIDOS
    ================================================= */

    if (apellidos.length < 2) {

        showMessage(
            "Los apellidos deben tener al menos 2 caracteres.",
            "error"
        );

        return;
    }

    /* =================================================
       VALIDAR CORREO
    ================================================= */

    const emailRegex =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(correo)) {

        showMessage(
            "Ingresa un correo electrónico válido.",
            "error"
        );

        return;
    }

    /* =================================================
       VALIDAR CONTRASEÑA
    ================================================= */

    if (password.length < 8) {

        showMessage(
            "La contraseña debe tener mínimo 8 caracteres.",
            "error"
        );

        return;
    }

    /* =================================================
       CONFIRMAR CONTRASEÑA
    ================================================= */

    if (password !== confirmPassword) {

        showMessage(
            "Las contraseñas no coinciden.",
            "error"
        );

        return;
    }

    /* =================================================
       BOTÓN
    ================================================= */

    const registerButton =
        registerForm.querySelector(".register-button");

    registerButton.disabled = true;

    registerButton.textContent =
        "Creando cuenta...";

    /* =================================================
       DATOS PARA PHP
    ================================================= */

    const userData = {

        nombres: nombres,

        apellidos: apellidos,

        correo: correo,

        password: password

    };

    try {

        /* =============================================
           ENVIAR DATOS A PHP
        ============================================= */

        const response = await fetch(
            "../../backend/Api/register.php",
            {
                method: "POST",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify(userData)
            }
        );

        /* =============================================
           RECIBIR RESPUESTA
        ============================================= */

        const data = await response.json();

        /* =============================================
           COMPROBAR RESPUESTA
        ============================================= */

        if (!response.ok || !data.success) {

            showMessage(
                data.message ||
                "No fue posible crear la cuenta.",
                "error"
            );

            registerButton.disabled = false;

            registerButton.textContent =
                "Crear cuenta";

            return;
        }

        /* =============================================
           REGISTRO EXITOSO
        ============================================= */

        showMessage(
            data.message ||
            "Usuario registrado correctamente.",
            "success"
        );

        /* =============================================
           REDIRIGIR AL LOGIN
        ============================================= */

        setTimeout(() => {

            window.location.href =
                "login.html";

        }, 1500);

    } catch (error) {

        console.error(
            "Error al conectar con el servidor:",
            error
        );

        showMessage(
            "No se pudo conectar con el servidor.",
            "error"
        );

        registerButton.disabled = false;

        registerButton.textContent =
            "Crear cuenta";
    }

});


/* =====================================================
   MOSTRAR MENSAJE
===================================================== */

function showMessage(text, type) {

    message.textContent = text;

    if (type === "error") {

        message.style.color = "#ff6b6b";

    } else {

        message.style.color = "#43e27c";
    }
}