<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Envía una respuesta exitosa en formato JSON.
     *
     * @param mixed $data Datos a retornar.
     * @param string $message Mensaje descriptivo.
     * @param int $code Código HTTP (por defecto 200).
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
     * Envía una respuesta de error en formato JSON.
     *
     * @param string $message Mensaje de error.
     * @param int $code Código HTTP (por defecto 400).
     * @param mixed $errors Detalles adicionales del error (opcional).
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
}
