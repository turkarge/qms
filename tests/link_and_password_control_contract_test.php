<?php

$root = dirname(__DIR__);
$css = file_get_contents($root . '/assets/css/app.css');
$login = file_get_contents($root . '/modules/auth/pages/login.php');
$script = file_get_contents($root . '/modules/auth/scripts/login.js');

$assertions = [
    'links never underline' => str_contains($css, "a:hover,\na:focus")
        && str_contains($css, 'text-decoration: none;'),
    'generic links inherit text color' => str_contains($css, 'a:not(.btn):not(.nav-link):not(.dropdown-item):not(.page-link)')
        && str_contains($css, 'color: inherit;'),
    'semantic link colors stay available' => str_contains($css, ':not([class*="text-"]):not([class*="link-"])'),
    'password toggle is a button' => str_contains($login, 'type="button"')
        && str_contains($login, 'id="toggle-login-password"')
        && !str_contains($login, '<a href="#" class="link-secondary" id="toggle-login-password"'),
    'password toggle is accessible' => str_contains($login, 'aria-controls="login-password"')
        && str_contains($login, 'aria-pressed="false"')
        && str_contains($script, 'setAttribute("aria-pressed"'),
    'password icon remains theme native' => str_contains($login, 'auth-password-toggle')
        && str_contains($css, 'color: var(--tblr-secondary-color);')
        && str_contains($css, 'background: transparent;'),
];

$failed = array_keys(array_filter($assertions, static fn (bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, 'Link and password control contract failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'Link and password control contract passed (' . count($assertions) . ' assertions).' . PHP_EOL;
