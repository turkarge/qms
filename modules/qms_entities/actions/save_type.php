<?php
if(!defined('KIRPI_CORE_ENTRY'))exit;require_once BASE_PATH.'/modules/qms_entities/language.php';require_once BASE_PATH.'/modules/qms_entities/helpers.php';require_action('POST',true);
if(!verify_csrf_token($_POST['csrf_token']??null))json_response(['status'=>'error','message'=>'CSRF'],419);if(!check_permission('qms_entities.manage'))json_response(['status'=>'error','message'=>qms_entities_lang('permission_denied')],403);
try{$settings=qms_entities_save_type_settings($_POST);kirpi_audit_log('update_type_settings','qms_entities',['entity_type'=>$settings['entity_type']??null,'company_id'=>$settings['company_id']??null],'entity_type_settings',0,'success');json_response(['status'=>'success','message'=>qms_entities_lang('updated_success'),'data'=>['resource'=>'types','row'=>$settings]]);}
catch(InvalidArgumentException $e){json_response(['status'=>'error','message'=>qms_entities_lang($e->getMessage(),qms_entities_lang('required_fields'))],422);}
catch(RuntimeException $e){if($e->getCode()===403)json_response(['status'=>'error','message'=>qms_entities_lang('permission_denied')],403);throw $e;}
catch(Throwable $e){error_log('qms entity type save error: '.$e->getMessage());json_response(['status'=>'error','message'=>qms_entities_lang('save_error')],500);}
