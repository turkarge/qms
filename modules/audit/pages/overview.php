<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/audit/language.php';

$auditTableReady = db_table_exists('audit_logs');
$summary = [
    'total_24h' => 0,
    'total_7d' => 0,
    'failed_7d' => 0,
    'active_modules_7d' => 0,
];
$moduleRows = [];
$recentRows = [];

if ($auditTableReady) {
    try {
        $summary['total_24h'] = (int) db()->query("
            SELECT COUNT(*)
            FROM audit_logs
            WHERE created_at >= (NOW() - INTERVAL 24 HOUR)
        ")->fetchColumn();

        $summary['total_7d'] = (int) db()->query("
            SELECT COUNT(*)
            FROM audit_logs
            WHERE created_at >= (NOW() - INTERVAL 7 DAY)
        ")->fetchColumn();

        $summary['failed_7d'] = (int) db()->query("
            SELECT COUNT(*)
            FROM audit_logs
            WHERE created_at >= (NOW() - INTERVAL 7 DAY)
              AND status <> 'success'
        ")->fetchColumn();

        $summary['active_modules_7d'] = (int) db()->query("
            SELECT COUNT(DISTINCT module_key)
            FROM audit_logs
            WHERE created_at >= (NOW() - INTERVAL 7 DAY)
        ")->fetchColumn();

        $moduleRows = db()->query("
            SELECT
                module_key,
                COUNT(*) AS event_count,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS success_count,
                SUM(CASE WHEN status <> 'success' THEN 1 ELSE 0 END) AS failed_count,
                MAX(created_at) AS last_event_at
            FROM audit_logs
            WHERE created_at >= (NOW() - INTERVAL 7 DAY)
            GROUP BY module_key
            ORDER BY event_count DESC, module_key ASC
            LIMIT 12
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $recentRows = db()->query("
            SELECT
                a.id,
                a.user_id,
                a.module_key,
                a.action_key,
                a.status,
                a.entity_type,
                a.entity_id,
                a.route_path,
                a.request_method,
                a.ip_address,
                a.created_at,
                u.name AS user_name
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            ORDER BY a.id DESC
            LIMIT 100
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('audit overview error: ' . $e->getMessage());
        $moduleRows = [];
        $recentRows = [];
    }
}

$statusBadge = static function (string $status): string {
    return $status === 'success' ? 'bg-green-lt' : 'bg-red-lt';
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(audit_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(audit_lang('audit_overview')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(audit_lang('audit_overview_hint')); ?></div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-secondary js-kirpi-report-print">
                        <i class="ti ti-printer"></i>
                        <?php echo e(audit_lang('print_pdf')); ?>
                    </button>
                    <button type="button" class="btn btn-outline-secondary js-kirpi-report-email" data-subject="<?php echo e(audit_lang('audit_overview')); ?>">
                        <i class="ti ti-mail"></i>
                        <?php echo e(audit_lang('email')); ?>
                    </button>
                    <?php if ($auditTableReady): ?>
                        <a href="<?php echo base_url('audit/actions/export?format=csv'); ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-file-type-csv"></i>
                            <?php echo e(audit_lang('csv_export')); ?>
                        </a>
                        <a href="<?php echo base_url('audit/actions/export?format=xls'); ?>" class="btn btn-primary">
                            <i class="ti ti-file-spreadsheet"></i>
                            <?php echo e(audit_lang('excel_export')); ?>
                        </a>
                        <a href="<?php echo base_url('audit/actions/overview-export?window=7&format=xls'); ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-chart-bar"></i>
                            <?php echo e(audit_lang('overview_export')); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!$auditTableReady): ?>
            <div class="alert alert-warning">
                <?php echo e(audit_lang('table_missing')); ?>
            </div>
        <?php endif; ?>

        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body">
                    <div class="subheader"><?php echo e(audit_lang('events_24h')); ?></div>
                    <div class="h1 mb-0"><?php echo (int) $summary['total_24h']; ?></div>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body">
                    <div class="subheader"><?php echo e(audit_lang('events_7d')); ?></div>
                    <div class="h1 mb-0"><?php echo (int) $summary['total_7d']; ?></div>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body">
                    <div class="subheader"><?php echo e(audit_lang('failed_7d')); ?></div>
                    <div class="h1 mb-0 text-danger"><?php echo (int) $summary['failed_7d']; ?></div>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body">
                    <div class="subheader"><?php echo e(audit_lang('active_modules_7d')); ?></div>
                    <div class="h1 mb-0"><?php echo (int) $summary['active_modules_7d']; ?></div>
                </div></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(audit_lang('module_distribution')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="Audit Modül Dağılımı" class="table table-vcenter card-table table-striped">
                    <thead>
                    <tr>
                        <th><?php echo e(audit_lang('module')); ?></th>
                        <th><?php echo e(audit_lang('event_count')); ?></th>
                        <th><?php echo e(audit_lang('success_count')); ?></th>
                        <th><?php echo e(audit_lang('failed_count')); ?></th>
                        <th><?php echo e(audit_lang('last_event')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($moduleRows)): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4"><?php echo e(audit_lang('no_records')); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($moduleRows as $row): ?>
                            <tr>
                                <td><code><?php echo e((string) ($row['module_key'] ?? '')); ?></code></td>
                                <td><?php echo (int) ($row['event_count'] ?? 0); ?></td>
                                <td><?php echo (int) ($row['success_count'] ?? 0); ?></td>
                                <td><?php echo (int) ($row['failed_count'] ?? 0); ?></td>
                                <td><?php echo e(kirpi_format_datetime((string) ($row['last_event_at'] ?? ''))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card kirpi-report-card">
            <div class="card-header d-print-none">
                <div class="input-icon w-100">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="search" class="form-control js-kirpi-report-search" placeholder="<?php echo e(audit_lang('audit_search_placeholder')); ?>">
                </div>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="<?php echo e(audit_lang('audit_overview')); ?>" class="table table-vcenter card-table table-striped">
                    <thead>
                    <tr>
                        <th data-type="number">ID</th>
                        <th data-type="date"><?php echo e(audit_lang('date')); ?></th>
                        <th data-type="text"><?php echo e(audit_lang('user')); ?></th>
                        <th data-type="text"><?php echo e(audit_lang('module')); ?></th>
                        <th data-type="text"><?php echo e(audit_lang('action')); ?></th>
                        <th data-type="text"><?php echo e(audit_lang('status')); ?></th>
                        <th data-type="text"><?php echo e(audit_lang('entity')); ?></th>
                        <th data-type="text"><?php echo e(audit_lang('route')); ?></th>
                        <th data-type="text"><?php echo e(audit_lang('ip')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentRows)): ?>
                        <tr><td colspan="9" class="text-center text-secondary py-4"><?php echo e(audit_lang('no_records')); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentRows as $row): ?>
                            <?php
                            $userName = trim((string) ($row['user_name'] ?? ''));
                            $userId = (int) ($row['user_id'] ?? 0);
                            $status = (string) ($row['status'] ?? 'success');
                            $entityType = trim((string) ($row['entity_type'] ?? ''));
                            $entityId = (int) ($row['entity_id'] ?? 0);
                            ?>
                            <tr>
                                <td data-sort="<?php echo (int) ($row['id'] ?? 0); ?>"><?php echo (int) ($row['id'] ?? 0); ?></td>
                                <td data-sort="<?php echo e((string) ($row['created_at'] ?? '')); ?>"><?php echo e(kirpi_format_datetime((string) ($row['created_at'] ?? ''))); ?></td>
                                <td><?php echo e($userName !== '' ? ($userName . ' (#' . $userId . ')') : '-'); ?></td>
                                <td><code><?php echo e((string) ($row['module_key'] ?? '')); ?></code></td>
                                <td><code><?php echo e((string) ($row['action_key'] ?? '')); ?></code></td>
                                <td><span class="badge <?php echo e($statusBadge($status)); ?>"><?php echo e($status); ?></span></td>
                                <td>
                                    <?php echo e($entityType !== '' ? $entityType : '-'); ?>
                                    <?php if ($entityId > 0): ?>
                                        <span class="text-secondary">#<?php echo $entityId; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><code><?php echo e((string) ($row['route_path'] ?? '')); ?></code></div>
                                    <div class="small text-secondary"><?php echo e((string) ($row['request_method'] ?? '')); ?></div>
                                </td>
                                <td><code><?php echo e((string) ($row['ip_address'] ?? '')); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
