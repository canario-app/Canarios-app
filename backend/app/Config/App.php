<?php

declare(strict_types=1);

namespace CanariApp\Backend\Config;

use DateTimeZone;
use RuntimeException;

/**
 * Configuración general del backend de CanariApp.
 */
final class App{
    private static bool $initialized = false;
    private static array $configuration = [];

    private function __construct() {}

    public static function boot(): void{
        self::initialize();
    }

    public static function get(string $key, mixed $default = null): mixed{
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

    public static function all(): array{
        self::initialize();
        return self::$configuration;
    }

    private static function initialize(): void{
        if (self::$initialized) {
            return;
        }

        $projectRoot = dirname(__DIR__, 2);
        
        // Asumimos que Dotenv ya fue cargado en public/index.php
        $timezone = self::environmentValue('APP_TIMEZONE', date_default_timezone_get());

        try {
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
            'jwt' => [
                'secret' => self::environmentValue('JWT_SECRET', 'clave_por_defecto_insegura'),
            ],
            'db' => [
                'host' => self::environmentValue('DB_HOST', 'postgres'),
                'port' => self::environmentValue('DB_PORT', '5432'),
                'name' => self::environmentValue('DB_NAME', 'mi_base_datos'),
                'user' => self::environmentValue('DB_USER', 'postgres'),
                'password' => self::environmentValue('DB_PASSWORD', 'password'),
            ],
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

    private static function environmentValue(string $name, string $default): string{
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if ($value === false || $value === null || trim((string) $value) === '') {
            return $default;
        }

        return trim((string) $value);
    }
}