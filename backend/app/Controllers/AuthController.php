<?php

declare(strict_types=1);

namespace CanariApp\Backend\Controllers;

use CanariApp\Backend\Helpers\ResponseHelper;
use CanariApp\Backend\Helpers\AuthHelper;
use CanariApp\Backend\Models\User;

class AuthController {
    
    /**
     * Inicia sesión verificando credenciales y devolviendo un JWT.
     */
    public function login(): void {
        // Leemos el JSON que envía el frontend
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!$email || !$password) {
            ResponseHelper::badRequest('El correo y la contraseña son obligatorios.');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        // Si el usuario no existe, o si la contraseña no coincide, rechazamos
        if (!$user || !$userModel->verifyPassword($password, $user['password'])) {
            ResponseHelper::unauthorized('Credenciales incorrectas.');
        }

        // Generamos el token usando el Helper
        $token = AuthHelper::generateToken($user['id'], $user['email']);

        // Ocultamos la contraseña encriptada antes de devolver los datos al cliente
        unset($user['password']);

        ResponseHelper::success([
            'token' => $token,
            'user' => $user
        ], 'Inicio de sesión exitoso.');
    }

    /**
     * Registra un nuevo usuario y devuelve un token para sesión automática.
     */
    public function register(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = $input['name'] ?? null;
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!$name || !$email || !$password) {
            ResponseHelper::badRequest('Nombre, correo y contraseña son obligatorios.');
        }

        $userModel = new User();
        
        // Verificamos que el email no esté tomado
        if ($userModel->findByEmail($email)) {
            ResponseHelper::conflict('El correo ya está registrado en el sistema.');
        }

        // Creamos al usuario (User.php se encargará de encriptar el password)
        $userId = $userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $password
        ]);

        // Generamos un token para que ya entre logueado
        $token = AuthHelper::generateToken($userId, $email);

        ResponseHelper::created([
            'token' => $token,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email
            ]
        ], 'Usuario registrado exitosamente.');
    }
}