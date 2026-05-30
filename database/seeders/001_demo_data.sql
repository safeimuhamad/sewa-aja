INSERT INTO users (id, name, email, password_hash, phone, role, status, email_verified_at) VALUES
('11111111-1111-4111-8111-111111111111', 'Admin SewaAja', 'admin@sewaaja.test', '$2y$10$SbbrPDT6Dv24J1wDPQXg2OezIwl06wJBYv7n9tlP0e4uCKIr8eJIy', '081100000001', 'admin', 'active', CURRENT_TIMESTAMP),
('22222222-2222-4222-8222-222222222222', 'Budi Rental', 'vendor@sewaaja.test', '$2y$10$SbbrPDT6Dv24J1wDPQXg2OezIwl06wJBYv7n9tlP0e4uCKIr8eJIy', '081100000002', 'vendor', 'active', CURRENT_TIMESTAMP),
('33333333-3333-4333-8333-333333333333', 'Citra Customer', 'customer@sewaaja.test', '$2y$10$SbbrPDT6Dv24J1wDPQXg2OezIwl06wJBYv7n9tlP0e4uCKIr8eJIy', '081100000003', 'customer', 'active', CURRENT_TIMESTAMP);

INSERT INTO vendors (id, user_id, store_name, slug, description, address, city, province, postal_code, status) VALUES
('44444444-4444-4444-8444-444444444444', '22222222-2222-4222-8222-222222222222', 'Budi Event Rental', 'budi-event-rental', 'Penyedia perlengkapan event, kamera, dan alat kerja harian.', 'Jl. Melati No. 10', 'Jakarta Selatan', 'DKI Jakarta', '12560', 'active');

INSERT INTO categories (id, parent_id, name, slug, description, is_active) VALUES
('55555555-5555-4555-8555-555555555555', NULL, 'Elektronik', 'elektronik', 'Kamera, laptop, audio, dan perangkat elektronik.', 1),
('66666666-6666-4666-8666-666666666666', NULL, 'Event', 'event', 'Tenda, kursi, meja, dan kebutuhan acara.', 1),
('77777777-7777-4777-8777-777777777777', NULL, 'Transportasi', 'transportasi', 'Kendaraan dan perlengkapan perjalanan.', 1);

INSERT INTO products (id, vendor_id, category_id, name, slug, description, price_per_day, deposit_amount, stock_quantity, unit_label, status) VALUES
('88888888-8888-4888-8888-888888888888', '44444444-4444-4444-8444-444444444444', '55555555-5555-4555-8555-555555555555', 'Kamera Mirrorless Sony A6400', 'kamera-mirrorless-sony-a6400', 'Kamera mirrorless untuk dokumentasi event dan konten harian.', 175000.00, 500000.00, 2, 'unit', 'active'),
('99999999-9999-4999-8999-999999999999', '44444444-4444-4444-8444-444444444444', '66666666-6666-4666-8666-666666666666', 'Paket Kursi Event 50 Pcs', 'paket-kursi-event-50-pcs', 'Paket kursi lipat untuk acara keluarga, seminar, dan gathering.', 300000.00, 250000.00, 3, 'paket', 'active');

INSERT INTO product_images (id, product_id, image_url, alt_text, sort_order, is_primary) VALUES
('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '88888888-8888-4888-8888-888888888888', '/uploads/products/sony-a6400-main.jpg', 'Kamera Sony A6400 tampak depan', 1, 1),
('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '88888888-8888-4888-8888-888888888888', '/uploads/products/sony-a6400-kit.jpg', 'Paket kamera Sony A6400 lengkap', 2, 0),
('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '99999999-9999-4999-8999-999999999999', '/uploads/products/kursi-event-main.jpg', 'Paket kursi event', 1, 1);

INSERT INTO product_units (id, product_id, sku, name, serial_number, condition_status, availability_status, notes) VALUES
('dddddddd-dddd-4ddd-8ddd-dddddddddddd', '88888888-8888-4888-8888-888888888888', 'CAM-SONY-A6400-001', 'Sony A6400 Unit 1', 'SNY-A6400-001', 'good', 'available', 'Termasuk lensa kit dan charger.'),
('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '88888888-8888-4888-8888-888888888888', 'CAM-SONY-A6400-002', 'Sony A6400 Unit 2', 'SNY-A6400-002', 'good', 'reserved', 'Termasuk lensa kit, charger, dan strap.'),
('ffffffff-ffff-4fff-8fff-ffffffffffff', '99999999-9999-4999-8999-999999999999', 'EVT-CHAIR-050-001', 'Paket Kursi Event Set 1', NULL, 'good', 'available', '50 kursi lipat bersih siap pakai.');

INSERT INTO bookings (id, customer_id, vendor_id, booking_code, status, start_date, end_date, subtotal_amount, deposit_amount, total_amount, notes) VALUES
('12121212-1212-4121-8121-121212121212', '33333333-3333-4333-8333-333333333333', '44444444-4444-4444-8444-444444444444', 'BKG-20260528-0001', 'confirmed', '2026-06-01', '2026-06-03', 350000.00, 500000.00, 850000.00, 'Untuk dokumentasi acara kantor.');

INSERT INTO booking_items (id, booking_id, product_id, product_unit_id, product_name, quantity, price_per_day, start_date, end_date, line_total) VALUES
('13131313-1313-4131-8131-131313131313', '12121212-1212-4121-8121-121212121212', '88888888-8888-4888-8888-888888888888', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'Kamera Mirrorless Sony A6400', 1, 175000.00, '2026-06-01', '2026-06-03', 350000.00);

INSERT INTO payments (id, booking_id, payment_code, method, status, amount, paid_at, transaction_reference, proof_image_url) VALUES
('14141414-1414-4141-8141-141414141414', '12121212-1212-4121-8121-121212121212', 'PAY-20260528-0001', 'bank_transfer', 'paid', 850000.00, CURRENT_TIMESTAMP, 'TRX-DEMO-0001', '/uploads/payments/demo-proof.jpg');
