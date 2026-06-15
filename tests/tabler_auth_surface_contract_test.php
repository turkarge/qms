<?php

$root = dirname(__DIR__);
$css = file_get_contents($root . '/assets/css/app.css');
$login = file_get_contents($root . '/modules/auth/pages/login.php');
$forgot = file_get_contents($root . '/modules/auth/pages/forgot_password.php');
$reset = file_get_contents($root . '/modules/auth/pages/reset_password.php');
$lock = file_get_contents($root . '/modules/auth/pages/lock.php');

$assertions = [
    'login surface uses tabler token' => str_contains($css, 'background: var(--tblr-bg-surface, var(--tblr-body-bg));')
        && !str_contains($css, '.auth-cover__form-side {\n    width: 100%;\n    max-width: 50%;\n    min-height: 100vh;\n    background: #fff;'),
    'login image fallback uses tabler token' => str_contains($css, 'background: var(--tblr-tertiary-bg);'),
    'theme buttons use native active state' => !str_contains($css, '.auth-theme-switcher .btn.active'),
    'login loads tabler assets' => str_contains($login, "asset_url('css/tabler.min.css')")
        && str_contains($login, 'auth-theme-switcher'),
    'password pages use shared auth logo' => str_contains($forgot, 'kirpi-auth-logo')
        && str_contains($reset, 'kirpi-auth-logo'),
    'lock screen uses tabler theme' => str_contains($lock, "setAttribute('data-bs-theme', theme)")
        && str_contains($lock, 'kirpi-pin-inputs'),
];

$failed = array_keys(array_filter($assertions, static fn (bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, 'Tabler auth surface contract failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'Tabler auth surface contract passed (' . count($assertions) . ' assertions).' . PHP_EOL;
