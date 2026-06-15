<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/backup/language.php';

$backupReady = kirpi_backup_table_ready();
$backups = [];
$restores = [];

if ($backupReady) {
    try {
        $backupsStmt = db()->query("
            SELECT b.id, b.label, b.file_name, b.file_size, b.status, b.created_at, u.name AS created_by_name
            FROM db_backups b
            LEFT JOIN users u ON u.id = b.created_by
            ORDER BY b.id DESC
            LIMIT 50
        ");
        $backups = $backupsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (db_table_exists('db_backup_restores')) {
            $restoresStmt = db()->query("
                SELECT r.id, r.backup_id, r.created_at, u.name AS restored_by_name
                FROM db_backup_restores r
                LEFT JOIN users u ON u.id = r.restored_by
                ORDER BY r.id DESC
                LIMIT 20
            ");
            $restores = $restoresStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        error_log('backup view page error: ' . $e->getMessage());
    }
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(backup_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(backup_lang('backup_restore')); ?></h2>
            </div>
            <?php if ($backupReady): ?>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a href="<?php echo base_url('backup/actions/export?type=backups&format=csv'); ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-file-type-csv"></i>
                            <?php echo e(backup_lang('export_backups_csv')); ?>
                        </a>
                        <a href="<?php echo base_url('backup/actions/export?type=backups&format=xls'); ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-file-spreadsheet"></i>
                            <?php echo e(backup_lang('export_backups_excel')); ?>
                        </a>
                        <a href="<?php echo base_url('backup/actions/export?type=restores&format=xls'); ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-history"></i>
                            <?php echo e(backup_lang('export_restores_excel')); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-body" data-backup-manager>
    <div class="container-xl">
        <div
            id="backup-operation-status"
            class="alert d-none align-items-center gap-2 mb-4"
            role="status"
            aria-live="polite"
            data-message-create="<?php echo e(backup_lang('working_create')); ?>"
            data-message-verify="<?php echo e(backup_lang('working_verify')); ?>"
            data-message-restore="<?php echo e(backup_lang('working_restore')); ?>"
            data-message-delete="<?php echo e(backup_lang('working_delete')); ?>"
            data-message-default="<?php echo e(backup_lang('working_default')); ?>"
            data-message-failed="<?php echo e(backup_lang('operation_failed')); ?>"
        >
            <span class="spinner-border spinner-border-sm" aria-hidden="true" data-backup-status-spinner></span>
            <span data-backup-status-text></span>
        </div>

        <?php if (!$backupReady): ?>
            <div class="alert alert-warning">
                <?php echo e(backup_lang('backup_tables_missing')); ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(backup_lang('new_backup')); ?></h3>
            </div>
            <form action="<?php echo base_url('backup/actions/create'); ?>" method="post" data-ajax="true" data-backup-operation="create">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label"><?php echo e(backup_lang('label')); ?></label>
                            <input type="text" name="label" class="form-control" placeholder="<?php echo e(backup_lang('label_placeholder')); ?>" <?php echo !$backupReady ? 'disabled' : ''; ?>>
                        </div>
                        <div class="col-12 col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100" data-backup-control <?php echo !$backupReady ? 'disabled' : ''; ?>><?php echo e(backup_lang('create_backup')); ?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(backup_lang('recent_backups')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="standard" data-table-title="Yedekler" class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo e(backup_lang('label')); ?></th>
                            <th><?php echo e(backup_lang('file')); ?></th>
                            <th><?php echo e(backup_lang('size')); ?></th>
                            <th><?php echo e(backup_lang('status')); ?></th>
                            <th><?php echo e(backup_lang('date')); ?></th>
                            <th><?php echo e(backup_lang('created_by')); ?></th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr><td colspan="8" class="text-secondary text-center py-4"><?php echo e(backup_lang('no_records')); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><?php echo (int) ($backup['id'] ?? 0); ?></td>
                                    <td><?php echo e((string) ($backup['label'] ?? '')); ?></td>
                                    <td><code><?php echo e((string) ($backup['file_name'] ?? '')); ?></code></td>
                                    <td><?php echo number_format(((int) ($backup['file_size'] ?? 0)) / 1024, 2); ?> KB</td>
                                    <td><span class="badge bg-blue-lt"><?php echo e((string) ($backup['status'] ?? '')); ?></span></td>
                                    <td><?php echo e((string) ($backup['created_at'] ?? '')); ?></td>
                                    <td><?php echo e((string) ($backup['created_by_name'] ?? '-')); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo base_url('backup/actions/download?id=' . (int) ($backup['id'] ?? 0)); ?>" class="btn btn-sm btn-outline-primary"><?php echo e(backup_lang('download')); ?></a>

                                            <form id="backup-verify-form-<?php echo (int) ($backup['id'] ?? 0); ?>" action="<?php echo base_url('backup/actions/verify'); ?>" method="post" data-ajax="true" data-backup-operation="verify" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                                <input type="hidden" name="backup_id" value="<?php echo (int) ($backup['id'] ?? 0); ?>">
                                            </form>
                                            <a href="#" class="btn btn-sm btn-outline-warning" data-backup-control data-confirm="<?php echo e(backup_lang('verify_confirm')); ?>" data-form="backup-verify-form-<?php echo (int) ($backup['id'] ?? 0); ?>"><?php echo e(backup_lang('verify')); ?></a>

                                            <form id="backup-restore-form-<?php echo (int) ($backup['id'] ?? 0); ?>" action="<?php echo base_url('backup/actions/restore'); ?>" method="post" data-ajax="true" data-backup-operation="restore" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                                <input type="hidden" name="backup_id" value="<?php echo (int) ($backup['id'] ?? 0); ?>">
                                            </form>
                                            <a href="#" class="btn btn-sm btn-outline-danger" data-backup-control data-confirm="<?php echo e(backup_lang('restore_confirm')); ?>" data-form="backup-restore-form-<?php echo (int) ($backup['id'] ?? 0); ?>"><?php echo e(backup_lang('restore')); ?></a>

                                            <form id="backup-delete-form-<?php echo (int) ($backup['id'] ?? 0); ?>" action="<?php echo base_url('backup/actions/delete'); ?>" method="post" data-ajax="true" data-backup-operation="delete" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                                <input type="hidden" name="backup_id" value="<?php echo (int) ($backup['id'] ?? 0); ?>">
                                            </form>
                                            <a href="#" class="btn btn-sm btn-outline-secondary" data-backup-control data-confirm="<?php echo e(backup_lang('delete_confirm')); ?>" data-form="backup-delete-form-<?php echo (int) ($backup['id'] ?? 0); ?>"><?php echo e(backup_lang('delete')); ?></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(backup_lang('recent_restores')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="Restore Kayıtları" class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Backup ID</th>
                            <th><?php echo e(backup_lang('restored_by')); ?></th>
                            <th><?php echo e(backup_lang('date')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($restores)): ?>
                            <tr><td colspan="4" class="text-secondary text-center py-4"><?php echo e(backup_lang('no_records')); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($restores as $restore): ?>
                                <tr>
                                    <td><?php echo (int) ($restore['id'] ?? 0); ?></td>
                                    <td><?php echo (int) ($restore['backup_id'] ?? 0); ?></td>
                                    <td><?php echo e((string) ($restore['restored_by_name'] ?? '-')); ?></td>
                                    <td><?php echo e((string) ($restore['created_at'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
