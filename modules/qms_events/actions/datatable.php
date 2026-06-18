<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_events/helpers.php';
require_action('GET', true);

$request = kirpi_table_request();
$params = [];
$where = [];
$from = "qms_domain_events e JOIN organization_companies c ON c.id=e.company_id LEFT JOIN users u ON u.id=e.actor_user_id LEFT JOIN qms_entities qe ON qe.id=e.entity_id";
$select = "e.id,e.event_id,e.event_type,e.entity_type,e.entity_id,e.company_id,e.actor_type,e.actor_user_id,e.source_module,e.correlation_id,e.payload,e.occurred_at,e.recorded_at,c.company_name,u.name AS actor_user_name,qe.entity_code,qe.title AS entity_title";
$columns = [
    'company_name' => 'c.company_name',
    'event_type' => 'e.event_type',
    'entity_type' => 'e.entity_type',
    'actor_type' => 'e.actor_type',
    'source_module' => 'e.source_module',
    'occurred_at' => 'e.occurred_at',
    'recorded_at' => 'e.recorded_at',
];
$search = ['c.company_name', 'e.event_type', 'e.entity_type', 'e.source_module', 'e.correlation_id', 'qe.entity_code', 'qe.title', 'u.name'];
$scope = organization_scope_where('e.company_id', $params);
if ($scope !== null) $where[] = $scope;
if ($request['search'] !== '') {
    $parts = [];
    foreach ($search as $index => $column) {
        $key = ':s' . $index;
        $parts[] = $column . ' LIKE ' . $key;
        $params[$key] = '%' . $request['search'] . '%';
    }
    $where[] = '(' . implode(' OR ', $parts) . ')';
}
foreach (kirpi_table_column_searches($request) as $name => $value) {
    if (!isset($columns[$name])) continue;
    $key = ':c' . preg_replace('/\W/', '', $name);
    $where[] = $columns[$name] . ' LIKE ' . $key;
    $params[$key] = '%' . $value . '%';
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$totalParams = [];
$totalScope = organization_scope_where('e.company_id', $totalParams);
$totalStmt = db()->prepare('SELECT COUNT(*) FROM ' . $from . ($totalScope ? ' WHERE ' . $totalScope : ''));
kirpi_table_bind($totalStmt, $totalParams);
$totalStmt->execute();
$total = (int) $totalStmt->fetchColumn();
$filteredStmt = db()->prepare('SELECT COUNT(*) FROM ' . $from . $whereSql);
kirpi_table_bind($filteredStmt, $params);
$filteredStmt->execute();
$filtered = (int) $filteredStmt->fetchColumn();
$sql = 'SELECT ' . $select . ' FROM ' . $from . $whereSql . ' ORDER BY ' . kirpi_table_order_sql($request, $columns, 'e.id DESC') . ' LIMIT :length OFFSET :start';
$stmt = db()->prepare($sql);
kirpi_table_bind($stmt, $params);
$stmt->bindValue(':length', $request['length'], PDO::PARAM_INT);
$stmt->bindValue(':start', $request['start'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($rows as &$row) {
    $row['event_type_name'] = qms_events_type_label((string) $row['event_type']);
    $row['actor_name'] = qms_events_actor_label((string) $row['actor_type'], $row['actor_user_name'] ?? null);
    $row['entity_name'] = trim((string) ($row['entity_code'] ?? '') . ' - ' . (string) ($row['entity_title'] ?? ''), ' -');
    $row['row_key'] = 'events-' . (int) $row['id'];
}
unset($row);
kirpi_table_response($request, $total, $filtered, $rows);
