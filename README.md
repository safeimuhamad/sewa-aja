# SewaAja

Aplikasi marketplace sewa berbasis microservice dengan backend PHP native dan frontend HTML + Bootstrap.

## Struktur Utama

```text
SewaAja/
├── frontend/                 # Aplikasi web statis untuk pengguna
│   ├── public/               # File yang diakses browser
│   └── src/                  # Komponen, halaman, dan client API frontend
├── backend/
│   ├── api-gateway/          # Pintu masuk request API dari frontend
│   ├── services/             # Service domain terpisah
│   │   ├── auth-service/     # Login, register, token/session
│   │   ├── user-service/     # Profil pengguna dan alamat
│   │   ├── product-service/  # Item sewa, kategori, ketersediaan
│   │   ├── booking-service/  # Booking, jadwal sewa, status pesanan
│   │   ├── payment-service/  # Pembayaran, invoice, callback
│   │   └── admin-service/    # Dashboard admin, approval, moderation, monitoring
│   └── shared/               # Kode PHP umum yang boleh dipakai service
├── database/                 # Migration dan seeder
├── config/                   # Konfigurasi global
├── docs/                     # Dokumentasi teknis
├── scripts/                  # Script development/deployment
└── storage/                  # Log dan file runtime lokal
```

## Cara Menjalankan di XAMPP

1. Letakkan folder ini di `htdocs`.
2. Salin `.env.example` menjadi `.env`, lalu sesuaikan konfigurasi database dan URL service.
3. Buat database MySQL `sewaaja`, lalu jalankan migration dan seeder di `database/README.md`.
4. Buka frontend:
   `http://localhost/sewaaja/frontend/public`
5. Cek API gateway:
   `http://localhost/sewaaja/backend/api-gateway/public`

## Konvensi Service

Setiap service punya pola folder yang sama:

```text
public/       # Front controller, hanya folder ini yang idealnya diekspos web server
src/
  Controllers/
  Models/
  Repositories/
  Services/
config/
```

Prinsipnya: controller menerima request, service menyimpan aturan bisnis, repository mengakses database, model mewakili struktur data.

## Database

Schema awal marketplace sewa tersedia di `database/migrations/001_create_rental_marketplace_schema.sql`.
Seeder demo tersedia di `database/seeders/001_demo_data.sql`.
Penjelasan ERD tersedia di `docs/erd.md`.
Dokumentasi Auth API tersedia di `docs/auth-api.md`.
Dokumentasi Product Catalog API tersedia di `docs/product-api.md`.
Dokumentasi Booking API tersedia di `docs/booking-api.md`.
Dokumentasi Midtrans Payment tersedia di `docs/midtrans-payment.md`.
Dokumentasi Vendor Dashboard API tersedia di `docs/vendor-dashboard-api.md`.
Dokumentasi Admin API tersedia di `docs/admin-api.md`.
Dokumentasi Customer Dashboard API tersedia di `docs/customer-dashboard-api.md`.
