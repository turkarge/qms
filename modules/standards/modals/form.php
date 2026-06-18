<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/standards/helpers.php';

$resource = trim((string) ($_GET['resource'] ?? 'standards'));
$allowed = ['standards', 'versions', 'clauses', 'requirements', 'controls'];
if (!in_array($resource, $allowed, true)) $resource = 'standards';
$id = (int) ($_GET['id'] ?? 0);
$record = $id > 0 ? standards_row($resource, $id) : null;
if ($id > 0 && !$record) {
    echo '<div class="modal-body"><div class="alert alert-danger">' . e(standards_lang('invalid_record')) . '</div></div>';
    exit;
}
if ($record && !organization_company_in_scope((int) ($record['company_id'] ?? 0))) {
    echo '<div class="modal-body"><div class="alert alert-danger">' . e(standards_lang('permission_denied', 'Yetkiniz yok.')) . '</div></div>';
    exit;
}
if (($id > 0 && !check_permission('standards.edit')) || ($id <= 0 && !check_permission('standards.create'))) {
    echo '<div class="modal-body"><div class="alert alert-danger">' . e(standards_lang('permission_denied', 'Yetkiniz yok.')) . '</div></div>';
    exit;
}
$options = standards_select_options();
$v = static fn(string $key, string $default = ''): string => (string) ($record[$key] ?? $default);
$titleKey = $id > 0 ? 'edit_' . $resource : 'new_' . $resource;
?>
<div class="modal-header">
  <h5 class="modal-title"><?php echo e(standards_lang($titleKey, standards_lang($resource . '_tab'))); ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form id="standards-form" action="<?php echo base_url('standards/actions/save'); ?>" method="post" data-ajax="true" data-close-modal="true">
<div class="modal-body">
  <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
  <input type="hidden" name="resource" value="<?php echo e($resource); ?>">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
  <div class="row g-3">
    <?php if ($resource === 'standards'): ?>
      <div class="col-md-6"><label class="form-label form-required"><?php echo e(standards_lang('company')); ?></label><select name="company_id" class="form-select" required><option value=""></option><?php foreach ($options['companies'] as $company): ?><option value="<?php echo (int) $company['id']; ?>" <?php echo (int) $v('company_id') === (int) $company['id'] ? 'selected' : ''; ?>><?php echo e($company['company_code'] . ' - ' . $company['company_name']); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label form-required"><?php echo e(standards_lang('status')); ?></label><select name="status" class="form-select" required><?php foreach ($options['statuses'] as $status): ?><option value="<?php echo e($status); ?>" <?php echo $v('status', 'active') === $status ? 'selected' : ''; ?>><?php echo e(standards_lang($status)); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label form-required"><?php echo e(standards_lang('standard_code')); ?></label><input name="standard_code" class="form-control" maxlength="40" value="<?php echo e($v('standard_code')); ?>" required></div>
      <div class="col-md-8"><label class="form-label form-required"><?php echo e(standards_lang('standard_name')); ?></label><input name="standard_name" class="form-control" maxlength="190" value="<?php echo e($v('standard_name')); ?>" required></div>
      <div class="col-md-6"><label class="form-label"><?php echo e(standards_lang('owner_organization')); ?></label><input name="owner_organization" class="form-control" maxlength="120" value="<?php echo e($v('owner_organization')); ?>"></div>
      <div class="col-md-6"><label class="form-label"><?php echo e(standards_lang('category')); ?></label><input name="category" class="form-control" maxlength="80" value="<?php echo e($v('category')); ?>"></div>
    <?php elseif ($resource === 'versions'): ?>
      <div class="col-md-8"><label class="form-label form-required"><?php echo e(standards_lang('standard')); ?></label><select name="standard_id" class="form-select" required><option value=""></option><?php foreach ($options['standards'] as $standard): ?><option value="<?php echo (int) $standard['id']; ?>" <?php echo (int) $v('standard_id') === (int) $standard['id'] ? 'selected' : ''; ?>><?php echo e($standard['standard_code'] . ' - ' . $standard['standard_name']); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label form-required"><?php echo e(standards_lang('status')); ?></label><select name="status" class="form-select" required><?php foreach ($options['statuses'] as $status): ?><option value="<?php echo e($status); ?>" <?php echo $v('status', 'published') === $status ? 'selected' : ''; ?>><?php echo e(standards_lang($status)); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label form-required"><?php echo e(standards_lang('version_label')); ?></label><input name="version_label" class="form-control" maxlength="60" value="<?php echo e($v('version_label')); ?>" required></div>
      <div class="col-md-3"><label class="form-label"><?php echo e(standards_lang('published_on')); ?></label><input type="date" name="published_on" class="form-control" value="<?php echo e(substr($v('published_on'), 0, 10)); ?>"></div>
      <div class="col-md-3"><label class="form-label"><?php echo e(standards_lang('effective_from')); ?></label><input type="date" name="effective_from" class="form-control" value="<?php echo e(substr($v('effective_from'), 0, 10)); ?>"></div>
      <div class="col-md-3"><label class="form-label"><?php echo e(standards_lang('transition_until')); ?></label><input type="date" name="transition_until" class="form-control" value="<?php echo e(substr($v('transition_until'), 0, 10)); ?>"></div>
    <?php elseif ($resource === 'clauses'): ?>
      <div class="col-md-8"><label class="form-label form-required"><?php echo e(standards_lang('version')); ?></label><select name="version_id" class="form-select" required><option value=""></option><?php foreach ($options['versions'] as $version): ?><option value="<?php echo (int) $version['id']; ?>" <?php echo (int) $v('version_id') === (int) $version['id'] ? 'selected' : ''; ?>><?php echo e($version['standard_code'] . ':' . $version['version_label']); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label form-required"><?php echo e(standards_lang('status')); ?></label><select name="status" class="form-select" required><?php foreach ($options['statuses'] as $status): ?><option value="<?php echo e($status); ?>" <?php echo $v('status', 'active') === $status ? 'selected' : ''; ?>><?php echo e(standards_lang($status)); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label form-required"><?php echo e(standards_lang('clause_code')); ?></label><input name="clause_code" class="form-control" maxlength="40" value="<?php echo e($v('clause_code')); ?>" required></div>
      <div class="col-md-7"><label class="form-label form-required"><?php echo e(standards_lang('title')); ?></label><input name="title" class="form-control" maxlength="190" value="<?php echo e($v('title')); ?>" required></div>
      <div class="col-md-2"><label class="form-label"><?php echo e(standards_lang('sort_order')); ?></label><input type="number" name="sort_order" class="form-control" value="<?php echo e($v('sort_order', '0')); ?>"></div>
      <div class="col-12"><label class="form-label"><?php echo e(standards_lang('body')); ?></label><textarea name="body" class="form-control" rows="4"><?php echo e($v('body')); ?></textarea></div>
    <?php elseif ($resource === 'requirements'): ?>
      <div class="col-md-6"><label class="form-label form-required"><?php echo e(standards_lang('version')); ?></label><select name="version_id" class="form-select" required><option value=""></option><?php foreach ($options['versions'] as $version): ?><option value="<?php echo (int) $version['id']; ?>" <?php echo (int) $v('version_id') === (int) $version['id'] ? 'selected' : ''; ?>><?php echo e($version['standard_code'] . ':' . $version['version_label']); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label form-required"><?php echo e(standards_lang('clause')); ?></label><select name="clause_id" class="form-select" required><option value=""></option><?php foreach ($options['clauses'] as $clause): ?><option value="<?php echo (int) $clause['id']; ?>" <?php echo (int) $v('clause_id') === (int) $clause['id'] ? 'selected' : ''; ?>><?php echo e($clause['standard_code'] . ':' . $clause['version_label'] . ' / ' . $clause['clause_code'] . ' - ' . $clause['title']); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label form-required"><?php echo e(standards_lang('requirement_code')); ?></label><input name="requirement_code" class="form-control" maxlength="60" value="<?php echo e($v('requirement_code')); ?>" required></div>
      <div class="col-md-6"><label class="form-label form-required"><?php echo e(standards_lang('title')); ?></label><input name="title" class="form-control" maxlength="190" value="<?php echo e($v('title')); ?>" required></div>
      <div class="col-md-3"><label class="form-label form-required"><?php echo e(standards_lang('status')); ?></label><select name="status" class="form-select" required><?php foreach ($options['statuses'] as $status): ?><option value="<?php echo e($status); ?>" <?php echo $v('status', 'active') === $status ? 'selected' : ''; ?>><?php echo e(standards_lang($status)); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label"><?php echo e(standards_lang('verification_method')); ?></label><input name="verification_method" class="form-control" maxlength="80" value="<?php echo e($v('verification_method')); ?>"></div>
      <div class="col-md-6"><label class="form-label"><?php echo e(standards_lang('criticality')); ?></label><input name="criticality" class="form-control" maxlength="30" value="<?php echo e($v('criticality', 'normal')); ?>"></div>
      <div class="col-12"><label class="form-label form-required"><?php echo e(standards_lang('requirement_text')); ?></label><textarea name="requirement_text" class="form-control" rows="4" required><?php echo e($v('requirement_text')); ?></textarea></div>
    <?php else: ?>
      <div class="col-md-8"><label class="form-label form-required"><?php echo e(standards_lang('requirement')); ?></label><select name="requirement_id" class="form-select" required><option value=""></option><?php foreach ($options['requirements'] as $requirement): ?><option value="<?php echo (int) $requirement['id']; ?>" <?php echo (int) $v('requirement_id') === (int) $requirement['id'] ? 'selected' : ''; ?>><?php echo e($requirement['standard_code'] . ':' . $requirement['version_label'] . ' / ' . $requirement['requirement_code'] . ' - ' . $requirement['title']); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label form-required"><?php echo e(standards_lang('status')); ?></label><select name="status" class="form-select" required><?php foreach ($options['statuses'] as $status): ?><option value="<?php echo e($status); ?>" <?php echo $v('status', 'active') === $status ? 'selected' : ''; ?>><?php echo e(standards_lang($status)); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label form-required"><?php echo e(standards_lang('control_code')); ?></label><input name="control_code" class="form-control" maxlength="60" value="<?php echo e($v('control_code')); ?>" required></div>
      <div class="col-md-6"><label class="form-label form-required"><?php echo e(standards_lang('title')); ?></label><input name="title" class="form-control" maxlength="190" value="<?php echo e($v('title')); ?>" required></div>
      <div class="col-md-3"><label class="form-label"><?php echo e(standards_lang('control_type')); ?></label><input name="control_type" class="form-control" maxlength="60" value="<?php echo e($v('control_type')); ?>"></div>
      <div class="col-12"><label class="form-label form-required"><?php echo e(standards_lang('control_text')); ?></label><textarea name="control_text" class="form-control" rows="4" required><?php echo e($v('control_text')); ?></textarea></div>
    <?php endif; ?>
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn me-auto" data-bs-dismiss="modal"><?php echo e(standards_lang('cancel')); ?></button>
  <button type="submit" class="btn btn-primary"><?php echo e(standards_lang('save')); ?></button>
</div>
</form>
