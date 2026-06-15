<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/organization/language.php';
require_once BASE_PATH . '/modules/organization/helpers.php';
require_action('POST', true);
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) json_response(['status'=>'error','message'=>'CSRF'],419);
$resource=trim((string)($_POST['resource']??''));$id=(int)($_POST['id']??0);$status=trim((string)($_POST['status']??''));$config=organization_resource_config($resource);
if(!$config||$id<=0||!in_array($status,['active','inactive'],true)) json_response(['status'=>'error','message'=>organization_lang('invalid_record')],422);
try{$companyColumn=$resource==='companies'?'id':'company_id';$scopeStmt=db()->prepare("SELECT {$companyColumn} AS company_id FROM {$config['table']} WHERE id=:id");$scopeStmt->execute([':id'=>$id]);$companyId=(int)$scopeStmt->fetchColumn();if($companyId<=0)json_response(['status'=>'error','message'=>organization_lang('invalid_record')],404);if(!organization_company_in_scope($companyId))json_response(['status'=>'error','message'=>organization_lang('permission_denied')],403);$stmt=db()->prepare("UPDATE {$config['table']} SET status=:status,updated_by_user_id=:user_id WHERE id=:id");$stmt->execute([':status'=>$status,':user_id'=>(int)(current_user()['id']??0),':id'=>$id]);kirpi_audit_log('toggle_status','organization',['resource'=>$resource,'status'=>$status],$config['entity'],$id,'success');json_response(['status'=>'success','message'=>organization_lang('status_updated')]);}catch(Throwable $e){error_log('organization status error: '.$e->getMessage());json_response(['status'=>'error','message'=>organization_lang('save_error')],500);}
