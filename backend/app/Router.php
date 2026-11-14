<?php

declare(strict_types=1);

namespace CanariApp\Backend;

use Bramus\Router\Router as BramusRouter;
use CanariApp\Backend\Controllers\AuthController;
use CanariApp\Backend\Controllers\BreederController;
use CanariApp\Backend\Controllers\HomeController;
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
        $this->router->get('/', HomeController::class . '@index');
        $this->router->get('/api/health', HomeController::class . '@health');
        $this->router->get('/api/v1', HomeController::class . '@v1');

        $this->router->post('/api/login', AuthController::class . '@login');

        // --- MIDDLEWARES DE SEGURIDAD ---
        $this->router->before('GET|POST|PUT|PATCH|DELETE', '/api/v1/.+', function (): void {
            AuthMiddleware::handle();
        });

        $this->router->before('GET|POST|PUT|PATCH|DELETE', '/api/v1/admin/.+', function (): void {
            AuthMiddleware::requireRole('administrador');
        });

        // --- RUTAS PRIVADAS ---
        $this->router->get('/api/v1/me', function (): void {
            ResponseHelper::success(
                ['user' => AuthMiddleware::user()],
                'Usuario autenticado.'
            );
        });

        // Perfil del criador autenticado
        $this->router->get('/api/v1/criador/perfil', BreederController::class . '@myProfile');
        $this->router->put('/api/v1/criador/perfil', BreederController::class . '@updateProfile');

        // Gestión administrativa de criadores
        $this->router->get('/api/v1/admin/criadores', BreederController::class . '@index');
        $this->router->get('/api/v1/admin/criadores/(\d+)', BreederController::class . '@show');

        $this->router->post('/api/v1/admin/register', AuthController::class . '@register');
        $this->router->post('/api/v1/admin/criadores', BreederController::class . '@store');
        
        $this->router->put('/api/v1/admin/criadores/(\d+)', BreederController::class . '@update');
        
        $this->router->delete('/api/v1/admin/criadores/(\d+)', BreederController::class . '@destroy');

        // --- 404 ---
        $this->router->set404(function (): void {
            ResponseHelper::notFound('La ruta solicitada no existe.');
        });
    }

    public function run(): void {
        $this->router->run();
    }
}