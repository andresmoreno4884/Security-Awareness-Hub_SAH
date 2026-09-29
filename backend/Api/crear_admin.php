<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Api / crear_admin.php
 *
 * Script de UTILIDAD para crear el administrador inicial a
 * mano si el INSERT del archivo .sql no se ejecutó. No forma
 * parte del CRUD de usuarios (por eso no tiene Controlador ni
 * exige sesión), pero sí reutiliza el Modelo para no repetir SQL.
 *
 * Recomendación: elimina o protege este archivo una vez creado
 * el administrador, para no dejarlo expuesto en producción.
 * ============================================================
 */

require_once __DIR__ . "/../bootstrap.php";

$usuarios = new UsuarioModel();

$correo = "admin@sah.com";

if ($usuarios->correoExiste($correo)) {
    Response::json(false, "El usuario administrador ya existe.");
}

try {

    $id = $usuarios->crear([
        "nombres" => "Administrador",
        "apellidos" => "Sistema",
        "correo" => $correo,
        "password" => "Admin123*",
        "rol" => "admin",
        "estado" => "activo"
    ]);

    Response::json(true, "Administrador creado correctamente.", [
        "usuario" => [
            "id" => $id,
            "nombres" => "Administrador",
            "apellidos" => "Sistema",
            "correo" => $correo,
            "rol" => "admin",
            "estado" => "activo"
        ]
    ]);

} catch (Throwable $e) {

    Response::json(false, "Error al crear el administrador.", [], 500);
}
