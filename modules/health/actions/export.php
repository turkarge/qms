<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/health/language.php';

$format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
$rows = [];

$add = static function (string $component, string $status, string $latency, string $detail) use (&$rows): void {
    $rows[] = [$component, $status, $latency, $detail];
};

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

$add('Application', APP_ENV === 'production' && APP_DEBUG === false ? 'OK' : 'WARN', '-', 'env=' . APP_ENV . ', debug=' . (APP_DEBUG ? 'true' : 'false') . ', ver=' . APP_VER);
$add('Database', $dbStatus, $dbLatency, $dbDetail);

if (db_table_exists('jobs_queue')) {
    $row = db()->query("SELECT SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) AS queued_count, SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing_count, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count FROM jobs_queue")->fetch(PDO::FETCH_ASSOC) ?: [];
    $failedCount = (int) ($row['failed_count'] ?? 0);
    $add('Queue', $failedCount > 0 ? 'WARN' : 'OK', '-', 'queued=' . (int) ($row['queued_count'] ?? 0) . ', processing=' . (int) ($row['processing_count'] ?? 0) . ', failed=' . $failedCount);
} else {
    $add('Queue', 'WARN', '-', health_lang('queue_table_missing'));
}

$mailDetail = trim((string) MAIL_HOST) !== '' ? health_lang('mail_host_defined_prefix') . MAIL_HOST : health_lang('mail_host_empty');
$add('Mail', trim((string) MAIL_HOST) !== '' ? 'OK' : 'WARN', '-', $mailDetail);

if (db_table_exists('db_backups')) {
    $row = db()->query("SELECT COUNT(*) AS backup_count, COALESCE(SUM(file_size), 0) AS total_size FROM db_backups")->fetch(PDO::FETCH_ASSOC) ?: [];
    $count = (int) ($row['backup_count'] ?? 0);
    $add('Backup', $count > 0 ? 'OK' : 'WARN', '-', 'count=' . $count . ', size=' . number_format(((int) ($row['total_size'] ?? 0)) / 1024 / 1024, 2) . ' MB');
} else {
    $add('Backup', 'WARN', '-', health_lang('backup_table_missing'));
}

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
$add('Disk', $diskStatus, '-', $diskDetail);
$add('Session', ini_get('session.cookie_secure') === '1' ? 'OK' : 'WARN', '-', 'secure=' . (ini_get('session.cookie_secure') === '1' ? '1' : '0') . ', samesite=' . (string) ini_get('session.cookie_samesite'));

if (kirpi_throttle_enabled() && kirpi_throttle_table_ready()) {
    $row = db()->query("SELECT COUNT(*) AS total_rows, SUM(CASE WHEN blocked_until IS NOT NULL AND blocked_until > NOW() THEN 1 ELSE 0 END) AS active_blocks FROM request_throttles")->fetch(PDO::FETCH_ASSOC) ?: [];
    $add('Throttle', 'OK', '-', 'rows=' . (int) ($row['total_rows'] ?? 0) . ', active_blocks=' . (int) ($row['active_blocks'] ?? 0));
} else {
    $add('Throttle', 'WARN', '-', health_lang('throttle_disabled_or_missing'));
}

kirpi_export_response($format, 'health-metrics-' . date('Ymd-His'), [
    health_lang('component'),
    health_lang('status'),
    health_lang('latency'),
    health_lang('detail'),
], $rows);
