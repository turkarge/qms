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

$adapterKey = trim((string) ($_POST['adapter_key'] ?? ''));
if ($adapterKey === '') {
    json_response([
        'status' => 'error',
        'message' => ai_lang('adapter_key_required', 'Adapter key zorunlu.'),
    ], 422);
}

$result = kirpi_ai_test_model_adapter($adapterKey);
$status = (string) ($result['status'] ?? 'failed');
$reason = (string) ($result['reason'] ?? 'provider_test_failed');
$fallbackMessages = [
    'adapter_not_found' => 'Adapter bulunamadı.',
    'mock_adapter_not_testable' => 'Mock adapter için canlı provider testi yapılmaz.',
    'adapter_disabled' => 'Adapter pasif. Önce adapter kaydını aktif edin.',
    'provider_runtime_not_supported' => 'Bu provider tipi canlı test için desteklenmiyor.',
    'external_adapter_not_configured' => 'Provider secret yapılandırması eksik.',
    'external_runtime_disabled' => 'Global runtime kapısı veya adapter runtime onayı kapalı.',
    'provider_base_url_missing' => 'Provider base URL eksik.',
    'provider_model_missing' => 'Provider model adı eksik.',
    'provider_request_failed' => 'Provider isteği başarısız oldu.',
    'provider_empty_response' => 'Provider boş cevap döndürdü.',
    'provider_response_invalid' => 'Provider yanıt verdi ancak beklenen JSON sözleşmesini karşılamadı.',
    'response_contract_valid' => 'Provider bağlantısı ve JSON yanıt sözleşmesi doğrulandı.',
    'response_received' => 'Provider bağlantı testi başarılı.',
];

kirpi_audit_log('provider_runtime_test', 'ai', [
    'adapter_key' => $adapterKey,
    'result_status' => $status,
    'reason' => $reason,
    'status_code' => (int) ($result['status_code'] ?? 0),
    'duration_ms' => (int) ($result['duration_ms'] ?? 0),
], 'ai_model_adapter', null, $status === 'success' ? 'success' : 'failed');

json_response([
    'status' => $status === 'success' ? 'success' : 'error',
    'message' => ai_lang($reason, $fallbackMessages[$reason] ?? 'Provider testi tamamlanamadı.'),
    'details' => [
        'adapter_key' => $adapterKey,
        'reason' => $reason,
        'status_code' => (int) ($result['status_code'] ?? 0),
        'duration_ms' => (int) ($result['duration_ms'] ?? 0),
    ],
], $status === 'success' ? 200 : 422);
