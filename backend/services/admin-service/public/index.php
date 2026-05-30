<?php

use App\AdminService\Controllers\AdminController;
use App\AdminService\Repositories\AdminRepository;
use Shared\Http\AuthGuard;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Security\RateLimiter;
use Shared\Security\SecurityHeaders;

$db = require __DIR__ . '/../config/bootstrap.php';

SecurityHeaders::apply();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Authorization, X-Access-Token, X-CSRF-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if (Request::method() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

RateLimiter::check('admin-service', 180, 60);

$controller = new AdminController(new AdminRepository($db), new AuthGuard($db));
$method = Request::method();
$path = Request::path();
$route = $method . ' ' . $path;

try {
    if ($route === 'GET /') {
        Response::success([
            'service' => 'admin-service',
            'status' => 'ok',
        ], 'SewaAja Admin Service is running.');
    } elseif ($route === 'GET /dashboard') {
        $controller->dashboard();
    } elseif ($route === 'GET /users') {
        $controller->users();
    } elseif ($route === 'GET /vendors') {
        $controller->vendors();
    } elseif ($route === 'GET /products') {
        $controller->products();
    } elseif ($route === 'GET /bookings') {
        $controller->bookings();
    } elseif ($route === 'GET /payments') {
        $controller->payments();
    } elseif ($route === 'GET /reports') {
        $controller->reports();
    } elseif ($route === 'GET /reviews') {
        $controller->reviews();
    } elseif ($route === 'GET /categories') {
        $controller->categories();
    } elseif ($route === 'POST /categories') {
        $controller->saveCategory();
    } elseif ($method === 'PUT' && preg_match('#^/categories/([^/]+)$#', $path, $matches)) {
        $controller->saveCategory(urldecode($matches[1]));
    } elseif ($method === 'DELETE' && preg_match('#^/categories/([^/]+)$#', $path, $matches)) {
        $controller->deleteCategory(urldecode($matches[1]));
    } elseif ($route === 'GET /locations') {
        $controller->locations();
    } elseif ($route === 'POST /locations') {
        $controller->saveLocation();
    } elseif ($method === 'PUT' && preg_match('#^/locations/([^/]+)$#', $path, $matches)) {
        $controller->saveLocation(urldecode($matches[1]));
    } elseif ($method === 'DELETE' && preg_match('#^/locations/([^/]+)$#', $path, $matches)) {
        $controller->deleteLocation(urldecode($matches[1]));
    } elseif ($method === 'PUT' && preg_match('#^/(users|vendors|products|bookings|payments|reviews)/([^/]+)/status$#', $path, $matches)) {
        $controller->updateStatus($matches[1], urldecode($matches[2]));
    } else {
        Response::error('Endpoint tidak ditemukan.', 404);
    }
} catch (Throwable $exception) {
    Response::error('Terjadi kesalahan server.', 500, [
        'server' => [(getenv('APP_DEBUG') ?: 'false') === 'true' ? $exception->getMessage() : 'Internal server error'],
    ]);
}
