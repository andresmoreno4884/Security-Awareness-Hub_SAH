<p align="center">
  <img src="frontend/Landing_Page/assets/imagenes/Page_Readme.png" alt="Security Awareness Hub" width="1000">
</p>

## 📌 Descripción

**Security Awareness Hub** es una plataforma web orientada a organizaciones que buscan fortalecer la cultura de ciberseguridad de sus colaboradores.

La plataforma nace como respuesta a la necesidad de mejorar la preparación de los empleados frente a amenazas digitales como **phishing, ingeniería social, malware, robo de credenciales y malas prácticas en el manejo de información**.

El proyecto busca centralizar los procesos de capacitación y concientización en ciberseguridad dentro de una organización, permitiendo gestionar usuarios, roles y contenidos educativos desde un mismo entorno.

Security Awareness Hub está diseñado para representar una solución aplicable a un entorno empresarial real, donde los administradores puedan gestionar la plataforma y los colaboradores puedan acceder a procesos de formación que les permitan identificar, prevenir y reportar posibles amenazas de seguridad.

La primera versión del proyecto estará enfocada en la construcción de un **MVP (Producto Mínimo Viable)** compuesto por las funcionalidades fundamentales de la plataforma:

- 🔐 Sistema de inicio de sesión.
- 🏢 Registro de empresas.
- 👤 Registro y gestión de usuarios.
- 🛡️ Gestión de roles y permisos.

Posteriormente, la plataforma podrá evolucionar con funcionalidades como cursos de capacitación, evaluaciones, reportes de phishing, estadísticas, seguimiento del progreso y generación de certificados.

> **Capacita · Concientiza · Protege**

## 🏗️ Arquitectura del backend (MVC en PHP puro)

El backend fue reorganizado siguiendo el patrón **Modelo-Vista-Controlador**, sin frameworks (sin Laravel), usando PHP + PDO + MySQL. El frontend (HTML/CSS/JS) sigue llamando exactamente a las mismas rutas de siempre (`backend/Api/*.php`), así que no fue necesario tocar el JavaScript.

```
backend/
├── Config/
│   └── database.php        # Conexión PDO a MySQL (patrón Singleton)
├── Core/                    # Clases de apoyo compartidas
│   ├── Request.php          # Lee método HTTP, JSON del body y $_GET
│   ├── Response.php         # "Vista": arma y envía la respuesta JSON
│   └── Auth.php             # Maneja sesión, login/logout y permisos
├── Models/                  # "M": una clase por tabla, solo SQL
│   ├── UsuarioModel.php
│   ├── EmpresaModel.php
│   ├── CursoModel.php
│   └── EvaluacionModel.php
├── Controllers/              # "C": validaciones y reglas de negocio
│   ├── AuthController.php    # login, register, logout
│   ├── UsuarioController.php
│   ├── EmpresaController.php
│   ├── CursoController.php
│   └── EvaluacionController.php
├── bootstrap.php             # Arranca sesión + carga Core/Modelos/Controladores
└── Api/                      # Front controllers: el único punto de entrada público
    ├── login.php, register.php, logout.php
    ├── usuarios.php, empresas.php, courses.php, evaluaciones.php
    └── test.php, crear_admin.php
```

**¿Cómo fluye una petición?**
`frontend (fetch)` → `Api/usuarios.php` (front controller, 3 líneas) → `UsuarioController` (valida y decide) → `UsuarioModel` (consulta MySQL con PDO) → `Response::json()` (responde al frontend).

**¿Dónde está la "Vista" si no hay HTML generado por PHP?**
Como el frontend es un SPA en JavaScript que solo consume JSON, la Vista del backend es el formato de la respuesta (`Core/Response.php`). Las vistas visuales (HTML/CSS) viven en `frontend/`, totalmente desacopladas del backend — esa separación también es parte de MVC.

**Corrección aplicada durante la migración:** en `courses.php` el estado del curso se guardaba con `(int)` sobre `"activo"/"inactivo"`, lo que siempre producía `0` y corrompía la columna `ENUM`. En `CursoController`/`CursoModel` ahora se maneja como texto (`'activo'`/`'inactivo'`), igual que en el resto de los módulos y que en la base de datos.
