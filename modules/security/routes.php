<?php

return [
    'security/view' => [
        'file' => 'modules/security/pages/view.php',
        'layout' => true,
        'permission' => 'security.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'security/actions/export' => [
        'file' => 'modules/security/actions/export.php',
        'layout' => false,
        'permission' => 'security.view',
        'auth' => true,
        'method' => 'GET',
    ],
];
