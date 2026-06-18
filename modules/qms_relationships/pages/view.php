<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_relationships/language.php';
$config = [
    'endpoint' => base_url('ajax/qms_relationships/datatable'),
    'canManage' => check_permission('qms_relationships.manage'),
    'canArchive' => check_permission('qms_relationships.archive'),
    'labels' => [
        'edit' => qms_relationships_lang('edit'),
        'archive' => qms_relationships_lang('archive'),
        'active' => qms_relationships_lang('active'),
        'inactive' => qms_relationships_lang('inactive'),
        'archived' => qms_relationships_lang('archived'),
    ],
];
?>
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">Kirpi QMS+</div>
        <h2 class="page-title"><?php echo e(qms_relationships_lang('qms_relationships')); ?></h2>
        <div class="text-secondary mt-1"><?php echo e(qms_relationships_lang('hint')); ?></div>
      </div>
      <?php if (check_permission('qms_relationships.manage')): ?>
      <div class="col-auto ms-auto">
        <a href="#" class="btn btn-primary btn-modal-trigger" data-url="/ajax/qms_relationships/form" data-size="modal-lg">
          <i class="ti ti-plus me-2"></i><?php echo e(qms_relationships_lang('new_relationship')); ?>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="page-body">
  <div class="container-xl">
    <div class="card">
      <div class="card-body p-0">
        <table id="qms-relationships-table" class="table table-vcenter table-striped w-100 kirpi-data-table">
          <thead>
            <tr>
              <th><?php echo e(qms_relationships_lang('company')); ?></th>
              <th><?php echo e(qms_relationships_lang('source_entity')); ?></th>
              <th><?php echo e(qms_relationships_lang('target_entity')); ?></th>
              <th><?php echo e(qms_relationships_lang('relationship_type')); ?></th>
              <th><?php echo e(qms_relationships_lang('relationship_kind')); ?></th>
              <th><?php echo e(qms_relationships_lang('status')); ?></th>
              <th class="w-1"><i class="ti ti-settings"></i></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>
<script type="application/json" id="qms-relationships-config"><?php echo json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
