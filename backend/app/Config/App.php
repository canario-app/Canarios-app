<?php

declare(strict_types=1);

namespace Backend\CanariosApp\Config;

use DateTimeZone;
use Dotenv\Dotenv;
use RuntimeException;

/**
 * Configuración general del backend de CanariApp.
 *
 * Carga las variables desde backend/.env. Si ese archivo todavía no existe,
 * utiliza backend/.env.example como configuración temporal de desarrollo.
 *
 * Ejemplos de uso:
 *   App::boot();
 *   $baseUrl = App::get('url');
 *   $logsPath = App::get('paths.logs');
 */
final class App
{
    /** Indica si la configuración ya fue inicializada durante esta ejecución. */
    private static bool $initialized = false;

    /** @var array<string, mixed> */
    private static array $configuration = [];

    private function __construct()
    {
    }

    /**
     * Inicializa la configuración y carga las variables de entorno.
     *
     * Puede llamarse desde el punto de entrada de la aplicación. Los métodos
     * get() y all() también inicializan la configuración automáticamente.
     */
    public static function boot(): void
    {
        self::initialize();
    }

    /**
     * Obtiene un valor de configuración.
     *
     * Admite claves anidadas usando puntos, por ejemplo: paths.logs.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::initialize();

        $value = self::$configuration;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Devuelve toda la configuración cargada.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        self::initialize();

        return self::$configuration;
    }

    /**
     * Carga el archivo de entorno y construye la configuración del proyecto.
     */
    private static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // App.php está en backend/app/Config; dos niveles arriba está backend/.
        $projectRoot = dirname(__DIR__, 2);
        $envFile = is_file($projectRoot . DIRECTORY_SEPARATOR . '.env')
            ? '.env'
            : '.env.example';

        if (!class_exists(Dotenv::class)) {
            throw new RuntimeException(
                'No se encontró phpdotenv. Ejecuta "composer install" dentro de backend/.'
            );
        }

        Dotenv::createImmutable($projectRoot, $envFile)->safeLoad();

        $timezone = self::environmentValue('APP_TIMEZONE', date_default_timezone_get());

        try {
            // Valida la zona horaria antes de aplicarla a las funciones de fecha de PHP.
            new DateTimeZone($timezone);
        } catch (\Exception $exception) {
            throw new RuntimeException(
                sprintf('La zona horaria configurada en APP_TIMEZONE no es válida: %s', $timezone),
                0,
                $exception
            );
        }

        date_default_timezone_set($timezone);

        $debugValue = filter_var(
            self::environmentValue('APP_DEBUG', 'false'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        $baseUrl = rtrim(self::environmentValue('APP_URL', 'http://localhost'), '/');

        self::$configuration = [
            'name' => self::environmentValue('APP_NAME', 'CanariApp'),
            'environment' => self::environmentValue('APP_ENV', 'local'),
            'debug' => $debugValue ?? false,
            'url' => $baseUrl,
            'timezone' => $timezone,
            'paths' => [
                'root' => $projectRoot,
                'app' => $projectRoot . DIRECTORY_SEPARATOR . 'app',
                'config' => $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config',
                'public' => $projectRoot . DIRECTORY_SEPARATOR . 'public',
                'storage' => $projectRoot . DIRECTORY_SEPARATOR . 'storage',
                'logs' => $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',
            ],
        ];

        self::$initialized = true;
    }

    /**
     * Lee una variable de entorno desde las fuentes disponibles.
     */
    private static function environmentValue(string $name, string $default): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if ($value === false || $value === null || trim((string) $value) === '') {
            return $default;
        }

        return trim((string) $value);
    }
}
