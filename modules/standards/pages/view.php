<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/standards/language.php';
$config = [
    'endpoint' => base_url('ajax/standards/datatable'),
    'labels' => [
        'active' => standards_lang('active'),
        'draft' => standards_lang('draft'),
        'published' => standards_lang('published'),
        'archived' => standards_lang('archived'),
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
      </div>
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
      </div>
      <div class="card-body p-0">
        <div class="tab-content">
          <div class="tab-pane active show" id="standards-tab">
            <table id="standards-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr>
              <th><?php echo e(standards_lang('company')); ?></th><th><?php echo e(standards_lang('standard_code')); ?></th><th><?php echo e(standards_lang('standard_name')); ?></th><th><?php echo e(standards_lang('status')); ?></th>
            </tr></thead></table>
          </div>
          <div class="tab-pane" id="versions-tab">
            <table id="standards-versions-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr>
              <th><?php echo e(standards_lang('company')); ?></th><th><?php echo e(standards_lang('standard')); ?></th><th><?php echo e(standards_lang('version_label')); ?></th><th><?php echo e(standards_lang('status')); ?></th>
            </tr></thead></table>
          </div>
          <div class="tab-pane" id="requirements-tab">
            <table id="standards-requirements-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr>
              <th><?php echo e(standards_lang('company')); ?></th><th><?php echo e(standards_lang('standard')); ?></th><th><?php echo e(standards_lang('clause_code')); ?></th><th><?php echo e(standards_lang('requirement_code')); ?></th><th><?php echo e(standards_lang('title')); ?></th><th><?php echo e(standards_lang('status')); ?></th>
            </tr></thead></table>
          </div>
          <div class="tab-pane" id="controls-tab">
            <table id="standards-controls-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr>
              <th><?php echo e(standards_lang('company')); ?></th><th><?php echo e(standards_lang('standard')); ?></th><th><?php echo e(standards_lang('requirement_code')); ?></th><th><?php echo e(standards_lang('control_code')); ?></th><th><?php echo e(standards_lang('title')); ?></th><th><?php echo e(standards_lang('status')); ?></th>
            </tr></thead></table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="application/json" id="standards-config"><?php echo json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
