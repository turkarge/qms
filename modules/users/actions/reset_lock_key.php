<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => 'Güvenlik doğrulaması başarısız oldu.',
    ], 419);
}

$targetUserId = (int) ($_POST['id'] ?? 0);
$currentUser = current_user();
$actorUserId = (int) ($currentUser['id'] ?? 0);

if ($targetUserId <= 0) {
    json_response([
        'status' => 'error',
        'message' => 'Geçersiz kullanıcı.',
    ], 422);
}

if (!kirpi_auth_lock_schema_ready()) {
    json_response([
        'status' => 'error',
        'message' => 'Lock key altyapısı hazır değil. Ayarlar > Eksikleri Kur çalıştırın.',
    ], 422);
}

try {
    $userStmt = db()->prepare("
        SELECT id, name, email
        FROM users
        WHERE id = :id
        LIMIT 1
    ");
    $userStmt->execute([
        ':id' => $targetUserId,
    ]);
    $targetUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        json_response([
            'status' => 'error',
            'message' => 'Kullanıcı bulunamadı.',
        ], 404);
    }

    $updateStmt = db()->prepare("
        UPDATE users
        SET lock_enabled = 0,
            lock_pin_hash = NULL
        WHERE id = :id
        LIMIT 1
    ");
    $updateStmt->execute([
        ':id' => $targetUserId,
    ]);

    if (kirpi_user_sessions_table_ready()) {
        $unlockStmt = db()->prepare("
            UPDATE user_sessions
            SET is_locked = 0,
                locked_at = NULL,
                updated_at = NOW()
            WHERE user_id = :user_id
        ");
        $unlockStmt->execute([
            ':user_id' => $targetUserId,
        ]);
    }

    if ($actorUserId > 0 && $actorUserId === $targetUserId) {
        unset($_SESSION['_auth_lock']);
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $_SESSION['user']['lock_enabled'] = false;
        }
    }

    kirpi_notify_user($targetUserId, 'users.lock_key_reset', [
        'user_name' => (string) ($targetUser['name'] ?? ''),
    ], [
        'title' => 'Lock key sıfırlandı',
        'message' => 'Yetkili bir kullanıcı oturum kilitleme keyinizi sıfırladı ve özelliği pasif yaptı.',
        'email' => !empty($targetUser['email']),
        'recipient_email' => (string) ($targetUser['email'] ?? ''),
        'email_template_key' => 'users.lock_key_reset',
    ]);

    kirpi_audit_log('reset_lock_key', 'users', [
        'target_user_id' => $targetUserId,
        'target_email' => (string) ($targetUser['email'] ?? ''),
    ], 'user', $targetUserId, 'success');

    json_response([
        'status' => 'success',
        'message' => 'Lock key sıfırlandı ve oturum kilitleme pasif yapıldı.',
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('reset lock key action error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => 'Lock key sıfırlanırken bir hata oluştu.',
    ], 500);
}
