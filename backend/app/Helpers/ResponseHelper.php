<?php

namespace CanariApp\Backend\Helpers;

class ResponseHelper
{
    /**
     * Envía una respuesta de éxito genérica (200 OK por defecto).
     */
    public static function success($data = null, string $message = 'Operación exitosa', int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Éxito 201: Recurso creado exitosamente (ej. nuevo usuario, registro guardado).
     */
    public static function created($data = null, string $message = 'Recurso creado con éxito'): void
    {
        self::success($data, $message, 201);
    }

    /**
     * Éxito 204: Sin contenido (La operación fue exitosa pero no hay datos que devolver, ej. eliminar).
     */
    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    /**
     * Envía una respuesta de error genérica.
     */
    public static function error(string $message = 'Ocurrió un error', int $code = 400, $errors = null): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');

        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Error 400: Solicitud incorrecta (Bad Request).
     */
    public static function badRequest(string $message = 'Solicitud incorrecta', $errors = null): void
    {
        self::error($message, 400, $errors);
    }

    /**
     * Error 401: No autorizado (Sin sesión activa o token inválido).
     */
    public static function unauthorized(string $message = 'No autorizado'): void
    {
        self::error($message, 401);
    }

    /**
     * Error 403: Prohibido (Sesión activa pero sin rol/permiso suficiente).
     */
    public static function forbidden(string $message = 'Acceso prohibido'): void
    {
        self::error($message, 403);
    }

    /**
     * Error 404: Recurso no encontrado.
     */
    public static function notFound(string $message = 'Recurso no encontrado'): void
    {
        self::error($message, 404);
    }

    /**
     * Error 409: Conflicto (ej. intentar registrar un email que ya existe en la BD).
     */
    public static function conflict(string $message = 'El recurso ya existe o hay un conflicto de datos'): void
    {
        self::error($message, 409);
    }

    /**
     * Error 422: Error de validación de datos del formulario.
     */
    public static function validationError($errors, string $message = 'Errores de validación'): void
    {
        self::error($message, 422, $errors);
    }

    /**
     * Error 429: Demasiadas peticiones (Rate limiting / bloqueo de intentos).
     */
    public static function tooManyRequests(string $message = 'Demasiadas solicitudes. Intente más tarde'): void
    {
        self::error($message, 429);
    }

    /**
     * Error 500: Error interno del servidor.
     */
    public static function internalError(string $message = 'Error interno del servidor', $errors = null): void
    {
        self::error($message, 500, $errors);
    }

    /**
     * Error 503: Servicio no disponible (ej. servidor en mantenimiento).
     */
    public static function serviceUnavailable(string $message = 'Servicio temporalmente no disponible'): void
    {
        self::error($message, 503);
    }
}
