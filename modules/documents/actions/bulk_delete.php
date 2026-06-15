<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/documents/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response(['status' => 'error', 'message' => documents_lang('csrf_failed')], 419);
}

$rawIds = $_POST['ids'] ?? [];
if (is_string($rawIds)) {
    $decoded = json_decode($rawIds, true);
    $rawIds = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', $rawIds);
}

$ids = array_values(array_unique(array_filter(array_map('intval', (array) $rawIds), static fn (int $id): bool => $id > 0)));
if ($ids === []) {
    json_response(['status' => 'error', 'message' => documents_lang('select_files')], 422);
}

$deleted = [];
$failed = [];
foreach ($ids as $documentId) {
    $document = document_find($documentId);
    if (!$document) {
        $failed[] = $documentId;
        continue;
    }

    try {
        if (!document_delete($documentId)) {
            $failed[] = $documentId;
            continue;
        }
        $deleted[] = $documentId;
        kirpi_audit_log('document_delete', 'documents', [
            'document_id' => $documentId,
            'original_name' => (string) ($document['original_name'] ?? ''),
            'bulk' => true,
        ], 'document', $documentId, 'success');
    } catch (Throwable $e) {
        error_log('document bulk delete error: ' . $e->getMessage());
        $failed[] = $documentId;
    }
}

if ($deleted === []) {
    json_response(['status' => 'error', 'message' => documents_lang('bulk_delete_error')], 500);
}

json_response([
    'status' => 'success',
    'message' => str_replace('{count}', (string) count($deleted), documents_lang('bulk_delete_success')),
    'deleted' => $deleted,
    'failed' => $failed,
    'reload_page' => true,
]);
