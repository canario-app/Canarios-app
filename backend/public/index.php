<?php

declare(strict_types=1);

use Backend\CanariosApp\Router;

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

// Carga las variables de entorno si existe el archivo .env.
// En instalaciones limpias se puede partir desde .env.example.
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
}

// Carga explícita porque composer.json actualmente apunta el autoload PSR-4
// a src/, mientras que Router.php está ubicado en app/.
require_once BASE_PATH . '/app/Router.php';

// Evita mostrar errores internos al cliente en ambientes productivos.
if (($_ENV['APP_DEBUG'] ?? 'false') !== 'true') {
    ini_set('display_errors', '0');
}

try {
    $router = new Router();
    $router->run();
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => 'Ocurrió un error interno en el servidor.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
