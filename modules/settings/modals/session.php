<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/settings/language.php';

if (!function_exists('kirpi_mask_session_value')) {
    function kirpi_mask_session_value(mixed $value, string $path = ''): mixed
    {
        $sensitiveKeys = [
            'csrf_token',
            'password',
            'pass',
            'token',
            'secret',
            'api_key',
            'authorization',
        ];

        if (is_array($value)) {
            $masked = [];
            foreach ($value as $key => $item) {
                $keyString = strtolower((string) $key);
                $nextPath = $path === '' ? $keyString : ($path . '.' . $keyString);

                $isSensitive = false;
                foreach ($sensitiveKeys as $needle) {
                    if ($keyString === $needle || str_contains($keyString, $needle) || str_contains($nextPath, $needle)) {
                        $isSensitive = true;
                        break;
                    }
                }

                if ($isSensitive) {
                    $masked[$key] = '[MASKED]';
                    continue;
                }

                $masked[$key] = kirpi_mask_session_value($item, $nextPath);
            }

            return $masked;
        }

        if (is_object($value)) {
            return kirpi_mask_session_value((array) $value, $path);
        }

        return $value;
    }
}

$sessionData = kirpi_mask_session_value($_SESSION ?? []);
$jsonSession = json_encode($sessionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonSession === false) {
    $jsonSession = '{}';
}
?>

<div class="modal-header">
    <h5 class="modal-title"><?php echo e(settings_lang('session_modal_title')); ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
    <div class="alert alert-info mb-3">
        <?php echo e(settings_lang('session_mask_info')); ?>
    </div>

    <pre class="bg-dark-lt p-3 rounded mb-0" style="max-height: 60vh; overflow: auto;"><code><?php echo e($jsonSession); ?></code></pre>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(settings_lang('close')); ?></button>
</div>

