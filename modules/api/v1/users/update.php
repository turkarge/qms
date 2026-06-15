<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/api/language.php';

require_action('PATCH', false);

$actor = api_require_token('users.edit', 'users:update');
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error(422, api_lang('invalid_user_id'));
}

$input = api_json_input();

$hasName = array_key_exists('name', $input);
$hasEmail = array_key_exists('email', $input);
$hasPassword = array_key_exists('password', $input);
$hasPasswordConfirm = array_key_exists('password_confirm', $input);
$hasRoleId = array_key_exists('role_id', $input);
$hasIsActive = array_key_exists('is_active', $input);

if (!$hasName && !$hasEmail && !$hasPassword && !$hasRoleId && !$hasIsActive) {
    api_error(422, api_lang('no_fields_to_update'));
}

$name = $hasName ? trim((string) ($input['name'] ?? '')) : null;
$email = $hasEmail ? strtolower(trim((string) ($input['email'] ?? ''))) : null;
$password = $hasPassword ? (string) ($input['password'] ?? '') : null;
$passwordConfirm = $hasPasswordConfirm ? (string) ($input['password_confirm'] ?? '') : null;
$roleId = $hasRoleId ? $input['role_id'] : null;
$isActive = $hasIsActive ? $input['is_active'] : null;

if ($hasName && $name === '') {
    api_error(422, api_lang('name_empty'));
}

if ($hasEmail) {
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_error(422, api_lang('invalid_email'));
    }
}

if ($hasPassword) {
    if ($password === null || mb_strlen($password) < 6) {
        api_error(422, api_lang('password_min_6'));
    }

    $compare = $hasPasswordConfirm ? $passwordConfirm : $password;
    if ($password !== $compare) {
        api_error(422, api_lang('password_mismatch'));
    }
}

if ($hasRoleId && $roleId !== null && $roleId !== '') {
    $roleId = (int) $roleId;
    if ($roleId <= 0) {
        api_error(422, api_lang('role_id_invalid'));
    }
} elseif ($hasRoleId) {
    $roleId = null;
}

if ($hasIsActive) {
    $parsedIsActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($parsedIsActive === null) {
        $parsedIsActive = ((int) $isActive === 1);
    }
    $isActive = $parsedIsActive;
}

try {
    $userStmt = db()->prepare("\n        SELECT\n            u.id,\n            u.name,\n            u.email,\n            u.role_id,\n            u.is_active,\n            r.name AS role_name\n        FROM users u\n        LEFT JOIN roles r ON r.id = u.role_id\n        WHERE u.id = :id\n        LIMIT 1\n    ");
    $userStmt->execute([':id' => $id]);
    $existingUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        api_error(404, api_lang('user_not_found'));
    }

    if ($hasEmail) {
        $checkStmt = db()->prepare("SELECT COUNT(id) FROM users WHERE email = :email AND id != :id");
        $checkStmt->execute([
            ':email' => $email,
            ':id' => $id,
        ]);
        if ((int) $checkStmt->fetchColumn() > 0) {
            api_error(422, api_lang('email_used_elsewhere'));
        }
    }

    $selectedRole = null;
    if ($hasRoleId && $roleId !== null) {
        $roleStmt = db()->prepare("SELECT id, name, is_active FROM roles WHERE id = :id LIMIT 1");
        $roleStmt->execute([':id' => $roleId]);
        $selectedRole = $roleStmt->fetch(PDO::FETCH_ASSOC);
        if (!$selectedRole) {
            api_error(422, api_lang('role_invalid'));
        }

        $roleChanged = (int) ($existingUser['role_id'] ?? 0) !== $roleId;
        if ($roleChanged && isset($selectedRole['is_active']) && (int) $selectedRole['is_active'] !== 1) {
            api_error(422, api_lang('role_inactive_assign'));
        }
    }

    $isSuperAdminUser = ((string) ($existingUser['role_name'] ?? '')) === 'Super Admin';
    if ($isSuperAdminUser && $hasIsActive && !$isActive) {
        api_error(422, api_lang('super_admin_cannot_disable'));
    }

    $isLeavingSuperAdminRole = $isSuperAdminUser
        && $hasRoleId
        && ($roleId === null || (($selectedRole['name'] ?? null) !== 'Super Admin'));

    if ($isLeavingSuperAdminRole) {
        $countStmt = db()->query("\n            SELECT COUNT(u.id)\n            FROM users u\n            INNER JOIN roles r ON r.id = u.role_id\n            WHERE r.name = 'Super Admin'\n              AND u.is_active = 1\n        ");
        $activeSuperAdminCount = (int) $countStmt->fetchColumn();
        if ($activeSuperAdminCount <= 1) {
            api_error(422, api_lang('super_admin_min_one'));
        }
    }

    $fields = [];
    $params = [':id' => $id];

    if ($hasName) {
        $fields[] = 'name = :name';
        $params[':name'] = $name;
    }
    if ($hasEmail) {
        $fields[] = 'email = :email';
        $params[':email'] = $email;
    }
    if ($hasRoleId) {
        $fields[] = 'role_id = :role_id';
        $params[':role_id'] = $roleId;
    }
    if ($hasIsActive) {
        $fields[] = 'is_active = :is_active';
        $params[':is_active'] = $isActive ? 1 : 0;
    }
    if ($hasPassword && $password !== null) {
        $fields[] = 'password = :password';
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if (empty($fields)) {
        api_error(422, api_lang('no_updatable_field'));
    }

    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $updateStmt = db()->prepare($sql);
    $updateStmt->execute($params);

    $resultStmt = db()->prepare("\n        SELECT\n            u.id,\n            u.name,\n            u.email,\n            u.is_active,\n            u.role_id,\n            r.name AS role_name,\n            u.created_at,\n            u.updated_at\n        FROM users u\n        LEFT JOIN roles r ON r.id = u.role_id\n        WHERE u.id = :id\n        LIMIT 1\n    ");
    $resultStmt->execute([':id' => $id]);
    $updated = $resultStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    kirpi_audit_log('api_update', 'users', [
        'actor_user_id' => (int) ($actor['id'] ?? 0),
        'target_user_id' => $id,
        'changed_fields' => array_values(array_map(static fn(string $f) => trim(str_replace(' = :' . explode(' = :', $f)[1], '', $f)), $fields)),
    ], 'user', $id, 'success');

    api_response(200, api_lang('user_updated'), [
        'user' => [
            'id' => (int) ($updated['id'] ?? 0),
            'name' => (string) ($updated['name'] ?? ''),
            'email' => (string) ($updated['email'] ?? ''),
            'is_active' => (int) ($updated['is_active'] ?? 0) === 1,
            'role_id' => isset($updated['role_id']) ? (int) $updated['role_id'] : null,
            'role_name' => $updated['role_name'] ?? null,
            'created_at' => (string) ($updated['created_at'] ?? ''),
            'updated_at' => (string) ($updated['updated_at'] ?? ''),
        ],
    ]);
} catch (Throwable $e) {
    error_log('api users update error: ' . $e->getMessage());
    api_error(500, api_lang('user_update_failed'));
}


