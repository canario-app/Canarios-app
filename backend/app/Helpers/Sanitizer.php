<?php

declare(strict_types=1);

namespace Backend\CanariosApp\Helpers;

use DateTimeImmutable;
use JsonException;

/**
 * Limpia y valida datos de entrada.
 *
 * Importante: esto no reemplaza las consultas preparadas. La defensa contra
 * inyección SQL debe implementarse en BaseModel usando PDO y parámetros.
 */
final class Sanitizer
{
    private function __construct()
    {
    }

    /**
     * Normaliza un texto y rechaza valores que no sean escalares.
     *
     * @param int|null $maxLength Longitud máxima en caracteres; null = sin límite.
     */
    public static function string(mixed $value, ?int $maxLength = null, bool $trim = true): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value) || is_resource($value)) {
            return null;
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        $value = (string) $value;

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_scrub($value, 'UTF-8');
        }

        // Elimina bytes nulos y caracteres de control, conservando tab, LF y CR.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        if ($trim) {
            $value = trim($value);
        }

        if ($maxLength !== null && mb_strlen($value, 'UTF-8') > $maxLength) {
            return null;
        }

        return $value;
    }

    /**
     * Convierte un valor a entero y aplica límites opcionales.
     */
    public static function int(mixed $value, ?int $min = null, ?int $max = null): ?int
    {
        if (is_int($value)) {
            $integer = $value;
        } elseif (is_string($value)) {
            $filtered = filter_var(trim($value), FILTER_VALIDATE_INT);

            if ($filtered === false) {
                return null;
            }

            $integer = $filtered;
        } else {
            return null;
        }

        if (($min !== null && $integer < $min) || ($max !== null && $integer > $max)) {
            return null;
        }

        return $integer;
    }

    /**
     * Convierte un valor a número decimal y aplica límites opcionales.
     */
    public static function float(mixed $value, ?float $min = null, ?float $max = null): ?float
    {
        if (is_int($value) || is_float($value)) {
            $number = (float) $value;
        } elseif (is_string($value) && is_numeric(trim($value))) {
            $number = (float) trim($value);
        } else {
            return null;
        }

        if (!is_finite($number)) {
            return null;
        }

        if (($min !== null && $number < $min) || ($max !== null && $number > $max)) {
            return null;
        }

        return $number;
    }

    /**
     * Normaliza valores booleanos habituales de formularios y JSON.
     */
    public static function bool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                0 => false,
                1 => true,
                default => null,
            };
        }

        if (is_string($value)) {
            return match (mb_strtolower(trim($value), 'UTF-8')) {
                '1', 'true', 'yes', 'si', 'sí', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => null,
            };
        }

        return null;
    }

    /**
     * Valida un correo electrónico.
     */
    public static function email(mixed $value, int $maxLength = 254): ?string
    {
        $email = self::string($value, $maxLength);

        if ($email === null || $email === '') {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    /**
     * Valida una fecha y la devuelve en el formato indicado.
     */
    public static function date(mixed $value, string $format = 'Y-m-d'): ?string
    {
        $date = self::string($value);

        if ($date === null || $date === '') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!' . $format, $date);
        $errors = DateTimeImmutable::getLastErrors();

        if ($parsed === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $parsed->format($format);
    }

    /**
     * Comprueba que un texto pertenezca a una lista cerrada de valores.
     *
     * @param array<int, string> $allowed
     */
    public static function enum(mixed $value, array $allowed): ?string
    {
        $value = self::string($value);

        if ($value === null) {
            return null;
        }

        return in_array($value, $allowed, true) ? $value : null;
    }

    /**
     * Comprueba la estructura de un arreglo. No limpia sus elementos.
     */
    public static function array(mixed $value, ?int $maxItems = null): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        if ($maxItems !== null && count($value) > $maxItems) {
            return null;
        }

        return $value;
    }

    /**
     * Decodifica un JSON de entrada y exige que su raíz sea un arreglo.
     */
    public static function json(mixed $value, int $maxDepth = 512): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, true, $maxDepth, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Valida una URL absoluta.
     */
    public static function url(mixed $value, int $maxLength = 2048): ?string
    {
        $url = self::string($value, $maxLength);

        if ($url === null || $url === '') {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
    }

    /**
     * Escapa un valor para incluirlo en HTML.
     *
     * Se usa al mostrar datos, no al guardarlos.
     */
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
