ALTER TABLE vendors
    ADD COLUMN latitude DECIMAL(10, 7) NULL AFTER postal_code,
    ADD COLUMN longitude DECIMAL(10, 7) NULL AFTER latitude,
    ADD COLUMN service_radius_km INT UNSIGNED NOT NULL DEFAULT 25 AFTER longitude,
    ADD KEY vendors_geo_index (latitude, longitude);

ALTER TABLE products
    ADD COLUMN coverage_city VARCHAR(100) NULL AFTER unit_label,
    ADD COLUMN coverage_radius_km INT UNSIGNED NULL AFTER coverage_city,
    ADD FULLTEXT KEY products_search_fulltext (name, description);

ALTER TABLE product_images
    ADD COLUMN thumbnail_url VARCHAR(255) NULL AFTER image_url,
    ADD COLUMN mime_type VARCHAR(80) NULL AFTER alt_text,
    ADD COLUMN file_size INT UNSIGNED NULL AFTER mime_type,
    ADD COLUMN width INT UNSIGNED NULL AFTER file_size,
    ADD COLUMN height INT UNSIGNED NULL AFTER width;

CREATE TABLE product_availability_blocks (
    id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    quantity_blocked INT UNSIGNED NOT NULL DEFAULT 1,
    reason VARCHAR(180) NULL,
    created_by CHAR(36) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY product_availability_blocks_product_id_index (product_id),
    KEY product_availability_blocks_date_index (start_date, end_date),
    KEY product_availability_blocks_deleted_at_index (deleted_at),
    CONSTRAINT product_availability_blocks_product_id_foreign
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT product_availability_blocks_created_by_foreign
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NULL,
    role_target ENUM('admin', 'vendor', 'customer') NULL,
    type VARCHAR(80) NOT NULL,
    channel ENUM('in_app', 'email') NOT NULL DEFAULT 'in_app',
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(255) NULL,
    payload JSON NULL,
    status ENUM('queued', 'sent', 'failed', 'read') NOT NULL DEFAULT 'queued',
    read_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY notifications_user_id_index (user_id),
    KEY notifications_role_target_index (role_target),
    KEY notifications_status_index (status),
    KEY notifications_type_index (type),
    KEY notifications_deleted_at_index (deleted_at),
    CONSTRAINT notifications_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
    id CHAR(36) NOT NULL,
    booking_id CHAR(36) NOT NULL,
    customer_id CHAR(36) NOT NULL,
    vendor_id CHAR(36) NOT NULL,
    product_id CHAR(36) NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review_type ENUM('product', 'vendor') NOT NULL DEFAULT 'product',
    comment TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    moderation_note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY reviews_booking_target_unique (booking_id, customer_id, review_type, product_id),
    KEY reviews_customer_id_index (customer_id),
    KEY reviews_vendor_id_index (vendor_id),
    KEY reviews_product_id_index (product_id),
    KEY reviews_status_index (status),
    KEY reviews_rating_index (rating),
    KEY reviews_deleted_at_index (deleted_at),
    CONSTRAINT reviews_booking_id_foreign
        FOREIGN KEY (booking_id) REFERENCES bookings (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT reviews_customer_id_foreign
        FOREIGN KEY (customer_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT reviews_vendor_id_foreign
        FOREIGN KEY (vendor_id) REFERENCES vendors (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT reviews_product_id_foreign
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT reviews_rating_check CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendor_finance_transactions (
    id CHAR(36) NOT NULL,
    vendor_id CHAR(36) NOT NULL,
    booking_id CHAR(36) NULL,
    payment_id CHAR(36) NULL,
    type ENUM('earning', 'fee', 'payout', 'adjustment') NOT NULL,
    amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    platform_fee_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    net_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'available', 'paid', 'void') NOT NULL DEFAULT 'pending',
    description VARCHAR(180) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY vendor_finance_vendor_id_index (vendor_id),
    KEY vendor_finance_booking_id_index (booking_id),
    KEY vendor_finance_payment_id_index (payment_id),
    KEY vendor_finance_status_index (status),
    KEY vendor_finance_deleted_at_index (deleted_at),
    CONSTRAINT vendor_finance_vendor_id_foreign
        FOREIGN KEY (vendor_id) REFERENCES vendors (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT vendor_finance_booking_id_foreign
        FOREIGN KEY (booking_id) REFERENCES bookings (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT vendor_finance_payment_id_foreign
        FOREIGN KEY (payment_id) REFERENCES payments (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendor_payouts (
    id CHAR(36) NOT NULL,
    vendor_id CHAR(36) NOT NULL,
    payout_code VARCHAR(40) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    status ENUM('requested', 'processing', 'paid', 'rejected') NOT NULL DEFAULT 'requested',
    bank_name VARCHAR(100) NULL,
    bank_account_name VARCHAR(120) NULL,
    bank_account_number VARCHAR(80) NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY vendor_payouts_code_unique (payout_code),
    KEY vendor_payouts_vendor_id_index (vendor_id),
    KEY vendor_payouts_status_index (status),
    KEY vendor_payouts_deleted_at_index (deleted_at),
    CONSTRAINT vendor_payouts_vendor_id_foreign
        FOREIGN KEY (vendor_id) REFERENCES vendors (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id CHAR(36) NOT NULL,
    actor_id CHAR(36) NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id CHAR(36) NULL,
    metadata JSON NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY audit_logs_actor_id_index (actor_id),
    KEY audit_logs_entity_index (entity_type, entity_id),
    KEY audit_logs_action_index (action),
    CONSTRAINT audit_logs_actor_id_foreign
        FOREIGN KEY (actor_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
