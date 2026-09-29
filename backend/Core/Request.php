<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Core / Request.php
 *
 * Clase de apoyo para leer la petición HTTP entrante:
 * el método (GET, POST, PUT, PATCH, DELETE), el cuerpo JSON
 * enviado por el frontend y los parámetros de la URL ($_GET).
 *
 * Los Controladores usan esta clase en lugar de acceder
 * directamente a $_SERVER, $_GET o php://input.
 * ============================================================
 */

class Request
{
    /** @var array|null Cuerpo JSON ya decodificado (caché) */
    private static ?array $body = null;

    /**
     * Devuelve el método HTTP de la petición actual.
     */
    public static function method(): string
    {
        return $_SERVER["REQUEST_METHOD"] ?? "GET";
    }

    /**
     * Decodifica el cuerpo JSON enviado por el cliente
     * (fetch envía el JSON en el "body" de la petición).
     * Si no hay JSON válido, devuelve un arreglo vacío.
     */
    public static function body(): array
    {
        if (self::$body !== null) {
            return self::$body;
        }

        $raw = file_get_contents("php://input");

        $decoded = [];

        if ($raw !== false && trim($raw) !== "") {

            $parsed = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                $decoded = $parsed;
            }
        }

        self::$body = $decoded;

        return self::$body;
    }

    /**
     * Obtiene un valor de $_GET (query string) de forma segura.
     */
    public static function query(string $key, $default = "")
    {
        return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
    }

    /**
     * Obtiene un valor del cuerpo JSON de forma segura.
     */
    public static function input(string $key, $default = "")
    {
        $body = self::body();

        return $body[$key] ?? $default;
    }
}
