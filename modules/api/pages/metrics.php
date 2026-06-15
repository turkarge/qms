<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/api/language.php';

$tableReady = db_table_exists('api_request_logs');
$window = trim((string) ($_GET['window'] ?? '24h'));
$windowMap = [
    '1h' => ['sql' => '1 HOUR', 'label' => api_lang('window_1h')],
    '24h' => ['sql' => '24 HOUR', 'label' => api_lang('window_24h')],
    '7d' => ['sql' => '7 DAY', 'label' => api_lang('window_7d')],
];
if (!isset($windowMap[$window])) {
    $window = '24h';
}
$windowSql = (string) $windowMap[$window]['sql'];
$windowLabel = (string) $windowMap[$window]['label'];

$summary = [
    'total' => 0,
    'status_2xx' => 0,
    'status_4xx' => 0,
    'status_5xx' => 0,
    'status_401' => 0,
    'status_403' => 0,
    'status_429' => 0,
    'unique_tokens' => 0,
    'unique_ips' => 0,
    'avg_duration_ms' => 0,
];
$topEndpoints = [];
$recentErrors = [];

if ($tableReady) {
    try {
        $summaryStmt = db()->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status_code BETWEEN 200 AND 299 THEN 1 ELSE 0 END) AS status_2xx,
                SUM(CASE WHEN status_code BETWEEN 400 AND 499 THEN 1 ELSE 0 END) AS status_4xx,
                SUM(CASE WHEN status_code BETWEEN 500 AND 599 THEN 1 ELSE 0 END) AS status_5xx,
                SUM(CASE WHEN status_code = 401 THEN 1 ELSE 0 END) AS status_401,
                SUM(CASE WHEN status_code = 403 THEN 1 ELSE 0 END) AS status_403,
                SUM(CASE WHEN status_code = 429 THEN 1 ELSE 0 END) AS status_429,
                COUNT(DISTINCT token_id) AS unique_tokens,
                COUNT(DISTINCT ip_address) AS unique_ips,
                ROUND(AVG(duration_ms), 0) AS avg_duration_ms
            FROM api_request_logs
            WHERE created_at >= (NOW() - INTERVAL {$windowSql})
        ");
        $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        foreach ($summary as $key => $value) {
            $summary[$key] = (int) ($summaryRow[$key] ?? 0);
        }

        $topStmt = db()->query("
            SELECT
                route_path,
                request_method,
                COUNT(*) AS hit_count,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_count
            FROM api_request_logs
            WHERE created_at >= (NOW() - INTERVAL {$windowSql})
            GROUP BY route_path, request_method
            ORDER BY hit_count DESC
            LIMIT 8
        ");
        $topEndpoints = $topStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $errorStmt = db()->query("
            SELECT
                created_at,
                route_path,
                request_method,
                status_code,
                error_code,
                ip_address
            FROM api_request_logs
            WHERE created_at >= (NOW() - INTERVAL {$windowSql})
              AND status_code >= 400
            ORDER BY id DESC
            LIMIT 20
        ");
        $recentErrors = $errorStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('api metrics page error: ' . $e->getMessage());
    }
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(api_lang('metrics_pretitle')); ?></div>
                <h2 class="page-title"><?php echo e(api_lang('metrics_title')); ?> (<?php echo e($windowLabel); ?>)</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <div class="btn-group" role="group" aria-label="Window Filter">
                        <a href="<?php echo base_url('api/metrics?window=1h'); ?>" class="btn <?php echo $window === '1h' ? 'btn-primary' : 'btn-outline-primary'; ?>"><?php echo e(api_lang('window_1h')); ?></a>
                        <a href="<?php echo base_url('api/metrics?window=24h'); ?>" class="btn <?php echo $window === '24h' ? 'btn-primary' : 'btn-outline-primary'; ?>"><?php echo e(api_lang('window_24h')); ?></a>
                        <a href="<?php echo base_url('api/metrics?window=7d'); ?>" class="btn <?php echo $window === '7d' ? 'btn-primary' : 'btn-outline-primary'; ?>"><?php echo e(api_lang('window_7d')); ?></a>
                    </div>
                    <a href="<?php echo base_url('api/actions/metrics-export?' . http_build_query(['window' => $window, 'format' => 'csv'])); ?>" class="btn btn-outline-secondary"><?php echo e(api_lang('export_csv')); ?></a>
                    <a href="<?php echo base_url('api/actions/metrics-export?' . http_build_query(['window' => $window, 'format' => 'xls'])); ?>" class="btn btn-outline-secondary"><?php echo e(api_lang('export_excel')); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!$tableReady): ?>
            <div class="alert alert-warning">
                <?php echo e(api_lang('table_missing')); ?>
            </div>
        <?php endif; ?>

        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-2">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary"><?php echo e(api_lang('total')); ?></div>
                        <div class="h1 m-0"><?php echo (int) $summary['total']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary">2xx</div>
                        <div class="h1 m-0 text-green"><?php echo (int) $summary['status_2xx']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary">4xx</div>
                        <div class="h1 m-0 text-yellow"><?php echo (int) $summary['status_4xx']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary">5xx</div>
                        <div class="h1 m-0 text-red"><?php echo (int) $summary['status_5xx']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary"><?php echo e(api_lang('token_unique')); ?></div>
                        <div class="h1 m-0"><?php echo (int) $summary['unique_tokens']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary"><?php echo e(api_lang('avg_ms')); ?></div>
                        <div class="h1 m-0"><?php echo (int) $summary['avg_duration_ms']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(api_lang('critical_codes')); ?> (<?php echo e($windowLabel); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-4">
                                <div class="text-secondary">401</div>
                                <div class="h2 m-0"><?php echo (int) $summary['status_401']; ?></div>
                            </div>
                            <div class="col-4">
                                <div class="text-secondary">403</div>
                                <div class="h2 m-0"><?php echo (int) $summary['status_403']; ?></div>
                            </div>
                            <div class="col-4">
                                <div class="text-secondary">429</div>
                                <div class="h2 m-0"><?php echo (int) $summary['status_429']; ?></div>
                            </div>
                            <div class="col-12 mt-2 text-secondary">
                                <?php echo e(api_lang('unique_ip')); ?>: <strong><?php echo (int) $summary['unique_ips']; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(api_lang('top_endpoints')); ?></h3>
                    </div>
                    <div class="table-responsive">
                        <table data-kirpi-table="report" data-table-title="API Endpoint Metrikleri" class="table table-vcenter card-table table-striped mb-0">
                            <thead>
                            <tr>
                                <th><?php echo e(api_lang('method')); ?></th>
                                <th><?php echo e(api_lang('path')); ?></th>
                                <th><?php echo e(api_lang('hit')); ?></th>
                                <th><?php echo e(api_lang('error')); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($topEndpoints)): ?>
                                <tr><td colspan="4" class="text-secondary text-center py-4"><?php echo e(api_lang('no_data')); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($topEndpoints as $row): ?>
                                    <tr>
                                        <td><code><?php echo e((string) ($row['request_method'] ?? 'GET')); ?></code></td>
                                        <td><code><?php echo e((string) ($row['route_path'] ?? '')); ?></code></td>
                                        <td><?php echo (int) ($row['hit_count'] ?? 0); ?></td>
                                        <td><?php echo (int) ($row['error_count'] ?? 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(api_lang('recent_errors')); ?> (<?php echo e($windowLabel); ?>)</h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="API Hataları" class="table table-vcenter card-table table-striped mb-0">
                    <thead>
                    <tr>
                        <th><?php echo e(api_lang('time')); ?></th>
                        <th><?php echo e(api_lang('method')); ?></th>
                        <th><?php echo e(api_lang('path')); ?></th>
                        <th><?php echo e(api_lang('status')); ?></th>
                        <th><?php echo e(api_lang('error_code')); ?></th>
                        <th><?php echo e(api_lang('ip')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentErrors)): ?>
                        <tr><td colspan="6" class="text-secondary text-center py-4"><?php echo e(api_lang('no_error_log')); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentErrors as $row): ?>
                            <tr>
                                <td><?php echo e((string) ($row['created_at'] ?? '')); ?></td>
                                <td><code><?php echo e((string) ($row['request_method'] ?? '')); ?></code></td>
                                <td><code><?php echo e((string) ($row['route_path'] ?? '')); ?></code></td>
                                <td><?php echo (int) ($row['status_code'] ?? 0); ?></td>
                                <td><code><?php echo e((string) ($row['error_code'] ?? '-')); ?></code></td>
                                <td><?php echo e((string) ($row['ip_address'] ?? '-')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
