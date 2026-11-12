<?php

declare(strict_types=1);

namespace CanariApp\Backend\Config;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Gestor de la conexión a la base de datos PostgreSQL.
 */
final class Db
{
    private static ?PDO $connection = null;

    private function __construct() {}

    /**
     * Obtiene la instancia única de la conexión a la base de datos.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            // Asumimos que Dotenv ya cargó las variables en public/index.php
            $host     = $_ENV['DB_HOST']     ?? $_SERVER['DB_HOST']     ?? 'postgres';
            $port     = $_ENV['DB_PORT']     ?? $_SERVER['DB_PORT']     ?? '5432';
            $db       = $_ENV['DB_NAME']     ?? $_SERVER['DB_NAME']     ?? 'mi_base_datos';
            $user     = $_ENV['DB_USER']     ?? $_SERVER['DB_USER']     ?? 'postgres';
            $password = $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? 'password';

            $dsn = "pgsql:host={$host};port={$port};dbname={$db};";

            try {
                self::$connection = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Lanzamos una RuntimeException para que el index.php la atrape y lance un error 500 controlado
                throw new RuntimeException("Error de conexión a la base de datos: " . $e->getMessage());
            }
        }

        return self::$connection;
    }
}