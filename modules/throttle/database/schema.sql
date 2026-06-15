CREATE TABLE IF NOT EXISTS request_throttles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    throttle_key CHAR(64) NOT NULL,
    hit_count INT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at DATETIME NOT NULL,
    blocked_until DATETIME NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_request_throttles_key (throttle_key),
    INDEX idx_request_throttles_blocked_until (blocked_until),
    INDEX idx_request_throttles_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

