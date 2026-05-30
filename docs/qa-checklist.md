# QA Checklist SewaAja

## Authentication

- Register customer with valid and invalid payloads.
- Register vendor and verify vendor role is blocked from customer-only APIs.
- Login with demo users and confirm token is stored.
- Logout and confirm protected pages redirect to login.
- Confirm rate limit returns HTTP 429 after repeated auth requests.

## Product and Search

- Product listing loads with pagination, sorting, category, price, location, and keyword filters.
- Availability filters exclude fully booked or manually blocked products.
- Search suggestions return relevant product names.
- Nearby search works with `latitude`, `longitude`, and `radius_km`.

## Availability and Booking

- Product calendar displays booked quantity, blocked quantity, and available stock.
- Vendor can block unavailable dates.
- Checkout rejects overlapping bookings and blocked dates.
- Quantity validation rejects requests above available stock.
- Cart calculations match backend quote totals.

## Media

- Vendor can upload JPG, PNG, and WebP images.
- Upload rejects unsupported files and files above max size.
- Uploaded images are converted to WebP and thumbnails are generated.
- Gallery sorting updates image order and primary image.

## Payment

- Midtrans token generation works in sandbox.
- QRIS and VA flows return actionable payment data.
- Webhook signature validation rejects invalid callbacks.
- Paid callbacks update payment and booking status.
- Payment history shows latest records.

## Reviews and Notifications

- Customer without completed rental cannot review.
- Customer with completed rental can submit review.
- Pending review is hidden from product detail until admin approves.
- Admin can approve or reject reviews.
- In-app notification dropdown loads authenticated user notifications.

## Dashboards

- Vendor dashboard loads product, booking, sales, and finance summaries.
- Admin dashboard loads stats, reports, moderation, and monitoring data.
- Customer dashboard loads active rentals, history, detail, invoice, cancel action, and profile.

## Responsive and Performance

- Test pages at 375px, 768px, 1024px, and desktop width.
- Buttons and form fields remain readable and touch-friendly.
- Images use lazy loading where applicable.
- Product pages include dynamic title, description, Open Graph, canonical, and structured data.
- Generate sitemap after product changes with `php backend/scripts/generate-sitemap.php`.
