<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/ai/language.php';

$schemaReady = kirpi_ai_schema_registry_ready();
$auditReady = kirpi_ai_audit_table_ready();
$modelsReady = kirpi_ai_models_table_ready();
$indexReady = kirpi_ai_schema_index_ready();
$manifestCount = kirpi_ai_schema_manifest_count();
$latestSync = kirpi_ai_latest_schema_sync();
$canManageSchema = check_permission('ai.schema.manage');
$canManageAdapters = check_permission('ai.adapters.manage');
$canViewAudit = check_permission('ai.audit.view');
$qualityReport = $canManageSchema ? kirpi_ai_schema_quality_report(1) : null;
$qualityMeta = is_array($qualityReport) ? (array) ($qualityReport['meta'] ?? []) : [];

$cards = [
    [
        'title' => ai_lang('schema_manifests'),
        'value' => $manifestCount,
        'label' => ai_lang('schema_manifests'),
        'detail' => ai_lang('schema_registry_detail'),
        'ready' => $manifestCount > 0,
    ],
    [
        'title' => ai_lang('schema_registry'),
        'value' => kirpi_ai_schema_count(),
        'label' => ai_lang('active_entities'),
        'detail' => ai_lang('schema_registry_detail'),
        'ready' => $schemaReady,
    ],
    [
        'title' => ai_lang('metadata_index'),
        'value' => kirpi_ai_schema_index_count(),
        'label' => ai_lang('index_records'),
        'detail' => ai_lang('metadata_index_detail'),
        'ready' => $indexReady,
    ],
    [
        'title' => ai_lang('schema_quality'),
        'value' => (int) ($qualityMeta['warning_count'] ?? 0),
        'label' => ai_lang('quality_warnings'),
        'detail' => ai_lang('schema_quality_detail'),
        'ready' => $canManageSchema ? ((int) ($qualityMeta['error_count'] ?? 0) === 0) : true,
    ],
    [
        'title' => ai_lang('ai_audit_log'),
        'value' => kirpi_ai_audit_count(),
        'label' => ai_lang('audit_records'),
        'detail' => ai_lang('ai_audit_log_detail'),
        'ready' => $auditReady,
    ],
    [
        'title' => ai_lang('model_adapters'),
        'value' => count(kirpi_ai_model_adapters()),
        'label' => ai_lang('adapter_count'),
        'detail' => ai_lang('model_adapters_detail'),
        'ready' => $modelsReady,
    ],
];

$statusBadge = static fn (bool $ready): string => $ready ? 'bg-green-lt' : 'bg-red-lt';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(ai_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(ai_lang('kirpi_intelligence')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(ai_lang('dashboard_detail')); ?></div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php if ($canManageSchema): ?>
                        <a href="<?php echo base_url('ai/query-flow'); ?>" class="btn btn-primary">
                            <?php echo e(ai_lang('query_flow')); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($canManageAdapters): ?>
                        <a href="<?php echo base_url('ai/providers'); ?>" class="btn btn-outline-primary">
                            <?php echo e(ai_lang('provider_settings', 'Provider Ayarları')); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo base_url('ai/schema'); ?>" class="btn btn-outline-primary">
                        <?php echo e(ai_lang('schema_discovery')); ?>
                    </a>
                    <?php if ($canViewAudit): ?>
                        <a href="<?php echo base_url('ai/audit'); ?>" class="btn btn-outline-secondary">
                            <?php echo e(ai_lang('view_audit')); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!$schemaReady): ?>
            <div class="alert alert-warning"><?php echo e(ai_lang('schema_missing')); ?></div>
        <?php endif; ?>

        <div class="row row-cards">
            <?php foreach ($cards as $card): ?>
                <div class="col-sm-6 col-lg-4 col-xl-2">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="subheader"><?php echo e((string) $card['title']); ?></div>
                                <span class="badge <?php echo $statusBadge((bool) $card['ready']); ?>">
                                    <?php echo e((bool) $card['ready'] ? ai_lang('status_ready') : ai_lang('status_missing')); ?>
                                </span>
                            </div>
                            <div class="h1 mb-1 mt-3"><?php echo (int) $card['value']; ?></div>
                            <div class="text-secondary"><?php echo e((string) $card['label']); ?></div>
                            <div class="text-secondary small mt-3"><?php echo e((string) $card['detail']); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row row-cards mt-1">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(ai_lang('primary_workflows')); ?></h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if ($canManageSchema): ?>
                            <a href="<?php echo base_url('ai/query-flow'); ?>" class="list-group-item list-group-item-action">
                                <div class="row align-items-center">
                                    <div class="col-auto"><i class="ti ti-git-branch"></i></div>
                                    <div class="col">
                                        <div><?php echo e(ai_lang('query_flow')); ?></div>
                                        <div class="text-secondary small"><?php echo e(ai_lang('query_flow_detail')); ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo base_url('ai/schema'); ?>" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto"><i class="ti ti-database-search"></i></div>
                                <div class="col">
                                    <div><?php echo e(ai_lang('schema_discovery')); ?></div>
                                    <div class="text-secondary small"><?php echo e(ai_lang('schema_discovery_detail')); ?></div>
                                </div>
                            </div>
                        </a>
                        <?php if ($canViewAudit): ?>
                            <a href="<?php echo base_url('ai/audit'); ?>" class="list-group-item list-group-item-action">
                                <div class="row align-items-center">
                                    <div class="col-auto"><i class="ti ti-history"></i></div>
                                    <div class="col">
                                        <div><?php echo e(ai_lang('view_audit')); ?></div>
                                        <div class="text-secondary small"><?php echo e(ai_lang('ai_audit_log_detail')); ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($canManageSchema): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo e(ai_lang('technical_tools')); ?></h3>
                        </div>
                        <div class="list-group list-group-flush">
                            <a href="<?php echo base_url('ai/planner'); ?>" class="list-group-item list-group-item-action">
                                <div class="row align-items-center">
                                    <div class="col-auto"><i class="ti ti-route-square"></i></div>
                                    <div class="col">
                                        <div><?php echo e(ai_lang('query_planner')); ?></div>
                                        <div class="text-secondary small"><?php echo e(ai_lang('query_planner_detail')); ?></div>
                                    </div>
                                </div>
                            </a>
                            <a href="<?php echo base_url('ai/quality'); ?>" class="list-group-item list-group-item-action">
                                <div class="row align-items-center">
                                    <div class="col-auto"><i class="ti ti-shield-check"></i></div>
                                    <div class="col">
                                        <div><?php echo e(ai_lang('schema_quality')); ?></div>
                                        <div class="text-secondary small"><?php echo e(ai_lang('schema_quality_detail')); ?></div>
                                    </div>
                                </div>
                            </a>
                            <a href="<?php echo base_url('ai/sql-candidate'); ?>" class="list-group-item list-group-item-action">
                                <div class="row align-items-center">
                                    <div class="col-auto"><i class="ti ti-file-pencil"></i></div>
                                    <div class="col">
                                        <div><?php echo e(ai_lang('sql_candidate')); ?></div>
                                        <div class="text-secondary small"><?php echo e(ai_lang('sql_candidate_detail')); ?></div>
                                    </div>
                                </div>
                            </a>
                            <?php if ($canManageAdapters): ?>
                                <a href="<?php echo base_url('ai/providers'); ?>" class="list-group-item list-group-item-action">
                                    <div class="row align-items-center">
                                        <div class="col-auto"><i class="ti ti-plug-connected"></i></div>
                                        <div class="col">
                                            <div><?php echo e(ai_lang('provider_settings', 'Provider Ayarları')); ?></div>
                                            <div class="text-secondary small"><?php echo e(ai_lang('provider_settings_detail', 'Model provider ve adapter ayarları arayüzden yönetilir; global runtime kapısı env içinde kalır.')); ?></div>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo base_url('ai/sql-preview'); ?>" class="list-group-item list-group-item-action">
                                <div class="row align-items-center">
                                    <div class="col-auto"><i class="ti ti-file-search"></i></div>
                                    <div class="col">
                                        <div><?php echo e(ai_lang('sql_preview')); ?></div>
                                        <div class="text-secondary small"><?php echo e(ai_lang('sql_preview_detail')); ?></div>
                                    </div>
                                </div>
                            </a>
                            <a href="<?php echo base_url('ai/sql-guard'); ?>" class="list-group-item list-group-item-action">
                                <div class="row align-items-center">
                                    <div class="col-auto"><i class="ti ti-shield-lock"></i></div>
                                    <div class="col">
                                        <div><?php echo e(ai_lang('sql_guard')); ?></div>
                                        <div class="text-secondary small"><?php echo e(ai_lang('sql_guard_detail')); ?></div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(ai_lang('latest_schema_sync')); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($latestSync === null): ?>
                            <div class="text-secondary"><?php echo e(ai_lang('no_schema_sync')); ?></div>
                        <?php else: ?>
                            <?php $syncDetails = (array) ($latestSync['details'] ?? []); ?>
                            <div class="mb-2"><?php echo e((string) ($latestSync['created_at'] ?? '-')); ?></div>
                            <div class="text-secondary small">
                                <?php echo e(ai_lang('entities')); ?>:
                                <strong><?php echo (int) ($syncDetails['entity_count'] ?? 0); ?></strong>
                                <br>
                                <?php echo e(ai_lang('fields')); ?>:
                                <strong><?php echo (int) ($syncDetails['field_count'] ?? 0); ?></strong>
                                <br>
                                <?php echo e(ai_lang('index_records')); ?>:
                                <strong><?php echo (int) ($syncDetails['index_count'] ?? 0); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($canManageSchema): ?>
                        <div class="card-footer">
                            <form action="<?php echo base_url('ai/actions/sync-schema'); ?>" method="post" data-ajax="true">
                                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                <button type="submit" class="btn btn-primary w-100">
                                    <?php echo e(ai_lang('sync_schema')); ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
