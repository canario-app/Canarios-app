<?php

declare(strict_types=1);

namespace CanariApp\Backend;

use Bramus\Router\Router as BramusRouter;
use CanariApp\Backend\Controllers\AuthController;
use CanariApp\Backend\Helpers\ResponseHelper;
use CanariApp\Backend\Middlewares\AuthMiddleware;

final class Router {
    private BramusRouter $router;

    public function __construct() {
        $this->router = new BramusRouter();
        $this->registerRoutes();
    }

    private function registerRoutes(): void {
        // --- RUTAS PÚBLICAS ---
        $this->router->get('/', function (): void {
            ResponseHelper::success(
                [
                    'version' => 'v1',
                    'documentation' => '/api/v1',
                ],
                'API de Canarios-app'
            );
        });

        $this->router->get('/api/health', function (): void {
            ResponseHelper::success(
                ['service' => 'ok'],
                'Backend disponible'
            );
        });

        $this->router->get('/api/v1', function (): void {
            ResponseHelper::success(
                [
                    'endpoints' => [
                        'health' => '/api/health',
                        'login' => '/api/login',
                        'register' => '/api/register',
                    ],
                ],
                'API Canarios-app v1'
            );
        });

        $this->router->post('/api/login', AuthController::class . '@login');
        $this->router->post('/api/register', AuthController::class . '@register');

        // --- MIDDLEWARE DE SEGURIDAD ---
        // Protege cualquier subruta dentro de /api/v1/* (sin afectar /api/v1 exacto)
        $this->router->before('GET|POST|PUT|PATCH|DELETE', '/api/v1/.+', function (): void {
            AuthMiddleware::handle();
        });

        // --- RUTAS PRIVADAS (Requieren Token) ---
        $this->router->get('/api/v1/me', function (): void {
            ResponseHelper::success(
                ['user' => AuthMiddleware::user()],
                'Usuario autenticado'
            );
        });

        // --- 404 ---
        $this->router->set404(function (): void {
            ResponseHelper::notFound('La ruta solicitada no existe.');
        });
    }

    public function run(): void {
        $this->router->run();
    }
}