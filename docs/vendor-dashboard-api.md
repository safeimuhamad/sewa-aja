# SewaAja Vendor Dashboard API

Semua endpoint vendor membutuhkan header:

```http
Authorization: Bearer {vendor_access_token}
Content-Type: application/json
```

## Product Service

Base URL: `/backend/services/product-service/public`

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/vendor/products` | Ambil profil vendor, daftar produk, dan kategori |
| POST | `/vendor/products` | Buat produk vendor |
| PUT | `/vendor/products/{id}` | Update produk vendor |
| DELETE | `/vendor/products/{id}` | Soft delete produk vendor |
| POST | `/vendor/products/{id}/images` | Tambah gambar galeri produk |
| PUT | `/vendor/products/{id}/inventory` | Update stok produk |

Contoh body produk:

```json
{
  "category_id": "uuid-kategori",
  "name": "Kamera Sony A7 III",
  "description": "Kamera mirrorless untuk event dan produksi konten.",
  "price_per_day": 175000,
  "deposit_amount": 50000,
  "stock_quantity": 3,
  "unit_label": "unit",
  "status": "active"
}
```

Contoh body gambar:

```json
{
  "image_url": "https://example.com/kamera.jpg",
  "alt_text": "Kamera Sony A7 III",
  "is_primary": true,
  "sort_order": 0
}
```

## Booking Service

Base URL: `/backend/services/booking-service/public`

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/vendor/dashboard` | Statistik produk, booking, dan sales summary |
| GET | `/vendor/bookings` | Daftar booking masuk untuk vendor |
| PUT | `/vendor/bookings/{id}/status` | Update status booking |

Status booking yang didukung:

```json
{
  "status": "confirmed"
}
```

Nilai status: `pending`, `confirmed`, `ongoing`, `completed`, `cancelled`.

## Frontend

Halaman vendor:

- `/frontend/public/vendor-dashboard.html`
- `/frontend/public/vendor-product-form.html`

Gunakan akun demo vendor:

- Email: `vendor@sewaaja.test`
- Password: `password123`
