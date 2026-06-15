CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_name VARCHAR(120) NOT NULL DEFAULT 'default',
    token_hash CHAR(64) NOT NULL,
    last_used_at DATETIME NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_api_tokens_hash (token_hash),
    INDEX idx_api_tokens_user_id (user_id),
    INDEX idx_api_tokens_expires_at (expires_at),
    INDEX idx_api_tokens_revoked_at (revoked_at),
    CONSTRAINT fk_api_tokens_user_id
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_token_scopes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_id BIGINT UNSIGNED NOT NULL,
    scope_key VARCHAR(80) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_api_token_scope (token_id, scope_key),
    INDEX idx_api_token_scopes_token_id (token_id),
    CONSTRAINT fk_api_token_scopes_token_id
        FOREIGN KEY (token_id) REFERENCES api_tokens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_request_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    route_path VARCHAR(190) NOT NULL,
    request_method VARCHAR(10) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL,
    error_code VARCHAR(80) NULL,
    user_id INT NULL,
    token_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    duration_ms INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_request_logs_created_at (created_at),
    INDEX idx_api_request_logs_status_code (status_code),
    INDEX idx_api_request_logs_route_path (route_path),
    INDEX idx_api_request_logs_error_code (error_code),
    INDEX idx_api_request_logs_user_id (user_id),
    INDEX idx_api_request_logs_token_id (token_id),
    INDEX idx_api_request_logs_created_route_method_status (created_at, route_path, request_method, status_code),
    INDEX idx_api_request_logs_created_status_id (created_at, status_code, id),
    CONSTRAINT fk_api_request_logs_user_id
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_api_request_logs_token_id
        FOREIGN KEY (token_id) REFERENCES api_tokens(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
