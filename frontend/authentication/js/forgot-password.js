/* =====================================================
   SECURITY AWARENESS
   RECUPERACIÓN DE CONTRASEÑA
===================================================== */

const recoveryForm =
    document.getElementById("recoveryForm");

const newPasswordForm =
    document.getElementById("newPasswordForm");

const message =
    document.getElementById("message");

const passwordMessage =
    document.getElementById("passwordMessage");


let userFound = null;


/* =====================================================
   BUSCAR CUENTA
===================================================== */

recoveryForm.addEventListener("submit", function (event) {

    event.preventDefault();


    const email =
        document
            .getElementById("email")
            .value
            .trim()
            .toLowerCase();


    if (!email) {

        showMessage(
            message,
            "Ingresa tu correo electrónico.",
            "error"
        );

        return;
    }


    /* ================================================
       OBTENER USUARIOS
    ================================================= */

    const users =
        JSON.parse(
            localStorage.getItem("sah_users") || "[]"
        );


    /* ================================================
       BUSCAR USUARIO
    ================================================= */

    userFound =
        users.find(
            user => user.email === email
        );


    /* ================================================
       NO ENCONTRADO
    ================================================= */

    if (!userFound) {

        showMessage(
            message,
            "No encontramos una cuenta con este correo.",
            "error"
        );

        return;
    }


    /* ================================================
       CUENTA ENCONTRADA
    ================================================= */

    showMessage(
        message,
        "Cuenta encontrada. Ahora crea una nueva contraseña.",
        "success"
    );


    /* ================================================
       OCULTAR CORREO
    ================================================= */

    recoveryForm.classList.add("hidden");


    /* ================================================
       MOSTRAR NUEVA CONTRASEÑA
    ================================================= */

    newPasswordForm.classList.remove("hidden");

});


/* =====================================================
   CAMBIAR CONTRASEÑA
===================================================== */

newPasswordForm.addEventListener(
    "submit",
    function (event) {

        event.preventDefault();


        const newPassword =
            document
                .getElementById("newPassword")
                .value;


        const confirmNewPassword =
            document
                .getElementById("confirmNewPassword")
                .value;


        /* ============================================
           VALIDAR LONGITUD
        ============================================ */

        if (newPassword.length < 6) {

            showMessage(
                passwordMessage,
                "La contraseña debe tener mínimo 6 caracteres.",
                "error"
            );

            return;
        }


        /* ============================================
           CONFIRMAR
        ============================================ */

        if (newPassword !== confirmNewPassword) {

            showMessage(
                passwordMessage,
                "Las contraseñas no coinciden.",
                "error"
            );

            return;
        }


        /* ============================================
           OBTENER USUARIOS
        ============================================ */

        const users =
            JSON.parse(
                localStorage.getItem("sah_users") || "[]"
            );


        /* ============================================
           ACTUALIZAR CONTRASEÑA
        ============================================ */

        const userIndex =
            users.findIndex(
                user =>
                    user.email === userFound.email
            );


        if (userIndex === -1) {

            showMessage(
                passwordMessage,
                "No fue posible actualizar la cuenta.",
                "error"
            );

            return;
        }


        users[userIndex].password =
            newPassword;


        /* ============================================
           GUARDAR
        ============================================ */

        localStorage.setItem(
            "sah_users",
            JSON.stringify(users)
        );


        /* ============================================
           MENSAJE
        ============================================ */

        showMessage(
            passwordMessage,
            "Contraseña actualizada correctamente.",
            "success"
        );


        /* ============================================
           VOLVER AL LOGIN
        ============================================ */

        setTimeout(() => {

            window.location.href = "login.html";

        }, 1500);

    }
);


/* =====================================================
   FUNCIÓN MENSAJES
===================================================== */

function showMessage(element, text, type) {

    element.textContent = text;


    if (type === "error") {

        element.style.color = "#ff6b6b";

    } else {

        element.style.color = "#43e27c";

    }
} 