<?php

namespace Shared\Security;

use Shared\Http\Response;

class RateLimiter
{
    public static function check(string $bucket, int $limit = 60, int $windowSeconds = 60): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
        $dir = getenv('RATE_LIMIT_PATH') ?: (__DIR__ . '/../../../storage/cache/rate_limit');

        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return true;
        }

        $key = preg_replace('/[^A-Za-z0-9_.-]/', '_', "{$bucket}_{$ip}");
        $file = "{$dir}/{$key}.json";
        $now = time();
        $state = ['start' => $now, 'hits' => 0];

        if (is_file($file)) {
            $state = json_decode((string) @file_get_contents($file), true) ?: $state;
        }

        if (($now - (int) $state['start']) >= $windowSeconds) {
            $state = ['start' => $now, 'hits' => 0];
        }

        $state['hits']++;
        if (@file_put_contents($file, json_encode($state)) === false) {
            return true;
        }

        if ($state['hits'] > $limit) {
            Response::error('Terlalu banyak request. Coba lagi sebentar.', 429);
            return false;
        }

        return true;
    }
}
