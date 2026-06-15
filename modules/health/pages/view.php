<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/health/language.php';

$metrics = [];
$now = kirpi_format_datetime(new DateTimeImmutable('now'));

$dbStatus = 'OK';
$dbDetail = health_lang('db_connection_ok');
$dbLatency = '-';

try {
    $start = microtime(true);
    db()->query('SELECT 1');
    $dbLatency = number_format((microtime(true) - $start) * 1000, 2) . ' ms';
} catch (Throwable $e) {
    $dbStatus = 'FAIL';
    $dbDetail = health_lang('db_query_failed');
}

$metrics[] = [
    'name' => 'Application',
    'status' => APP_ENV === 'production' && APP_DEBUG === false ? 'OK' : 'WARN',
    'latency' => '-',
    'detail' => 'env=' . APP_ENV . ', debug=' . (APP_DEBUG ? 'true' : 'false') . ', ver=' . APP_VER,
];

$metrics[] = [
    'name' => 'Database',
    'status' => $dbStatus,
    'latency' => $dbLatency,
    'detail' => $dbDetail,
];

$queueDetail = health_lang('queue_table_missing');
$queueStatus = 'WARN';
$queueLatency = '-';

if (db_table_exists('jobs_queue')) {
    try {
        $start = microtime(true);
        $stmt = db()->query("\n            SELECT\n                SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) AS queued_count,\n                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing_count,\n                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count\n            FROM jobs_queue\n        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $queueLatency = number_format((microtime(true) - $start) * 1000, 2) . ' ms';

        $queuedCount = (int) ($row['queued_count'] ?? 0);
        $processingCount = (int) ($row['processing_count'] ?? 0);
        $failedCount = (int) ($row['failed_count'] ?? 0);
        $queueDetail = 'queued=' . $queuedCount . ', processing=' . $processingCount . ', failed=' . $failedCount;
        $queueStatus = $failedCount > 0 ? 'WARN' : 'OK';
    } catch (Throwable $e) {
        $queueStatus = 'FAIL';
        $queueDetail = health_lang('queue_metrics_unreadable');
    }
}

$metrics[] = [
    'name' => 'Queue',
    'status' => $queueStatus,
    'latency' => $queueLatency,
    'detail' => $queueDetail,
];

$mailStatus = 'WARN';
$mailDetail = health_lang('mail_host_empty');

if (trim((string) MAIL_HOST) !== '') {
    $mailStatus = 'OK';
    $mailDetail = health_lang('mail_host_defined_prefix') . MAIL_HOST;
}

if (db_table_exists('mail_logs')) {
    try {
        $stmt = db()->query("\n            SELECT\n                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent_count,\n                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count\n            FROM mail_logs\n            WHERE created_at >= (NOW() - INTERVAL 24 HOUR)\n        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $sentCount = (int) ($row['sent_count'] ?? 0);
        $failedCount = (int) ($row['failed_count'] ?? 0);

        $mailDetail .= ', 24h sent=' . $sentCount . ', failed=' . $failedCount;
        if ($failedCount > 0 && $mailStatus === 'OK') {
            $mailStatus = 'WARN';
        }
    } catch (Throwable $e) {
        if ($mailStatus === 'OK') {
            $mailStatus = 'WARN';
        }
    }
}

$metrics[] = [
    'name' => 'Mail',
    'status' => $mailStatus,
    'latency' => '-',
    'detail' => $mailDetail,
];

$backupStatus = 'WARN';
$backupDetail = health_lang('backup_table_missing');

if (db_table_exists('db_backups')) {
    try {
        $stmt = db()->query("\n            SELECT COUNT(*) AS backup_count, COALESCE(SUM(file_size), 0) AS total_size\n            FROM db_backups\n        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $count = (int) ($row['backup_count'] ?? 0);
        $size = (int) ($row['total_size'] ?? 0);

        $latestStmt = db()->query("\n            SELECT created_at\n            FROM db_backups\n            ORDER BY id DESC\n            LIMIT 1\n        ");
        $latest = $latestStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $latestAt = (string) ($latest['created_at'] ?? '-');

        $backupDetail = 'count=' . $count . ', size=' . number_format($size / 1024 / 1024, 2) . ' MB, latest=' . $latestAt;
        $backupStatus = $count > 0 ? 'OK' : 'WARN';
    } catch (Throwable $e) {
        $backupStatus = 'FAIL';
        $backupDetail = health_lang('backup_metrics_unreadable');
    }
}

$metrics[] = [
    'name' => 'Backup',
    'status' => $backupStatus,
    'latency' => '-',
    'detail' => $backupDetail,
];

$storagePath = BASE_PATH . '/storage';
$diskStatus = 'WARN';
$diskDetail = health_lang('disk_unreadable');

if (is_dir($storagePath)) {
    $total = @disk_total_space($storagePath);
    $free = @disk_free_space($storagePath);

    if (is_numeric($total) && is_numeric($free) && $total > 0) {
        $usedPercent = (int) round((($total - $free) / $total) * 100);
        $diskStatus = $usedPercent >= 90 ? 'FAIL' : ($usedPercent >= 80 ? 'WARN' : 'OK');
        $diskDetail = 'used=' . $usedPercent . '%';
    }
}

$metrics[] = [
    'name' => 'Disk',
    'status' => $diskStatus,
    'latency' => '-',
    'detail' => $diskDetail,
];

$sessionStatus = 'OK';
$sessionDetail = 'secure=' . (ini_get('session.cookie_secure') === '1' ? '1' : '0') . ', samesite=' . (string) ini_get('session.cookie_samesite');
if (ini_get('session.cookie_secure') !== '1') {
    $sessionStatus = 'WARN';
}

$metrics[] = [
    'name' => 'Session',
    'status' => $sessionStatus,
    'latency' => '-',
    'detail' => $sessionDetail,
];

$throttleStatus = 'WARN';
$throttleDetail = health_lang('throttle_disabled_or_missing');

if (kirpi_throttle_enabled() && kirpi_throttle_table_ready()) {
    try {
        $stmt = db()->query("\n            SELECT COUNT(*) AS total_rows,\n                   SUM(CASE WHEN blocked_until IS NOT NULL AND blocked_until > NOW() THEN 1 ELSE 0 END) AS active_blocks\n            FROM request_throttles\n        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $totalRows = (int) ($row['total_rows'] ?? 0);
        $activeBlocks = (int) ($row['active_blocks'] ?? 0);
        $throttleStatus = 'OK';
        $throttleDetail = 'rows=' . $totalRows . ', active_blocks=' . $activeBlocks;
    } catch (Throwable $e) {
        $throttleStatus = 'FAIL';
        $throttleDetail = health_lang('throttle_metrics_unreadable');
    }
}

$metrics[] = [
    'name' => 'Throttle',
    'status' => $throttleStatus,
    'latency' => '-',
    'detail' => $throttleDetail,
];

$statusBadge = static function (string $status): string {
    if ($status === 'OK') {
        return 'bg-green-lt';
    }

    if ($status === 'WARN') {
        return 'bg-yellow-lt';
    }

    return 'bg-red-lt';
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(health_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(health_lang('health_metrics')); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(health_lang('system_matrix')); ?></h3>
                <div class="card-actions">
                    <span class="text-secondary"><?php echo e(health_lang('last_check')); ?>: <?php echo e($now); ?></span>
                </div>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="Sistem Sağlığı" class="table table-vcenter card-table table-striped">
                    <thead>
                    <tr>
                        <th><?php echo e(health_lang('component')); ?></th>
                        <th><?php echo e(health_lang('status')); ?></th>
                        <th><?php echo e(health_lang('latency')); ?></th>
                        <th><?php echo e(health_lang('detail')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($metrics as $metric): ?>
                        <tr>
                            <td><?php echo e((string) ($metric['name'] ?? '')); ?></td>
                            <td>
                                <span class="badge <?php echo e($statusBadge((string) ($metric['status'] ?? 'WARN'))); ?>">
                                    <?php echo e((string) ($metric['status'] ?? 'WARN')); ?>
                                </span>
                            </td>
                            <td><code><?php echo e((string) ($metric['latency'] ?? '-')); ?></code></td>
                            <td class="text-secondary"><?php echo e((string) ($metric['detail'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
