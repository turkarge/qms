<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

$format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
$limit = max(1, min(500, (int) ($_GET['limit'] ?? 500)));
$report = kirpi_ai_schema_quality_report($limit);

if (($report['status'] ?? '') !== 'success') {
    http_response_code(422);
    echo (string) ($report['message'] ?? ai_lang('schema_quality_error'));
    exit;
}

$warnings = (array) ($report['warnings'] ?? []);
$meta = (array) ($report['meta'] ?? []);
$filename = 'ai-schema-quality-' . date('Ymd-His');

if ($format === 'json') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . kirpi_export_filename($filename, 'json') . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    echo json_encode([
        'status' => 'success',
        'generated_at' => date(DATE_ATOM),
        'meta' => $meta,
        'warnings' => $warnings,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$rows = [];
foreach ($warnings as $warning) {
    $rows[] = [
        (string) ($warning['severity'] ?? ''),
        (string) ($warning['code'] ?? ''),
        (string) ($warning['module'] ?? ''),
        (string) ($warning['entity'] ?? ''),
        (string) ($warning['table'] ?? ''),
        (string) ($warning['field'] ?? ''),
        (string) ($warning['message'] ?? ''),
    ];
}

kirpi_export_response($format, $filename, [
    ai_lang('severity'),
    ai_lang('quality_code'),
    ai_lang('module'),
    ai_lang('entity'),
    ai_lang('table'),
    ai_lang('field_name'),
    ai_lang('quality_message'),
], $rows);
