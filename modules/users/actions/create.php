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

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$passwordConfirm = (string)($_POST['password_confirm'] ?? '');
$roleId = trim((string)($_POST['role_id'] ?? ''));
$isActive = isset($_POST['is_active']) ? 1 : 0;

if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
    json_response([
        'status' => 'error',
        'message' => 'Zorunlu alanları doldurun.',
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'status' => 'error',
        'message' => 'Geçerli bir e-posta adresi girin.',
    ], 422);
}

if (mb_strlen($password) < 6) {
    json_response([
        'status' => 'error',
        'message' => 'Şifre en az 6 karakter olmalıdır.',
    ], 422);
}

if ($password !== $passwordConfirm) {
    json_response([
        'status' => 'error',
        'message' => 'Şifreler uyuşmuyor.',
    ], 422);
}

$avatarFileName = null;
$selectedRole = null;

try {
    $checkStmt = db()->prepare("SELECT COUNT(id) FROM users WHERE email = :email");
    $checkStmt->execute([
        ':email' => $email,
    ]);

    if ((int)$checkStmt->fetchColumn() > 0) {
        json_response([
            'status' => 'error',
            'message' => 'Bu e-posta adresi zaten kayıtlı.',
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

        if (isset($selectedRole['is_active']) && (int)$selectedRole['is_active'] !== 1) {
            json_response([
                'status' => 'error',
                'message' => 'Pasif rol kullanıcıya atanamaz.',
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

        $avatarFileName = $uploadResult['file_name'];
    }

    $stmt = db()->prepare("
        INSERT INTO users (
            role_id,
            name,
            email,
            password,
            avatar,
            is_active
        ) VALUES (
            :role_id,
            :name,
            :email,
            :password,
            :avatar,
            :is_active
        )
    ");

    $stmt->execute([
        ':role_id' => $roleId !== '' ? (int)$roleId : null,
        ':name' => $name,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':avatar' => $avatarFileName,
        ':is_active' => $isActive,
    ]);

    $createdUserId = (int) db()->lastInsertId();
    kirpi_audit_log('create', 'users', [
        'target_user_id' => $createdUserId,
        'email' => $email,
        'role_id' => $roleId !== '' ? (int) $roleId : null,
        'is_active' => $isActive,
    ], 'user', $createdUserId, 'success');

    kirpi_notify_current_user('users.created', [
        'name' => $name,
        'email' => $email,
        'role_id' => $roleId !== '' ? (int) $roleId : null,
        'is_active' => $isActive === 1,
    ], [
        'title' => 'Kullanıcı oluşturuldu',
        'message' => $name . ' kullanıcısı başarıyla oluşturuldu.',
        'source_module' => 'users',
        'entity_type' => 'user',
        'entity_id' => $createdUserId,
    ]);

    json_response([
        'status' => 'success',
        'message' => 'Kullanıcı başarıyla oluşturuldu.',
    ]);
} catch (Throwable $e) {
    error_log('users create error: ' . $e->getMessage());

    if ($avatarFileName && is_file(BASE_PATH . '/uploads/avatars/' . $avatarFileName)) {
        @unlink(BASE_PATH . '/uploads/avatars/' . $avatarFileName);
    }

    json_response([
        'status' => 'error',
        'message' => 'Kullanıcı oluşturulurken bir hata oluştu.',
    ], 500);
}
