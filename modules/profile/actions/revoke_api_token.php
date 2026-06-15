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
$tokenId = (int) ($_POST['token_id'] ?? 0);

if ($userId <= 0 || $roleName !== 'Super Admin') {
    set_flash_message('danger', profile_lang('super_admin_only_manage'));
    redirect(base_url('profile/view'));
}

if ($tokenId <= 0) {
    set_flash_message('warning', profile_lang('invalid_token_record'));
    redirect(base_url('profile/view'));
}

if (!api_token_table_ready()) {
    set_flash_message('warning', profile_lang('token_table_not_ready'));
    redirect(base_url('profile/view'));
}

try {
    $ok = api_revoke_token_for_user($tokenId, $userId);
    if (!$ok) {
        set_flash_message('warning', profile_lang('token_not_found_or_revoked'));
        redirect(base_url('profile/view'));
    }

    if (isset($_SESSION['profile_api_token_copy_map']) && is_array($_SESSION['profile_api_token_copy_map'])) {
        unset($_SESSION['profile_api_token_copy_map'][(string) $tokenId]);
    }

    kirpi_audit_log('revoke_token', 'api', [
        'token_id' => $tokenId,
    ], 'api_token', $tokenId, 'success');

    kirpi_notify_current_user('api.token_revoked', [
        'token_id' => $tokenId,
    ], [
        'title' => 'API token iptal edildi',
        'message' => 'API token #' . $tokenId . ' iptal edildi.',
        'source_module' => 'api',
        'entity_type' => 'api_token',
        'entity_id' => $tokenId,
    ]);

    set_flash_message('success', profile_lang('token_revoked'));
    redirect(base_url('profile/view'));
} catch (Throwable $e) {
    error_log('profile revoke api token error: ' . $e->getMessage());

    kirpi_audit_log('revoke_token', 'api', [
        'token_id' => $tokenId,
        'error' => $e->getMessage(),
    ], 'api_token', $tokenId, 'failed');

    set_flash_message('danger', profile_lang('token_revoke_error'));
    redirect(base_url('profile/view'));
}
