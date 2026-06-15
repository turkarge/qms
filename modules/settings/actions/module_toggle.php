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

$moduleKey = trim((string) ($_POST['module_key'] ?? ''));
$isEnabledRaw = trim((string) ($_POST['is_enabled'] ?? ''));
$isEnabled = in_array($isEnabledRaw, ['1', 'true', 'on'], true);

if ($moduleKey === '') {
    json_response([
        'status' => 'error',
        'message' => settings_lang('module_key_required'),
    ], 422);
}

try {
    kirpi_sync_module_registry();

    $currentUser = current_user();
    $updatedBy = (int) ($currentUser['id'] ?? 0);

    $result = kirpi_set_module_enabled($moduleKey, $isEnabled, $updatedBy > 0 ? $updatedBy : null);
    if (!($result['success'] ?? false)) {
        json_response([
            'status' => 'error',
            'message' => (string) ($result['message'] ?? settings_lang('module_update_failed')),
        ], 422);
    }

    kirpi_audit_log('module_toggle', 'settings', [
        'module_key' => $moduleKey,
        'is_enabled' => $isEnabled,
    ], 'module', null, 'success');

    kirpi_notify_current_user('settings.module_toggled', [
        'module_key' => $moduleKey,
        'is_enabled' => $isEnabled,
        'status_label' => $isEnabled ? 'aktif' : 'pasif',
    ], [
        'title' => 'Modül durumu güncellendi',
        'message' => $moduleKey . ' modülü ' . ($isEnabled ? 'aktif' : 'pasif') . ' yapıldı.',
        'source_module' => 'settings',
        'entity_type' => 'module',
    ]);

    json_response([
        'status' => 'success',
        'message' => (string) ($result['message'] ?? settings_lang('module_updated')),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('module toggle action error: ' . $e->getMessage());

    kirpi_audit_log('module_toggle', 'settings', [
        'module_key' => $moduleKey,
        'is_enabled' => $isEnabled,
        'error' => $e->getMessage(),
    ], 'module', null, 'failed');

    json_response([
        'status' => 'error',
        'message' => settings_lang('module_update_error'),
    ], 500);
}
