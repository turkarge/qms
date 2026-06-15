<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

$sql = trim((string) ($_GET['sql'] ?? ''));
$plannerQuestion = trim((string) ($_GET['planner_question'] ?? ''));
$allowedTablesInput = trim((string) ($_GET['allowed_tables'] ?? ''));
$allowedFieldsInput = trim((string) ($_GET['allowed_fields'] ?? ''));
$allowedFields = [];
if ($allowedFieldsInput !== '') {
    $decodedFields = json_decode($allowedFieldsInput, true);
    if (is_array($decodedFields)) {
        $allowedFields = $decodedFields;
    }
}
$allowedTables = array_values(array_filter(array_map(
    static fn (string $table): string => trim($table),
    preg_split('/[\s,]+/', $allowedTablesInput) ?: []
)));
$preview = $sql !== ''
    ? kirpi_ai_preview_sql($sql, [
        'planner_question' => $plannerQuestion,
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
        'audit' => true,
    ])
    : null;
$guard = is_array($preview) ? ($preview['guard'] ?? null) : null;
$explain = is_array($preview) ? (array) ($preview['explain'] ?? []) : [];

$renderBadges = static function (array $items, string $class = 'bg-secondary-lt'): void {
    foreach ($items as $item) {
        $value = trim((string) $item);
        if ($value === '') {
            continue;
        }
        ?>
        <span class="badge <?php echo e($class); ?> me-1 mb-1"><?php echo e($value); ?></span>
        <?php
    }
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(ai_lang('kirpi_intelligence')); ?></div>
                <h2 class="page-title"><?php echo e(ai_lang('sql_preview')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(ai_lang('sql_preview_detail')); ?></div>
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
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(ai_lang('sql_preview_check')); ?></h3>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo base_url('ai/sql-preview'); ?>">
                    <input type="hidden" name="planner_question" value="<?php echo e($plannerQuestion); ?>">
                    <input type="hidden" name="allowed_fields" value="<?php echo e($allowedFieldsInput); ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?php echo e(ai_lang('sql_input')); ?></label>
                            <textarea name="sql" rows="5" class="form-control font-monospace" placeholder="<?php echo e(ai_lang('sql_placeholder')); ?>"><?php echo e($sql); ?></textarea>
                        </div>
                        <div class="col-12 col-lg">
                            <label class="form-label"><?php echo e(ai_lang('allowed_tables')); ?></label>
                            <input type="text" name="allowed_tables" class="form-control" value="<?php echo e($allowedTablesInput); ?>" placeholder="<?php echo e(ai_lang('allowed_tables_placeholder')); ?>">
                        </div>
                        <div class="col-12 col-lg-auto d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <?php echo e(ai_lang('preview_sql')); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($preview === null): ?>
            <div class="alert alert-info mt-3"><?php echo e(ai_lang('sql_preview_empty')); ?></div>
        <?php else: ?>
            <?php $guardAllowed = !empty($guard['allowed']); ?>
            <div class="row row-cards mt-1">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('decision')); ?></div>
                            <span class="badge <?php echo $guardAllowed ? 'bg-green-lt' : 'bg-red-lt'; ?>">
                                <?php echo e($guardAllowed ? ai_lang('preview_allowed') : ai_lang('blocked')); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('guard_result')); ?></div>
                            <span class="badge <?php echo $guardAllowed ? 'bg-green-lt' : 'bg-red-lt'; ?>">
                                <?php echo e($guardAllowed ? ai_lang('allowed') : ai_lang('blocked')); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('execution')); ?></div>
                            <span class="badge bg-red-lt"><?php echo e(ai_lang('disabled')); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('explain')); ?></div>
                            <span class="badge <?php echo (($explain['status'] ?? '') === 'success') ? 'bg-green-lt' : 'bg-red-lt'; ?>">
                                <?php echo e((string) (($explain['status'] ?? '') === 'success' ? ai_lang('allowed') : ai_lang('disabled'))); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('guard_reasons')); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($guard['reasons'])): ?>
                        <div class="text-success"><?php echo e(ai_lang('sql_guard_passed')); ?></div>
                    <?php else: ?>
                        <?php $renderBadges((array) ($guard['reasons'] ?? []), 'bg-red-lt'); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('preview_notes')); ?></h3>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ((array) ($preview['notes'] ?? []) as $note): ?>
                        <div class="list-group-item"><?php echo e((string) $note); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('explain_gate')); ?></h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="text-secondary small"><?php echo e(ai_lang('status')); ?></div>
                            <span class="badge <?php echo (($explain['status'] ?? '') === 'success') ? 'bg-green-lt' : 'bg-red-lt'; ?>">
                                <?php echo e((string) ($explain['status'] ?? 'blocked')); ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary small"><?php echo e(ai_lang('explain')); ?></div>
                            <?php echo !empty($explain['enabled']) ? e(ai_lang('enabled')) : e(ai_lang('disabled')); ?>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary small"><?php echo e(ai_lang('reason')); ?></div>
                            <code><?php echo e((string) ($explain['reason'] ?? '-')); ?></code>
                        </div>
                        <div class="col-md-3">
                            <div class="text-secondary small"><?php echo e(ai_lang('data_read')); ?></div>
                            <span class="badge bg-green-lt"><?php echo e(ai_lang('no')); ?></span>
                        </div>
                    </div>
                    <?php if (!empty($explain['rows'])): ?>
                        <div class="table-responsive mt-3">
                            <table data-kirpi-table="compact" data-table-title="EXPLAIN Önizleme" class="table table-vcenter card-table table-striped mb-0">
                                <thead>
                                <tr>
                                    <?php foreach (array_keys((array) ($explain['rows'][0] ?? [])) as $column): ?>
                                        <th><?php echo e((string) $column); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ((array) $explain['rows'] as $row): ?>
                                    <tr>
                                        <?php foreach ((array) $row as $value): ?>
                                            <td><?php echo e((string) $value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($plannerQuestion !== '' || !empty($allowedTables) || !empty($allowedFields)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('planner_context')); ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($plannerQuestion !== ''): ?>
                        <div class="mb-3">
                            <div class="text-secondary small"><?php echo e(ai_lang('question')); ?></div>
                            <?php echo e($plannerQuestion); ?>
                        </div>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="text-secondary small mb-1"><?php echo e(ai_lang('allowed_tables')); ?></div>
                            <?php $renderBadges($allowedTables); ?>
                            <?php if (empty($allowedTables)): ?>
                                <span class="text-secondary">-</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-8">
                            <div class="text-secondary small mb-1"><?php echo e(ai_lang('allowed_fields')); ?></div>
                            <?php if (empty($allowedFields)): ?>
                                <span class="text-secondary">-</span>
                            <?php else: ?>
                                <?php foreach ($allowedFields as $table => $fields): ?>
                                    <div class="mb-2">
                                        <code><?php echo e((string) $table); ?></code>
                                        <span class="text-secondary">:</span>
                                        <?php $renderBadges((array) $fields); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
