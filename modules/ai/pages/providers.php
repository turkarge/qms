<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

$modelsReady = kirpi_ai_models_table_ready();
$adapters = $modelsReady ? kirpi_ai_model_adapters_with_config() : [];
$runtimeEnabled = env_bool('AI_EXTERNAL_MODEL_RUNTIME_ENABLED', false);

$providerOptions = [
    'mock' => 'Mock',
    'openai' => 'OpenAI',
    'openai_compatible' => 'OpenAI Compatible',
];
$adapterTypeOptions = [
    'chat' => 'Chat',
    'sql_generation' => 'SQL Generation',
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(ai_lang('kirpi_intelligence')); ?></div>
                <h2 class="page-title"><?php echo e(ai_lang('provider_settings', 'Provider Ayarları')); ?></h2>
                <div class="text-secondary mt-1">
                    <?php echo e(ai_lang('provider_settings_detail', 'Model provider ve adapter ayarları arayüzden yönetilir; global runtime kapısı env içinde kalır.')); ?>
                </div>
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
        <?php if (!$modelsReady): ?>
            <div class="alert alert-warning"><?php echo e(ai_lang('adapter_missing')); ?></div>
        <?php endif; ?>

        <div class="alert <?php echo $runtimeEnabled ? 'alert-success' : 'alert-warning'; ?>">
            <div class="d-flex">
                <div class="me-2">
                    <i class="ti <?php echo $runtimeEnabled ? 'ti-shield-check' : 'ti-shield-lock'; ?>"></i>
                </div>
                <div>
                    <strong><?php echo e(ai_lang('global_runtime_gate', 'Global Runtime Kapısı')); ?>:</strong>
                    <code>AI_EXTERNAL_MODEL_RUNTIME_ENABLED=<?php echo $runtimeEnabled ? 'true' : 'false'; ?></code>
                    <div class="small mt-1">
                        <?php echo e(ai_lang('global_runtime_gate_hint', 'Bu kritik kapı yalnız env üzerinden açılır. Adapter runtime onayı arayüzden açılsa bile bu değer false ise gerçek provider çağrısı yapılmaz.')); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards">
            <?php foreach ($adapters as $adapter): ?>
                <?php
                $adapterKey = (string) ($adapter['adapter_key'] ?? '');
                $provider = (string) ($adapter['provider'] ?? '');
                $config = (array) ($adapter['config'] ?? []);
                $isMock = $provider === 'mock';
                $apiKeyRef = (string) ($config['api_key_ref'] ?? kirpi_ai_provider_secret_key($provider));
                $apiKeyEnv = (string) ($config['api_key_env'] ?? '');
                $secretSource = $apiKeyEnv !== '' ? 'env' : 'setting';
                $baseUrl = (string) ($config['base_url'] ?? '');
                $runtimeAdapterEnabled = filter_var($config['runtime_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $structuredOutputEnabled = !array_key_exists('structured_output', $config) || filter_var($config['structured_output'], FILTER_VALIDATE_BOOLEAN);
                ?>
                <div class="col-12">
                    <form action="<?php echo base_url('ai/actions/provider-update'); ?>" method="post" data-ajax="true" class="card">
                        <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                        <input type="hidden" name="adapter_key" value="<?php echo e($adapterKey); ?>">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title mb-1"><?php echo e($adapterKey); ?></h3>
                                <div class="text-secondary small">
                                    <?php echo e(ai_lang('updated_at')); ?>:
                                    <?php echo e((string) ($adapter['updated_at'] ?? '-')); ?>
                                </div>
                            </div>
                            <div class="card-actions">
                                <?php if (!$isMock): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary js-ai-provider-test"
                                        data-url="<?php echo e(base_url('ai/actions/provider-test')); ?>"
                                        data-adapter-key="<?php echo e($adapterKey); ?>"
                                    >
                                        <i class="ti ti-plug-connected"></i>
                                        <?php echo e(ai_lang('test_provider', 'Bağlantıyı Test Et')); ?>
                                    </button>
                                <?php endif; ?>
                                <span class="badge <?php echo !empty($adapter['is_enabled']) ? 'bg-green-lt' : 'bg-red-lt'; ?>">
                                    <?php echo e(!empty($adapter['is_enabled']) ? ai_lang('enabled') : ai_lang('disabled')); ?>
                                </span>
                                <span class="badge <?php echo !empty($adapter['secret_configured']) || $isMock ? 'bg-green-lt' : 'bg-yellow-lt'; ?>">
                                    <?php echo e(!empty($adapter['secret_configured']) || $isMock ? ai_lang('secret_ready', 'Secret hazır') : ai_lang('secret_missing', 'Secret eksik')); ?>
                                </span>
                                <span class="badge <?php echo (string) ($adapter['adapter_type'] ?? '') === 'sql_generation' && !empty($adapter['is_enabled']) ? 'bg-green-lt' : 'bg-secondary-lt'; ?>">
                                    <?php echo e((string) ($adapter['adapter_type'] ?? '') === 'sql_generation' && !empty($adapter['is_enabled']) ? ai_lang('query_flow_visible', 'Query Flow görünür') : ai_lang('query_flow_hidden', 'Query Flow gizli')); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label"><?php echo e(ai_lang('provider', 'Provider')); ?></label>
                                    <select name="provider" class="form-select" <?php echo $isMock ? 'disabled' : ''; ?>>
                                        <?php foreach ($providerOptions as $value => $label): ?>
                                            <option value="<?php echo e($value); ?>" <?php echo $value === $provider ? 'selected' : ''; ?>>
                                                <?php echo e($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label"><?php echo e(ai_lang('model_name', 'Model Adı')); ?></label>
                                    <input name="model_name" type="text" class="form-control" value="<?php echo e((string) ($config['model'] ?? $adapter['model_name'] ?? '')); ?>" <?php echo $isMock ? 'disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label"><?php echo e(ai_lang('adapter_type', 'Adapter Tipi')); ?></label>
                                    <select name="adapter_type" class="form-select" <?php echo $isMock ? 'disabled' : ''; ?>>
                                        <?php foreach ($adapterTypeOptions as $value => $label): ?>
                                            <option value="<?php echo e($value); ?>" <?php echo $value === (string) ($adapter['adapter_type'] ?? '') ? 'selected' : ''; ?>>
                                                <?php echo e($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label"><?php echo e(ai_lang('base_url', 'Base URL')); ?></label>
                                    <input name="base_url" type="url" class="form-control" value="<?php echo e($baseUrl); ?>" placeholder="https://api.openai.com/v1" <?php echo $isMock ? 'disabled' : ''; ?>>
                                </div>

                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label"><?php echo e(ai_lang('secret_source', 'Secret Kaynağı')); ?></label>
                                    <select name="secret_source" class="form-select" <?php echo $isMock ? 'disabled' : ''; ?>>
                                        <option value="setting" <?php echo $secretSource === 'setting' ? 'selected' : ''; ?>><?php echo e(ai_lang('secret_source_setting', 'Ayarlar tablosu')); ?></option>
                                        <option value="env" <?php echo $secretSource === 'env' ? 'selected' : ''; ?>><?php echo e(ai_lang('secret_source_env', 'Env değişkeni')); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label"><?php echo e(ai_lang('api_key_ref', 'API Key Setting Ref')); ?></label>
                                    <input name="api_key_ref" type="text" class="form-control" value="<?php echo e($apiKeyRef); ?>" <?php echo $isMock ? 'disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label"><?php echo e(ai_lang('api_key_env', 'API Key Env')); ?></label>
                                    <input name="api_key_env" type="text" class="form-control" value="<?php echo e($apiKeyEnv); ?>" placeholder="OPENAI_API_KEY" <?php echo $isMock ? 'disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label"><?php echo e(ai_lang('api_key_value', 'Yeni API Key')); ?></label>
                                    <input name="api_key_value" type="password" class="form-control" value="" autocomplete="new-password" placeholder="<?php echo e(ai_lang('secret_keep_placeholder', 'Boş bırakılırsa değişmez')); ?>" <?php echo $isMock ? 'disabled' : ''; ?>>
                                </div>

                                <div class="col-md-4 col-lg-2">
                                    <label class="form-label"><?php echo e(ai_lang('timeout_seconds', 'Timeout')); ?></label>
                                    <input name="timeout_seconds" type="number" min="5" max="120" class="form-control" value="<?php echo e((string) ($config['timeout_seconds'] ?? 30)); ?>">
                                </div>
                                <div class="col-md-4 col-lg-2">
                                    <label class="form-label"><?php echo e(ai_lang('temperature', 'Temperature')); ?></label>
                                    <input name="temperature" type="number" min="0" max="1" step="0.1" class="form-control" value="<?php echo e((string) ($config['temperature'] ?? 0)); ?>">
                                </div>
                                <div class="col-md-4 col-lg-2">
                                    <label class="form-label"><?php echo e(ai_lang('max_tokens', 'Max Tokens')); ?></label>
                                    <input name="max_tokens" type="number" min="128" max="4096" class="form-control" value="<?php echo e((string) ($config['max_tokens'] ?? 700)); ?>">
                                </div>
                                <div class="col-md-6 col-lg-3 d-flex align-items-end">
                                    <label class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1" <?php echo !empty($adapter['is_enabled']) ? 'checked' : ''; ?> <?php echo $isMock ? 'disabled' : ''; ?>>
                                        <span class="form-check-label"><?php echo e(ai_lang('adapter_enabled', 'Adapter aktif')); ?></span>
                                    </label>
                                </div>
                                <div class="col-md-6 col-lg-3 d-flex align-items-end">
                                    <label class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="structured_output" value="1" <?php echo $structuredOutputEnabled ? 'checked' : ''; ?> <?php echo $isMock ? 'disabled' : ''; ?>>
                                        <span class="form-check-label"><?php echo e(ai_lang('structured_output', 'Yapılandırılmış JSON çıktı')); ?></span>
                                    </label>
                                </div>
                                <div class="col-md-6 col-lg-3 d-flex align-items-end">
                                    <label class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="runtime_enabled" value="1" <?php echo $runtimeAdapterEnabled ? 'checked' : ''; ?> <?php echo $isMock ? 'disabled' : ''; ?>>
                                        <span class="form-check-label"><?php echo e(ai_lang('adapter_runtime_enabled', 'Runtime onayı')); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                <?php echo e(ai_lang('save_settings', 'Ayarları Kaydet')); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
