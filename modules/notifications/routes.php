<?php

return [
    'notifications/list' => [
        'file' => 'modules/notifications/pages/list.php',
        'layout' => true,
        'permission' => 'notifications.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'notifications/settings' => [
        'file' => 'modules/notifications/pages/settings.php',
        'layout' => true,
        'permission' => 'notifications.settings',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/notifications/datatable' => [
        'file' => 'modules/notifications/actions/datatable.php',
        'layout' => false,
        'permission' => 'notifications.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'notifications/actions/export' => [
        'file' => 'modules/notifications/actions/export.php',
        'layout' => false,
        'permission' => 'notifications.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'notifications/actions/mark-read' => [
        'file' => 'modules/notifications/actions/mark_read.php',
        'layout' => false,
        'permission' => 'notifications.view',
        'auth' => true,
        'method' => 'POST',
    ],
    'notifications/actions/mark-all-read' => [
        'file' => 'modules/notifications/actions/mark_all_read.php',
        'layout' => false,
        'permission' => 'notifications.view',
        'auth' => true,
        'method' => 'POST',
    ],
    'notifications/actions/settings-update' => [
        'file' => 'modules/notifications/actions/settings_update.php',
        'layout' => false,
        'permission' => 'notifications.settings',
        'auth' => true,
        'method' => 'POST',
    ],
];
