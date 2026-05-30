<?php

namespace App\AdminService\Controllers;

use App\AdminService\Repositories\AdminRepository;
use Shared\Http\AuthGuard;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Validation\Validator;

class AdminController
{
    public function __construct(
        private AdminRepository $admin,
        private AuthGuard $auth
    ) {
    }

    public function dashboard(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->dashboard(), 'Dashboard admin berhasil diambil.');
    }

    public function users(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->users($_GET), 'Data user berhasil diambil.');
    }

    public function vendors(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->vendors($_GET), 'Data vendor berhasil diambil.');
    }

    public function products(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->products($_GET), 'Data produk berhasil diambil.');
    }

    public function bookings(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->bookings($_GET), 'Data booking berhasil diambil.');
    }

    public function payments(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->payments($_GET), 'Data payment berhasil diambil.');
    }

    public function reports(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->reports($_GET), 'Laporan admin berhasil diambil.');
    }

    public function reviews(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->reviews($_GET), 'Data review berhasil diambil.');
    }

    public function categories(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->categories($_GET), 'Data kategori berhasil diambil.');
    }

    public function saveCategory(?string $id = null): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $data = Request::json();
        $errors = Validator::make($data, [
            'name' => ['required', 'max:120'],
            'slug' => ['max:140'],
            'icon_key' => ['max:60'],
        ]);

        if ($errors !== []) {
            Response::error('Validasi kategori gagal.', 422, $errors);
            return;
        }

        Response::success([
            'category' => $this->admin->saveCategory($data, $id),
        ], $id ? 'Kategori berhasil diperbarui.' : 'Kategori berhasil dibuat.', $id ? 200 : 201);
    }

    public function deleteCategory(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!$this->admin->deleteCategory($id)) {
            Response::error('Kategori tidak ditemukan.', 404);
            return;
        }

        Response::success([], 'Kategori berhasil dinonaktifkan.');
    }

    public function locations(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        Response::success($this->admin->locations($_GET), 'Data kota berhasil diambil.');
    }

    public function saveLocation(?string $id = null): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $data = Request::json();
        $errors = Validator::make($data, [
            'city' => ['required', 'max:120'],
            'province' => ['required', 'max:120'],
            'type' => ['required', 'in:Kota,Kabupaten'],
        ]);

        if ($errors !== []) {
            Response::error('Validasi kota gagal.', 422, $errors);
            return;
        }

        Response::success([
            'location' => $this->admin->saveLocation($data, $id),
        ], $id ? 'Kota berhasil diperbarui.' : 'Kota berhasil dibuat.', $id ? 200 : 201);
    }

    public function deleteLocation(string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!$this->admin->deleteLocation($id)) {
            Response::error('Kota tidak ditemukan.', 404);
            return;
        }

        Response::success([], 'Kota berhasil dinonaktifkan.');
    }

    public function updateStatus(string $resource, string $id): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $map = [
            'users' => 'users',
            'vendors' => 'vendors',
            'products' => 'products',
            'bookings' => 'bookings',
            'payments' => 'payments',
            'reviews' => 'reviews',
        ];

        if (!isset($map[$resource])) {
            Response::error('Resource admin tidak ditemukan.', 404);
            return;
        }

        $data = Request::json();
        $errors = Validator::make($data, ['status' => ['required']]);

        if ($errors !== []) {
            Response::error('Validasi status gagal.', 422, $errors);
            return;
        }

        if (!$this->admin->updateStatus($map[$resource], $id, (string) $data['status'])) {
            Response::error('Data tidak ditemukan atau status tidak valid.', 422);
            return;
        }

        Response::success([], 'Status berhasil diperbarui.');
    }

    private function requireAdmin(): ?array
    {
        return $this->auth->requireRole(['admin']);
    }
}
