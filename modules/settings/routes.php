<?php

return [
    'settings/view' => [
        'file' => 'modules/settings/pages/view.php',
        'layout' => true,
        'permission' => 'settings.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'settings/api-test' => [
        'file' => 'modules/settings/pages/api_test.php',
        'layout' => true,
        'permission' => 'settings.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'settings/modules' => [
        'file' => 'modules/settings/pages/modules.php',
        'layout' => true,
        'permission' => 'settings.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'settings/menu-management' => [
        'file' => 'modules/settings/pages/menu_management.php',
        'layout' => true,
        'permission' => 'settings.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/settings/session' => [
        'file' => 'modules/settings/modals/session.php',
        'layout' => false,
        'permission' => 'settings.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'settings/actions/export' => [
        'file' => 'modules/settings/actions/export.php',
        'layout' => false,
        'permission' => 'settings.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'settings/actions/update' => [
        'file' => 'modules/settings/actions/update.php',
        'layout' => false,
        'permission' => 'settings.update',
        'auth' => true,
        'method' => 'POST',
    ],
    'settings/actions/install-missing' => [
        'file' => 'modules/settings/actions/install_missing.php',
        'layout' => false,
        'permission' => 'settings.update',
        'auth' => true,
        'method' => 'POST',
    ],
    'settings/actions/module-toggle' => [
        'file' => 'modules/settings/actions/module_toggle.php',
        'layout' => false,
        'permission' => 'settings.update',
        'auth' => true,
        'method' => 'POST',
    ],
];
