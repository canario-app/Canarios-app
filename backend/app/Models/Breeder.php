<?php

declare(strict_types=1);

namespace CanariApp\Backend\Models;

use CanariApp\Backend\Config\Db;
use Throwable;

class Breeder extends BaseModel {
    protected string $table = 'breeders';
    protected string $primaryKey = 'id';

    /**
     * Busca el perfil de criador asociado a un ID de usuario (del token JWT).
     */
    public function findByUserId(int|string $userId): ?array {
        $sql = "SELECT b.id, b.user_id, b.member_number, b.created_at,
                       u.name, u.email, u.role
                FROM {$this->table} b
                INNER JOIN users u ON b.user_id = u.id
                WHERE b.user_id = :user_id
                LIMIT 1";

        return $this->fetch($sql, ['user_id' => $userId]);
    }

    /**
     * Obtiene un criador por su PK junto con los datos de su cuenta de usuario.
     */
    public function findWithUser(int|string $breederId): ?array {
        $sql = "SELECT b.id, b.user_id, b.member_number, b.created_at,
                       u.name, u.email, u.role
                FROM {$this->table} b
                INNER JOIN users u ON b.user_id = u.id
                WHERE b.id = :id
                LIMIT 1";

        return $this->fetch($sql, ['id' => $breederId]);
    }

    /**
     * Lista todos los criadores ordenados del más reciente al más antiguo.
     */
    public function findAllWithUser(): array {
        $sql = "SELECT b.id, b.user_id, b.member_number, b.created_at,
                       u.name, u.email, u.role
                FROM {$this->table} b
                INNER JOIN users u ON b.user_id = u.id
                ORDER BY b.id DESC";

        return $this->fetchAll($sql);
    }

    /**
     * Crea un perfil de criador vinculado a un usuario existente.
     */
    public function create(array $data): int|string|null {
        return $this->insert([
            'user_id'       => $data['user_id'],
            'member_number' => $data['member_number'] ?? null,
        ]);
    }

    /**
     * Busca criadores por coincidencia parcial en el nombre del usuario.
     */
    public function searchByName(string $name): array {
        $sql = "SELECT b.id, b.user_id, b.member_number, b.created_at,
                       u.name, u.email, u.role
                FROM {$this->table} b
                INNER JOIN users u ON b.user_id = u.id
                WHERE u.name ILIKE :name
                ORDER BY u.name ASC";

        return $this->fetchAll($sql, [
            'name' => '%' . trim($name) . '%',
        ]);
    }

    /**
     * Actualiza el perfil de criador y su cuenta de usuario asociada en una sola transacción.
     */
    public function updateWithUser(int $breederId, array $data): array|string {
        $db = Db::getConnection();

        try {
            $db->beginTransaction();

            // 1. Verificar que el criador exista y obtener su user_id
            $stmtFind = $db->prepare('SELECT user_id FROM breeders WHERE id = :id');
            $stmtFind->execute(['id' => $breederId]);
            $userId = $stmtFind->fetchColumn();

            if ($userId === false) {
                $db->rollBack();
                return 'not_found';
            }
            $userId = (int) $userId;

            // 2. Verificar que el correo o número de socio no estén ocupados por otro registro
            $stmtDup = $db->prepare(
                'SELECT 1 FROM users u
                 INNER JOIN breeders b ON b.user_id = u.id
                 WHERE (u.email = :email AND u.id <> :user_id)
                    OR (b.member_number = :member_number AND b.id <> :breeder_id)'
            );
            $stmtDup->execute([
                'email'         => $data['email'],
                'user_id'       => $userId,
                'member_number' => $data['member_number'],
                'breeder_id'    => $breederId,
            ]);

            if ($stmtDup->fetchColumn() !== false) {
                $db->rollBack();
                return 'conflict';
            }

            // 3. Actualizar tabla breeders
            $stmtBreeder = $db->prepare(
                'UPDATE breeders SET member_number = :member_number WHERE id = :breeder_id'
            );
            $stmtBreeder->execute([
                'member_number' => $data['member_number'],
                'breeder_id'    => $breederId,
            ]);

            // 4. Actualizar tabla users (con o sin cambio de contraseña)
            if (!empty($data['password'])) {
                $stmtUser = $db->prepare(
                    'UPDATE users SET name = :name, email = :email, password = :password WHERE id = :user_id'
                );
                $stmtUser->execute([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                    'user_id'  => $userId,
                ]);
            } else {
                $stmtUser = $db->prepare(
                    'UPDATE users SET name = :name, email = :email WHERE id = :user_id'
                );
                $stmtUser->execute([
                    'name'    => $data['name'],
                    'email'   => $data['email'],
                    'user_id' => $userId,
                ]);
            }

            $db->commit();

            return [
                'id'            => $breederId,
                'user_id'       => $userId,
                'name'          => $data['name'],
                'email'         => $data['email'],
                'member_number' => $data['member_number'],
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Elimina un criador junto con su cuenta de usuario.
     */
    public function deleteWithUser(int $breederId): bool {
        $db = Db::getConnection();

        try {
            $db->beginTransaction();

            $stmtBreeder = $db->prepare('DELETE FROM breeders WHERE id = :id RETURNING user_id');
            $stmtBreeder->execute(['id' => $breederId]);
            $userId = $stmtBreeder->fetchColumn();

            if ($userId === false) {
                $db->rollBack();
                return false;
            }

            $stmtUser = $db->prepare('DELETE FROM users WHERE id = :user_id AND role = :role');
            $stmtUser->execute([
                'user_id' => (int) $userId,
                'role'    => 'breeder',
            ]);

            $db->commit();

            return $stmtUser->rowCount() > 0;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}