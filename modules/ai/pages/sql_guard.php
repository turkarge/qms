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
$result = $sql !== ''
    ? kirpi_ai_sql_guard_readonly($sql, [
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
        'audit' => true,
    ])
    : null;

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
                <h2 class="page-title"><?php echo e(ai_lang('sql_guard')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(ai_lang('sql_guard_detail')); ?></div>
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
                <h3 class="card-title"><?php echo e(ai_lang('sql_guard_check')); ?></h3>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo base_url('ai/sql-guard'); ?>">
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
                                <?php echo e(ai_lang('check_sql')); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($result === null): ?>
            <div class="alert alert-info mt-3"><?php echo e(ai_lang('sql_guard_empty')); ?></div>
        <?php else: ?>
            <?php $allowed = !empty($result['allowed']); ?>
            <div class="row row-cards mt-1">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('status')); ?></div>
                            <span class="badge <?php echo $allowed ? 'bg-green-lt' : 'bg-red-lt'; ?>">
                                <?php echo e($allowed ? ai_lang('allowed') : ai_lang('blocked')); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('detected_tables')); ?></div>
                            <?php $renderBadges((array) ($result['tables'] ?? [])); ?>
                            <?php if (empty($result['tables'])): ?>
                                <span class="text-secondary">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('allowed_tables')); ?></div>
                            <?php $renderBadges((array) ($result['allowed_tables'] ?? [])); ?>
                            <?php if (empty($result['allowed_tables'])): ?>
                                <span class="text-secondary"><?php echo e(ai_lang('not_limited')); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('guard_reasons')); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($result['reasons'])): ?>
                        <div class="text-success"><?php echo e(ai_lang('sql_guard_passed')); ?></div>
                    <?php else: ?>
                        <?php $renderBadges((array) ($result['reasons'] ?? []), 'bg-red-lt'); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('detected_fields', 'Yakalanan Alanlar')); ?></h3>
                </div>
                <div class="card-body">
                    <?php $renderBadges((array) ($result['detected_fields'] ?? [])); ?>
                    <?php if (empty($result['detected_fields'])): ?>
                        <span class="text-secondary">-</span>
                    <?php endif; ?>
                    <?php if (!empty($result['blocked_fields'])): ?>
                        <div class="text-secondary small mt-3 mb-1"><?php echo e(ai_lang('blocked_fields', 'Bloklanan Alanlar')); ?></div>
                        <?php $renderBadges((array) ($result['blocked_fields'] ?? []), 'bg-red-lt'); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert alert-warning mt-3">
                <?php echo e(ai_lang('sql_guard_no_execute')); ?>
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
