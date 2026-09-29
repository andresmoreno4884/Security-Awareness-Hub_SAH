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


USE security_awareness_hub;

INSERT INTO cursos (
    titulo,
    descripcion,
    nivel_dificultad,
    duracion,
    numero_modulos,
    porcentaje_aprobacion,
    estado
)
VALUES (
    'Fundamentos de Ciberseguridad',
    'Introducción a los conceptos básicos de seguridad digital.',
    'basico',
    5,
    5,
    70,
    'activo'
);


INSERT INTO cursos (
    titulo,
    descripcion,
    nivel_dificultad,
    duracion,
    numero_modulos,
    porcentaje_aprobacion,
    estado
)
VALUES (
    'Seguridad de Contraseñas',
    'Aprende a crear, gestionar y proteger contraseñas seguras para evitar accesos no autorizados.',
    'basico',
    4,
    4,
    70,
    'activo'
);

-- ============================================================
-- TABLA: EVALUACIONES
-- ============================================================

CREATE TABLE IF NOT EXISTS evaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,

    curso_id INT NOT NULL,

    titulo VARCHAR(150) NOT NULL,

    descripcion TEXT NOT NULL,

    porcentaje_aprobacion INT NOT NULL DEFAULT 70,

    estado ENUM(
        'activo',
        'inactivo'
    ) NOT NULL DEFAULT 'activo',

    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_evaluaciones_curso
        FOREIGN KEY (curso_id)
        REFERENCES cursos(id)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLA: PREGUNTAS
-- ============================================================

CREATE TABLE IF NOT EXISTS preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,

    evaluacion_id INT NOT NULL,

    enunciado TEXT NOT NULL,

    orden INT NOT NULL DEFAULT 1,

    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_preguntas_evaluacion
        FOREIGN KEY (evaluacion_id)
        REFERENCES evaluaciones(id)
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLA: OPCIONES
-- ============================================================

CREATE TABLE IF NOT EXISTS opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,

    pregunta_id INT NOT NULL,

    texto VARCHAR(255) NOT NULL,

    es_correcta TINYINT(1) NOT NULL DEFAULT 0,

    orden INT NOT NULL DEFAULT 1,

    CONSTRAINT fk_opciones_pregunta
        FOREIGN KEY (pregunta_id)
        REFERENCES preguntas(id)
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ÍNDICES DE APOYO (mejoran el rendimiento de consultas frecuentes)
-- ============================================================

CREATE INDEX idx_evaluaciones_curso ON evaluaciones(curso_id);
CREATE INDEX idx_preguntas_evaluacion ON preguntas(evaluacion_id);
CREATE INDEX idx_opciones_pregunta ON opciones(pregunta_id);

USE security_awareness_hub;

CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,

    nombre VARCHAR(150) NOT NULL,

    nit VARCHAR(30) NOT NULL UNIQUE,

    correo VARCHAR(150) NOT NULL,

    telefono VARCHAR(30) DEFAULT NULL,

    direccion VARCHAR(200) DEFAULT NULL,

    tipo ENUM(
        'privada',
        'publica',
        'mixta'
    ) NOT NULL DEFAULT 'privada',

    estado ENUM(
        'activo',
        'inactivo'
    ) NOT NULL DEFAULT 'activo',

    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;