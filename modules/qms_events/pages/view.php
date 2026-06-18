<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/qms_events/language.php';
$config = [
    'endpoint' => base_url('ajax/qms_events/datatable'),
    'labels' => [
        'payload' => qms_events_lang('payload'),
    ],
];
?>
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">Kirpi QMS+</div>
        <h2 class="page-title"><?php echo e(qms_events_lang('qms_events')); ?></h2>
        <div class="text-secondary mt-1"><?php echo e(qms_events_lang('hint')); ?></div>
      </div>
    </div>
  </div>
</div>
<div class="page-body">
  <div class="container-xl">
    <div class="alert alert-info">
      <i class="ti ti-info-circle me-2"></i>
      Event store append-only calisir. Bu ekranda olaylar sadece okunur; kayit guncelleme veya silme islemi yoktur.
    </div>
    <div class="card">
      <div class="card-body p-0">
        <table id="qms-events-table" class="table table-vcenter table-striped w-100 kirpi-data-table">
          <thead>
            <tr>
              <th><?php echo e(qms_events_lang('recorded_at')); ?></th>
              <th><?php echo e(qms_events_lang('company')); ?></th>
              <th><?php echo e(qms_events_lang('event_type')); ?></th>
              <th><?php echo e(qms_events_lang('entity')); ?></th>
              <th><?php echo e(qms_events_lang('actor')); ?></th>
              <th><?php echo e(qms_events_lang('source_module')); ?></th>
              <th><?php echo e(qms_events_lang('correlation_id')); ?></th>
              <th><?php echo e(qms_events_lang('payload')); ?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>
<script type="application/json" id="qms-events-config"><?php echo json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
