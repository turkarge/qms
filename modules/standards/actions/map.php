<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/standards/helpers.php';
require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) json_response(['status'=>'error','message'=>'CSRF'],419);
if (!check_permission('standards.map')) json_response(['status'=>'error','message'=>standards_lang('permission_denied')],403);
try {
    $row = standards_map_requirement($_POST);
    kirpi_audit_log('map_requirement', 'standards', ['relationship_type'=>$row['relationship_type'] ?? null], 'standard_requirement', (int) ($_POST['requirement_id'] ?? 0), 'success');
    json_response(['status'=>'success','message'=>standards_lang('mapping_created'),'data'=>['resource'=>'mapping','row'=>$row]]);
} catch (InvalidArgumentException $e) {
    json_response(['status'=>'error','message'=>standards_lang($e->getMessage(), standards_lang('required_fields'))],422);
} catch (RuntimeException $e) {
    if ($e->getCode() === 403) json_response(['status'=>'error','message'=>standards_lang('permission_denied')],403);
    throw $e;
} catch (Throwable $e) {
    error_log('standards map error: ' . $e->getMessage());
    json_response(['status'=>'error','message'=>standards_lang('mapping_error')],500);
}
