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

$result = kirpi_backup_verify($backupId, $userId > 0 ? $userId : null);
if (!($result['success'] ?? false)) {
    kirpi_audit_log('verify', 'backup', [
        'backup_id' => $backupId,
        'error' => (string) ($result['message'] ?? 'backup verify failed'),
    ], 'backup', $backupId, 'failed');

    json_response([
        'status' => 'error',
        'message' => (string) ($result['message'] ?? backup_lang('verify_failed_default')),
    ], 422);
}

$message = (string) ($result['message'] ?? backup_lang('verify_success_default'));
$checksum = (string) ($result['checksum'] ?? '');
$dryRun = (bool) ($result['dry_run'] ?? false);
$tableCount = (int) ($result['dry_run_table_count'] ?? 0);

if ($checksum !== '') {
    $message .= backup_lang('checksum_prefix') . substr($checksum, 0, 12) . '...';
}
if ($dryRun) {
    $message .= backup_lang('dry_run_prefix') . $tableCount . backup_lang('dry_run_suffix');
}

kirpi_audit_log('verify', 'backup', [
    'backup_id' => $backupId,
    'checksum' => $checksum,
    'dry_run' => $dryRun,
    'dry_run_table_count' => $tableCount,
], 'backup', $backupId, 'success');

kirpi_notify_current_user('backup.verified', [
    'backup_id' => $backupId,
    'checksum' => $checksum,
    'dry_run' => $dryRun,
    'dry_run_table_count' => $tableCount,
], [
    'title' => 'Yedek doğrulandı',
    'message' => 'Yedek #' . $backupId . ' başarıyla doğrulandı.',
    'source_module' => 'backup',
    'entity_type' => 'backup',
    'entity_id' => $backupId,
]);

json_response([
    'status' => 'success',
    'message' => $message,
]);
