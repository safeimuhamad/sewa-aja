# Product Catalog API SewaAja

Base URL:

```text
http://localhost/sewaaja/backend/services/product-service/public
```

## Product Listing

`GET /products`

Query parameters:

```text
q=sony
category=elektronik
location=Jakarta
min_price=100000
max_price=500000
sort=price_asc
page=1
per_page=12
```

Sort values:

- `newest`
- `price_asc`
- `price_desc`
- `name_asc`
- `stock_desc`

Response:

```json
{
  "success": true,
  "message": "Produk berhasil diambil.",
  "data": {
    "items": [],
    "meta": {
      "page": 1,
      "per_page": 12,
      "total": 0,
      "total_pages": 0
    }
  },
  "errors": null
}
```

## Filter Options

`GET /products/filters`

Returns active categories, vendor locations, and supported sort options for the frontend filter UI.

## Product Detail

`GET /products/{id-or-slug}`

Example:

```text
GET /products/kamera-mirrorless-sony-a6400
```

Returns product detail, image gallery, vendor profile, units, related products, and demo reviews.

## Product Availability

`GET /products/{id-or-slug}/availability`

Query parameters:

```text
start_date=2026-06-01
end_date=2026-06-03
```

The API calculates booked quantity from active bookings and returns daily available stock.
