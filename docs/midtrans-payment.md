# Midtrans Payment Integration

## Environment

Tambahkan konfigurasi berikut ke `.env`:

```text
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=SB-Mid-server-...
MIDTRANS_CLIENT_KEY=SB-Mid-client-...
MIDTRANS_NOTIFICATION_URL=http://localhost/sewaaja/backend/services/payment-service/public/midtrans/callback
MIDTRANS_FINISH_URL=http://localhost/sewaaja/frontend/public/payment-status.html
```

Untuk production, ubah `MIDTRANS_IS_PRODUCTION=true` dan gunakan production server key/client key.

## Endpoints

### Generate Snap Token

`POST /midtrans/token`

Requires customer Bearer token.

```json
{
  "payment_code": "PAY-20260528132814-8961",
  "channel": "all"
}
```

Channel values:

- `all`: QRIS dan Virtual Account
- `qris`: QRIS saja
- `va`: Virtual Account saja

Response berisi:

- `snap_token`
- `redirect_url`
- `midtrans_order_id`

### Callback Webhook

`POST /midtrans/callback`

Midtrans dashboard notification URL:

```text
http://your-domain/backend/services/payment-service/public/midtrans/callback
```

Webhook divalidasi dengan signature:

```text
SHA512(order_id + status_code + gross_amount + server_key)
```

Status mapping:

- `settlement` atau `capture` accepted: payment `paid`, booking `confirmed`
- `pending`: payment `pending`, booking `pending`
- `expire`: payment `expired`, booking `cancelled`
- `cancel`, `deny`, `failure`: payment `failed`, booking `cancelled`
- `refund`, `partial_refund`: payment `refunded`, booking `cancelled`

### Payment History

`GET /payments/history`

Requires customer Bearer token.

## Notes

Create Snap transaction dilakukan dari backend menggunakan Server Key.
Frontend hanya menerima `redirect_url` dan tidak pernah menerima Server Key.
