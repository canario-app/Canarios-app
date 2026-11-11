<?php
#composer install (para activartlo ojo es donde la carpeta cd "F:\Universidad\Canarios-app-main\backend")
#php -S localhost:8000 -t public (para que la pagina sirva recuerda guardar y cerrar el cervidor si has cambiado algo)
declare(strict_types=1);

namespace Backend\CanariosApp;

use Bramus\Router\Router as BramusRouter;

/**
 * Registra y ejecuta los puntos de entrada HTTP de la API.
 *
 * Las operaciones de negocio se conectarán aquí cuando sus controladores
 * estén implementados.
 */
final class Router
#http://localhost:8000/ los otros solo pone las cosas de get despues del/ 
{
    private BramusRouter $router;

    public function __construct()
    {
        $this->router = new BramusRouter();
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        // Punto de entrada general.
        $this->router->get('/', function (): void {
            $this->jsonResponse([
                'success' => true,
                'message' => 'API de Canarios-app',
                'version' => 'v1',
                'documentation' => '/api/v1',
            ]);
        });

        // Comprobación para verificar que el backend está disponible.
        $this->router->get('/api/health', function (): void {
            $this->jsonResponse([
                'success' => true,
                'status' => 'ok',
                'message' => 'Backend disponible',
            ]);
        });

        // Índice de la API. No implica que los módulos estén implementados.
        $this->router->get('/api/v1', function (): void {
            $this->jsonResponse([
                'success' => true,
                'message' => 'API Canarios-app v1',
                'endpoints' => [
                    'health' => '/api/health',
                ],
            ]);
        });
        $this->router->get('/gato', function (): void {
            header('Content-Type: text/plain; charset=utf-8');

            echo "
        ╱|、
        (˚ˎ 。7 
        |、˜〵 
        じしˍ,)ノ
          miau
        ";
        });
        // Respuesta uniforme cuando una ruta no existe.
        $this->router->set404(function (): void {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Not Found',
                'message' => 'La ruta solicitada no existe.',
            ], 404);
        });
    }

    /**
     * Envía una respuesta JSON y establece el código HTTP.
     *
     * @param array<string, mixed> $data
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public function run(): void
    {
        $this->router->run();
    }
}
