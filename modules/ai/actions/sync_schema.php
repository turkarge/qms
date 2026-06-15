<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => ai_lang('csrf_failed'),
    ], 419);
}

$result = kirpi_ai_sync_schema_registry_from_manifests();
$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);

if (($result['status'] ?? '') === 'error') {
    json_response([
        'status' => 'error',
        'message' => ai_lang('schema_sync_error'),
    ], 422);
}

if (($result['status'] ?? '') === 'partial') {
    if ($userId > 0) {
        kirpi_notify_user($userId, 'ai.schema_synced', [
            'entity_count' => (int) ($result['entity_count'] ?? 0),
            'field_count' => (int) ($result['field_count'] ?? 0),
        ], [
            'title' => 'AI schema sync kısmi tamamlandı',
            'message' => 'AI schema sync kısmi tamamlandı. Logları kontrol edin.',
            'source_module' => 'ai',
            'entity_type' => 'schema_registry',
            'email' => false,
        ]);
    }

    json_response([
        'status' => 'warning',
        'message' => ai_lang('schema_sync_partial'),
        'reload_page' => true,
    ], 207);
}

$entityCount = (int) ($result['entity_count'] ?? 0);
$fieldCount = (int) ($result['field_count'] ?? 0);
$indexCount = (int) ($result['index_count'] ?? 0);
if ($userId > 0) {
    kirpi_notify_user($userId, 'ai.schema_synced', [
        'entity_count' => $entityCount,
        'field_count' => $fieldCount,
        'index_count' => $indexCount,
    ], [
        'title' => 'AI schema registry güncellendi',
        'message' => $entityCount . ' entity, ' . $fieldCount . ' field ve ' . $indexCount . ' index kaydı senkronize edildi.',
        'source_module' => 'ai',
        'entity_type' => 'schema_registry',
        'email' => false,
    ]);
}

json_response([
    'status' => 'success',
    'message' => sprintf(
        ai_lang('schema_sync_success'),
        $entityCount,
        $fieldCount,
        $indexCount
    ),
    'reload_page' => true,
]);
