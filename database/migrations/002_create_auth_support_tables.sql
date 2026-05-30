CREATE TABLE auth_tokens (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    token_id CHAR(36) NOT NULL,
    name VARCHAR(80) NOT NULL DEFAULT 'api',
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY auth_tokens_token_id_unique (token_id),
    KEY auth_tokens_user_id_index (user_id),
    KEY auth_tokens_expires_at_index (expires_at),
    KEY auth_tokens_revoked_at_index (revoked_at),
    KEY auth_tokens_deleted_at_index (deleted_at),
    CONSTRAINT auth_tokens_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    email VARCHAR(160) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY password_reset_tokens_user_id_index (user_id),
    KEY password_reset_tokens_email_index (email),
    KEY password_reset_tokens_expires_at_index (expires_at),
    KEY password_reset_tokens_used_at_index (used_at),
    KEY password_reset_tokens_deleted_at_index (deleted_at),
    CONSTRAINT password_reset_tokens_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
