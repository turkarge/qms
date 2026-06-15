<?php

$root = dirname(__DIR__);
$profile = file_get_contents($root . '/modules/profile/pages/view.php');
$health = file_get_contents($root . '/modules/health/pages/view.php');
$queue = file_get_contents($root . '/modules/queue/pages/view.php');
$security = file_get_contents($root . '/modules/security/pages/view.php');
$modules = file_get_contents($root . '/modules/settings/pages/modules.php');
$menus = file_get_contents($root . '/modules/settings/pages/menu_management.php');

$assertions = [
    'profile has no nested shadowless cards' => !str_contains($profile, 'card border-0 shadow-none')
        && !str_contains($profile, "'border-0 shadow-none'"),
    'profile uses native tabs' => str_contains($profile, 'nav nav-tabs card-header-tabs')
        && str_contains($profile, 'tab-content'),
    'health uses one table toolbar' => str_contains($health, 'data-kirpi-table="report"')
        && !str_contains($health, 'health/actions/export?format=csv')
        && !str_contains($health, 'health/actions/export?format=xls'),
    'queue uses one table toolbar' => str_contains($queue, 'data-kirpi-table="standard"')
        && !str_contains($queue, 'queue/actions/export?format=csv')
        && !str_contains($queue, 'queue/actions/export?format=xls'),
    'security tables use standard component' => substr_count($security, 'data-kirpi-table="standard"') >= 2,
    'settings tables use standard component' => str_contains($modules, 'data-kirpi-table="standard"')
        && substr_count($menus, 'data-kirpi-table="report"') === 3,
];

$failed = array_keys(array_filter($assertions, static fn (bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, 'Tabler critical pages contract failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'Tabler critical pages contract passed (' . count($assertions) . ' assertions).' . PHP_EOL;
