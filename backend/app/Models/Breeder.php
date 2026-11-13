<?php

declare(strict_types=1);

namespace CanariApp\Backend\Models;

use CanariApp\Backend\Config\Db;
use Throwable;

class Breeder extends BaseModel {
    protected string $table = 'criador';
    protected string $primaryKey = 'id_criador';

    /**
     * Busca el perfil de criador asociado a un ID de usuario (del token JWT).
     */
    public function findByUserId(int|string $userId): ?array {
        $sql = "SELECT c.id_criador, c.id_usuario, c.numero_socio, c.fecha_creacion,
                       u.nombre, u.correo, u.rol
                FROM {$this->table} c
                INNER JOIN usuario u ON c.id_usuario = u.id_usuario
                WHERE c.id_usuario = :id_usuario
                LIMIT 1";

        return $this->fetch($sql, ['id_usuario' => $userId]);
    }

    /**
     * Obtiene un criador por su PK junto con los datos de su cuenta de usuario.
     */
    public function findWithUser(int|string $breederId): ?array {
        $sql = "SELECT c.id_criador, c.id_usuario, c.numero_socio, c.fecha_creacion,
                       u.nombre, u.correo, u.rol
                FROM {$this->table} c
                INNER JOIN usuario u ON c.id_usuario = u.id_usuario
                WHERE c.id_criador = :id_criador
                LIMIT 1";

        return $this->fetch($sql, ['id_criador' => $breederId]);
    }

    /**
     * Lista todos los criadores ordenados del más reciente al más antiguo.
     */
    public function findAllWithUser(): array {
        $sql = "SELECT c.id_criador, c.id_usuario, c.numero_socio, c.fecha_creacion,
                       u.nombre, u.correo, u.rol
                FROM {$this->table} c
                INNER JOIN usuario u ON c.id_usuario = u.id_usuario
                ORDER BY c.id_criador DESC";

        return $this->fetchAll($sql);
    }

    /**
     * Crea un perfil de criador vinculado a un usuario existente.
     */
    public function create(array $data): int|string|null {
        return $this->insert([
            'id_usuario'   => $data['id_usuario'],
            'numero_socio' => $data['numero_socio'] ?? null,
        ]);
    }

    /**
     * Busca criadores por coincidencia parcial en el nombre del usuario.
     */
    public function searchByName(string $name): array {
        $sql = "SELECT c.id_criador, c.id_usuario, c.numero_socio, c.fecha_creacion,
                       u.nombre, u.correo, u.rol
                FROM {$this->table} c
                INNER JOIN usuario u ON c.id_usuario = u.id_usuario
                WHERE u.nombre ILIKE :nombre
                ORDER BY u.nombre ASC";

        return $this->fetchAll($sql, [
            'nombre' => '%' . trim($name) . '%',
        ]);
    }

    /**
     * Actualiza el perfil de criador y su cuenta de usuario asociada en una sola transacción.
     */
    public function updateWithUser(int $breederId, array $data): array|string {
        $db = Db::getConnection();

        try {
            $db->beginTransaction();

            // 1. Verificar que el criador exista y obtener su id_usuario
            $stmtFind = $db->prepare('SELECT id_usuario FROM criador WHERE id_criador = :id_criador');
            $stmtFind->execute(['id_criador' => $breederId]);
            $userId = $stmtFind->fetchColumn();

            if ($userId === false) {
                $db->rollBack();
                return 'not_found';
            }
            $userId = (int) $userId;

            // 2. Verificar que el correo o número de socio no estén ocupados por otro registro
            $stmtDup = $db->prepare(
                'SELECT 1 FROM usuario u
                 INNER JOIN criador c ON c.id_usuario = u.id_usuario
                 WHERE (u.correo = :correo AND u.id_usuario <> :id_usuario)
                    OR (c.numero_socio = :numero_socio AND c.id_criador <> :id_criador)'
            );
            $stmtDup->execute([
                'correo'       => $data['correo'],
                'id_usuario'   => $userId,
                'numero_socio' => $data['numero_socio'],
                'id_criador'   => $breederId,
            ]);

            if ($stmtDup->fetchColumn() !== false) {
                $db->rollBack();
                return 'conflict';
            }

            // 3. Actualizar tabla criador
            $stmtBreeder = $db->prepare(
                'UPDATE criador SET numero_socio = :numero_socio WHERE id_criador = :id_criador'
            );
            $stmtBreeder->execute([
                'numero_socio' => $data['numero_socio'],
                'id_criador'   => $breederId,
            ]);

            // 4. Actualizar tabla usuario (con o sin cambio de contraseña)
            $plainPassword = $data['password'] ?? $data['password_hash'] ?? null;
            if (!empty($plainPassword)) {
                $stmtUser = $db->prepare(
                    'UPDATE usuario SET nombre = :nombre, correo = :correo, password_hash = :password_hash WHERE id_usuario = :id_usuario'
                );
                $stmtUser->execute([
                    'nombre'        => $data['nombre'],
                    'correo'        => $data['correo'],
                    'password_hash' => password_hash((string) $plainPassword, PASSWORD_DEFAULT),
                    'id_usuario'    => $userId,
                ]);
            } else {
                $stmtUser = $db->prepare(
                    'UPDATE usuario SET nombre = :nombre, correo = :correo WHERE id_usuario = :id_usuario'
                );
                $stmtUser->execute([
                    'nombre'     => $data['nombre'],
                    'correo'     => $data['correo'],
                    'id_usuario' => $userId,
                ]);
            }

            $db->commit();

            return [
                'id_criador'   => $breederId,
                'id_usuario'   => $userId,
                'nombre'       => $data['nombre'],
                'correo'       => $data['correo'],
                'numero_socio' => $data['numero_socio'],
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

            $stmtBreeder = $db->prepare('DELETE FROM criador WHERE id_criador = :id_criador RETURNING id_usuario');
            $stmtBreeder->execute(['id_criador' => $breederId]);
            $userId = $stmtBreeder->fetchColumn();

            if ($userId === false) {
                $db->rollBack();
                return false;
            }

            $stmtUser = $db->prepare('DELETE FROM usuario WHERE id_usuario = :id_usuario AND rol = :rol');
            $stmtUser->execute([
                'id_usuario' => (int) $userId,
                'rol'        => 'criador',
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