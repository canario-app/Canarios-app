<?php

declare(strict_types=1);

namespace CanariApp\Backend\Controllers;

use CanariApp\Backend\Helpers\ResponseHelper;
use CanariApp\Backend\Middlewares\AuthMiddleware;
use CanariApp\Backend\Models\Criador;
use CanariApp\Backend\Models\User;
use PDOException;

class CriadorController {
    /**
     * [Criador] Obtiene el perfil del usuario autenticado.
     */
    public function miPerfil(): void {
        $userId = AuthMiddleware::userId();
        $criadorModel = new Criador();
        $perfil = $criadorModel->findByUsuarioId($userId);

        if ($perfil === null) {
            ResponseHelper::notFound('El usuario actual no tiene un perfil de criador asociado.');
        }

        ResponseHelper::success($perfil, 'Perfil de criador obtenido.');
    }

    /**
     * [Criador] Actualiza el número de socio del perfil propio.
     */
    public function actualizarPerfil(): void {
        $userId = AuthMiddleware::userId();
        $criadorModel = new Criador();
        $perfil = $criadorModel->findByUsuarioId($userId);

        if ($perfil === null) {
            ResponseHelper::notFound('El usuario actual no tiene un perfil de criador asociado.');
        }

        $input = $this->getJsonInput();
        $numeroSocio = $this->validateNumeroSocio($input['numero_socio'] ?? null);

        $criadorModel->update($perfil['id_criador'], [
            'numero_socio' => $numeroSocio,
        ]);

        $actualizado = $criadorModel->findWithUsuario($perfil['id_criador']);
        ResponseHelper::success($actualizado, 'Perfil actualizado correctamente.');
    }

    /**
     * [Admin] Lista todos los criadores o filtra por nombre si se envía ?nombre=...
     */
    public function index(): void {
        $criadorModel = new Criador();
        $nombre = trim((string) ($_GET['nombre'] ?? ''));

        $criadores = $nombre !== ''
            ? $criadorModel->searchByNombre($nombre)
            : $criadorModel->findAllWithUsuario();

        ResponseHelper::success($criadores, 'Listado de criadores obtenido.');
    }

    /**
     * [Admin] Muestra el detalle de un criador específico.
     */
    public function show(int|string $id): void {
        $criadorModel = new Criador();
        $criador = $criadorModel->findWithUsuario((int) $id);

        if ($criador === null) {
            ResponseHelper::notFound('Criador no encontrado.');
        }

        ResponseHelper::success($criador, 'Detalle del criador obtenido.');
    }

    /**
     * [Admin] Vincula manualmente un usuario existente como criador.
     */
    public function store(): void {
        $input = $this->getJsonInput();
        $idUsuario = $input['id_usuario'] ?? null;

        if (!is_numeric($idUsuario) || (int) $idUsuario <= 0) {
            ResponseHelper::badRequest('El campo id_usuario es obligatorio y debe ser un entero válido.');
        }

        $idUsuario = (int) $idUsuario;
        $numeroSocio = $this->validateNumeroSocio($input['numero_socio'] ?? null);

        $userModel = new User();
        if ($userModel->find($idUsuario) === null) {
            ResponseHelper::notFound('El usuario indicado no existe.');
        }

        $criadorModel = new Criador();
        if ($criadorModel->findByUsuarioId($idUsuario) !== null) {
            ResponseHelper::conflict('Este usuario ya tiene un perfil de criador asignado.');
        }

        $nuevoId = $criadorModel->create([
            'id_usuario' => $idUsuario,
            'numero_socio' => $numeroSocio,
        ]);

        $creado = $criadorModel->findWithUsuario($nuevoId);
        ResponseHelper::created($creado, 'Criador creado exitosamente.');
    }

    /**
     * [Admin] Actualiza los datos de un criador por su ID.
     */
    public function update(int|string $id): void {
        $idCriador = (int) $id;
        $criadorModel = new Criador();

        if ($criadorModel->find($idCriador) === null) {
            ResponseHelper::notFound('Criador no encontrado.');
        }

        $input = $this->getJsonInput();
        $numeroSocio = $this->validateNumeroSocio($input['numero_socio'] ?? null);

        $criadorModel->update($idCriador, [
            'numero_socio' => $numeroSocio,
        ]);

        $actualizado = $criadorModel->findWithUsuario($idCriador);
        ResponseHelper::success($actualizado, 'Criador actualizado exitosamente.');
    }

    /**
     * [Admin] Elimina un perfil de criador si no tiene registros dependientes.
     */
    public function destroy(int|string $id): void {
        $idCriador = (int) $id;
        $criadorModel = new Criador();

        if ($criadorModel->find($idCriador) === null) {
            ResponseHelper::notFound('Criador no encontrado.');
        }

        try {
            $criadorModel->delete($idCriador);
        } catch (PDOException $e) {
            // Código 23503 en PostgreSQL: violación de llave foránea (ej. tiene canarios asociados)
            if ($e->getCode() === '23503') {
                ResponseHelper::conflict('No se puede eliminar el criador porque tiene canarios registrados.');
            }
            throw $e;
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
    private function validateNumeroSocio(mixed $value): ?string {
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