<?php

function kirpi_database_table_count(): int
{
    try {
        $stmt = db()->query("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
        ");

        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('Database table count error: ' . $e->getMessage());
        return 0;
    }
}

function kirpi_database_has_any_tables(): bool
{
    return kirpi_database_table_count() > 0;
}

function kirpi_run_sql_file(string $filePath): int
{
    if (!is_file($filePath)) {
        throw new RuntimeException('Schema file not found: ' . $filePath);
    }

    $schemaSql = file_get_contents($filePath);
    if ($schemaSql === false) {
        throw new RuntimeException('Schema file read failed: ' . $filePath);
    }

    $statementCount = 0;

    foreach (array_filter(array_map('trim', explode(';', $schemaSql))) as $statement) {
        if ($statement === '') {
            continue;
        }

        db()->exec($statement);
        $statementCount++;
    }

    return $statementCount;
}

function kirpi_module_schema_files(): array
{
    $paths = [];
    $moduleDirs = glob(BASE_PATH . '/modules/*', GLOB_ONLYDIR) ?: [];
    sort($moduleDirs);

    foreach ($moduleDirs as $moduleDir) {
        $schemaFiles = glob($moduleDir . '/database/*.sql') ?: [];
        sort($schemaFiles);

        foreach ($schemaFiles as $schemaFile) {
            $paths[] = $schemaFile;
        }
    }

    return $paths;
}

function kirpi_install_database_schema(): array
{
    $coreStatements = kirpi_run_sql_file(BASE_PATH . '/database/core.sql');
    $moduleStatements = 0;
    $installedFiles = [];

    foreach (kirpi_module_schema_files() as $schemaFile) {
        $count = kirpi_run_sql_file($schemaFile);
        $moduleStatements += $count;
        $installedFiles[] = [
            'file' => str_replace(BASE_PATH . '/', '', $schemaFile),
            'statements' => $count,
        ];
    }

    if (function_exists('sync_permission_catalog') && db_table_exists('permissions') && db_table_exists('role_permissions')) {
        sync_permission_catalog();
    }

    if (function_exists('kirpi_sync_module_registry')) {
        kirpi_sync_module_registry();
    }

    return [
        'core_statements' => $coreStatements,
        'module_statements' => $moduleStatements,
        'installed_files' => $installedFiles,
    ];
}

function kirpi_extract_tables_from_sql(string $sqlContent): array
{
    $tables = [];

    if (preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?/i', $sqlContent, $matches)) {
        foreach ($matches[1] as $tableName) {
            $name = trim((string) $tableName);
            if ($name !== '') {
                $tables[] = $name;
            }
        }
    }

    return array_values(array_unique($tables));
}

function kirpi_expected_indexes_map(): array
{
    return [
        'users' => [
            'idx_users_is_active_id' => ['is_active', 'id'],
            'idx_users_default_company_id' => ['default_company_id'],
        ],
        'mail_logs' => [
            'idx_mail_logs_created_status_id' => ['created_at', 'status', 'id'],
        ],
        'audit_logs' => [
            'idx_audit_logs_created_status_id' => ['created_at', 'status', 'id'],
        ],
        'api_request_logs' => [
            'idx_api_request_logs_created_route_method_status' => ['created_at', 'route_path', 'request_method', 'status_code'],
            'idx_api_request_logs_created_status_id' => ['created_at', 'status_code', 'id'],
        ],
        'db_backups' => [
            'idx_db_backups_created_at_id' => ['created_at', 'id'],
        ],
        'notifications' => [
            'idx_notifications_source' => ['source_module', 'entity_type', 'entity_id'],
            'idx_notifications_template' => ['template_key'],
        ],
    ];
}

function kirpi_db_index_exists(string $tableName, string $indexName): bool
{
    try {
        $stmt = db()->prepare("
            SELECT COUNT(*)
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
              AND index_name = :index_name
        ");
        $stmt->execute([
            ':table_name' => $tableName,
            ':index_name' => $indexName,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function kirpi_missing_indexes_report(): array
{
    $expectedMap = kirpi_expected_indexes_map();
    $missingByTable = [];
    $requiredCount = 0;
    $missingCount = 0;

    foreach ($expectedMap as $tableName => $indexes) {
        if (!db_table_exists($tableName, true)) {
            continue;
        }

        foreach ((array) $indexes as $indexName => $columns) {
            $requiredCount++;

            if (kirpi_db_index_exists((string) $tableName, (string) $indexName)) {
                continue;
            }

            $missingCount++;
            if (!isset($missingByTable[$tableName])) {
                $missingByTable[$tableName] = [];
            }

            $missingByTable[$tableName][] = [
                'name' => (string) $indexName,
                'columns' => array_map('strval', (array) $columns),
            ];
        }
    }

    return [
        'required_index_count' => $requiredCount,
        'missing_index_count' => $missingCount,
        'missing_by_table' => $missingByTable,
    ];
}

function kirpi_expected_columns_map(): array
{
    return [
        'users' => [
            'lock_enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'lock_pin_hash' => 'VARCHAR(255) NULL',
            'session_version' => 'INT NOT NULL DEFAULT 0',
            'default_company_id' => 'BIGINT UNSIGNED NULL',
        ],
        'notifications' => [
            'template_key' => 'VARCHAR(120) NULL',
            'source_module' => 'VARCHAR(80) NULL',
            'entity_type' => 'VARCHAR(80) NULL',
            'entity_id' => 'BIGINT UNSIGNED NULL',
            'data_json' => 'LONGTEXT NULL',
        ],
    ];
}

function kirpi_db_column_exists_raw(string $tableName, string $columnName): bool
{
    try {
        $stmt = db()->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
              AND column_name = :column_name
        ");
        $stmt->execute([
            ':table_name' => $tableName,
            ':column_name' => $columnName,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function kirpi_missing_columns_report(): array
{
    $expectedMap = kirpi_expected_columns_map();
    $missingByTable = [];
    $requiredCount = 0;
    $missingCount = 0;

    foreach ($expectedMap as $tableName => $columns) {
        if (!db_table_exists($tableName, true)) {
            continue;
        }

        foreach ((array) $columns as $columnName => $definition) {
            $requiredCount++;

            if (kirpi_db_column_exists_raw((string) $tableName, (string) $columnName)) {
                continue;
            }

            $missingCount++;
            if (!isset($missingByTable[$tableName])) {
                $missingByTable[$tableName] = [];
            }

            $missingByTable[$tableName][] = [
                'name' => (string) $columnName,
                'definition' => (string) $definition,
            ];
        }
    }

    return [
        'required_column_count' => $requiredCount,
        'missing_column_count' => $missingCount,
        'missing_by_table' => $missingByTable,
    ];
}

function kirpi_install_missing_columns(): array
{
    $before = kirpi_missing_columns_report();
    $installed = [];

    foreach ((array) ($before['missing_by_table'] ?? []) as $tableName => $columns) {
        foreach ((array) $columns as $columnDef) {
            $columnName = (string) ($columnDef['name'] ?? '');
            $definition = (string) ($columnDef['definition'] ?? '');

            if ($columnName === '' || $definition === '') {
                continue;
            }

            $sql = sprintf(
                'ALTER TABLE `%s` ADD COLUMN `%s` %s',
                str_replace('`', '', (string) $tableName),
                str_replace('`', '', $columnName),
                $definition
            );

            try {
                db()->exec($sql);
                db_column_exists((string) $tableName, $columnName, true);
                $installed[] = [
                    'table' => (string) $tableName,
                    'column' => $columnName,
                ];
            } catch (Throwable $e) {
                error_log('Missing column install failed [' . $tableName . '.' . $columnName . ']: ' . $e->getMessage());
            }
        }
    }

    $after = kirpi_missing_columns_report();

    return [
        'before' => $before,
        'after' => $after,
        'installed_columns' => $installed,
    ];
}

function kirpi_install_missing_indexes(): array
{
    $before = kirpi_missing_indexes_report();
    $installed = [];

    foreach ((array) ($before['missing_by_table'] ?? []) as $tableName => $indexes) {
        foreach ((array) $indexes as $indexDef) {
            $indexName = (string) ($indexDef['name'] ?? '');
            $columns = array_map('strval', (array) ($indexDef['columns'] ?? []));

            if ($indexName === '' || empty($columns)) {
                continue;
            }

            $columnSql = implode(', ', array_map(static fn(string $col): string => '`' . str_replace('`', '', $col) . '`', $columns));
            $sql = sprintf(
                'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
                str_replace('`', '', (string) $tableName),
                str_replace('`', '', $indexName),
                $columnSql
            );

            try {
                db()->exec($sql);
                $installed[] = [
                    'table' => (string) $tableName,
                    'index' => $indexName,
                    'columns' => $columns,
                ];
            } catch (Throwable $e) {
                error_log('Missing index install failed [' . $tableName . '.' . $indexName . ']: ' . $e->getMessage());
            }
        }
    }

    $after = kirpi_missing_indexes_report();

    return [
        'before' => $before,
        'after' => $after,
        'installed_indexes' => $installed,
    ];
}

function kirpi_schema_file_tables(string $filePath): array
{
    if (!is_file($filePath)) {
        return [];
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        return [];
    }

    return kirpi_extract_tables_from_sql($content);
}

function kirpi_schema_files_with_tables(): array
{
    $schemaFiles = [BASE_PATH . '/database/core.sql'];
    foreach (kirpi_module_schema_files() as $moduleSchemaFile) {
        $schemaFiles[] = $moduleSchemaFile;
    }

    $result = [];
    foreach ($schemaFiles as $filePath) {
        $tables = kirpi_schema_file_tables($filePath);
        if (empty($tables)) {
            continue;
        }

        $result[] = [
            'file' => $filePath,
            'tables' => $tables,
        ];
    }

    return $result;
}

function kirpi_missing_tables_report(): array
{
    $missingTables = [];
    $missingByFile = [];
    $requiredCount = 0;

    foreach (kirpi_schema_files_with_tables() as $item) {
        $filePath = (string) ($item['file'] ?? '');
        $tables = (array) ($item['tables'] ?? []);

        if ($filePath === '' || empty($tables)) {
            continue;
        }

        $requiredCount += count($tables);
        $fileMissing = [];

        foreach ($tables as $tableName) {
            if (!db_table_exists($tableName, true)) {
                $fileMissing[] = $tableName;
                $missingTables[] = $tableName;
            }
        }

        if (!empty($fileMissing)) {
            $missingByFile[] = [
                'file' => str_replace(BASE_PATH . '/', '', $filePath),
                'tables' => array_values(array_unique($fileMissing)),
            ];
        }
    }

    $missingTables = array_values(array_unique($missingTables));

    return [
        'required_table_count' => $requiredCount,
        'missing_table_count' => count($missingTables),
        'missing_tables' => $missingTables,
        'missing_by_file' => $missingByFile,
    ];
}

function kirpi_install_missing_database_schema(): array
{
    $report = kirpi_missing_tables_report();
    $installedFiles = [];
    $installedStatements = 0;

    foreach ($report['missing_by_file'] as $item) {
        $relativeFile = (string) ($item['file'] ?? '');
        if ($relativeFile === '') {
            continue;
        }

        $fullPath = BASE_PATH . '/' . $relativeFile;
        $count = kirpi_run_sql_file($fullPath);
        $installedStatements += $count;

        $installedFiles[] = [
            'file' => $relativeFile,
            'statements' => $count,
            'target_tables' => $item['tables'] ?? [],
        ];
    }

    if (function_exists('sync_permission_catalog') && db_table_exists('permissions') && db_table_exists('role_permissions')) {
        sync_permission_catalog();
    }

    if (function_exists('kirpi_sync_module_registry')) {
        kirpi_sync_module_registry();
    }

    $columnResult = kirpi_install_missing_columns();
    $indexResult = kirpi_install_missing_indexes();
    $after = kirpi_missing_tables_report();

    return [
        'before' => $report,
        'after' => $after,
        'installed_files' => $installedFiles,
        'installed_statements' => $installedStatements,
        'columns' => $columnResult,
        'indexes' => $indexResult,
    ];
}

function kirpi_try_auto_setup_if_empty(): bool
{
    static $ran = false;

    if ($ran) {
        return false;
    }
    $ran = true;

    if (!env_bool('AUTO_WEB_SETUP', true)) {
        return false;
    }

    if (kirpi_database_has_any_tables()) {
        return false;
    }

    try {
        kirpi_install_database_schema();
        return true;
    } catch (Throwable $e) {
        error_log('Auto setup failed: ' . $e->getMessage());
        return false;
    }
}

function kirpi_try_auto_setup_if_missing(): bool
{
    static $ran = false;

    if ($ran) {
        return false;
    }
    $ran = true;

    if (!env_bool('AUTO_DB_ENSURE_MISSING', false)) {
        return false;
    }

    try {
        $ranInstall = false;
        $tableReport = kirpi_missing_tables_report();

        if (($tableReport['missing_table_count'] ?? 0) > 0) {
            kirpi_install_missing_database_schema();
            $ranInstall = true;
        }

        if (env_bool('AUTO_DB_ENSURE_COLUMNS', true)) {
            $columnReport = kirpi_missing_columns_report();
            if (($columnReport['missing_column_count'] ?? 0) > 0) {
                kirpi_install_missing_columns();
                $ranInstall = true;
            }
        }

        if (env_bool('AUTO_DB_ENSURE_INDEXES', true)) {
            $indexReport = kirpi_missing_indexes_report();
            if (($indexReport['missing_index_count'] ?? 0) > 0) {
                kirpi_install_missing_indexes();
                $ranInstall = true;
            }
        }

        return $ranInstall;
    } catch (Throwable $e) {
        error_log('Auto missing schema setup failed: ' . $e->getMessage());
        return false;
    }
}
