<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;

require_once BASE_PATH . '/modules/audit/language.php';
$ready = db_table_exists('audit_logs');
$tableConfig = [
    'endpoint' => base_url('ajax/audit/datatable'),
    'exportEndpoint' => base_url('audit/actions/export'),
    'labels' => ['all' => audit_lang('all')],
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(audit_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(audit_lang('audit_log')); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!$ready): ?>
            <div class="alert alert-warning"><?php echo e(audit_lang('table_missing')); ?></div>
        <?php else: ?>
            <div class="card kirpi-table-card">
                <div class="card-body p-0">
                    <table id="audit-data-table" class="table table-vcenter table-striped w-100 kirpi-data-table">
                        <thead><tr>
                            <th>ID</th>
                            <th><?php echo e(audit_lang('date')); ?></th>
                            <th><?php echo e(audit_lang('user')); ?></th>
                            <th><?php echo e(audit_lang('module')); ?></th>
                            <th><?php echo e(audit_lang('action')); ?></th>
                            <th><?php echo e(audit_lang('status')); ?></th>
                            <th><?php echo e(audit_lang('route')); ?></th>
                            <th><?php echo e(audit_lang('ip')); ?></th>
                            <th><?php echo e(audit_lang('detail')); ?></th>
                        </tr></thead>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script type="application/json" id="audit-table-config"><?php echo json_encode($tableConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
