<?php

namespace App\BookingService\Controllers;

use App\BookingService\Middleware\BookingAuthMiddleware;
use App\BookingService\Repositories\BookingRepository;
use Shared\Http\AuthGuard;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Validation\Validator;

class BookingController
{
    public function __construct(
        private BookingRepository $bookings,
        private BookingAuthMiddleware $auth,
        private ?AuthGuard $guard = null
    ) {
    }

    public function quote(): void
    {
        $data = Request::json();
        $items = $data['items'] ?? [];

        if (!is_array($items) || $items === []) {
            Response::error('Cart masih kosong.', 422, ['items' => ['Minimal satu item wajib dikirim.']]);
            return;
        }

        $quote = $this->bookings->quote($items);
        $statusCode = $quote['summary']['is_available'] ? 200 : 422;

        if ($statusCode === 422) {
            Response::error('Sebagian item tidak tersedia.', 422, $quote['errors']);
            return;
        }

        Response::success($quote, 'Quote berhasil dihitung.');
    }

    public function checkout(): void
    {
        $user = $this->auth->requireCustomer();

        if (!$user) {
            return;
        }

        $data = Request::json();
        $items = $data['items'] ?? [];

        if (!is_array($items) || $items === []) {
            Response::error('Cart masih kosong.', 422, ['items' => ['Minimal satu item wajib dikirim.']]);
            return;
        }

        try {
            $checkout = $this->bookings->checkout(
                $user['id'],
                $items,
                $data['payment_method'] ?? 'bank_transfer',
                $data['notes'] ?? null
            );
        } catch (\RuntimeException $exception) {
            Response::error('Checkout gagal karena stok tidak tersedia.', 422, json_decode($exception->getMessage(), true) ?: []);
            return;
        } catch (\Throwable $exception) {
            Response::error('Checkout gagal diproses.', 500, [
                'server' => [(getenv('APP_DEBUG') ?: 'false') === 'true' ? $exception->getMessage() : 'Internal server error'],
            ]);
            return;
        }

        Response::success($checkout, 'Checkout berhasil dibuat.', 201);
    }

    public function myBookings(): void
    {
        $user = $this->auth->requireCustomer();

        if (!$user) {
            return;
        }

        Response::success([
            'bookings' => $this->bookings->customerBookings($user['id']),
        ], 'Booking berhasil diambil.');
    }

    public function customerDashboard(): void
    {
        $user = $this->requireCustomer();

        if (!$user) {
            return;
        }

        Response::success($this->bookings->customerDashboard($user['id']), 'Dashboard customer berhasil diambil.');
    }

    public function customerRentals(): void
    {
        $user = $this->requireCustomer();

        if (!$user) {
            return;
        }

        Response::success($this->bookings->customerBookingsPaginated($user['id'], $_GET), 'Rental customer berhasil diambil.');
    }

    public function customerBookingDetail(string $bookingId): void
    {
        $user = $this->requireCustomer();

        if (!$user) {
            return;
        }

        $booking = $this->bookings->customerBookingDetail($user['id'], $bookingId);

        if (!$booking) {
            Response::error('Booking tidak ditemukan.', 404);
            return;
        }

        Response::success(['booking' => $booking], 'Detail booking berhasil diambil.');
    }

    public function cancelCustomerBooking(string $bookingId): void
    {
        $user = $this->requireCustomer();

        if (!$user) {
            return;
        }

        try {
            $booking = $this->bookings->cancelCustomerBooking($user['id'], $bookingId);
        } catch (\RuntimeException $exception) {
            Response::error($exception->getMessage(), 422);
            return;
        }

        if (!$booking) {
            Response::error('Booking tidak ditemukan.', 404);
            return;
        }

        Response::success(['booking' => $booking], 'Booking berhasil dibatalkan.');
    }

    public function customerInvoice(string $bookingId): void
    {
        $user = $this->requireCustomer();

        if (!$user) {
            return;
        }

        $invoice = $this->bookings->customerInvoice($user['id'], $bookingId);

        if (!$invoice) {
            Response::error('Invoice tidak ditemukan.', 404);
            return;
        }

        Response::success(['invoice' => $invoice], 'Invoice berhasil diambil.');
    }

    public function vendorDashboard(): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        Response::success([
            'vendor' => $this->vendorPayload($vendor),
            'summary' => $this->bookings->vendorSummary($vendor['id']),
        ], 'Dashboard vendor berhasil diambil.');
    }

    public function vendorBookings(): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        Response::success([
            'bookings' => $this->bookings->vendorBookings($vendor['id']),
        ], 'Booking vendor berhasil diambil.');
    }

    public function vendorFinance(): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        Response::success($this->bookings->vendorFinance($vendor['id']), 'Finance vendor berhasil diambil.');
    }

    public function updateVendorBookingStatus(string $bookingId): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        $data = Request::json();
        $errors = Validator::make($data, [
            'status' => ['required', 'in:pending,confirmed,ongoing,completed,cancelled'],
        ]);

        if ($errors !== []) {
            Response::error('Validasi status booking gagal.', 422, $errors);
            return;
        }

        try {
            $booking = $this->bookings->updateVendorBookingStatus($vendor['id'], $bookingId, $data['status']);
        } catch (\RuntimeException $exception) {
            Response::error($exception->getMessage(), 422);
            return;
        }

        if (!$booking) {
            Response::error('Booking tidak ditemukan.', 404);
            return;
        }

        Response::success(['booking' => $booking], 'Status booking berhasil diperbarui.');
    }

    private function requireVendor(): ?array
    {
        if (!$this->guard) {
            Response::error('Auth guard belum dikonfigurasi.', 500);
            return null;
        }

        $user = $this->guard->requireRole(['vendor']);

        if (!$user) {
            return null;
        }

        $vendor = $this->guard->vendorForUser($user['id']);

        if (!$vendor || $vendor['status'] !== 'active') {
            Response::error('Profil vendor tidak aktif atau tidak ditemukan.', 403);
            return null;
        }

        return $vendor;
    }

    private function requireCustomer(): ?array
    {
        if (!$this->guard) {
            Response::error('Auth guard belum dikonfigurasi.', 500);
            return null;
        }

        return $this->guard->requireRole(['customer']);
    }

    private function vendorPayload(array $vendor): array
    {
        return [
            'id' => $vendor['id'],
            'store_name' => $vendor['store_name'],
            'slug' => $vendor['slug'],
            'city' => $vendor['city'],
            'province' => $vendor['province'],
            'status' => $vendor['status'],
        ];
    }
}
