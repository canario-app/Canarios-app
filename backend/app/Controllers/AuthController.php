<?php

declare(strict_types=1);

namespace CanariApp\Backend\Controllers;

use CanariApp\Backend\Helpers\ResponseHelper;
use CanariApp\Backend\Helpers\AuthHelper;
use CanariApp\Backend\Models\Criador;
use CanariApp\Backend\Models\User;
use Throwable;

class AuthController {
    /**
     * Inicia sesión verificando credenciales y devolviendo un JWT.
     */
    public function login(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $correo = $input['correo'] ?? null;
        $password = $input['password'] ?? null;

        if (!$correo || !$password) {
            ResponseHelper::badRequest('El correo y la contraseña son obligatorios.');
        }

        $userModel = new User();
        $user = $userModel->findByCorreo($correo);

        if (!$user || !$userModel->verifyPassword($password, $user['password_hash'])) {
            ResponseHelper::unauthorized('Credenciales incorrectas.');
        }

        $token = AuthHelper::generateToken(
            $user['id_usuario'],
            $user['correo'],
            $user['rol']
        );

        unset($user['password_hash']);

        ResponseHelper::success([
            'token' => $token,
            'user' => $user,
        ], 'Inicio de sesión exitoso.');
    }

    /**
     * [Admin] Registra un nuevo usuario con rol 'criador' y crea su perfil asociado.
     */
    public function register(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $nombre = trim((string) ($input['nombre'] ?? ''));
        $correo = trim((string) ($input['correo'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $numeroSocioRaw = $input['numero_socio'] ?? null;

        if ($nombre === '' || $correo === '' || $password === '') {
            ResponseHelper::badRequest('Nombre, correo y contraseña son obligatorios.');
        }

        $numeroSocio = null;
        if ($numeroSocioRaw !== null && trim((string) $numeroSocioRaw) !== '') {
            $numeroSocio = trim((string) $numeroSocioRaw);
            if (mb_strlen($numeroSocio) > 5) {
                ResponseHelper::validationError([
                    'numero_socio' => 'El número de socio no puede exceder los 5 caracteres.',
                ]);
            }
        }

        $userModel = new User();

        if ($userModel->findByCorreo($correo)) {
            ResponseHelper::conflict('El correo ya está registrado en el sistema.');
        }

        $rol = 'criador';

        try {
            $userModel->beginTransaction();

            $userId = $userModel->create([
                'nombre' => $nombre,
                'correo' => $correo,
                'password' => $password,
                'rol' => $rol,
            ]);

            $criadorModel = new Criador();
            $criadorId = $criadorModel->create([
                'id_usuario' => $userId,
                'numero_socio' => $numeroSocio,
            ]);

            $userModel->commit();
        } catch (Throwable $e) {
            $userModel->rollBack();
            throw $e;
        }

        ResponseHelper::created([
            'user' => [
                'id_usuario' => $userId,
                'id_criador' => $criadorId,
                'nombre' => $nombre,
                'correo' => $correo,
                'rol' => $rol,
                'numero_socio' => $numeroSocio,
            ],
        ], 'Criador registrado exitosamente por el administrador.');
    }
}