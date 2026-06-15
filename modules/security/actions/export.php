<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/security/language.php';

$format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
$rows = [];

$checks = [
    [security_lang('check_app_env_name'), APP_ENV, APP_ENV === 'production', security_lang('check_app_env_hint')],
    [security_lang('check_debug_name'), APP_DEBUG ? 'true' : 'false', APP_DEBUG === false, security_lang('check_debug_hint')],
    [security_lang('check_proxy_name'), APP_TRUST_PROXY ? 'true' : 'false', APP_TRUST_PROXY === true, security_lang('check_proxy_hint')],
    [security_lang('check_web_setup_name'), env_bool('AUTO_WEB_SETUP', true) ? security_lang('enabled') : security_lang('disabled'), env_bool('AUTO_WEB_SETUP', true) === false, security_lang('check_web_setup_hint')],
    [security_lang('check_setup_key_name'), (string) env('SETUP_KEY', '') !== '' ? security_lang('configured') : security_lang('empty'), (string) env('SETUP_KEY', '') !== '', security_lang('check_setup_key_hint')],
    [security_lang('check_session_secure_name'), ini_get('session.cookie_secure') === '1' ? security_lang('enabled') : security_lang('disabled'), ini_get('session.cookie_secure') === '1', security_lang('check_session_secure_hint')],
    [security_lang('check_session_samesite_name'), (string) ini_get('session.cookie_samesite'), strtolower((string) ini_get('session.cookie_samesite')) === 'lax', security_lang('check_session_samesite_hint')],
];

foreach ($checks as $check) {
    $rows[] = ['security_check', $check[0], (string) $check[1], $check[2] ? 'OK' : security_lang('status_warn'), $check[3]];
}

$paths = [
    'uploads' => BASE_PATH . '/uploads',
    'uploads/avatars' => BASE_PATH . '/uploads/avatars',
    'uploads/documents' => BASE_PATH . '/uploads/documents',
    'logs' => BASE_PATH . '/logs',
    'storage' => BASE_PATH . '/storage',
];
foreach ($paths as $label => $path) {
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    $perm = file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : '----';
    $rows[] = ['directory', $label, $path, $exists && $writable ? 'OK' : security_lang('status_warn'), 'exists=' . ($exists ? '1' : '0') . ', writable=' . ($writable ? '1' : '0') . ', perm=' . $perm];
}

try {
    $stmt = db()->query('SHOW TABLES');
    foreach ($stmt->fetchAll(PDO::FETCH_NUM) ?: [] as $row) {
        if (isset($row[0])) {
            $rows[] = ['db_table', (string) $row[0], '', 'OK', ''];
        }
    }
} catch (Throwable $e) {
    $rows[] = ['db_table', 'database', '', 'FAIL', $e->getMessage()];
}

kirpi_export_response($format, 'security-monitor-' . date('Ymd-His'), [
    'Section',
    security_lang('col_check'),
    security_lang('col_value'),
    security_lang('col_status'),
    security_lang('col_note'),
], $rows);
