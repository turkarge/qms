<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/documents/language.php';

$documentId = (int) ($_GET['id'] ?? 0);
$document = document_find($documentId);

if (!$document) {
    http_response_code(404);
    exit(documents_lang('not_found'));
}

$fullPath = document_storage_full_path($document);
if ($fullPath === null) {
    http_response_code(404);
    exit(documents_lang('file_not_found'));
}

kirpi_audit_log('document_download', 'documents', [
    'document_id' => $documentId,
], 'document', $documentId, 'success');

$fileName = basename((string) ($document['original_name'] ?? $document['stored_name'] ?? ('document_' . $documentId)));
$mimeType = (string) ($document['mime_type'] ?? 'application/octet-stream');

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $fileName) . '"');
header('Content-Length: ' . (string) filesize($fullPath));
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
exit;
