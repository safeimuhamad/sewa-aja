CREATE TABLE users (
    id CHAR(36) NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    role ENUM('admin', 'vendor', 'customer') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    KEY users_role_index (role),
    KEY users_status_index (status),
    KEY users_deleted_at_index (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendors (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    store_name VARCHAR(140) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    description TEXT NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    province VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    status ENUM('pending', 'active', 'suspended') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY vendors_user_id_unique (user_id),
    UNIQUE KEY vendors_slug_unique (slug),
    KEY vendors_status_index (status),
    KEY vendors_location_index (city, province),
    KEY vendors_deleted_at_index (deleted_at),
    CONSTRAINT vendors_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id CHAR(36) NOT NULL,
    parent_id CHAR(36) NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY categories_slug_unique (slug),
    KEY categories_parent_id_index (parent_id),
    KEY categories_is_active_index (is_active),
    KEY categories_deleted_at_index (deleted_at),
    CONSTRAINT categories_parent_id_foreign
        FOREIGN KEY (parent_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id CHAR(36) NOT NULL,
    vendor_id CHAR(36) NOT NULL,
    category_id CHAR(36) NOT NULL,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    description TEXT NULL,
    price_per_day DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    deposit_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    unit_label VARCHAR(40) NOT NULL DEFAULT 'unit',
    status ENUM('draft', 'active', 'inactive') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY products_slug_unique (slug),
    KEY products_vendor_id_index (vendor_id),
    KEY products_category_id_index (category_id),
    KEY products_status_index (status),
    KEY products_stock_quantity_index (stock_quantity),
    KEY products_vendor_status_index (vendor_id, status),
    KEY products_category_status_index (category_id, status),
    KEY products_deleted_at_index (deleted_at),
    CONSTRAINT products_vendor_id_foreign
        FOREIGN KEY (vendor_id) REFERENCES vendors (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT products_category_id_foreign
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_images (
    id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(180) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY product_images_product_id_index (product_id),
    KEY product_images_primary_index (product_id, is_primary),
    KEY product_images_sort_order_index (product_id, sort_order),
    KEY product_images_deleted_at_index (deleted_at),
    CONSTRAINT product_images_product_id_foreign
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_units (
    id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    sku VARCHAR(80) NOT NULL,
    name VARCHAR(140) NOT NULL,
    serial_number VARCHAR(120) NULL,
    condition_status ENUM('new', 'good', 'fair', 'maintenance') NOT NULL DEFAULT 'good',
    availability_status ENUM('available', 'reserved', 'rented', 'maintenance', 'inactive') NOT NULL DEFAULT 'available',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY product_units_sku_unique (sku),
    KEY product_units_product_id_index (product_id),
    KEY product_units_availability_index (availability_status),
    KEY product_units_condition_index (condition_status),
    KEY product_units_product_availability_index (product_id, availability_status),
    KEY product_units_deleted_at_index (deleted_at),
    CONSTRAINT product_units_product_id_foreign
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
    id CHAR(36) NOT NULL,
    customer_id CHAR(36) NOT NULL,
    vendor_id CHAR(36) NOT NULL,
    booking_code VARCHAR(40) NOT NULL,
    status ENUM('pending', 'confirmed', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    subtotal_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    deposit_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY bookings_booking_code_unique (booking_code),
    KEY bookings_customer_id_index (customer_id),
    KEY bookings_vendor_id_index (vendor_id),
    KEY bookings_status_index (status),
    KEY bookings_date_range_index (start_date, end_date),
    KEY bookings_customer_status_index (customer_id, status),
    KEY bookings_vendor_status_index (vendor_id, status),
    KEY bookings_deleted_at_index (deleted_at),
    CONSTRAINT bookings_customer_id_foreign
        FOREIGN KEY (customer_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT bookings_vendor_id_foreign
        FOREIGN KEY (vendor_id) REFERENCES vendors (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_items (
    id CHAR(36) NOT NULL,
    booking_id CHAR(36) NOT NULL,
    product_id CHAR(36) NOT NULL,
    product_unit_id CHAR(36) NULL,
    product_name VARCHAR(180) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price_per_day DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    line_total DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY booking_items_booking_id_index (booking_id),
    KEY booking_items_product_id_index (product_id),
    KEY booking_items_product_unit_id_index (product_unit_id),
    KEY booking_items_date_range_index (start_date, end_date),
    KEY booking_items_deleted_at_index (deleted_at),
    CONSTRAINT booking_items_booking_id_foreign
        FOREIGN KEY (booking_id) REFERENCES bookings (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT booking_items_product_id_foreign
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT booking_items_product_unit_id_foreign
        FOREIGN KEY (product_unit_id) REFERENCES product_units (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id CHAR(36) NOT NULL,
    booking_id CHAR(36) NOT NULL,
    payment_code VARCHAR(40) NOT NULL,
    method ENUM('bank_transfer', 'ewallet', 'cash', 'payment_gateway') NOT NULL DEFAULT 'bank_transfer',
    status ENUM('pending', 'paid', 'failed', 'refunded', 'expired') NOT NULL DEFAULT 'pending',
    amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    paid_at TIMESTAMP NULL,
    transaction_reference VARCHAR(120) NULL,
    proof_image_url VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY payments_payment_code_unique (payment_code),
    KEY payments_booking_id_index (booking_id),
    KEY payments_status_index (status),
    KEY payments_method_index (method),
    KEY payments_paid_at_index (paid_at),
    KEY payments_deleted_at_index (deleted_at),
    CONSTRAINT payments_booking_id_foreign
        FOREIGN KEY (booking_id) REFERENCES bookings (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
