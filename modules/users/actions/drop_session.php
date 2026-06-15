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
        'message' => 'Session altyapısı hazır değil. Ayarlar > Eksikleri Kur çalıştırın.',
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
        SET session_version = session_version + 1
        WHERE id = :id
        LIMIT 1
    ");
    $updateStmt->execute([
        ':id' => $targetUserId,
    ]);

    if (kirpi_user_sessions_table_ready()) {
        $sessionDeleteStmt = db()->prepare("DELETE FROM user_sessions WHERE user_id = :user_id");
        $sessionDeleteStmt->execute([
            ':user_id' => $targetUserId,
        ]);
    }

    kirpi_notify_user($targetUserId, 'users.session_dropped', [
        'user_name' => (string) ($targetUser['name'] ?? ''),
    ], [
        'title' => 'Oturum sonlandırıldı',
        'message' => 'Yetkili bir kullanıcı tüm aktif oturumlarınızı sonlandırdı. Lütfen yeniden giriş yapın.',
        'email' => !empty($targetUser['email']),
        'recipient_email' => (string) ($targetUser['email'] ?? ''),
        'email_template_key' => 'users.session_dropped',
    ]);

    kirpi_audit_log('drop_session', 'users', [
        'target_user_id' => $targetUserId,
        'target_email' => (string) ($targetUser['email'] ?? ''),
    ], 'session', null, 'success');

    if ($actorUserId > 0 && $actorUserId === $targetUserId) {
        kirpi_delete_current_user_session();
        unset($_SESSION['user'], $_SESSION['_auth_lock']);

        json_response([
            'status' => 'success',
            'message' => 'Kendi oturumunuz sonlandırıldı. Yeniden giriş yapın.',
            'redirect' => base_url('auth/login'),
        ]);
    }

    json_response([
        'status' => 'success',
        'message' => 'Kullanıcının aktif oturumları sonlandırıldı.',
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('drop session action error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => 'Oturum sonlandırılırken bir hata oluştu.',
    ], 500);
}
