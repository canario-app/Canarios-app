<?php

declare(strict_types=1);

namespace CanariApp\Backend\Config;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Gestor de la conexión a la base de datos PostgreSQL.
 */
final class Db{
    private static ?PDO $connection = null;

    private function __construct() {}

    /**
     * Obtiene la instancia única de la conexión a la base de datos.
     */
    public static function getConnection(): PDO{
        if (self::$connection === null) {
            // cargamos la configuración de la base de datos desde App.php
            $host     = \CanariApp\Backend\Config\App::get('db.host');
            $port     = \CanariApp\Backend\Config\App::get('db.port');
            $db       = \CanariApp\Backend\Config\App::get('db.name');
            $user     = \CanariApp\Backend\Config\App::get('db.user');
            $password = \CanariApp\Backend\Config\App::get('db.password');

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