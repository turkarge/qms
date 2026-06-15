CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
    setting_value LONGTEXT NULL,
    is_secret TINYINT(1) NOT NULL DEFAULT 0,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_app_settings_is_secret (is_secret),
    INDEX idx_app_settings_updated_by (updated_by),
    CONSTRAINT fk_app_settings_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_modules (
    module_key VARCHAR(80) NOT NULL PRIMARY KEY,
    module_name VARCHAR(120) NOT NULL,
    installed_version VARCHAR(50) NOT NULL DEFAULT '1.0.0',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    is_core TINYINT(1) NOT NULL DEFAULT 1,
    load_order INT NOT NULL DEFAULT 100,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_app_modules_enabled (is_enabled),
    INDEX idx_app_modules_core (is_core),
    INDEX idx_app_modules_load_order (load_order),
    CONSTRAINT fk_app_modules_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
