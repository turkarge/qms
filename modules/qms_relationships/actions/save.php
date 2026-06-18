<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_relationships/language.php';
require_once BASE_PATH . '/modules/qms_relationships/helpers.php';
require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) json_response(['status'=>'error','message'=>'CSRF'],419);
if (!check_permission('qms_relationships.manage')) json_response(['status'=>'error','message'=>qms_relationships_lang('permission_denied')],403);
$id = (int) ($_POST['id'] ?? 0);
try {
    $row = qms_relationships_save($_POST);
    kirpi_audit_log($id > 0 ? 'update' : 'create', 'qms_relationships', ['relationship_type'=>$row['relationship_type'] ?? null, 'source_entity_id'=>$row['source_entity_id'] ?? null, 'target_entity_id'=>$row['target_entity_id'] ?? null], 'qms_relationship', (int) ($row['id'] ?? 0), 'success');
    json_response(['status'=>'success','message'=>qms_relationships_lang($id > 0 ? 'updated_success' : 'created_success'),'data'=>['resource'=>'relationships','row'=>$row]]);
} catch (InvalidArgumentException $e) {
    $key = $e->getMessage();
    json_response(['status'=>'error','message'=>qms_relationships_lang($key, qms_relationships_lang('required_fields'))],422);
} catch (RuntimeException $e) {
    if ($e->getCode() === 403) json_response(['status'=>'error','message'=>qms_relationships_lang('permission_denied')],403);
    throw $e;
} catch (Throwable $e) {
    error_log('qms relationship save error: ' . $e->getMessage());
    json_response(['status'=>'error','message'=>qms_relationships_lang('save_error')],500);
}
