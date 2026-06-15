<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/governance/language.php';
$resources = [
    'ownerships' => ['label' => governance_lang('ownerships'), 'new' => governance_lang('new_ownership'), 'icon' => 'ti-user-star'],
    'delegations' => ['label' => governance_lang('delegations'), 'new' => governance_lang('new_delegation'), 'icon' => 'ti-user-share'],
];
$config = [
    'endpoint' => base_url('ajax/governance/datatable'),
    'permissions' => [
        'ownerships' => check_permission('governance.ownership.manage'),
        'delegations' => check_permission('governance.delegation.manage'),
    ],
    'labels' => [
        'edit' => governance_lang('edit'), 'revoke' => governance_lang('revoke'), 'active' => governance_lang('active'),
        'inactive' => governance_lang('inactive'), 'expired' => governance_lang('expired'), 'revoked' => governance_lang('revoked'),
    ],
];
?>
<div class="page-header d-print-none"><div class="container-xl"><div class="row g-2 align-items-center">
  <div class="col"><div class="page-pretitle">Kirpi QMS+</div><h2 class="page-title"><?php echo e(governance_lang('governance')); ?></h2><div class="text-secondary mt-1"><?php echo e(governance_lang('governance_hint')); ?></div></div>
  <div class="col-auto ms-auto"><button type="button" class="btn btn-primary btn-modal-trigger" id="governance-new-button" data-url="/ajax/governance/form?resource=ownerships" data-size="modal-lg" <?php echo check_permission('governance.ownership.manage') ? '' : 'hidden'; ?>><i class="ti ti-plus"></i><span><?php echo e(governance_lang('new_ownership')); ?></span></button></div>
</div></div></div>
<div class="page-body"><div class="container-xl"><div class="card">
  <div class="card-header"><ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
  <?php $first=true; foreach($resources as $key=>$item): ?><li class="nav-item"><button class="nav-link <?php echo $first?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#governance-<?php echo e($key); ?>" data-resource="<?php echo e($key); ?>" type="button"><i class="ti <?php echo e($item['icon']); ?> me-2"></i><?php echo e($item['label']); ?></button></li><?php $first=false; endforeach; ?>
  </ul></div>
  <div class="card-body p-0"><div class="tab-content"><?php $first=true; foreach($resources as $key=>$item): ?><div class="tab-pane <?php echo $first?'active show':''; ?>" id="governance-<?php echo e($key); ?>"><table id="governance-<?php echo e($key); ?>-table" class="table table-vcenter table-striped w-100 kirpi-data-table"><thead><tr data-governance-head="<?php echo e($key); ?>"></tr></thead></table></div><?php $first=false; endforeach; ?></div></div>
</div></div></div>
<script type="application/json" id="governance-config"><?php echo json_encode($config, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/json" id="governance-resources"><?php echo json_encode($resources, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script>
