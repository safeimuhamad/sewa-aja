<?php

namespace App\ProductService\Controllers;

use App\ProductService\Repositories\ProductRepository;
use Shared\Http\AuthGuard;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Media\ImageUploadService;
use Shared\Validation\Validator;

class ProductController
{
    public function __construct(
        private ProductRepository $products,
        private ?AuthGuard $auth = null
    )
    {
    }

    public function index(): void
    {
        Response::success($this->products->paginate($_GET), 'Produk berhasil diambil.');
    }

    public function filters(): void
    {
        Response::success([
            'categories' => $this->products->categories(),
            'locations' => $this->products->locations(),
            'sorts' => [
                ['value' => 'newest', 'label' => 'Terbaru'],
                ['value' => 'price_asc', 'label' => 'Harga termurah'],
                ['value' => 'price_desc', 'label' => 'Harga termahal'],
                ['value' => 'name_asc', 'label' => 'Nama A-Z'],
                ['value' => 'stock_desc', 'label' => 'Stok terbanyak'],
            ],
        ], 'Filter berhasil diambil.');
    }

    public function suggestions(): void
    {
        Response::success([
            'suggestions' => $this->products->suggestions($_GET['q'] ?? ''),
        ], 'Suggestion berhasil diambil.');
    }

    public function show(string $identifier): void
    {
        $product = $this->products->findDetail($identifier);

        if (!$product) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success([
            'product' => $product,
            'reviews' => $this->products->reviews($product['id']),
        ], 'Detail produk berhasil diambil.');
    }

    public function availability(string $identifier): void
    {
        $availability = $this->products->availability(
            $identifier,
            $_GET['start_date'] ?? null,
            $_GET['end_date'] ?? null
        );

        if (!$availability) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success($availability, 'Availability berhasil dihitung.');
    }

    public function reviews(string $identifier): void
    {
        $product = $this->products->findDetail($identifier);

        if (!$product) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success($this->products->reviews($product['id'], $_GET), 'Review berhasil diambil.');
    }

    public function storeReview(string $identifier): void
    {
        if (!$this->auth) {
            Response::error('Auth guard belum dikonfigurasi.', 500);
            return;
        }

        $user = $this->auth->requireRole(['customer']);

        if (!$user) {
            return;
        }

        $product = $this->products->findDetail($identifier);

        if (!$product) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        $data = Request::json();
        $errors = Validator::make($data, [
            'rating' => ['required'],
            'comment' => ['max:1000'],
        ]);

        if (isset($data['rating']) && ((int) $data['rating'] < 1 || (int) $data['rating'] > 5)) {
            $errors['rating'][] = 'Rating harus antara 1 sampai 5.';
        }

        if ($errors !== []) {
            Response::error('Validasi review gagal.', 422, $errors);
            return;
        }

        $review = $this->products->createReview($user['id'], $product['id'], [
            'rating' => (int) $data['rating'],
            'comment' => trim((string) ($data['comment'] ?? '')),
        ]);

        if (!$review) {
            Response::error('Hanya penyewa terverifikasi dengan rental selesai yang dapat memberi review.', 403);
            return;
        }

        Response::success(['review' => $review], 'Review terkirim dan menunggu moderasi.', 201);
    }

    public function vendorProducts(): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        Response::success([
            'vendor' => $this->vendorPayload($vendor),
            'products' => $this->products->vendorProducts($vendor['id']),
            'categories' => $this->products->categories(),
        ], 'Produk vendor berhasil diambil.');
    }

    public function storeVendorProduct(): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        $data = Request::json();
        $errors = $this->productErrors($data);

        if ($errors !== []) {
            Response::error('Validasi produk gagal.', 422, $errors);
            return;
        }

        Response::success([
            'product' => $this->products->createForVendor($vendor['id'], $this->cleanProductData($data)),
        ], 'Produk berhasil dibuat.', 201);
    }

    public function updateVendorProduct(string $productId): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        $data = Request::json();
        $errors = $this->productErrors($data);

        if ($errors !== []) {
            Response::error('Validasi produk gagal.', 422, $errors);
            return;
        }

        $product = $this->products->updateForVendor($vendor['id'], $productId, $this->cleanProductData($data));

        if (!$product) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success(['product' => $product], 'Produk berhasil diperbarui.');
    }

    public function deleteVendorProduct(string $productId): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        if (!$this->products->deleteForVendor($vendor['id'], $productId)) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success(null, 'Produk berhasil dihapus.');
    }

    public function uploadVendorProductImage(string $productId): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        $data = Request::json();
        $errors = Validator::make($data, [
            'image_url' => ['required', 'max:500'],
        ]);

        if ($errors !== []) {
            Response::error('Validasi gambar gagal.', 422, $errors);
            return;
        }

        $image = $this->products->addImage($vendor['id'], $productId, [
            'image_url' => trim((string) $data['image_url']),
            'alt_text' => trim((string) ($data['alt_text'] ?? '')),
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'is_primary' => (bool) ($data['is_primary'] ?? false),
        ]);

        if (!$image) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success(['image' => $image], 'Gambar produk berhasil ditambahkan.', 201);
    }

    public function uploadVendorProductMedia(string $productId): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        if (empty($_FILES['image'])) {
            Response::error('File gambar wajib dikirim.', 422);
            return;
        }

        try {
            $upload = (new ImageUploadService(__DIR__ . '/../../../../../frontend/public'))->upload($_FILES['image'], 'products');
        } catch (\RuntimeException $exception) {
            Response::error($exception->getMessage(), 422);
            return;
        }

        $image = $this->products->addImage($vendor['id'], $productId, [
            ...$upload,
            'alt_text' => $_POST['alt_text'] ?? null,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_primary' => !empty($_POST['is_primary']),
        ]);

        if (!$image) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success(['image' => $image], 'Media produk berhasil diupload.', 201);
    }

    public function sortVendorProductImages(string $productId): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        $data = Request::json();
        $images = $data['images'] ?? [];

        if (!is_array($images)) {
            Response::error('Payload sort gambar tidak valid.', 422);
            return;
        }

        $sorted = $this->products->sortImages($vendor['id'], $productId, $images);

        if (!$sorted) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success(['images' => $sorted], 'Urutan gallery berhasil diperbarui.');
    }

    public function blockVendorProductDates(string $productId): void
    {
        $context = $this->requireVendorContext();

        if (!$context) {
            return;
        }

        $data = Request::json();
        $errors = Validator::make($data, [
            'start_date' => ['required'],
            'end_date' => ['required'],
        ]);

        if ($errors !== []) {
            Response::error('Validasi block tanggal gagal.', 422, $errors);
            return;
        }

        $block = $this->products->createAvailabilityBlock($context['vendor']['id'], $productId, $context['user']['id'], [
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'quantity_blocked' => max(1, (int) ($data['quantity_blocked'] ?? 1)),
            'reason' => trim((string) ($data['reason'] ?? '')),
        ]);

        if (!$block) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success(['block' => $block], 'Tanggal unavailable berhasil diblok.', 201);
    }

    public function updateVendorInventory(string $productId): void
    {
        $vendor = $this->requireVendor();

        if (!$vendor) {
            return;
        }

        $data = Request::json();
        $product = $this->products->updateInventory($vendor['id'], $productId, [
            'stock_quantity' => max(0, (int) ($data['stock_quantity'] ?? 0)),
        ]);

        if (!$product) {
            Response::error('Produk tidak ditemukan.', 404);
            return;
        }

        Response::success(['product' => $product], 'Inventory berhasil diperbarui.');
    }

    private function requireVendor(): ?array
    {
        $context = $this->requireVendorContext();

        return $context['vendor'] ?? null;
    }

    private function requireVendorContext(): ?array
    {
        if (!$this->auth) {
            Response::error('Auth guard belum dikonfigurasi.', 500);
            return null;
        }

        $user = $this->auth->requireRole(['vendor']);

        if (!$user) {
            return null;
        }

        $vendor = $this->auth->vendorForUser($user['id']);

        if (!$vendor || $vendor['status'] !== 'active') {
            Response::error('Profil vendor tidak aktif atau tidak ditemukan.', 403);
            return null;
        }

        return ['user' => $user, 'vendor' => $vendor];
    }

    private function productErrors(array $data): array
    {
        $errors = Validator::make($data, [
            'category_id' => ['required'],
            'name' => ['required', 'min:3', 'max:180'],
            'price_per_day' => ['required'],
            'status' => ['in:draft,active,inactive'],
        ]);

        if (isset($data['price_per_day']) && (float) $data['price_per_day'] < 0) {
            $errors['price_per_day'][] = 'Harga tidak boleh negatif.';
        }

        if (isset($data['deposit_amount']) && (float) $data['deposit_amount'] < 0) {
            $errors['deposit_amount'][] = 'Deposit tidak boleh negatif.';
        }

        if (isset($data['stock_quantity']) && (int) $data['stock_quantity'] < 0) {
            $errors['stock_quantity'][] = 'Stok tidak boleh negatif.';
        }

        return $errors;
    }

    private function cleanProductData(array $data): array
    {
        return [
            'category_id' => trim((string) $data['category_id']),
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'price_per_day' => (float) $data['price_per_day'],
            'deposit_amount' => (float) ($data['deposit_amount'] ?? 0),
            'stock_quantity' => max(0, (int) ($data['stock_quantity'] ?? 0)),
            'unit_label' => trim((string) ($data['unit_label'] ?? 'unit')) ?: 'unit',
            'status' => $data['status'] ?? 'draft',
        ];
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
