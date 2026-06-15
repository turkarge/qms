<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/backup/language.php';

if (!kirpi_backup_table_ready()) {
    http_response_code(404);
    echo backup_lang('backup_tables_missing');
    exit;
}

$format = trim((string) ($_GET['format'] ?? 'csv'));
$type = trim((string) ($_GET['type'] ?? 'backups'));

if ($type === 'restores') {
    if (!db_table_exists('db_backup_restores')) {
        http_response_code(404);
        echo backup_lang('backup_tables_missing');
        exit;
    }

    $stmt = db()->query("
        SELECT
            r.id,
            r.backup_id,
            b.label,
            b.file_name,
            r.restore_output,
            r.created_at,
            u.name AS restored_by_name
        FROM db_backup_restores r
        LEFT JOIN db_backups b ON b.id = r.backup_id
        LEFT JOIN users u ON u.id = r.restored_by
        ORDER BY r.id DESC
        LIMIT 5000
    ");

    $rows = [];
    while ($restore = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = [
            (int) ($restore['id'] ?? 0),
            (int) ($restore['backup_id'] ?? 0),
            (string) ($restore['label'] ?? ''),
            (string) ($restore['file_name'] ?? ''),
            (string) ($restore['restored_by_name'] ?? ''),
            kirpi_format_datetime((string) ($restore['created_at'] ?? '')),
            (string) ($restore['restore_output'] ?? ''),
        ];
    }

    kirpi_export_response($format, 'backup-restores-' . date('Ymd-His'), [
        'ID',
        'Backup ID',
        backup_lang('label'),
        backup_lang('file'),
        backup_lang('restored_by'),
        backup_lang('date'),
        backup_lang('restore_output'),
    ], $rows);
}

$stmt = db()->query("
    SELECT
        b.id,
        b.label,
        b.file_name,
        b.file_size,
        b.status,
        b.created_at,
        b.updated_at,
        u.name AS created_by_name
    FROM db_backups b
    LEFT JOIN users u ON u.id = b.created_by
    ORDER BY b.id DESC
    LIMIT 5000
");

$rows = [];
while ($backup = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $fileSize = (int) ($backup['file_size'] ?? 0);
    $rows[] = [
        (int) ($backup['id'] ?? 0),
        (string) ($backup['label'] ?? ''),
        (string) ($backup['file_name'] ?? ''),
        $fileSize,
        number_format($fileSize / 1024, 2) . ' KB',
        (string) ($backup['status'] ?? ''),
        (string) ($backup['created_by_name'] ?? ''),
        kirpi_format_datetime((string) ($backup['created_at'] ?? '')),
        kirpi_format_datetime((string) ($backup['updated_at'] ?? '')),
    ];
}

kirpi_export_response($format, 'backups-' . date('Ymd-His'), [
    'ID',
    backup_lang('label'),
    backup_lang('file'),
    backup_lang('size') . ' (bytes)',
    backup_lang('size'),
    backup_lang('status'),
    backup_lang('created_by'),
    backup_lang('created_at'),
    backup_lang('updated_at'),
], $rows);
