<?php

return [
    'users/view' => [
        'file' => 'modules/users/pages/view.php',
        'layout' => true,
        'permission' => 'users.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/users/create' => [
        'file' => 'modules/users/modals/create.php',
        'layout' => false,
        'permission' => 'users.create',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/users/edit' => [
        'file' => 'modules/users/modals/edit.php',
        'layout' => false,
        'permission' => 'users.edit',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/users/datatable' => [
        'file' => 'modules/users/actions/datatable.php',
        'layout' => false,
        'permission' => 'users.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'users/actions/export' => [
        'file' => 'modules/users/actions/export.php',
        'layout' => false,
        'permission' => 'users.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'users/actions/create' => [
        'file' => 'modules/users/actions/create.php',
        'layout' => false,
        'permission' => 'users.create',
        'auth' => true,
        'method' => 'POST',
    ],
    'users/actions/update' => [
        'file' => 'modules/users/actions/update.php',
        'layout' => false,
        'permission' => 'users.edit',
        'auth' => true,
        'method' => 'POST',
    ],
    'users/actions/toggle-status' => [
        'file' => 'modules/users/actions/toggle_status.php',
        'layout' => false,
        'permission' => 'users.status',
        'auth' => true,
        'method' => 'POST',
    ],
    'users/actions/drop-session' => [
        'file' => 'modules/users/actions/drop_session.php',
        'layout' => false,
        'permission' => 'users.session.drop',
        'auth' => true,
        'method' => 'POST',
    ],
    'users/actions/reset-lock-key' => [
        'file' => 'modules/users/actions/reset_lock_key.php',
        'layout' => false,
        'permission' => 'users.lock.reset',
        'auth' => true,
        'method' => 'POST',
    ],
];
