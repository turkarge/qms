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
$permissionSlugs = array_map('strval', $_POST['permission_slugs'] ?? []);

if ($id <= 0) {
    json_response([
        'status' => 'error',
        'message' => 'Geçersiz rol.',
    ], 422);
}

if (!db_table_exists('permissions') || !db_table_exists('role_permissions')) {
    json_response([
        'status' => 'error',
        'message' => 'Permission tabloları henüz kurulu değil.',
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

    if (($role['name'] ?? '') === 'Super Admin') {
        json_response([
            'status' => 'error',
            'message' => 'Super Admin rolü için izin matrisi düzenlenmez.',
        ], 422);
    }

    sync_role_permissions($id, $permissionSlugs);

    kirpi_audit_log('permissions_update', 'roles', [
        'target_role_id' => $id,
        'name' => (string) ($role['name'] ?? ''),
        'permission_count' => count($permissionSlugs),
        'permission_slugs' => array_values($permissionSlugs),
    ], 'role', $id, 'success');

    kirpi_notify_current_user('roles.permissions_updated', [
        'name' => (string) ($role['name'] ?? ''),
        'permission_count' => count($permissionSlugs),
        'permission_slugs' => array_values($permissionSlugs),
    ], [
        'title' => 'Rol izinleri güncellendi',
        'message' => '"' . ($role['name'] ?? 'Rol') . '" rolünün izinleri güncellendi.',
        'source_module' => 'roles',
        'entity_type' => 'role',
        'entity_id' => $id,
    ]);

    set_flash_message('success', '"' . ($role['name'] ?? 'Rol') . '" rolünün izinleri başarıyla güncellendi.');

    json_response([
        'status' => 'success',
        'message' => '"' . ($role['name'] ?? 'Rol') . '" rolünün izinleri başarıyla güncellendi.',
        'redirect' => base_url('roles/permissions?id=' . $id),
    ]);
} catch (Throwable $e) {
    error_log('roles permissions update error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => 'Rol izinleri güncellenirken bir hata oluştu.',
    ], 500);
}
