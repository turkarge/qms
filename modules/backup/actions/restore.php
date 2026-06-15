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

$backupId = (int) ($_POST['backup_id'] ?? 0);
if ($backupId <= 0) {
    json_response([
        'status' => 'error',
        'message' => backup_lang('invalid_backup_record'),
    ], 422);
}

$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);

$result = kirpi_backup_restore($backupId, $userId > 0 ? $userId : null);
if (!($result['success'] ?? false)) {
    kirpi_audit_log('restore', 'backup', [
        'backup_id' => $backupId,
        'error' => (string) ($result['message'] ?? 'backup restore failed'),
    ], 'backup', $backupId, 'failed');

    json_response([
        'status' => 'error',
        'message' => (string) ($result['message'] ?? backup_lang('restore_failed_default')),
    ], 422);
}

kirpi_audit_log('restore', 'backup', [
    'backup_id' => $backupId,
], 'backup', $backupId, 'success');

if ($userId > 0) {
    kirpi_notify_user($userId, 'backup.restored', [
        'backup_id' => $backupId,
    ], [
        'title' => 'Backup restore tamamlandı',
        'message' => 'Backup #' . $backupId . ' geri yükleme işlemi tamamlandı.',
        'source_module' => 'backup',
        'entity_type' => 'backup',
        'entity_id' => $backupId,
        'email' => false,
    ]);
}

json_response([
    'status' => 'success',
    'message' => backup_lang('restore_success'),
    'reload_page' => true,
]);
