<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/health/language.php';

function kirpi_health_env_sensitive_key(string $key): bool
{
    $normalized = strtoupper($key);
    $patterns = [
        'PASS',
        'PASSWORD',
        'SECRET',
        'TOKEN',
        'API_KEY',
        'ACCESS_KEY',
        'PRIVATE_KEY',
        'CREDENTIAL',
        'AUTH',
        'SETUP_KEY',
        'APP_KEY',
        'COOKIE',
        'SESSION',
        'DSN',
        'CERT',
    ];

    foreach ($patterns as $pattern) {
        if (str_contains($normalized, $pattern)) {
            return true;
        }
    }

    return false;
}

function kirpi_health_env_mask_value(string $value): string
{
    if ($value === '') {
        return '';
    }

    return str_repeat('*', min(12, max(8, strlen($value))));
}

function kirpi_health_env_collect(): array
{
    $items = [];
    $add = static function (string $key, mixed $value, string $source) use (&$items): void {
        $key = trim($key);
        if ($key === '' || is_array($value) || is_object($value)) {
            return;
        }

        $value = (string) $value;
        if (!isset($items[$key])) {
            $items[$key] = [
                'key' => $key,
                'value' => $value,
                'sources' => [],
                'masked' => kirpi_health_env_sensitive_key($key),
            ];
        }

        if (!in_array($source, $items[$key]['sources'], true)) {
            $items[$key]['sources'][] = $source;
        }
    };

    $getenvValues = getenv();
    if (is_array($getenvValues)) {
        foreach ($getenvValues as $key => $value) {
            $add((string) $key, $value, 'getenv');
        }
    }

    foreach ($_ENV as $key => $value) {
        $add((string) $key, $value, '$_ENV');
    }

    foreach ($_SERVER as $key => $value) {
        $serverKey = (string) $key;
        if (str_starts_with($serverKey, 'HTTP_') || in_array($serverKey, ['REQUEST_URI', 'QUERY_STRING', 'SCRIPT_NAME', 'PHP_SELF'], true)) {
            continue;
        }

        $add($serverKey, $value, '$_SERVER');
    }

    ksort($items, SORT_NATURAL | SORT_FLAG_CASE);

    return array_values($items);
}

$envItems = kirpi_health_env_collect();
$maskedCount = count(array_filter($envItems, static fn (array $item): bool => !empty($item['masked'])));
$visibleCount = max(0, count($envItems) - $maskedCount);
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(health_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(health_lang('env_reader')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(health_lang('env_reader_detail')); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards mb-3">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(health_lang('env_count')); ?></div>
                        <div class="h1 mb-0"><?php echo count($envItems); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(health_lang('masked')); ?></div>
                        <div class="h1 mb-0"><?php echo $maskedCount; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader"><?php echo e(health_lang('visible')); ?></div>
                        <div class="h1 mb-0"><?php echo $visibleCount; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table data-kirpi-table="standard" data-table-title="Ortam Değişkenleri" class="table table-vcenter card-table table-striped">
                    <thead>
                    <tr>
                        <th><?php echo e(health_lang('key')); ?></th>
                        <th><?php echo e(health_lang('value')); ?></th>
                        <th><?php echo e(health_lang('source')); ?></th>
                        <th><?php echo e(health_lang('status')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($envItems)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4"><?php echo e(health_lang('no_env_item')); ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($envItems as $item): ?>
                        <?php
                        $isMasked = !empty($item['masked']);
                        $value = $isMasked ? kirpi_health_env_mask_value((string) $item['value']) : (string) $item['value'];
                        ?>
                        <tr>
                            <td><code><?php echo e((string) $item['key']); ?></code></td>
                            <td class="text-break"><code><?php echo e($value); ?></code></td>
                            <td>
                                <?php foreach ((array) $item['sources'] as $source): ?>
                                    <span class="badge bg-blue-lt me-1"><?php echo e((string) $source); ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if ($isMasked): ?>
                                    <span class="badge bg-yellow-lt"><?php echo e(health_lang('masked')); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-green-lt"><?php echo e(health_lang('visible')); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
