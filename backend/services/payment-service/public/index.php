<?php

use App\PaymentService\Controllers\MidtransCallbackController;
use App\PaymentService\Controllers\PaymentController;
use App\PaymentService\Middleware\PaymentAuthMiddleware;
use App\PaymentService\Repositories\PaymentRepository;
use App\PaymentService\Services\MidtransClient;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Security\RateLimiter;
use Shared\Security\SecurityHeaders;

$db = require __DIR__ . '/../config/bootstrap.php';

SecurityHeaders::apply();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Authorization, X-Access-Token, X-CSRF-Token');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (Request::method() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

RateLimiter::check('payment-service', 180, 60);

$repository = new PaymentRepository($db);
$midtrans = new MidtransClient();
$controller = new PaymentController($repository, new PaymentAuthMiddleware($db), $midtrans);
$callback = new MidtransCallbackController($repository, $midtrans);
$route = Request::method() . ' ' . Request::path();

try {
    match ($route) {
        'GET /' => Response::success([
            'service' => 'payment-service',
            'status' => 'ok',
            'midtrans_environment' => filter_var(getenv('MIDTRANS_IS_PRODUCTION') ?: false, FILTER_VALIDATE_BOOLEAN) ? 'production' : 'sandbox',
        ], 'SewaAja Payment Service is running.'),
        'POST /midtrans/token' => $controller->createToken(),
        'POST /midtrans/callback' => $callback->handle(),
        'GET /payments/history' => $controller->history(),
        default => Response::error('Endpoint tidak ditemukan.', 404),
    };
} catch (Throwable $exception) {
    Response::error('Terjadi kesalahan server.', 500, [
        'server' => [(getenv('APP_DEBUG') ?: 'false') === 'true' ? $exception->getMessage() : 'Internal server error'],
    ]);
}
