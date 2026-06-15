<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/mail/language.php';

if (!kirpi_mail_templates_table_ready()) {
    http_response_code(404);
    echo mail_lang('template_tables_missing');
    exit;
}

$format = trim((string) ($_GET['format'] ?? 'csv'));
$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(template_key LIKE :search OR name LIKE :search OR subject LIKE :search OR html_body LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($status !== '' && in_array($status, ['0', '1'], true)) {
    $where[] = 'is_active = :is_active';
    $params[':is_active'] = (int) $status;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = db()->prepare("
    SELECT
        id,
        template_key,
        name,
        subject,
        html_body,
        is_active,
        is_system,
        created_at,
        updated_at
    FROM mail_templates
    {$whereSql}
    ORDER BY is_system DESC, template_key ASC
    LIMIT 5000
");
$stmt->execute($params);

$rows = [];
while ($template = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        (int) ($template['id'] ?? 0),
        (string) ($template['template_key'] ?? ''),
        (string) ($template['name'] ?? ''),
        (string) ($template['subject'] ?? ''),
        (string) ($template['html_body'] ?? ''),
        (int) ($template['is_system'] ?? 0) === 1 ? mail_lang('is_system') : mail_lang('custom'),
        (int) ($template['is_active'] ?? 0) === 1 ? mail_lang('is_active') : mail_lang('inactive'),
        kirpi_format_datetime((string) ($template['created_at'] ?? '')),
        kirpi_format_datetime((string) ($template['updated_at'] ?? '')),
    ];
}

kirpi_export_response($format, 'mail-templates-' . date('Ymd-His'), [
    'ID',
    mail_lang('template_key'),
    mail_lang('template_name'),
    mail_lang('subject'),
    mail_lang('html_body'),
    mail_lang('template_origin'),
    mail_lang('status'),
    mail_lang('created_at'),
    mail_lang('updated_at'),
], $rows);
