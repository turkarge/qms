<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/documents/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response(['status' => 'error', 'message' => documents_lang('csrf_failed')], 419);
}

$documentId = (int) ($_POST['id'] ?? 0);
$document = document_find($documentId);

if (!$document) {
    json_response(['status' => 'error', 'message' => documents_lang('not_found')], 404);
}

try {
    document_delete($documentId);

    kirpi_audit_log('document_delete', 'documents', [
        'document_id' => $documentId,
        'original_name' => (string) ($document['original_name'] ?? ''),
    ], 'document', $documentId, 'success');

    kirpi_notify_current_user('documents.deleted', [
        'document_id' => $documentId,
        'original_name' => (string) ($document['original_name'] ?? ''),
    ], [
        'title' => 'Doküman silindi',
        'message' => '"' . (string) ($document['original_name'] ?? 'Doküman') . '" dokümanı silindi.',
        'source_module' => 'documents',
        'entity_type' => 'document',
        'entity_id' => $documentId,
    ]);

    json_response([
        'status' => 'success',
        'message' => documents_lang('delete_success'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('document delete error: ' . $e->getMessage());
    json_response([
        'status' => 'error',
        'message' => APP_DEBUG ? (documents_lang('delete_error') . ' [' . $e->getMessage() . ']') : documents_lang('delete_error'),
    ], 500);
}
