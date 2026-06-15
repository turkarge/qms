<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/auth/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('csrf_failed'),
    ], 419);
}

$user = current_user();
$userId = (int) ($user['id'] ?? 0);
$pin = trim((string) ($_POST['lock_pin'] ?? ''));

if ($userId <= 0) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('invalid_session'),
        'redirect' => base_url('auth/login'),
    ], 403);
}

if (!kirpi_session_lock_state()) {
    json_response([
        'status' => 'success',
        'message' => auth_lang('session_already_open'),
        'redirect' => base_url(APP_DEFAULT_ROUTE),
    ]);
}

if (!preg_match('/^\d{4}$/', $pin)) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('key_must_be_4_digits'),
    ], 422);
}

if (!kirpi_auth_lock_schema_ready()) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('lock_infra_not_ready'),
    ], 422);
}

try {
    $stmt = db()->prepare("
        SELECT lock_enabled, lock_pin_hash
        FROM users
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        ':id' => $userId,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $lockEnabled = (int) ($row['lock_enabled'] ?? 0) === 1;
    $pinHash = (string) ($row['lock_pin_hash'] ?? '');

    if (!$lockEnabled || $pinHash === '') {
        kirpi_unlock_session();

        json_response([
            'status' => 'warning',
            'message' => auth_lang('lock_disabled_session_opened'),
            'redirect' => base_url(APP_DEFAULT_ROUTE),
        ]);
    }

    if (!password_verify($pin, $pinHash)) {
        kirpi_audit_log('unlock_failed', 'auth', [
            'user_id' => $userId,
            'reason' => 'invalid_lock_pin',
        ], 'session', null, 'failed');

        json_response([
            'status' => 'error',
            'message' => auth_lang('key_wrong'),
        ], 401);
    }

    kirpi_unlock_session();

    kirpi_audit_log('unlock', 'auth', [
        'user_id' => $userId,
    ], 'session', null, 'success');

    $redirect = $_SESSION['redirect_to'] ?? base_url(APP_DEFAULT_ROUTE);
    unset($_SESSION['redirect_to']);

    json_response([
        'status' => 'success',
        'message' => auth_lang('lock_opened'),
        'redirect' => (string) $redirect,
    ]);
} catch (Throwable $e) {
    error_log('auth unlock action error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => auth_lang('unlock_error'),
    ], 500);
}
