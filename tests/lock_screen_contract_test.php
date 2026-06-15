<?php

$root = dirname(__DIR__);
$page = file_get_contents($root . '/modules/auth/pages/lock.php');
$script = file_get_contents($root . '/assets/js/lock-screen.js');
$css = file_get_contents($root . '/assets/css/app.css');
$action = file_get_contents($root . '/modules/auth/actions/unlock.php');

$assertions = [
    'four pin fields' => substr_count($page, 'data-lock-pin-digit') === 1
        && str_contains($page, 'for ($digit = 1; $digit <= 4; $digit++)'),
    'hidden backend value' => str_contains($page, 'name="lock_pin" data-lock-pin-value'),
    'numeric single digit input' => str_contains($page, 'inputmode="numeric"')
        && str_contains($page, 'maxlength="1"'),
    'automatic submit' => str_contains($script, 'pin.length !== inputs.length')
        && str_contains($script, 'form.requestSubmit()'),
    'paste support' => str_contains($script, 'clipboardData')
        && str_contains($script, 'addEventListener("paste"'),
    'error reset' => str_contains($script, 'kirpi:form.error')
        && str_contains($script, 'clearPin()'),
    'responsive stable dimensions' => str_contains($css, 'grid-template-columns: repeat(4, 3.5rem)')
        && str_contains($css, '@media (max-width: 359.98px)'),
    'backend contract unchanged' => str_contains($action, "\$_POST['lock_pin']")
        && str_contains($action, "preg_match('/^\\d{4}$/', \$pin)"),
    'theme preference' => str_contains($page, "localStorage.getItem('kirpi_theme_preference')")
        && str_contains($page, "setAttribute('data-bs-theme', theme)"),
];

$failed = array_keys(array_filter($assertions, static fn (bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, 'Lock screen contract failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'Lock screen contract passed (' . count($assertions) . ' assertions).' . PHP_EOL;
