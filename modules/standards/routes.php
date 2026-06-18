<?php

return [
    'standards/view' => ['file' => 'modules/standards/pages/view.php', 'layout' => true, 'permission' => 'standards.view', 'auth' => true, 'method' => 'GET'],
    'ajax/standards/datatable' => ['file' => 'modules/standards/actions/datatable.php', 'layout' => false, 'permission' => 'standards.view', 'auth' => true, 'method' => 'GET'],
    'ajax/standards/form' => ['file' => 'modules/standards/modals/form.php', 'layout' => false, 'permission' => 'standards.view', 'auth' => true, 'method' => 'GET'],
    'standards/actions/save' => ['file' => 'modules/standards/actions/save.php', 'layout' => false, 'permission' => 'standards.view', 'auth' => true, 'method' => 'POST'],
];
