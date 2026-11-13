<?php

declare(strict_types=1);

namespace CanariApp\Backend\Models;

class User extends BaseModel {
    protected string $table = 'usuario';
    protected string $primaryKey = 'id_usuario';

    /**
     * Busca un usuario por su correo electrónico.
     */
    public function findByCorreo(string $correo): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE correo = :correo LIMIT 1";
        return $this->fetch($sql, ['correo' => $correo]);
    }

    /**
     * Crea un nuevo usuario encriptando la contraseña en password_hash.
     */
    public function create(array $data): int|string|null {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);
        }

        // Asegura cumplir el CHECK (rol IN ('criador','administrador'))
        $data['rol'] ??= 'criador';

        return $this->insert($data);
    }

    /**
     * Verifica si una contraseña en texto plano coincide con password_hash.
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}