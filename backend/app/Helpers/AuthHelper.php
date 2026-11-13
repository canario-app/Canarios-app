<?php

declare(strict_types=1);

namespace CanariApp\Backend\Helpers;

use CanariApp\Backend\Config\App;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthHelper {
    /**
     * Genera un token JWT incluyendo el ID, correo y rol del usuario.
     */
    public static function generateToken(int|string $userId, string $correo, string $rol): string {
        $secret = App::get('jwt.secret');
        $issuedAt = time();
        $expirationTime = $issuedAt + (2 * 3600); // 2 horas

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $userId,
            'correo' => $correo,
            'rol' => $rol,
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Valida un token JWT. Retorna el payload si es válido, o null si fue alterado o expiró.
     */
    public static function validateToken(string $token): ?object {
        try {
            $secret = App::get('jwt.secret');
            return JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Exception $e) {
            return null;
        }
    }
}