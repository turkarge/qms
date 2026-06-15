<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;

require_once BASE_PATH . '/modules/notifications/language.php';
$ready = db_table_exists('notifications');
$hasMetadata = $ready && db_column_exists('notifications', 'source_module') && db_column_exists('notifications', 'template_key');
$sourceModules = [];
if ($hasMetadata) {
    try {
        $sourceModules = db()->query("SELECT DISTINCT source_module FROM notifications WHERE source_module IS NOT NULL AND source_module <> '' ORDER BY source_module")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $e) {
        error_log('notifications filter options error: ' . $e->getMessage());
    }
}
$tableConfig = [
    'endpoint' => base_url('ajax/notifications/datatable'),
    'exportEndpoint' => base_url('notifications/actions/export'),
    'markReadEndpoint' => 'notifications/actions/mark-read',
    'sourceModules' => array_values($sourceModules),
    'labels' => [
        'all' => notifications_lang('all_statuses'),
        'read' => notifications_lang('status_read'),
        'unread' => notifications_lang('status_unread'),
        'markRead' => notifications_lang('mark_read'),
    ],
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(notifications_lang('communication_center')); ?></div>
                <h2 class="page-title"><?php echo e(notifications_lang('notifications')); ?></h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php if (check_permission('notifications.settings')): ?>
                        <a href="<?php echo base_url('notifications/settings'); ?>" class="btn btn-outline-secondary"><i class="ti ti-settings"></i><?php echo e(notifications_lang('settings')); ?></a>
                    <?php endif; ?>
                    <?php if ($ready): ?>
                        <form id="notifications-mark-all-read-form" action="<?php echo base_url('notifications/actions/mark-all-read'); ?>" method="post" data-ajax="true" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-checks"></i><?php echo e(notifications_lang('mark_all_read')); ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!$ready): ?>
            <div class="alert alert-warning"><?php echo e(notifications_lang('tables_missing')); ?></div>
        <?php else: ?>
            <div class="card kirpi-table-card">
                <div class="card-body p-0">
                    <table id="notifications-data-table" class="table table-vcenter table-striped w-100 kirpi-data-table">
                        <thead><tr>
                            <th><?php echo e(notifications_lang('table_notification')); ?></th>
                            <th><?php echo e(notifications_lang('table_source')); ?></th>
                            <th><?php echo e(notifications_lang('table_channel')); ?></th>
                            <th><?php echo e(notifications_lang('table_status')); ?></th>
                            <th><?php echo e(notifications_lang('table_date')); ?></th>
                            <th class="w-1 text-center" title="İşlemler" aria-label="İşlemler"><i class="ti ti-settings"></i></th>
                        </tr></thead>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script type="application/json" id="notifications-table-config"><?php echo json_encode($tableConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
