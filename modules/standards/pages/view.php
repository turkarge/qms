<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/standards/language.php';
$activeCompany = function_exists('organization_active_company') ? organization_active_company() : null;
$config = [
    'endpoint' => base_url('ajax/standards/datatable'),
    'canCreate' => check_permission('standards.create'),
    'canEdit' => check_permission('standards.edit'),
    'canMap' => check_permission('standards.map'),
    'labels' => [
        'active' => standards_lang('active'),
        'draft' => standards_lang('draft'),
        'published' => standards_lang('published'),
        'archived' => standards_lang('archived'),
        'edit' => standards_lang('edit'),
        'mapping' => standards_lang('requirement_mapping'),
    ],
];
?>
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">Kirpi QMS+</div>
        <h2 class="page-title"><?php echo e(standards_lang('standards')); ?></h2>
        <div class="text-secondary mt-1"><?php echo e(standards_lang('hint')); ?></div>
        <div class="text-secondary small mt-1"><?php echo e(standards_lang('active_company_prefix') . ($activeCompany['company_name'] ?? standards_lang('no_active_company'))); ?></div>
      </div>
      <?php if (check_permission('standards.create')): ?>
      <div class="col-auto ms-auto">
        <div class="btn-list">
          <a href="#" class="btn btn-primary btn-modal-trigger" data-url="/ajax/standards/form?resource=standards" data-size="modal-lg"><i class="ti ti-plus me-2"></i><?php echo e(standards_lang('new_standards')); ?></a>
          <a href="#" class="btn btn-outline-primary btn-modal-trigger" data-url="/ajax/standards/form?resource=requirements" data-size="modal-lg"><i class="ti ti-plus me-2"></i><?php echo e(standards_lang('new_requirements')); ?></a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="page-body">
  <div class="container-xl">
    <div class="card">
      <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
          <li class="nav-item"><a href="#standards-tab" class="nav-link active" data-bs-toggle="tab"><?php echo e(standards_lang('standards_tab')); ?></a></li>
          <li class="nav-item"><a href="#versions-tab" class="nav-link" data-bs-toggle="tab"><?php echo e(standards_lang('versions_tab')); ?></a></li>
          <li class="nav-item"><a href="#requirements-tab" class="nav-link" data-bs-toggle="tab"><?php echo e(standards_lang('requirements_tab')); ?></a></li>
          <li class="nav-item"><a href="#controls-tab" class="nav-link" data-bs-toggle="tab"><?php echo e(standards_lang('controls_tab')); ?></a></li>
        </ul>
        <?php if (check_permission('standards.create')): ?>
        <div class="card-actions">
          <a href="#" class="btn btn-outline-primary btn-sm btn-modal-trigger" data-url="/ajax/standards/form?resource=versions" data-size="modal-lg"><?php echo e(standards_lang('new_versions')); ?></a>
          <a href="#" class="btn btn-outline-primary btn-sm btn-modal-trigger" data-url="/ajax/standards/form?resource=clauses" data-size="modal-lg"><?php echo e(standards_lang('new_clauses')); ?></a>
          <a href="#" class="btn btn-outline-primary btn-sm btn-modal-trigger" data-url="/ajax/standards/form?resource=controls" data-size="modal-lg"><?php echo e(standards_lang('new_controls')); ?></a>
        </div>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <div class="tab-content">
          <div class="tab-pane active show" id="standards-tab">
            <table id="standards-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr>
              <th><?php echo e(standards_lang('company')); ?></th><th><?php echo e(standards_lang('standard_code')); ?></th><th><?php echo e(standards_lang('standard_name')); ?></th><th><?php echo e(standards_lang('status')); ?></th><th class="w-1"><i class="ti ti-settings"></i></th>
            </tr></thead></table>
          </div>
          <div class="tab-pane" id="versions-tab">
            <table id="standards-versions-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr>
              <th><?php echo e(standards_lang('company')); ?></th><th><?php echo e(standards_lang('standard')); ?></th><th><?php echo e(standards_lang('version_label')); ?></th><th><?php echo e(standards_lang('status')); ?></th><th class="w-1"><i class="ti ti-settings"></i></th>
            </tr></thead></table>
          </div>
          <div class="tab-pane" id="requirements-tab">
            <table id="standards-requirements-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr>
              <th><?php echo e(standards_lang('company')); ?></th><th><?php echo e(standards_lang('standard')); ?></th><th><?php echo e(standards_lang('clause_code')); ?></th><th><?php echo e(standards_lang('requirement_code')); ?></th><th><?php echo e(standards_lang('title')); ?></th><th><?php echo e(standards_lang('status')); ?></th><th class="w-1"><i class="ti ti-settings"></i></th>
            </tr></thead></table>
          </div>
          <div class="tab-pane" id="controls-tab">
            <table id="standards-controls-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr>
              <th><?php echo e(standards_lang('company')); ?></th><th><?php echo e(standards_lang('standard')); ?></th><th><?php echo e(standards_lang('requirement_code')); ?></th><th><?php echo e(standards_lang('control_code')); ?></th><th><?php echo e(standards_lang('title')); ?></th><th><?php echo e(standards_lang('status')); ?></th><th class="w-1"><i class="ti ti-settings"></i></th>
            </tr></thead></table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="application/json" id="standards-config"><?php echo json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
