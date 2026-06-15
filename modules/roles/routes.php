<?php

return [
    'roles/view' => [
        'file' => 'modules/roles/pages/view.php',
        'layout' => true,
        'permission' => 'roles.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'roles/permissions' => [
        'file' => 'modules/roles/pages/permissions.php',
        'layout' => true,
        'permission' => 'roles.permissions',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/roles/datatable' => [
        'file' => 'modules/roles/actions/datatable.php',
        'layout' => false,
        'permission' => 'roles.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/roles/create' => [
        'file' => 'modules/roles/modals/create.php',
        'layout' => false,
        'permission' => 'roles.create',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/roles/edit' => [
        'file' => 'modules/roles/modals/edit.php',
        'layout' => false,
        'permission' => 'roles.edit',
        'auth' => true,
        'method' => 'GET',
    ],
    'roles/actions/export' => [
        'file' => 'modules/roles/actions/export.php',
        'layout' => false,
        'permission' => 'roles.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'roles/actions/create' => [
        'file' => 'modules/roles/actions/create.php',
        'layout' => false,
        'permission' => 'roles.create',
        'auth' => true,
        'method' => 'POST',
    ],
    'roles/actions/update' => [
        'file' => 'modules/roles/actions/update.php',
        'layout' => false,
        'permission' => 'roles.edit',
        'auth' => true,
        'method' => 'POST',
    ],
    'roles/actions/toggle-status' => [
        'file' => 'modules/roles/actions/toggle_status.php',
        'layout' => false,
        'permission' => 'roles.status',
        'auth' => true,
        'method' => 'POST',
    ],
    'roles/actions/permissions-update' => [
        'file' => 'modules/roles/actions/permissions_update.php',
        'layout' => false,
        'permission' => 'roles.permissions',
        'auth' => true,
        'method' => 'POST',
    ],
];
