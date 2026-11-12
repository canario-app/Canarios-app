<?php

declare(strict_types=1);

namespace CanariApp\Backend\Models;

class User extends BaseModel {
    protected string $table = 'users';

    /**
     * Busca un usuario por su correo electrónico.
     */
    public function findByEmail(string $email): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->fetch($sql, ['email' => $email]);
    }

    /**
     * Crea un nuevo usuario encriptando la contraseña automáticamente.
     */
    public function create(array $data): int|string|null {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        return $this->insert($data);
    }

    /**
     * Verifica si una contraseña en texto plano coincide con el hash de la BD.
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}