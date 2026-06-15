<?php

return [
    'queue/view' => [
        'file' => 'modules/queue/pages/view.php',
        'layout' => true,
        'permission' => 'queue.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'queue/actions/enqueue-test-mail' => [
        'file' => 'modules/queue/actions/enqueue_test_mail.php',
        'layout' => false,
        'permission' => 'queue.manage',
        'auth' => true,
        'method' => 'POST',
    ],
    'queue/actions/work-once' => [
        'file' => 'modules/queue/actions/work_once.php',
        'layout' => false,
        'permission' => 'queue.manage',
        'auth' => true,
        'method' => 'POST',
    ],
    'queue/actions/retry-failed' => [
        'file' => 'modules/queue/actions/retry_failed.php',
        'layout' => false,
        'permission' => 'queue.manage',
        'auth' => true,
        'method' => 'POST',
    ],
    'queue/actions/export' => [
        'file' => 'modules/queue/actions/export.php',
        'layout' => false,
        'permission' => 'queue.view',
        'auth' => true,
        'method' => 'GET',
    ],
];
