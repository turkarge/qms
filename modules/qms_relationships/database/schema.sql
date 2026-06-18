CREATE TABLE IF NOT EXISTS qms_relationship_types (
    relationship_type VARCHAR(80) PRIMARY KEY,
    relationship_kind VARCHAR(40) NOT NULL,
    display_name VARCHAR(160) NOT NULL,
    inverse_display_name VARCHAR(160) NULL,
    description VARCHAR(255) NULL,
    source_entity_types JSON NULL,
    target_entity_types JSON NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_qms_relationship_types_kind_status (relationship_kind, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qms_entity_relationships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    relationship_uid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    source_entity_id BIGINT UNSIGNED NOT NULL,
    target_entity_id BIGINT UNSIGNED NOT NULL,
    relationship_type VARCHAR(80) NOT NULL,
    relationship_kind VARCHAR(40) NOT NULL,
    description TEXT NULL,
    evidence_strength VARCHAR(30) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    metadata JSON NULL,
    valid_from DATE NULL,
    valid_until DATE NULL,
    archived_at DATETIME NULL,
    archived_by_user_id INT NULL,
    created_by_user_id INT NULL,
    updated_by_user_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_qms_relationships_uid (relationship_uid),
    INDEX idx_qms_relationships_company_status (company_id, status),
    INDEX idx_qms_relationships_source (source_entity_id, relationship_type, status),
    INDEX idx_qms_relationships_target (target_entity_id, relationship_type, status),
    CONSTRAINT fk_qms_relationships_company FOREIGN KEY (company_id) REFERENCES organization_companies(id) ON DELETE RESTRICT,
    CONSTRAINT fk_qms_relationships_source FOREIGN KEY (source_entity_id) REFERENCES qms_entities(id) ON DELETE RESTRICT,
    CONSTRAINT fk_qms_relationships_target FOREIGN KEY (target_entity_id) REFERENCES qms_entities(id) ON DELETE RESTRICT,
    CONSTRAINT fk_qms_relationships_type FOREIGN KEY (relationship_type) REFERENCES qms_relationship_types(relationship_type) ON DELETE RESTRICT,
    CONSTRAINT fk_qms_relationships_archived_by FOREIGN KEY (archived_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_qms_relationships_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_qms_relationships_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO qms_relationship_types(relationship_type, relationship_kind, display_name, inverse_display_name, description) VALUES
('satisfies_requirement', 'direct', 'Satisfies Requirement', 'Satisfied By', 'Entity satisfies a standard requirement.'),
('provides_evidence_for', 'evidence', 'Provides Evidence For', 'Has Evidence', 'Evidence record supports another entity.'),
('depends_on', 'dependency', 'Depends On', 'Required By', 'Entity depends on another entity.'),
('references', 'reference', 'References', 'Referenced By', 'General traceable reference between entities.'),
('caused_by', 'direct', 'Caused By', 'Causes', 'Finding or action is caused by another entity.'),
('mitigates', 'direct', 'Mitigates', 'Mitigated By', 'Entity mitigates a risk or gap.')
ON DUPLICATE KEY UPDATE relationship_kind=VALUES(relationship_kind), display_name=VALUES(display_name), inverse_display_name=VALUES(inverse_display_name), description=VALUES(description), status='active';
