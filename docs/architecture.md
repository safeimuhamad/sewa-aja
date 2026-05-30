# Arsitektur SewaAja

SewaAja memakai pendekatan microservice sederhana:

- Frontend memanggil API Gateway.
- API Gateway meneruskan request ke service sesuai domain.
- Tiap service menyimpan aturan bisnis masing-masing.
- Kode umum seperti response JSON dan koneksi database ditempatkan di `backend/shared`.

Service awal:

- `auth-service`: autentikasi dan otorisasi.
- `user-service`: data pengguna.
- `product-service`: katalog barang sewa.
- `booking-service`: transaksi penyewaan.
- `payment-service`: pembayaran.

