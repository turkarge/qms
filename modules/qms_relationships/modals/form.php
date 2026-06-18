<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_relationships/language.php';
require_once BASE_PATH . '/modules/qms_relationships/helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$record = $id > 0 ? qms_relationships_row($id) : null;
if ($id > 0 && !$record) {
    echo '<div class="modal-body"><div class="alert alert-danger">' . e(qms_relationships_lang('invalid_record')) . '</div></div>';
    exit;
}
if ($record && (!organization_company_in_scope((int) $record['company_id']) || !check_permission('qms_relationships.manage'))) {
    echo '<div class="modal-body"><div class="alert alert-danger">' . e(qms_relationships_lang('permission_denied')) . '</div></div>';
    exit;
}

$options = qms_relationships_select_options();
$v = static fn(string $key, string $default = ''): string => (string) ($record[$key] ?? $default);
?>
<div class="modal-header">
  <h5 class="modal-title"><?php echo e(qms_relationships_lang($id > 0 ? 'edit_relationship' : 'new_relationship')); ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form id="qms-relationships-form" action="<?php echo base_url('qms_relationships/actions/save'); ?>" method="post" data-ajax="true" data-close-modal="true">
<div class="modal-body">
  <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label form-required"><?php echo e(qms_relationships_lang('company')); ?></label>
      <select name="company_id" class="form-select" data-qms-relationship-company required>
        <option value=""><?php echo e(qms_relationships_lang('select_company')); ?></option>
        <?php foreach ($options['companies'] as $company): ?>
          <option value="<?php echo (int) $company['id']; ?>" <?php echo (int) $v('company_id') === (int) $company['id'] ? 'selected' : ''; ?>><?php echo e($company['company_code'] . ' - ' . $company['company_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label form-required"><?php echo e(qms_relationships_lang('relationship_type')); ?></label>
      <select name="relationship_type" class="form-select" required>
        <option value=""><?php echo e(qms_relationships_lang('select_type')); ?></option>
        <?php foreach ($options['types'] as $type): ?>
          <option value="<?php echo e((string) $type['relationship_type']); ?>" data-kind="<?php echo e((string) $type['relationship_kind']); ?>" <?php echo $v('relationship_type') === (string) $type['relationship_type'] ? 'selected' : ''; ?>><?php echo e((string) $type['display_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label form-required"><?php echo e(qms_relationships_lang('source_entity')); ?></label>
      <select name="source_entity_id" class="form-select" data-company-entity required>
        <option value=""><?php echo e(qms_relationships_lang('select_entity')); ?></option>
        <?php foreach ($options['entities'] as $entity): ?>
          <option value="<?php echo (int) $entity['id']; ?>" data-company-id="<?php echo (int) $entity['company_id']; ?>" <?php echo (int) $v('source_entity_id') === (int) $entity['id'] ? 'selected' : ''; ?>><?php echo e($entity['entity_code'] . ' - ' . $entity['title']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label form-required"><?php echo e(qms_relationships_lang('target_entity')); ?></label>
      <select name="target_entity_id" class="form-select" data-company-entity required>
        <option value=""><?php echo e(qms_relationships_lang('select_entity')); ?></option>
        <?php foreach ($options['entities'] as $entity): ?>
          <option value="<?php echo (int) $entity['id']; ?>" data-company-id="<?php echo (int) $entity['company_id']; ?>" <?php echo (int) $v('target_entity_id') === (int) $entity['id'] ? 'selected' : ''; ?>><?php echo e($entity['entity_code'] . ' - ' . $entity['title']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label"><?php echo e(qms_relationships_lang('evidence_strength')); ?></label>
      <input name="evidence_strength" class="form-control" maxlength="30" value="<?php echo e($v('evidence_strength')); ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label form-required"><?php echo e(qms_relationships_lang('status')); ?></label>
      <select name="status" class="form-select" required>
        <?php foreach ($options['statuses'] as $status): ?>
          <option value="<?php echo e($status); ?>" <?php echo $v('status', 'active') === $status ? 'selected' : ''; ?>><?php echo e(qms_relationships_lang($status)); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label"><?php echo e(qms_relationships_lang('valid_from')); ?></label>
      <input type="date" name="valid_from" class="form-control" value="<?php echo e(substr($v('valid_from'), 0, 10)); ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label"><?php echo e(qms_relationships_lang('valid_until')); ?></label>
      <input type="date" name="valid_until" class="form-control" value="<?php echo e(substr($v('valid_until'), 0, 10)); ?>">
    </div>
    <div class="col-12">
      <label class="form-label"><?php echo e(qms_relationships_lang('description')); ?></label>
      <textarea name="description" class="form-control" rows="3"><?php echo e($v('description')); ?></textarea>
    </div>
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn me-auto" data-bs-dismiss="modal"><?php echo e(qms_relationships_lang('cancel')); ?></button>
  <button type="submit" class="btn btn-primary"><?php echo e(qms_relationships_lang('save')); ?></button>
</div>
</form>
