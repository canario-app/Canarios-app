<?php

declare(strict_types=1);

namespace CanariApp\Backend\Helpers;

use CanariApp\Backend\Config\App;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthHelper {
    
    /**
     * Genera un token JWT válido por un tiempo determinado (ej. 2 horas).
     */
    public static function generateToken(int|string $userId, string $email): string {
        $secret = App::get('jwt.secret');
        $issuedAt = time();
        $expirationTime = $issuedAt + (2 * 3600); // 2 horas

        $payload = [
            'iat' => $issuedAt,        // Cuándo se emitió
            'exp' => $expirationTime,  // Cuándo expira
            'sub' => $userId,          // Sujeto (ID del usuario)
            'email' => $email          // Datos extra útiles
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Valida un token JWT. Retorna el payload si es válido, o null si fue alterado o expiró.
     */
    public static function validateToken(string $token): ?object {
        try {
            $secret = App::get('jwt.secret');
            // Decodifica y valida automáticamente la firma y la fecha de expiración
            return JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Exception $e) {
            // Si el token expiró, está mal formado o la firma no coincide, cae aquí.
            return null;
        }
    }
}