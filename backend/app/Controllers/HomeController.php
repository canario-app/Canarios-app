<?php

declare(strict_types=1);

namespace CanariApp\Backend\Controllers;

use CanariApp\Backend\Helpers\ResponseHelper;

class HomeController {
    /**
     * Endpoint raíz de bienvenida a la API.
     */
    public function index(): void {
        ResponseHelper::success([
            'app' => 'CanariApp API',
            'version' => 'v1',
            'documentation' => '/api/v1',
        ], 'Bienvenido a la API de CanariApp.');
    }

    /**
     * Comprueba que el servidor PHP esté respondiendo correctamente.
     */
    public function health(): void {
        ResponseHelper::success([
            'service' => 'ok',
            'timestamp' => date('c'),
        ], 'Backend disponible.');
    }

    /**
     * Devuelve el mapa general de endpoints públicos y privados de la v1.
     */
    public function v1(): void {
        ResponseHelper::success([
            'public_endpoints' => [
                'health' => 'GET /api/health',
                'login' => 'POST /api/login',
            ],
            'private_endpoints' => [
                'me' => 'GET /api/v1/me',
                'criador_perfil' => 'GET|PUT /api/v1/criador/perfil',
                'admin_register' => 'POST /api/v1/admin/register',
                'admin_criadores' => 'GET|POST|PUT|DELETE /api/v1/admin/criadores',
            ],
        ], 'API CanariApp v1');
    }
}