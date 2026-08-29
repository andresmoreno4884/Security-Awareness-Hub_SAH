-- ============================================================
-- SECURITY AWARENESS HUB (SAH)
-- BASE DE DATOS LIMPIA
-- SENA ADSO
-- ============================================================

-- Eliminar la base de datos anterior
DROP DATABASE IF EXISTS security_awareness_hub;

-- Crear nuevamente la base de datos
CREATE DATABASE security_awareness_hub
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Seleccionar la base de datos
USE security_awareness_hub;


-- ============================================================
-- TABLA: USUARIOS
-- ============================================================

CREATE TABLE usuarios (

    id INT AUTO_INCREMENT PRIMARY KEY,

    nombres VARCHAR(100) NOT NULL,

    apellidos VARCHAR(100) NOT NULL,

    correo VARCHAR(150) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    rol ENUM('usuario', 'admin')
        NOT NULL DEFAULT 'usuario',

    estado ENUM('activo', 'inactivo')
        NOT NULL DEFAULT 'activo',

    fecha_registro TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- VERIFICACIÓN
-- ============================================================

SELECT
    id,
    nombres,
    apellidos,
    correo,
    password,
    rol,
    estado,
    fecha_registro
FROM usuarios;
