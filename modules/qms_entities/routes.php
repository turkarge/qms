<?php
return [
 'qms_entities/view'=>['file'=>'modules/qms_entities/pages/view.php','layout'=>true,'permission'=>'qms_entities.view','auth'=>true,'method'=>'GET'],
 'ajax/qms_entities/datatable'=>['file'=>'modules/qms_entities/actions/datatable.php','layout'=>false,'permission'=>'qms_entities.view','auth'=>true,'method'=>'GET'],
 'qms_entities/actions/archive'=>['file'=>'modules/qms_entities/actions/archive.php','layout'=>false,'permission'=>'qms_entities.archive','auth'=>true,'method'=>'POST'],
];
