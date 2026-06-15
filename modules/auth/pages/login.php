<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/auth/language.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (is_user_logged_in()) {
    if (kirpi_session_lock_state()) {
        redirect(base_url('auth/lock'));
    }

    redirect(base_url(APP_DEFAULT_ROUTE));
}

$coverImage = AUTH_LOGIN_COVER_IMAGE !== ''
    ? AUTH_LOGIN_COVER_IMAGE
    : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1400&q=80';
?>
<!DOCTYPE html>
<html lang="<?php echo e(strtolower((string) env('APP_LOCALE', 'tr'))); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo e(app_name()); ?> - <?php echo e(auth_lang('login_title')); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="<?php echo asset_url('css/tabler.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/tabler-icons.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/app.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/toastr.min.css'); ?>" rel="stylesheet">
    <script>
    (function () {
        try {
            const preference = localStorage.getItem('kirpi_theme_preference') || 'system';
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = preference === 'system' ? (prefersDark ? 'dark' : 'light') : preference;
            document.documentElement.setAttribute('data-bs-theme', theme);
            document.documentElement.setAttribute('data-kirpi-theme', theme);
            document.documentElement.setAttribute('data-kirpi-theme-preference', preference);
        } catch (error) {
            document.documentElement.setAttribute('data-bs-theme', 'light');
            document.documentElement.setAttribute('data-kirpi-theme', 'light');
            document.documentElement.setAttribute('data-kirpi-theme-preference', 'system');
        }
    })();
    </script>
</head>
<body class="auth-cover-page">
<script>
window.KIRPI_CONFIG = {
    baseUrl: "<?php echo e(BASE_URL); ?>",
    csrfToken: "<?php echo e(get_csrf_token()); ?>"
};
</script>

<div class="auth-cover">
    <div class="auth-cover__form-side">
        <div class="auth-cover__form-wrap">
            <div class="text-center text-lg-start mb-4">
                <h1 class="navbar-brand navbar-brand-autodark mb-0 d-inline-flex align-items-center gap-2">
                    <img src="<?php echo asset_url('img/logo.svg'); ?>" alt="<?php echo e(app_name()); ?>" class="kirpi-auth-logo">
                    <span><?php echo e(app_name()); ?></span>
                </h1>
            </div>

            <h2 class="h1 mb-3"><?php echo e(auth_lang('login_heading')); ?></h2>

            <div id="login-alert-area"></div>

            <form
                id="login-form"
                action="<?php echo base_url('auth/actions/login'); ?>"
                method="post"
                data-ajax="true"
                novalidate
            >
                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">

                <div class="mb-3">
                    <label class="form-label"><?php echo e(auth_lang('email')); ?></label>
                    <input
                        type="email"
                        class="form-control"
                        name="email"
                        placeholder="<?php echo e(auth_lang('email_placeholder')); ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="mb-2">
                    <label class="form-label">
                        <?php echo e(auth_lang('password')); ?>
                        <span class="form-label-description">
                            <a href="<?php echo base_url('auth/forgot-password'); ?>"><?php echo e(auth_lang('forgot_password')); ?></a>
                        </span>
                    </label>

                    <div class="input-group input-group-flat">
                        <input
                            type="password"
                            class="form-control"
                            name="password"
                            id="login-password"
                            placeholder="<?php echo e(auth_lang('password_placeholder')); ?>"
                            autocomplete="current-password"
                            required
                        >
                        <span class="input-group-text p-0 pe-2">
                            <button
                                type="button"
                                class="btn btn-icon border-0 bg-transparent shadow-none auth-password-toggle"
                                id="toggle-login-password"
                                aria-label="<?php echo e(auth_lang('show_password')); ?>"
                                aria-controls="login-password"
                                aria-pressed="false"
                            >
                                <i class="ti ti-eye" aria-hidden="true"></i>
                            </button>
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="remember" value="1">
                        <span class="form-check-label"><?php echo e(auth_lang('remember_me')); ?></span>
                    </label>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100" id="login-submit-button">
                        <?php echo e(auth_lang('login_button')); ?>
                    </button>
                </div>
            </form>

            <div class="text-secondary mt-4">
                <?php echo e(auth_lang('terms_accept_prefix')); ?>
                <a href="<?php echo base_url('auth/terms'); ?>"><?php echo e(auth_lang('terms_accept_link')); ?></a>
                <?php echo e(auth_lang('terms_accept_suffix')); ?>
            </div>

            <div class="auth-theme-switcher mt-4">
                <div class="btn-group w-100" role="group" aria-label="Tema seçimi">
                    <button type="button" class="btn btn-outline-secondary" data-theme-choice="light">Light</button>
                    <button type="button" class="btn btn-outline-secondary" data-theme-choice="dark">Dark</button>
                    <button type="button" class="btn btn-outline-secondary" data-theme-choice="system">Sistem</button>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-cover__image-side">
        <div
            class="auth-cover__image"
            style="background-image: url('<?php echo e($coverImage); ?>');"
        ></div>
    </div>
</div>

<script src="<?php echo asset_url('js/jquery-3.7.1.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/tabler.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/toastr.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/app.js'); ?>"></script>
<script src="<?php echo base_url('modules/auth/scripts/login.js'); ?>"></script>
<!-- Cloudflare Web Analytics --><script defer src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='{"token": "7356366510c54c86a154d277ed978201"}'></script><!-- End Cloudflare Web Analytics -->
</body>
</html>
