<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/backup/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => backup_lang('csrf_failed'),
    ], 419);
}

$label = trim((string) ($_POST['label'] ?? ''));
$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);

$result = kirpi_backup_create($label !== '' ? $label : null, $userId > 0 ? $userId : null);
if (!($result['success'] ?? false)) {
    kirpi_audit_log('create', 'backup', [
        'label' => $label,
        'error' => (string) ($result['message'] ?? 'backup create failed'),
    ], 'backup', null, 'failed');

    json_response([
        'status' => 'error',
        'message' => (string) ($result['message'] ?? backup_lang('create_failed_default')),
    ], 422);
}

$backupId = (int) ($result['backup_id'] ?? 0);
$retentionDeletedCount = (int) ($result['retention_deleted_count'] ?? 0);
kirpi_audit_log('create', 'backup', [
    'backup_id' => $backupId,
    'file_name' => (string) ($result['file_name'] ?? ''),
    'retention_deleted_count' => $retentionDeletedCount,
], 'backup', $backupId, 'success');

$fileSize = (int) ($result['file_size'] ?? 0);
if ($userId > 0) {
    kirpi_notify_user($userId, 'backup.completed', [
        'label' => $label !== '' ? $label : ('Backup #' . $backupId),
        'file_name' => (string) ($result['file_name'] ?? ''),
        'file_size' => $fileSize > 0 ? number_format($fileSize / 1024 / 1024, 2) . ' MB' : '0 MB',
    ], [
        'title' => 'Backup tamamlandı',
        'message' => 'Backup #' . $backupId . ' başarıyla oluşturuldu.',
        'source_module' => 'backup',
        'entity_type' => 'backup',
        'entity_id' => $backupId,
        'email' => false,
    ]);
}

$message = backup_lang('create_success_prefix') . $backupId;
if ($retentionDeletedCount > 0) {
    $message .= backup_lang('retention_deleted_prefix') . $retentionDeletedCount . backup_lang('retention_deleted_suffix');
}

json_response([
    'status' => 'success',
    'message' => $message,
    'reload_page' => true,
]);
