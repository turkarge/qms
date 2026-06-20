<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/standards/helpers.php';

$requirementId = (int) ($_GET['requirement_id'] ?? 0);
$requirement = standards_row('requirements', $requirementId);
$requirementEntity = standards_requirement_entity($requirementId);
if (!$requirement || !$requirementEntity || !organization_company_in_scope((int) $requirement['company_id'])) {
    echo '<div class="modal-body"><div class="alert alert-danger">' . e(standards_lang('invalid_record')) . '</div></div>';
    exit;
}
$canMap = check_permission('standards.map');
$entities = standards_mapping_entity_options((int) $requirement['company_id']);
$mappings = standards_requirement_mappings($requirementId);
$types = array_values(array_filter(qms_relationships_types(), static fn(array $type): bool => in_array((string) $type['relationship_type'], ['satisfies_requirement', 'provides_evidence_for', 'references'], true)));
?>
<div class="modal-header">
  <h5 class="modal-title"><?php echo e(standards_lang('requirement_mapping')); ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
  <div class="alert alert-info mb-3">
    <div class="fw-bold"><?php echo e($requirement['standard_code'] . ':' . $requirement['version_label'] . ' / ' . $requirement['requirement_code']); ?></div>
    <div><?php echo e($requirement['title']); ?></div>
  </div>
  <?php if ($canMap): ?>
  <form id="standards-mapping-form" action="<?php echo base_url('standards/actions/map'); ?>" method="post" data-ajax="true">
    <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
    <input type="hidden" name="requirement_id" value="<?php echo $requirementId; ?>">
    <div class="row g-3">
      <div class="col-md-7">
        <label class="form-label form-required"><?php echo e(standards_lang('source_entity')); ?></label>
        <select name="source_entity_id" class="form-select" required>
          <option value=""><?php echo e(standards_lang('select_entity')); ?></option>
          <?php foreach ($entities as $entity): ?>
            <option value="<?php echo (int) $entity['id']; ?>"><?php echo e($entity['entity_code'] . ' - ' . $entity['title'] . ' (' . $entity['entity_type'] . ')'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label form-required"><?php echo e(standards_lang('relationship_type')); ?></label>
        <select name="relationship_type" class="form-select" required>
          <?php foreach ($types as $type): ?>
            <option value="<?php echo e((string) $type['relationship_type']); ?>"><?php echo e((string) $type['display_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label"><?php echo e(standards_lang('description')); ?></label>
        <textarea name="description" class="form-control" rows="2"></textarea>
      </div>
      <div class="col-12 text-end"><button type="submit" class="btn btn-primary"><i class="ti ti-link me-2"></i><?php echo e(standards_lang('map_requirement')); ?></button></div>
    </div>
  </form>
  <hr>
  <?php endif; ?>
  <div class="subheader mb-2"><?php echo e(standards_lang('current_mappings')); ?></div>
  <?php if (!$mappings): ?>
    <div class="text-secondary"><?php echo e(standards_lang('no_mappings')); ?></div>
  <?php else: ?>
    <div class="list-group list-group-flush border rounded">
      <?php foreach ($mappings as $mapping): ?>
      <div class="list-group-item d-flex align-items-center gap-3">
        <div class="flex-fill">
          <div class="fw-medium"><?php echo e($mapping['source_code'] . ' - ' . $mapping['source_title']); ?></div>
          <div class="text-secondary small"><?php echo e($mapping['relationship_type_name']); ?><?php echo !empty($mapping['description']) ? ' - ' . e((string) $mapping['description']) : ''; ?></div>
        </div>
        <?php if ($canMap): ?><button type="button" class="btn btn-sm btn-outline-danger js-standards-unmap" data-id="<?php echo (int) $mapping['id']; ?>"><i class="ti ti-link-off"></i></button><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal"><?php echo e(standards_lang('close')); ?></button></div>
