<?php
declare(strict_types=1);

namespace CanariApp\Backend;

use Bramus\Router\Router as BramusRouter;
use CanariApp\Backend\Helpers\ResponseHelper;

/**
 * Registra y ejecuta los puntos de entrada HTTP de la API.
 */
final class Router{
    private BramusRouter $router;

    public function __construct(){
        $this->router = new BramusRouter();
        $this->registerRoutes();
    }

    private function registerRoutes(): void{
        // Punto de entrada general.
        $this->router->get('/', function (): void {
            // Enviamos solo la "data" en el primer parámetro, y el "message" en el segundo.
            ResponseHelper::success(
                [
                    'version' => 'v1',
                    'documentation' => '/api/v1'
                ],
                'API de Canarios-app'
            );
        });

        // Comprobación para verificar que el backend está disponible.
        $this->router->get('/api/health', function (): void {
            ResponseHelper::success(
                ['service' => 'ok'],
                'Backend disponible'
            );
        });

        // Índice de la API.
        $this->router->get('/api/v1', function (): void {
            ResponseHelper::success(
                [
                    'endpoints' => [
                        'health' => '/api/health'
                    ]
                ],
                'API Canarios-app v1'
            );
        });

        // Respuesta uniforme cuando una ruta no existe.
        $this->router->set404(function (): void {
            // Usamos directamente el método notFound del helper
            ResponseHelper::notFound('La ruta solicitada no existe.');
        });
    }

    public function run(): void{
        $this->router->run();
    }
}