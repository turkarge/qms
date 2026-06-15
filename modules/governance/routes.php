<?php

return [
    'governance/view' => ['file' => 'modules/governance/pages/view.php', 'layout' => true, 'permission' => 'governance.view', 'auth' => true, 'method' => 'GET'],
    'ajax/governance/datatable' => ['file' => 'modules/governance/actions/datatable.php', 'layout' => false, 'permission' => 'governance.view', 'auth' => true, 'method' => 'GET'],
    'ajax/governance/form' => ['file' => 'modules/governance/modals/form.php', 'layout' => false, 'permission' => 'governance.view', 'auth' => true, 'method' => 'GET'],
    'governance/actions/save' => ['file' => 'modules/governance/actions/save.php', 'layout' => false, 'permission' => 'governance.view', 'auth' => true, 'method' => 'POST'],
    'governance/actions/revoke' => ['file' => 'modules/governance/actions/revoke.php', 'layout' => false, 'permission' => 'governance.delegation.manage', 'auth' => true, 'method' => 'POST'],
];
