<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/users/language.php';

if (!db_table_exists('users')) {
    http_response_code(404);
    echo users_lang('table_not_ready');
    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$roleId = trim((string) ($_GET['role_id'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$name = trim((string) ($_GET['name'] ?? ''));
$email = trim((string) ($_GET['email'] ?? ''));
$role = trim((string) ($_GET['role'] ?? ''));
$createdAt = trim((string) ($_GET['created_at'] ?? ''));
$updatedAt = trim((string) ($_GET['updated_at'] ?? ''));
$format = trim((string) ($_GET['format'] ?? 'csv'));

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR r.name LIKE :search_role)';
    $searchPattern = '%' . $search . '%';
    $params[':search_name'] = $searchPattern;
    $params[':search_email'] = $searchPattern;
    $params[':search_role'] = $searchPattern;
}

if ($roleId !== '') {
    $where[] = 'u.role_id = :role_id';
    $params[':role_id'] = (int) $roleId;
}

if ($status !== '' && in_array($status, ['0', '1'], true)) {
    $where[] = 'u.is_active = :is_active';
    $params[':is_active'] = (int) $status;
}

$columnSearches = [
    'name' => ['column' => 'u.name', 'value' => $name],
    'email' => ['column' => 'u.email', 'value' => $email],
    'role' => ['column' => 'r.name', 'value' => $role],
    'created_at' => ['column' => 'u.created_at', 'value' => $createdAt],
    'updated_at' => ['column' => 'u.updated_at', 'value' => $updatedAt],
];
foreach ($columnSearches as $key => $filter) {
    if ($filter['value'] === '') {
        continue;
    }
    $parameter = ':filter_' . $key;
    $where[] = $filter['column'] . ' LIKE ' . $parameter;
    $params[$parameter] = '%' . $filter['value'] . '%';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        u.is_active,
        u.lock_enabled,
        u.session_version,
        u.created_at,
        u.updated_at,
        r.name AS role_name,
        r.is_active AS role_is_active
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    {$whereSql}
    ORDER BY u.id DESC
    LIMIT 5000
";

$stmt = db()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$rows = [];
while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $roleLabel = (string) ($user['role_name'] ?? '');

    if ($roleLabel !== '' && isset($user['role_is_active']) && (int) $user['role_is_active'] !== 1) {
        $roleLabel .= users_lang('status_inactive_suffix');
    }

    $rows[] = [
        (int) ($user['id'] ?? 0),
        (string) ($user['name'] ?? ''),
        (string) ($user['email'] ?? ''),
        $roleLabel,
        (int) ($user['is_active'] ?? 0) === 1 ? users_lang('active') : users_lang('inactive'),
        (int) ($user['lock_enabled'] ?? 0) === 1 ? users_lang('lock_enabled') : users_lang('lock_disabled'),
        (int) ($user['session_version'] ?? 0),
        kirpi_format_datetime((string) ($user['created_at'] ?? '')),
        kirpi_format_datetime((string) ($user['updated_at'] ?? '')),
    ];
}

kirpi_export_response($format, 'users-' . date('Ymd-His'), [
    'ID',
    users_lang('name_surname'),
    users_lang('email'),
    users_lang('role'),
    users_lang('table_status'),
    users_lang('lock_status'),
    users_lang('session_version'),
    users_lang('table_created_at'),
    users_lang('updated_at'),
], $rows);
