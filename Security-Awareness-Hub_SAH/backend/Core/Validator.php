<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * Core / Validator.php
 *
 * Reglas de validación reutilizadas por los formularios PHP.
 * ============================================================
 */

class Validator
{
    /**
     * Política de contraseña segura:
     *   a. Mínimo 8 caracteres
     *   b. Mínimo 2 caracteres numéricos
     *   c. Mínimo 1 carácter especial (símbolo)
     *
     * Devuelve null si la contraseña cumple, o un mensaje de
     * error legible para el usuario si no cumple.
     */
    public static function contrasenaSegura(string $password): ?string
    {
        if (strlen($password) < 8) {
            return "La contraseña debe tener mínimo 8 caracteres.";
        }

        if (preg_match_all('/[0-9]/', $password) < 2) {
            return "La contraseña debe tener mínimo 2 números.";
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return "La contraseña debe tener mínimo 1 carácter especial (ej: !, @, #, $, %).";
        }

        return null;
    }

    /**
     * Expresión regular equivalente a contrasenaSegura(), para usarla
     * en el atributo HTML "pattern" y que el navegador valide en vivo
     * sin necesitar JavaScript.
     */
    public const PASSWORD_PATTERN = '(?=(?:.*[0-9]){2,})(?=.*[^A-Za-z0-9]).{8,}';
}