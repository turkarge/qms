<?php
return [
 'qms_entities/view'=>['file'=>'modules/qms_entities/pages/view.php','layout'=>true,'permission'=>'qms_entities.view','auth'=>true,'method'=>'GET'],
 'ajax/qms_entities/datatable'=>['file'=>'modules/qms_entities/actions/datatable.php','layout'=>false,'permission'=>'qms_entities.view','auth'=>true,'method'=>'GET'],
 'ajax/qms_entities/form'=>['file'=>'modules/qms_entities/modals/form.php','layout'=>false,'permission'=>'qms_entities.view','auth'=>true,'method'=>'GET'],
 'ajax/qms_entities/type-form'=>['file'=>'modules/qms_entities/modals/type_form.php','layout'=>false,'permission'=>'qms_entities.view','auth'=>true,'method'=>'GET'],
 'qms_entities/actions/save'=>['file'=>'modules/qms_entities/actions/save.php','layout'=>false,'permission'=>'qms_entities.manage','auth'=>true,'method'=>'POST'],
 'qms_entities/actions/save-type'=>['file'=>'modules/qms_entities/actions/save_type.php','layout'=>false,'permission'=>'qms_entities.manage','auth'=>true,'method'=>'POST'],
 'qms_entities/actions/archive'=>['file'=>'modules/qms_entities/actions/archive.php','layout'=>false,'permission'=>'qms_entities.archive','auth'=>true,'method'=>'POST'],
];
