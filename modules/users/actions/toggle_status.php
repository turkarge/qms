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

$id = (int)($_POST['id'] ?? 0);
$status = (int)($_POST['status'] ?? -1);
$currentUser = current_user();
$currentUserId = (int)($currentUser['id'] ?? 0);

if ($id <= 0 || !in_array($status, [0, 1], true)) {
    json_response([
        'status' => 'error',
        'message' => 'Geçersiz istek.',
    ], 422);
}

if ($id === $currentUserId && $status !== 1) {
    json_response([
        'status' => 'error',
        'message' => 'Kendi hesabınızı pasife alamazsınız.',
    ], 422);
}

try {
    $userStmt = db()->prepare("
        SELECT
            u.id,
            u.is_active,
            r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.id = :id
        LIMIT 1
    ");
    $userStmt->execute([
        ':id' => $id,
    ]);

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        json_response([
            'status' => 'error',
            'message' => 'Kullanıcı bulunamadı.',
        ], 404);
    }

    if (($user['role_name'] ?? null) === 'Super Admin' && $status !== 1) {
        json_response([
            'status' => 'error',
            'message' => 'Super Admin kullanıcısı pasife alınamaz.',
        ], 422);
    }

    $stmt = db()->prepare("UPDATE users SET is_active = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id' => $id,
    ]);

    kirpi_audit_log('toggle_status', 'users', [
        'target_user_id' => $id,
        'is_active' => $status,
    ], 'user', $id, 'success');

    kirpi_notify_current_user('users.status_changed', [
        'target_user_id' => $id,
        'is_active' => $status === 1,
        'status_label' => $status === 1 ? 'aktif' : 'pasif',
    ], [
        'title' => 'Kullanıcı durumu güncellendi',
        'message' => 'Kullanıcı #' . $id . ' ' . ($status === 1 ? 'aktif' : 'pasif') . ' yapıldı.',
        'source_module' => 'users',
        'entity_type' => 'user',
        'entity_id' => $id,
    ]);

    json_response([
        'status' => 'success',
        'message' => $status === 1 ? 'Kullanıcı aktif yapıldı.' : 'Kullanıcı pasif yapıldı.',
    ]);
} catch (Throwable $e) {
    error_log('users toggle status hatasi: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => 'Durum güncellenirken bir hata oluştu.',
    ], 500);
}
