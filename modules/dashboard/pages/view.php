<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/dashboard/language.php';
if (is_file(BASE_PATH . '/modules/organization/helpers.php')) {
    require_once BASE_PATH . '/modules/organization/helpers.php';
}

$metrics = [
    'user_total' => 0,
    'user_active' => 0,
    'role_total' => 0,
    'notifications_unread' => 0,
    'api_24h_total' => 0,
    'throttle_active_blocks' => 0,
    'enabled_modules' => 0,
    'qms_companies' => 0,
    'qms_entities' => 0,
    'qms_relationships' => 0,
    'qms_events_7d' => 0,
];

$qmsActiveCompany = function_exists('organization_active_company') ? organization_active_company() : null;
$qmsActiveCompanyId = (int) ($qmsActiveCompany['id'] ?? 0);

try {
    if (db_table_exists('users')) {
        $stmt = db()->query('SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_total FROM users');
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $metrics['user_total'] = (int) ($row['total'] ?? 0);
        $metrics['user_active'] = (int) ($row['active_total'] ?? 0);
    }

    if (db_table_exists('roles')) {
        $stmt = db()->query('SELECT COUNT(*) FROM roles');
        $metrics['role_total'] = (int) $stmt->fetchColumn();
    }

    $currentUser = current_user();
    if (!empty($currentUser['id'])) {
        $metrics['notifications_unread'] = get_unread_notifications_count((int) $currentUser['id']);
    }

    if (db_table_exists('api_request_logs')) {
        $stmt = db()->query("SELECT COUNT(*) FROM api_request_logs WHERE created_at >= (NOW() - INTERVAL 24 HOUR)");
        $metrics['api_24h_total'] = (int) $stmt->fetchColumn();
    }

    if (db_table_exists('request_throttles')) {
        $stmt = db()->query("SELECT COUNT(*) FROM request_throttles WHERE blocked_until IS NOT NULL AND blocked_until > NOW()");
        $metrics['throttle_active_blocks'] = (int) $stmt->fetchColumn();
    }

    if (db_table_exists('organization_companies')) {
        $params = [];
        $where = ["status = 'active'"];
        $scope = function_exists('organization_scope_where') ? organization_scope_where('id', $params) : null;
        if ($scope !== null) $where[] = $scope;
        $stmt = db()->prepare('SELECT COUNT(*) FROM organization_companies WHERE ' . implode(' AND ', $where));
        $stmt->execute($params);
        $metrics['qms_companies'] = (int) $stmt->fetchColumn();
    }

    if ($qmsActiveCompanyId > 0 && db_table_exists('qms_entities')) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM qms_entities WHERE company_id = :company AND status <> 'archived'");
        $stmt->execute([':company' => $qmsActiveCompanyId]);
        $metrics['qms_entities'] = (int) $stmt->fetchColumn();
    }

    if ($qmsActiveCompanyId > 0 && db_table_exists('qms_entity_relationships')) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM qms_entity_relationships WHERE company_id = :company AND status <> 'archived'");
        $stmt->execute([':company' => $qmsActiveCompanyId]);
        $metrics['qms_relationships'] = (int) $stmt->fetchColumn();
    }

    if ($qmsActiveCompanyId > 0 && db_table_exists('qms_domain_events')) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM qms_domain_events WHERE company_id = :company AND recorded_at >= (NOW() - INTERVAL 7 DAY)");
        $stmt->execute([':company' => $qmsActiveCompanyId]);
        $metrics['qms_events_7d'] = (int) $stmt->fetchColumn();
    }
} catch (Throwable $e) {
    error_log('dashboard metrics error: ' . $e->getMessage());
}

$modules = function_exists('kirpi_list_modules') ? kirpi_list_modules() : [];
$metrics['enabled_modules'] = count(array_filter($modules, static fn(array $m): bool => !empty($m['enabled'])));

$uploadPath = BASE_PATH . '/uploads';
$apiEnabled = env_bool('API_ENABLED', true);
if (function_exists('kirpi_setting_bool')) {
    $apiEnabled = kirpi_setting_bool('api.enabled', $apiEnabled);
}

$checks = [
    [
        'ok' => true,
        'title' => dashboard_lang('front_controller'),
        'detail' => dashboard_lang('front_controller_ok'),
    ],
    [
        'ok' => db_table_exists('users'),
        'title' => dashboard_lang('database_schema'),
        'detail' => db_table_exists('users') ? dashboard_lang('database_ok') : dashboard_lang('database_missing'),
    ],
    [
        'ok' => is_dir($uploadPath) && is_writable($uploadPath),
        'title' => dashboard_lang('upload_folder'),
        'detail' => (is_dir($uploadPath) && is_writable($uploadPath)) ? dashboard_lang('upload_ok') : dashboard_lang('upload_warn'),
    ],
    [
        'ok' => $apiEnabled,
        'title' => dashboard_lang('api_status'),
        'detail' => $apiEnabled ? dashboard_lang('api_on') : dashboard_lang('api_off'),
    ],
    [
        'ok' => function_exists('kirpi_throttle_enabled') ? kirpi_throttle_enabled() : false,
        'title' => dashboard_lang('throttle_protection'),
        'detail' => (function_exists('kirpi_throttle_enabled') && kirpi_throttle_enabled()) ? dashboard_lang('throttle_on') : dashboard_lang('throttle_off'),
    ],
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(dashboard_lang('brand')); ?></div>
                <h2 class="page-title"><?php echo e(dashboard_lang('dashboard')); ?></h2>
                <div class="text-secondary mt-1">
                    <?php echo e(dashboard_lang('summary')); ?>
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="<?php echo base_url('health/view'); ?>" class="btn btn-outline-primary"><?php echo e(dashboard_lang('health_metrics')); ?></a>
                    <a href="<?php echo base_url('settings/view'); ?>" class="btn btn-primary"><?php echo e(dashboard_lang('settings')); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(dashboard_lang('users')); ?></div>
                        <div class="h1 mb-2"><?php echo (int) $metrics['user_total']; ?></div>
                        <div class="text-secondary"><?php echo e(dashboard_lang('active_prefix')); ?><?php echo (int) $metrics['user_active']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(dashboard_lang('roles')); ?></div>
                        <div class="h1 mb-2"><?php echo (int) $metrics['role_total']; ?></div>
                        <div class="text-secondary"><?php echo e(dashboard_lang('roles_hint')); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(dashboard_lang('unread_notifications')); ?></div>
                        <div class="h1 mb-2"><?php echo (int) $metrics['notifications_unread']; ?></div>
                        <div class="text-secondary"><?php echo e(dashboard_lang('user_based_active')); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(dashboard_lang('modules')); ?></div>
                        <div class="h1 mb-2"><?php echo (int) $metrics['enabled_modules']; ?></div>
                        <div class="text-secondary"><?php echo e(dashboard_lang('active_module_count')); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title"><?php echo e(dashboard_lang('qms_summary')); ?></h3>
                            <div class="card-subtitle">
                                <?php echo e($qmsActiveCompany ? dashboard_lang('qms_active_company_prefix') . (string) ($qmsActiveCompany['company_name'] ?? '') : dashboard_lang('qms_no_active_company')); ?>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a href="<?php echo base_url('qms_entities/view'); ?>" class="btn btn-outline-primary btn-sm"><?php echo e(dashboard_lang('qms_entities_link')); ?></a>
                            <a href="<?php echo base_url('qms_events/view'); ?>" class="btn btn-outline-primary btn-sm"><?php echo e(dashboard_lang('qms_events_link')); ?></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6 col-lg-3">
                                <div class="subheader"><?php echo e(dashboard_lang('qms_companies')); ?></div>
                                <div class="h2 mb-1"><?php echo (int) $metrics['qms_companies']; ?></div>
                                <div class="text-secondary"><?php echo e(dashboard_lang('qms_companies_hint')); ?></div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="subheader"><?php echo e(dashboard_lang('qms_entities')); ?></div>
                                <div class="h2 mb-1"><?php echo (int) $metrics['qms_entities']; ?></div>
                                <div class="text-secondary"><?php echo e(dashboard_lang('qms_active_company_scope')); ?></div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="subheader"><?php echo e(dashboard_lang('qms_relationships')); ?></div>
                                <div class="h2 mb-1"><?php echo (int) $metrics['qms_relationships']; ?></div>
                                <div class="text-secondary"><?php echo e(dashboard_lang('qms_active_company_scope')); ?></div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="subheader"><?php echo e(dashboard_lang('qms_events_7d')); ?></div>
                                <div class="h2 mb-1"><?php echo (int) $metrics['qms_events_7d']; ?></div>
                                <div class="text-secondary"><?php echo e(dashboard_lang('qms_events_7d_hint')); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(dashboard_lang('api_calls_24h')); ?></div>
                        <div class="h1 mb-2"><?php echo (int) $metrics['api_24h_total']; ?></div>
                        <div class="text-secondary"><?php echo e(dashboard_lang('api_calls_24h_hint')); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(dashboard_lang('active_throttle_blocks')); ?></div>
                        <div class="h1 mb-2"><?php echo (int) $metrics['throttle_active_blocks']; ?></div>
                        <div class="text-secondary"><?php echo e(dashboard_lang('throttle_blocks_hint')); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(dashboard_lang('system_checklist')); ?></h3>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($checks as $check): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="status-dot status-dot-animated <?php echo !empty($check['ok']) ? 'bg-green' : 'bg-red'; ?> d-block"></span>
                            </div>
                            <div class="col text-truncate">
                                <span class="text-body d-block"><?php echo e((string) ($check['title'] ?? dashboard_lang('check_default'))); ?></span>
                                <div class="d-block text-secondary text-truncate mt-n1">
                                    <?php echo e((string) ($check['detail'] ?? '')); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
