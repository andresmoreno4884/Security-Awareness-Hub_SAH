CREATE DATABASE IF NOT EXISTS security_awareness_hub
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE security_awareness_hub;


-- =========================================================
-- 1. EMPRESAS
-- =========================================================

CREATE TABLE empresas (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    nit VARCHAR(30) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    direccion VARCHAR(200) DEFAULT NULL,
    tipo ENUM('privada','publica','mixta') NOT NULL DEFAULT 'privada',
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY nit (nit)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- 2. USUARIOS
-- =========================================================

CREATE TABLE usuarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,

    rol ENUM(
        'admin',
        'admin_empresa',
        'empleado'
    ) NOT NULL DEFAULT 'empleado',

    empresa_id INT(11) DEFAULT NULL,

    estado ENUM(
        'activo',
        'inactivo'
    ) NOT NULL DEFAULT 'activo',

    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY correo (correo),
    KEY idx_empresa_id (empresa_id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- 3. CURSOS
-- =========================================================

CREATE TABLE cursos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,

    nivel_dificultad ENUM(
        'basico',
        'intermedio',
        'avanzado'
    ) NOT NULL DEFAULT 'basico',

    duracion INT(11) NOT NULL,
    numero_modulos INT(11) NOT NULL DEFAULT 1,
    porcentaje_aprobacion INT(11) NOT NULL DEFAULT 70,

    estado ENUM(
        'activo',
        'inactivo'
    ) NOT NULL DEFAULT 'activo',

    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- 4. EVALUACIONES
-- =========================================================

CREATE TABLE evaluaciones (
    id INT(11) NOT NULL AUTO_INCREMENT,
    curso_id INT(11) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    porcentaje_aprobacion INT(11) NOT NULL DEFAULT 70,

    estado ENUM(
        'activo',
        'inactivo'
    ) NOT NULL DEFAULT 'activo',

    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_curso_id (curso_id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- 5. PREGUNTAS
-- =========================================================

CREATE TABLE preguntas (
    id INT(11) NOT NULL AUTO_INCREMENT,
    evaluacion_id INT(11) NOT NULL,
    enunciado TEXT NOT NULL,
    orden INT(11) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_evaluacion_id (evaluacion_id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- 6. OPCIONES
-- =========================================================

CREATE TABLE opciones (
    id INT(11) NOT NULL AUTO_INCREMENT,
    pregunta_id INT(11) NOT NULL,
    texto VARCHAR(255) NOT NULL,
    es_correcta TINYINT(1) NOT NULL DEFAULT 0,
    orden INT(11) NOT NULL DEFAULT 1,

    PRIMARY KEY (id),
    KEY idx_pregunta_id (pregunta_id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- 7. ASIGNACIONES
-- =========================================================

CREATE TABLE asignaciones (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) NOT NULL,
    curso_id INT(11) NOT NULL,

    estado ENUM(
        'asignado',
        'en_progreso',
        'completado',
        'cancelado'
    ) NOT NULL DEFAULT 'asignado',

    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_finalizacion TIMESTAMP NULL DEFAULT NULL,

    PRIMARY KEY (id),

    UNIQUE KEY unique_usuario_curso (
        usuario_id,
        curso_id
    ),

    KEY idx_usuario_id (usuario_id),
    KEY idx_curso_id (curso_id),
    KEY idx_estado (estado)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- 8. PROGRESO
-- =========================================================

CREATE TABLE progreso (
    id INT(11) NOT NULL AUTO_INCREMENT,
    asignacion_id INT(11) NOT NULL,

    porcentaje INT(11) NOT NULL DEFAULT 0,
    modulos_completados INT(11) NOT NULL DEFAULT 0,

    ultima_actividad TIMESTAMP NULL DEFAULT NULL,

    fecha_actualizacion TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY unique_asignacion (
        asignacion_id
    ),

    KEY idx_asignacion_id (asignacion_id),
    KEY idx_porcentaje (porcentaje)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- 9. RESULTADOS DE EVALUACIONES
-- =========================================================

CREATE TABLE resultados_evaluaciones (
    id INT(11) NOT NULL AUTO_INCREMENT,

    asignacion_id INT(11) NOT NULL,
    evaluacion_id INT(11) NOT NULL,

    puntaje INT(11) NOT NULL DEFAULT 0,
    aprobado TINYINT(1) NOT NULL DEFAULT 0,

    intento INT(11) NOT NULL DEFAULT 1,

    fecha_presentacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_asignacion_id (asignacion_id),
    KEY idx_evaluacion_id (evaluacion_id),
    KEY idx_aprobado (aprobado),
    KEY idx_fecha_presentacion (fecha_presentacion)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- 10. ACTIVIDAD DE ACCESO
-- =========================================================

CREATE TABLE actividad_acceso (
    id INT(11) NOT NULL AUTO_INCREMENT,

    usuario_id INT(11) NOT NULL,

    tipo ENUM(
        'login',
        'logout'
    ) NOT NULL,

    fecha_acceso TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    ip VARCHAR(45) DEFAULT NULL,

    PRIMARY KEY (id),

    KEY idx_usuario_id (usuario_id),
    KEY idx_tipo (tipo),
    KEY idx_fecha_acceso (fecha_acceso)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;