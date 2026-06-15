<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/template/language.php';

if (!kirpi_templates_table_ready()) {
    http_response_code(404);
    echo template_lang('table_missing');
    exit;
}

$kind = trim((string) ($_GET['kind'] ?? 'email'));
if (!in_array($kind, kirpi_template_kinds(), true)) {
    $kind = 'email';
}

$search = trim((string) ($_GET['search'] ?? ''));
$moduleKey = trim((string) ($_GET['module_key'] ?? ''));
$code = trim((string) ($_GET['code'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$format = trim((string) ($_GET['format'] ?? 'csv'));

$where = ['kind = :kind'];
$params = [
    ':kind' => $kind,
];

if ($search !== '') {
    $where[] = '(name LIKE :search OR subject LIKE :search OR body LIKE :search OR target_key LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($moduleKey !== '') {
    $where[] = 'module_key = :module_key';
    $params[':module_key'] = $moduleKey;
}

if ($code !== '') {
    $where[] = 'code LIKE :code';
    $params[':code'] = '%' . $code . '%';
}

if ($status !== '' && in_array($status, ['0', '1'], true)) {
    $where[] = 'is_active = :is_active';
    $params[':is_active'] = (int) $status;
}

$sql = "
    SELECT
        id,
        kind,
        module_key,
        target_key,
        code,
        name,
        language,
        subject,
        body,
        variables_json,
        is_system,
        is_active,
        created_at,
        updated_at
    FROM templates
    WHERE " . implode(' AND ', $where) . "
    ORDER BY is_system DESC, module_key ASC, target_key ASC, code ASC
    LIMIT 5000
";

$stmt = db()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$rows = [];
while ($template = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        (int) ($template['id'] ?? 0),
        template_lang('kind_' . (string) ($template['kind'] ?? $kind), (string) ($template['kind'] ?? $kind)),
        (string) ($template['module_key'] ?? ''),
        (string) ($template['target_key'] ?? ''),
        (string) ($template['code'] ?? ''),
        (string) ($template['name'] ?? ''),
        (string) ($template['language'] ?? ''),
        (string) ($template['subject'] ?? ''),
        (string) ($template['body'] ?? ''),
        (string) ($template['variables_json'] ?? ''),
        (int) ($template['is_system'] ?? 0) === 1 ? template_lang('system') : template_lang('custom'),
        (int) ($template['is_active'] ?? 0) === 1 ? template_lang('active') : template_lang('inactive'),
        kirpi_format_datetime((string) ($template['created_at'] ?? '')),
        kirpi_format_datetime((string) ($template['updated_at'] ?? '')),
    ];
}

kirpi_export_response($format, 'templates-' . $kind . '-' . date('Ymd-His'), [
    'ID',
    template_lang('kind'),
    template_lang('module'),
    template_lang('target'),
    template_lang('code'),
    template_lang('name'),
    template_lang('language'),
    template_lang('subject'),
    template_lang('body'),
    template_lang('variables'),
    template_lang('template_origin'),
    template_lang('status'),
    template_lang('created_at'),
    template_lang('updated_at'),
], $rows);
