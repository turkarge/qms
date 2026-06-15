<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/auth/language.php';

require_action('POST', true);

$logoutUser = current_user();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    if ($isAjax) {
        json_response([
            'status' => 'error',
            'message' => auth_lang('csrf_failed'),
        ], 419);
    }

    set_flash_message('danger', auth_lang('csrf_failed'));
    redirect(base_url('auth/login'));
}

kirpi_audit_log('logout', 'auth', [
    'email' => (string) ($logoutUser['email'] ?? ''),
    'role_id' => (int) ($logoutUser['role_id'] ?? 0),
    'role_name' => (string) ($logoutUser['role_name'] ?? ''),
], 'session', null, 'success');

kirpi_delete_current_user_session();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

if ($isAjax) {
    json_response([
        'status' => 'success',
        'message' => auth_lang('logout_success'),
        'redirect' => base_url('auth/login'),
    ]);
}

redirect(base_url('auth/login'));
