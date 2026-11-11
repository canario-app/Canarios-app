<?php

declare(strict_types=1);

use CanariApp\Backend\Router;
use CanariApp\Backend\Helpers\ResponseHelper;
use CanariApp\Backend\Config\App;

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

// Carga las variables de entorno
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
}

// Inicializa la configuración global (incluyendo la zona horaria)
App::boot();

// Evita mostrar errores si debug es false
if (!App::get('debug', false)) {
    ini_set('display_errors', '0');
}

try {
    $router = new Router();
    $router->run();
} catch (Throwable $exception) {
    error_log((string) $exception);

    if (App::get('debug', false)) {
        ResponseHelper::internalError($exception->getMessage());
    }

    ResponseHelper::internalError('Ocurrió un error interno en el servidor.');
}