<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

$question = trim((string) ($_GET['question'] ?? ''));
$limit = max(1, min(20, (int) ($_GET['limit'] ?? 5)));
$plan = $question !== ''
    ? kirpi_ai_build_query_plan($question, ['limit' => $limit])
    : null;
$primary = is_array($plan) ? ($plan['primary_candidate'] ?? null) : null;
$candidates = is_array($plan) ? (array) ($plan['candidates'] ?? []) : [];
$allowedTables = is_array($plan) ? (array) ($plan['allowed_tables'] ?? []) : [];
$allowedFields = is_array($plan) ? (array) ($plan['allowed_fields'] ?? []) : [];
$guardUrl = base_url('ai/sql-guard?' . http_build_query([
    'planner_question' => $question,
    'allowed_tables' => implode(', ', $allowedTables),
    'allowed_fields' => json_encode($allowedFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
]));
$previewUrl = base_url('ai/sql-preview?' . http_build_query([
    'planner_question' => $question,
    'allowed_tables' => implode(', ', $allowedTables),
    'allowed_fields' => json_encode($allowedFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
]));
$candidateUrl = base_url('ai/sql-candidate?' . http_build_query([
    'question' => $question,
    'allowed_tables' => implode(', ', $allowedTables),
    'allowed_fields' => json_encode($allowedFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
]));

$renderBadges = static function (array $items): void {
    foreach ($items as $item) {
        $value = trim((string) $item);
        if ($value === '') {
            continue;
        }
        ?>
        <span class="badge bg-secondary-lt me-1 mb-1"><?php echo e($value); ?></span>
        <?php
    }
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(ai_lang('kirpi_intelligence')); ?></div>
                <h2 class="page-title"><?php echo e(ai_lang('query_planner')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(ai_lang('query_planner_detail')); ?></div>
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
                <h3 class="card-title"><?php echo e(ai_lang('query_plan')); ?></h3>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo base_url('ai/planner'); ?>">
                    <div class="row g-2">
                        <div class="col-12 col-lg">
                            <label class="form-label"><?php echo e(ai_lang('question')); ?></label>
                            <textarea name="question" rows="3" class="form-control" placeholder="<?php echo e(ai_lang('question_placeholder')); ?>"><?php echo e($question); ?></textarea>
                        </div>
                        <div class="col-12 col-sm-4 col-lg-2">
                            <label class="form-label"><?php echo e(ai_lang('limit')); ?></label>
                            <input type="number" min="1" max="20" name="limit" class="form-control" value="<?php echo e((string) $limit); ?>">
                        </div>
                        <div class="col-12 col-sm-auto d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <?php echo e(ai_lang('build_plan')); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($plan === null): ?>
            <div class="alert alert-info mt-3"><?php echo e(ai_lang('no_question')); ?></div>
        <?php else: ?>
            <div class="row row-cards mt-1">
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('candidate_count')); ?></div>
                            <div class="h1 mb-0"><?php echo (int) ($plan['candidate_count'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('search_mode')); ?></div>
                            <div class="h3 mb-0"><?php echo e((string) ($plan['search_mode'] ?? '-')); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('index_records')); ?></div>
                            <div class="h1 mb-0"><?php echo (int) ($plan['index_count'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader"><?php echo e(ai_lang('status')); ?></div>
                            <span class="badge bg-green-lt"><?php echo e(ai_lang('status_ready')); ?></span>
                            <div class="text-secondary small mt-2"><?php echo e(ai_lang('no_sql_generated')); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($candidates)): ?>
                <div class="alert alert-warning mt-3"><?php echo e(ai_lang('no_query_plan')); ?></div>
            <?php else: ?>
                <?php if (is_array($primary)): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo e(ai_lang('primary_candidate')); ?></h3>
                            <div class="card-actions">
                                <span class="badge bg-blue-lt"><?php echo e(ai_lang('score')); ?>: <?php echo (int) ($primary['score'] ?? 0); ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="text-secondary small"><?php echo e(ai_lang('module')); ?></div>
                                    <code><?php echo e((string) ($primary['module'] ?? '')); ?></code>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-secondary small"><?php echo e(ai_lang('entity')); ?></div>
                                    <?php echo e((string) ($primary['entity'] ?? '')); ?>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-secondary small"><?php echo e(ai_lang('table')); ?></div>
                                    <code><?php echo e((string) ($primary['table'] ?? '')); ?></code>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-secondary small"><?php echo e(ai_lang('permission')); ?></div>
                                    <code><?php echo e((string) ($primary['permission'] ?? '-')); ?></code>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="text-secondary small mb-1"><?php echo e(ai_lang('recommended_fields')); ?></div>
                                <?php $renderBadges((array) ($primary['recommended_fields'] ?? [])); ?>
                            </div>
                            <?php if (!empty($primary['notes'])): ?>
                                <div class="text-secondary small mt-3"><?php echo e(implode(' ', (array) $primary['notes'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card mt-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title"><?php echo e(ai_lang('guard_context')); ?></h3>
                            <div class="text-secondary small mt-1"><?php echo e(ai_lang('guard_context_detail')); ?></div>
                        </div>
                        <div class="card-actions">
                            <a href="<?php echo e($previewUrl); ?>" class="btn btn-outline-primary">
                                <?php echo e(ai_lang('open_sql_preview')); ?>
                            </a>
                            <a href="<?php echo e($candidateUrl); ?>" class="btn btn-outline-secondary">
                                <?php echo e(ai_lang('open_sql_candidate')); ?>
                            </a>
                            <a href="<?php echo e($guardUrl); ?>" class="btn btn-outline-secondary">
                                <?php echo e(ai_lang('open_sql_guard')); ?>
                            </a>
                        </div>
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

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(ai_lang('candidate_entities')); ?></h3>
                    </div>
                    <div class="table-responsive">
                        <table data-kirpi-table="report" data-table-title="Planner Aday Entityleri" class="table table-vcenter card-table table-striped mb-0">
                            <thead>
                            <tr>
                                <th><?php echo e(ai_lang('score')); ?></th>
                                <th><?php echo e(ai_lang('module')); ?></th>
                                <th><?php echo e(ai_lang('entity')); ?></th>
                                <th><?php echo e(ai_lang('table')); ?></th>
                                <th><?php echo e(ai_lang('permission')); ?></th>
                                <th><?php echo e(ai_lang('recommended_fields')); ?></th>
                                <th><?php echo e(ai_lang('matched_terms')); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($candidates as $candidate): ?>
                                <tr>
                                    <td><?php echo (int) ($candidate['score'] ?? 0); ?></td>
                                    <td><code><?php echo e((string) ($candidate['module'] ?? '')); ?></code></td>
                                    <td><?php echo e((string) ($candidate['entity'] ?? '')); ?></td>
                                    <td><code><?php echo e((string) ($candidate['table'] ?? '')); ?></code></td>
                                    <td><code><?php echo e((string) ($candidate['permission'] ?? '-')); ?></code></td>
                                    <td><?php $renderBadges((array) ($candidate['recommended_fields'] ?? [])); ?></td>
                                    <td><?php $renderBadges((array) ($candidate['matched_terms'] ?? [])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('safety_notes')); ?></h3>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ((array) ($plan['safety_notes'] ?? []) as $note): ?>
                        <div class="list-group-item"><?php echo e((string) $note); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
