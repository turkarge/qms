<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/queue/language.php';

$format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));

if (!kirpi_queue_table_ready()) {
    kirpi_export_response($format, 'queue-jobs-' . date('Ymd-His'), [
        queue_lang('status'),
        queue_lang('detail'),
    ], [[
        'missing',
        queue_lang('table_not_ready'),
    ]]);
    exit;
}

$rows = [];
$stmt = db()->query("
    SELECT id, queue_name, job_type, attempts, max_attempts, status, last_error, available_at, reserved_at, finished_at, created_at, updated_at
    FROM jobs_queue
    ORDER BY id DESC
    LIMIT 500
");

while ($job = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        (int) ($job['id'] ?? 0),
        (string) ($job['queue_name'] ?? ''),
        (string) ($job['job_type'] ?? ''),
        (int) ($job['attempts'] ?? 0),
        (int) ($job['max_attempts'] ?? 0),
        (string) ($job['status'] ?? ''),
        (string) ($job['last_error'] ?? ''),
        kirpi_format_datetime((string) ($job['available_at'] ?? '')),
        kirpi_format_datetime((string) ($job['reserved_at'] ?? '')),
        kirpi_format_datetime((string) ($job['finished_at'] ?? '')),
        kirpi_format_datetime((string) ($job['created_at'] ?? '')),
        kirpi_format_datetime((string) ($job['updated_at'] ?? '')),
    ];
}

kirpi_export_response($format, 'queue-jobs-' . date('Ymd-His'), [
    'ID',
    queue_lang('queue'),
    queue_lang('type'),
    queue_lang('attempts'),
    'Max',
    queue_lang('status'),
    queue_lang('error'),
    'Available At',
    'Reserved At',
    'Finished At',
    'Created At',
    'Updated At',
], $rows);
