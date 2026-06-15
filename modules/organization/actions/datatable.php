<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/organization/language.php';
require_once BASE_PATH . '/modules/organization/helpers.php';
require_action('GET', true);

$resource = trim((string) ($_GET['resource'] ?? 'companies'));
if (!organization_resource_config($resource)) json_response(['error' => organization_lang('invalid_resource')], 422);
$request = kirpi_table_request();
$search = $request['search'];
$status = trim((string) ($_GET['status'] ?? ''));
$params = [];
$where = [];
if ($status !== '') { $where[] = 'x.status = :status'; $params[':status'] = $status; }

$definitions = [
 'companies' => [
   'from' => 'organization_companies x', 'search' => ['x.company_code','x.company_name','x.legal_name'],
   'columns' => ['company_code'=>'x.company_code','company_name'=>'x.company_name','legal_name'=>'x.legal_name','status'=>'x.status','updated_at'=>'x.updated_at'],
   'select' => 'x.id,x.company_code,x.company_name,x.legal_name,x.status,x.created_at,x.updated_at', 'fallback'=>'x.company_name ASC'],
 'units' => [
   'from' => 'organization_units x JOIN organization_companies c ON c.id=x.company_id LEFT JOIN organization_units p ON p.id=x.parent_unit_id',
   'search' => ['x.unit_code','x.unit_name','x.unit_type','c.company_name','p.unit_name'],
   'columns' => ['unit_code'=>'x.unit_code','unit_name'=>'x.unit_name','unit_type'=>'x.unit_type','company_name'=>'c.company_name','parent_name'=>'p.unit_name','status'=>'x.status','updated_at'=>'x.updated_at'],
   'select' => 'x.id,x.company_id,x.unit_code,x.unit_name,x.unit_type,x.status,x.created_at,x.updated_at,c.company_name,p.unit_name AS parent_name', 'fallback'=>'c.company_name,x.sort_order,x.unit_name'],
 'positions' => [
   'from' => 'organization_positions x JOIN organization_companies c ON c.id=x.company_id LEFT JOIN organization_units d ON d.id=x.department_unit_id',
   'search' => ['x.position_code','x.position_name','c.company_name','d.unit_name'],
   'columns' => ['position_code'=>'x.position_code','position_name'=>'x.position_name','company_name'=>'c.company_name','department_name'=>'d.unit_name','status'=>'x.status','updated_at'=>'x.updated_at'],
   'select' => 'x.id,x.company_id,x.position_code,x.position_name,x.status,x.created_at,x.updated_at,c.company_name,d.unit_name AS department_name', 'fallback'=>'x.position_name ASC'],
 'assignments' => [
   'from' => 'organization_user_assignments x JOIN users u ON u.id=x.user_id JOIN organization_companies c ON c.id=x.company_id LEFT JOIN organization_units ou ON ou.id=x.unit_id LEFT JOIN organization_positions p ON p.id=x.position_id',
   'search' => ['u.name','u.email','c.company_name','ou.unit_name','p.position_name','x.scope_mode'],
   'columns' => ['user_name'=>'u.name','company_name'=>'c.company_name','unit_name'=>'ou.unit_name','position_name'=>'p.position_name','scope_mode'=>'x.scope_mode','status'=>'x.status','updated_at'=>'x.updated_at'],
   'select' => 'x.id,x.user_id,x.company_id,x.unit_id,x.position_id,x.scope_mode,x.is_primary,x.status,x.starts_at,x.ends_at,x.created_at,x.updated_at,u.name AS user_name,u.email,c.company_name,ou.unit_name,p.position_name', 'fallback'=>'u.name ASC'],
];
$def = $definitions[$resource];
$scopeWhere = organization_scope_where($resource === 'companies' ? 'x.id' : 'x.company_id', $params);
if ($scopeWhere !== null) $where[] = $scopeWhere;
if ($search !== '') { $parts=[]; foreach($def['search'] as $i=>$col){$key=':search_'.$i;$parts[]="$col LIKE $key";$params[$key]='%'.$search.'%';} $where[]='('.implode(' OR ',$parts).')'; }
$columnSearches = kirpi_table_column_searches($request);
foreach ($columnSearches as $name=>$value) { if (!isset($def['columns'][$name])) continue; $key=':column_'.preg_replace('/[^a-z0-9_]/i','',$name); $where[]=$def['columns'][$name]." LIKE $key"; $params[$key]='%'.$value.'%'; }
$whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';
$orderSql = kirpi_table_order_sql($request,$def['columns'],$def['fallback']);
try {
 $total=(int)db()->query('SELECT COUNT(*) FROM '.$def['from'])->fetchColumn();
 $count=db()->prepare('SELECT COUNT(*) FROM '.$def['from'].' '.$whereSql); kirpi_table_bind($count,$params); $count->execute(); $filtered=(int)$count->fetchColumn();
 $stmt=db()->prepare('SELECT '.$def['select'].' FROM '.$def['from'].' '.$whereSql.' ORDER BY '.$orderSql.' LIMIT :length OFFSET :start'); kirpi_table_bind($stmt,$params); $stmt->bindValue(':length',$request['length'],PDO::PARAM_INT);$stmt->bindValue(':start',$request['start'],PDO::PARAM_INT);$stmt->execute();
 $rows=$stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
 foreach($rows as &$row){$row['row_key']=$resource.'-'.(int)$row['id'];$row['created_at_display']=kirpi_format_datetime((string)($row['created_at']??''));$row['updated_at_display']=kirpi_format_datetime((string)($row['updated_at']??''));}
 kirpi_table_response($request,$total,$filtered,$rows);
} catch(Throwable $e){error_log('organization datatable error: '.$e->getMessage());json_response(['draw'=>$request['draw'],'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[],'error'=>organization_lang('load_error')],500);}
