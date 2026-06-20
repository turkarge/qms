<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/standards/helpers.php';
require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) json_response(['status'=>'error','message'=>'CSRF'],419);
if (!check_permission('standards.map')) json_response(['status'=>'error','message'=>standards_lang('permission_denied')],403);
try {
    $id = (int) ($_POST['id'] ?? 0);
    standards_unmap_requirement($id);
    kirpi_audit_log('unmap_requirement', 'standards', [], 'qms_relationship', $id, 'success');
    json_response(['status'=>'success','message'=>standards_lang('mapping_archived')]);
} catch (InvalidArgumentException $e) {
    json_response(['status'=>'error','message'=>standards_lang($e->getMessage(), standards_lang('invalid_record'))],422);
} catch (RuntimeException $e) {
    if ($e->getCode() === 403) json_response(['status'=>'error','message'=>standards_lang('permission_denied')],403);
    throw $e;
} catch (Throwable $e) {
    error_log('standards unmap error: ' . $e->getMessage());
    json_response(['status'=>'error','message'=>standards_lang('mapping_error')],500);
}
