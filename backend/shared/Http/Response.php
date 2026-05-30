<?php

namespace Shared\Http;

class Response
{
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    public static function success(array $data = [], string $message = 'OK', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors ?: null,
        ], $statusCode);
    }
}
