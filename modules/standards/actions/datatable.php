<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/standards/helpers.php';
require_action('GET', true);

$resource = trim((string) ($_GET['resource'] ?? 'standards'));
$request = kirpi_table_request();
$params = [];
$where = [];

$defs = [
    'standards' => [
        'from' => 'standards_catalog x JOIN organization_companies c ON c.id=x.company_id',
        'select' => 'x.id,x.company_id,c.company_name,x.standard_code,x.standard_name,x.owner_organization,x.category,x.status,x.created_at',
        'columns' => ['company_name'=>'c.company_name','standard_code'=>'x.standard_code','standard_name'=>'x.standard_name','status'=>'x.status','created_at'=>'x.created_at'],
        'search' => ['c.company_name','x.standard_code','x.standard_name','x.owner_organization','x.category'],
        'scope' => 'x.company_id',
        'order' => 'c.company_name ASC,x.standard_code ASC',
    ],
    'versions' => [
        'from' => 'standards_versions x JOIN standards_catalog s ON s.id=x.standard_id JOIN organization_companies c ON c.id=s.company_id',
        'select' => 'x.id,s.company_id,c.company_name,s.standard_code,s.standard_name,x.version_label,x.status,x.published_on,x.effective_from,x.created_at',
        'columns' => ['company_name'=>'c.company_name','standard_code'=>'s.standard_code','version_label'=>'x.version_label','status'=>'x.status','created_at'=>'x.created_at'],
        'search' => ['c.company_name','s.standard_code','s.standard_name','x.version_label'],
        'scope' => 's.company_id',
        'order' => 'c.company_name ASC,s.standard_code ASC,x.version_label DESC',
    ],
    'requirements' => [
        'from' => 'standards_requirements x JOIN standards_versions v ON v.id=x.version_id JOIN standards_catalog s ON s.id=v.standard_id JOIN standards_clauses cl ON cl.id=x.clause_id JOIN organization_companies c ON c.id=s.company_id',
        'select' => 'x.id,s.company_id,c.company_name,s.standard_code,s.standard_name,v.version_label,cl.clause_code,x.requirement_code,x.title,x.criticality,x.status,x.created_at',
        'columns' => ['company_name'=>'c.company_name','standard_code'=>'s.standard_code','version_label'=>'v.version_label','clause_code'=>'cl.clause_code','requirement_code'=>'x.requirement_code','title'=>'x.title','status'=>'x.status'],
        'search' => ['c.company_name','s.standard_code','s.standard_name','v.version_label','cl.clause_code','x.requirement_code','x.title','x.requirement_text'],
        'scope' => 's.company_id',
        'order' => 'c.company_name ASC,s.standard_code ASC,v.version_label DESC,x.requirement_code ASC',
    ],
    'controls' => [
        'from' => 'standards_controls x JOIN standards_requirements r ON r.id=x.requirement_id JOIN standards_versions v ON v.id=r.version_id JOIN standards_catalog s ON s.id=v.standard_id JOIN organization_companies c ON c.id=s.company_id',
        'select' => 'x.id,s.company_id,c.company_name,s.standard_code,s.standard_name,v.version_label,r.requirement_code,x.control_code,x.title,x.control_type,x.status,x.created_at',
        'columns' => ['company_name'=>'c.company_name','standard_code'=>'s.standard_code','requirement_code'=>'r.requirement_code','control_code'=>'x.control_code','title'=>'x.title','status'=>'x.status'],
        'search' => ['c.company_name','s.standard_code','s.standard_name','r.requirement_code','x.control_code','x.title','x.control_text'],
        'scope' => 's.company_id',
        'order' => 'c.company_name ASC,s.standard_code ASC,r.requirement_code ASC,x.control_code ASC',
    ],
];
if (!isset($defs[$resource])) $resource = 'standards';
$def = $defs[$resource];
$scope = organization_scope_where($def['scope'], $params);
if ($scope !== null) $where[] = $scope;
if ($request['search'] !== '') {
    $parts = [];
    foreach ($def['search'] as $i => $column) {
        $key = ':s' . $i;
        $parts[] = $column . ' LIKE ' . $key;
        $params[$key] = '%' . $request['search'] . '%';
    }
    $where[] = '(' . implode(' OR ', $parts) . ')';
}
foreach (kirpi_table_column_searches($request) as $name => $value) {
    if (!isset($def['columns'][$name])) continue;
    $key = ':c' . preg_replace('/\W/', '', $name);
    $where[] = $def['columns'][$name] . ' LIKE ' . $key;
    $params[$key] = '%' . $value . '%';
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$totalParams = [];
$totalScope = organization_scope_where($def['scope'], $totalParams);
$totalStmt = db()->prepare('SELECT COUNT(*) FROM ' . $def['from'] . ($totalScope ? ' WHERE ' . $totalScope : ''));
kirpi_table_bind($totalStmt, $totalParams);
$totalStmt->execute();
$total = (int) $totalStmt->fetchColumn();
$filteredStmt = db()->prepare('SELECT COUNT(*) FROM ' . $def['from'] . $whereSql);
kirpi_table_bind($filteredStmt, $params);
$filteredStmt->execute();
$filtered = (int) $filteredStmt->fetchColumn();
$stmt = db()->prepare('SELECT ' . $def['select'] . ' FROM ' . $def['from'] . $whereSql . ' ORDER BY ' . kirpi_table_order_sql($request, $def['columns'], $def['order']) . ' LIMIT :length OFFSET :start');
kirpi_table_bind($stmt, $params);
$stmt->bindValue(':length', $request['length'], PDO::PARAM_INT);
$stmt->bindValue(':start', $request['start'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($rows as &$row) {
    $row['row_key'] = $resource . '-' . (int) $row['id'];
}
unset($row);
kirpi_table_response($request, $total, $filtered, $rows);
