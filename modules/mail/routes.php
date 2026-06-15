<?php

return [
    'mail/test' => [
        'file' => 'modules/mail/pages/test.php',
        'layout' => true,
        'permission' => 'mail.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'mail/actions/send-test' => [
        'file' => 'modules/mail/actions/send_test.php',
        'layout' => false,
        'permission' => 'mail.test',
        'auth' => true,
        'method' => 'POST',
    ],
    'mail/templates' => [
        'file' => 'modules/mail/pages/templates.php',
        'layout' => true,
        'permission' => 'mail.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'mail/actions/template-create' => [
        'file' => 'modules/mail/actions/template_create.php',
        'layout' => false,
        'permission' => 'mail.view',
        'auth' => true,
        'method' => 'POST',
    ],
    'mail/actions/template-update' => [
        'file' => 'modules/mail/actions/template_update.php',
        'layout' => false,
        'permission' => 'mail.view',
        'auth' => true,
        'method' => 'POST',
    ],
    'mail/actions/template-delete' => [
        'file' => 'modules/mail/actions/template_delete.php',
        'layout' => false,
        'permission' => 'mail.view',
        'auth' => true,
        'method' => 'POST',
    ],
    'mail/actions/templates-export' => [
        'file' => 'modules/mail/actions/templates_export.php',
        'layout' => false,
        'permission' => 'mail.view',
        'auth' => true,
        'method' => 'GET',
    ],
];
