# SewaAja Growth Features API

## Availability

- `GET /backend/services/product-service/public/products/{id|slug}/availability?start_date=2026-06-01&end_date=2026-06-07`
- `GET /backend/services/product-service/public/products/{id|slug}/calendar`
- `POST /backend/services/product-service/public/vendor/products/{id}/availability-blocks`

Vendor block payload:

```json
{
  "start_date": "2026-06-10",
  "end_date": "2026-06-11",
  "quantity_blocked": 1,
  "reason": "Maintenance rutin"
}
```

## Media

- `POST /backend/services/product-service/public/vendor/products/{id}/media`
- `PUT /backend/services/product-service/public/vendor/products/{id}/images/sort`

Upload uses multipart form data with `image`, optional `alt_text`, `sort_order`, and `is_primary`.

## Reviews

- `GET /backend/services/product-service/public/products/{id|slug}/reviews`
- `POST /backend/services/product-service/public/products/{id|slug}/reviews`
- `GET /backend/services/admin-service/public/reviews`
- `PUT /backend/services/admin-service/public/reviews/{id}/status`

Only customers with completed bookings can create reviews. Reviews default to `pending` and must be approved by admin before appearing publicly.

## Search and Location

- `GET /backend/services/product-service/public/products?q=kamera&category=elektronik&min_price=100000&max_price=300000&available_start=2026-06-01&available_end=2026-06-03`
- `GET /backend/services/product-service/public/products?latitude=-6.2607&longitude=106.7816&radius_km=25`
- `GET /backend/services/product-service/public/products/suggestions?q=kam`

Nearby search uses an indexed latitude/longitude bounding box for fast filtering.

## Notifications

- `GET /backend/services/notification-service/public/notifications`
- `POST /backend/services/notification-service/public/notifications`
- `PUT /backend/services/notification-service/public/notifications/{id}/read`

Notifications support `in_app` and `email` channel records. Email delivery can be processed later from queued rows.

## Reporting and Finance

- `GET /backend/services/admin-service/public/reports?start_date=2026-05-01&end_date=2026-05-31`
- `GET /backend/services/booking-service/public/vendor/finance`

Finance summary calculates gross revenue, platform fee, net earnings, paid payout, and available balance.
