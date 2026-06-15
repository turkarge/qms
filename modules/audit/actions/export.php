<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/audit/language.php';

if (!db_table_exists('audit_logs')) {
    http_response_code(404);
    echo audit_lang('table_missing_short');
    exit;
}

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$moduleFilter = trim((string) ($_GET['module'] ?? ''));
$actionFilter = trim((string) ($_GET['action'] ?? ''));
$userIdFilter = (int) ($_GET['user_id'] ?? 0);
$searchFilter = trim((string) ($_GET['search'] ?? ''));
$userFilter = trim((string) ($_GET['user'] ?? ''));
$routeFilter = trim((string) ($_GET['route'] ?? ''));
$ipFilter = trim((string) ($_GET['ip'] ?? ''));
$dateFilter = trim((string) ($_GET['date'] ?? ''));
$format = trim((string) ($_GET['format'] ?? 'csv'));

$where = [];
$params = [];

if ($searchFilter !== '') {
    $where[] = '(u.name LIKE :global_user OR a.module_key LIKE :global_module OR a.action_key LIKE :global_action OR a.route_path LIKE :global_route OR a.ip_address LIKE :global_ip)';
    foreach (['user', 'module', 'action', 'route', 'ip'] as $key) {
        $params[':global_' . $key] = '%' . $searchFilter . '%';
    }
}

if ($userFilter !== '') {
    $where[] = 'u.name LIKE :user_name';
    $params[':user_name'] = '%' . $userFilter . '%';
}

if ($routeFilter !== '') {
    $where[] = 'a.route_path LIKE :route_path';
    $params[':route_path'] = '%' . $routeFilter . '%';
}

if ($ipFilter !== '') {
    $where[] = 'a.ip_address LIKE :ip_address';
    $params[':ip_address'] = '%' . $ipFilter . '%';
}

if ($dateFilter !== '') {
    $where[] = 'a.created_at LIKE :created_at';
    $params[':created_at'] = '%' . $dateFilter . '%';
}

if ($statusFilter !== '') {
    $where[] = 'a.status = :status';
    $params[':status'] = $statusFilter;
}

if ($moduleFilter !== '') {
    $where[] = 'a.module_key LIKE :module_key';
    $params[':module_key'] = '%' . $moduleFilter . '%';
}

if ($actionFilter !== '') {
    $where[] = 'a.action_key LIKE :action_key';
    $params[':action_key'] = '%' . $actionFilter . '%';
}

if ($userIdFilter > 0) {
    $where[] = 'a.user_id = :user_id';
    $params[':user_id'] = $userIdFilter;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT
        a.id,
        a.user_id,
        a.module_key,
        a.action_key,
        a.status,
        a.entity_type,
        a.entity_id,
        a.route_path,
        a.request_method,
        a.ip_address,
        a.user_agent,
        a.details_json,
        a.created_at,
        u.name AS user_name
    FROM audit_logs a
    LEFT JOIN users u ON u.id = a.user_id
    {$whereSql}
    ORDER BY a.id DESC
    LIMIT 5000
";

$stmt = db()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$rows = [];
while ($log = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $userName = trim((string) ($log['user_name'] ?? ''));
    $userId = (int) ($log['user_id'] ?? 0);
    $entityType = trim((string) ($log['entity_type'] ?? ''));
    $entityId = (int) ($log['entity_id'] ?? 0);

    $rows[] = [
        (int) ($log['id'] ?? 0),
        kirpi_format_datetime((string) ($log['created_at'] ?? '')),
        $userName !== '' ? ($userName . ' (#' . $userId . ')') : '',
        $userId > 0 ? $userId : '',
        (string) ($log['module_key'] ?? ''),
        (string) ($log['action_key'] ?? ''),
        (string) ($log['status'] ?? ''),
        $entityType,
        $entityId > 0 ? $entityId : '',
        (string) ($log['route_path'] ?? ''),
        (string) ($log['request_method'] ?? ''),
        (string) ($log['ip_address'] ?? ''),
        (string) ($log['user_agent'] ?? ''),
        (string) ($log['details_json'] ?? ''),
    ];
}

kirpi_export_response($format, 'audit-' . date('Ymd-His'), [
    'ID',
    audit_lang('date'),
    audit_lang('user'),
    audit_lang('user_id'),
    audit_lang('module'),
    audit_lang('action'),
    audit_lang('status'),
    audit_lang('entity'),
    'Entity ID',
    audit_lang('route'),
    'Method',
    audit_lang('ip'),
    'User Agent',
    audit_lang('detail'),
], $rows);
