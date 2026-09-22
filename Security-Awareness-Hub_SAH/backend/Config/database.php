<?php

/**
 * ============================================================
 * SECURITY AWARENESS HUB
 * CONEXIÓN A MYSQL
 * PHP + PDO
 * ============================================================
 */

class Database
{
    private static $connection = null;

    public static function connect()
    {
        if (self::$connection === null) {

            $host = "localhost";
            $database = "security_awareness_hub";
            $username = "root";
            $password = "";

            $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

            try {

                self::$connection = new PDO(
                    $dsn,
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );

            } catch (PDOException $e) {

                header("Content-Type: application/json; charset=UTF-8");

                http_response_code(500);

                echo json_encode([
                    "success" => false,
                    "message" => "Error de conexión con la base de datos.",
                    "error" => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }
        }

        return self::$connection;
    }
}