<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_relationships/language.php';
require_once BASE_PATH . '/modules/qms_relationships/helpers.php';
require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) json_response(['status'=>'error','message'=>'CSRF'],419);
$id = (int) ($_POST['id'] ?? 0);
$row = qms_relationships_row($id);
if (!$row) json_response(['status'=>'error','message'=>qms_relationships_lang('invalid_record')],404);
if (!organization_company_in_scope((int) $row['company_id'])) json_response(['status'=>'error','message'=>qms_relationships_lang('permission_denied')],403);
try {
    $user = (int) (current_user()['id'] ?? 0) ?: null;
    $stmt = db()->prepare("UPDATE qms_entity_relationships SET status='archived',archived_at=NOW(),archived_by_user_id=:user,updated_by_user_id=:user WHERE id=:id AND status<>'archived'");
    $stmt->execute([':user'=>$user, ':id'=>$id]);
    kirpi_audit_log('archive', 'qms_relationships', [], 'qms_relationship', $id, 'success');
    json_response(['status'=>'success','message'=>qms_relationships_lang('archive_success')]);
} catch (Throwable $e) {
    error_log('qms relationship archive error: ' . $e->getMessage());
    json_response(['status'=>'error','message'=>qms_relationships_lang('archive_error')],500);
}
