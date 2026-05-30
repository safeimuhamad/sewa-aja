<?php

require_once __DIR__ . '/../../../shared/Support/EnvLoader.php';
require_once __DIR__ . '/../../../shared/Database/Connection.php';
require_once __DIR__ . '/../../../shared/Support/Uuid.php';
require_once __DIR__ . '/../../../shared/Support/Jwt.php';
require_once __DIR__ . '/../../../shared/Http/Request.php';
require_once __DIR__ . '/../../../shared/Http/Response.php';
require_once __DIR__ . '/../../../shared/Http/AuthGuard.php';
require_once __DIR__ . '/../../../shared/Security/RateLimiter.php';
require_once __DIR__ . '/../../../shared/Security/SecurityHeaders.php';
require_once __DIR__ . '/../../../shared/Validation/Validator.php';
require_once __DIR__ . '/../src/Middleware/BookingAuthMiddleware.php';
require_once __DIR__ . '/../src/Repositories/BookingRepository.php';
require_once __DIR__ . '/../src/Controllers/BookingController.php';

use Shared\Database\Connection;
use Shared\Support\EnvLoader;

EnvLoader::load(__DIR__ . '/../../../../.env');

return Connection::make([
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'sewaaja',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
]);
