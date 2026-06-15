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

$email = strtolower(trim((string) ($_POST['email'] ?? '')));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('invalid_email'),
    ], 422);
}

if (!db_table_exists('auth_password_resets')) {
    json_response([
        'status' => 'error',
        'message' => auth_lang('forgot_table_missing'),
    ], 422);
}

try {
    $stmt = db()->prepare("
        SELECT id, email, name, is_active
        FROM users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([
        ':email' => $email,
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($user && (int) ($user['is_active'] ?? 0) === 1) {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60));

        $cleanupStmt = db()->prepare("
            DELETE FROM auth_password_resets
            WHERE user_id = :user_id
               OR expires_at < NOW()
               OR used_at IS NOT NULL
        ");
        $cleanupStmt->execute([
            ':user_id' => (int) $user['id'],
        ]);

        $insertStmt = db()->prepare("
            INSERT INTO auth_password_resets (
                user_id,
                email,
                token_hash,
                expires_at,
                request_ip,
                request_user_agent
            ) VALUES (
                :user_id,
                :email,
                :token_hash,
                :expires_at,
                :request_ip,
                :request_user_agent
            )
        ");
        $insertStmt->execute([
            ':user_id' => (int) $user['id'],
            ':email' => (string) ($user['email'] ?? ''),
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
            ':request_ip' => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ':request_user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        $resetLink = base_url('auth/reset-password?token=' . urlencode($rawToken));
        $mailResult = kirpi_send_templated_mail(
            (string) ($user['email'] ?? ''),
            'auth.password_reset',
            [
                'user_name' => (string) ($user['name'] ?? ''),
                'reset_link' => $resetLink,
                'expires_minutes' => '60',
            ],
            (int) ($user['id'] ?? 0)
        );

        kirpi_audit_log('forgot_password_request', 'auth', [
            'email' => $email,
            'user_id' => (int) ($user['id'] ?? 0),
            'mail_success' => (bool) ($mailResult['success'] ?? false),
        ], 'password_reset', null, ($mailResult['success'] ?? false) ? 'success' : 'failed');
    }

    json_response([
        'status' => 'success',
        'message' => auth_lang('forgot_email_sent'),
    ]);
} catch (Throwable $e) {
    error_log('forgot password action error: ' . $e->getMessage());
    json_response([
        'status' => 'error',
        'message' => auth_lang('forgot_email_send_error'),
    ], 500);
}
