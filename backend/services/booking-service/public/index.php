<?php

use App\BookingService\Controllers\BookingController;
use App\BookingService\Middleware\BookingAuthMiddleware;
use App\BookingService\Repositories\BookingRepository;
use Shared\Http\AuthGuard;
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

RateLimiter::check('booking-service', 180, 60);

$controller = new BookingController(
    new BookingRepository($db),
    new BookingAuthMiddleware($db),
    new AuthGuard($db)
);
$method = Request::method();
$path = Request::path();
$route = $method . ' ' . $path;

try {
    if ($route === 'GET /') {
        Response::success([
            'service' => 'booking-service',
            'status' => 'ok',
        ], 'SewaAja Booking Service is running.');
    } elseif ($route === 'POST /quote') {
        $controller->quote();
    } elseif ($route === 'POST /checkout') {
        $controller->checkout();
    } elseif ($route === 'GET /bookings') {
        $controller->myBookings();
    } elseif ($route === 'GET /customer/dashboard') {
        $controller->customerDashboard();
    } elseif ($route === 'GET /customer/rentals') {
        $controller->customerRentals();
    } elseif ($method === 'GET' && preg_match('#^/customer/bookings/([^/]+)$#', $path, $matches)) {
        $controller->customerBookingDetail(urldecode($matches[1]));
    } elseif ($method === 'PUT' && preg_match('#^/customer/bookings/([^/]+)/cancel$#', $path, $matches)) {
        $controller->cancelCustomerBooking(urldecode($matches[1]));
    } elseif ($method === 'GET' && preg_match('#^/customer/bookings/([^/]+)/invoice$#', $path, $matches)) {
        $controller->customerInvoice(urldecode($matches[1]));
    } elseif ($route === 'GET /vendor/dashboard') {
        $controller->vendorDashboard();
    } elseif ($route === 'GET /vendor/bookings') {
        $controller->vendorBookings();
    } elseif ($route === 'GET /vendor/finance') {
        $controller->vendorFinance();
    } elseif ($method === 'PUT' && preg_match('#^/vendor/bookings/([^/]+)/status$#', $path, $matches)) {
        $controller->updateVendorBookingStatus(urldecode($matches[1]));
    } else {
        Response::error('Endpoint tidak ditemukan.', 404);
    }
} catch (Throwable $exception) {
    Response::error('Terjadi kesalahan server.', 500, [
        'server' => [(getenv('APP_DEBUG') ?: 'false') === 'true' ? $exception->getMessage() : 'Internal server error'],
    ]);
}
