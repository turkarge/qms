<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/notifications/language.php';

if (!db_table_exists('notifications')) {
    http_response_code(404);
    echo notifications_lang('table_not_ready');
    exit;
}

$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);

if ($userId <= 0) {
    http_response_code(403);
    echo notifications_lang('invalid_session');
    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$sourceModule = trim((string) ($_GET['source_module'] ?? ''));
$templateKey = trim((string) ($_GET['template_key'] ?? ''));
$format = trim((string) ($_GET['format'] ?? 'csv'));
$hasMetadataColumns = db_column_exists('notifications', 'source_module')
    && db_column_exists('notifications', 'template_key')
    && db_column_exists('notifications', 'entity_type')
    && db_column_exists('notifications', 'entity_id');

$where = ['n.user_id = :user_id'];
$params = [
    ':user_id' => $userId,
];

if ($search !== '') {
    $where[] = '(n.title LIKE :search OR n.message LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($status === 'unread') {
    $where[] = 'n.read_at IS NULL';
}

if ($status === 'read') {
    $where[] = 'n.read_at IS NOT NULL';
}

if ($hasMetadataColumns && $sourceModule !== '') {
    $where[] = 'n.source_module = :source_module';
    $params[':source_module'] = $sourceModule;
}

if ($hasMetadataColumns && $templateKey !== '') {
    $where[] = 'n.template_key = :template_key';
    $params[':template_key'] = $templateKey;
}

$metaSelect = $hasMetadataColumns
    ? 'n.template_key, n.source_module, n.entity_type, n.entity_id,'
    : 'NULL AS template_key, NULL AS source_module, NULL AS entity_type, NULL AS entity_id,';

$sql = "
    SELECT
        n.id,
        n.title,
        n.message,
        n.channel,
        {$metaSelect}
        n.created_at,
        n.read_at
    FROM notifications n
    WHERE " . implode(' AND ', $where) . "
    ORDER BY n.id DESC
    LIMIT 5000
";

$stmt = db()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$rows = [];
while ($notification = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $entity = trim((string) ($notification['entity_type'] ?? ''));
    $entityId = (int) ($notification['entity_id'] ?? 0);

    $rows[] = [
        (int) ($notification['id'] ?? 0),
        (string) ($notification['title'] ?? ''),
        (string) ($notification['message'] ?? ''),
        (string) ($notification['channel'] ?? ''),
        empty($notification['read_at']) ? notifications_lang('status_unread') : notifications_lang('status_read'),
        (string) ($notification['source_module'] ?? ''),
        (string) ($notification['template_key'] ?? ''),
        $entity,
        $entityId > 0 ? $entityId : '',
        kirpi_format_datetime((string) ($notification['created_at'] ?? '')),
        kirpi_format_datetime((string) ($notification['read_at'] ?? '')),
    ];
}

kirpi_export_response($format, 'notifications-' . date('Ymd-His'), [
    'ID',
    notifications_lang('table_notification'),
    'Mesaj',
    notifications_lang('table_channel'),
    notifications_lang('table_status'),
    notifications_lang('table_source'),
    'Template',
    'Entity',
    'Entity ID',
    notifications_lang('table_date'),
    'Okunma Tarihi',
], $rows);
