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

if (!kirpi_ai_models_table_ready()) {
    json_response([
        'status' => 'error',
        'message' => ai_lang('adapter_missing'),
    ], 422);
}

$adapterKey = trim((string) ($_POST['adapter_key'] ?? ''));
if ($adapterKey === '') {
    json_response([
        'status' => 'error',
        'message' => ai_lang('adapter_key_required', 'Adapter key zorunlu.'),
    ], 422);
}

$currentUser = current_user();
$updatedBy = (int) ($currentUser['id'] ?? 0);
$updatedBy = $updatedBy > 0 ? $updatedBy : null;

$result = kirpi_ai_update_model_adapter($adapterKey, $_POST, $updatedBy);
if (empty($result['success'])) {
    $messageKey = (string) ($result['message'] ?? 'adapter_update_failed');
    $fallbackMessages = [
        'adapter_not_found' => 'Adapter bulunamadı.',
        'provider_invalid' => 'Provider değeri geçersiz.',
        'model_missing' => 'Model adı zorunlu.',
        'adapter_type_invalid' => 'Adapter tipi geçersiz.',
        'base_url_missing' => 'OpenAI-compatible provider için base URL zorunlu.',
        'api_key_env_missing' => 'Env secret kaynağı için API key env adı zorunlu.',
        'api_key_save_failed' => 'API key secret olarak kaydedilemedi.',
        'config_encode_failed' => 'Adapter config üretilemedi.',
        'adapter_update_failed' => 'Adapter güncellenemedi.',
    ];

    json_response([
        'status' => 'error',
        'message' => ai_lang($messageKey, $fallbackMessages[$messageKey] ?? 'Adapter güncellenemedi.'),
    ], 422);
}

$changedKeys = array_values(array_filter((array) ($result['changed_keys'] ?? [])));
kirpi_audit_log('provider_update', 'ai', [
    'adapter_key' => $adapterKey,
    'changed_keys' => $changedKeys,
    'secret_value_changed' => in_array('api_key_ref_secret', $changedKeys, true),
], 'ai_model_adapter', null, 'success');

kirpi_ai_log_operation('provider_update', 'success', [
    'adapter_key' => $adapterKey,
    'changed_keys' => $changedKeys,
    'secret_value_changed' => in_array('api_key_ref_secret', $changedKeys, true),
], $adapterKey, 'ai_model_adapter', null);

kirpi_notify_current_user('ai.provider_updated', [
    'adapter_key' => $adapterKey,
    'changed_count' => count($changedKeys),
], [
    'title' => ai_lang('provider_updated', 'Provider ayarları güncellendi'),
    'message' => $adapterKey . ' adapter ayarları güncellendi.',
    'source_module' => 'ai',
    'entity_type' => 'ai_model_adapter',
]);

json_response([
    'status' => 'success',
    'message' => ai_lang('provider_updated', 'Provider ayarları güncellendi.'),
    'reload_page' => true,
]);
