<?php

return [
    'organization/view' => [
        'file' => 'modules/organization/pages/view.php',
        'layout' => true,
        'permission' => 'organization.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/organization/datatable' => [
        'file' => 'modules/organization/actions/datatable.php',
        'layout' => false,
        'permission' => 'organization.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/organization/form' => [
        'file' => 'modules/organization/modals/form.php',
        'layout' => false,
        'permission' => 'organization.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'ajax/organization/tree' => [
        'file' => 'modules/organization/modals/tree.php',
        'layout' => false,
        'permission' => 'organization.view',
        'auth' => true,
        'method' => 'GET',
    ],
    'organization/actions/save' => [
        'file' => 'modules/organization/actions/save.php',
        'layout' => false,
        'permission' => 'organization.view',
        'auth' => true,
        'method' => 'POST',
    ],
    'organization/actions/set-active-company' => [
        'file' => 'modules/organization/actions/set_active_company.php',
        'layout' => false,
        'permission' => 'organization.view',
        'auth' => true,
        'method' => 'POST',
    ],
    'organization/actions/toggle-status' => [
        'file' => 'modules/organization/actions/toggle_status.php',
        'layout' => false,
        'permission' => 'organization.status',
        'auth' => true,
        'method' => 'POST',
    ],
    'organization/actions/export' => [
        'file' => 'modules/organization/actions/export.php',
        'layout' => false,
        'permission' => 'organization.export',
        'auth' => true,
        'method' => 'GET',
    ],
];
