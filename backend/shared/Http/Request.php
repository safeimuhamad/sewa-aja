<?php

namespace Shared\Http;

class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script !== '' && str_starts_with($uri, '/' . $script)) {
            $uri = substr($uri, strlen('/' . $script));
        }

        return '/' . trim($uri, '/');
    }

    public static function json(): array
    {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload ?: '{}', true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $_SERVER['HTTP_X_AUTHORIZATION']
            ?? '';

        if (!$header && !empty($_SERVER['HTTP_X_ACCESS_TOKEN'])) {
            $header = 'Bearer ' . $_SERVER['HTTP_X_ACCESS_TOKEN'];
        }

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
