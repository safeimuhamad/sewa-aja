<?php

use App\ProductService\Controllers\ProductController;
use App\ProductService\Repositories\ProductRepository;
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

RateLimiter::check('product-service', 240, 60);

$controller = new ProductController(new ProductRepository($db), new AuthGuard($db));
$method = Request::method();
$path = Request::path();
$route = $method . ' ' . $path;

try {
    if ($route === 'GET /') {
        Response::success([
            'service' => 'product-service',
            'status' => 'ok',
        ], 'SewaAja Product Service is running.');
    } elseif ($route === 'GET /products') {
        $controller->index();
    } elseif ($route === 'GET /products/filters') {
        $controller->filters();
    } elseif ($route === 'GET /products/suggestions') {
        $controller->suggestions();
    } elseif ($route === 'GET /vendor/products') {
        $controller->vendorProducts();
    } elseif ($route === 'POST /vendor/products') {
        $controller->storeVendorProduct();
    } elseif ($method === 'PUT' && preg_match('#^/vendor/products/([^/]+)$#', $path, $matches)) {
        $controller->updateVendorProduct(urldecode($matches[1]));
    } elseif ($method === 'DELETE' && preg_match('#^/vendor/products/([^/]+)$#', $path, $matches)) {
        $controller->deleteVendorProduct(urldecode($matches[1]));
    } elseif ($method === 'POST' && preg_match('#^/vendor/products/([^/]+)/images$#', $path, $matches)) {
        $controller->uploadVendorProductImage(urldecode($matches[1]));
    } elseif ($method === 'POST' && preg_match('#^/vendor/products/([^/]+)/media$#', $path, $matches)) {
        $controller->uploadVendorProductMedia(urldecode($matches[1]));
    } elseif ($method === 'PUT' && preg_match('#^/vendor/products/([^/]+)/images/sort$#', $path, $matches)) {
        $controller->sortVendorProductImages(urldecode($matches[1]));
    } elseif ($method === 'POST' && preg_match('#^/vendor/products/([^/]+)/availability-blocks$#', $path, $matches)) {
        $controller->blockVendorProductDates(urldecode($matches[1]));
    } elseif ($method === 'PUT' && preg_match('#^/vendor/products/([^/]+)/inventory$#', $path, $matches)) {
        $controller->updateVendorInventory(urldecode($matches[1]));
    } elseif ($method === 'GET' && preg_match('#^/products/([^/]+)/availability$#', $path, $matches)) {
        $controller->availability(urldecode($matches[1]));
    } elseif ($method === 'GET' && preg_match('#^/products/([^/]+)/calendar$#', $path, $matches)) {
        $controller->availability(urldecode($matches[1]));
    } elseif ($method === 'GET' && preg_match('#^/products/([^/]+)/reviews$#', $path, $matches)) {
        $controller->reviews(urldecode($matches[1]));
    } elseif ($method === 'POST' && preg_match('#^/products/([^/]+)/reviews$#', $path, $matches)) {
        $controller->storeReview(urldecode($matches[1]));
    } elseif ($method === 'GET' && preg_match('#^/products/([^/]+)$#', $path, $matches)) {
        $controller->show(urldecode($matches[1]));
    } else {
        Response::error('Endpoint tidak ditemukan.', 404);
    }
} catch (Throwable $exception) {
    Response::error('Terjadi kesalahan server.', 500, [
        'server' => [(getenv('APP_DEBUG') ?: 'false') === 'true' ? $exception->getMessage() : 'Internal server error'],
    ]);
}
