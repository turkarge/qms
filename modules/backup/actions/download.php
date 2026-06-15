<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/backup/language.php';

require_action('GET', true);

$backupId = (int) ($_GET['id'] ?? 0);
if ($backupId <= 0) {
    http_response_code(422);
    exit(backup_lang('invalid_backup_record'));
}

if (!kirpi_backup_table_ready()) {
    http_response_code(422);
    exit(backup_lang('table_not_ready'));
}

try {
    $stmt = db()->prepare("
        SELECT id, file_name, file_path
        FROM db_backups
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        ':id' => $backupId,
    ]);

    $backup = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$backup) {
        http_response_code(404);
        exit(backup_lang('record_not_found'));
    }

    $filePath = (string) ($backup['file_path'] ?? '');
    $fileName = (string) ($backup['file_name'] ?? ('backup_' . $backupId . '.sql'));

    $backupDir = realpath(kirpi_backup_storage_dir()) ?: '';
    $realFile = realpath($filePath) ?: '';

    if ($backupDir === '' || $realFile === '' || !str_starts_with($realFile, $backupDir . DIRECTORY_SEPARATOR)) {
        http_response_code(403);
        exit(backup_lang('file_path_invalid'));
    }

    if (!is_file($realFile) || !is_readable($realFile)) {
        http_response_code(404);
        exit(backup_lang('file_not_found'));
    }

    kirpi_audit_log('download', 'backup', [
        'backup_id' => $backupId,
        'file_name' => $fileName,
    ], 'backup', $backupId, 'success');

    header('Content-Description: File Transfer');
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
    header('Content-Length: ' . (string) filesize($realFile));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($realFile);
    exit;
} catch (Throwable $e) {
    error_log('backup download error: ' . $e->getMessage());
    http_response_code(500);
    exit(backup_lang('download_error'));
}
