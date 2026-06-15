<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/template/language.php';

$path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$kind = 'email';
if (str_ends_with($path, 'template/print')) {
    $kind = 'print';
} elseif (str_ends_with($path, 'template/content')) {
    $kind = 'content';
}

$titles = [
    'email' => template_lang('email_templates'),
    'print' => template_lang('print_templates'),
    'content' => template_lang('content_templates'),
];
$templates = [];
$tableReady = kirpi_templates_table_ready();
$modules = kirpi_template_supported_modules();
$targets = kirpi_template_supported_targets($kind);
$search = trim((string) ($_GET['search'] ?? ''));
$moduleFilter = trim((string) ($_GET['module_key'] ?? ''));
$codeFilter = trim((string) ($_GET['code'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));

if ($tableReady) {
    if ($kind === 'email' && function_exists('kirpi_mail_default_templates')) {
        kirpi_template_sync_mail_defaults(kirpi_mail_default_templates());
    }
    if ($kind === 'content') {
        kirpi_template_sync_notification_defaults();
    }

    try {
        $where = ['kind = :kind'];
        $params = [
            ':kind' => $kind,
        ];

        if ($search !== '') {
            $where[] = '(name LIKE :search OR subject LIKE :search OR body LIKE :search OR target_key LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($moduleFilter !== '') {
            $where[] = 'module_key = :module_key';
            $params[':module_key'] = $moduleFilter;
        }

        if ($codeFilter !== '') {
            $where[] = 'code LIKE :code';
            $params[':code'] = '%' . $codeFilter . '%';
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['0', '1'], true)) {
            $where[] = 'is_active = :is_active';
            $params[':is_active'] = (int) $statusFilter;
        }

        $stmt = db()->prepare("
            SELECT id, kind, module_key, target_key, code, name, language, subject, body, variables_json, is_system, is_active, updated_at
            FROM templates
            WHERE " . implode(' AND ', $where) . "
            ORDER BY is_system DESC, module_key ASC, target_key ASC, code ASC
        ");
        $stmt->execute($params);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('template page list error: ' . $e->getMessage());
        $templates = [];
    }
}

$kindTabs = [
    'email' => 'template/email',
    'print' => 'template/print',
    'content' => 'template/content',
];
$filterParams = [
    'kind' => $kind,
];
if ($search !== '') {
    $filterParams['search'] = $search;
}
if ($moduleFilter !== '') {
    $filterParams['module_key'] = $moduleFilter;
}
if ($codeFilter !== '') {
    $filterParams['code'] = $codeFilter;
}
if ($statusFilter !== '' && in_array($statusFilter, ['0', '1'], true)) {
    $filterParams['status'] = $statusFilter;
}
$csvExportUrl = base_url('template/actions/export?' . http_build_query($filterParams + ['format' => 'csv']));
$xlsExportUrl = base_url('template/actions/export?' . http_build_query($filterParams + ['format' => 'xls']));
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(template_lang('templates')); ?></div>
                <h2 class="page-title"><?php echo e($titles[$kind] ?? template_lang('templates')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(template_lang('templates_hint')); ?></div>
            </div>
            <?php if ($kind === 'email' && route_exists('mail/test')): ?>
                <div class="col-auto ms-auto d-print-none">
                    <a href="<?php echo base_url('mail/test'); ?>" class="btn btn-outline-primary">
                        <?php echo e(template_lang('back_to_mail')); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card mb-4">
            <div class="card-body">
                <div class="btn-list">
                    <?php foreach ($kindTabs as $tabKind => $route): ?>
                        <a href="<?php echo base_url($route); ?>" class="btn <?php echo $kind === $tabKind ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <?php echo e(template_lang('kind_' . $tabKind)); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (!$tableReady): ?>
            <div class="alert alert-warning"><?php echo e(template_lang('table_missing')); ?></div>
        <?php else: ?>
            <div class="card mb-4">
                <form method="get" action="">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(template_lang('filters')); ?></h3>
                        <div class="card-actions">
                            <div class="btn-list">
                                <a href="<?php echo e($csvExportUrl); ?>" class="btn btn-outline-secondary">
                                    <i class="ti ti-file-type-csv"></i>
                                    <?php echo e(template_lang('export_csv')); ?>
                                </a>
                                <a href="<?php echo e($xlsExportUrl); ?>" class="btn btn-outline-secondary">
                                    <i class="ti ti-file-spreadsheet"></i>
                                    <?php echo e(template_lang('export_excel')); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-lg-4">
                                <label class="form-label"><?php echo e(template_lang('search')); ?></label>
                                <input type="search" name="search" class="form-control" value="<?php echo e($search); ?>" placeholder="<?php echo e(template_lang('search_placeholder')); ?>">
                            </div>
                            <div class="col-12 col-lg-2">
                                <label class="form-label"><?php echo e(template_lang('module')); ?></label>
                                <select name="module_key" class="form-select">
                                    <option value=""><?php echo e(template_lang('all_modules')); ?></option>
                                    <?php foreach ($modules as $moduleKey => $moduleLabel): ?>
                                        <option value="<?php echo e($moduleKey); ?>" <?php echo $moduleFilter === $moduleKey ? 'selected' : ''; ?>>
                                            <?php echo e($moduleLabel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-lg-2">
                                <label class="form-label"><?php echo e(template_lang('code')); ?></label>
                                <input type="text" name="code" class="form-control" value="<?php echo e($codeFilter); ?>">
                            </div>
                            <div class="col-12 col-lg-2">
                                <label class="form-label"><?php echo e(template_lang('status')); ?></label>
                                <select name="status" class="form-select">
                                    <option value=""><?php echo e(template_lang('all_statuses')); ?></option>
                                    <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>><?php echo e(template_lang('active')); ?></option>
                                    <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>><?php echo e(template_lang('inactive')); ?></option>
                                </select>
                            </div>
                            <div class="col-12 col-lg-2">
                                <div class="btn-list">
                                    <button type="submit" class="btn btn-primary"><?php echo e(template_lang('filter')); ?></button>
                                    <a href="<?php echo base_url($kindTabs[$kind] ?? 'template/templates'); ?>" class="btn btn-outline-secondary"><?php echo e(template_lang('clear')); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (check_permission('template.manage')): ?>
                <div class="card mb-4">
                    <form action="<?php echo base_url('template/actions/create'); ?>" method="post" data-ajax="true">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo e(template_lang('new_template')); ?></h3>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                            <input type="hidden" name="kind" value="<?php echo e($kind); ?>">

                            <div class="row g-3">
                                <div class="col-12 col-lg-3">
                                    <label class="form-label form-required"><?php echo e(template_lang('module')); ?></label>
                                    <select name="module_key" class="form-select" required>
                                        <?php foreach ($modules as $moduleKey => $moduleLabel): ?>
                                            <option value="<?php echo e($moduleKey); ?>"><?php echo e($moduleLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-3">
                                    <label class="form-label form-required"><?php echo e(template_lang('target')); ?></label>
                                    <select name="target_key" class="form-select" required>
                                        <?php foreach ($targets as $targetKey => $targetLabel): ?>
                                            <option value="<?php echo e($targetKey); ?>"><?php echo e($targetLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-3">
                                    <label class="form-label form-required"><?php echo e(template_lang('code')); ?></label>
                                    <input type="text" name="code" class="form-control" required>
                                    <small class="form-hint"><?php echo e(template_lang('code_hint')); ?></small>
                                </div>
                                <div class="col-12 col-lg-3">
                                    <label class="form-label form-required"><?php echo e(template_lang('name')); ?></label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-12 col-lg-2">
                                    <label class="form-label form-required"><?php echo e(template_lang('language')); ?></label>
                                    <input type="text" name="language" class="form-control" maxlength="10" required value="<?php echo e((string) env('APP_LOCALE', 'tr')); ?>">
                                </div>
                                <?php if (in_array($kind, ['email', 'content'], true)): ?>
                                    <div class="col-12 col-lg-10">
                                        <label class="form-label form-required"><?php echo e(template_lang('subject')); ?></label>
                                        <input type="text" name="subject" class="form-control" required>
                                    </div>
                                <?php endif; ?>
                                <div class="col-12">
                                    <label class="form-label form-required"><?php echo e(template_lang('body')); ?></label>
                                    <textarea name="body" rows="8" class="form-control js-template-body" required></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label"><?php echo e(template_lang('variables')); ?></label>
                                    <input type="text" name="variables" class="form-control">
                                    <small class="form-hint"><?php echo e(template_lang('variables_hint')); ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary"><?php echo e(template_lang('save')); ?></button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(template_lang('template_list')); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($templates)): ?>
                        <div class="text-secondary"><?php echo e(template_lang('no_records')); ?></div>
                    <?php else: ?>
                        <div class="accordion" id="template-registry-accordion">
                            <?php foreach ($templates as $template): ?>
                                <?php
                                $templateId = (int) ($template['id'] ?? 0);
                                $isSystem = (int) ($template['is_system'] ?? 0) === 1;
                                $isActive = (int) ($template['is_active'] ?? 0) === 1;
                                $variables = kirpi_template_normalize_variables(json_decode((string) ($template['variables_json'] ?? '[]'), true) ?: []);
                                $variables = array_values(array_unique(array_merge(
                                    $variables,
                                    kirpi_template_extract_placeholders((string) ($template['subject'] ?? '')),
                                    kirpi_template_extract_placeholders((string) ($template['body'] ?? ''))
                                )));
                                sort($variables);
                                $itemId = 'template-registry-' . $templateId;
                                ?>
                                <div class="accordion-item mb-3 border rounded">
                                    <h2 class="accordion-header" id="<?php echo e($itemId); ?>-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo e($itemId); ?>-body" aria-expanded="false" aria-controls="<?php echo e($itemId); ?>-body">
                                            <div class="w-100 d-flex align-items-center justify-content-between pe-3">
                                                <div>
                                                    <strong><?php echo e((string) ($template['name'] ?? '')); ?></strong>
                                                    <div class="text-secondary">
                                                        <code><?php echo e((string) ($template['module_key'] ?? '')); ?></code>
                                                        <span class="mx-1">/</span>
                                                        <code><?php echo e((string) ($template['target_key'] ?? '')); ?></code>
                                                        <span class="mx-1">/</span>
                                                        <code><?php echo e((string) ($template['code'] ?? '')); ?></code>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <span class="badge <?php echo $isActive ? 'bg-green-lt' : 'bg-red-lt'; ?>"><?php echo e($isActive ? template_lang('active') : template_lang('inactive')); ?></span>
                                                    <span class="badge <?php echo $isSystem ? 'bg-blue-lt' : 'bg-gray-lt'; ?>"><?php echo e($isSystem ? template_lang('system') : template_lang('custom')); ?></span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="<?php echo e($itemId); ?>-body" class="accordion-collapse collapse" aria-labelledby="<?php echo e($itemId); ?>-header" data-bs-parent="#template-registry-accordion">
                                        <div class="accordion-body">
                                            <?php if (check_permission('template.manage')): ?>
                                                <form action="<?php echo base_url('template/actions/update'); ?>" method="post" data-ajax="true" class="mb-3">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $templateId; ?>">
                                                    <div class="row g-3">
                                                        <div class="col-12 col-lg-3">
                                                            <label class="form-label"><?php echo e(template_lang('module')); ?></label>
                                                            <input type="text" class="form-control" value="<?php echo e((string) ($template['module_key'] ?? '')); ?>" disabled>
                                                        </div>
                                                        <div class="col-12 col-lg-3">
                                                            <label class="form-label"><?php echo e(template_lang('target')); ?></label>
                                                            <input type="text" class="form-control" value="<?php echo e((string) ($template['target_key'] ?? '')); ?>" disabled>
                                                        </div>
                                                        <div class="col-12 col-lg-3">
                                                            <label class="form-label"><?php echo e(template_lang('code')); ?></label>
                                                            <input type="text" class="form-control" value="<?php echo e((string) ($template['code'] ?? '')); ?>" disabled>
                                                        </div>
                                                        <div class="col-12 col-lg-3">
                                                            <label class="form-label form-required"><?php echo e(template_lang('name')); ?></label>
                                                            <input type="text" name="name" class="form-control" required value="<?php echo e((string) ($template['name'] ?? '')); ?>">
                                                        </div>
                                                        <div class="col-12 col-lg-2">
                                                            <label class="form-label form-required"><?php echo e(template_lang('language')); ?></label>
                                                            <input type="text" name="language" class="form-control" maxlength="10" required value="<?php echo e((string) ($template['language'] ?? 'tr')); ?>">
                                                        </div>
                                                        <?php if (in_array($kind, ['email', 'content'], true)): ?>
                                                            <div class="col-12 col-lg-10">
                                                                <label class="form-label form-required"><?php echo e(template_lang('subject')); ?></label>
                                                                <input type="text" name="subject" class="form-control" required value="<?php echo e((string) ($template['subject'] ?? '')); ?>">
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="col-12">
                                                            <label class="form-label form-required"><?php echo e(template_lang('body')); ?></label>
                                                            <textarea name="body" rows="8" class="form-control js-template-body" required><?php echo e((string) ($template['body'] ?? '')); ?></textarea>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label"><?php echo e(template_lang('variables')); ?></label>
                                                            <input type="text" name="variables" class="form-control" value="<?php echo e(implode(',', $variables)); ?>">
                                                        </div>
                                                        <div class="col-12 d-flex align-items-center justify-content-between">
                                                            <label class="form-check">
                                                                <input type="checkbox" class="form-check-input" name="is_active" value="1" <?php echo $isActive ? 'checked' : ''; ?>>
                                                                <span class="form-check-label"><?php echo e(template_lang('active')); ?></span>
                                                            </label>
                                                            <button type="submit" class="btn btn-primary"><?php echo e(template_lang('update')); ?></button>
                                                        </div>
                                                    </div>
                                                </form>
                                            <?php endif; ?>

                                            <div>
                                                <div class="text-secondary mb-1"><?php echo e(template_lang('variables')); ?></div>
                                                <?php if (empty($variables)): ?>
                                                    <span class="text-secondary">-</span>
                                                <?php else: ?>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php foreach ($variables as $variable): ?>
                                                            <span class="badge bg-azure-lt"><code>{{<?php echo e($variable); ?>}}</code></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    document.addEventListener('submit', function () {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }
    }, true);
})();
</script>
