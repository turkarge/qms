<?php

$root = dirname(__DIR__);
$requiredAssets = [
    'assets/vendor/datatables/js/dataTables.min.js',
    'assets/vendor/datatables/js/dataTables.bootstrap5.min.js',
    'assets/vendor/datatables/js/dataTables.buttons.min.js',
    'assets/vendor/datatables/js/dataTables.responsive.min.js',
    'assets/vendor/datatables/js/dataTables.select.min.js',
    'assets/vendor/datatables/js/dataTables.colReorder.min.js',
    'assets/vendor/datatables/js/dataTables.fixedHeader.min.js',
    'assets/vendor/datatables/js/dataTables.keyTable.min.js',
    'assets/js/kirpi-table.js',
    'assets/css/kirpi-table.css',
];

foreach ($requiredAssets as $asset) {
    $path = $root . '/' . $asset;
    if (!is_file($path) || filesize($path) === 0) {
        fwrite(STDERR, "Missing DataTables asset: {$asset}\n");
        exit(1);
    }
}

$routeSource = file_get_contents($root . '/modules/users/routes.php');
$endpointSource = file_get_contents($root . '/modules/users/actions/datatable.php');
$scriptSource = file_get_contents($root . '/modules/users/scripts/view.js');
$pageSource = file_get_contents($root . '/modules/users/pages/view.php');

if (!str_contains((string) $routeSource, "'ajax/users/datatable'")) {
    fwrite(STDERR, "Users DataTables route is missing.\n");
    exit(1);
}

foreach (['kirpi_table_request', 'kirpi_table_response', ':global_name', ':global_email', ':global_role'] as $token) {
    if (!str_contains((string) $endpointSource, $token)) {
        fwrite(STDERR, "Users DataTables response contract is missing {$token}.\n");
        exit(1);
    }
}

$helperSource = file_get_contents($root . '/core/kirpi_table.php');
foreach (['recordsTotal', 'recordsFiltered', 'kirpi_table_order_sql', 'kirpi_table_bind'] as $token) {
    if (!str_contains((string) $helperSource, $token)) {
        fwrite(STDERR, "Core KirpiTable server contract is missing {$token}.\n");
        exit(1);
    }
}

foreach (['DataTable.render.select()', 'columnFilters', 'serverExport', 'stateKey'] as $token) {
    if (!str_contains((string) $scriptSource, $token)) {
        fwrite(STDERR, "Users table configuration is missing {$token}.\n");
        exit(1);
    }
}

foreach (['users-role-filter', 'users-status-filter', 'users-filter-reset'] as $removedControl) {
    if (str_contains((string) $pageSource, $removedControl)) {
        fwrite(STDERR, "Redundant external filter remains: {$removedControl}.\n");
        exit(1);
    }
}

if (!str_contains((string) $pageSource, 'ti ti-settings')) {
    fwrite(STDERR, "Actions column settings icon is missing.\n");
    exit(1);
}

if (!str_contains((string) $scriptSource, 'js-kirpi-row-menu') || str_contains((string) $scriptSource, 'data-bs-toggle="dropdown"')) {
    fwrite(STDERR, "Controlled row actions dropdown contract is invalid.\n");
    exit(1);
}

$kirpiTableSource = file_get_contents($root . '/assets/js/kirpi-table.js');
$kirpiTableCss = file_get_contents($root . '/assets/css/kirpi-table.css');
if (!str_contains((string) $kirpiTableSource, '.kirpi-row-actions .dropdown-item')
    || !str_contains((string) $kirpiTableSource, 'getOrCreateInstance(actionToggle).hide()')) {
    fwrite(STDERR, "Row actions dropdown does not close before action handling.\n");
    exit(1);
}

foreach (['background-color: var(--tblr-bg-surface) !important', 'div.dt-button-collection .dt-button:hover', 'div.dt-button-collection .dt-button.dt-button-active'] as $token) {
    if (!str_contains((string) $kirpiTableCss, $token)) {
        fwrite(STDERR, "DataTables collection theme contract is missing {$token}.\n");
        exit(1);
    }
}

fwrite(STDOUT, "Users DataTables contract tests passed.\n");
