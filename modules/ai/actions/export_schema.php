<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

if (!kirpi_ai_schema_registry_ready()) {
    http_response_code(404);
    echo ai_lang('schema_missing');
    exit;
}

$format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
$limit = max(1, min(5000, (int) ($_GET['limit'] ?? 5000)));
$canManageSchema = check_permission('ai.schema.manage');

$discovery = kirpi_ai_discover_schema([
    'include_sensitive' => $canManageSchema && (string) ($_GET['include_sensitive'] ?? '') === '1',
    'filterable_only' => (string) ($_GET['filterable_only'] ?? '') === '1',
    'search' => trim((string) ($_GET['discovery_q'] ?? $_GET['search'] ?? '')),
    'module' => trim((string) ($_GET['module'] ?? '')),
    'entity' => trim((string) ($_GET['entity'] ?? '')),
    'table' => trim((string) ($_GET['table'] ?? '')),
    'permission' => trim((string) ($_GET['permission'] ?? '')),
    'limit' => $limit,
]);

if (($discovery['status'] ?? '') !== 'success') {
    http_response_code(422);
    echo (string) ($discovery['message'] ?? ai_lang('schema_sync_error'));
    exit;
}

$entities = (array) ($discovery['entities'] ?? []);
$meta = (array) ($discovery['meta'] ?? []);
$filename = 'ai-schema-' . date('Ymd-His');

if ($format === 'json') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . kirpi_export_filename($filename, 'json') . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    echo json_encode([
        'status' => 'success',
        'generated_at' => date(DATE_ATOM),
        'meta' => $meta,
        'entities' => $entities,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$rows = [];
foreach ($entities as $entity) {
    $fields = (array) ($entity['fields'] ?? []);
    if (empty($fields)) {
        $rows[] = [
            (string) ($entity['module'] ?? ''),
            (string) ($entity['entity'] ?? ''),
            (string) ($entity['table'] ?? ''),
            (string) ($entity['permission'] ?? ''),
            (string) ($entity['description'] ?? ''),
            '',
            '',
            '',
            '',
            '',
            json_encode((array) ($entity['metadata'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
            '',
        ];
        continue;
    }

    foreach ($fields as $field) {
        $rows[] = [
            (string) ($entity['module'] ?? ''),
            (string) ($entity['entity'] ?? ''),
            (string) ($entity['table'] ?? ''),
            (string) ($entity['permission'] ?? ''),
            (string) ($entity['description'] ?? ''),
            (string) ($field['name'] ?? ''),
            (string) ($field['type'] ?? ''),
            (string) ($field['description'] ?? ''),
            !empty($field['is_sensitive']) ? '1' : '0',
            !empty($field['is_filterable']) ? '1' : '0',
            json_encode((array) ($entity['metadata'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
            json_encode((array) ($field['metadata'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
        ];
    }
}

kirpi_export_response($format, $filename, [
    ai_lang('module'),
    ai_lang('entity'),
    ai_lang('table'),
    ai_lang('permission'),
    ai_lang('entity_description'),
    ai_lang('field_name'),
    ai_lang('field_type'),
    ai_lang('field_description'),
    ai_lang('is_sensitive'),
    ai_lang('is_filterable'),
    ai_lang('entity_metadata'),
    ai_lang('field_metadata'),
], $rows);
