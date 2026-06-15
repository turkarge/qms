<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

$question = trim((string) ($_GET['question'] ?? ''));
$limit = max(1, min(20, (int) ($_GET['limit'] ?? 5)));
$requestedModelAdapter = trim((string) ($_GET['model_adapter'] ?? 'mock-sql-generator'));
$generateCandidate = (string) ($_GET['generate_candidate'] ?? '') === '1';
$plan = $question !== '' ? kirpi_ai_build_query_plan($question, ['limit' => $limit]) : null;
$allowedTables = is_array($plan) ? (array) ($plan['allowed_tables'] ?? []) : [];
$allowedFields = is_array($plan) ? (array) ($plan['allowed_fields'] ?? []) : [];
$candidate = null;
$preview = null;
$guard = null;
$explain = [];

$hiddenActiveAdapters = [];
foreach (kirpi_ai_model_adapters_with_config() as $adapter) {
    if (!empty($adapter['is_enabled']) && (string) ($adapter['adapter_type'] ?? '') !== 'sql_generation') {
        $hiddenActiveAdapters[] = $adapter;
    }
}

$adapters = kirpi_ai_sql_generation_adapters();
$adapterOptions = [
    'mock-sql-generator' => ai_lang('mock_sql_generator'),
];
foreach ($adapters as $adapter) {
    $key = trim((string) ($adapter['adapter_key'] ?? ''));
    if ($key !== '' && $key !== 'mock-sql-generator') {
        $labelParts = [
            (string) ($adapter['provider'] ?? 'provider'),
            (string) ($adapter['model_name'] ?? ''),
            '(' . $key . ')',
        ];
        $statusParts = [];
        if (empty($adapter['secret_configured'])) {
            $statusParts[] = ai_lang('secret_missing', 'Secret eksik');
        }
        if (!kirpi_ai_adapter_runtime_enabled($adapter)) {
            $statusParts[] = ai_lang('runtime_disabled', 'Runtime kapalı');
        }
        $adapterOptions[$key] = implode(' / ', array_filter($labelParts));
        if (!empty($statusParts)) {
            $adapterOptions[$key] .= ' - ' . implode(', ', $statusParts);
        }
    }
}
$modelAdapter = array_key_exists($requestedModelAdapter, $adapterOptions)
    ? $requestedModelAdapter
    : (string) array_key_first($adapterOptions);
$selectedAdapter = null;
foreach ($adapters as $adapter) {
    if ((string) ($adapter['adapter_key'] ?? '') === $modelAdapter) {
        $selectedAdapter = $adapter;
        break;
    }
}

if ($generateCandidate && $question !== '' && !empty($allowedTables)) {
    $candidate = kirpi_ai_generate_sql_candidate($question, [
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
    ], $modelAdapter);

    $candidateSql = trim((string) ($candidate['candidate_sql'] ?? ''));
    if ($candidateSql !== '' && (string) ($candidate['status'] ?? '') !== 'blocked') {
        $preview = kirpi_ai_preview_sql($candidateSql, [
            'planner_question' => $question,
            'allowed_tables' => $allowedTables,
            'allowed_fields' => $allowedFields,
            'audit' => true,
        ]);
        $guard = is_array($preview) ? ($preview['guard'] ?? null) : null;
        $explain = is_array($preview) ? (array) ($preview['explain'] ?? []) : [];
    }
}

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

$stepBadge = static function (bool $done): string {
    return $done ? 'bg-green-lt' : 'bg-secondary-lt';
};

$sanitizeAdapter = static function (array $adapter): array {
    $config = (array) ($adapter['config'] ?? []);
    $safeConfig = [];
    foreach ($config as $key => $value) {
        $key = (string) $key;
        if (preg_match('/(secret|token|password|api[_-]?key)$/i', $key) === 1 && !in_array($key, ['api_key_ref', 'api_key_env'], true)) {
            $safeConfig[$key] = '[masked]';
            continue;
        }
        $safeConfig[$key] = is_scalar($value) || $value === null ? $value : '[non-scalar]';
    }

    return [
        'adapter_key' => (string) ($adapter['adapter_key'] ?? ''),
        'provider' => (string) ($adapter['provider'] ?? ''),
        'model_name' => (string) ($adapter['model_name'] ?? ''),
        'adapter_type' => (string) ($adapter['adapter_type'] ?? ''),
        'is_enabled' => !empty($adapter['is_enabled']),
        'is_external' => !empty($adapter['is_external']),
        'secret_configured' => !empty($adapter['secret_configured']),
        'runtime_enabled' => kirpi_ai_adapter_runtime_enabled($adapter),
        'query_flow_visible' => (string) ($adapter['adapter_type'] ?? '') === 'sql_generation' && !empty($adapter['is_enabled']),
        'config' => $safeConfig,
        'updated_at' => (string) ($adapter['updated_at'] ?? ''),
    ];
};

$allAdaptersForDebug = kirpi_ai_model_adapters_with_config();
$debugPayload = [
    'generated_at' => date('c'),
    'page' => 'ai/query-flow',
    'request' => [
        'question' => $question,
        'limit' => $limit,
        'requested_model_adapter' => $requestedModelAdapter,
        'resolved_model_adapter' => $modelAdapter,
        'generate_candidate' => $generateCandidate,
    ],
    'runtime' => [
        'global_runtime_enabled' => env_bool('AI_EXTERNAL_MODEL_RUNTIME_ENABLED', false),
        'sql_explain_enabled' => env_bool('AI_SQL_EXPLAIN_ENABLED', false),
    ],
    'adapter_selection' => [
        'options' => $adapterOptions,
        'selected_adapter' => is_array($selectedAdapter) ? $sanitizeAdapter($selectedAdapter) : null,
        'hidden_active_adapters' => array_map($sanitizeAdapter, $hiddenActiveAdapters),
        'all_adapters' => array_map($sanitizeAdapter, $allAdaptersForDebug),
    ],
    'planner' => [
        'status' => is_array($plan) ? 'ready' : 'missing',
        'allowed_tables' => $allowedTables,
        'allowed_fields' => $allowedFields,
        'plan' => $plan,
    ],
    'candidate' => $candidate,
    'preview' => $preview,
    'guard' => $guard,
    'explain' => $explain,
    'notes' => [
        'secrets_are_masked_or_omitted',
        'secret_configured_is_boolean_only',
        'api_key_ref_and_api_key_env_are_references_not_secret_values',
    ],
];
$debugJson = json_encode($debugPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '{}';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(ai_lang('kirpi_intelligence')); ?></div>
                <h2 class="page-title"><?php echo e(ai_lang('query_flow')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(ai_lang('query_flow_detail')); ?></div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-primary js-ai-debug-copy" data-debug-target="ai-query-flow-debug-json">
                        <i class="ti ti-copy"></i>
                        <?php echo e(ai_lang('copy_debug_json', 'Debug JSON Kopyala')); ?>
                    </button>
                    <a href="<?php echo base_url('ai/view'); ?>" class="btn btn-outline-secondary">
                        <?php echo e(ai_lang('back_to_ai')); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="ai-query-flow-debug-json"><?php echo $debugJson; ?></script>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(ai_lang('query_flow')); ?></h3>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo base_url('ai/query-flow'); ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?php echo e(ai_lang('question')); ?></label>
                            <textarea name="question" rows="3" class="form-control" placeholder="<?php echo e(ai_lang('question_placeholder')); ?>"><?php echo e($question); ?></textarea>
                        </div>
                        <div class="col-md-6 col-lg-3">
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
                            <label class="form-label"><?php echo e(ai_lang('limit')); ?></label>
                            <input type="number" min="1" max="20" name="limit" class="form-control" value="<?php echo e((string) $limit); ?>">
                        </div>
                        <div class="col-12 col-lg-auto d-flex align-items-end">
                            <div class="btn-list">
                                <button type="submit" class="btn btn-primary"><?php echo e(ai_lang('build_plan')); ?></button>
                                <button type="submit" name="generate_candidate" value="1" class="btn btn-outline-primary">
                                    <?php echo e(ai_lang('run_query_flow')); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php if (is_array($selectedAdapter) && (empty($selectedAdapter['secret_configured']) || !kirpi_ai_adapter_runtime_enabled($selectedAdapter))): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <div class="fw-semibold"><?php echo e(ai_lang('adapter_not_ready', 'Seçili adapter canlı üretim için hazır değil.')); ?></div>
                        <div class="small">
                            <?php if (empty($selectedAdapter['secret_configured'])): ?>
                                <?php echo e(ai_lang('adapter_secret_missing_hint', 'Provider Ayarları ekranında bu adapter için API key kaynağını yapılandırın.')); ?>
                            <?php endif; ?>
                            <?php if (!kirpi_ai_adapter_runtime_enabled($selectedAdapter)): ?>
                                <?php echo e(ai_lang('adapter_runtime_missing_hint', 'Global runtime kapısı ve adapter runtime onayı açık olmalıdır.')); ?>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo base_url('ai/providers'); ?>" class="btn btn-sm btn-outline-primary mt-2">
                            <?php echo e(ai_lang('provider_settings', 'Provider Ayarları')); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if (!empty($hiddenActiveAdapters)): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <div class="fw-semibold"><?php echo e(ai_lang('query_flow_hidden_adapters', 'Bazı aktif adapterlar Query Flow listesinde gösterilmiyor.')); ?></div>
                        <div class="small mb-2">
                            <?php echo e(ai_lang('query_flow_hidden_adapters_hint', 'Query Flow yalnız Adapter Tipi SQL Generation olan adapterları listeler. Chat adapterlar bağlantı testi için kullanılabilir, SQL üretim akışında kullanılmaz.')); ?>
                        </div>
                        <div class="mb-2">
                            <?php foreach ($hiddenActiveAdapters as $adapter): ?>
                                <span class="badge bg-blue-lt me-1 mb-1">
                                    <?php echo e((string) ($adapter['adapter_key'] ?? '')); ?>
                                    /
                                    <?php echo e((string) ($adapter['adapter_type'] ?? '')); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo base_url('ai/providers'); ?>" class="btn btn-sm btn-outline-primary">
                            <?php echo e(ai_lang('provider_settings', 'Provider Ayarları')); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row row-cards mt-1">
            <div class="col-md">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">1. <?php echo e(ai_lang('query_planner')); ?></div>
                        <span class="badge <?php echo $stepBadge(is_array($plan)); ?>"><?php echo e(is_array($plan) ? ai_lang('status_ready') : ai_lang('status_missing')); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">2. <?php echo e(ai_lang('guard_context')); ?></div>
                        <span class="badge <?php echo $stepBadge(!empty($allowedTables)); ?>"><?php echo (int) count($allowedTables); ?> <?php echo e(ai_lang('allowed_tables')); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">3. <?php echo e(ai_lang('sql_candidate')); ?></div>
                        <span class="badge <?php echo $stepBadge(is_array($candidate)); ?>"><?php echo e((string) ($candidate['status'] ?? ai_lang('status_missing'))); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">4. <?php echo e(ai_lang('sql_preview')); ?></div>
                        <span class="badge <?php echo $stepBadge(is_array($preview)); ?>"><?php echo e((string) ($preview['decision'] ?? ai_lang('status_missing'))); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">5. <?php echo e(ai_lang('explain_gate')); ?></div>
                        <span class="badge <?php echo (($explain['status'] ?? '') === 'success') ? 'bg-green-lt' : 'bg-red-lt'; ?>">
                            <?php echo e((string) ($explain['reason'] ?? ($explain['status'] ?? ai_lang('status_missing')))); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($plan === null): ?>
            <div class="alert alert-info mt-3"><?php echo e(ai_lang('no_question')); ?></div>
        <?php else: ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('guard_context')); ?></h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="text-secondary small mb-1"><?php echo e(ai_lang('allowed_tables')); ?></div>
                            <?php $renderBadges($allowedTables); ?>
                            <?php if (empty($allowedTables)): ?><span class="text-secondary">-</span><?php endif; ?>
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

            <?php if (is_array($candidate)): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(ai_lang('sql_candidate')); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-secondary small"><?php echo e(ai_lang('model_adapter')); ?></div>
                                <code><?php echo e((string) ($candidate['model_adapter'] ?? '-')); ?></code>
                            </div>
                            <div class="col-md-3">
                                <div class="text-secondary small"><?php echo e(ai_lang('generation_mode')); ?></div>
                                <code><?php echo e((string) ($candidate['generation_mode'] ?? '-')); ?></code>
                            </div>
                            <div class="col-md-3">
                                <div class="text-secondary small"><?php echo e(ai_lang('confidence')); ?></div>
                                <?php echo (int) round(((float) ($candidate['confidence'] ?? 0)) * 100); ?>%
                            </div>
                            <div class="col-md-3">
                                <div class="text-secondary small"><?php echo e(ai_lang('execution')); ?></div>
                                <span class="badge bg-red-lt"><?php echo e(ai_lang('disabled')); ?></span>
                            </div>
                            <div class="col-12">
                                <div class="text-secondary small mb-1"><?php echo e(ai_lang('candidate_sql')); ?></div>
                                <pre class="kirpi-code-block p-3 rounded"><code><?php echo e((string) ($candidate['candidate_sql'] ?? '')); ?></code></pre>
                            </div>
                            <?php if (!empty($candidate['warnings'])): ?>
                                <div class="col-12">
                                    <div class="text-secondary small mb-1"><?php echo e(ai_lang('candidate_warnings')); ?></div>
                                    <?php $renderBadges((array) ($candidate['warnings'] ?? []), 'bg-yellow-lt'); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($candidate['guard']['blocked_fields'])): ?>
                                <div class="col-12">
                                    <div class="text-secondary small mb-1"><?php echo e(ai_lang('blocked_fields', 'Bloklanan Alanlar')); ?></div>
                                    <?php $renderBadges((array) ($candidate['guard']['blocked_fields'] ?? []), 'bg-red-lt'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (is_array($preview)): ?>
                <?php $guardAllowed = !empty($guard['allowed']); ?>
                <div class="row row-cards mt-1">
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
                                <code><?php echo e((string) ($explain['reason'] ?? ($explain['status'] ?? '-'))); ?></code>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="subheader"><?php echo e(ai_lang('data_read')); ?></div>
                                <span class="badge bg-green-lt"><?php echo e(ai_lang('no')); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(ai_lang('audit_chain')); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php $renderBadges(['query_plan_preview', 'sql_candidate_generate', 'sql_preview_check'], 'bg-blue-lt'); ?>
                        <?php if (!empty($explain['enabled'])): ?>
                            <?php $renderBadges(['sql_explain_check'], 'bg-blue-lt'); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
