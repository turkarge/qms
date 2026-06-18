<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/profile/language.php';
if (is_file(BASE_PATH . '/modules/organization/language.php')) {
    require_once BASE_PATH . '/modules/organization/language.php';
}
if (is_file(BASE_PATH . '/modules/organization/helpers.php')) {
    require_once BASE_PATH . '/modules/organization/helpers.php';
}

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => profile_lang('csrf_failed'),
    ], 419);
}

$currentUser = current_user();
$id = (int) ($currentUser['id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
$defaultCompanySchemaReady = db_table_exists('users') && db_column_exists('users', 'default_company_id');
$defaultCompanyId = $defaultCompanySchemaReady ? (int) ($_POST['default_company_id'] ?? 0) : 0;

if ($id <= 0) {
    json_response([
        'status' => 'error',
        'message' => profile_lang('invalid_session'),
    ], 403);
}

if ($name === '' || $email === '') {
    json_response([
        'status' => 'error',
        'message' => profile_lang('required_fields'),
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'status' => 'error',
        'message' => profile_lang('invalid_email'),
    ], 422);
}

$passwordWillChange = ($password !== '' || $passwordConfirm !== '');

if ($passwordWillChange) {
    if (mb_strlen($password) < 6) {
        json_response([
            'status' => 'error',
            'message' => profile_lang('password_min'),
        ], 422);
    }

    if ($password !== $passwordConfirm) {
        json_response([
            'status' => 'error',
            'message' => profile_lang('password_mismatch'),
        ], 422);
    }
}

if (
    $defaultCompanyId > 0
    && function_exists('organization_company_in_scope')
    && !organization_company_in_scope($defaultCompanyId, $currentUser)
) {
    json_response([
        'status' => 'error',
        'message' => organization_lang('permission_denied', profile_lang('profile_update_error')),
    ], 403);
}

$newAvatarFileName = null;
$oldAvatarFileName = null;

try {
    $userStmt = db()->prepare("\n        SELECT id, avatar, role_id\n        FROM users\n        WHERE id = :id\n        LIMIT 1\n    ");
    $userStmt->execute([
        ':id' => $id,
    ]);

    $existingUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        json_response([
            'status' => 'error',
            'message' => profile_lang('user_not_found'),
        ], 404);
    }

    $oldAvatarFileName = $existingUser['avatar'] ?? null;

    $checkStmt = db()->prepare("\n        SELECT COUNT(id)\n        FROM users\n        WHERE email = :email\n          AND id != :id\n    ");
    $checkStmt->execute([
        ':email' => $email,
        ':id' => $id,
    ]);

    if ((int) $checkStmt->fetchColumn() > 0) {
        json_response([
            'status' => 'error',
            'message' => profile_lang('email_in_use'),
        ], 422);
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
        'name = :name',
        'email = :email',
    ];

    $params = [
        ':id' => $id,
        ':name' => $name,
        ':email' => $email,
    ];

    if ($passwordWillChange) {
        $fields[] = 'password = :password';
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($newAvatarFileName !== null) {
        $fields[] = 'avatar = :avatar';
        $params[':avatar'] = $newAvatarFileName;
    }

    if ($defaultCompanySchemaReady) {
        $fields[] = 'default_company_id = :default_company_id';
        $params[':default_company_id'] = $defaultCompanyId > 0 ? $defaultCompanyId : null;
    }

    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    if ($newAvatarFileName !== null && $oldAvatarFileName) {
        $oldPath = BASE_PATH . '/uploads/avatars/' . $oldAvatarFileName;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $roleName = $currentUser['role_name'] ?? null;
    $roleId = isset($existingUser['role_id']) ? (int) $existingUser['role_id'] : null;

    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['role_id'] = $roleId;
    $_SESSION['user']['role_name'] = $roleName;
    $_SESSION['user']['permissions'] = load_user_permissions($roleId, $roleName);
    if ($defaultCompanySchemaReady) {
        $_SESSION['user']['default_company_id'] = $defaultCompanyId > 0 ? $defaultCompanyId : null;
        if ($defaultCompanyId > 0 && empty($_SESSION['active_company_id'])) {
            $_SESSION['active_company_id'] = $defaultCompanyId;
        }
    }

    if ($newAvatarFileName !== null) {
        $_SESSION['user']['avatar'] = $newAvatarFileName;
    }

    kirpi_audit_log('update', 'profile', [
        'target_user_id' => $id,
        'email' => $email,
        'password_changed' => $passwordWillChange,
        'avatar_changed' => $newAvatarFileName !== null,
    ], 'user', $id, 'success');

    json_response([
        'status' => 'success',
        'message' => profile_lang('profile_updated'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('profile update error: ' . $e->getMessage());

    if ($newAvatarFileName && is_file(BASE_PATH . '/uploads/avatars/' . $newAvatarFileName)) {
        @unlink(BASE_PATH . '/uploads/avatars/' . $newAvatarFileName);
    }

    json_response([
        'status' => 'error',
        'message' => profile_lang('profile_update_error'),
    ], 500);
}
