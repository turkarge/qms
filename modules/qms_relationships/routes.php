<?php

return [
    'qms_relationships/view' => ['file' => 'modules/qms_relationships/pages/view.php', 'layout' => true, 'permission' => 'qms_relationships.view', 'auth' => true, 'method' => 'GET'],
    'ajax/qms_relationships/datatable' => ['file' => 'modules/qms_relationships/actions/datatable.php', 'layout' => false, 'permission' => 'qms_relationships.view', 'auth' => true, 'method' => 'GET'],
    'ajax/qms_relationships/form' => ['file' => 'modules/qms_relationships/modals/form.php', 'layout' => false, 'permission' => 'qms_relationships.view', 'auth' => true, 'method' => 'GET'],
    'qms_relationships/actions/save' => ['file' => 'modules/qms_relationships/actions/save.php', 'layout' => false, 'permission' => 'qms_relationships.manage', 'auth' => true, 'method' => 'POST'],
    'qms_relationships/actions/archive' => ['file' => 'modules/qms_relationships/actions/archive.php', 'layout' => false, 'permission' => 'qms_relationships.archive', 'auth' => true, 'method' => 'POST'],
];
