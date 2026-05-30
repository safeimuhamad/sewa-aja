<?php

namespace Shared\Support;

class FileLogger
{
    public static function error(string $message, array $context = []): void
    {
        $dir = __DIR__ . '/../../../storage/logs';

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $dir . '/app.log',
            json_encode([
                'level' => 'error',
                'message' => $message,
                'context' => $context,
                'created_at' => date(DATE_ATOM),
            ], JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }
}
