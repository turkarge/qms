<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

$qualityReport = kirpi_ai_schema_quality_report(100);
$qualityMeta = (array) ($qualityReport['meta'] ?? []);
$qualityWarnings = (array) ($qualityReport['warnings'] ?? []);
$qualityByModule = (array) ($qualityMeta['by_module'] ?? []);
$qualityExportUrl = static function (string $format): string {
    return base_url('ai/actions/export-quality?' . http_build_query([
        'format' => $format,
        'limit' => 500,
    ]));
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(ai_lang('kirpi_intelligence')); ?></div>
                <h2 class="page-title"><?php echo e(ai_lang('schema_quality')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(ai_lang('schema_quality_detail')); ?></div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="<?php echo e($qualityExportUrl('json')); ?>" class="btn btn-outline-secondary">JSON</a>
                    <a href="<?php echo e($qualityExportUrl('csv')); ?>" class="btn btn-outline-secondary">CSV</a>
                    <a href="<?php echo e($qualityExportUrl('xls')); ?>" class="btn btn-outline-secondary">XLS</a>
                    <a href="<?php echo base_url('ai/view'); ?>" class="btn btn-outline-secondary">
                        <?php echo e(ai_lang('back_to_ai')); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(ai_lang('quality_warnings')); ?></div>
                        <div class="h1 mb-1 mt-3"><?php echo (int) ($qualityMeta['warning_count'] ?? 0); ?></div>
                        <div class="text-secondary"><?php echo e(ai_lang('quality_warnings')); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(ai_lang('quality_errors')); ?></div>
                        <div class="h1 mb-1 mt-3"><?php echo (int) ($qualityMeta['error_count'] ?? 0); ?></div>
                        <div class="text-secondary"><?php echo e(ai_lang('quality_errors')); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(ai_lang('module')); ?></div>
                        <div class="h1 mb-1 mt-3"><?php echo count($qualityByModule); ?></div>
                        <div class="text-secondary"><?php echo e(ai_lang('module')); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($qualityByModule)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo e(ai_lang('module')); ?></h3>
                </div>
                <div class="table-responsive">
                    <table data-kirpi-table="report" data-table-title="Schema Kalite Modülleri" class="table table-vcenter card-table table-striped mb-0">
                        <thead>
                        <tr>
                            <th><?php echo e(ai_lang('module')); ?></th>
                            <th><?php echo e(ai_lang('quality_warnings')); ?></th>
                            <th><?php echo e(ai_lang('quality_errors')); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($qualityByModule as $moduleKey => $moduleQuality): ?>
                            <tr>
                                <td><code><?php echo e((string) $moduleKey); ?></code></td>
                                <td><?php echo (int) ($moduleQuality['warning_count'] ?? 0); ?></td>
                                <td><?php echo (int) ($moduleQuality['error_count'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(ai_lang('schema_quality')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="Schema Kalite Bulguları" class="table table-vcenter card-table table-striped mb-0">
                    <thead>
                    <tr>
                        <th><?php echo e(ai_lang('severity')); ?></th>
                        <th><?php echo e(ai_lang('quality_code')); ?></th>
                        <th><?php echo e(ai_lang('module')); ?></th>
                        <th><?php echo e(ai_lang('entity')); ?></th>
                        <th><?php echo e(ai_lang('field_name')); ?></th>
                        <th><?php echo e(ai_lang('quality_message')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($qualityWarnings)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                <?php echo e(ai_lang('no_quality_warnings')); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($qualityWarnings as $warning): ?>
                            <tr>
                                <td>
                                    <span class="badge <?php echo (string) ($warning['severity'] ?? '') === 'error' ? 'bg-red-lt' : 'bg-yellow-lt'; ?>">
                                        <?php echo e((string) ($warning['severity'] ?? 'warning')); ?>
                                    </span>
                                </td>
                                <td><code><?php echo e((string) ($warning['code'] ?? '')); ?></code></td>
                                <td><code><?php echo e((string) ($warning['module'] ?? '')); ?></code></td>
                                <td><?php echo e((string) ($warning['entity'] ?? '')); ?></td>
                                <td><?php echo e((string) ($warning['field'] ?? '-')); ?></td>
                                <td><?php echo e((string) ($warning['message'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
