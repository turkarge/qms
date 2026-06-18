<?php

return [
    'qms_events/view' => ['file' => 'modules/qms_events/pages/view.php', 'layout' => true, 'permission' => 'qms_events.view', 'auth' => true, 'method' => 'GET'],
    'ajax/qms_events/datatable' => ['file' => 'modules/qms_events/actions/datatable.php', 'layout' => false, 'permission' => 'qms_events.view', 'auth' => true, 'method' => 'GET'],
];
