<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/organization/language.php';

$resources = [
    'companies' => ['label' => organization_lang('companies'), 'new' => organization_lang('new_company'), 'icon' => 'ti-building'],
    'units' => ['label' => organization_lang('units'), 'new' => organization_lang('new_unit'), 'icon' => 'ti-sitemap'],
    'positions' => ['label' => organization_lang('positions'), 'new' => organization_lang('new_position'), 'icon' => 'ti-id-badge-2'],
    'assignments' => ['label' => organization_lang('assignments'), 'new' => organization_lang('new_assignment'), 'icon' => 'ti-user-check'],
];
$config = [
    'endpoint' => base_url('ajax/organization/datatable'),
    'exportEndpoint' => base_url('organization/actions/export'),
    'permissions' => [
        'create' => check_permission('organization.create'),
        'edit' => check_permission('organization.edit'),
        'assign' => check_permission('organization.assign'),
        'status' => check_permission('organization.status'),
        'export' => check_permission('organization.export'),
    ],
    'labels' => [
        'edit' => organization_lang('edit'), 'active' => organization_lang('active'), 'inactive' => organization_lang('inactive'),
        'archived' => organization_lang('archived'), 'expired' => organization_lang('expired'), 'revoked' => organization_lang('revoked'),
        'facility' => organization_lang('facility'), 'location' => organization_lang('location'), 'department' => organization_lang('department'), 'team' => organization_lang('team'),
    ],
];
?>
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col"><div class="page-pretitle">Kirpi QMS+</div><h2 class="page-title"><?php echo e(organization_lang('organization')); ?></h2><div class="text-secondary mt-1"><?php echo e(organization_lang('organization_hint')); ?></div></div>
      <div class="col-auto ms-auto"><div class="btn-list"><button type="button" class="btn btn-outline-secondary btn-modal-trigger" data-url="/ajax/organization/tree" data-size="modal-lg"><i class="ti ti-hierarchy-3"></i><?php echo e(organization_lang('hierarchy')); ?></button><button type="button" class="btn btn-primary btn-modal-trigger" id="organization-new-button" data-url="/ajax/organization/form?resource=companies" data-size="modal-lg" <?php echo check_permission('organization.create') ? '' : 'hidden'; ?>><i class="ti ti-plus"></i><span><?php echo e(organization_lang('new_company')); ?></span></button></div></div>
    </div>
  </div>
</div>
<div class="page-body"><div class="container-xl">
  <div class="card">
    <div class="card-header"><ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
      <?php $first = true; foreach ($resources as $key => $item): ?>
      <li class="nav-item" role="presentation"><button class="nav-link <?php echo $first ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#organization-<?php echo e($key); ?>" data-resource="<?php echo e($key); ?>" type="button"><i class="ti <?php echo e($item['icon']); ?> me-2"></i><?php echo e($item['label']); ?></button></li>
      <?php $first = false; endforeach; ?>
    </ul></div>
    <div class="card-body p-0"><div class="tab-content">
      <?php $first = true; foreach ($resources as $key => $item): ?>
      <div class="tab-pane <?php echo $first ? 'active show' : ''; ?>" id="organization-<?php echo e($key); ?>" data-resource-pane="<?php echo e($key); ?>">
        <table id="organization-<?php echo e($key); ?>-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr data-organization-head="<?php echo e($key); ?>"></tr></thead></table>
      </div>
      <?php $first = false; endforeach; ?>
    </div></div>
  </div>
</div></div>
<script type="application/json" id="organization-config"><?php echo json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/json" id="organization-resources"><?php echo json_encode($resources, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
