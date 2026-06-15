<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;

require_action('GET', true);
$request = kirpi_table_request();
$searches = kirpi_table_column_searches($request);
$hasPermissions = db_table_exists('permissions') && db_table_exists('role_permissions');
$columnMap = ['name' => 'r.name', 'is_active' => 'r.is_active', 'user_count' => 'user_count', 'permission_count' => 'permission_count'];
$where = [];
$params = [];

$nameSearch = $searches['name'] ?? '';
if ($request['search'] !== '') {
    $where[] = 'r.name LIKE :global_search';
    $params[':global_search'] = '%' . $request['search'] . '%';
}
if ($nameSearch !== '') {
    $where[] = 'r.name LIKE :name_search';
    $params[':name_search'] = '%' . $nameSearch . '%';
}
if (isset($searches['is_active']) && in_array($searches['is_active'], ['0', '1'], true)) {
    $where[] = 'r.is_active = :is_active';
    $params[':is_active'] = (int) $searches['is_active'];
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderSql = kirpi_table_order_sql($request, $columnMap, 'r.name ASC');
$permissionJoin = $hasPermissions ? 'LEFT JOIN role_permissions rp ON rp.role_id = r.id' : '';
$permissionSelect = $hasPermissions ? 'COUNT(DISTINCT rp.permission_id)' : '0';

try {
    $total = (int) db()->query('SELECT COUNT(id) FROM roles')->fetchColumn();
    $count = db()->prepare("SELECT COUNT(r.id) FROM roles r {$whereSql}");
    kirpi_table_bind($count, $params);
    $count->execute();
    $filtered = (int) $count->fetchColumn();

    $stmt = db()->prepare("SELECT r.id, r.name, r.is_active, COUNT(DISTINCT u.id) AS user_count, {$permissionSelect} AS permission_count FROM roles r LEFT JOIN users u ON u.role_id = r.id {$permissionJoin} {$whereSql} GROUP BY r.id, r.name, r.is_active ORDER BY {$orderSql} LIMIT :length OFFSET :start");
    kirpi_table_bind($stmt, $params);
    $stmt->bindValue(':length', $request['length'], PDO::PARAM_INT);
    $stmt->bindValue(':start', $request['start'], PDO::PARAM_INT);
    $stmt->execute();
    $data = array_map(static fn(array $row): array => [
        'id' => (int) $row['id'],
        'row_key' => 'role-' . (int) $row['id'],
        'name' => (string) $row['name'],
        'is_active' => (int) $row['is_active'] === 1,
        'user_count' => (int) $row['user_count'],
        'permission_count' => (int) $row['permission_count'],
    ], $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    kirpi_table_response($request, $total, $filtered, $data);
} catch (Throwable $e) {
    error_log('roles datatable error: ' . $e->getMessage());
    json_response(['draw' => $request['draw'], 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => roles_lang('table_load_error')], 500);
}
