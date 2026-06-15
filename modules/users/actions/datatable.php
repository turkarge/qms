<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_action('GET', true);

$request = kirpi_table_request();
$columns = $request['columns'];
$globalSearch = $request['search'];
$roleId = trim((string) ($_GET['role_id'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$columnMap = [
    'name' => 'u.name',
    'email' => 'u.email',
    'role_name' => 'r.name',
    'is_active' => 'u.is_active',
    'created_at' => 'u.created_at',
    'created_at_display' => 'u.created_at',
    'updated_at' => 'u.updated_at',
    'updated_at_display' => 'u.updated_at',
];

$where = [];
$params = [];

if ($globalSearch !== '') {
    $pattern = '%' . $globalSearch . '%';
    $where[] = '(u.name LIKE :global_name OR u.email LIKE :global_email OR r.name LIKE :global_role)';
    $params[':global_name'] = $pattern;
    $params[':global_email'] = $pattern;
    $params[':global_role'] = $pattern;
}

if ($roleId !== '' && ctype_digit($roleId)) {
    $where[] = 'u.role_id = :role_id';
    $params[':role_id'] = (int) $roleId;
}

if (in_array($status, ['0', '1'], true)) {
    $where[] = 'u.is_active = :status';
    $params[':status'] = (int) $status;
}

foreach ($columns as $index => $column) {
    if (!is_array($column)) {
        continue;
    }
    $columnName = (string) ($column['name'] ?? $column['data'] ?? '');
    $searchValue = trim((string) ($column['search']['value'] ?? ''));
    if ($searchValue === '' || !isset($columnMap[$columnName])) {
        continue;
    }

    $parameter = ':column_' . (int) $index;
    if ($columnName === 'is_active' && in_array($searchValue, ['0', '1'], true)) {
        $where[] = $columnMap[$columnName] . ' = ' . $parameter;
        $params[$parameter] = (int) $searchValue;
        continue;
    }

    $where[] = $columnMap[$columnName] . ' LIKE ' . $parameter;
    $params[$parameter] = '%' . $searchValue . '%';
}

$orderSql = kirpi_table_order_sql($request, $columnMap, 'u.id DESC');
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $total = (int) db()->query('SELECT COUNT(id) FROM users')->fetchColumn();

    $countStmt = db()->prepare("SELECT COUNT(u.id) FROM users u LEFT JOIN roles r ON r.id = u.role_id {$whereSql}");
    kirpi_table_bind($countStmt, $params);
    $countStmt->execute();
    $filtered = (int) $countStmt->fetchColumn();

    $stmt = db()->prepare("
        SELECT
            u.id,
            u.name,
            u.email,
            u.avatar,
            u.is_active,
            u.created_at,
            u.updated_at,
            r.name AS role_name,
            r.is_active AS role_is_active
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        {$whereSql}
        ORDER BY {$orderSql}
        LIMIT :length OFFSET :start
    ");
    kirpi_table_bind($stmt, $params);
    $stmt->bindValue(':length', $request['length'], PDO::PARAM_INT);
    $stmt->bindValue(':start', $request['start'], PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = (string) ($row['name'] ?? '');
        $avatar = trim((string) ($row['avatar'] ?? ''));
        $data[] = [
            'id' => (int) ($row['id'] ?? 0),
            'row_key' => 'user-' . (int) ($row['id'] ?? 0),
            'name' => $name,
            'initial' => mb_strtoupper(mb_substr($name, 0, 1)),
            'email' => (string) ($row['email'] ?? ''),
            'avatar_url' => $avatar !== '' ? base_url('uploads/avatars/' . ltrim($avatar, '/')) : null,
            'role_name' => $row['role_name'] ?? null,
            'role_is_active' => $row['role_is_active'] === null ? null : (int) $row['role_is_active'] === 1,
            'is_active' => (int) ($row['is_active'] ?? 0) === 1,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'created_at_display' => kirpi_format_datetime((string) ($row['created_at'] ?? '')),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
            'updated_at_display' => kirpi_format_datetime((string) ($row['updated_at'] ?? '')),
        ];
    }

    kirpi_table_response($request, $total, $filtered, $data);
} catch (Throwable $e) {
    error_log('users datatable error: ' . $e->getMessage());
    json_response([
        'draw' => $request['draw'],
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Kullanıcı tablosu yüklenemedi.',
    ], 500);
}
