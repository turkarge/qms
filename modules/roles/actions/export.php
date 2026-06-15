<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/roles/language.php';

if (!db_table_exists('roles')) {
    http_response_code(404);
    echo roles_lang('table_not_ready');
    exit;
}

$type = strtolower(trim((string) ($_GET['type'] ?? 'roles')));
$format = trim((string) ($_GET['format'] ?? 'csv'));

if ($type === 'permissions') {
    if (!check_permission('roles.permissions')) {
        http_response_code(403);
        echo roles_lang('permission_denied');
        exit;
    }

    $catalog = kirpi_core_permission_catalog();
    $rows = [];

    foreach ($catalog as $groupKey => $group) {
        foreach (($group['permissions'] ?? []) as $permission) {
            $rows[] = [
                (string) $groupKey,
                (string) ($group['title'] ?? $groupKey),
                (string) ($permission['slug'] ?? ''),
                (string) ($permission['name'] ?? ''),
            ];
        }
    }

    kirpi_export_response($format, 'permission-catalog-' . date('Ymd-His'), [
        roles_lang('module'),
        roles_lang('module_title'),
        roles_lang('permission_slug'),
        roles_lang('permission_name'),
    ], $rows);
}

if ($type === 'matrix') {
    if (!check_permission('roles.permissions')) {
        http_response_code(403);
        echo roles_lang('permission_denied');
        exit;
    }

    if (!db_table_exists('permissions') || !db_table_exists('role_permissions')) {
        http_response_code(404);
        echo roles_lang('permission_tables_missing');
        exit;
    }

    $stmt = db()->query("
        SELECT
            r.id AS role_id,
            r.name AS role_name,
            r.is_active,
            p.group_name,
            p.slug,
            p.name AS permission_name,
            CASE WHEN rp.permission_id IS NULL THEN 0 ELSE 1 END AS assigned
        FROM roles r
        CROSS JOIN permissions p
        LEFT JOIN role_permissions rp ON rp.role_id = r.id AND rp.permission_id = p.id
        ORDER BY r.name ASC, p.group_name ASC, p.slug ASC
    ");

    $rows = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $isSuperAdmin = (string) ($row['role_name'] ?? '') === 'Super Admin';
        $assigned = $isSuperAdmin || (int) ($row['assigned'] ?? 0) === 1;

        $rows[] = [
            (int) ($row['role_id'] ?? 0),
            (string) ($row['role_name'] ?? ''),
            (int) ($row['is_active'] ?? 0) === 1 ? roles_lang('active') : roles_lang('inactive'),
            (string) ($row['group_name'] ?? ''),
            (string) ($row['slug'] ?? ''),
            (string) ($row['permission_name'] ?? ''),
            $assigned ? roles_lang('yes') : roles_lang('no'),
        ];
    }

    kirpi_export_response($format, 'role-permission-matrix-' . date('Ymd-His'), [
        'Role ID',
        roles_lang('role_name'),
        roles_lang('table_status'),
        roles_lang('module'),
        roles_lang('permission_slug'),
        roles_lang('permission_name'),
        roles_lang('assigned'),
    ], $rows);
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$hasPermissionSchema = db_table_exists('permissions') && db_table_exists('role_permissions');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = 'r.name LIKE :search';
    $params[':search'] = '%' . $search . '%';
}

if ($status !== '' && in_array($status, ['0', '1'], true)) {
    $where[] = 'r.is_active = :is_active';
    $params[':is_active'] = (int) $status;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT
        r.id,
        r.name,
        r.is_active,
        r.created_at,
        r.updated_at,
        COUNT(DISTINCT u.id) AS user_count," . ($hasPermissionSchema
            ? "
        COUNT(DISTINCT rp.permission_id) AS permission_count"
            : "
        0 AS permission_count") . "
    FROM roles r
    LEFT JOIN users u ON u.role_id = r.id
    " . ($hasPermissionSchema ? "LEFT JOIN role_permissions rp ON rp.role_id = r.id" : "") . "
    {$whereSql}
    GROUP BY r.id, r.name, r.is_active, r.created_at, r.updated_at
    ORDER BY r.name ASC
    LIMIT 5000
";

$stmt = db()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$rows = [];
while ($role = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        (int) ($role['id'] ?? 0),
        (string) ($role['name'] ?? ''),
        (int) ($role['is_active'] ?? 0) === 1 ? roles_lang('active') : roles_lang('inactive'),
        (int) ($role['user_count'] ?? 0),
        (int) ($role['permission_count'] ?? 0),
        kirpi_format_datetime((string) ($role['created_at'] ?? '')),
        kirpi_format_datetime((string) ($role['updated_at'] ?? '')),
    ];
}

kirpi_export_response($format, 'roles-' . date('Ymd-His'), [
    'ID',
    roles_lang('role_name'),
    roles_lang('table_status'),
    roles_lang('table_user_count'),
    roles_lang('table_permission_count'),
    roles_lang('created_at'),
    roles_lang('updated_at'),
], $rows);
