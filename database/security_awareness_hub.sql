-- ============================================================
-- SECURITY AWARENESS HUB (SAH)
-- BASE DE DATOS
-- SENA ADSO
-- ============================================================

DROP DATABASE IF EXISTS security_awareness_hub;

CREATE DATABASE security_awareness_hub
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

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
-- USUARIO ADMINISTRADOR INICIAL
-- ============================================================

INSERT INTO usuarios (
    nombres,
    apellidos,
    correo,
    password,
    rol,
    estado
)
VALUES (
    'Administrador',
    'SAH',
    'admin@sah.com',
    '$2y$12$awlzns0sJO9ICU3Y.SfiXe4Ia3PhyHVLMsuOgw4rmfMv3ivoR/lcS',
    'admin',
    'activo'
);


-- ============================================================
-- VERIFICACIÓN
-- ============================================================

SELECT
    id,
    nombres,
    apellidos,
    correo,
    rol,
    estado,
    fecha_registro
FROM usuarios;

USE security_awareness_hub;

CREATE TABLE IF NOT EXISTS cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,

    titulo VARCHAR(150) NOT NULL,

    descripcion TEXT NOT NULL,

    nivel_dificultad ENUM(
        'basico',
        'intermedio',
        'avanzado'
    ) NOT NULL DEFAULT 'basico',

    duracion INT NOT NULL,

    numero_modulos INT NOT NULL DEFAULT 1,

    porcentaje_aprobacion INT NOT NULL DEFAULT 70,

    estado ENUM(
        'activo',
        'inactivo'
    ) NOT NULL DEFAULT 'activo',

    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;