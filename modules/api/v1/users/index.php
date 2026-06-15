<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/api/language.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    api_require_token('users.view', 'users:read');

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = (int) ($_GET['per_page'] ?? 20);
    if ($perPage <= 0) {
        $perPage = 20;
    }
    if ($perPage > 100) {
        $perPage = 100;
    }

    $search = trim((string) ($_GET['search'] ?? ''));
    $roleId = trim((string) ($_GET['role_id'] ?? ''));
    $status = trim((string) ($_GET['status'] ?? ''));

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(u.name LIKE :search_name OR u.email LIKE :search_email OR r.name LIKE :search_role)";
        $searchPattern = '%' . $search . '%';
        $params[':search_name'] = $searchPattern;
        $params[':search_email'] = $searchPattern;
        $params[':search_role'] = $searchPattern;
    }

    if ($roleId !== '') {
        $where[] = "u.role_id = :role_id";
        $params[':role_id'] = (int) $roleId;
    }

    if ($status !== '' && in_array($status, ['0', '1'], true)) {
        $where[] = "u.is_active = :is_active";
        $params[':is_active'] = (int) $status;
    }

    $whereSql = '';
    if (!empty($where)) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    $offset = ($page - 1) * $perPage;

    try {
        $countSql = "\n            SELECT COUNT(u.id)\n            FROM users u\n            LEFT JOIN roles r ON r.id = u.role_id\n            {$whereSql}\n        ";

        $countStmt = db()->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();
        $totalPages = (int) ceil($total / $perPage);

        $sql = "\n            SELECT\n                u.id,\n                u.name,\n                u.email,\n                u.avatar,\n                u.is_active,\n                u.created_at,\n                u.updated_at,\n                u.role_id,\n                r.name AS role_name\n            FROM users u\n            LEFT JOIN roles r ON r.id = u.role_id\n            {$whereSql}\n            ORDER BY u.id DESC\n            LIMIT :limit OFFSET :offset\n        ";

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $users = array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'avatar_url' => !empty($row['avatar']) ? base_url('uploads/avatars/' . ltrim((string) $row['avatar'], '/')) : null,
                'is_active' => (int) ($row['is_active'] ?? 0) === 1,
                'role_id' => isset($row['role_id']) ? (int) $row['role_id'] : null,
                'role_name' => $row['role_name'] ?? null,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
        }, $rows);

        api_response(200, 'OK', [
            'users' => $users,
        ], [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
        ]);
    } catch (Throwable $e) {
        error_log('api users list error: ' . $e->getMessage());
        api_error(500, api_lang('users_list_failed'));
    }
}

if ($method === 'POST') {
    $actor = api_require_token('users.create', 'users:create');
    $input = api_json_input();

    $name = trim((string) ($input['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    $passwordConfirm = (string) ($input['password_confirm'] ?? $password);
    $roleIdRaw = $input['role_id'] ?? null;
    $isActiveRaw = $input['is_active'] ?? true;

    if ($name === '' || $email === '' || $password === '') {
        api_error(422, api_lang('users_create_required'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_error(422, api_lang('invalid_email'));
    }

    if (mb_strlen($password) < 6) {
        api_error(422, api_lang('password_min_6'));
    }

    if ($password !== $passwordConfirm) {
        api_error(422, api_lang('password_mismatch'));
    }

    $isActive = filter_var($isActiveRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($isActive === null) {
        $isActive = (int) $isActiveRaw === 1;
    }

    $roleId = null;
    if ($roleIdRaw !== null && $roleIdRaw !== '') {
        $roleId = (int) $roleIdRaw;
        if ($roleId <= 0) {
            api_error(422, api_lang('role_id_invalid'));
        }
    }

    try {
        $checkStmt = db()->prepare("SELECT COUNT(id) FROM users WHERE email = :email");
        $checkStmt->execute([':email' => $email]);
        if ((int) $checkStmt->fetchColumn() > 0) {
            api_error(422, api_lang('email_exists'));
        }

        if ($roleId !== null) {
            $roleStmt = db()->prepare("SELECT id, name, is_active FROM roles WHERE id = :id LIMIT 1");
            $roleStmt->execute([':id' => $roleId]);
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                api_error(422, api_lang('role_invalid'));
            }

            if (isset($role['is_active']) && (int) $role['is_active'] !== 1) {
                api_error(422, api_lang('role_inactive_assign'));
            }
        }

        $insertStmt = db()->prepare("\n            INSERT INTO users (\n                role_id,\n                name,\n                email,\n                password,\n                is_active\n            ) VALUES (\n                :role_id,\n                :name,\n                :email,\n                :password,\n                :is_active\n            )\n        ");
        $insertStmt->execute([
            ':role_id' => $roleId,
            ':name' => $name,
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':is_active' => $isActive ? 1 : 0,
        ]);

        $createdId = (int) db()->lastInsertId();

        kirpi_audit_log('api_create', 'users', [
            'actor_user_id' => (int) ($actor['id'] ?? 0),
            'target_user_id' => $createdId,
            'email' => $email,
            'role_id' => $roleId,
            'is_active' => $isActive,
        ], 'user', $createdId, 'success');

        api_response(201, api_lang('user_created'), [
            'user' => [
                'id' => $createdId,
                'name' => $name,
                'email' => $email,
                'role_id' => $roleId,
                'is_active' => $isActive,
            ],
        ]);
    } catch (Throwable $e) {
        error_log('api users create error: ' . $e->getMessage());
        api_error(500, api_lang('user_create_failed'));
    }
}

api_error(405, api_lang('method_not_allowed'));

