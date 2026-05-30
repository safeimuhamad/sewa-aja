# Auth API SewaAja

Base URL:

```text
http://localhost/sewaaja/backend/services/auth-service/public
```

## Response Format

Success:

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {},
  "errors": null
}
```

Error:

```json
{
  "success": false,
  "message": "Validasi gagal.",
  "data": null,
  "errors": {
    "email": ["Format email tidak valid."]
  }
}
```

## Register Customer

`POST /register/customer`

```json
{
  "name": "Citra Customer",
  "email": "citra@example.com",
  "password": "password123",
  "phone": "08123456789"
}
```

## Register Vendor

`POST /register/vendor`

```json
{
  "name": "Budi Rental",
  "email": "budi@example.com",
  "password": "password123",
  "phone": "08123456788",
  "store_name": "Budi Event Rental",
  "city": "Jakarta Selatan",
  "province": "DKI Jakarta"
}
```

## Login

`POST /login`

```json
{
  "email": "citra@example.com",
  "password": "password123"
}
```

Response `data.auth.access_token` dipakai sebagai Bearer token.
Implementasi ini memakai JWT dengan tabel `auth_tokens` untuk revocation, mirip pola personal access token/Sanctum tetapi dibuat native tanpa Laravel:

```text
Authorization: Bearer <access_token>
```

## Forgot Password

`POST /forgot-password`

```json
{
  "email": "citra@example.com"
}
```

Pada mode debug lokal, response menyertakan `demo_reset_token` agar proses bisa diuji tanpa email server.

## Profile

`GET /profile`

Header:

```text
Authorization: Bearer <access_token>
```

## Logout

`POST /logout`

Header:

```text
Authorization: Bearer <access_token>
```

Logout akan me-revoke `jti` token di tabel `auth_tokens`.

## Role Middleware Example

`GET /vendor/check`

Endpoint ini hanya menerima user dengan role `vendor`.
