<?php

declare(strict_types=1);

namespace CanariApp\Backend\Models;

use CanariApp\Backend\Config\Db;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOStatement;
use Throwable;

/**
 * Base para los modelos de la aplicación.
 *
 * Se conecta automáticamente usando Config\Db por defecto, 
 * pero permite inyectar una conexión PDO diferente para facilitar las pruebas.
 *
 * Todas las consultas usan sentencias preparadas. Los nombres de tablas y
 * columnas se validan y citan; nunca deben venir directamente del usuario.
 */
abstract class BaseModel{
    protected PDO $db;

    /** Nombre de la tabla. Cada modelo hijo debe definirlo. */
    protected string $table = '';

    /** Columna usada por find(), update() y delete(). */
    protected string $primaryKey = 'id';

    /**
     * @param PDO|null $db Conexión opcional (para testing). Si es null, usa la global.
     */
    public function __construct(?PDO $db = null){
        // Usa la conexión pasada, o llama al Singleton de tu configuración
        $this->db = $db ?? Db::getConnection();
    }

    public function getConnection(): PDO{
        return $this->db;
    }

    public function getTable(): string{
        return $this->table;
    }

    public function getPrimaryKey(): string{
        return $this->primaryKey;
    }

    /**
     * Ejecuta una consulta preparada y devuelve el statement.
     *
     * @param array<int|string, mixed> $params Parámetros posicionales (0-based) o nombrados.
     */
    public function query(string $sql, array $params = []): PDOStatement{
        $statement = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $parameter = is_int($key) ? $key + 1 : $key;

            $statement->bindValue($parameter, $value, $this->paramType($value));
        }

        $statement->execute();

        return $statement;
    }

    /**
     * Devuelve la primera fila o null.
     *
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array{
        $row = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Devuelve todas las filas.
     *
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array{
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve el primer valor de la primera fila o null si no hay filas.
     *
     * @param array<int|string, mixed> $params
     */
    public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed{
        $row = $this->query($sql, $params)->fetch(PDO::FETCH_NUM);

        if ($row === false || !array_key_exists($column, $row)) {
            return null;
        }

        return $row[$column];
    }

    /**
     * Ejecuta una sentencia y devuelve la cantidad de filas afectadas.
     *
     * @param array<int|string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int{
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Busca una fila por su clave primaria.
     *
     * @param array<int, string> $columns
     * @return array<string, mixed>|null
     */
    public function find(mixed $id, array $columns = ['*']): ?array{
        return $this->findBy([$this->primaryKey => $id], $columns);
    }

    /**
     * Busca la primera fila que cumpla las condiciones.
     *
     * Las condiciones son igualdad por columna. Un valor null genera IS NULL
     * y un arreglo genera IN (...).
     *
     * @param array<string, mixed> $conditions
     * @param array<int, string> $columns
     * @param array<string, string>|array<int, string> $orderBy
     * @return array<string, mixed>|null
     */
    public function findBy(
        array $conditions,
        array $columns = ['*'],
        array $orderBy = []
    ): ?array {
        $rows = $this->findAllBy($conditions, $columns, $orderBy, 1);

        return $rows[0] ?? null;
    }

    /**
     * Devuelve todas las filas que cumplan las condiciones.
     *
     * @param array<string, mixed> $conditions
     * @param array<int, string> $columns
     * @param array<string, string>|array<int, string> $orderBy
     * @return array<int, array<string, mixed>>
     */
    public function findAllBy(
        array $conditions = [],
        array $columns = ['*'],
        array $orderBy = [],
        ?int $limit = null,
        int $offset = 0
    ): array {
        [$where, $params] = $this->buildWhere($conditions);

        $sql = sprintf(
            'SELECT %s FROM %s%s%s%s',
            $this->columnList($columns),
            $this->tableName(),
            $where,
            $this->buildOrderBy($orderBy),
            $this->buildLimitOffset($limit, $offset)
        );

        return $this->fetchAll($sql, $params);
    }

    /**
     * Devuelve todas las filas de la tabla.
     *
     * @param array<int, string> $columns
     * @param array<string, string>|array<int, string> $orderBy
     * @return array<int, array<string, mixed>>
     */
    public function all(
        array $columns = ['*'],
        array $orderBy = [],
        ?int $limit = null,
        int $offset = 0
    ): array {
        return $this->findAllBy([], $columns, $orderBy, $limit, $offset);
    }

    /**
     * Inserta una fila y devuelve el ID generado cuando la BD lo permite.
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int|string|null{
        if ($data === []) {
            throw new InvalidArgumentException('No hay datos para insertar.');
        }

        $quotedColumns = [];
        $placeholders = [];

        foreach (array_keys($data) as $column) {
            $column = (string) $column;
            $quotedColumns[] = $this->quoteIdentifier($column);
            $placeholders[] = ':' . $column;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->tableName(),
            implode(', ', $quotedColumns),
            implode(', ', $placeholders)
        );

        // PostgreSQL permite obtener el ID de forma fiable con RETURNING.
        if ($this->driver() === 'pgsql') {
            $sql .= ' RETURNING ' . $this->quoteIdentifier($this->primaryKey);

            return $this->normalizeInsertId($this->fetchColumn($sql, $data));
        }

        $this->query($sql, $data);

        return $this->normalizeInsertId($this->db->lastInsertId());
    }

    /**
     * Actualiza una fila por su clave primaria.
     *
     * @param array<string, mixed> $data
     */
    public function update(mixed $id, array $data): int{
        if ($data === []) {
            throw new InvalidArgumentException('No hay datos para actualizar.');
        }

        $assignments = [];
        $params = ['id' => $id];

        foreach ($data as $column => $value) {
            $column = (string) $column;
            $parameter = 'set_' . $column;

            $assignments[] = $this->quoteIdentifier($column) . ' = :' . $parameter;
            $params[$parameter] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :id',
            $this->tableName(),
            implode(', ', $assignments),
            $this->quoteIdentifier($this->primaryKey)
        );

        return $this->execute($sql, $params);
    }

    /**
     * Elimina una fila por su clave primaria.
     */
    public function delete(mixed $id): int{
        $sql = sprintf(
            'DELETE FROM %s WHERE %s = :id',
            $this->tableName(),
            $this->quoteIdentifier($this->primaryKey)
        );

        return $this->execute($sql, ['id' => $id]);
    }

    /**
     * Cuenta filas que cumplan las condiciones.
     *
     * @param array<string, mixed> $conditions
     */
    public function count(array $conditions = []): int{
        [$where, $params] = $this->buildWhere($conditions);

        $sql = sprintf(
            'SELECT COUNT(*) FROM %s%s',
            $this->tableName(),
            $where
        );

        return (int) $this->fetchColumn($sql, $params);
    }

    /**
     * Comprueba si existe una fila con la clave primaria indicada.
     */
    public function exists(mixed $id): bool{
        return $this->count([$this->primaryKey => $id]) > 0;
    }

    /**
     * Ejecuta un callback dentro de una transacción.
     *
     * Si el callback lanza una excepción, se hace rollback y se relanza.
     */
    public function transaction(callable $callback): mixed{
        $this->db->beginTransaction();

        try {
            $result = $callback($this);

            if ($this->db->inTransaction()) {
                $this->db->commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function beginTransaction(): bool{
        return $this->db->beginTransaction();
    }

    public function commit(): bool{
        return $this->db->commit();
    }

    public static function rollBack(): bool {
        $connection = Db::getConnection();
        return $connection->inTransaction() ? $connection->rollBack() : false;
    }

    public function inTransaction(): bool{
        return $this->db->inTransaction();
    }

    /**
     * Valida y cita un nombre de tabla o columna.
     */
    public function quoteIdentifier(string $identifier): string{
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Identificador SQL inválido: "%s".', $identifier)
            );
        }

        return $this->driver() === 'mysql'
            ? '`' . $identifier . '`'
            : '"' . $identifier . '"';
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array{0: string, 1: array<string, mixed>}
     */
    protected function buildWhere(array $conditions): array{
        if ($conditions === []) {
            return ['', []];
        }

        $clauses = [];
        $params = [];
        $index = 0;

        foreach ($conditions as $column => $value) {
            $quotedColumn = $this->quoteIdentifier((string) $column);

            if ($value === null) {
                $clauses[] = $quotedColumn . ' IS NULL';
                continue;
            }

            if (is_array($value)) {
                if ($value === []) {
                    $clauses[] = '1 = 0';
                    continue;
                }

                $placeholders = [];

                foreach ($value as $item) {
                    $name = 'where_' . $index++;
                    $placeholders[] = ':' . $name;
                    $params[$name] = $item;
                }

                $clauses[] = $quotedColumn . ' IN (' . implode(', ', $placeholders) . ')';
                continue;
            }

            $name = 'where_' . $index++;
            $clauses[] = $quotedColumn . ' = :' . $name;
            $params[$name] = $value;
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }

    /**
     * @param array<string, string>|array<int, string> $orderBy
     */
    protected function buildOrderBy(array $orderBy): string{
        if ($orderBy === []) {
            return '';
        }

        $parts = [];

        foreach ($orderBy as $column => $direction) {
            if (is_int($column)) {
                $value = trim((string) $direction);

                if ($value === '') {
                    throw new InvalidArgumentException('Orden inválido.');
                }

                $pieces = preg_split('/\s+/', $value) ?: [];
                $column = (string) ($pieces[0] ?? '');
                $direction = $pieces[1] ?? 'ASC';
            }

            $direction = strtoupper((string) $direction);

            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                throw new InvalidArgumentException(
                    sprintf('Dirección de orden inválida: "%s".', $direction)
                );
            }

            $parts[] = $this->quoteIdentifier((string) $column) . ' ' . $direction;
        }

        return ' ORDER BY ' . implode(', ', $parts);
    }

    protected function buildLimitOffset(?int $limit, int $offset): string{
        $sql = '';

        if ($limit !== null) {
            $sql .= sprintf(' LIMIT %d', max(0, $limit));
        }

        if ($offset > 0) {
            $sql .= sprintf(' OFFSET %d', $offset);
        }

        return $sql;
    }

    /**
     * @param array<int, string> $columns
     */
    protected function columnList(array $columns): string{
        if ($columns === [] || in_array('*', $columns, true)) {
            return '*';
        }

        return implode(', ', array_map(
            fn (string $column): string => $this->quoteIdentifier($column),
            $columns
        ));
    }

    protected function tableName(): string{
        if ($this->table === '') {
            throw new LogicException('El modelo no definió la propiedad $table.');
        }

        return $this->quoteIdentifier($this->table);
    }

    protected function driver(): string{
        return (string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    protected function paramType(mixed $value): int{
        return match (true) {
            $value === null => PDO::PARAM_NULL,
            is_bool($value) => PDO::PARAM_BOOL,
            is_int($value) => PDO::PARAM_INT,
            default => PDO::PARAM_STR,
        };
    }

    protected function normalizeInsertId(mixed $id): int|string|null{
        if ($id === false || $id === null || $id === '') {
            return null;
        }

        $integer = filter_var($id, FILTER_VALIDATE_INT);

        return $integer !== false ? $integer : (string) $id;
    }
}
