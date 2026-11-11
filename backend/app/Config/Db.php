<?php

// Subimos 2 niveles: de Config -> app -> backend/
$rootPath = dirname(DIR, 2);

require_once $rootPath . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Buscamos el archivo .env en la raíz de backend/
$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

$host     = $_ENV['DB_HOST']     ?? $_SERVER['DB_HOST']     ?? 'postgres';
$port     = $_ENV['DB_PORT']     ?? $_SERVER['DB_PORT']     ?? '5432';
$db       = $_ENV['DB_NAME']     ?? $_SERVER['DB_NAME']     ?? 'mi_base_datos';
$user     = $_ENV['DB_USER']     ?? $_SERVER['DB_USER']     ?? 'postgres';
$password = $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? 'password';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}