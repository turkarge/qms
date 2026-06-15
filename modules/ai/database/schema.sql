CREATE TABLE IF NOT EXISTS ai_schema_entities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_key VARCHAR(80) NOT NULL,
    entity_key VARCHAR(120) NOT NULL,
    table_name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    permission_slug VARCHAR(150) NULL,
    metadata_json LONGTEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ai_schema_entity (module_key, entity_key),
    INDEX idx_ai_schema_entities_module (module_key),
    INDEX idx_ai_schema_entities_table (table_name),
    INDEX idx_ai_schema_entities_permission (permission_slug),
    INDEX idx_ai_schema_entities_active (is_active),
    CONSTRAINT fk_ai_schema_entities_created_by
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ai_schema_entities_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_schema_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_id BIGINT UNSIGNED NOT NULL,
    field_name VARCHAR(120) NOT NULL,
    field_type VARCHAR(80) NULL,
    description VARCHAR(500) NULL,
    is_sensitive TINYINT(1) NOT NULL DEFAULT 0,
    is_filterable TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    metadata_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ai_schema_field (entity_id, field_name),
    INDEX idx_ai_schema_fields_entity (entity_id),
    INDEX idx_ai_schema_fields_sensitive (is_sensitive),
    INDEX idx_ai_schema_fields_filterable (is_filterable),
    INDEX idx_ai_schema_fields_active (is_active),
    CONSTRAINT fk_ai_schema_fields_entity_id
        FOREIGN KEY (entity_id) REFERENCES ai_schema_entities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_schema_index (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_id BIGINT UNSIGNED NOT NULL,
    field_id BIGINT UNSIGNED NULL,
    module_key VARCHAR(80) NOT NULL,
    entity_key VARCHAR(120) NOT NULL,
    table_name VARCHAR(120) NOT NULL,
    field_name VARCHAR(120) NULL,
    token VARCHAR(120) NOT NULL,
    source_type VARCHAR(40) NOT NULL,
    source_text VARCHAR(500) NULL,
    weight SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ai_schema_index_token (token),
    INDEX idx_ai_schema_index_entity (entity_id),
    INDEX idx_ai_schema_index_field (field_id),
    INDEX idx_ai_schema_index_module_entity (module_key, entity_key),
    INDEX idx_ai_schema_index_source (source_type),
    CONSTRAINT fk_ai_schema_index_entity_id
        FOREIGN KEY (entity_id) REFERENCES ai_schema_entities(id) ON DELETE CASCADE,
    CONSTRAINT fk_ai_schema_index_field_id
        FOREIGN KEY (field_id) REFERENCES ai_schema_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_model_adapters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adapter_key VARCHAR(120) NOT NULL,
    provider VARCHAR(80) NOT NULL,
    model_name VARCHAR(150) NOT NULL,
    adapter_type VARCHAR(40) NOT NULL DEFAULT 'chat',
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    is_external TINYINT(1) NOT NULL DEFAULT 1,
    config_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ai_model_adapter_key (adapter_key),
    INDEX idx_ai_model_adapters_provider (provider),
    INDEX idx_ai_model_adapters_enabled (is_enabled),
    INDEX idx_ai_model_adapters_external (is_external)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_key VARCHAR(120) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'success',
    model_adapter VARCHAR(120) NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT NULL,
    route_path VARCHAR(190) NULL,
    ip_address VARCHAR(45) NULL,
    details_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ai_audit_logs_user_id (user_id),
    INDEX idx_ai_audit_logs_action_key (action_key),
    INDEX idx_ai_audit_logs_status (status),
    INDEX idx_ai_audit_logs_model_adapter (model_adapter),
    INDEX idx_ai_audit_logs_created_at (created_at),
    INDEX idx_ai_audit_logs_created_status_id (created_at, status, id),
    CONSTRAINT fk_ai_audit_logs_user_id
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ai_model_adapters (
    adapter_key,
    provider,
    model_name,
    adapter_type,
    is_enabled,
    is_external,
    config_json
) VALUES
    ('local-qwen-placeholder', 'qwen', 'qwen-local', 'chat', 0, 0, NULL),
    ('openai-placeholder', 'openai', 'external-model', 'chat', 0, 1, JSON_OBJECT('api_key_env', 'OPENAI_API_KEY')),
    ('openai-sql-placeholder', 'openai', 'external-sql-model', 'sql_generation', 0, 1, JSON_OBJECT('api_key_env', 'OPENAI_API_KEY')),
    ('mock-sql-generator', 'mock', 'mock-sql-generator', 'sql_generation', 1, 0, NULL)
ON DUPLICATE KEY UPDATE
    adapter_key = VALUES(adapter_key);
