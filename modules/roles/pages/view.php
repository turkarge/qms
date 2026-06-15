<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/roles/language.php';

$tableConfig = [
    'endpoint' => base_url('ajax/roles/datatable'),
    'exportEndpoint' => base_url('roles/actions/export'),
    'permissions' => [
        'edit' => check_permission('roles.edit'),
        'status' => check_permission('roles.status'),
        'permissions' => check_permission('roles.permissions'),
    ],
    'labels' => [
        'active' => roles_lang('active'),
        'inactive' => roles_lang('inactive'),
        'edit' => roles_lang('edit'),
        'permissions' => roles_lang('permissions'),
    ],
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(roles_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(roles_lang('roles')); ?></h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php if (check_permission('roles.permissions')): ?>
                        <a href="<?php echo base_url('roles/permissions'); ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-shield-check"></i>
                            <?php echo e(roles_lang('permissions')); ?>
                        </a>
                    <?php endif; ?>
                    <a href="#" class="btn btn-primary btn-modal-trigger" data-url="/ajax/roles/create" data-size="modal-md">
                        <i class="ti ti-plus"></i>
                        <?php echo e(roles_lang('new_role')); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card kirpi-table-card">
            <div class="card-body p-0">
                <table id="roles-data-table" class="table table-vcenter table-striped w-100 kirpi-data-table">
                    <thead>
                        <tr>
                            <th><?php echo e(roles_lang('table_role')); ?></th>
                            <th><?php echo e(roles_lang('table_status')); ?></th>
                            <th><?php echo e(roles_lang('table_user_count')); ?></th>
                            <th><?php echo e(roles_lang('table_permission_count')); ?></th>
                            <th class="w-1 text-center" title="İşlemler" aria-label="İşlemler"><i class="ti ti-settings"></i></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="roles-table-config"><?php echo json_encode($tableConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
