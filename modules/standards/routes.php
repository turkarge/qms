<?php

return [
    'standards/view' => ['file' => 'modules/standards/pages/view.php', 'layout' => true, 'permission' => 'standards.view', 'auth' => true, 'method' => 'GET'],
    'ajax/standards/datatable' => ['file' => 'modules/standards/actions/datatable.php', 'layout' => false, 'permission' => 'standards.view', 'auth' => true, 'method' => 'GET'],
];
