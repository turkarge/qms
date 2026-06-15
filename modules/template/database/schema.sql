CREATE TABLE IF NOT EXISTS templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kind VARCHAR(30) NOT NULL,
    module_key VARCHAR(80) NOT NULL,
    target_key VARCHAR(120) NOT NULL,
    code VARCHAR(120) NOT NULL,
    name VARCHAR(190) NOT NULL,
    language VARCHAR(10) NOT NULL DEFAULT 'tr',
    subject VARCHAR(190) NULL,
    body MEDIUMTEXT NOT NULL,
    variables_json LONGTEXT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by_user_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_templates_scope (kind, module_key, target_key, language, code),
    INDEX idx_templates_lookup (kind, module_key, target_key, language, is_active),
    INDEX idx_templates_module (module_key),
    INDEX idx_templates_active (is_active),
    CONSTRAINT fk_templates_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
