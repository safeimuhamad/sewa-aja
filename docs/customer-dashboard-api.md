# SewaAja Customer Dashboard API

Semua endpoint customer membutuhkan token user dengan role `customer`.

```http
Authorization: Bearer {customer_access_token}
Content-Type: application/json
```

## Booking Service

Base URL: `/backend/services/booking-service/public`

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/customer/dashboard` | Overview dashboard, active rentals, recent history |
| GET | `/customer/rentals` | List rental dengan pagination, filter `status_group`, `status`, `q` |
| GET | `/customer/bookings/{id}` | Detail booking customer |
| PUT | `/customer/bookings/{id}/cancel` | Cancel booking customer untuk status `pending` atau `confirmed` |
| GET | `/customer/bookings/{id}/invoice` | Data invoice untuk download HTML |

Contoh list:

```http
GET /customer/rentals?status_group=active&page=1&per_page=5
```

`status_group`:

- `active`: `pending`, `confirmed`, `ongoing`
- `history`: `completed`, `cancelled`

## Auth Service

Base URL: `/backend/services/auth-service/public`

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/profile` | Ambil profile user login |
| PUT | `/profile` | Update nama dan nomor HP |

Contoh update profile:

```json
{
  "name": "Citra Customer",
  "phone": "081100000003"
}
```

## Frontend

Halaman customer:

- `/frontend/public/customer-dashboard.html`
- `/frontend/public/customer-booking-detail.html?id={booking_id}`

Akun demo customer:

- Email: `customer@sewaaja.test`
- Password: `password123`
