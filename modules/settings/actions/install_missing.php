<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/settings/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => settings_lang('csrf_failed'),
    ], 419);
}

try {
    $result = kirpi_install_missing_database_schema();
    $beforeMissing = (int) ($result['before']['missing_table_count'] ?? 0);
    $afterMissing = (int) ($result['after']['missing_table_count'] ?? 0);
    $beforeMissingColumns = (int) ($result['columns']['before']['missing_column_count'] ?? 0);
    $afterMissingColumns = (int) ($result['columns']['after']['missing_column_count'] ?? 0);
    $beforeMissingIndexes = (int) ($result['indexes']['before']['missing_index_count'] ?? 0);
    $afterMissingIndexes = (int) ($result['indexes']['after']['missing_index_count'] ?? 0);

    kirpi_audit_log('install_missing_schema', 'settings', [
        'before_missing_table_count' => $beforeMissing,
        'after_missing_table_count' => $afterMissing,
        'before_missing_column_count' => $beforeMissingColumns,
        'after_missing_column_count' => $afterMissingColumns,
        'before_missing_index_count' => $beforeMissingIndexes,
        'after_missing_index_count' => $afterMissingIndexes,
        'installed_files' => $result['installed_files'] ?? [],
        'installed_columns' => $result['columns']['installed_columns'] ?? [],
        'installed_indexes' => $result['indexes']['installed_indexes'] ?? [],
    ], 'schema', null, 'success');

    kirpi_notify_current_user('settings.schema_installed', [
        'before_missing_table_count' => $beforeMissing,
        'after_missing_table_count' => $afterMissing,
        'before_missing_column_count' => $beforeMissingColumns,
        'after_missing_column_count' => $afterMissingColumns,
        'before_missing_index_count' => $beforeMissingIndexes,
        'after_missing_index_count' => $afterMissingIndexes,
    ], [
        'title' => 'Eksik kurulum kontrolü tamamlandı',
        'message' => 'Eksik tablo, kolon ve indeks kurulumu kontrol edildi.',
        'source_module' => 'settings',
        'entity_type' => 'schema',
    ]);

    if ($beforeMissing <= 0 && $beforeMissingColumns <= 0 && $beforeMissingIndexes <= 0) {
        json_response([
            'status' => 'success',
            'message' => settings_lang('no_missing_schema'),
            'reload_page' => true,
        ]);
    }

    if ($afterMissing <= 0 && $afterMissingColumns <= 0 && $afterMissingIndexes <= 0) {
        json_response([
            'status' => 'success',
            'message' => settings_lang('missing_installed'),
            'reload_page' => true,
        ]);
    }

    json_response([
        'status' => 'warning',
        'message' => settings_lang('still_missing'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('install missing schema action error: ' . $e->getMessage());

    kirpi_audit_log('install_missing_schema', 'settings', [
        'error' => $e->getMessage(),
    ], 'schema', null, 'failed');

    json_response([
        'status' => 'error',
        'message' => settings_lang('install_missing_error'),
    ], 500);
}
