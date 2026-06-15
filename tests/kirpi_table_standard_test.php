<?php

$root = dirname(__DIR__);
$header = file_get_contents($root . '/layouts/header.php');
$footer = file_get_contents($root . '/layouts/footer.php');
$script = file_get_contents($root . '/assets/js/kirpi-table.js');

foreach ([
    'vendor/datatables/css/dataTables.bootstrap5.min.css',
    'css/kirpi-table.css',
] as $asset) {
    if (!str_contains((string) $header, $asset)) {
        fwrite(STDERR, "Global KirpiTable style is missing: {$asset}.\n");
        exit(1);
    }
}

foreach ([
    'vendor/datatables/js/dataTables.min.js',
    'vendor/datatables/js/dataTables.responsive.min.js',
    'js/kirpi-table.js',
] as $asset) {
    if (!str_contains((string) $footer, $asset)) {
        fwrite(STDERR, "Global KirpiTable script is missing: {$asset}.\n");
        exit(1);
    }
}

foreach (['standard', 'report', 'compact', 'matrix'] as $profile) {
    if (!str_contains((string) $script, "profile === \"{$profile}\"") && $profile !== 'standard') {
        fwrite(STDERR, "KirpiTable profile is missing: {$profile}.\n");
        exit(1);
    }
}

foreach (['MutationObserver', 'data-kirpi-table', 'tbody td[colspan]', 'serverSide: false'] as $token) {
    if (!str_contains((string) $script, $token)) {
        fwrite(STDERR, "KirpiTable standard contract is missing {$token}.\n");
        exit(1);
    }
}

foreach (['kirpi-table-tool', 'kirpi-table-toolbar', 'input-group kirpi-table-control', 'new DataTable.Buttons', 'table.buttons(0, null).container()', 'top: enableButtons ? toolbar', 'toolbarSearch.addEventListener', 'search: ""', 'element.closest(".card")?.classList.add("kirpi-table-card")', 'window.location.reload()'] as $token) {
    if (!str_contains((string) $script, $token)) {
        fwrite(STDERR, "KirpiTable toolbar contract is missing {$token}.\n");
        exit(1);
    }
}

$settingsModules = file_get_contents($root . '/modules/settings/pages/modules.php');
$settingsMenus = file_get_contents($root . '/modules/settings/pages/menu_management.php');
$securityPage = file_get_contents($root . '/modules/security/pages/view.php');
foreach ([$settingsModules, $settingsMenus, $securityPage] as $page) {
    if (str_contains((string) $page, 'ti-file-type-csv') || str_contains((string) $page, 'ti-file-spreadsheet')) {
        fwrite(STDERR, "Duplicate page-level table export control remains.\n");
        exit(1);
    }
}
if (substr_count((string) $securityPage, 'data-kirpi-table="standard"') < 2) {
    fwrite(STDERR, "Security monitoring tables do not use the standard KirpiTable profile.\n");
    exit(1);
}

foreach (['users', 'roles', 'audit', 'notifications'] as $module) {
    $route = file_get_contents($root . "/modules/{$module}/routes.php");
    $action = file_get_contents($root . "/modules/{$module}/actions/datatable.php");
    $page = file_get_contents($root . "/modules/{$module}/pages/" . ($module === 'audit' || $module === 'notifications' ? 'list.php' : 'view.php'));
    if (!str_contains((string) $route, "ajax/{$module}/datatable")
        || !str_contains((string) $action, 'kirpi_table_request')
        || !str_contains((string) $page, 'kirpi-data-table')) {
        fwrite(STDERR, "Server-side KirpiTable contract is incomplete: {$module}.\n");
        exit(1);
    }
}

if (!is_file($root . '/tests/kirpi_table_endpoint_smoke.php')) {
    fwrite(STDERR, "KirpiTable database endpoint smoke test is missing.\n");
    exit(1);
}

$pageFiles = glob($root . '/modules/*/pages/*.php') ?: [];
$unmarked = [];
foreach ($pageFiles as $file) {
    $source = file_get_contents($file);
    if (!str_contains((string) $source, '<table')) {
        continue;
    }
    if (preg_match_all('/<table\b[^>]*>/', (string) $source, $matches) === false) {
        continue;
    }
    foreach ($matches[0] as $tableTag) {
        if (!str_contains($tableTag, 'data-kirpi-table=') && !str_contains($tableTag, 'kirpi-data-table')) {
            $unmarked[] = str_replace($root . DIRECTORY_SEPARATOR, '', $file);
        }
    }
}

if ($unmarked) {
    fwrite(STDERR, "Unmarked Core page tables: " . implode(', ', array_unique($unmarked)) . "\n");
    exit(1);
}

fwrite(STDOUT, "KirpiTable standard tests passed.\n");
