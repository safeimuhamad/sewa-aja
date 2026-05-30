ALTER TABLE payments
    ADD COLUMN midtrans_order_id VARCHAR(80) NULL AFTER payment_code,
    ADD COLUMN snap_token VARCHAR(255) NULL AFTER midtrans_order_id,
    ADD COLUMN redirect_url VARCHAR(255) NULL AFTER snap_token,
    ADD COLUMN transaction_status VARCHAR(50) NULL AFTER status,
    ADD COLUMN fraud_status VARCHAR(50) NULL AFTER transaction_status,
    ADD COLUMN payment_type VARCHAR(50) NULL AFTER method,
    ADD COLUMN raw_response JSON NULL AFTER proof_image_url,
    ADD UNIQUE KEY payments_midtrans_order_id_unique (midtrans_order_id),
    ADD KEY payments_transaction_status_index (transaction_status),
    ADD KEY payments_payment_type_index (payment_type);
