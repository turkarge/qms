<?php

$root = dirname(__DIR__);
$nav = file_get_contents($root . '/layouts/nav.php');
$app = file_get_contents($root . '/assets/js/app.js');
$css = file_get_contents($root . '/assets/css/app.css');

$assertions = [
    'native tabler header' => str_contains($nav, '<header class="navbar navbar-expand-md d-print-none">'),
    'native collapse trigger' => str_contains($nav, 'data-bs-toggle="collapse" data-bs-target="#navbar-menu"')
        && !str_contains($nav, 'js-mobile-nav-toggle'),
    'native brand' => str_contains($nav, 'navbar-brand navbar-brand-autodark me-3')
        && str_contains($nav, 'navbar-brand-image me-2'),
    'native mobile navigation' => !str_contains($nav, 'js-mobile-nav-close')
        && !str_contains($app, 'initMobileNavigation')
        && !str_contains($css, 'mobile-nav-open'),
    'native navbar surfaces' => !str_contains($css, '.kirpi-app-shell .navbar {')
        && !str_contains($css, '.navbar .dropdown-toggle::after')
        && !str_contains($css, '.kirpi-brand-logo'),
    'native notification badge' => str_contains($nav, 'badge badge-sm bg-red text-red-fg ms-1')
        && !str_contains($nav, 'position-absolute top-0 start-100 translate-middle'),
    'native notification actions' => str_contains($nav, 'class="link-secondary js-notification-mark-all')
        && str_contains($nav, 'class="list-group-item-actions js-notification-mark-read"'),
    'read hover has no custom background' => str_contains($css, '.js-notification-mark-read:hover i')
        && !str_contains($css, '.js-notification-mark-read:hover {'),
    'notification item keeps text color' => str_contains($nav, 'text-reset text-decoration-none d-block text-truncate js-notification-open')
        && !str_contains($nav, 'text-body d-block text-truncate js-notification-open'),
    'official lock icon' => str_contains($nav, 'icon-tabler-lock')
        && str_contains($nav, 'stroke="currentColor"')
        && !str_contains($nav, 'ti ti-user-key'),
    'single nested menu caret' => !str_contains($nav, 'ti ti-chevron-right opacity-75'),
    'auth logo has scoped size' => str_contains($css, '.kirpi-auth-logo')
        && str_contains($css, 'height: 28px;'),
];

$failed = array_keys(array_filter($assertions, static fn (bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, 'Navbar Tabler contract failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'Navbar Tabler contract passed (' . count($assertions) . ' assertions).' . PHP_EOL;
