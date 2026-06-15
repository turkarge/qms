<?php

return [
    'audit/overview' => [
        'file' => 'modules/audit/pages/overview.php',
        'layout' => true,
        'permission' => 'audit.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'audit/list' => [
        'file' => 'modules/audit/pages/list.php',
        'layout' => true,
        'permission' => 'audit.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/audit/datatable' => [
        'file' => 'modules/audit/actions/datatable.php',
        'layout' => false,
        'permission' => 'audit.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'audit/actions/export' => [
        'file' => 'modules/audit/actions/export.php',
        'layout' => false,
        'permission' => 'audit.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'audit/actions/overview-export' => [
        'file' => 'modules/audit/actions/overview_export.php',
        'layout' => false,
        'permission' => 'audit.view',
        'auth' => true,
        'method' => 'GET',
    ],
];
