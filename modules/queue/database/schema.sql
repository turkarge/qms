CREATE TABLE IF NOT EXISTS jobs_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue_name VARCHAR(80) NOT NULL DEFAULT 'default',
    job_type VARCHAR(120) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    max_attempts INT NOT NULL DEFAULT 3,
    status VARCHAR(30) NOT NULL DEFAULT 'queued',
    last_error TEXT NULL,
    available_at DATETIME NOT NULL,
    reserved_at DATETIME NULL,
    finished_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jobs_queue_status_available (status, available_at),
    INDEX idx_jobs_queue_queue_name (queue_name),
    INDEX idx_jobs_queue_job_type (job_type),
    INDEX idx_jobs_queue_pick (queue_name, status, available_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
