<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/profile/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash_message('danger', profile_lang('csrf_failed'));
    redirect(base_url('profile/view'));
}

$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);
$roleName = (string) ($currentUser['role_name'] ?? '');

if ($userId <= 0 || $roleName !== 'Super Admin') {
    set_flash_message('danger', profile_lang('super_admin_only_create'));
    redirect(base_url('profile/view'));
}

if (!api_is_enabled()) {
    set_flash_message('warning', profile_lang('api_disabled_token'));
    redirect(base_url('profile/view'));
}

if (!api_token_table_ready()) {
    set_flash_message('warning', profile_lang('api_table_not_ready'));
    redirect(base_url('profile/view'));
}

$tokenName = trim((string) ($_POST['token_name'] ?? 'profile-token'));
$tokenName = $tokenName !== '' ? $tokenName : 'profile-token';
$ttlOption = trim((string) ($_POST['ttl_option'] ?? '1_month'));
$scopeOption = trim((string) ($_POST['scope_option'] ?? 'full_access'));

$ttlMap = [
    '24h' => 24 * 60 * 60,
    '1_month' => 30 * 24 * 60 * 60,
    '3_months' => 90 * 24 * 60 * 60,
    '6_months' => 180 * 24 * 60 * 60,
    '1_year' => 365 * 24 * 60 * 60,
    'unlimited' => -1,
];

$ttlSeconds = $ttlMap[$ttlOption] ?? $ttlMap['1_month'];

$scopeMap = [
    'full_access' => ['*'],
    'profile_read' => ['profile:read'],
    'users_read' => ['profile:read', 'users:read'],
    'users_manage' => ['profile:read', 'users:read', 'users:create', 'users:update', 'users:status'],
];
$scopes = $scopeMap[$scopeOption] ?? $scopeMap['full_access'];

try {
    $issued = api_issue_token_for_user($userId, $tokenName, $ttlSeconds, $scopes);
    if (!$issued) {
        set_flash_message('danger', profile_lang('token_create_failed'));
        redirect(base_url('profile/view'));
    }

    $_SESSION['profile_api_token_once'] = [
        'token_id' => (int) ($issued['token_id'] ?? 0),
        'token' => (string) ($issued['token'] ?? ''),
        'expires_at' => (string) ($issued['expires_at'] ?? ''),
        'token_name' => $tokenName,
        'ttl_option' => $ttlOption,
        'is_unlimited' => (bool) ($issued['is_unlimited'] ?? false),
        'scopes' => (array) ($issued['scopes'] ?? $scopes),
    ];

    $newTokenId = (int) ($issued['token_id'] ?? 0);
    if ($newTokenId > 0 && !empty($issued['token'])) {
        if (!isset($_SESSION['profile_api_token_copy_map']) || !is_array($_SESSION['profile_api_token_copy_map'])) {
            $_SESSION['profile_api_token_copy_map'] = [];
        }

        $_SESSION['profile_api_token_copy_map'][(string) $newTokenId] = (string) $issued['token'];
    }

    kirpi_audit_log('create_token', 'api', [
        'token_id' => (int) ($issued['token_id'] ?? 0),
        'token_name' => $tokenName,
        'expires_at' => (string) ($issued['expires_at'] ?? ''),
        'ttl_option' => $ttlOption,
        'scope_option' => $scopeOption,
        'scopes' => (array) ($issued['scopes'] ?? $scopes),
    ], 'api_token', null, 'success');

    kirpi_notify_current_user('api.token_created', [
        'token_id' => (int) ($issued['token_id'] ?? 0),
        'token_name' => $tokenName,
        'expires_at' => (string) ($issued['expires_at'] ?? ''),
        'ttl_option' => $ttlOption,
        'scope_option' => $scopeOption,
    ], [
        'title' => 'API token oluşturuldu',
        'message' => '"' . $tokenName . '" API token kaydı oluşturuldu.',
        'source_module' => 'api',
        'entity_type' => 'api_token',
        'entity_id' => (int) ($issued['token_id'] ?? 0),
    ]);

    set_flash_message('success', profile_lang('token_created_once'));
    redirect(base_url('profile/view'));
} catch (Throwable $e) {
    error_log('profile create api token error: ' . $e->getMessage());

    kirpi_audit_log('create_token', 'api', [
        'token_name' => $tokenName,
        'error' => $e->getMessage(),
    ], 'api_token', null, 'failed');

    set_flash_message('danger', profile_lang('token_create_error'));
    redirect(base_url('profile/view'));
}
