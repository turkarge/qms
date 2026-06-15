<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/audit/language.php';

if (!db_table_exists('audit_logs')) {
    http_response_code(404);
    echo audit_lang('table_missing_short');
    exit;
}

$format = trim((string) ($_GET['format'] ?? 'csv'));
$window = (int) ($_GET['window'] ?? 7);
if (!in_array($window, [1, 7, 30], true)) {
    $window = 7;
}

$stmt = db()->prepare("
    SELECT
        module_key,
        COUNT(*) AS event_count,
        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS success_count,
        SUM(CASE WHEN status <> 'success' THEN 1 ELSE 0 END) AS failed_count,
        COUNT(DISTINCT user_id) AS user_count,
        MIN(created_at) AS first_event_at,
        MAX(created_at) AS last_event_at
    FROM audit_logs
    WHERE created_at >= (NOW() - INTERVAL :window DAY)
    GROUP BY module_key
    ORDER BY event_count DESC, module_key ASC
    LIMIT 5000
");
$stmt->bindValue(':window', $window, PDO::PARAM_INT);
$stmt->execute();

$rows = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        (string) ($row['module_key'] ?? ''),
        (int) ($row['event_count'] ?? 0),
        (int) ($row['success_count'] ?? 0),
        (int) ($row['failed_count'] ?? 0),
        (int) ($row['user_count'] ?? 0),
        kirpi_format_datetime((string) ($row['first_event_at'] ?? '')),
        kirpi_format_datetime((string) ($row['last_event_at'] ?? '')),
    ];
}

kirpi_export_response($format, 'audit-overview-' . $window . 'd-' . date('Ymd-His'), [
    audit_lang('module'),
    audit_lang('event_count'),
    audit_lang('success_count'),
    audit_lang('failed_count'),
    audit_lang('user_count'),
    audit_lang('first_event'),
    audit_lang('last_event'),
], $rows);
