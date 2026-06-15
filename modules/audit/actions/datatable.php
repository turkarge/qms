<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;

require_action('GET', true);
$request = kirpi_table_request(20);
$searches = kirpi_table_column_searches($request);
$columnMap = [
    'id' => 'a.id', 'created_at' => 'a.created_at', 'user_name' => 'u.name',
    'module_key' => 'a.module_key', 'action_key' => 'a.action_key', 'status' => 'a.status',
    'route_path' => 'a.route_path', 'ip_address' => 'a.ip_address',
];
$where = [];
$params = [];

if ($request['search'] !== '') {
    $where[] = '(u.name LIKE :global_user OR a.module_key LIKE :global_module OR a.action_key LIKE :global_action OR a.route_path LIKE :global_route OR a.ip_address LIKE :global_ip)';
    foreach (['user', 'module', 'action', 'route', 'ip'] as $key) $params[':global_' . $key] = '%' . $request['search'] . '%';
}
foreach (['created_at', 'user_name', 'module_key', 'action_key', 'route_path', 'ip_address'] as $name) {
    if (!empty($searches[$name])) {
        $param = ':filter_' . $name;
        $where[] = $columnMap[$name] . ' LIKE ' . $param;
        $params[$param] = '%' . $searches[$name] . '%';
    }
}
if (!empty($searches['status'])) {
    $where[] = 'a.status = :filter_status';
    $params[':filter_status'] = $searches['status'];
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderSql = kirpi_table_order_sql($request, $columnMap, 'a.id DESC');

try {
    $total = (int) db()->query('SELECT COUNT(id) FROM audit_logs')->fetchColumn();
    $count = db()->prepare("SELECT COUNT(a.id) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id {$whereSql}");
    kirpi_table_bind($count, $params);
    $count->execute();
    $filtered = (int) $count->fetchColumn();
    $stmt = db()->prepare("SELECT a.id, a.user_id, a.module_key, a.action_key, a.status, a.route_path, a.request_method, a.ip_address, a.details_json, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id {$whereSql} ORDER BY {$orderSql} LIMIT :length OFFSET :start");
    kirpi_table_bind($stmt, $params);
    $stmt->bindValue(':length', $request['length'], PDO::PARAM_INT);
    $stmt->bindValue(':start', $request['start'], PDO::PARAM_INT);
    $stmt->execute();
    $data = array_map(static function (array $row): array {
        $userId = (int) ($row['user_id'] ?? 0);
        $userName = trim((string) ($row['user_name'] ?? ''));
        return [
            'id' => (int) $row['id'],
            'created_at_display' => kirpi_format_datetime((string) $row['created_at']),
            'user_display' => $userName !== '' ? $userName . ' (#' . $userId . ')' : '-',
            'module_key' => (string) $row['module_key'],
            'action_key' => (string) $row['action_key'],
            'status' => (string) $row['status'],
            'route_path' => (string) ($row['route_path'] ?? ''),
            'request_method' => (string) ($row['request_method'] ?? ''),
            'ip_address' => (string) ($row['ip_address'] ?? ''),
            'details_json' => (string) ($row['details_json'] ?? ''),
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    kirpi_table_response($request, $total, $filtered, $data);
} catch (Throwable $e) {
    error_log('audit datatable error: ' . $e->getMessage());
    json_response(['draw' => $request['draw'], 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => audit_lang('load_error')], 500);
}
