<?php

declare(strict_types=1);

use CanariApp\Backend\Router;
use CanariApp\Backend\Helpers\ResponseHelper;

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

// Carga las variables de entorno si existe el archivo .env.
// En instalaciones limpias se puede partir desde .env.example.
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
}

// Evita mostrar errores internos al cliente en ambientes productivos.
if (($_ENV['APP_DEBUG'] ?? 'false') !== 'true') {
    ini_set('display_errors', '0');
}

try {
    $router = new Router();
    $router->run();
} catch (Throwable $exception) {
    // 1. Siempre guardamos el error real en los logs del servidor
    error_log((string) $exception);

    // 2. Si estamos desarrollando (APP_DEBUG=true), mostramos el error real en el JSON
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        ResponseHelper::internalError($exception->getMessage());
    }

    // 3. Si estamos en producción, mostramos el mensaje genérico seguro
    ResponseHelper::internalError('Ocurrió un error interno en el servidor.');
}