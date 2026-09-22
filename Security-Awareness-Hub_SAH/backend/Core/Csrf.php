<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Core / Csrf.php
 *
 * Protección CSRF muy simple para los formularios PHP (sin
 * JavaScript). Cada formulario que modifica datos (crear,
 * editar, eliminar, activar/desactivar) incluye un campo oculto
 * con un token guardado en la sesión; si al enviarlo no coincide,
 * se rechaza la petición.
 * ============================================================
 */

class Csrf
{
    /** Genera (o reutiliza) el token de la sesión actual. */
    public static function token(): string
    {
        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }

        return $_SESSION["csrf_token"];
    }

    /** Imprime el <input hidden> listo para pegar dentro de un <form>. */
    public static function campo(): void
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, "UTF-8");
        echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /** Compara el token recibido por POST contra el de la sesión. */
    public static function validar(): bool
    {
        $recibido = $_POST["csrf_token"] ?? "";
        $esperado = $_SESSION["csrf_token"] ?? "";

        return $recibido !== "" && $esperado !== "" && hash_equals($esperado, $recibido);
    }
}