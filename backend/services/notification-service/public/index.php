<?php

use App\NotificationService\Controllers\NotificationController;
use Shared\Http\AuthGuard;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Notifications\NotificationService;
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

RateLimiter::check('notification-service', 180, 60);

$controller = new NotificationController(new NotificationService($db), new AuthGuard($db));
$method = Request::method();
$path = Request::path();
$route = $method . ' ' . $path;

try {
    if ($route === 'GET /') {
        Response::success([
            'service' => 'notification-service',
            'status' => 'ok',
        ], 'SewaAja Notification Service is running.');
    } elseif ($route === 'GET /notifications') {
        $controller->index();
    } elseif ($route === 'POST /notifications') {
        $controller->store();
    } elseif ($method === 'PUT' && preg_match('#^/notifications/([^/]+)/read$#', $path, $matches)) {
        $controller->markRead(urldecode($matches[1]));
    } else {
        Response::error('Endpoint tidak ditemukan.', 404);
    }
} catch (Throwable $exception) {
    Response::error('Terjadi kesalahan server.', 500, [
        'server' => [(getenv('APP_DEBUG') ?: 'false') === 'true' ? $exception->getMessage() : 'Internal server error'],
    ]);
}
