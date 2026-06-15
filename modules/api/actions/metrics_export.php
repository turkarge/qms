<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/api/language.php';

$format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
$window = trim((string) ($_GET['window'] ?? '24h'));
$windowMap = [
    '1h' => '1 HOUR',
    '24h' => '24 HOUR',
    '7d' => '7 DAY',
];
if (!isset($windowMap[$window])) {
    $window = '24h';
}
$windowSql = $windowMap[$window];

if (!db_table_exists('api_request_logs')) {
    kirpi_export_response($format, 'api-metrics-' . date('Ymd-His'), [
        api_lang('status'),
        api_lang('error'),
    ], [[
        'missing',
        api_lang('table_missing'),
    ]]);
    exit;
}

$rows = [];

$summaryStmt = db()->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status_code BETWEEN 200 AND 299 THEN 1 ELSE 0 END) AS status_2xx,
        SUM(CASE WHEN status_code BETWEEN 400 AND 499 THEN 1 ELSE 0 END) AS status_4xx,
        SUM(CASE WHEN status_code BETWEEN 500 AND 599 THEN 1 ELSE 0 END) AS status_5xx,
        SUM(CASE WHEN status_code = 401 THEN 1 ELSE 0 END) AS status_401,
        SUM(CASE WHEN status_code = 403 THEN 1 ELSE 0 END) AS status_403,
        SUM(CASE WHEN status_code = 429 THEN 1 ELSE 0 END) AS status_429,
        COUNT(DISTINCT token_id) AS unique_tokens,
        COUNT(DISTINCT ip_address) AS unique_ips,
        ROUND(AVG(duration_ms), 0) AS avg_duration_ms
    FROM api_request_logs
    WHERE created_at >= (NOW() - INTERVAL {$windowSql})
");
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
foreach ($summary as $key => $value) {
    $rows[] = ['summary', $window, (string) $key, (string) ($value ?? 0), '', '', ''];
}

$topStmt = db()->query("
    SELECT route_path, request_method, COUNT(*) AS hit_count, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_count
    FROM api_request_logs
    WHERE created_at >= (NOW() - INTERVAL {$windowSql})
    GROUP BY route_path, request_method
    ORDER BY hit_count DESC
    LIMIT 50
");
while ($row = $topStmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        'top_endpoint',
        $window,
        (string) ($row['request_method'] ?? ''),
        (string) ($row['route_path'] ?? ''),
        (int) ($row['hit_count'] ?? 0),
        (int) ($row['error_count'] ?? 0),
        '',
    ];
}

$errorStmt = db()->query("
    SELECT created_at, route_path, request_method, status_code, error_code, ip_address
    FROM api_request_logs
    WHERE created_at >= (NOW() - INTERVAL {$windowSql})
      AND status_code >= 400
    ORDER BY id DESC
    LIMIT 200
");
while ($row = $errorStmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        'recent_error',
        $window,
        kirpi_format_datetime((string) ($row['created_at'] ?? '')),
        (string) ($row['request_method'] ?? ''),
        (string) ($row['route_path'] ?? ''),
        (int) ($row['status_code'] ?? 0),
        (string) ($row['error_code'] ?? '') . ' ' . (string) ($row['ip_address'] ?? ''),
    ];
}

kirpi_export_response($format, 'api-metrics-' . $window . '-' . date('Ymd-His'), [
    'Section',
    'Window',
    'Key',
    'Value',
    'Metric A',
    'Metric B',
    'Detail',
], $rows);
