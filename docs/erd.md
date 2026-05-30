# ERD SewaAja

## Relasi Utama

```text
users (admin/vendor/customer)
  ├─ has one vendors
  └─ has many bookings as customer

vendors
  ├─ belongs to users
  ├─ has many products
  └─ has many bookings

categories
  ├─ belongs to categories as parent
  ├─ has many categories as children
  └─ has many products

products
  ├─ belongs to vendors
  ├─ belongs to categories
  ├─ has many product_images
  ├─ has many product_units
  └─ has many booking_items

bookings
  ├─ belongs to users as customer
  ├─ belongs to vendors
  ├─ has many booking_items
  └─ has many payments

booking_items
  ├─ belongs to bookings
  ├─ belongs to products
  └─ belongs to product_units

payments
  └─ belongs to bookings

auth_tokens
  └─ belongs to users

password_reset_tokens
  └─ belongs to users
```

## Desain Data

`users` menyimpan semua akun sistem dengan role `admin`, `vendor`, dan `customer`.
Jika user berperan sebagai vendor, detail tokonya disimpan di tabel `vendors`.

`products` selalu dimiliki oleh `vendors` dan masuk ke satu `categories`.
Produk dapat memiliki banyak gambar melalui `product_images`.
Stok cepat dapat dibaca dari `products.stock_quantity`, sementara unit fisik yang bisa dilacak satu per satu disimpan di `product_units`.

`bookings` mewakili transaksi sewa dari satu customer ke satu vendor.
Satu booking dapat memiliki banyak item melalui `booking_items`.
Setiap item menyimpan snapshot nama produk dan harga supaya riwayat transaksi tetap stabil walaupun data produk berubah.

`payments` terhubung ke booking dan mendukung beberapa status pembayaran seperti `pending`, `paid`, `failed`, `refunded`, dan `expired`.

`auth_tokens` menyimpan `jti` JWT yang masih aktif agar logout bisa melakukan revocation.
`password_reset_tokens` menyimpan hash token reset password dengan masa berlaku terbatas.

## Indexing

Index dibuat pada kolom yang sering dipakai untuk pencarian dan filter:

- `email`, `role`, dan `status` pada `users`.
- `slug`, `status`, `city`, dan `province` pada `vendors`.
- `slug`, `parent_id`, dan `is_active` pada `categories`.
- `vendor_id`, `category_id`, `status`, dan `stock_quantity` pada `products`.
- `product_id`, `is_primary`, dan `sort_order` pada `product_images`.
- `product_id`, `sku`, dan `availability_status` pada `product_units`.
- `customer_id`, `vendor_id`, `status`, `start_date`, dan `end_date` pada `bookings`.
- `booking_id`, `product_id`, dan `product_unit_id` pada `booking_items`.
- `booking_id`, `status`, `method`, dan `paid_at` pada `payments`.
