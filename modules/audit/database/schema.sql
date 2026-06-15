CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    module_key VARCHAR(80) NOT NULL,
    action_key VARCHAR(120) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'success',
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT NULL,
    route_path VARCHAR(190) NULL,
    request_method VARCHAR(10) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    details_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_logs_user_id (user_id),
    INDEX idx_audit_logs_module_key (module_key),
    INDEX idx_audit_logs_action_key (action_key),
    INDEX idx_audit_logs_status (status),
    INDEX idx_audit_logs_created_at (created_at),
    INDEX idx_audit_logs_created_status_id (created_at, status, id),
    CONSTRAINT fk_audit_logs_user_id
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
