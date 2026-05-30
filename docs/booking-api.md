# Booking API SewaAja

Base URL:

```text
http://localhost/sewaaja/backend/services/booking-service/public
```

## Quote Cart

`POST /quote`

Quote tidak membutuhkan login. Endpoint ini menghitung durasi, subtotal, deposit, total, sekaligus memvalidasi stok dan overlap booking.

```json
{
  "items": [
    {
      "product_id": "88888888-8888-4888-8888-888888888888",
      "quantity": 1,
      "start_date": "2026-06-05",
      "end_date": "2026-06-07"
    }
  ]
}
```

## Checkout

`POST /checkout`

Wajib menggunakan Bearer token customer.

```text
Authorization: Bearer <access_token>
```

```json
{
  "payment_method": "bank_transfer",
  "notes": "Pickup sore hari.",
  "items": [
    {
      "product_id": "88888888-8888-4888-8888-888888888888",
      "quantity": 1,
      "start_date": "2026-06-05",
      "end_date": "2026-06-07"
    }
  ]
}
```

Checkout membuat:

- `bookings` dengan status `pending`
- `booking_items`
- `payments` dengan status `pending`

Jika cart berisi item dari beberapa vendor, sistem membuat satu booking per vendor.

## My Bookings

`GET /bookings`

Wajib menggunakan Bearer token customer. Mengembalikan daftar booking milik customer yang sedang login.
