<?php

require_once __DIR__ . '/../../../shared/Support/EnvLoader.php';
require_once __DIR__ . '/../../../shared/Database/Connection.php';
require_once __DIR__ . '/../../../shared/Support/Jwt.php';
require_once __DIR__ . '/../../../shared/Http/Request.php';
require_once __DIR__ . '/../../../shared/Http/Response.php';
require_once __DIR__ . '/../../../shared/Security/RateLimiter.php';
require_once __DIR__ . '/../../../shared/Security/SecurityHeaders.php';
require_once __DIR__ . '/../src/Middleware/PaymentAuthMiddleware.php';
require_once __DIR__ . '/../src/Services/MidtransClient.php';
require_once __DIR__ . '/../src/Repositories/PaymentRepository.php';
require_once __DIR__ . '/../src/Controllers/PaymentController.php';
require_once __DIR__ . '/../src/Controllers/MidtransCallbackController.php';

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
