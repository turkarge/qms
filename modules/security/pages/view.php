<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/security/language.php';

$checks = [];

$checks[] = [
    'name' => security_lang('check_app_env_name'),
    'value' => APP_ENV,
    'ok' => APP_ENV === 'production',
    'hint' => security_lang('check_app_env_hint'),
];

$checks[] = [
    'name' => security_lang('check_debug_name'),
    'value' => APP_DEBUG ? 'true' : 'false',
    'ok' => APP_DEBUG === false,
    'hint' => security_lang('check_debug_hint'),
];

$checks[] = [
    'name' => security_lang('check_proxy_name'),
    'value' => APP_TRUST_PROXY ? 'true' : 'false',
    'ok' => APP_TRUST_PROXY === true,
    'hint' => security_lang('check_proxy_hint'),
];

$checks[] = [
    'name' => security_lang('check_web_setup_name'),
    'value' => env_bool('AUTO_WEB_SETUP', true) ? security_lang('enabled') : security_lang('disabled'),
    'ok' => env_bool('AUTO_WEB_SETUP', true) === false,
    'hint' => security_lang('check_web_setup_hint'),
];

$setupKey = (string) env('SETUP_KEY', '');
$checks[] = [
    'name' => security_lang('check_setup_key_name'),
    'value' => $setupKey !== '' ? security_lang('configured') : security_lang('empty'),
    'ok' => $setupKey !== '',
    'hint' => security_lang('check_setup_key_hint'),
];

$checks[] = [
    'name' => security_lang('check_session_secure_name'),
    'value' => ini_get('session.cookie_secure') === '1' ? security_lang('enabled') : security_lang('disabled'),
    'ok' => ini_get('session.cookie_secure') === '1',
    'hint' => security_lang('check_session_secure_hint'),
];

$checks[] = [
    'name' => security_lang('check_session_samesite_name'),
    'value' => (string) ini_get('session.cookie_samesite'),
    'ok' => strtolower((string) ini_get('session.cookie_samesite')) === 'lax',
    'hint' => security_lang('check_session_samesite_hint'),
];

$dirChecks = [];
$paths = [
    'uploads' => BASE_PATH . '/uploads',
    'uploads/avatars' => BASE_PATH . '/uploads/avatars',
    'uploads/documents' => BASE_PATH . '/uploads/documents',
    'logs' => BASE_PATH . '/logs',
    'storage' => BASE_PATH . '/storage',
];

foreach ($paths as $label => $path) {
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    $perm = file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : '----';

    $dirChecks[] = [
        'name' => $label,
        'path' => $path,
        'exists' => $exists,
        'writable' => $writable,
        'perm' => $perm,
    ];
}

$dbTables = [];
try {
    $stmt = db()->query("SHOW TABLES");
    $rows = $stmt->fetchAll(PDO::FETCH_NUM) ?: [];
    foreach ($rows as $row) {
        if (isset($row[0])) {
            $dbTables[] = (string) $row[0];
        }
    }
} catch (Throwable $e) {
    $dbTables = [];
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(security_lang('page_pretitle')); ?></div>
                <h2 class="page-title"><?php echo e(security_lang('page_title')); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(security_lang('security_checks_title')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="standard" data-table-title="Güvenlik Kontrolleri" class="table table-vcenter card-table table-striped">
                    <thead>
                    <tr>
                        <th><?php echo e(security_lang('col_check')); ?></th>
                        <th><?php echo e(security_lang('col_value')); ?></th>
                        <th><?php echo e(security_lang('col_status')); ?></th>
                        <th><?php echo e(security_lang('col_note')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($checks as $check): ?>
                        <tr>
                            <td><?php echo e($check['name']); ?></td>
                            <td><code><?php echo e((string) $check['value']); ?></code></td>
                            <td>
                                <?php if ($check['ok']): ?>
                                    <span class="badge bg-green-lt">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-red-lt"><?php echo e(security_lang('status_warn')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary"><?php echo e($check['hint']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(security_lang('dirs_title')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="standard" data-table-title="Dizin Güvenliği" class="table table-vcenter card-table table-striped">
                    <thead>
                    <tr>
                        <th><?php echo e(security_lang('col_folder')); ?></th>
                        <th><?php echo e(security_lang('col_path')); ?></th>
                        <th><?php echo e(security_lang('col_exists')); ?></th>
                        <th><?php echo e(security_lang('col_writable')); ?></th>
                        <th><?php echo e(security_lang('col_perm')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dirChecks as $d): ?>
                        <tr>
                            <td><?php echo e($d['name']); ?></td>
                            <td><code><?php echo e($d['path']); ?></code></td>
                            <td><?php echo $d['exists'] ? '<span class="badge bg-green-lt">' . e(security_lang('yes')) . '</span>' : '<span class="badge bg-red-lt">' . e(security_lang('no')) . '</span>'; ?></td>
                            <td><?php echo $d['writable'] ? '<span class="badge bg-green-lt">' . e(security_lang('yes')) . '</span>' : '<span class="badge bg-red-lt">' . e(security_lang('no')) . '</span>'; ?></td>
                            <td><code><?php echo e($d['perm']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(security_lang('db_tables_title')); ?></h3>
            </div>
            <div class="card-body">
                <?php if (empty($dbTables)): ?>
                    <div class="text-secondary"><?php echo e(security_lang('db_empty')); ?></div>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($dbTables as $table): ?>
                            <span class="badge bg-blue-lt"><?php echo e($table); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
