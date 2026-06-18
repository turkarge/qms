<?php
require_once BASE_PATH.'/modules/organization/helpers.php';

function qms_entities_uuid(): string {
 $b=random_bytes(16);$b[6]=chr((ord($b[6])&0x0f)|0x40);$b[8]=chr((ord($b[8])&0x3f)|0x80);$h=bin2hex($b);return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20);
}
function qms_entities_allowed_statuses(): array { return ['draft','active','pending_approval','rejected','closed','cancelled','superseded','archived']; }
function qms_entities_type(string $entityType): ?array { $s=db()->prepare("SELECT * FROM qms_entity_types WHERE entity_type=:type AND status='active'");$s->execute([':type'=>$entityType]);return $s->fetch(PDO::FETCH_ASSOC)?:null; }
function qms_entities_type_settings(int $companyId,string $entityType): ?array {
 $type=qms_entities_type($entityType);if(!$type)return null;
 $company=db()->prepare('SELECT company_name FROM organization_companies WHERE id=:id');$company->execute([':id'=>$companyId]);$type['company_name']=(string)$company->fetchColumn();
 $s=db()->prepare('SELECT * FROM qms_entity_type_settings WHERE company_id=:company AND entity_type=:type');$s->execute([':company'=>$companyId,':type'=>$entityType]);$settings=$s->fetch(PDO::FETCH_ASSOC)?:[];
 $type['company_id']=$companyId;$type['effective_prefix']=$settings['entity_prefix']??$type['entity_prefix'];$type['effective_template']=$settings['template']??'{company_code}-{entity_prefix}-{year}-{sequence:5}';$type['effective_is_numbered']=array_key_exists('is_numbered',$settings)&&$settings['is_numbered']!==null?(int)$settings['is_numbered']:(int)$type['is_numbered'];
 return $type;
}
function qms_entities_next_code(int $companyId,string $entityType,?int $facilityId=null,?int $year=null): string {
 $own=!db()->inTransaction();if($own)db()->beginTransaction();try{
  $type=qms_entities_type_settings($companyId,$entityType);if(!$type||!(int)$type['effective_is_numbered']||trim((string)$type['effective_prefix'])==='')throw new InvalidArgumentException('Entity type is not numbered.');
  $company=db()->prepare('SELECT company_code FROM organization_companies WHERE id=:id');$company->execute([':id'=>$companyId]);$companyCode=(string)$company->fetchColumn();if($companyCode==='')throw new InvalidArgumentException('Invalid company.');
  if($facilityId&& !organization_record_belongs_to_company('organization_units',$facilityId,$companyId))throw new InvalidArgumentException('Invalid facility scope.');
  $period=(string)($year??(int)date('Y'));$insert=db()->prepare("INSERT INTO qms_number_sequences(company_id,facility_id,entity_type,period_key,template) VALUES(:company,:facility,:type,:period,:template) ON DUPLICATE KEY UPDATE template=VALUES(template),updated_at=CURRENT_TIMESTAMP");$insert->execute([':company'=>$companyId,':facility'=>$facilityId?:null,':type'=>$entityType,':period'=>$period,':template'=>(string)$type['effective_template']]);
  $select=db()->prepare('SELECT id,template,last_sequence FROM qms_number_sequences WHERE company_id=:company AND facility_scope_key=:facility_key AND entity_type=:type AND period_key=:period FOR UPDATE');$select->execute([':company'=>$companyId,':facility_key'=>$facilityId?:0,':type'=>$entityType,':period'=>$period]);$sequence=$select->fetch(PDO::FETCH_ASSOC);if(!$sequence)throw new RuntimeException('Sequence row missing.');
  $next=(int)$sequence['last_sequence']+1;$update=db()->prepare('UPDATE qms_number_sequences SET last_sequence=:next WHERE id=:id');$update->execute([':next'=>$next,':id'=>$sequence['id']]);$padded=str_pad((string)$next,5,'0',STR_PAD_LEFT);$code=strtr((string)$sequence['template'],['{company_code}'=>$companyCode,'{entity_prefix}'=>(string)$type['effective_prefix'],'{year}'=>$period,'{sequence:5}'=>$padded,'{sequence}'=>(string)$next]);if($own)db()->commit();return $code;
 }catch(Throwable $e){if($own&&db()->inTransaction())db()->rollBack();throw $e;}
}
function qms_entities_save_type_settings(array $data): array {
 $company=(int)($data['company_id']??0);$entityType=trim((string)($data['entity_type']??''));$prefix=trim((string)($data['entity_prefix']??''));$template=trim((string)($data['template']??''));$isNumbered=isset($data['is_numbered'])?1:0;
 if($company<=0||$entityType===''||!qms_entities_type($entityType))throw new InvalidArgumentException('required_fields');if(!organization_company_in_scope($company))throw new RuntimeException('permission_denied',403);if($isNumbered&&($prefix===''||$template===''))throw new InvalidArgumentException('required_fields');
 $stmt=db()->prepare('INSERT INTO qms_entity_type_settings(company_id,entity_type,entity_prefix,template,is_numbered,updated_by_user_id) VALUES(:company,:type,:prefix,:template,:numbered,:user) ON DUPLICATE KEY UPDATE entity_prefix=VALUES(entity_prefix),template=VALUES(template),is_numbered=VALUES(is_numbered),updated_by_user_id=VALUES(updated_by_user_id)');
 $stmt->execute([':company'=>$company,':type'=>$entityType,':prefix'=>$prefix?:null,':template'=>$template?:null,':numbered'=>$isNumbered,':user'=>(int)(current_user()['id']??0)?:null]);
 return qms_entities_type_settings($company,$entityType)??[];
}
function qms_entities_register(array $data): array {
 $company=(int)($data['company_id']??0);$typeKey=trim((string)($data['entity_type']??''));$domainTable=trim((string)($data['domain_table']??''));$domainId=(int)($data['domain_record_id']??0);$title=trim((string)($data['title']??''));$status=(string)($data['status']??'draft');
 if($company<=0||$typeKey===''||$domainTable===''||$domainId<=0||$title===''||!in_array($status,qms_entities_allowed_statuses(),true))throw new InvalidArgumentException('Invalid managed entity data.');$type=qms_entities_type($typeKey);if(!$type)throw new InvalidArgumentException('Invalid entity type.');
 $own=!db()->inTransaction();if($own)db()->beginTransaction();try{$code=trim((string)($data['entity_code']??''));if($code===''&&(int)$type['is_numbered'])$code=qms_entities_next_code($company,$typeKey,(int)($data['facility_id']??0)?:null);if($code==='')throw new InvalidArgumentException('Entity code is required.');
  $user=(int)($data['actor_user_id']??(current_user()['id']??0));$metadata=$data['metadata']??null;$stmt=db()->prepare('INSERT INTO qms_entities(entity_uid,entity_type,domain_table,domain_record_id,entity_code,title,description,company_id,facility_id,department_id,team_id,owner_user_id,status,retention_class,metadata,created_by_user_id,updated_by_user_id) VALUES(:uid,:type,:domain_table,:domain_id,:code,:title,:description,:company,:facility,:department,:team,:owner,:status,:retention,:metadata,:created_user,:updated_user)');$stmt->execute([':uid'=>qms_entities_uuid(),':type'=>$typeKey,':domain_table'=>$domainTable,':domain_id'=>$domainId,':code'=>$code,':title'=>$title,':description'=>trim((string)($data['description']??''))?:null,':company'=>$company,':facility'=>(int)($data['facility_id']??0)?:null,':department'=>(int)($data['department_id']??0)?:null,':team'=>(int)($data['team_id']??0)?:null,':owner'=>(int)($data['owner_user_id']??0)?:null,':status'=>$status,':retention'=>(string)($data['retention_class']??$type['retention_class']),':metadata'=>$metadata===null?null:json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),':created_user'=>$user?:null,':updated_user'=>$user?:null]);$id=(int)db()->lastInsertId();if($own)db()->commit();return qms_entities_row($id)??[];
 }catch(Throwable $e){if($own&&db()->inTransaction())db()->rollBack();throw $e;}
}
function qms_entities_row(int $id): ?array { $s=db()->prepare('SELECT x.*,c.company_name,u.name AS owner_name,t.display_name AS entity_type_name FROM qms_entities x JOIN organization_companies c ON c.id=x.company_id JOIN qms_entity_types t ON t.entity_type=x.entity_type LEFT JOIN users u ON u.id=x.owner_user_id WHERE x.id=:id');$s->execute([':id'=>$id]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r)return null;$r['row_key']='entities-'.$id;return $r; }

function qms_entities_select_options(): array {
 $organization=organization_select_options();
 $users=db()->query("SELECT u.id,u.name,u.email,GROUP_CONCAT(DISTINCT a.company_id ORDER BY a.company_id) AS company_ids FROM users u JOIN organization_user_assignments a ON a.user_id=u.id AND a.status='active' AND (a.starts_at IS NULL OR a.starts_at<=NOW()) AND (a.ends_at IS NULL OR a.ends_at>=NOW()) WHERE u.is_active=1 GROUP BY u.id,u.name,u.email ORDER BY u.name")->fetchAll(PDO::FETCH_ASSOC)?:[];
 $allowed=array_flip(array_map(static fn(array $company):int=>(int)$company['id'],$organization['companies']));
 foreach($users as &$user){$ids=array_values(array_filter(array_map('intval',explode(',',(string)$user['company_ids'])),static fn(int $id):bool=>isset($allowed[$id])));$user['company_ids']=$ids;}unset($user);
 $users=array_values(array_filter($users,static fn(array $user):bool=>$user['company_ids']!==[]));
 return ['companies'=>$organization['companies'],'units'=>$organization['units'],'users'=>$users];
}

function qms_entities_user_in_company(int $userId,int $companyId): bool {
 if($userId<=0)return true;
 $s=db()->prepare("SELECT COUNT(*) FROM organization_user_assignments WHERE user_id=:user AND company_id=:company AND status='active' AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW())");$s->execute([':user'=>$userId,':company'=>$companyId]);return (int)$s->fetchColumn()>0;
}

function qms_entities_update(int $id,array $data): array {
 $current=qms_entities_row($id);if(!$current)throw new InvalidArgumentException('invalid_record');
 $company=(int)($data['company_id']??$current['company_id']);$title=trim((string)($data['title']??$current['title']));$status=(string)($data['status']??$current['status']);$facility=(int)($data['facility_id']??0);$department=(int)($data['department_id']??0);$team=(int)($data['team_id']??0);$owner=(int)($data['owner_user_id']??0);
 if($company<=0||$title===''||!in_array($status,qms_entities_allowed_statuses(),true))throw new InvalidArgumentException('required_fields');
 if(($data['enforce_scope']??true)&&!organization_company_in_scope($company))throw new RuntimeException('permission_denied',403);
 foreach([[$facility,'facility'],[$department,'department'],[$team,'team']] as [$unitId,$type]){if($unitId>0){$s=db()->prepare('SELECT COUNT(*) FROM organization_units WHERE id=:id AND company_id=:company AND unit_type=:type');$s->execute([':id'=>$unitId,':company'=>$company,':type'=>$type]);if(!(int)$s->fetchColumn())throw new InvalidArgumentException('required_fields');}}
 if(!qms_entities_user_in_company($owner,$company))throw new InvalidArgumentException('required_fields');
 $closedAt=$status==='closed'&&!$current['closed_at']?date('Y-m-d H:i:s'):$current['closed_at'];$closedBy=$status==='closed'&&!$current['closed_by_user_id']?(int)(current_user()['id']??0):$current['closed_by_user_id'];
 $stmt=db()->prepare('UPDATE qms_entities SET title=:title,description=:description,company_id=:company,facility_id=:facility,department_id=:department,team_id=:team,owner_user_id=:owner,status=:status,closed_at=:closed_at,closed_by_user_id=:closed_by,updated_by_user_id=:user WHERE id=:id');
 $stmt->execute([':title'=>$title,':description'=>trim((string)($data['description']??''))?:null,':company'=>$company,':facility'=>$facility?:null,':department'=>$department?:null,':team'=>$team?:null,':owner'=>$owner?:null,':status'=>$status,':closed_at'=>$closedAt,':closed_by'=>$closedBy?:null,':user'=>(int)(current_user()['id']??0)?:null,':id'=>$id]);
 return qms_entities_row($id)??[];
}
