<?php

declare(strict_types=1);

namespace CanariApp\Backend\Models;

class Criador extends BaseModel {
    protected string $table = 'criador';
    protected string $primaryKey = 'id_criador';

    /**
     * Busca el perfil de criador asociado a un ID de usuario (del token JWT).
     */
    public function findByUsuarioId(int|string $idUsuario): ?array {
        $sql = "SELECT c.id_criador, c.id_usuario, c.numero_socio, c.fecha_creacion,
                       u.nombre, u.correo, u.rol
                FROM {$this->table} c
                INNER JOIN usuario u ON c.id_usuario = u.id_usuario
                WHERE c.id_usuario = :id_usuario
                LIMIT 1";

        return $this->fetch($sql, ['id_usuario' => $idUsuario]);
    }

    /**
     * Obtiene un criador por su PK junto con los datos de su cuenta de usuario.
     */
    public function findWithUsuario(int|string $idCriador): ?array {
        $sql = "SELECT c.id_criador, c.id_usuario, c.numero_socio, c.fecha_creacion,
                       u.nombre, u.correo, u.rol
                FROM {$this->table} c
                INNER JOIN usuario u ON c.id_usuario = u.id_usuario
                WHERE c.id_criador = :id_criador
                LIMIT 1";

        return $this->fetch($sql, ['id_criador' => $idCriador]);
    }

    /**
     * Lista todos los criadores ordenados del más reciente al más antiguo.
     */
    public function findAllWithUsuario(): array {
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
            'id_usuario' => $data['id_usuario'],
            'numero_socio' => $data['numero_socio'] ?? null,
        ]);
    }

    /**
     * Busca criadores por coincidencia parcial en el nombre del usuario.
     */
    public function searchByNombre(string $nombre): array {
        $sql = "SELECT c.id_criador, c.id_usuario, c.numero_socio, c.fecha_creacion,
                       u.nombre, u.correo, u.rol
                FROM {$this->table} c
                INNER JOIN usuario u ON c.id_usuario = u.id_usuario
                WHERE u.nombre ILIKE :nombre
                ORDER BY u.nombre ASC";

        return $this->fetchAll($sql, [
            'nombre' => '%' . trim($nombre) . '%',
        ]);
    }
}