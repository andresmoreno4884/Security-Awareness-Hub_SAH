<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Core / Response.php
 *
 * Esta clase representa la "V" (VISTA) del patrón MVC para
 * nuestra API. Como el frontend es un SPA en JavaScript que
 * solo consume JSON (no HTML generado por PHP), la "vista"
 * de esta aplicación backend es la respuesta JSON: aquí es
 * donde se decide el formato final que ve el cliente.
 *
 * Ningún Controlador ni Modelo hace "echo" directamente.
 * Todos usan Response::json() para responder, así el formato
 * de salida queda centralizado en un solo lugar.
 * ============================================================
 */

class Response
{
    /**
     * Envía una respuesta JSON estándar y termina la ejecución.
     *
     * @param bool   $success Indica si la operación fue exitosa
     * @param string $message Mensaje legible para el usuario
     * @param array  $data    Datos adicionales (usuarios, cursos, etc.)
     * @param int    $status  Código de estado HTTP
     */
    public static function json(
        bool $success,
        string $message = "",
        array $data = [],
        int $status = 200
    ): void {

        http_response_code($status);

        header("Content-Type: application/json; charset=UTF-8");

        echo json_encode(
            array_merge(
                [
                    "success" => $success,
                    "message" => $message
                ],
                $data
            ),
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }
}
