<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_relationships/helpers.php';
require_action('GET', true);

$request = kirpi_table_request();
$params = [];
$where = [];
$from = "qms_entity_relationships r JOIN organization_companies c ON c.id=r.company_id JOIN qms_relationship_types rt ON rt.relationship_type=r.relationship_type JOIN qms_entities se ON se.id=r.source_entity_id JOIN qms_entities te ON te.id=r.target_entity_id";
$select = "r.id,r.company_id,c.company_name,r.source_entity_id,r.target_entity_id,r.relationship_type,r.relationship_kind,r.description,r.evidence_strength,r.status,r.valid_from,r.valid_until,r.created_at,rt.display_name AS relationship_type_name,se.entity_code AS source_code,se.title AS source_title,te.entity_code AS target_code,te.title AS target_title";
$columns = [
    'company_name' => 'c.company_name',
    'source_title' => 'se.title',
    'target_title' => 'te.title',
    'relationship_type_name' => 'rt.display_name',
    'relationship_kind' => 'r.relationship_kind',
    'status' => 'r.status',
    'created_at' => 'r.created_at',
];
$search = ['c.company_name', 'se.entity_code', 'se.title', 'te.entity_code', 'te.title', 'rt.display_name', 'r.relationship_kind', 'r.description'];
$scope = organization_scope_where('r.company_id', $params);
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
$totalScope = organization_scope_where('r.company_id', $totalParams);
$totalStmt = db()->prepare('SELECT COUNT(*) FROM ' . $from . ($totalScope ? ' WHERE ' . $totalScope : ''));
kirpi_table_bind($totalStmt, $totalParams);
$totalStmt->execute();
$total = (int) $totalStmt->fetchColumn();
$filteredStmt = db()->prepare('SELECT COUNT(*) FROM ' . $from . $whereSql);
kirpi_table_bind($filteredStmt, $params);
$filteredStmt->execute();
$filtered = (int) $filteredStmt->fetchColumn();
$sql = 'SELECT ' . $select . ' FROM ' . $from . $whereSql . ' ORDER BY ' . kirpi_table_order_sql($request, $columns, 'r.id DESC') . ' LIMIT :length OFFSET :start';
$stmt = db()->prepare($sql);
kirpi_table_bind($stmt, $params);
$stmt->bindValue(':length', $request['length'], PDO::PARAM_INT);
$stmt->bindValue(':start', $request['start'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($rows as &$row) {
    $row['row_key'] = 'relationships-' . (int) $row['id'];
}
unset($row);
kirpi_table_response($request, $total, $filtered, $rows);
