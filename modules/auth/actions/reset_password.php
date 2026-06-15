<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/auth/language.php';

require_action('POST', false);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('csrf_failed_refresh'),
    ], 419);
}

$token = trim((string) ($_POST['token'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

if ($token === '') {
    json_response([
        'status' => 'error',
        'message' => auth_lang('reset_token_missing'),
    ], 422);
}

if (mb_strlen($password) < 6) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('password_min_6'),
    ], 422);
}

if ($password !== $passwordConfirm) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('password_mismatch'),
    ], 422);
}

if (!db_table_exists('auth_password_resets')) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('forgot_table_missing'),
    ], 422);
}

try {
    $tokenHash = hash('sha256', $token);
    $resetStmt = db()->prepare("
        SELECT id, user_id, email, expires_at, used_at
        FROM auth_password_resets
        WHERE token_hash = :token_hash
          AND used_at IS NULL
          AND expires_at >= NOW()
        LIMIT 1
    ");
    $resetStmt->execute([
        ':token_hash' => $tokenHash,
    ]);
    $resetRow = $resetStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$resetRow) {
        json_response([
            'status' => 'error',
            'message' => auth_lang('reset_token_invalid'),
        ], 422);
    }

    $userId = (int) ($resetRow['user_id'] ?? 0);
    if ($userId <= 0) {
        json_response([
            'status' => 'error',
            'message' => auth_lang('reset_token_invalid'),
        ], 422);
    }

    $updateUserStmt = db()->prepare("
        UPDATE users
        SET password = :password,
            session_version = session_version + 1,
            updated_at = NOW()
        WHERE id = :id
        LIMIT 1
    ");
    $updateUserStmt->execute([
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':id' => $userId,
    ]);

    $useTokenStmt = db()->prepare("
        UPDATE auth_password_resets
        SET used_at = NOW()
        WHERE id = :id
        LIMIT 1
    ");
    $useTokenStmt->execute([
        ':id' => (int) ($resetRow['id'] ?? 0),
    ]);

    if (kirpi_user_sessions_table_ready()) {
        $sessionDeleteStmt = db()->prepare("DELETE FROM user_sessions WHERE user_id = :user_id");
        $sessionDeleteStmt->execute([
            ':user_id' => $userId,
        ]);
    }

    kirpi_audit_log('password_reset_complete', 'auth', [
        'user_id' => $userId,
        'email' => (string) ($resetRow['email'] ?? ''),
    ], 'password_reset', null, 'success');

    json_response([
        'status' => 'success',
        'message' => auth_lang('reset_success'),
        'redirect' => base_url('auth/login'),
    ]);
} catch (Throwable $e) {
    error_log('reset password action error: ' . $e->getMessage());
    json_response([
        'status' => 'error',
        'message' => auth_lang('reset_error'),
    ], 500);
}
