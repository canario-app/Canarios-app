<?php

declare(strict_types=1);

namespace CanariApp\Backend\Controllers;

use CanariApp\Backend\Helpers\ResponseHelper;
use CanariApp\Backend\Middlewares\AuthMiddleware;
use CanariApp\Backend\Models\Breeder;
use CanariApp\Backend\Models\User;

class BreederController {
    /**
     * [Criador] Obtiene el perfil del usuario autenticado.
     */
    public function myProfile(): void {
        $userId = AuthMiddleware::userId();
        $breederModel = new Breeder();
        $profile = $breederModel->findByUserId($userId);

        if ($profile === null) {
            ResponseHelper::notFound('El usuario actual no tiene un perfil de criador asociado.');
        }

        ResponseHelper::success($profile, 'Perfil de criador obtenido.');
    }

    /**
     * [Criador] Actualiza el número de socio del perfil propio.
     */
    public function updateProfile(): void {
        $userId = AuthMiddleware::userId();
        $breederModel = new Breeder();
        $profile = $breederModel->findByUserId($userId);

        if ($profile === null) {
            ResponseHelper::notFound('El usuario actual no tiene un perfil de criador asociado.');
        }

        $input = $this->getJsonInput();
        $memberNumber = $this->validateMemberNumber($input['numero_socio'] ?? null);

        $breederModel->update($profile['id_criador'], [
            'numero_socio' => $memberNumber,
        ]);

        $updated = $breederModel->findWithUser($profile['id_criador']);
        ResponseHelper::success($updated, 'Perfil actualizado correctamente.');
    }

    /**
     * [Admin] Lista todos los criadores o filtra por nombre si se envía ?nombre=...
     */
    public function index(): void {
        $breederModel = new Breeder();
        $name = trim((string) ($_GET['nombre'] ?? ''));

        $breeders = $name !== ''
            ? $breederModel->searchByName($name)
            : $breederModel->findAllWithUser();

        ResponseHelper::success($breeders, 'Listado de criadores obtenido.');
    }

    /**
     * [Admin] Muestra el detalle de un criador específico.
     */
    public function show(int|string $id): void {
        $breederModel = new Breeder();
        $breeder = $breederModel->findWithUser((int) $id);

        if ($breeder === null) {
            ResponseHelper::notFound('Criador no encontrado.');
        }

        ResponseHelper::success($breeder, 'Detalle del criador obtenido.');
    }

    /**
     * [Admin] Vincula manualmente un usuario existente como criador.
     */
    public function store(): void {
        $input = $this->getJsonInput();
        $userId = $input['id_usuario'] ?? null;

        if (!is_numeric($userId) || (int) $userId <= 0) {
            ResponseHelper::badRequest('El campo id_usuario es obligatorio y debe ser un entero válido.');
        }

        $userId = (int) $userId;
        $memberNumber = $this->validateMemberNumber($input['numero_socio'] ?? null);

        $userModel = new User();
        if ($userModel->find($userId) === null) {
            ResponseHelper::notFound('El usuario indicado no existe.');
        }

        $breederModel = new Breeder();
        if ($breederModel->findByUserId($userId) !== null) {
            ResponseHelper::conflict('Este usuario ya tiene un perfil de criador asignado.');
        }

        $newId = $breederModel->create([
            'id_usuario'   => $userId,
            'numero_socio' => $memberNumber,
        ]);

        $created = $breederModel->findWithUser($newId);
        ResponseHelper::created($created, 'Criador creado exitosamente.');
    }

    /**
     * [Admin] Actualiza los datos de un criador y su cuenta de usuario por su ID.
     */
    public function update(int|string $id): void {
        $breederId = (int) $id;

        if ($breederId <= 0) {
            ResponseHelper::badRequest('El ID del criador no es válido.');
        }

        $breederModel = new Breeder();
        $existing = $breederModel->findWithUser($breederId);

        if ($existing === null) {
            ResponseHelper::notFound('Criador no encontrado.');
        }

        $input = $this->getJsonInput();

        $name = array_key_exists('nombre', $input)
            ? trim((string) $input['nombre'])
            : $existing['nombre'];

        $email = array_key_exists('correo', $input)
            ? trim((string) $input['correo'])
            : $existing['correo'];

        $memberNumber = array_key_exists('numero_socio', $input)
            ? $this->validateMemberNumber($input['numero_socio'])
            : $existing['numero_socio'];

        $password = isset($input['password']) ? trim((string) $input['password']) : null;

        $errors = [];
        if ($name === '') {
            $errors['nombre'] = 'El nombre es obligatorio.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['correo'] = 'El correo electrónico no es válido.';
        }
        if ($password !== null && $password !== '' && strlen($password) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        $result = $breederModel->updateWithUser($breederId, [
            'nombre'       => $name,
            'correo'       => $email,
            'numero_socio' => $memberNumber,
            'password'     => ($password !== null && $password !== '') ? $password : null,
        ]);

        if ($result === 'not_found') {
            ResponseHelper::notFound('Criador no encontrado.');
        }

        if ($result === 'conflict') {
            ResponseHelper::conflict('El correo o el número de socio ya está registrado por otro usuario.');
        }

        ResponseHelper::success($result, 'Criador actualizado exitosamente.');
    }

    /**
     * [Admin] Elimina un perfil de criador junto con su cuenta de usuario.
     */
    public function destroy(int|string $id): void {
        $breederId = (int) $id;

        if ($breederId <= 0) {
            ResponseHelper::badRequest('El ID del criador no es válido.');
        }

        $breederModel = new Breeder();
        $deleted = $breederModel->deleteWithUser($breederId);

        if (!$deleted) {
            ResponseHelper::notFound('No se encontró el criador especificado.');
        }

        ResponseHelper::noContent();
    }

    /**
     * Lee y decodifica el cuerpo JSON de la petición.
     */
    private function getJsonInput(): array {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            ResponseHelper::badRequest('El cuerpo de la petición debe ser un JSON válido.');
        }

        return $decoded;
    }

    /**
     * Valida y normaliza numero_socio (VARCHAR(5) nullable en PostgreSQL).
     */
    private function validateMemberNumber(mixed $value): ?string {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) && !is_numeric($value)) {
            ResponseHelper::validationError([
                'numero_socio' => 'El número de socio debe ser texto o nulo.',
            ]);
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        if (mb_strlen($trimmed) > 5) {
            ResponseHelper::validationError([
                'numero_socio' => 'El número de socio no puede exceder los 5 caracteres.',
            ]);
        }

        return $trimmed;
    }
}