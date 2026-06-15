<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/documents/language.php';

if (!documents_tables_ready()) {
    http_response_code(404);
    echo documents_lang('tables_missing');
    exit;
}

$format = trim((string) ($_GET['format'] ?? 'csv'));
$search = trim((string) ($_GET['search'] ?? ''));
$documentType = trim((string) ($_GET['document_type'] ?? ''));
$entityType = trim((string) ($_GET['entity_type'] ?? ''));
$entityId = (int) ($_GET['entity_id'] ?? 0);

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(d.original_name LIKE :search OR d.mime_type LIKE :search OR d.storage_path LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($documentType !== '') {
    $where[] = 'd.document_type = :document_type';
    $params[':document_type'] = $documentType;
}

if ($entityType !== '') {
    $where[] = 'dl.entity_type = :entity_type';
    $params[':entity_type'] = $entityType;
}

if ($entityId > 0) {
    $where[] = 'dl.entity_id = :entity_id';
    $params[':entity_id'] = $entityId;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = db()->prepare("
    SELECT
        d.id,
        d.document_type,
        d.original_name,
        d.mime_type,
        d.file_size,
        d.created_at,
        u.name AS uploaded_by_name,
        COUNT(dl.id) AS link_count,
        GROUP_CONCAT(
            DISTINCT CONCAT(dl.entity_type, '#', dl.entity_id, ' (', dl.relation_type, ')')
            ORDER BY dl.entity_type ASC, dl.entity_id ASC
            SEPARATOR ', '
        ) AS entity_links
    FROM documents d
    LEFT JOIN users u ON u.id = d.uploaded_by_user_id
    LEFT JOIN document_links dl ON dl.document_id = d.id
    {$whereSql}
    GROUP BY d.id, d.document_type, d.original_name, d.mime_type, d.file_size, d.created_at, u.name
    ORDER BY d.id DESC
    LIMIT 5000
");
$stmt->execute($params);

$rows = [];
while ($document = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        (int) ($document['id'] ?? 0),
        (string) ($document['document_type'] ?? ''),
        (string) ($document['original_name'] ?? ''),
        (string) ($document['mime_type'] ?? ''),
        (int) ($document['file_size'] ?? 0),
        documents_format_size((int) ($document['file_size'] ?? 0)),
        (string) ($document['uploaded_by_name'] ?? ''),
        (int) ($document['link_count'] ?? 0),
        (string) ($document['entity_links'] ?? ''),
        kirpi_format_datetime((string) ($document['created_at'] ?? '')),
    ];
}

kirpi_export_response($format, 'documents-' . date('Ymd-His'), [
    'ID',
    documents_lang('document_type'),
    documents_lang('original_name'),
    documents_lang('mime_type'),
    documents_lang('file_size') . ' (bytes)',
    documents_lang('file_size'),
    documents_lang('uploaded_by'),
    'Link',
    documents_lang('entity_links'),
    documents_lang('created_at'),
], $rows);
