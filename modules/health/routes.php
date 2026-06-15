<?php

return [
    'health/view' => [
        'file' => 'modules/health/pages/view.php',
        'layout' => true,
        'permission' => 'health.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'health/env' => [
        'file' => 'modules/health/pages/env.php',
        'layout' => true,
        'permission' => 'health.env.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'health/actions/export' => [
        'file' => 'modules/health/actions/export.php',
        'layout' => false,
        'permission' => 'health.view',
        'auth' => true,
        'method' => 'GET',
    ],
];
