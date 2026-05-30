# SewaAja Admin API

Semua endpoint admin membutuhkan token user dengan role `admin`.

```http
Authorization: Bearer {admin_access_token}
Content-Type: application/json
```

Base URL: `/backend/services/admin-service/public`

## Endpoints

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/dashboard` | Statistik user, vendor, produk, booking, payment, revenue, dan status breakdown |
| GET | `/users` | User management dengan search, filter role/status, pagination |
| GET | `/vendors` | Vendor approval dengan search/filter/pagination |
| GET | `/products` | Product moderation dengan search/filter/pagination |
| GET | `/bookings` | Booking management dengan search/filter/pagination |
| GET | `/payments` | Payment monitoring dengan search/filter/pagination |
| PUT | `/{resource}/{id}/status` | Update status resource |

Query list:

```http
GET /vendors?q=budi&status=pending&page=1&per_page=10
```

Update status:

```json
{
  "status": "active"
}
```

Resource dan status:

- `users`: `active`, `inactive`, `suspended`
- `vendors`: `pending`, `active`, `suspended`
- `products`: `draft`, `active`, `inactive`
- `bookings`: `pending`, `confirmed`, `ongoing`, `completed`, `cancelled`
- `payments`: `pending`, `paid`, `failed`, `refunded`, `expired`

## Frontend

Halaman admin:

- `/frontend/public/admin-dashboard.html`

Akun demo admin:

- Email: `admin@sewaaja.test`
- Password: `password123`
