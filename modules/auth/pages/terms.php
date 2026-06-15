<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/auth/language.php';
?>
<!DOCTYPE html>
<html lang="<?php echo e(strtolower((string) env('APP_LOCALE', 'tr'))); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo e(app_name()); ?> - <?php echo e(auth_lang('terms_title')); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="<?php echo asset_url('css/tabler.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/tabler-icons.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/app.css'); ?>" rel="stylesheet">
</head>
<body>
<div class="page">
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <h2 class="page-title"><?php echo e(auth_lang('terms_title')); ?></h2>
                        <div class="text-secondary mt-1"><?php echo e(app_name()); ?></div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="<?php echo base_url('auth/login'); ?>" class="btn btn-primary"><?php echo e(auth_lang('back_to_login_button')); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="card-body">
                        <h3><?php echo e(auth_lang('terms_h1')); ?></h3>
                        <p>
                            <?php echo e(auth_lang('terms_p1')); ?>
                        </p>

                        <h3><?php echo e(auth_lang('terms_h2')); ?></h3>
                        <p>
                            <?php echo e(auth_lang('terms_p2')); ?>
                        </p>

                        <h3><?php echo e(auth_lang('terms_h3')); ?></h3>
                        <p>
                            <?php echo e(auth_lang('terms_p3')); ?>
                        </p>

                        <h3><?php echo e(auth_lang('terms_h4')); ?></h3>
                        <p class="mb-0">
                            <?php echo e(auth_lang('terms_p4')); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Cloudflare Web Analytics --><script defer src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='{"token": "7356366510c54c86a154d277ed978201"}'></script><!-- End Cloudflare Web Analytics -->
</body>
</html>
