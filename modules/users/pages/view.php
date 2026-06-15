<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/users/language.php';

$tableConfig = [
    'endpoint' => base_url('ajax/users/datatable'),
    'exportEndpoint' => base_url('users/actions/export'),
    'permissions' => [
        'edit' => check_permission('users.edit'),
        'status' => check_permission('users.status'),
        'dropSession' => check_permission('users.session.drop'),
        'resetLock' => check_permission('users.lock.reset'),
    ],
    'labels' => [
        'active' => users_lang('active'),
        'inactive' => users_lang('inactive'),
        'edit' => users_lang('edit'),
        'session' => users_lang('session'),
        'key' => users_lang('key'),
        'dropSessionConfirm' => users_lang('drop_session_confirm'),
        'resetKeyConfirm' => users_lang('reset_key_list_confirm'),
    ],
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(users_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(users_lang('users')); ?></h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="#" class="btn btn-primary btn-modal-trigger" data-url="/ajax/users/create" data-size="modal-lg">
                    <i class="ti ti-plus"></i>
                    <?php echo e(users_lang('new_user')); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card kirpi-table-card">
            <div class="card-body border-bottom py-2 kirpi-table-selection-shell" id="users-selection-shell" hidden>
                <div class="kirpi-table-selection" id="users-selection-bar">
                    <strong><span id="users-selection-count">0</span> kayıt seçildi</strong>
                    <button type="button" class="btn btn-sm btn-ghost-secondary" id="users-selection-clear">Seçimi temizle</button>
                </div>
            </div>

            <div class="card-body p-0">
                <table id="users-data-table" class="table table-vcenter table-striped w-100 kirpi-data-table">
                    <thead>
                        <tr>
                            <th class="w-1"></th>
                            <th><?php echo e(users_lang('name_surname')); ?></th>
                            <th><?php echo e(users_lang('email')); ?></th>
                            <th><?php echo e(users_lang('table_role')); ?></th>
                            <th><?php echo e(users_lang('table_status')); ?></th>
                            <th><?php echo e(users_lang('table_created_at')); ?></th>
                            <th><?php echo e(users_lang('updated_at')); ?></th>
                            <th class="w-1 text-center" title="İşlemler" aria-label="İşlemler"><i class="ti ti-settings"></i></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="users-table-config"><?php echo json_encode($tableConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
