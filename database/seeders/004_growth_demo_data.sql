UPDATE vendors
SET latitude = -6.2607000,
    longitude = 106.7816000,
    service_radius_km = 25
WHERE id = '44444444-4444-4444-8444-444444444444';

UPDATE products
SET coverage_city = 'Jakarta Selatan',
    coverage_radius_km = 25
WHERE id IN (
    '88888888-8888-4888-8888-888888888888',
    '99999999-9999-4999-8999-999999999999'
);

INSERT INTO product_availability_blocks
(id, product_id, start_date, end_date, quantity_blocked, reason, created_by) VALUES
('15151515-1515-4151-8151-151515151515', '88888888-8888-4888-8888-888888888888', '2026-06-10', '2026-06-11', 1, 'Maintenance rutin', '22222222-2222-4222-8222-222222222222');

INSERT INTO notifications
(id, user_id, role_target, type, channel, title, message, action_url, status) VALUES
('16161616-1616-4161-8161-161616161616', '33333333-3333-4333-8333-333333333333', NULL, 'booking', 'in_app', 'Booking dikonfirmasi', 'Booking kamera Sony A6400 sudah dikonfirmasi vendor.', 'customer-dashboard.html', 'sent'),
('17171717-1717-4171-8171-171717171717', NULL, 'admin', 'review', 'in_app', 'Review menunggu moderasi', 'Ada review baru yang perlu dicek admin.', 'admin-dashboard.html', 'sent');

INSERT INTO reviews
(id, booking_id, customer_id, vendor_id, product_id, rating, review_type, comment, status) VALUES
('18181818-1818-4181-8181-181818181818', '12121212-1212-4121-8121-121212121212', '33333333-3333-4333-8333-333333333333', '44444444-4444-4444-8444-444444444444', '88888888-8888-4888-8888-888888888888', 5, 'product', 'Unit bersih, lengkap, dan proses pickup cepat.', 'approved');

INSERT INTO vendor_finance_transactions
(id, vendor_id, booking_id, payment_id, type, amount, platform_fee_amount, net_amount, status, description) VALUES
('19191919-1919-4191-8191-191919191919', '44444444-4444-4444-8444-444444444444', '12121212-1212-4121-8121-121212121212', '14141414-1414-4141-8141-141414141414', 'earning', 850000.00, 85000.00, 765000.00, 'available', 'Pendapatan booking demo kamera');
