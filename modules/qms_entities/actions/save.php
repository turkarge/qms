<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_entities/language.php';
require_once BASE_PATH . '/modules/qms_entities/helpers.php';
require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) json_response(['status'=>'error','message'=>'CSRF'],419);
if (!check_permission('qms_entities.manage')) json_response(['status'=>'error','message'=>qms_entities_lang('permission_denied')],403);
$id = (int) ($_POST['id'] ?? 0);
try {
    $row = qms_entities_update($id, $_POST);
    kirpi_audit_log('update', 'qms_entities', ['domain_table'=>$row['domain_table'] ?? null, 'domain_record_id'=>$row['domain_record_id'] ?? null], 'managed_entity', $id, 'success');
    json_response(['status'=>'success','message'=>qms_entities_lang('updated_success'),'data'=>['resource'=>'entities','row'=>$row]]);
} catch (InvalidArgumentException $e) {
    $key = $e->getMessage();
    json_response(['status'=>'error','message'=>qms_entities_lang($key, qms_entities_lang('required_fields'))],422);
} catch (RuntimeException $e) {
    if ($e->getCode() === 403) json_response(['status'=>'error','message'=>qms_entities_lang('permission_denied')],403);
    throw $e;
} catch (Throwable $e) {
    error_log('qms entity save error: '.$e->getMessage());
    json_response(['status'=>'error','message'=>qms_entities_lang('save_error')],500);
}
