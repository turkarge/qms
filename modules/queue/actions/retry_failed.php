<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/queue/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => queue_lang('csrf_failed'),
    ], 419);
}

if (!kirpi_queue_table_ready()) {
    json_response([
        'status' => 'error',
        'message' => queue_lang('table_not_ready'),
    ], 422);
}

$stmt = db()->prepare("\n    UPDATE jobs_queue\n    SET status = 'queued',\n        last_error = NULL,\n        available_at = NOW(),\n        reserved_at = NULL,\n        finished_at = NULL,\n        updated_at = NOW()\n    WHERE status = 'failed'\n");
$stmt->execute();
$affected = (int) $stmt->rowCount();
$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);

kirpi_audit_log('retry_failed', 'queue', [
    'affected_rows' => $affected,
], 'queue_job', null, 'success');

if ($userId > 0 && $affected > 0) {
    kirpi_notify_user($userId, 'queue.retry_failed', [
        'affected_count' => $affected,
    ], [
        'title' => 'Queue retry başlatıldı',
        'message' => $affected . ' başarısız queue işi yeniden kuyruğa alındı.',
        'source_module' => 'queue',
        'entity_type' => 'queue_job',
        'email' => false,
    ]);
}

json_response([
    'status' => 'success',
    'message' => queue_lang('retry_success_prefix') . $affected,
    'reload_page' => true,
]);
