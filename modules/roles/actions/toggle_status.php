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

$id = (int) ($_POST['id'] ?? 0);
$status = (int) ($_POST['status'] ?? -1);
$currentUser = current_user();
$currentRoleId = (int) ($currentUser['role_id'] ?? 0);

if ($id <= 0 || !in_array($status, [0, 1], true)) {
    json_response([
        'status' => 'error',
        'message' => 'Geçersiz istek.',
    ], 422);
}

if ($id === $currentRoleId && $status !== 1) {
    json_response([
        'status' => 'error',
        'message' => 'Oturumdaki kullanıcının rolü pasif yapılamaz.',
    ], 422);
}

try {
    $roleStmt = db()->prepare("
        SELECT id, name
        FROM roles
        WHERE id = :id
        LIMIT 1
    ");
    $roleStmt->execute([
        ':id' => $id,
    ]);

    $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        json_response([
            'status' => 'error',
            'message' => 'Rol bulunamadı.',
        ], 404);
    }

    if (($role['name'] ?? '') === 'Super Admin' && $status !== 1) {
        json_response([
            'status' => 'error',
            'message' => 'Super Admin rolü pasif yapılamaz.',
        ], 422);
    }

    $stmt = db()->prepare("
        UPDATE roles
        SET is_active = :status
        WHERE id = :id
    ");
    $stmt->execute([
        ':status' => $status,
        ':id' => $id,
    ]);

    kirpi_audit_log('toggle_status', 'roles', [
        'target_role_id' => $id,
        'name' => (string) ($role['name'] ?? ''),
        'is_active' => $status,
    ], 'role', $id, 'success');

    kirpi_notify_current_user('roles.status_changed', [
        'name' => (string) ($role['name'] ?? ''),
        'is_active' => $status === 1,
        'status_label' => $status === 1 ? 'aktif' : 'pasif',
    ], [
        'title' => 'Rol durumu güncellendi',
        'message' => '"' . ($role['name'] ?? 'Rol') . '" rolü ' . ($status === 1 ? 'aktif' : 'pasif') . ' yapıldı.',
        'source_module' => 'roles',
        'entity_type' => 'role',
        'entity_id' => $id,
    ]);

    json_response([
        'status' => 'success',
        'message' => '"' . ($role['name'] ?? 'Rol') . '" rolü ' . ($status === 1 ? 'aktif yapıldı.' : 'pasif yapıldı.'),
    ]);
} catch (Throwable $e) {
    error_log('roles toggle status error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => 'Rol durumu güncellenirken bir hata oluştu.',
    ], 500);
}
