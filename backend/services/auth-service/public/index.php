<?php

use App\AuthService\Controllers\AuthController;
use App\AuthService\Middleware\AuthMiddleware;
use App\AuthService\Repositories\AuthTokenRepository;
use App\AuthService\Repositories\PasswordResetRepository;
use App\AuthService\Repositories\UserRepository;
use App\AuthService\Repositories\VendorRepository;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Security\RateLimiter;
use Shared\Security\SecurityHeaders;

$db = require __DIR__ . '/../config/bootstrap.php';

SecurityHeaders::apply();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Authorization, X-Access-Token, X-CSRF-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');

if (Request::method() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

RateLimiter::check('auth-service', 90, 60);

$users = new UserRepository($db);
$vendors = new VendorRepository($db);
$tokens = new AuthTokenRepository($db);
$passwordResets = new PasswordResetRepository($db);
$auth = new AuthMiddleware($users, $tokens);
$controller = new AuthController($db, $users, $vendors, $tokens, $passwordResets, $auth);

$route = Request::method() . ' ' . Request::path();

try {
    match ($route) {
        'GET /' => Response::success([
            'service' => 'auth-service',
            'status' => 'ok',
        ], 'SewaAja Auth Service is running.'),
        'POST /register/customer' => $controller->registerCustomer(),
        'POST /register/vendor' => $controller->registerVendor(),
        'POST /login' => $controller->login(),
        'POST /forgot-password' => $controller->forgotPassword(),
        'GET /profile' => $controller->profile(),
        'PUT /profile' => $controller->updateProfile(),
        'POST /logout' => $controller->logout(),
        'GET /vendor/check' => $controller->vendorOnlyExample(),
        default => Response::error('Endpoint tidak ditemukan.', 404),
    };
} catch (Throwable $exception) {
    Response::error('Terjadi kesalahan server.', 500, [
        'server' => [(getenv('APP_DEBUG') ?: 'false') === 'true' ? $exception->getMessage() : 'Internal server error'],
    ]);
}
