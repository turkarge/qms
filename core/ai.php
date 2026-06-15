<?php

function kirpi_ai_schema_registry_ready(): bool
{
    return db_table_exists('ai_schema_entities')
        && db_table_exists('ai_schema_fields');
}

function kirpi_ai_audit_table_ready(): bool
{
    return db_table_exists('ai_audit_logs');
}

function kirpi_ai_models_table_ready(): bool
{
    return db_table_exists('ai_model_adapters');
}

function kirpi_ai_schema_index_ready(): bool
{
    return db_table_exists('ai_schema_index');
}

function kirpi_ai_schema_count(): int
{
    if (!kirpi_ai_schema_registry_ready()) {
        return 0;
    }

    try {
        $stmt = db()->query('SELECT COUNT(*) FROM ai_schema_entities WHERE is_active = 1');
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('ai schema count error: ' . $e->getMessage());
        return 0;
    }
}

function kirpi_ai_field_count(): int
{
    if (!kirpi_ai_schema_registry_ready()) {
        return 0;
    }

    try {
        $stmt = db()->query('SELECT COUNT(*) FROM ai_schema_fields WHERE is_active = 1');
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('ai field count error: ' . $e->getMessage());
        return 0;
    }
}

function kirpi_ai_schema_index_count(): int
{
    if (!kirpi_ai_schema_index_ready()) {
        return 0;
    }

    try {
        $stmt = db()->query('SELECT COUNT(*) FROM ai_schema_index');
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('ai schema index count error: ' . $e->getMessage());
        return 0;
    }
}

function kirpi_ai_schema_manifest_files(): array
{
    $files = [];
    $moduleDirs = glob(BASE_PATH . '/modules/*', GLOB_ONLYDIR) ?: [];
    sort($moduleDirs);

    foreach ($moduleDirs as $moduleDir) {
        $manifestPath = rtrim($moduleDir, '/\\') . '/ai/schema.json';
        if (is_file($manifestPath)) {
            $files[] = $manifestPath;
        }
    }

    return $files;
}

function kirpi_ai_schema_manifest_count(): int
{
    return count(kirpi_ai_schema_manifest_files());
}

function kirpi_ai_normalize_schema_manifest(string $filePath): array
{
    $raw = (string) file_get_contents($filePath);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [
            'status' => 'error',
            'message' => 'invalid_json',
            'entities' => [],
        ];
    }

    $moduleKey = trim((string) ($decoded['module'] ?? basename(dirname(dirname($filePath)))));
    $entities = [];

    foreach ((array) ($decoded['entities'] ?? []) as $entity) {
        if (!is_array($entity)) {
            continue;
        }

        $entityKey = trim((string) ($entity['entity'] ?? $entity['entity_key'] ?? ''));
        $tableName = trim((string) ($entity['table'] ?? $entity['table_name'] ?? ''));
        if ($moduleKey === '' || $entityKey === '' || $tableName === '') {
            continue;
        }

        $fields = [];
        foreach ((array) ($entity['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldName = trim((string) ($field['name'] ?? $field['field_name'] ?? ''));
            if ($fieldName === '') {
                continue;
            }

            $fields[] = [
                'name' => $fieldName,
                'type' => trim((string) ($field['type'] ?? $field['field_type'] ?? '')),
                'description' => trim((string) ($field['description'] ?? '')),
                'is_sensitive' => !empty($field['is_sensitive']) ? 1 : 0,
                'is_filterable' => array_key_exists('is_filterable', $field) ? (!empty($field['is_filterable']) ? 1 : 0) : 1,
                'metadata' => is_array($field['metadata'] ?? null) ? (array) $field['metadata'] : [],
            ];
        }

        $entities[] = [
            'module' => $moduleKey,
            'entity' => $entityKey,
            'table' => $tableName,
            'description' => trim((string) ($entity['description'] ?? '')),
            'permission' => trim((string) ($entity['permission'] ?? $entity['permission_slug'] ?? '')),
            'metadata' => is_array($entity['metadata'] ?? null) ? (array) $entity['metadata'] : [],
            'fields' => $fields,
        ];
    }

    return [
        'status' => 'success',
        'message' => null,
        'entities' => $entities,
    ];
}

function kirpi_ai_publish_schema_entity(array $entity): array
{
    if (!kirpi_ai_schema_registry_ready()) {
        return [
            'status' => 'error',
            'message' => 'schema_registry_not_ready',
        ];
    }

    $moduleKey = trim((string) ($entity['module'] ?? ''));
    $entityKey = trim((string) ($entity['entity'] ?? ''));
    $tableName = trim((string) ($entity['table'] ?? ''));

    if ($moduleKey === '' || $entityKey === '' || $tableName === '') {
        return [
            'status' => 'error',
            'message' => 'invalid_entity',
        ];
    }

    $user = current_user();
    $userId = (int) ($user['id'] ?? 0);
    $metadataJson = null;
    if (!empty($entity['metadata']) && is_array($entity['metadata'])) {
        $encoded = json_encode($entity['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $metadataJson = $encoded === false ? null : $encoded;
    }

    db()->beginTransaction();

    try {
        $entityStmt = db()->prepare("
            INSERT INTO ai_schema_entities (
                module_key,
                entity_key,
                table_name,
                description,
                permission_slug,
                metadata_json,
                is_active,
                created_by,
                updated_by
            ) VALUES (
                :module_key,
                :entity_key,
                :table_name,
                :description,
                :permission_slug,
                :metadata_json,
                1,
                :created_by,
                :updated_by
            )
            ON DUPLICATE KEY UPDATE
                table_name = VALUES(table_name),
                description = VALUES(description),
                permission_slug = VALUES(permission_slug),
                metadata_json = VALUES(metadata_json),
                is_active = 1,
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ");

        $permission = trim((string) ($entity['permission'] ?? ''));
        $entityStmt->execute([
            ':module_key' => mb_substr($moduleKey, 0, 80),
            ':entity_key' => mb_substr($entityKey, 0, 120),
            ':table_name' => mb_substr($tableName, 0, 120),
            ':description' => mb_substr(trim((string) ($entity['description'] ?? '')), 0, 500) ?: null,
            ':permission_slug' => $permission !== '' ? mb_substr($permission, 0, 150) : null,
            ':metadata_json' => $metadataJson,
            ':created_by' => $userId > 0 ? $userId : null,
            ':updated_by' => $userId > 0 ? $userId : null,
        ]);

        $lookupStmt = db()->prepare("
            SELECT id
            FROM ai_schema_entities
            WHERE module_key = :module_key
              AND entity_key = :entity_key
            LIMIT 1
        ");
        $lookupStmt->execute([
            ':module_key' => $moduleKey,
            ':entity_key' => $entityKey,
        ]);
        $entityId = (int) $lookupStmt->fetchColumn();

        if ($entityId <= 0) {
            throw new RuntimeException('schema entity lookup failed');
        }

        $publishedFields = [];
        $fieldStmt = db()->prepare("
            INSERT INTO ai_schema_fields (
                entity_id,
                field_name,
                field_type,
                description,
                is_sensitive,
                is_filterable,
                is_active,
                metadata_json
            ) VALUES (
                :entity_id,
                :field_name,
                :field_type,
                :description,
                :is_sensitive,
                :is_filterable,
                1,
                :metadata_json
            )
            ON DUPLICATE KEY UPDATE
                field_type = VALUES(field_type),
                description = VALUES(description),
                is_sensitive = VALUES(is_sensitive),
                is_filterable = VALUES(is_filterable),
                is_active = 1,
                metadata_json = VALUES(metadata_json),
                updated_at = CURRENT_TIMESTAMP
        ");

        foreach ((array) ($entity['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldName = trim((string) ($field['name'] ?? ''));
            if ($fieldName === '') {
                continue;
            }

            $fieldMetadataJson = null;
            if (!empty($field['metadata']) && is_array($field['metadata'])) {
                $encoded = json_encode($field['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $fieldMetadataJson = $encoded === false ? null : $encoded;
            }

            $fieldStmt->execute([
                ':entity_id' => $entityId,
                ':field_name' => mb_substr($fieldName, 0, 120),
                ':field_type' => mb_substr(trim((string) ($field['type'] ?? '')), 0, 80) ?: null,
                ':description' => mb_substr(trim((string) ($field['description'] ?? '')), 0, 500) ?: null,
                ':is_sensitive' => !empty($field['is_sensitive']) ? 1 : 0,
                ':is_filterable' => !empty($field['is_filterable']) ? 1 : 0,
                ':metadata_json' => $fieldMetadataJson,
            ]);

            $publishedFields[] = $fieldName;
        }

        if (!empty($publishedFields)) {
            $placeholders = implode(', ', array_fill(0, count($publishedFields), '?'));
            $deactivateStmt = db()->prepare("
                UPDATE ai_schema_fields
                SET is_active = 0,
                    updated_at = CURRENT_TIMESTAMP
                WHERE entity_id = ?
                  AND field_name NOT IN ({$placeholders})
            ");
            $deactivateStmt->execute(array_merge([$entityId], $publishedFields));
        }

        db()->commit();

        return [
            'status' => 'success',
            'entity_id' => $entityId,
            'field_count' => count($publishedFields),
        ];
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        error_log('ai schema publish error: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => $e->getMessage(),
        ];
    }
}

function kirpi_ai_sync_schema_registry_from_manifests(): array
{
    if (!kirpi_ai_schema_registry_ready()) {
        return [
            'status' => 'error',
            'message' => 'schema_registry_not_ready',
            'files' => [],
            'entity_count' => 0,
            'field_count' => 0,
            'errors' => [],
        ];
    }

    $files = kirpi_ai_schema_manifest_files();
    $entityCount = 0;
    $fieldCount = 0;
    $errors = [];
    $installedFiles = [];

    foreach ($files as $filePath) {
        $relative = str_replace(BASE_PATH . '/', '', str_replace('\\', '/', $filePath));
        $manifest = kirpi_ai_normalize_schema_manifest($filePath);
        if (($manifest['status'] ?? '') !== 'success') {
            $errors[] = [
                'file' => $relative,
                'message' => (string) ($manifest['message'] ?? 'manifest_error'),
            ];
            continue;
        }

        $fileEntities = 0;
        $fileFields = 0;
        foreach ((array) ($manifest['entities'] ?? []) as $entity) {
            $result = kirpi_ai_publish_schema_entity((array) $entity);
            if (($result['status'] ?? '') !== 'success') {
                $errors[] = [
                    'file' => $relative,
                    'entity' => (string) ($entity['entity'] ?? ''),
                    'message' => (string) ($result['message'] ?? 'publish_error'),
                ];
                continue;
            }

            $fileEntities++;
            $fileFields += (int) ($result['field_count'] ?? 0);
        }

        $entityCount += $fileEntities;
        $fieldCount += $fileFields;
        $installedFiles[] = [
            'file' => $relative,
            'entities' => $fileEntities,
            'fields' => $fileFields,
        ];
    }

    $status = empty($errors) ? 'success' : 'partial';
    $indexResult = kirpi_ai_rebuild_schema_index();

    kirpi_ai_log_operation('schema_sync', $status === 'success' ? 'success' : 'failed', [
        'files' => $installedFiles,
        'errors' => $errors,
        'entity_count' => $entityCount,
        'field_count' => $fieldCount,
        'index_status' => (string) ($indexResult['status'] ?? 'skipped'),
        'index_count' => (int) ($indexResult['index_count'] ?? 0),
    ], null, 'schema_registry', null);

    return [
        'status' => $status,
        'message' => null,
        'files' => $installedFiles,
        'entity_count' => $entityCount,
        'field_count' => $fieldCount,
        'index_status' => (string) ($indexResult['status'] ?? 'skipped'),
        'index_count' => (int) ($indexResult['index_count'] ?? 0),
        'errors' => $errors,
    ];
}

function kirpi_ai_index_text_entries(string $text, string $sourceType, int $weight): array
{
    $tokens = kirpi_ai_tokenize_search($text);
    $entries = [];

    foreach ($tokens as $token) {
        $entries[] = [
            'token' => $token,
            'source_type' => $sourceType,
            'source_text' => mb_substr(trim($text), 0, 500),
            'weight' => $weight,
        ];
    }

    return $entries;
}

function kirpi_ai_index_metadata_entries(array $metadata, string $sourceType, int $weight): array
{
    $entries = [];

    foreach ((array) ($metadata['aliases'] ?? []) as $alias) {
        $entries = array_merge($entries, kirpi_ai_index_text_entries((string) $alias, $sourceType, $weight));
    }

    foreach ((array) ($metadata['keywords'] ?? []) as $keyword) {
        $entries = array_merge($entries, kirpi_ai_index_text_entries((string) $keyword, $sourceType, $weight));
    }

    return $entries;
}

function kirpi_ai_rebuild_schema_index(): array
{
    if (!kirpi_ai_schema_registry_ready()) {
        return [
            'status' => 'error',
            'message' => 'schema_registry_not_ready',
            'index_count' => 0,
        ];
    }

    if (!kirpi_ai_schema_index_ready()) {
        return [
            'status' => 'skipped',
            'message' => 'schema_index_not_ready',
            'index_count' => 0,
        ];
    }

    try {
        $stmt = db()->query("
            SELECT
                e.id AS entity_id,
                e.module_key,
                e.entity_key,
                e.table_name,
                e.description AS entity_description,
                e.permission_slug,
                e.metadata_json AS entity_metadata_json,
                f.id AS field_id,
                f.field_name,
                f.field_type,
                f.description AS field_description,
                f.is_sensitive,
                f.is_filterable,
                f.metadata_json AS field_metadata_json
            FROM ai_schema_entities e
            LEFT JOIN ai_schema_fields f ON f.entity_id = e.id AND f.is_active = 1
            WHERE e.is_active = 1
            ORDER BY e.module_key ASC, e.entity_key ASC, f.field_name ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        db()->beginTransaction();
        db()->exec('DELETE FROM ai_schema_index');

        $insert = db()->prepare("
            INSERT INTO ai_schema_index (
                entity_id,
                field_id,
                module_key,
                entity_key,
                table_name,
                field_name,
                token,
                source_type,
                source_text,
                weight
            ) VALUES (
                :entity_id,
                :field_id,
                :module_key,
                :entity_key,
                :table_name,
                :field_name,
                :token,
                :source_type,
                :source_text,
                :weight
            )
        ");

        $seen = [];
        $indexCount = 0;
        foreach ($rows as $row) {
            $entityId = (int) ($row['entity_id'] ?? 0);
            if ($entityId <= 0) {
                continue;
            }

            $fieldId = (int) ($row['field_id'] ?? 0);
            $fieldName = trim((string) ($row['field_name'] ?? ''));
            $isSensitive = (int) ($row['is_sensitive'] ?? 0) === 1;
            $entityMetadata = json_decode((string) ($row['entity_metadata_json'] ?? ''), true) ?: [];
            $fieldMetadata = json_decode((string) ($row['field_metadata_json'] ?? ''), true) ?: [];

            $entries = [];
            $entityKey = (string) ($row['entity_key'] ?? '');
            $moduleKey = (string) ($row['module_key'] ?? '');
            $tableName = (string) ($row['table_name'] ?? '');

            $entries = array_merge($entries, kirpi_ai_index_text_entries($moduleKey, 'module', 8));
            $entries = array_merge($entries, kirpi_ai_index_text_entries($entityKey, 'entity', 14));
            $entries = array_merge($entries, kirpi_ai_index_text_entries($tableName, 'table', 10));
            $entries = array_merge($entries, kirpi_ai_index_text_entries((string) ($row['entity_description'] ?? ''), 'entity_description', 6));
            $entries = array_merge($entries, kirpi_ai_index_metadata_entries($entityMetadata, 'entity_alias', 16));

            if ($fieldId > 0 && $fieldName !== '' && !$isSensitive) {
                $entries = array_merge($entries, kirpi_ai_index_text_entries($fieldName, 'field', 12));
                $entries = array_merge($entries, kirpi_ai_index_text_entries((string) ($row['field_type'] ?? ''), 'field_type', 3));
                $entries = array_merge($entries, kirpi_ai_index_text_entries((string) ($row['field_description'] ?? ''), 'field_description', 5));
                $entries = array_merge($entries, kirpi_ai_index_metadata_entries($fieldMetadata, 'field_alias', 14));
            }

            foreach ($entries as $entry) {
                $token = trim((string) ($entry['token'] ?? ''));
                $sourceType = trim((string) ($entry['source_type'] ?? ''));
                if ($token === '' || $sourceType === '') {
                    continue;
                }

                $dedupeKey = implode('|', [
                    $entityId,
                    $fieldId > 0 && !$isSensitive ? $fieldId : 0,
                    $token,
                    $sourceType,
                ]);
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;

                $insert->execute([
                    ':entity_id' => $entityId,
                    ':field_id' => $fieldId > 0 && !$isSensitive ? $fieldId : null,
                    ':module_key' => mb_substr($moduleKey, 0, 80),
                    ':entity_key' => mb_substr($entityKey, 0, 120),
                    ':table_name' => mb_substr($tableName, 0, 120),
                    ':field_name' => $fieldId > 0 && !$isSensitive ? mb_substr($fieldName, 0, 120) : null,
                    ':token' => mb_substr($token, 0, 120),
                    ':source_type' => mb_substr($sourceType, 0, 40),
                    ':source_text' => mb_substr((string) ($entry['source_text'] ?? ''), 0, 500) ?: null,
                    ':weight' => max(1, min(65535, (int) ($entry['weight'] ?? 1))),
                ]);
                $indexCount++;
            }
        }

        db()->commit();

        kirpi_ai_log_operation('schema_index_rebuild', 'success', [
            'index_count' => $indexCount,
        ], null, 'schema_registry', null);

        return [
            'status' => 'success',
            'message' => null,
            'index_count' => $indexCount,
        ];
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        error_log('ai schema index rebuild error: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => $e->getMessage(),
            'index_count' => 0,
        ];
    }
}

function kirpi_ai_audit_count(): int
{
    if (!kirpi_ai_audit_table_ready()) {
        return 0;
    }

    try {
        $stmt = db()->query('SELECT COUNT(*) FROM ai_audit_logs');
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('ai audit count error: ' . $e->getMessage());
        return 0;
    }
}

function kirpi_ai_list_audit_logs(array $filters = [], int $page = 1, int $limit = 25): array
{
    if (!kirpi_ai_audit_table_ready()) {
        return [
            'records' => [],
            'total' => 0,
            'page' => 1,
            'limit' => $limit,
            'total_pages' => 0,
        ];
    }

    $page = max(1, $page);
    $limit = max(1, min(100, $limit));
    $offset = ($page - 1) * $limit;
    $where = [];
    $params = [];

    $status = trim((string) ($filters['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'l.status = :status';
        $params[':status'] = $status;
    }

    $action = trim((string) ($filters['action'] ?? ''));
    if ($action !== '') {
        $where[] = 'l.action_key LIKE :action_key';
        $params[':action_key'] = '%' . $action . '%';
    }

    $modelAdapter = trim((string) ($filters['model_adapter'] ?? ''));
    if ($modelAdapter !== '') {
        $where[] = 'l.model_adapter LIKE :model_adapter';
        $params[':model_adapter'] = '%' . $modelAdapter . '%';
    }

    $entityType = trim((string) ($filters['entity_type'] ?? ''));
    if ($entityType !== '') {
        $where[] = 'l.entity_type LIKE :entity_type';
        $params[':entity_type'] = '%' . $entityType . '%';
    }

    $userId = (int) ($filters['user_id'] ?? 0);
    if ($userId > 0) {
        $where[] = 'l.user_id = :user_id';
        $params[':user_id'] = $userId;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    try {
        $countStmt = db()->prepare("
            SELECT COUNT(l.id)
            FROM ai_audit_logs l
            {$whereSql}
        ");
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = db()->prepare("
            SELECT
                l.id,
                l.user_id,
                l.action_key,
                l.status,
                l.model_adapter,
                l.entity_type,
                l.entity_id,
                l.route_path,
                l.ip_address,
                l.details_json,
                l.created_at,
                u.name AS user_name
            FROM ai_audit_logs l
            LEFT JOIN users u ON u.id = l.user_id
            {$whereSql}
            ORDER BY l.id DESC
            LIMIT :limit_rows OFFSET :offset_rows
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset_rows', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'records' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) ceil($total / $limit),
        ];
    } catch (Throwable $e) {
        error_log('ai audit list error: ' . $e->getMessage());

        return [
            'records' => [],
            'total' => 0,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => 0,
        ];
    }
}

function kirpi_ai_model_adapters(): array
{
    if (!kirpi_ai_models_table_ready()) {
        return [];
    }

    try {
        $stmt = db()->query("
            SELECT adapter_key, provider, model_name, adapter_type, is_enabled, is_external
            FROM ai_model_adapters
            ORDER BY is_enabled DESC, adapter_key ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('ai model adapter list error: ' . $e->getMessage());
        return [];
    }
}

function kirpi_ai_sql_generation_adapters(bool $enabledOnly = true): array
{
    $adapters = [];
    foreach (kirpi_ai_model_adapters_with_config() as $adapter) {
        if ((string) ($adapter['adapter_type'] ?? '') !== 'sql_generation') {
            continue;
        }

        if ($enabledOnly && empty($adapter['is_enabled'])) {
            continue;
        }

        $adapters[] = $adapter;
    }

    return $adapters;
}

function kirpi_ai_model_adapters_with_config(): array
{
    if (!kirpi_ai_models_table_ready()) {
        return [];
    }

    try {
        $stmt = db()->query("
            SELECT adapter_key, provider, model_name, adapter_type, is_enabled, is_external, config_json, updated_at
            FROM ai_model_adapters
            ORDER BY is_enabled DESC, adapter_type ASC, adapter_key ASC
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $config = [];
            if (!empty($row['config_json'])) {
                $decoded = json_decode((string) $row['config_json'], true);
                if (is_array($decoded)) {
                    $config = $decoded;
                }
            }
            $row['config'] = $config;
            $row['secret_configured'] = kirpi_ai_adapter_secret_configured($row);
        }
        unset($row);

        return $rows;
    } catch (Throwable $e) {
        error_log('ai model adapter config list error: ' . $e->getMessage());
        return [];
    }
}

function kirpi_ai_model_adapter(string $adapterKey): ?array
{
    if (!kirpi_ai_models_table_ready()) {
        return null;
    }

    $adapterKey = trim($adapterKey);
    if ($adapterKey === '') {
        return null;
    }

    try {
        $stmt = db()->prepare("
            SELECT adapter_key, provider, model_name, adapter_type, is_enabled, is_external, config_json
            FROM ai_model_adapters
            WHERE adapter_key = :adapter_key
            LIMIT 1
        ");
        $stmt->execute([
            ':adapter_key' => $adapterKey,
        ]);

        $adapter = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$adapter) {
            return null;
        }

        $config = [];
        if (!empty($adapter['config_json'])) {
            $decoded = json_decode((string) $adapter['config_json'], true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }

        $adapter['config'] = $config;

        return $adapter;
    } catch (Throwable $e) {
        error_log('ai model adapter read error: ' . $e->getMessage());
        return null;
    }
}

function kirpi_ai_setting_secret_key(string $adapterKey): string
{
    $normalized = strtolower(trim($adapterKey));
    $normalized = preg_replace('/[^a-z0-9_.-]+/', '.', $normalized) ?: 'default';
    $normalized = trim($normalized, '.');

    return 'ai.adapter.' . $normalized . '.api_key';
}

function kirpi_ai_provider_secret_key(string $provider): string
{
    $normalized = strtolower(trim($provider));
    $normalized = preg_replace('/[^a-z0-9_.-]+/', '.', $normalized) ?: 'default';
    $normalized = trim($normalized, '.');

    return 'ai.provider.' . $normalized . '.api_key';
}

function kirpi_ai_update_model_adapter(string $adapterKey, array $input, ?int $updatedBy = null): array
{
    $adapter = kirpi_ai_model_adapter($adapterKey);
    if ($adapter === null) {
        return [
            'success' => false,
            'message' => 'adapter_not_found',
        ];
    }

    $provider = strtolower(trim((string) ($input['provider'] ?? $adapter['provider'] ?? '')));
    $modelName = trim((string) ($input['model_name'] ?? $adapter['model_name'] ?? ''));
    $adapterType = strtolower(trim((string) ($input['adapter_type'] ?? $adapter['adapter_type'] ?? 'sql_generation')));
    $baseUrl = rtrim(trim((string) ($input['base_url'] ?? '')), '/');
    $secretSource = strtolower(trim((string) ($input['secret_source'] ?? 'setting')));
    $apiKeyEnv = trim((string) ($input['api_key_env'] ?? ''));
    $apiKeyRef = trim((string) ($input['api_key_ref'] ?? ''));
    $apiKeyValue = trim((string) ($input['api_key_value'] ?? ''));
    $isEnabled = !empty($input['is_enabled']) ? 1 : 0;
    $runtimeEnabled = !empty($input['runtime_enabled']);
    $structuredOutput = !empty($input['structured_output']);
    $isExternal = $provider === 'mock' ? 0 : 1;

    if (!in_array($provider, ['mock', 'openai', 'openai_compatible'], true)) {
        return [
            'success' => false,
            'message' => 'provider_invalid',
        ];
    }

    if ($modelName === '') {
        return [
            'success' => false,
            'message' => 'model_missing',
        ];
    }

    if (!in_array($adapterType, ['chat', 'sql_generation'], true)) {
        return [
            'success' => false,
            'message' => 'adapter_type_invalid',
        ];
    }

    if (!in_array($secretSource, ['setting', 'env'], true)) {
        $secretSource = 'setting';
    }

    $timeout = max(5, min(120, (int) ($input['timeout_seconds'] ?? 30)));
    $temperature = max(0, min(1, (float) ($input['temperature'] ?? 0)));
    $maxTokens = max(128, min(4096, (int) ($input['max_tokens'] ?? 700)));
    $config = [
        'runtime_enabled' => $runtimeEnabled,
        'timeout_seconds' => $timeout,
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        'structured_output' => $structuredOutput,
    ];

    if ($provider === 'openai_compatible') {
        if ($baseUrl === '') {
            return [
                'success' => false,
                'message' => 'base_url_missing',
            ];
        }
        $config['base_url'] = $baseUrl;
    } elseif ($provider === 'openai' && $baseUrl !== '') {
        $config['base_url'] = $baseUrl;
    }

    if ($provider !== 'mock') {
        $config['model'] = $modelName;
        if ($secretSource === 'env') {
            if ($apiKeyEnv === '') {
                return [
                    'success' => false,
                    'message' => 'api_key_env_missing',
                ];
            }
            $config['api_key_env'] = $apiKeyEnv;
        } else {
            $apiKeyRef = $apiKeyRef !== '' ? $apiKeyRef : kirpi_ai_setting_secret_key($adapterKey);
            $config['api_key_ref'] = $apiKeyRef;
            if ($apiKeyValue !== '') {
                if (!kirpi_setting_set($apiKeyRef, $apiKeyValue, $updatedBy, true)) {
                    return [
                        'success' => false,
                        'message' => 'api_key_save_failed',
                    ];
                }

                $providerSecretRef = kirpi_ai_provider_secret_key($provider);
                if ($providerSecretRef !== $apiKeyRef) {
                    kirpi_setting_set($providerSecretRef, $apiKeyValue, $updatedBy, true);
                }
            }
        }
    } else {
        $config = [];
        $isEnabled = 1;
        $isExternal = 0;
    }

    $configJson = empty($config) ? null : json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($configJson === false) {
        return [
            'success' => false,
            'message' => 'config_encode_failed',
        ];
    }

    try {
        $stmt = db()->prepare("
            UPDATE ai_model_adapters
            SET
                provider = :provider,
                model_name = :model_name,
                adapter_type = :adapter_type,
                is_enabled = :is_enabled,
                is_external = :is_external,
                config_json = :config_json
            WHERE adapter_key = :adapter_key
            LIMIT 1
        ");
        $stmt->execute([
            ':provider' => $provider,
            ':model_name' => $modelName,
            ':adapter_type' => $adapterType,
            ':is_enabled' => $isEnabled,
            ':is_external' => $isExternal,
            ':config_json' => $configJson,
            ':adapter_key' => $adapterKey,
        ]);

        return [
            'success' => true,
            'changed_keys' => [
                'provider',
                'model_name',
                'adapter_type',
                'is_enabled',
                'is_external',
                'config_json',
                $apiKeyValue !== '' && isset($config['api_key_ref']) ? 'api_key_ref_secret' : null,
            ],
        ];
    } catch (Throwable $e) {
        error_log('ai model adapter update error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'adapter_update_failed',
        ];
    }
}

function kirpi_ai_list_schema_entities(int $limit = 50): array
{
    if (!kirpi_ai_schema_registry_ready()) {
        return [];
    }

    $limit = max(1, min(200, $limit));

    try {
        $stmt = db()->prepare("
            SELECT
                e.id,
                e.module_key,
                e.entity_key,
                e.table_name,
                e.description,
                e.permission_slug,
                e.is_active,
                COUNT(f.id) AS field_count,
                e.updated_at
            FROM ai_schema_entities e
            LEFT JOIN ai_schema_fields f ON f.entity_id = e.id AND f.is_active = 1
            GROUP BY e.id
            ORDER BY e.module_key ASC, e.entity_key ASC
            LIMIT :limit_rows
        ");
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('ai schema entity list error: ' . $e->getMessage());
        return [];
    }
}

function kirpi_ai_schema_filter_options(): array
{
    if (!kirpi_ai_schema_registry_ready()) {
        return [
            'modules' => [],
            'entities' => [],
            'tables' => [],
            'permissions' => [],
        ];
    }

    try {
        $readDistinct = static function (string $column): array {
            $allowed = ['module_key', 'entity_key', 'table_name', 'permission_slug'];
            if (!in_array($column, $allowed, true)) {
                return [];
            }

            $stmt = db()->query("
                SELECT DISTINCT {$column} AS value
                FROM ai_schema_entities
                WHERE is_active = 1
                  AND {$column} IS NOT NULL
                  AND {$column} <> ''
                ORDER BY {$column} ASC
            ");

            return array_values(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
        };

        return [
            'modules' => $readDistinct('module_key'),
            'entities' => $readDistinct('entity_key'),
            'tables' => $readDistinct('table_name'),
            'permissions' => $readDistinct('permission_slug'),
        ];
    } catch (Throwable $e) {
        error_log('ai schema filter options error: ' . $e->getMessage());

        return [
            'modules' => [],
            'entities' => [],
            'tables' => [],
            'permissions' => [],
        ];
    }
}

function kirpi_ai_latest_schema_sync(): ?array
{
    if (!kirpi_ai_audit_table_ready()) {
        return null;
    }

    try {
        $stmt = db()->prepare("
            SELECT action_key, status, details_json, created_at
            FROM ai_audit_logs
            WHERE action_key = :action_key
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([':action_key' => 'schema_sync']);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            return null;
        }

        $details = json_decode((string) ($record['details_json'] ?? ''), true);
        $record['details'] = is_array($details) ? $details : [];

        return $record;
    } catch (Throwable $e) {
        error_log('ai latest schema sync error: ' . $e->getMessage());
        return null;
    }
}

function kirpi_ai_schema_quality_report(int $limit = 100): array
{
    $limit = max(1, min(500, $limit));

    if (!kirpi_ai_schema_registry_ready()) {
        return [
            'status' => 'error',
            'message' => 'schema_registry_not_ready',
            'warnings' => [],
            'meta' => [
                'warning_count' => 0,
                'error_count' => 0,
                'by_module' => [],
            ],
        ];
    }

    try {
        $stmt = db()->query("
            SELECT
                e.id AS entity_id,
                e.module_key,
                e.entity_key,
                e.table_name,
                e.description AS entity_description,
                e.permission_slug,
                f.field_name,
                f.field_type,
                f.description AS field_description,
                f.is_sensitive,
                f.is_filterable
            FROM ai_schema_entities e
            LEFT JOIN ai_schema_fields f ON f.entity_id = e.id AND f.is_active = 1
            WHERE e.is_active = 1
            ORDER BY e.module_key ASC, e.entity_key ASC, f.field_name ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('ai schema quality error: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => 'schema_quality_failed',
            'warnings' => [],
            'meta' => [
                'warning_count' => 0,
                'error_count' => 0,
                'by_module' => [],
            ],
        ];
    }

    $entities = [];
    foreach ($rows as $row) {
        $entityId = (int) ($row['entity_id'] ?? 0);
        if ($entityId <= 0) {
            continue;
        }

        if (!isset($entities[$entityId])) {
            $entities[$entityId] = [
                'module' => (string) ($row['module_key'] ?? ''),
                'entity' => (string) ($row['entity_key'] ?? ''),
                'table' => (string) ($row['table_name'] ?? ''),
                'description' => trim((string) ($row['entity_description'] ?? '')),
                'permission' => trim((string) ($row['permission_slug'] ?? '')),
                'fields' => [],
            ];
        }

        $fieldName = trim((string) ($row['field_name'] ?? ''));
        if ($fieldName === '') {
            continue;
        }

        $entities[$entityId]['fields'][] = [
            'name' => $fieldName,
            'type' => trim((string) ($row['field_type'] ?? '')),
            'description' => trim((string) ($row['field_description'] ?? '')),
            'is_sensitive' => (int) ($row['is_sensitive'] ?? 0) === 1,
            'is_filterable' => (int) ($row['is_filterable'] ?? 0) === 1,
        ];
    }

    $warnings = [];
    $byModule = [];
    $errorCount = 0;
    $sensitiveFieldPatterns = [
        '/(^|_)password($|_)/',
        '/(^|_)passwd($|_)/',
        '/(^|_)token_hash($|_)/',
        '/(^|_)(access|refresh|secret|private|api)_token($|_)/',
        '/(^|_)(secret|private|api|access)_key($|_)/',
        '/(^|_)secret_value($|_)/',
        '/(^|_)email$/',
        '/_email$/',
        '/(^|_)email_address$/',
        '/(^|_)ip_address($|_)/',
        '/(^|_)(file|storage|absolute)_path($|_)/',
        '/(^|_)(payload|details|data)_json($|_)/',
        '/(^|_)(request|response|html)?_?body($|_)/',
        '/(^|_)user_agent($|_)/',
        '/(^|_)(password|token|secret)_hash($|_)/',
    ];

    $addWarning = static function (
        string $severity,
        string $code,
        array $entity,
        ?array $field,
        string $message
    ) use (&$warnings, &$byModule, &$errorCount): void {
        $module = (string) ($entity['module'] ?? '');
        $warnings[] = [
            'severity' => $severity,
            'code' => $code,
            'module' => $module,
            'entity' => (string) ($entity['entity'] ?? ''),
            'table' => (string) ($entity['table'] ?? ''),
            'field' => $field !== null ? (string) ($field['name'] ?? '') : null,
            'message' => $message,
        ];

        if (!isset($byModule[$module])) {
            $byModule[$module] = [
                'warning_count' => 0,
                'error_count' => 0,
            ];
        }

        $byModule[$module]['warning_count']++;
        if ($severity === 'error') {
            $byModule[$module]['error_count']++;
            $errorCount++;
        }
    };

    foreach ($entities as $entity) {
        if ((string) ($entity['description'] ?? '') === '') {
            $addWarning('warning', 'missing_entity_description', $entity, null, 'Entity description is missing.');
        }

        if ((string) ($entity['permission'] ?? '') === '') {
            $addWarning('error', 'missing_permission', $entity, null, 'Permission slug is missing.');
        }

        $fields = (array) ($entity['fields'] ?? []);
        if (empty($fields)) {
            $addWarning('error', 'missing_fields', $entity, null, 'Entity has no active fields.');
            continue;
        }

        foreach ($fields as $field) {
            if ((string) ($field['description'] ?? '') === '') {
                $addWarning('warning', 'missing_field_description', $entity, $field, 'Field description is missing.');
            }

            if ((string) ($field['type'] ?? '') === '') {
                $addWarning('warning', 'missing_field_type', $entity, $field, 'Field type is missing.');
            }

            $fieldName = mb_strtolower((string) ($field['name'] ?? ''));
            if (empty($field['is_sensitive'])) {
                foreach ($sensitiveFieldPatterns as $pattern) {
                    if (preg_match($pattern, $fieldName) === 1) {
                        $addWarning('warning', 'possible_sensitive_field', $entity, $field, 'Field name suggests sensitive data but is_sensitive is not set.');
                        break;
                    }
                }
            }
        }
    }

    ksort($byModule);

    return [
        'status' => 'success',
        'message' => null,
        'warnings' => array_slice($warnings, 0, $limit),
        'meta' => [
            'warning_count' => count($warnings),
            'error_count' => $errorCount,
            'by_module' => $byModule,
            'limit' => $limit,
        ],
    ];
}

function kirpi_ai_user_has_permission(?array $user, ?string $permissionSlug): bool
{
    $permissionSlug = trim((string) $permissionSlug);
    if ($permissionSlug === '') {
        return true;
    }

    if (!$user) {
        return false;
    }

    if (($user['role_name'] ?? null) === 'Super Admin') {
        return true;
    }

    return in_array($permissionSlug, array_map('strval', (array) ($user['permissions'] ?? [])), true);
}

function kirpi_ai_discover_schema(array $options = [], ?array $user = null): array
{
    if (!kirpi_ai_schema_registry_ready()) {
        return [
            'status' => 'error',
            'message' => 'schema_registry_not_ready',
            'entities' => [],
            'meta' => [
                'entity_count' => 0,
                'field_count' => 0,
                'sensitive_field_count' => 0,
            ],
        ];
    }

    $user = $user ?? current_user();
    $includeSensitive = !empty($options['include_sensitive']) && kirpi_ai_user_has_permission($user, 'ai.schema.manage');
    $filterableOnly = !empty($options['filterable_only']);
    $search = mb_strtolower(trim((string) ($options['search'] ?? '')));
    $moduleFilter = trim((string) ($options['module'] ?? ''));
    $entityFilter = trim((string) ($options['entity'] ?? ''));
    $tableFilter = trim((string) ($options['table'] ?? ''));
    $permissionFilter = trim((string) ($options['permission'] ?? ''));
    $limit = max(1, min(200, (int) ($options['limit'] ?? 50)));

    try {
        $stmt = db()->prepare("
            SELECT
                e.id AS entity_id,
                e.module_key,
                e.entity_key,
                e.table_name,
                e.description AS entity_description,
                e.permission_slug,
                e.metadata_json AS entity_metadata_json,
                f.field_name,
                f.field_type,
                f.description AS field_description,
                f.is_sensitive,
                f.is_filterable,
                f.metadata_json AS field_metadata_json
            FROM ai_schema_entities e
            LEFT JOIN ai_schema_fields f ON f.entity_id = e.id AND f.is_active = 1
            WHERE e.is_active = 1
            ORDER BY e.module_key ASC, e.entity_key ASC, f.field_name ASC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('ai schema discovery error: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => 'schema_discovery_failed',
            'entities' => [],
            'meta' => [
                'entity_count' => 0,
                'field_count' => 0,
                'sensitive_field_count' => 0,
            ],
        ];
    }

    $entities = [];
    $sensitiveFieldCount = 0;

    foreach ($rows as $row) {
        $permissionSlug = trim((string) ($row['permission_slug'] ?? ''));
        if (!kirpi_ai_user_has_permission($user, $permissionSlug)) {
            continue;
        }

        if ($moduleFilter !== '' && (string) ($row['module_key'] ?? '') !== $moduleFilter) {
            continue;
        }

        if ($entityFilter !== '' && (string) ($row['entity_key'] ?? '') !== $entityFilter) {
            continue;
        }

        if ($tableFilter !== '' && (string) ($row['table_name'] ?? '') !== $tableFilter) {
            continue;
        }

        if ($permissionFilter !== '' && $permissionSlug !== $permissionFilter) {
            continue;
        }

        $haystack = mb_strtolower(implode(' ', [
            (string) ($row['module_key'] ?? ''),
            (string) ($row['entity_key'] ?? ''),
            (string) ($row['table_name'] ?? ''),
            (string) ($row['entity_description'] ?? ''),
            (string) ($row['entity_metadata_json'] ?? ''),
            (string) ($row['field_name'] ?? ''),
            (string) ($row['field_description'] ?? ''),
            (string) ($row['field_metadata_json'] ?? ''),
        ]));

        if ($search !== '' && !str_contains($haystack, $search)) {
            continue;
        }

        $entityId = (int) ($row['entity_id'] ?? 0);
        if ($entityId <= 0) {
            continue;
        }

        if (!isset($entities[$entityId])) {
            if (count($entities) >= $limit) {
                continue;
            }

            $entities[$entityId] = [
                'id' => $entityId,
                'module' => (string) ($row['module_key'] ?? ''),
                'entity' => (string) ($row['entity_key'] ?? ''),
                'table' => (string) ($row['table_name'] ?? ''),
                'description' => (string) ($row['entity_description'] ?? ''),
                'permission' => $permissionSlug !== '' ? $permissionSlug : null,
                'metadata' => json_decode((string) ($row['entity_metadata_json'] ?? ''), true) ?: [],
                'fields' => [],
            ];
        }

        $fieldName = trim((string) ($row['field_name'] ?? ''));
        if ($fieldName === '') {
            continue;
        }

        $isSensitive = (int) ($row['is_sensitive'] ?? 0) === 1;
        $isFilterable = (int) ($row['is_filterable'] ?? 0) === 1;

        if ($isSensitive) {
            $sensitiveFieldCount++;
        }

        if ($isSensitive && !$includeSensitive) {
            continue;
        }

        if ($filterableOnly && !$isFilterable) {
            continue;
        }

        $entities[$entityId]['fields'][] = [
            'name' => $fieldName,
            'type' => (string) ($row['field_type'] ?? ''),
            'description' => (string) ($row['field_description'] ?? ''),
            'is_sensitive' => $isSensitive,
            'is_filterable' => $isFilterable,
            'metadata' => json_decode((string) ($row['field_metadata_json'] ?? ''), true) ?: [],
        ];
    }

    $entityList = array_values($entities);
    $fieldCount = 0;
    foreach ($entityList as $entity) {
        $fieldCount += count((array) ($entity['fields'] ?? []));
    }

    kirpi_ai_log_operation('schema_discovery', 'success', [
        'entity_count' => count($entityList),
        'field_count' => $fieldCount,
        'include_sensitive' => $includeSensitive,
        'filterable_only' => $filterableOnly,
        'search' => $search,
    ], null, 'schema_registry', null);

    return [
        'status' => 'success',
        'message' => null,
        'entities' => $entityList,
        'meta' => [
            'entity_count' => count($entityList),
            'field_count' => $fieldCount,
            'sensitive_field_count' => $sensitiveFieldCount,
            'include_sensitive' => $includeSensitive,
            'filterable_only' => $filterableOnly,
            'search' => $search,
            'module' => $moduleFilter,
            'entity' => $entityFilter,
            'table' => $tableFilter,
            'permission' => $permissionFilter,
        ],
    ];
}

function kirpi_ai_tokenize_search(string $query): array
{
    $query = mb_strtolower(trim($query));
    if ($query === '') {
        return [];
    }

    $parts = preg_split('/[^\p{L}\p{N}_]+/u', $query) ?: [];
    $tokens = [];

    foreach ($parts as $part) {
        $part = trim((string) $part);
        if (mb_strlen($part) < 2) {
            continue;
        }

        $tokens[] = $part;
    }

    return array_values(array_unique($tokens));
}

function kirpi_ai_text_score(string $text, array $tokens, int $exactWeight = 6, int $containsWeight = 2): int
{
    $text = mb_strtolower($text);
    if ($text === '' || empty($tokens)) {
        return 0;
    }

    $score = 0;
    foreach ($tokens as $token) {
        if ($text === $token) {
            $score += $exactWeight;
            continue;
        }

        if (str_contains($text, $token)) {
            $score += $containsWeight;
        }
    }

    return $score;
}

function kirpi_ai_search_schema_index(array $tokens, int $limit, ?array $user = null): ?array
{
    if (!kirpi_ai_schema_index_ready() || kirpi_ai_schema_index_count() <= 0) {
        return null;
    }

    $user = $user ?? current_user();

    try {
        $stmt = db()->query("
            SELECT
                i.entity_id,
                i.field_id,
                i.module_key,
                i.entity_key,
                i.table_name,
                i.field_name,
                i.token,
                i.source_type,
                i.source_text,
                i.weight,
                e.description AS entity_description,
                e.permission_slug,
                f.field_type,
                f.description AS field_description
            FROM ai_schema_index i
            INNER JOIN ai_schema_entities e ON e.id = i.entity_id AND e.is_active = 1
            LEFT JOIN ai_schema_fields f ON f.id = i.field_id AND f.is_active = 1
            ORDER BY i.module_key ASC, i.entity_key ASC, i.field_name ASC, i.weight DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('ai schema index search error: ' . $e->getMessage());
        return null;
    }

    $results = [];
    foreach ($rows as $row) {
        $permissionSlug = trim((string) ($row['permission_slug'] ?? ''));
        if (!kirpi_ai_user_has_permission($user, $permissionSlug)) {
            continue;
        }

        $indexToken = mb_strtolower(trim((string) ($row['token'] ?? '')));
        if ($indexToken === '') {
            continue;
        }

        $matchedTokens = [];
        $score = 0;
        foreach ($tokens as $queryToken) {
            $queryToken = mb_strtolower((string) $queryToken);
            if ($queryToken === '') {
                continue;
            }

            if ($indexToken === $queryToken) {
                $score += (int) ($row['weight'] ?? 1) * 3;
                $matchedTokens[] = $queryToken;
                continue;
            }

            if (str_contains($indexToken, $queryToken) || str_contains($queryToken, $indexToken)) {
                $score += (int) ($row['weight'] ?? 1);
                $matchedTokens[] = $queryToken;
            }
        }

        if ($score <= 0) {
            continue;
        }

        $entityId = (int) ($row['entity_id'] ?? 0);
        if ($entityId <= 0) {
            continue;
        }

        if (!isset($results[$entityId])) {
            $results[$entityId] = [
                'score' => 0,
                'module' => (string) ($row['module_key'] ?? ''),
                'entity' => (string) ($row['entity_key'] ?? ''),
                'table' => (string) ($row['table_name'] ?? ''),
                'description' => (string) ($row['entity_description'] ?? ''),
                'permission' => $permissionSlug !== '' ? $permissionSlug : null,
                'matched_fields' => [],
                'matched_terms' => [],
                'matched_sources' => [],
            ];
        }

        $results[$entityId]['score'] += $score;
        foreach ($matchedTokens as $matchedToken) {
            $results[$entityId]['matched_terms'][$matchedToken] = true;
        }

        $sourceType = (string) ($row['source_type'] ?? '');
        $sourceText = (string) ($row['source_text'] ?? '');
        if ($sourceType !== '') {
            $results[$entityId]['matched_sources'][] = [
                'type' => $sourceType,
                'text' => $sourceText,
                'token' => $indexToken,
                'score' => $score,
            ];
        }

        $fieldName = trim((string) ($row['field_name'] ?? ''));
        if ($fieldName !== '') {
            if (!isset($results[$entityId]['matched_fields'][$fieldName])) {
                $results[$entityId]['matched_fields'][$fieldName] = [
                    'name' => $fieldName,
                    'type' => (string) ($row['field_type'] ?? ''),
                    'description' => (string) ($row['field_description'] ?? ''),
                    'score' => 0,
                    'matched_terms' => [],
                ];
            }

            $results[$entityId]['matched_fields'][$fieldName]['score'] += $score;
            foreach ($matchedTokens as $matchedToken) {
                $results[$entityId]['matched_fields'][$fieldName]['matched_terms'][$matchedToken] = true;
            }
        }
    }

    $resultList = array_values($results);
    foreach ($resultList as &$result) {
        $fields = array_values((array) ($result['matched_fields'] ?? []));
        foreach ($fields as &$field) {
            $field['matched_terms'] = array_keys((array) ($field['matched_terms'] ?? []));
        }
        unset($field);

        usort($fields, static function (array $a, array $b): int {
            return ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
        });

        $sources = (array) ($result['matched_sources'] ?? []);
        usort($sources, static function (array $a, array $b): int {
            return ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
        });

        $result['matched_fields'] = $fields;
        $result['matched_terms'] = array_keys((array) ($result['matched_terms'] ?? []));
        $result['matched_sources'] = array_slice($sources, 0, 5);
    }
    unset($result);

    usort($resultList, static function (array $a, array $b): int {
        $scoreCompare = ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }

        return strcmp((string) ($a['entity'] ?? ''), (string) ($b['entity'] ?? ''));
    });

    return array_slice($resultList, 0, $limit);
}

function kirpi_ai_search_schema(string $query, array $options = [], ?array $user = null): array
{
    $tokens = kirpi_ai_tokenize_search($query);
    $limit = max(1, min(50, (int) ($options['limit'] ?? 10)));

    if (empty($tokens)) {
        return [
            'status' => 'success',
            'query' => trim($query),
            'tokens' => [],
            'results' => [],
            'meta' => [
                'result_count' => 0,
            ],
        ];
    }

    $indexedResults = kirpi_ai_search_schema_index($tokens, $limit, $user);
    if (is_array($indexedResults)) {
        kirpi_ai_log_operation('schema_search', 'success', [
            'query' => trim($query),
            'tokens' => $tokens,
            'result_count' => count($indexedResults),
            'mode' => 'metadata_index',
        ], null, 'schema_registry', null);

        return [
            'status' => 'success',
            'query' => trim($query),
            'tokens' => $tokens,
            'results' => $indexedResults,
            'meta' => [
                'result_count' => count($indexedResults),
                'mode' => 'metadata_index',
                'index_count' => kirpi_ai_schema_index_count(),
            ],
        ];
    }

    $discovery = kirpi_ai_discover_schema([
        'include_sensitive' => false,
        'filterable_only' => !empty($options['filterable_only']),
        'limit' => 200,
    ], $user);

    if (($discovery['status'] ?? '') !== 'success') {
        return [
            'status' => 'error',
            'query' => trim($query),
            'tokens' => $tokens,
            'results' => [],
            'meta' => [
                'result_count' => 0,
            ],
        ];
    }

    $results = [];
    foreach ((array) ($discovery['entities'] ?? []) as $entity) {
        $entityScore = 0;
        $entityScore += kirpi_ai_text_score((string) ($entity['module'] ?? ''), $tokens, 8, 3);
        $entityScore += kirpi_ai_text_score((string) ($entity['entity'] ?? ''), $tokens, 10, 4);
        $entityScore += kirpi_ai_text_score((string) ($entity['table'] ?? ''), $tokens, 8, 3);
        $entityScore += kirpi_ai_text_score((string) ($entity['description'] ?? ''), $tokens, 4, 1);
        $entityScore += kirpi_ai_text_score(json_encode($entity['metadata'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', $tokens, 5, 2);

        $matchedFields = [];
        foreach ((array) ($entity['fields'] ?? []) as $field) {
            $fieldScore = 0;
            $fieldScore += kirpi_ai_text_score((string) ($field['name'] ?? ''), $tokens, 8, 3);
            $fieldScore += kirpi_ai_text_score((string) ($field['type'] ?? ''), $tokens, 3, 1);
            $fieldScore += kirpi_ai_text_score((string) ($field['description'] ?? ''), $tokens, 4, 1);
            $fieldScore += kirpi_ai_text_score(json_encode($field['metadata'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', $tokens, 5, 2);

            if ($fieldScore <= 0) {
                continue;
            }

            $matchedFields[] = [
                'name' => (string) ($field['name'] ?? ''),
                'type' => (string) ($field['type'] ?? ''),
                'description' => (string) ($field['description'] ?? ''),
                'score' => $fieldScore,
            ];
        }

        $score = $entityScore;
        foreach ($matchedFields as $field) {
            $score += (int) ($field['score'] ?? 0);
        }

        if ($score <= 0) {
            continue;
        }

        usort($matchedFields, static function (array $a, array $b): int {
            return ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
        });

        $results[] = [
            'score' => $score,
            'module' => (string) ($entity['module'] ?? ''),
            'entity' => (string) ($entity['entity'] ?? ''),
            'table' => (string) ($entity['table'] ?? ''),
            'description' => (string) ($entity['description'] ?? ''),
            'permission' => $entity['permission'] ?? null,
            'matched_fields' => $matchedFields,
        ];
    }

    usort($results, static function (array $a, array $b): int {
        $scoreCompare = ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }

        return strcmp((string) ($a['entity'] ?? ''), (string) ($b['entity'] ?? ''));
    });

    $results = array_slice($results, 0, $limit);

    kirpi_ai_log_operation('schema_search', 'success', [
        'query' => trim($query),
        'tokens' => $tokens,
        'result_count' => count($results),
        'mode' => 'discovery_fallback',
    ], null, 'schema_registry', null);

    return [
        'status' => 'success',
        'query' => trim($query),
        'tokens' => $tokens,
        'results' => $results,
        'meta' => [
            'result_count' => count($results),
            'mode' => 'discovery_fallback',
        ],
    ];
}

function kirpi_ai_build_query_plan(string $question, array $options = [], ?array $user = null): array
{
    $question = trim($question);
    $limit = max(1, min(20, (int) ($options['limit'] ?? 5)));
    $tokens = kirpi_ai_tokenize_search($question);
    $safetyNotes = [
        'Bu aşama SQL üretmez ve veri okumaz.',
        'Adaylar RBAC ve hassas alan kurallarıyla sınırlıdır.',
        'SQL aşamasına geçmeden önce Read-only SQL Guard zorunludur.',
    ];

    if ($question === '' || empty($tokens)) {
        return [
            'status' => 'success',
            'question' => $question,
            'tokens' => [],
            'search_mode' => null,
            'index_count' => kirpi_ai_schema_index_count(),
            'candidate_count' => 0,
            'primary_candidate' => null,
            'candidates' => [],
            'safety_notes' => $safetyNotes,
            'meta' => [
                'message' => 'empty_question',
            ],
        ];
    }

    $search = kirpi_ai_search_schema($question, ['limit' => $limit], $user);
    if (($search['status'] ?? '') !== 'success') {
        kirpi_ai_log_operation('query_plan_preview', 'failed', [
            'question' => $question,
            'tokens' => $tokens,
            'reason' => 'schema_search_failed',
        ], null, 'query_plan', null);

        return [
            'status' => 'error',
            'question' => $question,
            'tokens' => $tokens,
            'search_mode' => null,
            'index_count' => kirpi_ai_schema_index_count(),
            'candidate_count' => 0,
            'primary_candidate' => null,
            'candidates' => [],
            'safety_notes' => $safetyNotes,
            'meta' => [
                'message' => 'schema_search_failed',
            ],
        ];
    }

    $candidates = [];
    foreach ((array) ($search['results'] ?? []) as $index => $result) {
        $matchedFields = array_values((array) ($result['matched_fields'] ?? []));
        $fieldDetails = [];
        $recommendedFields = [];

        foreach ($matchedFields as $field) {
            $name = trim((string) ($field['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $recommendedFields[] = $name;
            $fieldDetails[] = [
                'name' => $name,
                'type' => (string) ($field['type'] ?? ''),
                'description' => (string) ($field['description'] ?? ''),
                'score' => (int) ($field['score'] ?? 0),
                'matched_terms' => array_values((array) ($field['matched_terms'] ?? [])),
            ];

            if (count($recommendedFields) >= 8) {
                break;
            }
        }

        $notes = [];
        if (empty($recommendedFields)) {
            $notes[] = 'Entity metadata ile eşleşti; field seçimi için kullanıcı onayı gerekir.';
        }

        $candidates[] = [
            'rank' => $index + 1,
            'score' => (int) ($result['score'] ?? 0),
            'module' => (string) ($result['module'] ?? ''),
            'entity' => (string) ($result['entity'] ?? ''),
            'table' => (string) ($result['table'] ?? ''),
            'description' => (string) ($result['description'] ?? ''),
            'permission' => $result['permission'] ?? null,
            'matched_terms' => array_values((array) ($result['matched_terms'] ?? [])),
            'matched_sources' => array_values((array) ($result['matched_sources'] ?? [])),
            'recommended_fields' => array_values(array_unique($recommendedFields)),
            'field_details' => $fieldDetails,
            'notes' => $notes,
        ];
    }

    $plan = [
        'status' => 'success',
        'question' => $question,
        'tokens' => $tokens,
        'search_mode' => $search['meta']['mode'] ?? null,
        'index_count' => (int) ($search['meta']['index_count'] ?? kirpi_ai_schema_index_count()),
        'candidate_count' => count($candidates),
        'primary_candidate' => $candidates[0] ?? null,
        'candidates' => $candidates,
        'allowed_tables' => array_values(array_unique(array_filter(array_map(
            static fn (array $candidate): string => (string) ($candidate['table'] ?? ''),
            $candidates
        )))),
        'allowed_fields' => array_reduce($candidates, static function (array $carry, array $candidate): array {
            $table = (string) ($candidate['table'] ?? '');
            if ($table !== '') {
                $carry[$table] = (array) ($candidate['recommended_fields'] ?? []);
            }

            return $carry;
        }, []),
        'safety_notes' => $safetyNotes,
        'meta' => [
            'result_count' => (int) ($search['meta']['result_count'] ?? count($candidates)),
            'sql_generated' => false,
            'data_read' => false,
        ],
    ];

    kirpi_ai_log_operation('query_plan_preview', 'success', [
        'question' => $question,
        'tokens' => $tokens,
        'candidate_count' => count($candidates),
        'primary_entity' => $candidates[0]['entity'] ?? null,
        'search_mode' => $plan['search_mode'],
        'sql_generated' => false,
    ], null, 'query_plan', null);

    return $plan;
}

function kirpi_ai_log_operation(
    string $action,
    string $status,
    array $details = [],
    ?string $modelAdapter = null,
    ?string $entityType = null,
    ?int $entityId = null
): void {
    $status = in_array($status, ['success', 'failed', 'blocked'], true) ? $status : 'success';
    $user = current_user();
    $userId = (int) ($user['id'] ?? 0);

    $detailsJson = null;
    if (!empty($details)) {
        $encoded = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $detailsJson = $encoded === false ? null : $encoded;
    }

    if (kirpi_ai_audit_table_ready()) {
        try {
            $stmt = db()->prepare("
                INSERT INTO ai_audit_logs (
                    user_id,
                    action_key,
                    status,
                    model_adapter,
                    entity_type,
                    entity_id,
                    route_path,
                    ip_address,
                    details_json
                ) VALUES (
                    :user_id,
                    :action_key,
                    :status,
                    :model_adapter,
                    :entity_type,
                    :entity_id,
                    :route_path,
                    :ip_address,
                    :details_json
                )
            ");

            $stmt->execute([
                ':user_id' => $userId > 0 ? $userId : null,
                ':action_key' => mb_substr(trim($action), 0, 120),
                ':status' => $status,
                ':model_adapter' => $modelAdapter !== null ? mb_substr(trim($modelAdapter), 0, 120) : null,
                ':entity_type' => $entityType !== null ? mb_substr(trim($entityType), 0, 80) : null,
                ':entity_id' => $entityId,
                ':route_path' => mb_substr((string) ($GLOBALS['current_route_path'] ?? ''), 0, 190),
                ':ip_address' => function_exists('kirpi_request_ip') ? kirpi_request_ip() : null,
                ':details_json' => $detailsJson,
            ]);
        } catch (Throwable $e) {
            error_log('ai audit log insert error: ' . $e->getMessage());
        }
    }

    if (function_exists('kirpi_audit_log')) {
        kirpi_audit_log($action, 'ai', $details, $entityType, $entityId, $status);
    }
}

function kirpi_ai_sql_guard_readonly(string $sql, array $options = []): array
{
    $normalized = trim($sql);
    $canonical = strtolower(trim(preg_replace('/\s+/', ' ', $normalized) ?? ''));
    $reasons = [];
    $tables = [];
    $allowedTables = array_values(array_filter(array_map(
        static fn ($table): string => strtolower(trim((string) $table, " \t\n\r\0\x0B`")),
        (array) ($options['allowed_tables'] ?? [])
    )));
    $allowedFields = [];
    foreach ((array) ($options['allowed_fields'] ?? []) as $table => $fields) {
        $normalizedTable = strtolower(trim((string) $table, " \t\n\r\0\x0B`"));
        if ($normalizedTable === '') {
            continue;
        }

        $allowedFields[$normalizedTable] = array_values(array_unique(array_filter(array_map(
            static fn ($field): string => strtolower(trim((string) $field, " \t\n\r\0\x0B`")),
            (array) $fields
        ))));
    }

    if ($canonical === '') {
        $reasons[] = 'empty_sql';
    }

    if ($canonical !== '' && !str_starts_with($canonical, 'select')) {
        $reasons[] = 'only_select_allowed';
    }

    if (preg_match('/(--|#|\/\*|\*\/)/', $normalized) === 1) {
        $reasons[] = 'comments_not_allowed';
    }

    if (preg_match('/;/', $normalized) === 1) {
        $reasons[] = 'semicolon_not_allowed';
    }

    if (preg_match('/\b(delete|update|insert|drop|alter|truncate|create|replace|grant|revoke|merge|call|execute|prepare|deallocate|set|use|lock|unlock|handler)\b/i', $canonical) === 1) {
        $reasons[] = 'dangerous_keyword';
    }

    if (preg_match('/\b(into\s+outfile|into\s+dumpfile|load_file\s*\(|sleep\s*\(|benchmark\s*\(|information_schema|mysql\.|performance_schema|sys\.)/i', $canonical) === 1) {
        $reasons[] = 'unsafe_expression';
    }

    if (preg_match('/\bunion\b/i', $canonical) === 1) {
        $reasons[] = 'union_not_allowed';
    }

    if (kirpi_ai_sql_uses_wildcard_select($normalized)) {
        $reasons[] = 'wildcard_select_not_allowed';
    }

    if (preg_match('/\b(from|join)\s*\(/i', $canonical) === 1) {
        $reasons[] = 'subquery_not_allowed';
    }

    if (preg_match_all('/\b(from|join)\s+(`?[a-zA-Z_][a-zA-Z0-9_]*`?(?:\.`?[a-zA-Z_][a-zA-Z0-9_]*`?)?)/', $canonical, $matches, PREG_SET_ORDER) > 0) {
        foreach ($matches as $match) {
            $table = strtolower(str_replace('`', '', (string) ($match[2] ?? '')));
            if (str_contains($table, '.')) {
                $parts = explode('.', $table);
                $table = (string) end($parts);
            }

            if ($table !== '') {
                $tables[] = $table;
            }
        }
    }

    $tables = array_values(array_unique($tables));
    if ($canonical !== '' && empty($tables)) {
        $reasons[] = 'table_required';
    }

    if (!empty($allowedTables)) {
        $blockedTables = array_values(array_diff($tables, $allowedTables));
        if (!empty($blockedTables)) {
            $reasons[] = 'table_not_allowed';
        }
    }

    $fieldCheck = kirpi_ai_sql_validate_allowed_fields($normalized, $tables, $allowedFields);
    if (!$fieldCheck['allowed']) {
        $reasons[] = 'field_not_allowed';
    }

    $reasons = array_values(array_unique($reasons));
    $allowed = empty($reasons);
    $result = [
        'allowed' => $allowed,
        'status' => $allowed ? 'allowed' : 'blocked',
        'reason' => $reasons[0] ?? null,
        'reasons' => $reasons,
        'tables' => $tables,
        'allowed_tables' => $allowedTables,
        'detected_fields' => $fieldCheck['detected_fields'],
        'blocked_fields' => $fieldCheck['blocked_fields'],
        'allowed_fields' => $allowedFields,
    ];

    if (!empty($options['audit']) && $normalized !== '') {
        kirpi_ai_log_operation('sql_guard_check', $allowed ? 'success' : 'blocked', [
            'allowed' => $allowed,
            'reasons' => $reasons,
            'tables' => $tables,
            'allowed_tables' => $allowedTables,
            'detected_fields' => $fieldCheck['detected_fields'],
            'blocked_fields' => $fieldCheck['blocked_fields'],
            'sql_length' => strlen($normalized),
        ], null, 'sql_guard', null);
    }

    return $result;
}

function kirpi_ai_sql_validate_allowed_fields(string $sql, array $tables, array $allowedFields): array
{
    if ($sql === '' || empty($allowedFields)) {
        return [
            'allowed' => true,
            'detected_fields' => [],
            'blocked_fields' => [],
        ];
    }

    $withoutStrings = preg_replace("/'(?:''|[^'])*'|\"(?:\"\"|[^\"])*\"/s", ' ', $sql) ?? $sql;
    $aliases = [];
    if (preg_match_all('/\b(?:from|join)\s+(`?[a-zA-Z_][a-zA-Z0-9_]*`?)(?:\s+(?:as\s+)?(`?[a-zA-Z_][a-zA-Z0-9_]*`?))?/i', $withoutStrings, $tableMatches, PREG_SET_ORDER) > 0) {
        $reservedAliases = ['where', 'join', 'left', 'right', 'inner', 'outer', 'cross', 'on', 'group', 'order', 'limit', 'having'];
        foreach ($tableMatches as $match) {
            $table = strtolower(str_replace('`', '', (string) ($match[1] ?? '')));
            $alias = strtolower(str_replace('`', '', (string) ($match[2] ?? '')));
            $aliases[$table] = $table;
            if ($alias !== '' && !in_array($alias, $reservedAliases, true)) {
                $aliases[$alias] = $table;
            }
        }
    }

    $detected = [];
    $blocked = [];
    if (preg_match_all('/\b(`?[a-zA-Z_][a-zA-Z0-9_]*`?)\s*\.\s*(`?[a-zA-Z_][a-zA-Z0-9_]*`?)\b/', $withoutStrings, $qualifiedMatches, PREG_SET_ORDER) > 0) {
        foreach ($qualifiedMatches as $match) {
            $qualifier = strtolower(str_replace('`', '', (string) ($match[1] ?? '')));
            $field = strtolower(str_replace('`', '', (string) ($match[2] ?? '')));
            $table = $aliases[$qualifier] ?? $qualifier;
            $key = $table . '.' . $field;
            $detected[] = $key;
            if (!in_array($field, (array) ($allowedFields[$table] ?? []), true)) {
                $blocked[] = $key;
            }
        }
    }

    $unqualifiedSql = preg_replace('/\b`?[a-zA-Z_][a-zA-Z0-9_]*`?\s*\.\s*`?[a-zA-Z_][a-zA-Z0-9_]*`?\b/', ' ', $withoutStrings) ?? $withoutStrings;
    $unqualifiedSql = preg_replace('/\b(?:from|join)\s+`?[a-zA-Z_][a-zA-Z0-9_]*`?(?:\s+(?:as\s+)?`?[a-zA-Z_][a-zA-Z0-9_]*`?)?/i', ' ', $unqualifiedSql) ?? $unqualifiedSql;
    $unqualifiedSql = preg_replace('/\b[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', '(', $unqualifiedSql) ?? $unqualifiedSql;
    $unqualifiedSql = preg_replace('/\bas\s+`?[a-zA-Z_][a-zA-Z0-9_]*`?/i', ' ', $unqualifiedSql) ?? $unqualifiedSql;

    $keywords = [
        'select', 'distinct', 'from', 'join', 'left', 'right', 'inner', 'outer', 'cross', 'on',
        'where', 'and', 'or', 'not', 'is', 'null', 'true', 'false', 'in', 'like', 'between',
        'group', 'by', 'order', 'asc', 'desc', 'having', 'limit', 'offset', 'case', 'when',
        'then', 'else', 'end', 'as', 'all', 'any', 'exists',
    ];
    $tableNames = array_values(array_unique(array_merge($tables, array_keys($aliases))));
    $allowedUnion = array_values(array_unique(array_merge(...array_values($allowedFields))));
    if (preg_match_all('/\b`?([a-zA-Z_][a-zA-Z0-9_]*)`?\b/', $unqualifiedSql, $identifierMatches) > 0) {
        foreach ((array) ($identifierMatches[1] ?? []) as $identifier) {
            $field = strtolower((string) $identifier);
            if (in_array($field, $keywords, true) || in_array($field, $tableNames, true)) {
                continue;
            }

            $detected[] = $field;
            if (!in_array($field, $allowedUnion, true)) {
                $blocked[] = $field;
            }
        }
    }

    $detected = array_values(array_unique($detected));
    $blocked = array_values(array_unique($blocked));

    return [
        'allowed' => empty($blocked),
        'detected_fields' => $detected,
        'blocked_fields' => $blocked,
    ];
}

function kirpi_ai_sql_uses_wildcard_select(string $sql): bool
{
    $sql = trim($sql);
    if ($sql === '') {
        return false;
    }

    if (preg_match('/^\s*select\s+(?:distinct\s+)?(.+?)\s+from\s/is', $sql, $matches) !== 1) {
        return false;
    }

    $selectList = (string) ($matches[1] ?? '');

    return preg_match('/(^|,|\s)(`?[a-zA-Z_][a-zA-Z0-9_]*`?\.)?\*(?=\s*(,|$))/i', $selectList) === 1;
}

function kirpi_ai_preview_sql(string $sql, array $context = []): array
{
    $sql = trim($sql);
    $allowedTables = array_values(array_filter(array_map(
        static fn ($table): string => trim((string) $table),
        (array) ($context['allowed_tables'] ?? [])
    )));
    $allowedFields = (array) ($context['allowed_fields'] ?? []);
    $plannerQuestion = trim((string) ($context['planner_question'] ?? ''));

    if ($sql === '') {
        return [
            'status' => 'empty',
            'decision' => 'blocked',
            'executable' => false,
            'execution_enabled' => false,
            'explain_enabled' => false,
            'planner_question' => $plannerQuestion,
            'allowed_tables' => $allowedTables,
            'allowed_fields' => $allowedFields,
            'guard' => null,
            'notes' => [
                'SQL girilmedi.',
                'Bu aşama SQL çalıştırmaz.',
            ],
        ];
    }

    $guard = kirpi_ai_sql_guard_readonly($sql, [
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
        'audit' => false,
    ]);
    $guardAllowed = !empty($guard['allowed']);
    $decision = $guardAllowed ? 'preview_allowed' : 'blocked';
    $notes = [
        'SQL çalıştırılmadı.',
        'EXPLAIN çalıştırılmadı.',
        'Gerçek veri okunmadı.',
    ];

    if ($guardAllowed) {
        $notes[] = 'Guard kontrolü geçti; yürütme yine de kapalıdır.';
    } else {
        $notes[] = 'Guard kontrolü blokladı; SQL üretim akışına geri dönülmelidir.';
    }

    $explain = kirpi_ai_explain_sql($sql, [
        'allowed_tables' => $allowedTables,
        'guard' => $guard,
        'audit' => false,
    ]);

    $preview = [
        'status' => 'success',
        'decision' => $decision,
        'executable' => false,
        'execution_enabled' => false,
        'explain_enabled' => !empty($explain['enabled']),
        'explain' => $explain,
        'planner_question' => $plannerQuestion,
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
        'guard' => $guard,
        'detected_tables' => (array) ($guard['tables'] ?? []),
        'notes' => $notes,
        'meta' => [
            'sql_length' => strlen($sql),
            'guard_allowed' => $guardAllowed,
        ],
    ];

    if (!empty($context['audit'])) {
        kirpi_ai_log_operation('sql_preview_check', $guardAllowed ? 'success' : 'blocked', [
            'decision' => $decision,
            'guard_allowed' => $guardAllowed,
            'guard_reasons' => (array) ($guard['reasons'] ?? []),
            'detected_tables' => (array) ($guard['tables'] ?? []),
            'allowed_tables' => $allowedTables,
            'planner_question' => $plannerQuestion !== '' ? $plannerQuestion : null,
            'sql_length' => strlen($sql),
            'execution_enabled' => false,
            'explain_enabled' => !empty($explain['enabled']),
            'explain_status' => $explain['status'] ?? null,
            'explain_reason' => $explain['reason'] ?? null,
        ], null, 'sql_preview', null);
    }

    return $preview;
}

function kirpi_ai_explain_sql(string $sql, array $context = []): array
{
    $sql = trim($sql);
    $enabled = env_bool('AI_SQL_EXPLAIN_ENABLED', false);
    $guard = $context['guard'] ?? null;
    if (!is_array($guard)) {
        $guard = kirpi_ai_sql_guard_readonly($sql, [
            'allowed_tables' => (array) ($context['allowed_tables'] ?? []),
            'allowed_fields' => (array) ($context['allowed_fields'] ?? []),
            'audit' => false,
        ]);
    }

    $base = [
        'enabled' => $enabled,
        'status' => 'blocked',
        'reason' => null,
        'rows' => [],
        'execution_enabled' => false,
        'data_read' => false,
    ];

    if ($sql === '') {
        $base['reason'] = 'empty_sql';
        return $base;
    }

    if (empty($guard['allowed'])) {
        $base['reason'] = 'guard_blocked';
        $base['guard_reasons'] = (array) ($guard['reasons'] ?? []);
        return $base;
    }

    if (!$enabled) {
        $base['reason'] = 'explain_disabled';
        return $base;
    }

    try {
        $stmt = db()->query('EXPLAIN ' . $sql);
        $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        $base['status'] = 'success';
        $base['reason'] = null;
        $base['rows'] = $rows;

        if (!empty($context['audit'])) {
            kirpi_ai_log_operation('sql_explain_check', 'success', [
                'row_count' => count($rows),
                'tables' => (array) ($guard['tables'] ?? []),
                'sql_length' => strlen($sql),
                'execution_enabled' => false,
                'data_read' => false,
            ], null, 'sql_explain', null);
        }

        return $base;
    } catch (Throwable $e) {
        error_log('ai sql explain error: ' . $e->getMessage());
        $base['status'] = 'blocked';
        $base['reason'] = 'explain_failed';

        if (!empty($context['audit'])) {
            kirpi_ai_log_operation('sql_explain_check', 'blocked', [
                'reason' => 'explain_failed',
                'tables' => (array) ($guard['tables'] ?? []),
                'sql_length' => strlen($sql),
                'execution_enabled' => false,
                'data_read' => false,
            ], null, 'sql_explain', null);
        }

        return $base;
    }
}

function kirpi_ai_build_sql_candidate(array $input): array
{
    $question = trim((string) ($input['question'] ?? ''));
    $candidateSql = trim((string) ($input['candidate_sql'] ?? ''));
    $modelAdapter = trim((string) ($input['model_adapter'] ?? 'manual'));
    $promptHash = trim((string) ($input['prompt_hash'] ?? ''));
    $generationMode = trim((string) ($input['generation_mode'] ?? 'manual'));
    $confidence = (float) ($input['confidence'] ?? 0);
    $confidence = max(0, min(1, $confidence));
    $allowedTables = array_values(array_filter(array_map(
        static fn ($table): string => trim((string) $table),
        (array) ($input['allowed_tables'] ?? [])
    )));
    $allowedFields = (array) ($input['allowed_fields'] ?? []);
    $warnings = [];

    if ($modelAdapter === '') {
        $modelAdapter = 'manual';
    }

    if ($generationMode === '') {
        $generationMode = $modelAdapter === 'manual' ? 'manual' : 'adapter';
    }

    if ($candidateSql === '') {
        $warnings[] = 'candidate_sql_empty';
    }

    if ($candidateSql !== '' && kirpi_ai_sql_uses_wildcard_select($candidateSql)) {
        $warnings[] = 'wildcard_select_not_allowed';
    }

    $guard = $candidateSql !== '' ? kirpi_ai_sql_guard_readonly($candidateSql, [
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
        'audit' => false,
    ]) : null;
    if (is_array($guard) && empty($guard['allowed'])) {
        $warnings[] = 'guard_blocked';
        $warnings = array_merge($warnings, array_map('strval', (array) ($guard['reasons'] ?? [])));
    }

    if (empty($allowedTables)) {
        $warnings[] = 'allowed_tables_missing';
    }

    if ($modelAdapter === 'manual') {
        $warnings[] = 'manual_candidate';
    }

    $candidate = [
        'status' => in_array('guard_blocked', $warnings, true) ? 'blocked' : ($candidateSql === '' ? 'empty' : 'ready'),
        'question' => $question,
        'planner_context' => [
            'allowed_tables' => $allowedTables,
            'allowed_fields' => $allowedFields,
        ],
        'candidate_sql' => $candidateSql,
        'model_adapter' => $modelAdapter,
        'confidence' => $confidence,
        'prompt_hash' => $promptHash !== '' ? $promptHash : null,
        'generation_mode' => $generationMode,
        'warnings' => array_values(array_unique($warnings)),
        'generated_at' => date('c'),
        'execution_enabled' => false,
        'preview_required' => true,
        'guard' => $guard,
    ];

    if (!empty($input['audit']) && $candidateSql !== '') {
        kirpi_ai_log_operation('sql_candidate_review', $candidate['status'] === 'blocked' ? 'blocked' : 'success', [
            'question' => $question !== '' ? $question : null,
            'model_adapter' => $modelAdapter,
            'confidence' => $confidence,
            'prompt_hash' => $promptHash !== '' ? $promptHash : null,
            'generation_mode' => $generationMode,
            'warnings' => $candidate['warnings'],
            'allowed_tables' => $allowedTables,
            'sql_length' => strlen($candidateSql),
            'execution_enabled' => false,
            'preview_required' => true,
        ], $modelAdapter, 'sql_candidate', null);
    }

    return $candidate;
}

function kirpi_ai_blocked_sql_candidate(string $adapterKey, string $reason, array $details = []): array
{
    kirpi_ai_log_operation('sql_candidate_generate', 'blocked', array_merge([
        'model_adapter' => $adapterKey,
        'reason' => $reason,
        'execution_enabled' => false,
        'preview_required' => true,
    ], $details), $adapterKey, 'sql_candidate', null);

    return [
        'status' => 'blocked',
        'reason' => $reason,
        'model_adapter' => $adapterKey,
        'candidate_sql' => '',
        'warnings' => [$reason],
        'execution_enabled' => false,
        'preview_required' => true,
    ];
}

function kirpi_ai_adapter_secret_configured(array $adapter): bool
{
    return kirpi_ai_adapter_secret($adapter) !== '';
}

function kirpi_ai_adapter_secret(array $adapter): string
{
    $config = (array) ($adapter['config'] ?? []);
    $apiKeyEnv = trim((string) ($config['api_key_env'] ?? ''));
    if ($apiKeyEnv !== '') {
        $value = trim((string) env($apiKeyEnv, ''));
        if ($value !== '') {
            return $value;
        }
    }

    $apiKeyRef = trim((string) ($config['api_key_ref'] ?? ''));
    if ($apiKeyRef !== '' && function_exists('kirpi_setting_get')) {
        $value = trim((string) kirpi_setting_get($apiKeyRef, ''));
        if ($value !== '') {
            return $value;
        }
    }

    $provider = strtolower(trim((string) ($adapter['provider'] ?? '')));
    if ($provider !== '' && function_exists('kirpi_setting_get')) {
        $providerRef = kirpi_ai_provider_secret_key($provider);
        $value = trim((string) kirpi_setting_get($providerRef, ''));
        if ($value !== '') {
            return $value;
        }
    }

    $fallbackRef = kirpi_ai_adapter_fallback_secret_ref($adapter);
    if ($fallbackRef !== '' && function_exists('kirpi_setting_get')) {
        $value = trim((string) kirpi_setting_get($fallbackRef, ''));
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function kirpi_ai_adapter_fallback_secret_ref(array $adapter): string
{
    if (!kirpi_ai_models_table_ready() || !function_exists('kirpi_setting_get')) {
        return '';
    }

    $provider = strtolower(trim((string) ($adapter['provider'] ?? '')));
    if ($provider === '' || $provider === 'mock') {
        return '';
    }

    $currentKey = trim((string) ($adapter['adapter_key'] ?? ''));

    try {
        $stmt = db()->prepare("
            SELECT adapter_key, config_json
            FROM ai_model_adapters
            WHERE provider = :provider
              AND adapter_key <> :adapter_key
              AND config_json IS NOT NULL
            ORDER BY is_enabled DESC, adapter_type ASC, adapter_key ASC
        ");
        $stmt->execute([
            ':provider' => $provider,
            ':adapter_key' => $currentKey,
        ]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $decoded = json_decode((string) ($row['config_json'] ?? ''), true);
            if (!is_array($decoded)) {
                continue;
            }

            $ref = trim((string) ($decoded['api_key_ref'] ?? ''));
            if ($ref !== '' && trim((string) kirpi_setting_get($ref, '')) !== '') {
                return $ref;
            }
        }
    } catch (Throwable $e) {
        error_log('ai adapter fallback secret lookup error: ' . $e->getMessage());
    }

    return '';
}

function kirpi_ai_adapter_runtime_enabled(array $adapter): bool
{
    if (!env_bool('AI_EXTERNAL_MODEL_RUNTIME_ENABLED', false)) {
        return false;
    }

    $config = (array) ($adapter['config'] ?? []);

    if (array_key_exists('runtime_enabled', $config)) {
        return filter_var($config['runtime_enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    return false;
}

function kirpi_ai_http_json_post(string $url, array $headers, array $payload, int $timeoutSeconds = 30): array
{
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($body === false) {
        return [
            'success' => false,
            'status_code' => 0,
            'error' => 'json_encode_failed',
            'body' => '',
            'json' => null,
        ];
    }

    $headers[] = 'Content-Type: application/json';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
        ]);
        $responseBody = (string) curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => max(1, $timeoutSeconds),
                'ignore_errors' => true,
            ],
        ]);
        $responseBody = (string) @file_get_contents($url, false, $context);
        $error = $responseBody === '' ? 'http_request_failed' : '';
        $statusCode = 0;
        foreach (($http_response_header ?? []) as $headerLine) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', (string) $headerLine, $matches) === 1) {
                $statusCode = (int) ($matches[1] ?? 0);
                break;
            }
        }
    }

    $decoded = null;
    if ($responseBody !== '') {
        $json = json_decode($responseBody, true);
        if (is_array($json)) {
            $decoded = $json;
        }
    }

    return [
        'success' => $statusCode >= 200 && $statusCode < 300 && is_array($decoded),
        'status_code' => $statusCode,
        'error' => $error,
        'body' => $responseBody,
        'json' => $decoded,
    ];
}

function kirpi_ai_extract_sql_from_model_text(string $text): array
{
    $text = trim($text);
    $warnings = [];

    if ($text === '') {
        return [
            'sql' => '',
            'confidence' => 0,
            'warnings' => ['empty_model_response'],
        ];
    }

    if (preg_match('/<think\b[^>]*>.*?<\/think>/is', $text) === 1) {
        $text = trim((string) preg_replace('/<think\b[^>]*>.*?<\/think>/is', '', $text));
        $warnings[] = 'model_reasoning_stripped';
    }

    $decoded = json_decode($text, true);
    if (!is_array($decoded)) {
        $decoded = kirpi_ai_extract_json_object_from_model_text($text);
        if (is_array($decoded)) {
            $warnings[] = 'json_object_extracted';
        }
    }

    if (is_array($decoded)) {
        $sql = trim((string) ($decoded['sql'] ?? $decoded['candidate_sql'] ?? ''));
        $confidence = (float) ($decoded['confidence'] ?? 0.65);
        $modelWarnings = array_values(array_filter(array_map('strval', (array) ($decoded['warnings'] ?? []))));
        if ($sql !== '' && !str_starts_with(strtolower(ltrim($sql)), 'select')) {
            $extractedSql = kirpi_ai_extract_select_statement_from_text($sql);
            if ($extractedSql !== '') {
                $sql = $extractedSql;
                $warnings[] = 'sql_statement_extracted';
            } else {
                $sql = '';
                $warnings[] = 'sql_statement_missing';
            }
        }

        return [
            'sql' => $sql,
            'confidence' => max(0, min(1, $confidence)),
            'warnings' => array_values(array_unique(array_merge($warnings, $modelWarnings))),
        ];
    }

    if (preg_match('/```(?:sql)?\s*(.*?)```/is', $text, $matches) === 1) {
        $text = trim((string) ($matches[1] ?? $text));
        $warnings[] = 'fenced_sql_extracted';
    } else {
        $warnings[] = 'plain_text_sql_extracted';
    }

    $sql = kirpi_ai_extract_select_statement_from_text($text);
    if ($sql === '') {
        $warnings[] = 'sql_statement_missing';

        return [
            'sql' => '',
            'confidence' => 0,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    return [
        'sql' => $sql,
        'confidence' => 0.55,
        'warnings' => $warnings,
    ];
}

function kirpi_ai_extract_json_object_from_model_text(string $text): ?array
{
    $text = trim((string) preg_replace('/<think\b[^>]*>.*?<\/think>/is', '', $text));
    if ($text === '') {
        return null;
    }

    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    $length = strlen($text);
    for ($start = 0; $start < $length; $start++) {
        if ($text[$start] !== '{') {
            continue;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        for ($index = $start; $index < $length; $index++) {
            $char = $text[$index];
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"') {
                $inString = true;
                continue;
            }
            if ($char === '{') {
                $depth++;
                continue;
            }
            if ($char !== '}') {
                continue;
            }

            $depth--;
            if ($depth === 0) {
                $candidate = substr($text, $start, $index - $start + 1);
                $decoded = json_decode($candidate, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                break;
            }
        }
    }

    return null;
}

function kirpi_ai_extract_select_statement_from_text(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    if (preg_match('/\bselect\b[\s\S]*?\bfrom\b[\s\S]*/i', $text, $matches) !== 1) {
        return '';
    }

    $sql = trim((string) ($matches[0] ?? ''));
    $sql = preg_split('/\r?\n\s*(?:warnings?|confidence|explanation|açıklama)\s*[:=]/i', $sql)[0] ?? $sql;
    $sql = trim($sql);
    $semicolonPos = strpos($sql, ';');
    if ($semicolonPos !== false) {
        $sql = substr($sql, 0, $semicolonPos);
    }

    return trim($sql);
}

function kirpi_ai_parse_chat_completion_response(array $response): array
{
    $content = trim((string) ($response['choices'][0]['message']['content'] ?? ''));
    if ($content === '') {
        return [
            'sql' => '',
            'confidence' => 0,
            'warnings' => ['chat_completion_content_missing'],
        ];
    }

    return kirpi_ai_extract_sql_from_model_text($content);
}

function kirpi_ai_build_sql_generation_prompt(string $question, array $context = []): array
{
    $question = trim($question);
    $allowedTables = array_values(array_filter(array_map(
        static fn ($table): string => trim((string) $table),
        (array) ($context['allowed_tables'] ?? [])
    )));
    $allowedFields = (array) ($context['allowed_fields'] ?? []);

    $lines = [
        'Task: Produce one read-only SQL candidate for the given user question.',
        'Hard rules:',
        '- Return a single SELECT statement only.',
        '- Do not explain, reason, summarize, or include markdown.',
        '- Do not use SELECT * or table.*. Select explicit allowed fields only.',
        '- Do not use semicolons, comments, UNION, subqueries, DDL, DML, system schemas, or unsafe functions.',
        '- Use only the allowed tables and fields below.',
        '- Do not invent tables or fields.',
        '- Do not execute SQL.',
        '- The candidate must still pass SQL Preview and SQL Guard.',
        '- Return only JSON with keys: sql, confidence, warnings.',
        '- sql must be a single SELECT statement or an empty string if no safe candidate can be produced.',
        '',
        'User question:',
        $question !== '' ? $question : '-',
        '',
        'Allowed tables:',
        empty($allowedTables) ? '-' : implode(', ', $allowedTables),
        '',
        'Allowed fields by table:',
    ];

    if (empty($allowedFields)) {
        $lines[] = '-';
    } else {
        foreach ($allowedFields as $table => $fields) {
            $fieldList = array_values(array_filter(array_map('strval', (array) $fields)));
            $lines[] = (string) $table . ': ' . (empty($fieldList) ? '-' : implode(', ', $fieldList));
        }
    }

    $prompt = implode("\n", $lines);

    return [
        'status' => 'success',
        'prompt' => $prompt,
        'prompt_hash' => hash('sha256', $prompt),
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
        'safety_rules' => [
            'single_select_only',
            'no_execution',
            'preview_required',
            'guard_required',
            'allowed_schema_only',
        ],
    ];
}

function kirpi_ai_mock_generate_sql_candidate(string $question, array $context = []): array
{
    $prompt = kirpi_ai_build_sql_generation_prompt($question, $context);
    $allowedTables = (array) ($prompt['allowed_tables'] ?? []);
    $allowedFields = (array) ($prompt['allowed_fields'] ?? []);
    $table = (string) ($allowedTables[0] ?? '');
    $warnings = ['mock_generation'];
    $candidateSql = '';
    $fields = [];

    if ($table === '') {
        $warnings[] = 'allowed_tables_missing';
    } else {
        $rawFields = (array) ($allowedFields[$table] ?? []);
        foreach ($rawFields as $field) {
            $field = trim((string) $field);
            if ($field !== '' && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field) === 1) {
                $fields[] = $field;
            }

            if (count($fields) >= 5) {
                break;
            }
        }

        if (empty($fields)) {
            $fields[] = 'id';
            $warnings[] = 'field_fallback';
        }

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table) === 1) {
            $candidateSql = 'SELECT ' . implode(', ', array_unique($fields)) . ' FROM ' . $table . ' LIMIT 50';
        } else {
            $warnings[] = 'invalid_table_name';
        }
    }

    $candidate = kirpi_ai_build_sql_candidate([
        'question' => $question,
        'candidate_sql' => $candidateSql,
        'model_adapter' => 'mock-sql-generator',
        'confidence' => $candidateSql !== '' ? 0.35 : 0,
        'prompt_hash' => (string) ($prompt['prompt_hash'] ?? ''),
        'generation_mode' => 'mock',
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
    ]);
    $candidate['warnings'] = array_values(array_unique(array_merge((array) ($candidate['warnings'] ?? []), $warnings)));
    $candidate['prompt_hash'] = (string) ($prompt['prompt_hash'] ?? '');
    $candidate['generation_mode'] = 'mock';
    $candidate['prompt'] = (string) ($prompt['prompt'] ?? '');

    if (!empty($context['audit']) && $candidateSql !== '') {
        kirpi_ai_log_operation('sql_candidate_generate', 'success', [
            'question' => $question !== '' ? $question : null,
            'model_adapter' => 'mock-sql-generator',
            'generation_mode' => 'mock',
            'prompt_hash' => $candidate['prompt_hash'],
            'warnings' => $candidate['warnings'],
            'allowed_tables' => $allowedTables,
            'sql_length' => strlen($candidateSql),
            'execution_enabled' => false,
            'preview_required' => true,
        ], 'mock-sql-generator', 'sql_candidate', null);
    }

    return $candidate;
}

function kirpi_ai_external_generate_sql_candidate(string $question, array $context, array $adapter): array
{
    $adapterKey = (string) ($adapter['adapter_key'] ?? '');
    $provider = strtolower(trim((string) ($adapter['provider'] ?? '')));
    $config = (array) ($adapter['config'] ?? []);
    $prompt = kirpi_ai_build_sql_generation_prompt($question, $context);
    $allowedTables = (array) ($prompt['allowed_tables'] ?? []);
    $allowedFields = (array) ($prompt['allowed_fields'] ?? []);

    if (!in_array($provider, ['openai', 'openai_compatible'], true)) {
        return kirpi_ai_blocked_sql_candidate($adapterKey, 'provider_runtime_not_supported', [
            'provider' => $provider,
        ]);
    }

    $secret = kirpi_ai_adapter_secret($adapter);
    if ($secret === '') {
        return kirpi_ai_blocked_sql_candidate($adapterKey, 'external_adapter_not_configured', [
            'secret_policy' => 'env_or_setting_reference_required',
        ]);
    }

    $baseUrl = rtrim(trim((string) ($config['base_url'] ?? '')), '/');
    if ($baseUrl === '') {
        $baseUrl = $provider === 'openai' ? 'https://api.openai.com/v1' : '';
    }
    if ($baseUrl === '') {
        return kirpi_ai_blocked_sql_candidate($adapterKey, 'provider_base_url_missing', [
            'provider' => $provider,
        ]);
    }

    $model = trim((string) ($config['model'] ?? $adapter['model_name'] ?? ''));
    if ($model === '') {
        return kirpi_ai_blocked_sql_candidate($adapterKey, 'provider_model_missing', [
            'provider' => $provider,
        ]);
    }

    $timeout = max(5, min(120, (int) ($config['timeout_seconds'] ?? 30)));
    $temperature = max(0, min(1, (float) ($config['temperature'] ?? 0)));
    $maxTokens = max(128, min(4096, (int) ($config['max_tokens'] ?? 700)));
    $endpoint = $baseUrl . '/chat/completions';
    $payload = [
        'model' => $model,
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You generate safe read-only SQL candidates. Return only JSON. Never include secrets or real data.',
            ],
            [
                'role' => 'user',
                'content' => (string) ($prompt['prompt'] ?? ''),
            ],
        ],
    ];
    $structuredOutput = !array_key_exists('structured_output', $config) || filter_var($config['structured_output'], FILTER_VALIDATE_BOOLEAN);
    if ($structuredOutput) {
        $payload['response_format'] = ['type' => 'json_object'];
    }

    $http = kirpi_ai_http_json_post($endpoint, [
        'Authorization: Bearer ' . $secret,
    ], $payload, $timeout);

    $structuredOutputFallback = false;
    if ($structuredOutput && !($http['success'] ?? false) && in_array((int) ($http['status_code'] ?? 0), [400, 404, 415, 422], true)) {
        unset($payload['response_format']);
        $http = kirpi_ai_http_json_post($endpoint, [
            'Authorization: Bearer ' . $secret,
        ], $payload, $timeout);
        $structuredOutputFallback = true;
    }

    if (!($http['success'] ?? false)) {
        kirpi_ai_log_operation('sql_candidate_generate', 'failed', [
            'model_adapter' => $adapterKey,
            'provider' => $provider,
            'reason' => 'provider_request_failed',
            'status_code' => (int) ($http['status_code'] ?? 0),
            'prompt_hash' => (string) ($prompt['prompt_hash'] ?? ''),
            'execution_enabled' => false,
            'preview_required' => true,
        ], $adapterKey, 'sql_candidate', null);

        return [
            'status' => 'blocked',
            'reason' => 'provider_request_failed',
            'model_adapter' => $adapterKey,
            'candidate_sql' => '',
            'warnings' => ['provider_request_failed'],
            'execution_enabled' => false,
            'preview_required' => true,
        ];
    }

    $parsed = kirpi_ai_parse_chat_completion_response((array) ($http['json'] ?? []));
    $candidateSql = trim((string) ($parsed['sql'] ?? ''));
    $warnings = array_values(array_unique(array_merge(
        ['external_provider_generation'],
        $structuredOutputFallback ? ['structured_output_fallback'] : [],
        array_map('strval', (array) ($parsed['warnings'] ?? []))
    )));

    $candidate = kirpi_ai_build_sql_candidate([
        'question' => $question,
        'candidate_sql' => $candidateSql,
        'model_adapter' => $adapterKey,
        'confidence' => (float) ($parsed['confidence'] ?? 0.65),
        'prompt_hash' => (string) ($prompt['prompt_hash'] ?? ''),
        'generation_mode' => 'external_provider',
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
    ]);
    $candidate['warnings'] = array_values(array_unique(array_merge((array) ($candidate['warnings'] ?? []), $warnings)));
    $candidate['prompt_hash'] = (string) ($prompt['prompt_hash'] ?? '');
    $candidate['generation_mode'] = 'external_provider';

    $candidateReady = (string) ($candidate['status'] ?? '') === 'ready';
    kirpi_ai_log_operation($candidateReady ? 'sql_candidate_generate' : 'sql_candidate_generate_blocked', $candidateReady ? 'success' : 'blocked', [
        'question' => $question !== '' ? $question : null,
        'model_adapter' => $adapterKey,
        'provider' => $provider,
        'generation_mode' => 'external_provider',
        'prompt_hash' => $candidate['prompt_hash'],
        'warnings' => $candidate['warnings'],
        'guard_reasons' => (array) ($candidate['guard']['reasons'] ?? []),
        'allowed_tables' => $allowedTables,
        'sql_length' => strlen($candidateSql),
        'execution_enabled' => false,
        'preview_required' => true,
    ], $adapterKey, 'sql_candidate', null);

    return $candidate;
}

function kirpi_ai_test_model_adapter(string $adapterKey): array
{
    $adapterKey = trim($adapterKey);
    $adapter = kirpi_ai_model_adapter($adapterKey);

    if ($adapter === null) {
        return [
            'status' => 'blocked',
            'reason' => 'adapter_not_found',
        ];
    }

    $provider = strtolower(trim((string) ($adapter['provider'] ?? '')));
    $config = (array) ($adapter['config'] ?? []);

    if ((string) ($adapter['provider'] ?? '') === 'mock') {
        return [
            'status' => 'blocked',
            'reason' => 'mock_adapter_not_testable',
        ];
    }

    if ((int) ($adapter['is_enabled'] ?? 0) !== 1) {
        return [
            'status' => 'blocked',
            'reason' => 'adapter_disabled',
        ];
    }

    if (!in_array($provider, ['openai', 'openai_compatible'], true)) {
        return [
            'status' => 'blocked',
            'reason' => 'provider_runtime_not_supported',
        ];
    }

    if (!kirpi_ai_adapter_secret_configured($adapter)) {
        return [
            'status' => 'blocked',
            'reason' => 'external_adapter_not_configured',
        ];
    }

    if (!kirpi_ai_adapter_runtime_enabled($adapter)) {
        return [
            'status' => 'blocked',
            'reason' => 'external_runtime_disabled',
        ];
    }

    $baseUrl = rtrim(trim((string) ($config['base_url'] ?? '')), '/');
    if ($baseUrl === '') {
        $baseUrl = $provider === 'openai' ? 'https://api.openai.com/v1' : '';
    }
    if ($baseUrl === '') {
        return [
            'status' => 'blocked',
            'reason' => 'provider_base_url_missing',
        ];
    }

    $model = trim((string) ($config['model'] ?? $adapter['model_name'] ?? ''));
    if ($model === '') {
        return [
            'status' => 'blocked',
            'reason' => 'provider_model_missing',
        ];
    }

    $timeout = max(5, min(120, (int) ($config['timeout_seconds'] ?? 30)));
    $endpoint = $baseUrl . '/chat/completions';
    $payload = [
        'model' => $model,
        'temperature' => 0,
        'max_tokens' => 80,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Return only JSON. Do not include secrets or real data.',
            ],
            [
                'role' => 'user',
                'content' => 'Return exactly this JSON object: {"status":"ok","purpose":"provider_test"}',
            ],
        ],
    ];
    $structuredOutput = !array_key_exists('structured_output', $config) || filter_var($config['structured_output'], FILTER_VALIDATE_BOOLEAN);
    if ($structuredOutput) {
        $payload['response_format'] = ['type' => 'json_object'];
    }

    $startedAt = microtime(true);
    $http = kirpi_ai_http_json_post($endpoint, [
        'Authorization: Bearer ' . kirpi_ai_adapter_secret($adapter),
    ], $payload, $timeout);
    if ($structuredOutput && !($http['success'] ?? false) && in_array((int) ($http['status_code'] ?? 0), [400, 404, 415, 422], true)) {
        unset($payload['response_format']);
        $http = kirpi_ai_http_json_post($endpoint, [
            'Authorization: Bearer ' . kirpi_ai_adapter_secret($adapter),
        ], $payload, $timeout);
    }
    $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
    $statusCode = (int) ($http['status_code'] ?? 0);

    if (!($http['success'] ?? false)) {
        kirpi_ai_log_operation('provider_runtime_test', 'failed', [
            'model_adapter' => $adapterKey,
            'provider' => $provider,
            'reason' => 'provider_request_failed',
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
        ], $adapterKey, 'ai_model_adapter', null);

        return [
            'status' => 'failed',
            'reason' => 'provider_request_failed',
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
        ];
    }

    $content = trim((string) (($http['json']['choices'][0]['message']['content'] ?? '') ?: ''));
    $testResponse = kirpi_ai_extract_json_object_from_model_text($content);
    $responseOk = is_array($testResponse)
        && (string) ($testResponse['status'] ?? '') === 'ok'
        && (string) ($testResponse['purpose'] ?? '') === 'provider_test';

    kirpi_ai_log_operation('provider_runtime_test', $responseOk ? 'success' : 'failed', [
        'model_adapter' => $adapterKey,
        'provider' => $provider,
        'reason' => $responseOk ? 'response_contract_valid' : ($content === '' ? 'provider_empty_response' : 'provider_response_invalid'),
        'status_code' => $statusCode,
        'duration_ms' => $durationMs,
    ], $adapterKey, 'ai_model_adapter', null);

    return [
        'status' => $responseOk ? 'success' : 'failed',
        'reason' => $responseOk ? 'response_contract_valid' : ($content === '' ? 'provider_empty_response' : 'provider_response_invalid'),
        'status_code' => $statusCode,
        'duration_ms' => $durationMs,
    ];
}

function kirpi_ai_generate_sql_candidate(string $question, array $context = [], string $adapterKey = 'mock-sql-generator'): array
{
    $adapterKey = trim($adapterKey) !== '' ? trim($adapterKey) : 'mock-sql-generator';
    $adapter = kirpi_ai_model_adapter($adapterKey);

    if ($adapter === null && $adapterKey !== 'mock-sql-generator') {
        return kirpi_ai_blocked_sql_candidate($adapterKey, 'adapter_not_found');
    }

    if ($adapterKey === 'mock-sql-generator' || (string) ($adapter['provider'] ?? '') === 'mock') {
        return kirpi_ai_mock_generate_sql_candidate($question, array_merge($context, [
            'audit' => true,
        ]));
    }

    if ((int) ($adapter['is_enabled'] ?? 0) !== 1) {
        return kirpi_ai_blocked_sql_candidate($adapterKey, 'adapter_disabled');
    }

    if ((string) ($adapter['adapter_type'] ?? '') !== 'sql_generation') {
        return kirpi_ai_blocked_sql_candidate($adapterKey, 'adapter_type_not_supported', [
            'adapter_type' => (string) ($adapter['adapter_type'] ?? ''),
            'required_adapter_type' => 'sql_generation',
        ]);
    }

    if ((int) ($adapter['is_external'] ?? 0) === 1 && !kirpi_ai_adapter_secret_configured($adapter)) {
        return kirpi_ai_blocked_sql_candidate($adapterKey, 'external_adapter_not_configured', [
            'secret_policy' => 'env_or_setting_reference_required',
        ]);
    }

    if (!kirpi_ai_adapter_runtime_enabled($adapter)) {
        return kirpi_ai_blocked_sql_candidate($adapterKey, 'external_runtime_disabled', [
            'runtime_flag' => 'AI_EXTERNAL_MODEL_RUNTIME_ENABLED',
            'provider' => (string) ($adapter['provider'] ?? ''),
        ]);
    }

    return kirpi_ai_external_generate_sql_candidate($question, $context, $adapter);
}
