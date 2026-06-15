<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/auth/language.php';

if (is_user_logged_in()) {
    redirect(base_url(APP_DEFAULT_ROUTE));
}
?>
<!DOCTYPE html>
<html lang="<?php echo e(strtolower((string) env('APP_LOCALE', 'tr'))); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo e(app_name()); ?> - <?php echo e(auth_lang('forgot_title')); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="<?php echo asset_url('css/tabler.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/tabler-icons.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/app.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/toastr.min.css'); ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column">
<script>
window.KIRPI_CONFIG = {
    baseUrl: "<?php echo e(BASE_URL); ?>",
    csrfToken: "<?php echo e(get_csrf_token()); ?>"
};
</script>
<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <h1 class="navbar-brand navbar-brand-autodark d-inline-flex align-items-center gap-2">
                <img src="<?php echo asset_url('img/logo.svg'); ?>" alt="<?php echo e(app_name()); ?>" class="kirpi-auth-logo">
                <span><?php echo e(app_name()); ?></span>
            </h1>
        </div>

        <form
            id="forgot-password-form"
            class="card card-md"
            action="<?php echo base_url('auth/actions/forgot-password'); ?>"
            method="post"
            data-ajax="true"
            autocomplete="off"
            novalidate
        >
            <div class="card-body">
                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                <h2 class="card-title text-center mb-4"><?php echo e(auth_lang('forgot_heading')); ?></h2>
                <p class="text-secondary mb-4">
                    <?php echo e(auth_lang('forgot_description')); ?>
                </p>
                <div id="forgot-password-alert" class="mb-3" style="display:none;"></div>

                <div class="mb-3">
                    <label class="form-label"><?php echo e(auth_lang('email')); ?></label>
                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="<?php echo e(auth_lang('email_placeholder')); ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        <?php echo e(auth_lang('forgot_send')); ?>
                    </button>
                </div>
            </div>
        </form>

        <div class="text-center text-secondary mt-3">
            <a href="<?php echo base_url('auth/login'); ?>"><?php echo e(auth_lang('back_to_login')); ?></a>
        </div>
    </div>
</div>
<script src="<?php echo asset_url('js/jquery-3.7.1.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/tabler.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/toastr.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/app.js'); ?>"></script>
<script>
document.addEventListener('kirpi:form.success', function (event) {
    const form = event.detail?.form;
    if (!form || form.id !== 'forgot-password-form') {
        return;
    }

    const result = event.detail?.result || {};
    const alertBox = document.getElementById('forgot-password-alert');
    if (!alertBox) {
        return;
    }

    const typeMap = {
        success: 'alert-success',
        error: 'alert-danger',
        warning: 'alert-warning',
        info: 'alert-info'
    };

    const cls = typeMap[result.status] || 'alert-info';
    alertBox.className = 'alert ' + cls + ' mb-3';
    alertBox.textContent = result.message || '';
    alertBox.style.display = result.message ? 'block' : 'none';
});
</script>
<!-- Cloudflare Web Analytics --><script defer src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='{"token": "7356366510c54c86a154d277ed978201"}'></script><!-- End Cloudflare Web Analytics -->
</body>
</html>
