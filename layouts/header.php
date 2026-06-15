<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

$user = current_user();
$flash = get_flash_message();

global $current_route;
$route_file = $current_route['file'] ?? null;
$page_script = resolve_page_script($route_file);
$isDocumentsPage = $route_file === 'modules/documents/pages/view.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo e(app_name()); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?php echo e(app_name()); ?>">
    <link rel="manifest" href="<?php echo base_url('manifest.webmanifest'); ?>">

    <link href="<?php echo asset_url('css/tabler.min.css'); ?>" rel="stylesheet"/>
    <link href="<?php echo asset_url('css/tabler-icons.min.css'); ?>" rel="stylesheet"/>
    <link href="<?php echo asset_url('css/app.css'); ?>" rel="stylesheet"/>
    <link href="<?php echo asset_url('css/toastr.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('vendor/datatables/css/dataTables.bootstrap5.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('vendor/datatables/css/buttons.bootstrap5.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('vendor/datatables/css/responsive.bootstrap5.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('vendor/datatables/css/select.bootstrap5.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('vendor/datatables/css/colReorder.bootstrap5.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('vendor/datatables/css/fixedHeader.bootstrap5.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('vendor/datatables/css/keyTable.bootstrap5.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/kirpi-table.css'); ?>" rel="stylesheet">
    <?php if ($isDocumentsPage): ?>
    <link href="<?php echo asset_url('vendor/filepond/filepond.min.css'); ?>" rel="stylesheet">
    <?php endif; ?>
    <script>
    (function () {
        try {
            const storedThemePreference = localStorage.getItem('kirpi_theme_preference') || 'system';
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = storedThemePreference === 'system'
                ? (prefersDark ? 'dark' : 'light')
                : storedThemePreference;
            const storedLayout = localStorage.getItem('kirpi_layout_width') || 'boxed';
            document.documentElement.setAttribute('data-bs-theme', theme);
            document.documentElement.setAttribute('data-kirpi-theme', theme);
            document.documentElement.setAttribute('data-kirpi-theme-preference', storedThemePreference);
            document.documentElement.setAttribute('data-kirpi-layout', storedLayout);
        } catch (error) {
            document.documentElement.setAttribute('data-bs-theme', 'light');
            document.documentElement.setAttribute('data-kirpi-theme', 'light');
            document.documentElement.setAttribute('data-kirpi-theme-preference', 'system');
            document.documentElement.setAttribute('data-kirpi-layout', 'boxed');
        }
    })();
    </script>
</head>

<body class="kirpi-app-shell">
<script>
window.KIRPI_CONFIG = {
    baseUrl: "<?php echo e(BASE_URL); ?>",
    csrfToken: "<?php echo e(get_csrf_token()); ?>",
    serviceWorkerVersion: "<?php echo e((string) (is_file(BASE_PATH . '/service-worker.js') ? filemtime(BASE_PATH . '/service-worker.js') : APP_VER)); ?>",
    flashMessage: <?php echo json_encode($flash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
};
</script>

<div class="page">
    <?php
    ob_start();
    require BASE_PATH . '/layouts/nav.php';
    $navOutput = (string) ob_get_clean();
    $navOutput = str_replace("\xEF\xBB\xBF", '', $navOutput);
    $navOutput = preg_replace('/\x{FEFF}/u', '', $navOutput) ?? $navOutput;
    $navOutput = ltrim($navOutput);
    echo $navOutput;
    ?>

    <div class="page-wrapper">
