# Setup Local

Repository: safeimuhamad/sewa-aja
Database local: sewaaja

## Jalankan di komputer lain

1. Clone repository:

   ```bash
   git clone git@github.com:safeimuhamad/sewa-aja.git
   cd sewa-aja
   ```

2. Install dependency sesuai stack project, misalnya Composer/NPM jika tersedia.

3. Buat database dan import schema:

   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql -u root < database/schema.sql
   ```

4. Sesuaikan konfigurasi database lokal di file config/.env project.

5. Jalankan project melalui Apache/XAMPP atau command bawaan project.

## Update schema ke GitHub

Schema yang disimpan di repo adalah struktur tabel saja, tanpa data asli. Untuk update schema setelah ada perubahan table:

```bash
./scripts/export-schema-and-push.sh
```
