<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;

require_action('GET', true);
$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);
if ($userId <= 0) json_response(['error' => notifications_lang('invalid_session')], 403);

$request = kirpi_table_request();
$searches = kirpi_table_column_searches($request);
$hasMetadata = db_column_exists('notifications', 'source_module') && db_column_exists('notifications', 'template_key') && db_column_exists('notifications', 'entity_type') && db_column_exists('notifications', 'entity_id');
$columnMap = ['title' => 'n.title', 'source_module' => 'n.source_module', 'channel' => 'n.channel', 'read_status' => 'n.read_at', 'created_at' => 'n.created_at'];
$where = ['n.user_id = :user_id'];
$params = [':user_id' => $userId];

$titleSearch = $searches['title'] ?? '';
$global = $request['search'];
if ($global !== '') {
    $where[] = $hasMetadata
        ? '(n.title LIKE :global_title OR n.message LIKE :global_message OR n.template_key LIKE :global_template)'
        : '(n.title LIKE :global_title OR n.message LIKE :global_message)';
    $params[':global_title'] = '%' . $global . '%';
    $params[':global_message'] = '%' . $global . '%';
    if ($hasMetadata) $params[':global_template'] = '%' . $global . '%';
}
if ($titleSearch !== '') {
    $where[] = $hasMetadata
        ? '(n.title LIKE :title_search OR n.message LIKE :message_search OR n.template_key LIKE :template_search)'
        : '(n.title LIKE :title_search OR n.message LIKE :message_search)';
    $params[':title_search'] = '%' . $titleSearch . '%';
    $params[':message_search'] = '%' . $titleSearch . '%';
    if ($hasMetadata) $params[':template_search'] = '%' . $titleSearch . '%';
}
if ($hasMetadata && !empty($searches['source_module'])) {
    $where[] = 'n.source_module = :source_module';
    $params[':source_module'] = $searches['source_module'];
}
if (!empty($searches['channel'])) {
    $where[] = 'n.channel LIKE :channel';
    $params[':channel'] = '%' . $searches['channel'] . '%';
}
if (($searches['read_status'] ?? '') === 'read') $where[] = 'n.read_at IS NOT NULL';
if (($searches['read_status'] ?? '') === 'unread') $where[] = 'n.read_at IS NULL';
if (!empty($searches['created_at'])) {
    $where[] = 'n.created_at LIKE :created_at';
    $params[':created_at'] = '%' . $searches['created_at'] . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$orderSql = kirpi_table_order_sql($request, $columnMap, 'n.id DESC');
$metaSelect = $hasMetadata ? 'n.template_key, n.source_module, n.entity_type, n.entity_id,' : 'NULL AS template_key, NULL AS source_module, NULL AS entity_type, NULL AS entity_id,';

try {
    $totalStmt = db()->prepare('SELECT COUNT(id) FROM notifications WHERE user_id = :user_id');
    $totalStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $totalStmt->execute();
    $total = (int) $totalStmt->fetchColumn();
    $count = db()->prepare("SELECT COUNT(n.id) FROM notifications n {$whereSql}");
    kirpi_table_bind($count, $params);
    $count->execute();
    $filtered = (int) $count->fetchColumn();
    $stmt = db()->prepare("SELECT n.id, n.title, n.message, n.channel, {$metaSelect} n.created_at, n.read_at FROM notifications n {$whereSql} ORDER BY {$orderSql} LIMIT :length OFFSET :start");
    kirpi_table_bind($stmt, $params);
    $stmt->bindValue(':length', $request['length'], PDO::PARAM_INT);
    $stmt->bindValue(':start', $request['start'], PDO::PARAM_INT);
    $stmt->execute();
    $data = array_map(static fn(array $row): array => [
        'id' => (int) $row['id'],
        'row_key' => 'notification-' . (int) $row['id'],
        'title' => (string) $row['title'],
        'message' => (string) $row['message'],
        'channel' => (string) $row['channel'],
        'template_key' => (string) ($row['template_key'] ?? ''),
        'source_module' => (string) ($row['source_module'] ?? ''),
        'entity_type' => (string) ($row['entity_type'] ?? ''),
        'entity_id' => isset($row['entity_id']) ? (int) $row['entity_id'] : null,
        'read_status' => empty($row['read_at']) ? 'unread' : 'read',
        'created_at_display' => kirpi_format_datetime((string) $row['created_at']),
    ], $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    kirpi_table_response($request, $total, $filtered, $data);
} catch (Throwable $e) {
    error_log('notifications datatable error: ' . $e->getMessage());
    json_response(['draw' => $request['draw'], 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => notifications_lang('list_load_error')], 500);
}
