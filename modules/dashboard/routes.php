<?php

return [
    'dashboard/view' => [
        'file' => 'modules/dashboard/pages/view.php',
        'layout' => true,
        'permission' => null,
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/dashboard/about' => [
        'file' => 'modules/dashboard/modals/about.php',
        'layout' => false,
        'permission' => null,
        'auth' => true,
        'method' => 'GET',
    ],
];