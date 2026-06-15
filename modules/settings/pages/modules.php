<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/settings/language.php';

$modules = kirpi_list_modules();
$requiredByMap = [];

foreach ($modules as $candidateModule) {
    $candidateKey = (string) ($candidateModule['key'] ?? '');
    if ($candidateKey === '') {
        continue;
    }

    if (!array_key_exists($candidateKey, $requiredByMap)) {
        $requiredByMap[$candidateKey] = [];
    }
}

foreach ($modules as $candidateModule) {
    if (empty($candidateModule['enabled'])) {
        continue;
    }

    $ownerKey = (string) ($candidateModule['key'] ?? '');
    $requires = array_map('strval', (array) ($candidateModule['requires'] ?? []));

    foreach ($requires as $requiredKey) {
        if ($requiredKey === '' || !array_key_exists($requiredKey, $requiredByMap)) {
            continue;
        }

        $requiredByMap[$requiredKey][] = $ownerKey;
    }
}

foreach ($requiredByMap as $moduleKey => $dependents) {
    $requiredByMap[$moduleKey] = array_values(array_unique($dependents));
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(settings_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(settings_lang('module_management')); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!kirpi_modules_registry_ready()): ?>
            <div class="alert alert-warning">
                <code>app_modules</code> <?php echo e(settings_lang('app_modules_missing')); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(settings_lang('modules')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="standard" data-table-title="Modül Registry" class="table table-vcenter card-table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Key</th>
                        <th><?php echo e(settings_lang('name')); ?></th>
                        <th><?php echo e(settings_lang('version')); ?></th>
                        <th><?php echo e(settings_lang('order')); ?></th>
                        <th><?php echo e(settings_lang('dependency')); ?></th>
                        <th><?php echo e(settings_lang('dependent_modules')); ?></th>
                        <th><?php echo e(settings_lang('type')); ?></th>
                        <th><?php echo e(settings_lang('status')); ?></th>
                        <th class="w-1"><?php echo e(settings_lang('operation')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($modules)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-4"><?php echo e(settings_lang('module_not_found')); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($modules as $module): ?>
                            <?php
                            $moduleKey = (string) ($module['key'] ?? '');
                            $isCore = !empty($module['core']);
                            $isEnabled = !empty($module['enabled']);
                            $requires = array_map('strval', (array) ($module['requires'] ?? []));
                            $requiredBy = array_map('strval', (array) ($requiredByMap[$moduleKey] ?? []));
                            $hasDisableBlock = !$isCore && $isEnabled && !empty($requiredBy);
                            ?>
                            <tr>
                                <td><code><?php echo e($moduleKey); ?></code></td>
                                <td><?php echo e((string) ($module['name'] ?? $moduleKey)); ?></td>
                                <td><?php echo e((string) ($module['version'] ?? '1.0.0')); ?></td>
                                <td><?php echo (int) ($module['load_order'] ?? 100); ?></td>
                                <td>
                                    <?php if (empty($requires)): ?>
                                        <span class="text-secondary">-</span>
                                    <?php else: ?>
                                        <code><?php echo e(implode(', ', $requires)); ?></code>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($requiredBy)): ?>
                                        <span class="text-secondary">-</span>
                                    <?php else: ?>
                                        <code><?php echo e(implode(', ', $requiredBy)); ?></code>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $isCore ? 'bg-blue-lt' : 'bg-azure-lt'; ?>">
                                        <?php echo $isCore ? e(settings_lang('core')) : e(settings_lang('plugin')); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $isEnabled ? 'bg-green-lt' : 'bg-red-lt'; ?>">
                                        <?php echo $isEnabled ? e(settings_lang('active')) : e(settings_lang('passive')); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isCore): ?>
                                        <span class="text-secondary small"><?php echo e(settings_lang('locked')); ?></span>
                                    <?php else: ?>
                                        <form
                                            id="module-toggle-form-<?php echo e($moduleKey); ?>"
                                            action="<?php echo base_url('settings/actions/module-toggle'); ?>"
                                            method="post"
                                            data-ajax="true"
                                            class="d-none"
                                        >
                                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                            <input type="hidden" name="module_key" value="<?php echo e($moduleKey); ?>">
                                            <input type="hidden" name="is_enabled" value="<?php echo $isEnabled ? '0' : '1'; ?>">
                                        </form>
                                        <a
                                            href="#"
                                            class="btn btn-sm <?php echo $isEnabled ? 'btn-outline-danger' : 'btn-outline-success'; ?> <?php echo $hasDisableBlock ? 'disabled' : ''; ?>"
                                            <?php if (!$hasDisableBlock): ?>
                                                data-confirm="<?php echo $isEnabled ? e(settings_lang('disable_confirm')) : e(settings_lang('enable_confirm')); ?>"
                                                data-form="module-toggle-form-<?php echo e($moduleKey); ?>"
                                            <?php endif; ?>
                                            title="<?php echo $hasDisableBlock ? e(settings_lang('disable_blocked_title_prefix') . implode(', ', $requiredBy)) : ''; ?>"
                                        >
                                            <?php echo $hasDisableBlock ? e(settings_lang('dependent_module_exists')) : ($isEnabled ? e(settings_lang('disable')) : e(settings_lang('enable'))); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
