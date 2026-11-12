<?php
declare(strict_types=1);

use CanariApp\Backend\Router;
use CanariApp\Backend\Helpers\ResponseHelper;
use CanariApp\Backend\Config\App;

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

try {
    // 1. Cargamos entorno y configuración DENTRO del try
    $envFile = BASE_PATH . '/.env';
    if (is_file($envFile)) {
        Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
    }
    
    App::boot();

    if (!App::get('debug', false)) {
        ini_set('display_errors', '0');
    }

    // 2. Ejecutamos el router
    $router = new Router();
    $router->run();

} catch (Throwable $exception) {
    error_log((string) $exception);
    
    // Si falla Dotenv o App::boot, ahora pasará por aquí y devolverá un JSON
    if (class_exists(App::class) && App::get('debug', false)) {
        ResponseHelper::internalError($exception->getMessage());
    }
    
    ResponseHelper::internalError('Ocurrió un error interno en el servidor.');
}