<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

$auditReady = kirpi_ai_audit_table_ready();
$page = max(1, (int) ($_GET['page'] ?? 1));
$filters = [
    'status' => trim((string) ($_GET['status'] ?? '')),
    'action' => trim((string) ($_GET['action'] ?? '')),
    'model_adapter' => trim((string) ($_GET['model_adapter'] ?? '')),
    'entity_type' => trim((string) ($_GET['entity_type'] ?? '')),
    'user_id' => (int) ($_GET['user_id'] ?? 0),
];
$auditList = kirpi_ai_list_audit_logs($filters, $page, 25);
$records = (array) ($auditList['records'] ?? []);
$totalPages = (int) ($auditList['total_pages'] ?? 0);

$pageUrl = static function (int $targetPage) use ($filters): string {
    $query = array_filter([
        'page' => $targetPage,
        'status' => $filters['status'] ?? '',
        'action' => $filters['action'] ?? '',
        'model_adapter' => $filters['model_adapter'] ?? '',
        'entity_type' => $filters['entity_type'] ?? '',
        'user_id' => ((int) ($filters['user_id'] ?? 0)) > 0 ? (int) $filters['user_id'] : null,
    ], static fn ($value): bool => $value !== '' && $value !== null);

    return base_url('ai/audit') . ($query ? ('?' . http_build_query($query)) : '');
};

$formatDetails = static function (?string $json): string {
    $json = trim((string) $json);
    if ($json === '') {
        return '-';
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return $json;
    }

    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $json;
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(ai_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(ai_lang('ai_audit_log')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(ai_lang('ai_audit_log_detail')); ?></div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="<?php echo base_url('ai/view'); ?>" class="btn btn-outline-secondary">
                    <?php echo e(ai_lang('back_to_ai')); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!$auditReady): ?>
            <div class="alert alert-warning">
                <?php echo e(ai_lang('audit_missing')); ?>
            </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(ai_lang('filters')); ?></h3>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo base_url('ai/audit'); ?>">
                    <div class="row g-2">
                        <div class="col-12 col-md-2">
                            <label class="form-label"><?php echo e(ai_lang('status')); ?></label>
                            <select name="status" class="form-select" <?php echo !$auditReady ? 'disabled' : ''; ?>>
                                <option value=""><?php echo e(ai_lang('all')); ?></option>
                                <?php foreach (['success', 'failed', 'blocked'] as $status): ?>
                                    <option value="<?php echo e($status); ?>" <?php echo ($filters['status'] ?? '') === $status ? 'selected' : ''; ?>>
                                        <?php echo e($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label"><?php echo e(ai_lang('action')); ?></label>
                            <input name="action" type="text" class="form-control" value="<?php echo e((string) ($filters['action'] ?? '')); ?>" <?php echo !$auditReady ? 'disabled' : ''; ?>>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label"><?php echo e(ai_lang('model_adapter')); ?></label>
                            <input name="model_adapter" type="text" class="form-control" value="<?php echo e((string) ($filters['model_adapter'] ?? '')); ?>" <?php echo !$auditReady ? 'disabled' : ''; ?>>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label"><?php echo e(ai_lang('entity_type')); ?></label>
                            <input name="entity_type" type="text" class="form-control" value="<?php echo e((string) ($filters['entity_type'] ?? '')); ?>" <?php echo !$auditReady ? 'disabled' : ''; ?>>
                        </div>
                        <div class="col-12 col-md-1">
                            <label class="form-label"><?php echo e(ai_lang('user_id')); ?></label>
                            <input name="user_id" type="number" min="1" class="form-control" value="<?php echo (int) ($filters['user_id'] ?? 0) ?: ''; ?>" <?php echo !$auditReady ? 'disabled' : ''; ?>>
                        </div>
                        <div class="col-12 col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100" <?php echo !$auditReady ? 'disabled' : ''; ?>>
                                <?php echo e(ai_lang('filter')); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(ai_lang('records')); ?></h3>
                <div class="card-actions text-secondary">
                    <?php echo e(ai_lang('total')); ?>:
                    <strong><?php echo (int) ($auditList['total'] ?? 0); ?></strong>
                </div>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="AI Audit Log" class="table table-vcenter card-table table-striped">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo e(ai_lang('date')); ?></th>
                        <th><?php echo e(ai_lang('user')); ?></th>
                        <th><?php echo e(ai_lang('action')); ?></th>
                        <th><?php echo e(ai_lang('status')); ?></th>
                        <th><?php echo e(ai_lang('model_adapter')); ?></th>
                        <th><?php echo e(ai_lang('entity')); ?></th>
                        <th><?php echo e(ai_lang('route')); ?></th>
                        <th><?php echo e(ai_lang('detail')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-4">
                                <?php echo e(ai_lang('no_audit_records')); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <?php
                            $status = (string) ($record['status'] ?? '');
                            $userName = trim((string) ($record['user_name'] ?? ''));
                            $userId = (int) ($record['user_id'] ?? 0);
                            ?>
                            <tr>
                                <td><?php echo (int) ($record['id'] ?? 0); ?></td>
                                <td><?php echo e((string) ($record['created_at'] ?? '')); ?></td>
                                <td><?php echo e($userName !== '' ? ($userName . ' (#' . $userId . ')') : '-'); ?></td>
                                <td><code><?php echo e((string) ($record['action_key'] ?? '')); ?></code></td>
                                <td>
                                    <?php if ($status === 'success'): ?>
                                        <span class="badge bg-green-lt">success</span>
                                    <?php elseif ($status === 'blocked'): ?>
                                        <span class="badge bg-yellow-lt">blocked</span>
                                    <?php else: ?>
                                        <span class="badge bg-red-lt"><?php echo e($status !== '' ? $status : 'failed'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo e((string) ($record['model_adapter'] ?? '')); ?></code></td>
                                <td>
                                    <div><code><?php echo e((string) ($record['entity_type'] ?? '')); ?></code></div>
                                    <div class="small text-secondary">#<?php echo (int) ($record['entity_id'] ?? 0); ?></div>
                                </td>
                                <td>
                                    <div><code><?php echo e((string) ($record['route_path'] ?? '')); ?></code></div>
                                    <div class="small text-secondary"><?php echo e((string) ($record['ip_address'] ?? '')); ?></div>
                                </td>
                                <td>
                                    <details>
                                        <summary><?php echo e(ai_lang('view')); ?></summary>
                                        <pre class="mb-0"><?php echo e($formatDetails($record['details_json'] ?? null)); ?></pre>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="card-footer d-flex align-items-center">
                    <ul class="pagination m-0 ms-auto">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo e($pageUrl(max(1, $page - 1))); ?>"><?php echo e(ai_lang('previous')); ?></a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo e($pageUrl($i)); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo e($pageUrl(min($totalPages, $page + 1))); ?>"><?php echo e(ai_lang('next')); ?></a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
