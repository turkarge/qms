CREATE TABLE IF NOT EXISTS standards_catalog (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    standard_uid CHAR(36) NOT NULL,
    standard_code VARCHAR(40) NOT NULL,
    standard_name VARCHAR(190) NOT NULL,
    owner_organization VARCHAR(120) NULL,
    category VARCHAR(80) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_by_user_id INT NULL,
    updated_by_user_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_standards_catalog_uid (standard_uid),
    UNIQUE KEY uk_standards_catalog_company_code (company_id, standard_code),
    INDEX idx_standards_catalog_company_status (company_id, status),
    CONSTRAINT fk_standards_catalog_company FOREIGN KEY (company_id) REFERENCES organization_companies(id) ON DELETE RESTRICT,
    CONSTRAINT fk_standards_catalog_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_standards_catalog_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS standards_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    standard_id BIGINT UNSIGNED NOT NULL,
    version_uid CHAR(36) NOT NULL,
    version_label VARCHAR(60) NOT NULL,
    published_on DATE NULL,
    effective_from DATE NULL,
    transition_until DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    created_by_user_id INT NULL,
    updated_by_user_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_standards_versions_uid (version_uid),
    UNIQUE KEY uk_standards_versions_standard_label (standard_id, version_label),
    INDEX idx_standards_versions_status (status),
    CONSTRAINT fk_standards_versions_standard FOREIGN KEY (standard_id) REFERENCES standards_catalog(id) ON DELETE RESTRICT,
    CONSTRAINT fk_standards_versions_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_standards_versions_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS standards_clauses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version_id BIGINT UNSIGNED NOT NULL,
    parent_clause_id BIGINT UNSIGNED NULL,
    clause_uid CHAR(36) NOT NULL,
    clause_code VARCHAR(40) NOT NULL,
    title VARCHAR(190) NOT NULL,
    body TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_standards_clauses_uid (clause_uid),
    UNIQUE KEY uk_standards_clauses_version_code (version_id, clause_code),
    INDEX idx_standards_clauses_parent (parent_clause_id),
    CONSTRAINT fk_standards_clauses_version FOREIGN KEY (version_id) REFERENCES standards_versions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_standards_clauses_parent FOREIGN KEY (parent_clause_id) REFERENCES standards_clauses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS standards_requirements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version_id BIGINT UNSIGNED NOT NULL,
    clause_id BIGINT UNSIGNED NOT NULL,
    requirement_uid CHAR(36) NOT NULL,
    requirement_code VARCHAR(60) NOT NULL,
    title VARCHAR(190) NOT NULL,
    requirement_text TEXT NOT NULL,
    verification_method VARCHAR(80) NULL,
    criticality VARCHAR(30) NOT NULL DEFAULT 'normal',
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_standards_requirements_uid (requirement_uid),
    UNIQUE KEY uk_standards_requirements_version_code (version_id, requirement_code),
    INDEX idx_standards_requirements_clause (clause_id, status),
    CONSTRAINT fk_standards_requirements_version FOREIGN KEY (version_id) REFERENCES standards_versions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_standards_requirements_clause FOREIGN KEY (clause_id) REFERENCES standards_clauses(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS standards_controls (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requirement_id BIGINT UNSIGNED NOT NULL,
    control_uid CHAR(36) NOT NULL,
    control_code VARCHAR(60) NOT NULL,
    title VARCHAR(190) NOT NULL,
    control_text TEXT NOT NULL,
    control_type VARCHAR(60) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_standards_controls_uid (control_uid),
    UNIQUE KEY uk_standards_controls_requirement_code (requirement_id, control_code),
    INDEX idx_standards_controls_requirement (requirement_id, status),
    CONSTRAINT fk_standards_controls_requirement FOREIGN KEY (requirement_id) REFERENCES standards_requirements(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
