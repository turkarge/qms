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
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$passwordConfirm = (string)($_POST['password_confirm'] ?? '');
$roleId = trim((string)($_POST['role_id'] ?? ''));
$isActive = isset($_POST['is_active']) ? 1 : 0;

if ($id <= 0) {
    json_response([
        'status' => 'error',
        'message' => 'Geçersiz kullanıcı.',
    ], 422);
}

if ($name === '' || $email === '') {
    json_response([
        'status' => 'error',
        'message' => 'Ad soyad ve e-posta alanları zorunludur.',
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'status' => 'error',
        'message' => 'Geçerli bir e-posta adresi girin.',
    ], 422);
}

$passwordWillChange = ($password !== '' || $passwordConfirm !== '');

if ($passwordWillChange) {
    if (mb_strlen($password) < 6) {
        json_response([
            'status' => 'error',
            'message' => 'Yeni şifre en az 6 karakter olmalıdır.',
        ], 422);
    }

    if ($password !== $passwordConfirm) {
        json_response([
            'status' => 'error',
            'message' => 'Yeni şifreler uyuşmuyor.',
        ], 422);
    }
}

$newAvatarFileName = null;
$oldAvatarFileName = null;
$selectedRole = null;

try {
    $userStmt = db()->prepare("
        SELECT
            u.id,
            u.avatar,
            u.role_id,
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

    $existingUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        json_response([
            'status' => 'error',
            'message' => 'Kullanıcı bulunamadı.',
        ], 404);
    }

    $oldAvatarFileName = $existingUser['avatar'] ?? null;

    $checkStmt = db()->prepare("
        SELECT COUNT(id)
        FROM users
        WHERE email = :email
          AND id != :id
    ");
    $checkStmt->execute([
        ':email' => $email,
        ':id' => $id,
    ]);

    if ((int)$checkStmt->fetchColumn() > 0) {
        json_response([
            'status' => 'error',
            'message' => 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.',
        ], 422);
    }

    if ($roleId !== '') {
        $roleCheckStmt = db()->prepare("SELECT id, name, is_active FROM roles WHERE id = :id LIMIT 1");
        $roleCheckStmt->execute([
            ':id' => (int)$roleId,
        ]);

        $selectedRole = $roleCheckStmt->fetch(PDO::FETCH_ASSOC);

        if (!$selectedRole) {
            json_response([
                'status' => 'error',
                'message' => 'Seçilen rol geçersiz.',
            ], 422);
        }

        $roleChanged = (int)($existingUser['role_id'] ?? 0) !== (int)$roleId;

        if (
            $roleChanged &&
            isset($selectedRole['is_active']) &&
            (int)$selectedRole['is_active'] !== 1
        ) {
            json_response([
                'status' => 'error',
                'message' => 'Pasif rol kullanıcıya atanamaz.',
            ], 422);
        }
    }

    $isSuperAdminUser = ($existingUser['role_name'] ?? null) === 'Super Admin';

    if ($isSuperAdminUser && $isActive !== 1) {
        json_response([
            'status' => 'error',
            'message' => 'Super Admin kullanıcı pasife alınamaz.',
        ], 422);
    }

    $isLeavingSuperAdminRole = $isSuperAdminUser
        && ($roleId === '' || ($selectedRole['name'] ?? null) !== 'Super Admin');

    if ($isLeavingSuperAdminRole) {
        $activeSuperAdminCountStmt = db()->query("
            SELECT COUNT(u.id)
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE r.name = 'Super Admin'
              AND u.is_active = 1
        ");

        $activeSuperAdminCount = (int) $activeSuperAdminCountStmt->fetchColumn();

        if ($activeSuperAdminCount <= 1) {
            json_response([
                'status' => 'error',
                'message' => 'Sistemde en az bir aktif Super Admin kullanıcı kalmalıdır.',
            ], 422);
        }
    }

    if (!empty($_FILES['avatar']['name'] ?? '')) {
        $uploadResult = kirpi_upload_avatar($_FILES['avatar']);

        if (!$uploadResult['success']) {
            json_response([
                'status' => 'error',
                'message' => $uploadResult['message'],
            ], 422);
        }

        $newAvatarFileName = $uploadResult['file_name'];
    }

    $fields = [
        'role_id = :role_id',
        'name = :name',
        'email = :email',
        'is_active = :is_active',
    ];

    $params = [
        ':id' => $id,
        ':role_id' => $roleId !== '' ? (int)$roleId : null,
        ':name' => $name,
        ':email' => $email,
        ':is_active' => $isActive,
    ];

    if ($passwordWillChange) {
        $fields[] = 'password = :password';
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($newAvatarFileName !== null) {
        $fields[] = 'avatar = :avatar';
        $params[':avatar'] = $newAvatarFileName;
    }

    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    if ($newAvatarFileName !== null && $oldAvatarFileName) {
        $oldPath = BASE_PATH . '/uploads/avatars/' . $oldAvatarFileName;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $currentSessionUser = current_user();
    $isCurrentUser = (int)($currentSessionUser['id'] ?? 0) === $id;

    if ($isCurrentUser) {
        if ($isActive !== 1) {
            unset($_SESSION['user']);

            kirpi_audit_log('update', 'users', [
                'target_user_id' => $id,
                'email' => $email,
                'role_id' => $roleId !== '' ? (int) $roleId : null,
                'is_active' => $isActive,
                'forced_logout' => true,
            ], 'user', $id, 'success');

            json_response([
                'status' => 'success',
                'message' => 'Kullanıcı güncellendi. Hesabınız pasife alındığı için tekrar giriş yapmanız gerekiyor.',
                'redirect' => base_url('auth/login'),
            ]);
        }

        $sessionRoleId = $roleId !== '' ? (int)$roleId : null;
        $sessionRoleName = $selectedRole['name'] ?? ($existingUser['role_name'] ?? null);

        $_SESSION['user']['id'] = $id;
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['role_id'] = $sessionRoleId;
        $_SESSION['user']['role_name'] = $sessionRoleName;
        $_SESSION['user']['permissions'] = load_user_permissions($sessionRoleId, $sessionRoleName);

        if ($newAvatarFileName !== null) {
            $_SESSION['user']['avatar'] = $newAvatarFileName;
        }
    }

    kirpi_audit_log('update', 'users', [
        'target_user_id' => $id,
        'email' => $email,
        'role_id' => $roleId !== '' ? (int) $roleId : null,
        'is_active' => $isActive,
        'password_changed' => $passwordWillChange,
        'avatar_changed' => $newAvatarFileName !== null,
    ], 'user', $id, 'success');

    kirpi_notify_current_user('users.updated', [
        'name' => $name,
        'email' => $email,
        'role_id' => $roleId !== '' ? (int) $roleId : null,
        'is_active' => $isActive === 1,
        'password_changed' => $passwordWillChange,
        'avatar_changed' => $newAvatarFileName !== null,
    ], [
        'title' => 'Kullanıcı güncellendi',
        'message' => $name . ' kullanıcısı başarıyla güncellendi.',
        'source_module' => 'users',
        'entity_type' => 'user',
        'entity_id' => $id,
    ]);

    json_response([
        'status' => 'success',
        'message' => 'Kullanıcı başarıyla güncellendi.',
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('users update error: ' . $e->getMessage());

    if ($newAvatarFileName && is_file(BASE_PATH . '/uploads/avatars/' . $newAvatarFileName)) {
        @unlink(BASE_PATH . '/uploads/avatars/' . $newAvatarFileName);
    }

    json_response([
        'status' => 'error',
        'message' => 'Kullanıcı güncellenirken bir hata oluştu.',
    ], 500);
}
