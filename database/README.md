# Database SewaAja

## Cara Menjalankan

Jalankan migration terlebih dahulu, lalu seeder:

```bash
mysql -u root -p sewaaja < database/migrations/001_create_rental_marketplace_schema.sql
mysql -u root -p sewaaja < database/migrations/002_create_auth_support_tables.sql
mysql -u root -p sewaaja < database/migrations/003_add_midtrans_fields_to_payments.sql
mysql -u root -p sewaaja < database/migrations/004_add_marketplace_growth_features.sql
mysql -u root -p sewaaja < database/seeders/001_demo_data.sql
mysql -u root -p sewaaja < database/seeders/004_growth_demo_data.sql
```

Jika password MySQL kosong di XAMPP, gunakan:

```bash
mysql -u root sewaaja < database/migrations/001_create_rental_marketplace_schema.sql
mysql -u root sewaaja < database/migrations/002_create_auth_support_tables.sql
mysql -u root sewaaja < database/migrations/003_add_midtrans_fields_to_payments.sql
mysql -u root sewaaja < database/migrations/004_add_marketplace_growth_features.sql
mysql -u root sewaaja < database/seeders/001_demo_data.sql
mysql -u root sewaaja < database/seeders/004_growth_demo_data.sql
```

## Catatan

- Semua primary key memakai UUID `CHAR(36)`.
- Semua tabel utama memiliki `created_at`, `updated_at`, dan `deleted_at`.
- `deleted_at` dipakai untuk soft delete.
- Seeder demo memakai password `password123` untuk semua akun demo.
