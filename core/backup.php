<?php

function kirpi_backup_table_ready(): bool
{
    return db_table_exists('db_backups');
}

function kirpi_backup_storage_dir(): string
{
    $dir = BASE_PATH . '/storage/backups';

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

function kirpi_shell_exec_available(): bool
{
    if (!function_exists('shell_exec')) {
        return false;
    }

    $disabled = (string) ini_get('disable_functions');
    if ($disabled === '') {
        return true;
    }

    $items = array_map('trim', explode(',', $disabled));
    return !in_array('shell_exec', $items, true);
}

function kirpi_mysql_password_arg(): string
{
    $password = (string) DB_PASS;
    return '--password=' . escapeshellarg($password);
}

function kirpi_mysql_ssl_candidates(): array
{
    $sslMode = strtoupper(trim((string) env('DB_SSL_MODE', 'DISABLED')));
    $allowed = ['DISABLED', 'PREFERRED', 'REQUIRED', 'VERIFY_CA', 'VERIFY_IDENTITY'];

    if (!in_array($sslMode, $allowed, true)) {
        $sslMode = 'DISABLED';
    }

    if ($sslMode === 'DISABLED') {
        return [
            '',
            '--skip-ssl',
            '--ssl=0',
            '--ssl-mode=DISABLED',
        ];
    }

    if ($sslMode === 'PREFERRED') {
        return [
            '',
            '--ssl-mode=PREFERRED',
        ];
    }

    return [
        '--ssl-mode=REQUIRED',
        '--ssl',
    ];
}

function kirpi_backup_run_command(string $command, ?string $errorPath = null): array
{
    $outputLines = [];
    $exitCode = 0;
    exec($command, $outputLines, $exitCode);

    $errorOutput = '';
    if ($errorPath !== null && is_file($errorPath)) {
        $errorOutput = trim((string) file_get_contents($errorPath));
        @unlink($errorPath);
    }

    $output = trim(implode(PHP_EOL, $outputLines));
    if ($errorOutput === '' && $output !== '') {
        $errorOutput = $output;
    }

    return [
        'exit_code' => $exitCode,
        'error_output' => $errorOutput,
    ];
}

function kirpi_backup_is_unknown_ssl_option(string $errorText): bool
{
    $errorText = strtolower($errorText);
    if ($errorText === '') {
        return false;
    }

    return str_contains($errorText, 'unknown variable') || str_contains($errorText, 'unknown option');
}

function kirpi_backup_ignore_tables_args(): string
{
    $includeSystemTables = filter_var((string) env('BACKUP_INCLUDE_SYSTEM_TABLES', 'false'), FILTER_VALIDATE_BOOLEAN);
    if ($includeSystemTables) {
        return '';
    }

    $dbName = (string) DB_NAME;
    $ignoredTables = [
        'db_backups',
        'db_backup_restores',
    ];

    $args = [];
    foreach ($ignoredTables as $table) {
        $args[] = '--ignore-table=' . escapeshellarg($dbName . '.' . $table);
    }

    return implode(' ', $args);
}

function kirpi_backup_retention_count(): int
{
    $value = (int) env('BACKUP_RETENTION_COUNT', '20');
    return $value > 0 ? $value : 0;
}

function kirpi_backup_apply_retention(?int $keepCount = null): array
{
    if (!kirpi_backup_table_ready()) {
        return [
            'deleted_count' => 0,
            'deleted_files' => 0,
            'deleted_ids' => [],
        ];
    }

    $keep = $keepCount ?? kirpi_backup_retention_count();
    if ($keep <= 0) {
        return [
            'deleted_count' => 0,
            'deleted_files' => 0,
            'deleted_ids' => [],
        ];
    }

    $stmt = db()->prepare("\n        SELECT id, file_path\n        FROM db_backups\n        ORDER BY id DESC\n    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (count($rows) <= $keep) {
        return [
            'deleted_count' => 0,
            'deleted_files' => 0,
            'deleted_ids' => [],
        ];
    }

    $toDelete = array_slice($rows, $keep);
    $deleteIds = [];
    $deletedFiles = 0;
    $backupDir = realpath(kirpi_backup_storage_dir()) ?: '';

    foreach ($toDelete as $item) {
        $id = (int) ($item['id'] ?? 0);
        $filePath = (string) ($item['file_path'] ?? '');
        $realFile = realpath($filePath) ?: '';

        if (
            $id > 0
            && $backupDir !== ''
            && $realFile !== ''
            && str_starts_with($realFile, $backupDir . DIRECTORY_SEPARATOR)
            && is_file($realFile)
        ) {
            if (@unlink($realFile)) {
                $deletedFiles++;
            }
        }

        if ($id > 0) {
            $deleteIds[] = $id;
        }
    }

    if (!empty($deleteIds)) {
        $placeholders = implode(', ', array_fill(0, count($deleteIds), '?'));
        $deleteStmt = db()->prepare("DELETE FROM db_backups WHERE id IN ($placeholders)");
        $deleteStmt->execute($deleteIds);
    }

    return [
        'deleted_count' => count($deleteIds),
        'deleted_files' => $deletedFiles,
        'deleted_ids' => $deleteIds,
    ];
}

function kirpi_backup_checksum_sha256(string $filePath): string
{
    $hash = @hash_file('sha256', $filePath);
    return is_string($hash) ? $hash : '';
}

function kirpi_mysql_exec_sql(string $sql): array
{
    $lastOutput = '';
    $lastExitCode = 1;

    foreach (kirpi_mysql_ssl_candidates() as $sslArg) {
        $command = sprintf(
            'mysql %s -h %s -P %s -u %s %s -e %s 2>&1',
            $sslArg,
            escapeshellarg((string) DB_HOST),
            escapeshellarg((string) DB_PORT),
            escapeshellarg((string) DB_USER),
            kirpi_mysql_password_arg(),
            escapeshellarg($sql)
        );

        $run = kirpi_backup_run_command($command);
        $lastExitCode = (int) ($run['exit_code'] ?? 1);
        $lastOutput = trim((string) ($run['error_output'] ?? ''));

        if ($lastExitCode === 0) {
            return [
                'success' => true,
                'exit_code' => 0,
                'output' => $lastOutput,
            ];
        }
    }

    return [
        'success' => false,
        'exit_code' => $lastExitCode,
        'output' => $lastOutput,
    ];
}

function kirpi_backup_restore_file_to_database(string $filePath, string $databaseName): array
{
    $lastOutput = '';
    $lastExitCode = 1;

    foreach (kirpi_mysql_ssl_candidates() as $sslArg) {
        $command = sprintf(
            'mysql %s -h %s -P %s -u %s %s %s < %s 2>&1',
            $sslArg,
            escapeshellarg((string) DB_HOST),
            escapeshellarg((string) DB_PORT),
            escapeshellarg((string) DB_USER),
            kirpi_mysql_password_arg(),
            escapeshellarg($databaseName),
            escapeshellarg($filePath)
        );

        $run = kirpi_backup_run_command($command);
        $lastExitCode = (int) ($run['exit_code'] ?? 1);
        $lastOutput = trim((string) ($run['error_output'] ?? ''));

        if ($lastExitCode === 0) {
            return [
                'success' => true,
                'exit_code' => 0,
                'output' => $lastOutput,
            ];
        }
    }

    return [
        'success' => false,
        'exit_code' => $lastExitCode,
        'output' => $lastOutput,
    ];
}

function kirpi_backup_verify(int $backupId, ?int $userId = null): array
{
    if (!kirpi_backup_table_ready()) {
        return [
            'success' => false,
            'message' => 'Backup table is not ready.',
        ];
    }

    if (!kirpi_shell_exec_available()) {
        return [
            'success' => false,
            'message' => 'shell_exec kullanılamıyor. Doğrulama çalıştırılamadı.',
        ];
    }

    $stmt = db()->prepare("\n        SELECT id, file_path, file_name\n        FROM db_backups\n        WHERE id = :id\n        LIMIT 1\n    ");
    $stmt->execute([
        ':id' => $backupId,
    ]);

    $backup = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$backup) {
        return [
            'success' => false,
            'message' => 'Backup kaydı bulunamadı.',
        ];
    }

    $filePath = (string) ($backup['file_path'] ?? '');
    if ($filePath === '' || !is_file($filePath) || !is_readable($filePath)) {
        return [
            'success' => false,
            'message' => 'Backup dosyası bulunamadı veya okunamıyor.',
        ];
    }

    $checksum = kirpi_backup_checksum_sha256($filePath);
    if ($checksum === '') {
        return [
            'success' => false,
            'message' => 'Backup SHA-256 hesabı oluşturulamadı.',
        ];
    }

    $dryRunEnabled = filter_var((string) env('BACKUP_VERIFY_DRY_RUN', 'true'), FILTER_VALIDATE_BOOLEAN);
    if (!$dryRunEnabled) {
        return [
            'success' => true,
            'message' => 'Backup dogrulandi (checksum).',
            'checksum' => $checksum,
            'dry_run' => false,
        ];
    }

    $suffix = date('YmdHis') . '_' . substr(md5(uniqid((string) $backupId, true)), 0, 8);
    $tempDatabaseName = 'kirpi_backup_verify_' . $suffix;
    $escapedDbName = str_replace('`', '``', $tempDatabaseName);
    $charset = preg_replace('/[^a-zA-Z0-9_]/', '', (string) DB_CHARSET) ?: 'utf8mb4';
    $createSql = "CREATE DATABASE IF NOT EXISTS `{$escapedDbName}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci";
    $dropSql = "DROP DATABASE IF EXISTS `{$escapedDbName}`";

    $createResult = kirpi_mysql_exec_sql($createSql);
    if (!($createResult['success'] ?? false)) {
        return [
            'success' => false,
            'message' => 'Dry-run DB oluşturulamadı. ' . (string) ($createResult['output'] ?? ''),
        ];
    }

    $restoreOutput = '';
    try {
        $restoreResult = kirpi_backup_restore_file_to_database($filePath, $tempDatabaseName);
        $restoreOutput = (string) ($restoreResult['output'] ?? '');

        if (!($restoreResult['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'Dry-run restore başarısız. ' . ($restoreOutput !== '' ? $restoreOutput : ('exit code: ' . (string) ($restoreResult['exit_code'] ?? '1'))),
            ];
        }

        $countStmt = db()->prepare("\n            SELECT COUNT(*)\n            FROM information_schema.tables\n            WHERE table_schema = :schema\n        ");
        $countStmt->execute([
            ':schema' => $tempDatabaseName,
        ]);
        $tableCount = (int) $countStmt->fetchColumn();
    } finally {
        kirpi_mysql_exec_sql($dropSql);
    }

    if (db_table_exists('db_backup_restores')) {
        try {
            $logStmt = db()->prepare("\n                INSERT INTO db_backup_restores (\n                    backup_id,\n                    restored_by,\n                    restore_output\n                ) VALUES (\n                    :backup_id,\n                    :restored_by,\n                    :restore_output\n                )\n            ");
            $logStmt->execute([
                ':backup_id' => $backupId,
                ':restored_by' => $userId,
                ':restore_output' => mb_substr('[VERIFY] SHA256=' . $checksum . ' tables=' . $tableCount . ' output=' . trim($restoreOutput), 0, 5000),
            ]);
        } catch (Throwable $e) {
            error_log('backup verify log error: ' . $e->getMessage());
        }
    }

    return [
        'success' => true,
        'message' => 'Backup dogrulandi (checksum + dry-run restore).',
        'checksum' => $checksum,
        'dry_run' => true,
        'dry_run_table_count' => $tableCount,
    ];
}

function kirpi_backup_create(?string $label = null, ?int $userId = null): array
{
    if (!kirpi_backup_table_ready()) {
        return [
            'success' => false,
            'message' => 'Backup table is not ready.',
        ];
    }

    if (!kirpi_shell_exec_available()) {
        return [
            'success' => false,
            'message' => 'shell_exec kullanılamıyor. Backup oluşturulamadı.',
        ];
    }

    $dir = kirpi_backup_storage_dir();
    $stamp = date('Ymd_His');
    $safeLabel = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($label ?? 'manual')) ?: 'manual';
    $fileName = 'backup_' . $stamp . '_' . $safeLabel . '.sql';
    $fullPath = $dir . '/' . $fileName;
    $errorPath = $dir . '/' . $fileName . '.err';

    $attemptErrors = [];
    $success = false;

    foreach (kirpi_mysql_ssl_candidates() as $sslArg) {
        $command = sprintf(
            'mysqldump --single-transaction --routines --triggers --events %s %s -h %s -P %s -u %s %s %s 1> %s 2> %s',
            $sslArg,
            kirpi_backup_ignore_tables_args(),
            escapeshellarg((string) DB_HOST),
            escapeshellarg((string) DB_PORT),
            escapeshellarg((string) DB_USER),
            kirpi_mysql_password_arg(),
            escapeshellarg((string) DB_NAME),
            escapeshellarg($fullPath),
            escapeshellarg($errorPath)
        );

        $run = kirpi_backup_run_command($command, $errorPath);
        $exitCode = (int) ($run['exit_code'] ?? 1);
        $errorOutput = trim((string) ($run['error_output'] ?? ''));

        if ($exitCode === 0 && is_file($fullPath) && filesize($fullPath) > 0) {
            $success = true;
            break;
        }

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        if ($errorOutput !== '') {
            $attemptErrors[] = $errorOutput;
        } else {
            $attemptErrors[] = 'exit code: ' . $exitCode;
        }

        if ($errorOutput !== '' && !kirpi_backup_is_unknown_ssl_option($errorOutput)) {
            // Not an option-compatibility issue; no need to keep trying unsupported variants.
            continue;
        }
    }

    if (!$success) {
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        return [
            'success' => false,
            'message' => 'mysqldump başarısız. ' . implode(' | ', array_slice($attemptErrors, -2)),
        ];
    }

    $size = (int) filesize($fullPath);

    $stmt = db()->prepare("\n        INSERT INTO db_backups (\n            label,\n            file_name,\n            file_path,\n            file_size,\n            status,\n            created_by\n        ) VALUES (\n            :label,\n            :file_name,\n            :file_path,\n            :file_size,\n            'ready',\n            :created_by\n        )\n    ");
    $stmt->execute([
        ':label' => $label !== null && trim($label) !== '' ? trim($label) : ('Backup ' . date('d.m.Y H:i')),
        ':file_name' => $fileName,
        ':file_path' => $fullPath,
        ':file_size' => $size,
        ':created_by' => $userId,
    ]);

    $retention = kirpi_backup_apply_retention();

    return [
        'success' => true,
        'backup_id' => (int) db()->lastInsertId(),
        'file_name' => $fileName,
        'file_size' => $size,
        'retention_deleted_count' => (int) ($retention['deleted_count'] ?? 0),
        'retention_deleted_ids' => $retention['deleted_ids'] ?? [],
    ];
}

function kirpi_backup_restore(int $backupId, ?int $userId = null): array
{
    if (!kirpi_backup_table_ready()) {
        return [
            'success' => false,
            'message' => 'Backup table is not ready.',
        ];
    }

    if (!kirpi_shell_exec_available()) {
        return [
            'success' => false,
            'message' => 'shell_exec kullanılamıyor. Restore çalıştırılamadı.',
        ];
    }

    $stmt = db()->prepare("\n        SELECT id, label, file_name, file_path, file_size, status, created_by\n        FROM db_backups\n        WHERE id = :id\n        LIMIT 1\n    ");
    $stmt->execute([
        ':id' => $backupId,
    ]);

    $backup = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$backup) {
        return [
            'success' => false,
            'message' => 'Backup kaydı bulunamadı.',
        ];
    }

    $filePath = (string) ($backup['file_path'] ?? '');
    if ($filePath === '' || !is_file($filePath)) {
        return [
            'success' => false,
            'message' => 'Backup dosyası bulunamadı.',
        ];
    }

    $exitCode = 1;
    $output = '';

    foreach (kirpi_mysql_ssl_candidates() as $sslArg) {
        $command = sprintf(
            'mysql %s -h %s -P %s -u %s %s %s < %s 2>&1',
            $sslArg,
            escapeshellarg((string) DB_HOST),
            escapeshellarg((string) DB_PORT),
            escapeshellarg((string) DB_USER),
            kirpi_mysql_password_arg(),
            escapeshellarg((string) DB_NAME),
            escapeshellarg($filePath)
        );

        $run = kirpi_backup_run_command($command);
        $exitCode = (int) ($run['exit_code'] ?? 1);
        $output = trim((string) ($run['error_output'] ?? ''));

        if ($exitCode === 0) {
            break;
        }
    }

    if ($exitCode !== 0) {
        return [
            'success' => false,
            'message' => 'Restore komutu başarısız. ' . ($output !== '' ? $output : ('exit code: ' . $exitCode)),
        ];
    }

    // Restore dump eskiyse backup kayıtlarını geri alabilir/silebilir.
    // Dosya kaydini geri ekleyip restore logunu "best effort" olarak yazariz.
    try {
        if (db_table_exists('db_backups')) {
            $checkStmt = db()->prepare("\n                SELECT id\n                FROM db_backups\n                WHERE id = :id\n                LIMIT 1\n            ");
            $checkStmt->execute([
                ':id' => $backupId,
            ]);

            $exists = $checkStmt->fetchColumn();
            if ($exists === false) {
                $reInsertStmt = db()->prepare("\n                    INSERT INTO db_backups (\n                        label,\n                        file_name,\n                        file_path,\n                        file_size,\n                        status,\n                        created_by\n                    ) VALUES (\n                        :label,\n                        :file_name,\n                        :file_path,\n                        :file_size,\n                        :status,\n                        :created_by\n                    )\n                ");
                $reInsertStmt->execute([
                    ':label' => (string) ($backup['label'] ?? ('Backup ' . date('d.m.Y H:i'))),
                    ':file_name' => (string) ($backup['file_name'] ?? ''),
                    ':file_path' => (string) ($backup['file_path'] ?? ''),
                    ':file_size' => (int) ($backup['file_size'] ?? 0),
                    ':status' => (string) ($backup['status'] ?? 'ready'),
                    ':created_by' => (int) ($backup['created_by'] ?? 0) ?: null,
                ]);
            }
        }

        if (db_table_exists('db_backup_restores')) {
            $logStmt = db()->prepare("\n                INSERT INTO db_backup_restores (\n                    backup_id,\n                    restored_by,\n                    restore_output\n                ) VALUES (\n                    :backup_id,\n                    :restored_by,\n                    :restore_output\n                )\n            ");
            $logStmt->execute([
                ':backup_id' => $backupId,
                ':restored_by' => $userId,
                ':restore_output' => mb_substr(trim((string) $output), 0, 5000),
            ]);
        }
    } catch (Throwable $e) {
        error_log('backup restore post-process error: ' . $e->getMessage());
    }

    return [
        'success' => true,
        'message' => 'Backup geri yükleme komutu çalıştırıldı.',
    ];
}
