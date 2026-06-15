<?php

$root = dirname(__DIR__);
$css = file_get_contents($root . '/assets/css/app.css');

$assertions = [
    'kirpi compatibility tokens map to tabler' => str_contains($css, '--kirpi-shell-bg: var(--tblr-body-bg)')
        && str_contains($css, '--kirpi-shell-accent: var(--tblr-primary)')
        && str_contains($css, '--kirpi-border: var(--tblr-border-color)'),
    'no parallel dark palette' => preg_match('/:root\[data-kirpi-theme="dark"\]\s*\{[^}]*--kirpi-shell-bg:/s', $css) !== 1,
    'native body surface' => str_contains($css, 'background-color: var(--tblr-body-bg);')
        && !str_contains($css, 'radial-gradient(circle at top left'),
    'native card surface' => !str_contains($css, '.kirpi-app-shell .page-wrapper .card {')
        && !str_contains($css, '.kirpi-app-shell .page-wrapper .card,'),
    'native secondary text' => !str_contains($css, '.kirpi-app-shell .page-wrapper .text-secondary'),
    'native code surface' => str_contains($css, 'background: var(--tblr-tertiary-bg);')
        && str_contains($css, 'color: var(--tblr-body-color);'),
];

$failed = array_keys(array_filter($assertions, static fn (bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, 'Tabler UI foundation contract failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'Tabler UI foundation contract passed (' . count($assertions) . ' assertions).' . PHP_EOL;
