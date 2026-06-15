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
if ($userId <= 0) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('invalid_session'),
    ], 403);
}

if (!kirpi_auth_lock_schema_ready()) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('lock_infra_missing'),
    ], 422);
}

try {
    $stmt = db()->prepare("\n        SELECT lock_enabled, lock_pin_hash\n        FROM users\n        WHERE id = :id\n        LIMIT 1\n    ");
    $stmt->execute([
        ':id' => $userId,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $lockEnabled = (int) ($row['lock_enabled'] ?? 0) === 1;
    $pinHash = (string) ($row['lock_pin_hash'] ?? '');

    if (!$lockEnabled || $pinHash === '') {
        json_response([
            'status' => 'warning',
            'message' => auth_lang('lock_not_active'),
            'redirect' => base_url('profile/view'),
        ]);
    }

    kirpi_lock_session();

    kirpi_audit_log('lock', 'auth', [
        'user_id' => $userId,
    ], 'session', null, 'success');

    json_response([
        'status' => 'success',
        'message' => auth_lang('session_locked'),
        'redirect' => base_url('auth/lock'),
    ]);
} catch (Throwable $e) {
    error_log('auth lock action error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => auth_lang('lock_error'),
    ], 500);
}
