<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_entities/language.php';
require_once BASE_PATH . '/modules/qms_entities/helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$record = qms_entities_row($id);
if (!$record) {
    echo '<div class="modal-body"><div class="alert alert-danger">' . e(qms_entities_lang('invalid_record')) . '</div></div>';
    exit;
}
if (!organization_company_in_scope((int) $record['company_id']) || !check_permission('qms_entities.manage')) {
    echo '<div class="modal-body"><div class="alert alert-danger">' . e(qms_entities_lang('permission_denied')) . '</div></div>';
    exit;
}
$options = qms_entities_select_options();
$v = static fn(string $key, string $default = ''): string => (string) ($record[$key] ?? $default);
?>
<div class="modal-header"><h5 class="modal-title"><?php echo e(qms_entities_lang('edit_entity')); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<form id="qms-entities-form" action="<?php echo base_url('qms_entities/actions/save'); ?>" method="post" data-ajax="true" data-close-modal="true">
<div class="modal-body">
  <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
  <div class="alert alert-info"><?php echo e(qms_entities_lang('locked_identity_hint')); ?></div>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label"><?php echo e(qms_entities_lang('entity_code')); ?></label><input class="form-control" value="<?php echo e($v('entity_code')); ?>" readonly></div>
    <div class="col-md-4"><label class="form-label"><?php echo e(qms_entities_lang('entity_type')); ?></label><input class="form-control" value="<?php echo e($v('entity_type_name')); ?>" readonly></div>
    <div class="col-md-4"><label class="form-label"><?php echo e(qms_entities_lang('domain_source')); ?></label><input class="form-control" value="<?php echo e($v('domain_table') . '#' . $v('domain_record_id')); ?>" readonly></div>
    <div class="col-md-6"><label class="form-label form-required"><?php echo e(qms_entities_lang('company')); ?></label><select name="company_id" class="form-select" data-qms-entity-company required><option value=""></option><?php foreach($options['companies'] as $company): ?><option value="<?php echo (int)$company['id']; ?>" <?php echo (int)$v('company_id')===(int)$company['id']?'selected':''; ?>><?php echo e($company['company_code'].' - '.$company['company_name']); ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label form-required"><?php echo e(qms_entities_lang('status')); ?></label><select name="status" class="form-select" required><?php foreach(qms_entities_allowed_statuses() as $status): ?><option value="<?php echo e($status); ?>" <?php echo $v('status')===$status?'selected':''; ?>><?php echo e(qms_entities_lang($status)); ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><label class="form-label form-required"><?php echo e(qms_entities_lang('title')); ?></label><input name="title" class="form-control" maxlength="190" value="<?php echo e($v('title')); ?>" required></div>
    <div class="col-12"><label class="form-label"><?php echo e(qms_entities_lang('description')); ?></label><textarea name="description" class="form-control" rows="3"><?php echo e($v('description')); ?></textarea></div>
    <div class="col-md-4"><label class="form-label"><?php echo e(qms_entities_lang('facility')); ?></label><select name="facility_id" class="form-select" data-company-unit data-unit-type="facility"><option value=""><?php echo e(qms_entities_lang('none')); ?></option><?php foreach($options['units'] as $unit): if($unit['unit_type']!=='facility') continue; ?><option value="<?php echo (int)$unit['id']; ?>" data-company-id="<?php echo (int)$unit['company_id']; ?>" <?php echo (int)$v('facility_id')===(int)$unit['id']?'selected':''; ?>><?php echo e($unit['unit_code'].' - '.$unit['unit_name']); ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label"><?php echo e(qms_entities_lang('department')); ?></label><select name="department_id" class="form-select" data-company-unit data-unit-type="department"><option value=""><?php echo e(qms_entities_lang('none')); ?></option><?php foreach($options['units'] as $unit): if($unit['unit_type']!=='department') continue; ?><option value="<?php echo (int)$unit['id']; ?>" data-company-id="<?php echo (int)$unit['company_id']; ?>" <?php echo (int)$v('department_id')===(int)$unit['id']?'selected':''; ?>><?php echo e($unit['unit_code'].' - '.$unit['unit_name']); ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label"><?php echo e(qms_entities_lang('team')); ?></label><select name="team_id" class="form-select" data-company-unit data-unit-type="team"><option value=""><?php echo e(qms_entities_lang('none')); ?></option><?php foreach($options['units'] as $unit): if($unit['unit_type']!=='team') continue; ?><option value="<?php echo (int)$unit['id']; ?>" data-company-id="<?php echo (int)$unit['company_id']; ?>" <?php echo (int)$v('team_id')===(int)$unit['id']?'selected':''; ?>><?php echo e($unit['unit_code'].' - '.$unit['unit_name']); ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label"><?php echo e(qms_entities_lang('owner')); ?></label><select name="owner_user_id" class="form-select" data-company-user><option value=""><?php echo e(qms_entities_lang('none')); ?></option><?php foreach($options['users'] as $user): ?><option value="<?php echo (int)$user['id']; ?>" data-company-ids="<?php echo e(implode(',', $user['company_ids'])); ?>" <?php echo (int)$v('owner_user_id')===(int)$user['id']?'selected':''; ?>><?php echo e($user['name'].' ('.$user['email'].')'); ?></option><?php endforeach; ?></select></div>
  </div>
</div>
<div class="modal-footer"><button type="button" class="btn me-auto" data-bs-dismiss="modal"><?php echo e(qms_entities_lang('cancel')); ?></button><button type="submit" class="btn btn-primary"><?php echo e(qms_entities_lang('save')); ?></button></div>
</form>
