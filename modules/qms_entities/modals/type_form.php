<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_entities/language.php';
require_once BASE_PATH . '/modules/qms_entities/helpers.php';
$companyId=(int)($_GET['company_id']??0);$entityType=trim((string)($_GET['entity_type']??''));$settings=qms_entities_type_settings($companyId,$entityType);
if(!$settings||!organization_company_in_scope($companyId)||!check_permission('qms_entities.manage')){echo '<div class="modal-body"><div class="alert alert-danger">'.e(qms_entities_lang('permission_denied')).'</div></div>';exit;}
?>
<div class="modal-header"><h5 class="modal-title"><?php echo e(qms_entities_lang('edit_type_settings')); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<form id="qms-entity-type-form" action="<?php echo base_url('qms_entities/actions/save-type'); ?>" method="post" data-ajax="true" data-close-modal="true">
<div class="modal-body"><input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>"><input type="hidden" name="company_id" value="<?php echo $companyId; ?>"><input type="hidden" name="entity_type" value="<?php echo e($entityType); ?>">
 <div class="row g-3">
  <div class="col-md-6"><label class="form-label"><?php echo e(qms_entities_lang('company')); ?></label><input class="form-control" value="<?php echo e((string)($settings['company_name'] ?? $companyId)); ?>" readonly></div>
  <div class="col-md-6"><label class="form-label"><?php echo e(qms_entities_lang('entity_type')); ?></label><input class="form-control" value="<?php echo e($settings['display_name'].' ('.$entityType.')'); ?>" readonly></div>
  <div class="col-md-4"><label class="form-label"><?php echo e(qms_entities_lang('numbered')); ?></label><label class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="is_numbered" value="1" <?php echo (int)$settings['effective_is_numbered']===1?'checked':''; ?>><span class="form-check-label"><?php echo e(qms_entities_lang('active')); ?></span></label></div>
  <div class="col-md-4"><label class="form-label form-required"><?php echo e(qms_entities_lang('prefix')); ?></label><input name="entity_prefix" class="form-control" maxlength="20" value="<?php echo e((string)$settings['effective_prefix']); ?>"></div>
  <div class="col-md-12"><label class="form-label form-required"><?php echo e(qms_entities_lang('number_template')); ?></label><input name="template" class="form-control" maxlength="190" value="<?php echo e((string)$settings['effective_template']); ?>"><div class="form-hint">{company_code}, {entity_prefix}, {year}, {sequence:5}</div></div>
 </div>
</div><div class="modal-footer"><button type="button" class="btn me-auto" data-bs-dismiss="modal"><?php echo e(qms_entities_lang('cancel')); ?></button><button type="submit" class="btn btn-primary"><?php echo e(qms_entities_lang('save')); ?></button></div></form>
