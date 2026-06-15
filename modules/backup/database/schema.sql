CREATE TABLE IF NOT EXISTS db_backups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(190) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'ready',
    created_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_db_backups_status (status),
    INDEX idx_db_backups_created_by (created_by),
    INDEX idx_db_backups_created_at_id (created_at, id),
    CONSTRAINT fk_db_backups_created_by
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS db_backup_restores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_id BIGINT UNSIGNED NOT NULL,
    restored_by INT NULL,
    restore_output TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_db_backup_restores_backup_id (backup_id),
    INDEX idx_db_backup_restores_restored_by (restored_by),
    CONSTRAINT fk_db_backup_restores_backup_id
        FOREIGN KEY (backup_id) REFERENCES db_backups(id) ON DELETE CASCADE,
    CONSTRAINT fk_db_backup_restores_restored_by
        FOREIGN KEY (restored_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
