<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

$question = trim((string) ($_GET['question'] ?? ($_GET['planner_question'] ?? '')));
$candidateSql = trim((string) ($_GET['candidate_sql'] ?? ($_GET['sql'] ?? '')));
$requestedModelAdapter = trim((string) ($_GET['model_adapter'] ?? 'manual'));
$confidence = max(0, min(100, (int) ($_GET['confidence'] ?? 0)));
$generateCandidate = (string) ($_GET['generate_candidate'] ?? '') === '1';
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
$candidate = null;
$adapters = kirpi_ai_sql_generation_adapters();
$adapterOptions = [
    'manual' => ai_lang('manual_candidate'),
    'mock-sql-generator' => ai_lang('mock_sql_generator'),
];
foreach ($adapters as $adapter) {
    $key = trim((string) ($adapter['adapter_key'] ?? ''));
    if ($key !== '' && $key !== 'mock-sql-generator') {
        $adapterOptions[$key] = $key . ' / ' . (string) ($adapter['model_name'] ?? '');
    }
}
$modelAdapter = array_key_exists($requestedModelAdapter, $adapterOptions)
    ? $requestedModelAdapter
    : 'manual';

if ($generateCandidate) {
    $candidate = kirpi_ai_generate_sql_candidate($question, [
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
    ], $modelAdapter !== 'manual' ? $modelAdapter : 'mock-sql-generator');
    $candidateSql = (string) ($candidate['candidate_sql'] ?? '');
    $modelAdapter = (string) ($candidate['model_adapter'] ?? $modelAdapter);
    $confidence = (int) round(((float) ($candidate['confidence'] ?? 0)) * 100);
} elseif ($candidateSql !== '') {
    $candidate = kirpi_ai_build_sql_candidate([
        'question' => $question,
        'candidate_sql' => $candidateSql,
        'model_adapter' => $modelAdapter,
        'confidence' => $confidence / 100,
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
        'audit' => true,
    ]);
}
$previewUrl = $candidate !== null
    && (string) ($candidate['status'] ?? '') !== 'blocked'
    ? base_url('ai/sql-preview?' . http_build_query([
        'planner_question' => $question,
        'allowed_tables' => implode(', ', $allowedTables),
        'allowed_fields' => $allowedFieldsInput,
        'sql' => $candidateSql,
    ]))
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
                <h2 class="page-title"><?php echo e(ai_lang('sql_candidate')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(ai_lang('sql_candidate_detail')); ?></div>
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
                <h3 class="card-title"><?php echo e(ai_lang('sql_candidate_review')); ?></h3>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo base_url('ai/sql-candidate'); ?>">
                    <input type="hidden" name="allowed_fields" value="<?php echo e($allowedFieldsInput); ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?php echo e(ai_lang('question')); ?></label>
                            <textarea name="question" rows="2" class="form-control" placeholder="<?php echo e(ai_lang('question_placeholder')); ?>"><?php echo e($question); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?php echo e(ai_lang('candidate_sql')); ?></label>
                            <textarea name="candidate_sql" rows="5" class="form-control font-monospace" placeholder="<?php echo e(ai_lang('sql_placeholder')); ?>"><?php echo e($candidateSql); ?></textarea>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label"><?php echo e(ai_lang('model_adapter')); ?></label>
                            <select name="model_adapter" class="form-select">
                                <?php foreach ($adapterOptions as $key => $label): ?>
                                    <option value="<?php echo e((string) $key); ?>" <?php echo (string) $key === $modelAdapter ? 'selected' : ''; ?>>
                                        <?php echo e((string) $label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label"><?php echo e(ai_lang('confidence')); ?></label>
                            <input type="number" min="0" max="100" name="confidence" class="form-control" value="<?php echo e((string) $confidence); ?>">
                        </div>
                        <div class="col-12 col-lg">
                            <label class="form-label"><?php echo e(ai_lang('allowed_tables')); ?></label>
                            <input type="text" name="allowed_tables" class="form-control" value="<?php echo e($allowedTablesInput); ?>" placeholder="<?php echo e(ai_lang('allowed_tables_placeholder')); ?>">
                        </div>
                        <div class="col-12 col-lg-auto d-flex align-items-end">
                            <div class="btn-list w-100">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo e(ai_lang('review_candidate')); ?>
                                </button>
                                <button type="submit" name="generate_candidate" value="1" class="btn btn-outline-secondary">
                                    <?php echo e(ai_lang('generate_candidate')); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($candidate === null): ?>
            <div class="alert alert-info mt-3"><?php echo e(ai_lang('sql_candidate_empty')); ?></div>
        <?php else: ?>
            <div class="row row-cards mt-1">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('status')); ?></div>
                            <?php $candidateStatus = (string) ($candidate['status'] ?? 'ready'); ?>
                            <span class="badge <?php echo $candidateStatus === 'blocked' ? 'bg-red-lt' : 'bg-green-lt'; ?>">
                                <?php echo e($candidateStatus); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('model_adapter')); ?></div>
                            <code><?php echo e((string) ($candidate['model_adapter'] ?? 'manual')); ?></code>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('confidence')); ?></div>
                            <div class="h2 mb-0"><?php echo (int) round(((float) ($candidate['confidence'] ?? 0)) * 100); ?>%</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('generation_mode')); ?></div>
                            <code><?php echo e((string) ($candidate['generation_mode'] ?? 'manual')); ?></code>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('candidate_warnings')); ?></h3>
                    <div class="card-actions">
                        <?php if ($previewUrl !== null && $candidateSql !== ''): ?>
                            <a href="<?php echo e($previewUrl); ?>" class="btn btn-outline-primary">
                                <?php echo e(ai_lang('open_sql_preview')); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($candidate['warnings'])): ?>
                        <div class="text-success"><?php echo e(ai_lang('no_candidate_warnings')); ?></div>
                    <?php else: ?>
                        <?php $renderBadges((array) ($candidate['warnings'] ?? []), 'bg-yellow-lt'); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row row-cards mt-1">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('prompt_hash')); ?></div>
                            <code><?php echo e((string) ($candidate['prompt_hash'] ?? '-')); ?></code>
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
                            <div class="subheader"><?php echo e(ai_lang('preview_required')); ?></div>
                            <span class="badge bg-green-lt"><?php echo e(ai_lang('yes')); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('planner_context')); ?></h3>
                </div>
                <div class="card-body">
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
