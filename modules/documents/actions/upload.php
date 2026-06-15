<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/documents/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response(['status' => 'error', 'message' => documents_lang('csrf_failed')], 419);
}

$documentType = documents_sanitize_key((string) ($_POST['document_type'] ?? 'attachment'));
$entityType = documents_sanitize_key((string) ($_POST['entity_type'] ?? ''), '');
$entityId = (int) ($_POST['entity_id'] ?? 0);
$isFilePondUpload = (string) ($_POST['filepond'] ?? '') === '1';
$uploadInput = $_FILES['document_files'] ?? ($_FILES['document_file'] ?? []);
$files = documents_normalize_uploads($uploadInput);

if ($files === []) {
    json_response(['status' => 'error', 'message' => documents_lang('files_required')], 422);
}

$uploaded = [];
$failed = [];
foreach ($files as $file) {
    $result = document_store_upload($file, $documentType);
    if (!($result['success'] ?? false) || !empty($result['skipped'])) {
        $failed[] = [
            'name' => (string) ($file['name'] ?? ''),
            'message' => (string) ($result['message'] ?? documents_lang('upload_error')),
        ];
        continue;
    }

    $documentId = (int) ($result['document_id'] ?? 0);
    if ($entityType !== '' && $entityId > 0) {
        document_link_existing($documentId, $entityType, $entityId, $documentType);
    }

    $uploaded[] = ['id' => $documentId, 'name' => (string) ($file['name'] ?? '')];
    kirpi_audit_log('document_upload', 'documents', [
        'document_id' => $documentId,
        'document_type' => $documentType,
        'entity_type' => $entityType !== '' ? $entityType : null,
        'entity_id' => $entityId > 0 ? $entityId : null,
    ], 'document', $documentId, 'success');
}

if ($uploaded === []) {
    json_response([
        'status' => 'error',
        'message' => (string) ($failed[0]['message'] ?? documents_lang('upload_error')),
        'uploaded' => [],
        'failed' => $failed,
    ], 422);
}

$firstDocumentId = (int) ($uploaded[0]['id'] ?? 0);
$summaryTemplate = $failed === [] ? documents_lang('upload_summary') : documents_lang('upload_partial_summary');
$summary = str_replace(
    ['{count}', '{failed}'],
    [(string) count($uploaded), (string) count($failed)],
    $summaryTemplate
);
if (!$isFilePondUpload) {
    kirpi_notify_current_user('documents.uploaded', [
        'document_id' => $firstDocumentId,
        'document_type' => $documentType,
        'uploaded_count' => count($uploaded),
        'failed_count' => count($failed),
    ], [
        'title' => documents_lang('upload_success_title'),
        'message' => $summary,
        'source_module' => 'documents',
        'entity_type' => 'document',
        'entity_id' => $firstDocumentId,
    ]);
}

json_response([
    'status' => 'success',
    'message' => $summary,
    'uploaded' => $uploaded,
    'failed' => $failed,
    'reload_page' => true,
]);
