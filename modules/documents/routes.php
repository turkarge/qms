<?php

return [
    'documents/view' => [
        'file' => 'modules/documents/pages/view.php',
        'layout' => true,
        'permission' => 'documents.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'documents/actions/upload' => [
        'file' => 'modules/documents/actions/upload.php',
        'layout' => false,
        'permission' => 'documents.upload',
        'auth' => true,
        'method' => 'POST',
    ],
    'documents/actions/export' => [
        'file' => 'modules/documents/actions/export.php',
        'layout' => false,
        'permission' => 'documents.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'documents/actions/download/{id}' => [
        'file' => 'modules/documents/actions/download.php',
        'layout' => false,
        'permission' => 'documents.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'documents/actions/delete' => [
        'file' => 'modules/documents/actions/delete.php',
        'layout' => false,
        'permission' => 'documents.manage',
        'auth' => true,
        'method' => 'POST',
    ],
    'documents/actions/bulk-delete' => [
        'file' => 'modules/documents/actions/bulk_delete.php',
        'layout' => false,
        'permission' => 'documents.manage',
        'auth' => true,
        'method' => 'POST',
    ],
];
