<?php

return [
    'backup/view' => [
        'file' => 'modules/backup/pages/view.php',
        'layout' => true,
        'permission' => 'backup.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'backup/actions/create' => [
        'file' => 'modules/backup/actions/create.php',
        'layout' => false,
        'permission' => 'backup.create',
        'auth' => true,
        'method' => 'POST',
    ],
    'backup/actions/restore' => [
        'file' => 'modules/backup/actions/restore.php',
        'layout' => false,
        'permission' => 'backup.restore',
        'auth' => true,
        'method' => 'POST',
    ],
    'backup/actions/verify' => [
        'file' => 'modules/backup/actions/verify.php',
        'layout' => false,
        'permission' => 'backup.restore',
        'auth' => true,
        'method' => 'POST',
    ],
    'backup/actions/download' => [
        'file' => 'modules/backup/actions/download.php',
        'layout' => false,
        'permission' => 'backup.download',
        'auth' => true,
        'method' => 'GET',
    ],
    'backup/actions/delete' => [
        'file' => 'modules/backup/actions/delete.php',
        'layout' => false,
        'permission' => 'backup.delete',
        'auth' => true,
        'method' => 'POST',
    ],
    'backup/actions/export' => [
        'file' => 'modules/backup/actions/export.php',
        'layout' => false,
        'permission' => 'backup.view',
        'auth' => true,
        'method' => 'GET',
    ],
];
