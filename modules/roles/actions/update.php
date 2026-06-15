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
$name = trim((string) ($_POST['name'] ?? ''));
$isActive = isset($_POST['is_active']) ? 1 : 0;
$currentUser = current_user();
$currentRoleId = (int) ($currentUser['role_id'] ?? 0);

if ($id <= 0) {
    json_response([
        'status' => 'error',
        'message' => 'Geçersiz rol.',
    ], 422);
}

if ($id === $currentRoleId && $isActive !== 1) {
    json_response([
        'status' => 'error',
        'message' => 'Oturumdaki kullanıcının rolü pasif yapılamaz.',
    ], 422);
}

if ($name === '') {
    json_response([
        'status' => 'error',
        'message' => 'Rol adı zorunludur.',
    ], 422);
}

if (mb_strlen($name) < 2) {
    json_response([
        'status' => 'error',
        'message' => 'Rol adı en az 2 karakter olmalıdır.',
    ], 422);
}

try {
    $roleStmt = db()->prepare("
        SELECT id, name, is_active
        FROM roles
        WHERE id = :id
        LIMIT 1
    ");
    $roleStmt->execute([
        ':id' => $id,
    ]);

    $existingRole = $roleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingRole) {
        json_response([
            'status' => 'error',
            'message' => 'Rol bulunamadı.',
        ], 404);
    }

    if (($existingRole['name'] ?? '') === 'Super Admin' && $name !== 'Super Admin') {
        json_response([
            'status' => 'error',
            'message' => 'Super Admin rol adı değiştirilemez.',
        ], 422);
    }

    if (($existingRole['name'] ?? '') === 'Super Admin' && $isActive !== 1) {
        json_response([
            'status' => 'error',
            'message' => 'Super Admin rolü pasif yapılamaz.',
        ], 422);
    }

    $checkStmt = db()->prepare("
        SELECT COUNT(id)
        FROM roles
        WHERE LOWER(name) = LOWER(:name)
          AND id != :id
    ");
    $checkStmt->execute([
        ':name' => $name,
        ':id' => $id,
    ]);

    if ((int) $checkStmt->fetchColumn() > 0) {
        json_response([
            'status' => 'error',
            'message' => 'Bu rol adı başka bir kayıt tarafından kullanılıyor.',
        ], 422);
    }

    $stmt = db()->prepare("
        UPDATE roles
        SET name = :name,
            is_active = :is_active
        WHERE id = :id
    ");
    $stmt->execute([
        ':name' => $name,
        ':is_active' => $isActive,
        ':id' => $id,
    ]);

    if ($id === $currentRoleId && isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        $_SESSION['user']['role_name'] = $name;
        $_SESSION['user']['permissions'] = load_user_permissions($currentRoleId, $name);
    }

    kirpi_audit_log('update', 'roles', [
        'target_role_id' => $id,
        'name' => $name,
        'is_active' => $isActive,
    ], 'role', $id, 'success');

    kirpi_notify_current_user('roles.updated', [
        'name' => $name,
        'is_active' => $isActive === 1,
    ], [
        'title' => 'Rol güncellendi',
        'message' => '"' . $name . '" rolü başarıyla güncellendi.',
        'source_module' => 'roles',
        'entity_type' => 'role',
        'entity_id' => $id,
    ]);

    json_response([
        'status' => 'success',
        'message' => '"' . $name . '" rolü başarıyla güncellendi.',
    ]);
} catch (Throwable $e) {
    error_log('roles update error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => 'Rol güncellenirken bir hata oluştu.',
    ], 500);
}
