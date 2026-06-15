<?php

return [
    'template/email' => [
        'file' => 'modules/template/pages/templates.php',
        'layout' => true,
        'permission' => 'template.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'template/print' => [
        'file' => 'modules/template/pages/templates.php',
        'layout' => true,
        'permission' => 'template.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'template/content' => [
        'file' => 'modules/template/pages/templates.php',
        'layout' => true,
        'permission' => 'template.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'template/templates' => [
        'file' => 'modules/template/pages/templates.php',
        'layout' => true,
        'permission' => 'template.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'template/actions/export' => [
        'file' => 'modules/template/actions/export.php',
        'layout' => false,
        'permission' => 'template.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'template/actions/create' => [
        'file' => 'modules/template/actions/create.php',
        'layout' => false,
        'permission' => 'template.manage',
        'auth' => true,
        'method' => 'POST',
    ],
    'template/actions/update' => [
        'file' => 'modules/template/actions/update.php',
        'layout' => false,
        'permission' => 'template.manage',
        'auth' => true,
        'method' => 'POST',
    ],
    'template/actions/toggle-status' => [
        'file' => 'modules/template/actions/toggle_status.php',
        'layout' => false,
        'permission' => 'template.manage',
        'auth' => true,
        'method' => 'POST',
    ],
];
