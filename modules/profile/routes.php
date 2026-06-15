<?php

return [
    'profile/view' => [
        'file' => 'modules/profile/pages/view.php',
        'layout' => true,
        'permission' => 'profile.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'profile/actions/update' => [
        'file' => 'modules/profile/actions/update.php',
        'layout' => false,
        'permission' => 'profile.edit',
        'auth' => true,
        'method' => 'POST',
    ],
    'profile/actions/create-api-token' => [
        'file' => 'modules/profile/actions/create_api_token.php',
        'layout' => false,
        'permission' => 'profile.edit',
        'auth' => true,
        'method' => 'POST',
    ],
    'profile/actions/revoke-api-token' => [
        'file' => 'modules/profile/actions/revoke_api_token.php',
        'layout' => false,
        'permission' => 'profile.edit',
        'auth' => true,
        'method' => 'POST',
    ],
    'profile/actions/lock-settings' => [
        'file' => 'modules/profile/actions/lock_settings.php',
        'layout' => false,
        'permission' => 'profile.edit',
        'auth' => true,
        'method' => 'POST',
    ],
];
