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

if (!kirpi_settings_table_ready()) {
    json_response([
        'status' => 'error',
        'message' => settings_lang('settings_table_not_ready'),
    ], 422);
}

$appName = trim((string) ($_POST['app_name'] ?? ''));
$mailHost = trim((string) ($_POST['mail_host'] ?? ''));
$mailPort = trim((string) ($_POST['mail_port'] ?? ''));
$mailUsername = trim((string) ($_POST['mail_username'] ?? ''));
$mailPassword = (string) ($_POST['mail_password'] ?? '');
$mailEncryption = strtolower(trim((string) ($_POST['mail_encryption'] ?? 'tls')));
$mailFromAddress = trim((string) ($_POST['mail_from_address'] ?? ''));
$mailFromName = trim((string) ($_POST['mail_from_name'] ?? ''));
$apiEnabled = isset($_POST['api_enabled']) ? '1' : '0';

if ($appName === '') {
    json_response([
        'status' => 'error',
        'message' => settings_lang('app_name_required'),
    ], 422);
}

if ($mailPort !== '' && (!ctype_digit($mailPort) || (int) $mailPort <= 0)) {
    json_response([
        'status' => 'error',
        'message' => settings_lang('mail_port_invalid'),
    ], 422);
}

if (!in_array($mailEncryption, ['tls', 'ssl', 'none'], true)) {
    json_response([
        'status' => 'error',
        'message' => settings_lang('mail_encryption_invalid'),
    ], 422);
}

if ($mailFromAddress !== '' && !filter_var($mailFromAddress, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'status' => 'error',
        'message' => settings_lang('mail_from_invalid'),
    ], 422);
}

$currentUser = current_user();
$updatedBy = (int) ($currentUser['id'] ?? 0);
$updatedBy = $updatedBy > 0 ? $updatedBy : null;

try {
    $changes = [];

    kirpi_setting_set('app.name', $appName, $updatedBy);
    $changes[] = 'app.name';

    kirpi_setting_set('api.enabled', $apiEnabled, $updatedBy);
    $changes[] = 'api.enabled';

    kirpi_setting_set('mail.host', $mailHost, $updatedBy);
    $changes[] = 'mail.host';

    kirpi_setting_set('mail.port', $mailPort !== '' ? $mailPort : '587', $updatedBy);
    $changes[] = 'mail.port';

    kirpi_setting_set('mail.username', $mailUsername, $updatedBy);
    $changes[] = 'mail.username';

    if (trim($mailPassword) !== '') {
        kirpi_setting_set('mail.password', $mailPassword, $updatedBy, true);
        $changes[] = 'mail.password';
    }

    kirpi_setting_set('mail.encryption', $mailEncryption, $updatedBy);
    $changes[] = 'mail.encryption';

    kirpi_setting_set('mail.from_address', $mailFromAddress, $updatedBy);
    $changes[] = 'mail.from_address';

    kirpi_setting_set('mail.from_name', $mailFromName, $updatedBy);
    $changes[] = 'mail.from_name';

    kirpi_audit_log('update', 'settings', [
        'changed_keys' => $changes,
    ], 'settings', null, 'success');

    kirpi_notify_current_user('settings.updated', [
        'changed_keys' => $changes,
        'changed_count' => count($changes),
    ], [
        'title' => 'Ayarlar güncellendi',
        'message' => count($changes) . ' ayar başarıyla güncellendi.',
        'source_module' => 'settings',
        'entity_type' => 'settings',
    ]);

    json_response([
        'status' => 'success',
        'message' => settings_lang('settings_updated'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('settings update error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => settings_lang('settings_update_error'),
    ], 500);
}
