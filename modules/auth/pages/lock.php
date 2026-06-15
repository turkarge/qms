<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/auth/language.php';

if (!is_user_logged_in()) {
    redirect(base_url('auth/login'));
}

if (!kirpi_session_lock_state()) {
    redirect(base_url(APP_DEFAULT_ROUTE));
}

$user = current_user();
$userName = (string) ($user['name'] ?? 'Kullanıcı');
$userRole = (string) ($user['role_name'] ?? '');
$initial = mb_strtoupper(mb_substr($userName, 0, 1));
$avatarUrl = !empty($user['avatar'])
    ? base_url('uploads/avatars/' . ltrim((string) $user['avatar'], '/'))
    : null;
?>
<!DOCTYPE html>
<html lang="<?php echo e(strtolower((string) env('APP_LOCALE', 'tr'))); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo e(app_name()); ?> - <?php echo e(auth_lang('lock_title')); ?></title>
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
        } catch (error) {
            document.documentElement.setAttribute('data-bs-theme', 'light');
            document.documentElement.setAttribute('data-kirpi-theme', 'light');
        }
    })();
    </script>
</head>
<body class="kirpi-lock-page">
<script>
window.KIRPI_CONFIG = {
    baseUrl: "<?php echo e(BASE_URL); ?>",
    csrfToken: "<?php echo e(get_csrf_token()); ?>"
};
</script>

<main class="kirpi-lock-shell">
    <section class="kirpi-lock-panel" aria-labelledby="lock-screen-title">
        <div class="kirpi-lock-identity">
            <div class="kirpi-lock-avatar-wrap">
                <?php if ($avatarUrl): ?>
                    <span class="avatar avatar-xl kirpi-lock-avatar" style="background-image: url('<?php echo e($avatarUrl); ?>')"></span>
                <?php else: ?>
                    <span class="avatar avatar-xl kirpi-lock-avatar"><?php echo e($initial); ?></span>
                <?php endif; ?>
                <span class="kirpi-lock-indicator" aria-hidden="true">
                    <i class="ti ti-lock"></i>
                </span>
            </div>

            <h1 id="lock-screen-title" class="h2 mb-1"><?php echo e($userName); ?></h1>
            <?php if ($userRole !== ''): ?>
                <div class="text-secondary mb-2"><?php echo e($userRole); ?></div>
            <?php endif; ?>
            <p class="text-secondary mb-0"><?php echo e(auth_lang('lock_info')); ?></p>
        </div>

        <form id="lock-pin-form" class="kirpi-lock-form" action="<?php echo base_url('auth/actions/unlock'); ?>" method="post" data-ajax="true" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
            <input type="hidden" name="lock_pin" data-lock-pin-value>

            <fieldset class="border-0 p-0 m-0">
                <legend class="visually-hidden"><?php echo e(auth_lang('lock_key_label')); ?></legend>
                <div class="kirpi-pin-inputs" data-lock-pin-inputs>
                    <?php for ($digit = 1; $digit <= 4; $digit++): ?>
                        <input
                            type="password"
                            class="form-control kirpi-pin-input"
                            inputmode="numeric"
                            pattern="[0-9]"
                            maxlength="1"
                            autocomplete="one-time-code"
                            aria-label="<?php echo e(str_replace(':digit', (string) $digit, auth_lang('lock_pin_digit'))); ?>"
                            data-lock-pin-digit
                            <?php echo $digit === 1 ? 'autofocus' : ''; ?>
                        >
                    <?php endfor; ?>
                </div>
            </fieldset>

            <div class="kirpi-lock-progress text-secondary" aria-live="polite" data-lock-pin-status></div>
            <button type="submit" class="visually-hidden" tabindex="-1"><?php echo e(auth_lang('unlock_button')); ?></button>
        </form>

        <form action="<?php echo base_url('auth/actions/logout'); ?>" method="post" data-ajax="true" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
            <button type="submit" class="btn btn-link link-secondary text-decoration-none px-2">
                <i class="ti ti-user-switch me-1"></i>
                <?php echo e(auth_lang('login_other_account')); ?>
            </button>
        </form>
    </section>
</main>

<script src="<?php echo asset_url('js/jquery-3.7.1.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/tabler.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/toastr.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/app.js'); ?>"></script>
<script src="<?php echo asset_url('js/lock-screen.js'); ?>"></script>
<!-- Cloudflare Web Analytics --><script defer src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='{"token": "7356366510c54c86a154d277ed978201"}'></script><!-- End Cloudflare Web Analytics -->
</body>
</html>
