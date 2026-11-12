<?php

declare(strict_types=1);

namespace CanariApp\Backend\Middlewares;

use CanariApp\Backend\Helpers\AuthHelper;
use CanariApp\Backend\Helpers\ResponseHelper;

final class AuthMiddleware {
    private static ?object $currentUser = null;

    /**
     * Verifica que la petición incluya un token Bearer válido.
     */
    public static function handle(): void {
        $token = self::extractBearerToken();

        if ($token === null) {
            ResponseHelper::unauthorized('Token de autenticación no proporcionado.');
        }

        $payload = AuthHelper::validateToken($token);

        if ($payload === null) {
            ResponseHelper::unauthorized('Token inválido o expirado.');
        }

        self::$currentUser = $payload;
    }

    /**
     * Devuelve el payload completo del usuario autenticado en la petición actual.
     */
    public static function user(): ?object {
        return self::$currentUser;
    }

    /**
     * Devuelve el ID (claim 'sub') del usuario autenticado.
     */
    public static function userId(): int|string|null {
        return self::$currentUser?->sub ?? null;
    }

    /**
     * Extrae el token del encabezado Authorization contemplando Apache, Nginx y PHP built-in.
     */
    private static function extractBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if ($header === null && function_exists('getallheaders')) {
            $headers = array_change_key_case(getallheaders(), CASE_LOWER);
            $header = $headers['authorization'] ?? null;
        }

        if (!is_string($header) || preg_match('/^Bearer\s+(\S+)$/i', trim($header), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}