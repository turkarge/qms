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

$result = kirpi_queue_work_once('default');
$status = (string) ($result['status'] ?? 'error');
$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);

if ($status === 'failed') {
    kirpi_audit_log('work_once', 'queue', $result, 'queue_job', isset($result['job_id']) ? (int) $result['job_id'] : null, 'failed');

    if ($userId > 0) {
        kirpi_notify_user($userId, 'queue.job_failed', [
            'job_type' => (string) ($result['job_type'] ?? 'unknown'),
            'error_message' => (string) ($result['message'] ?? queue_lang('work_failed_default')),
        ], [
            'title' => 'Queue job başarısız oldu',
            'message' => (string) ($result['message'] ?? queue_lang('work_failed_default')),
            'source_module' => 'queue',
            'entity_type' => 'queue_job',
            'entity_id' => isset($result['job_id']) ? (int) $result['job_id'] : null,
            'email' => false,
        ]);
    }

    json_response([
        'status' => 'error',
        'message' => (string) ($result['message'] ?? queue_lang('work_failed_default')),
        'reload_page' => true,
    ], 422);
}

if ($status === 'processed') {
    kirpi_audit_log('work_once', 'queue', $result, 'queue_job', (int) ($result['job_id'] ?? 0), 'success');

    json_response([
        'status' => 'success',
        'message' => queue_lang('work_processed_prefix') . (int) ($result['job_id'] ?? 0),
        'reload_page' => true,
    ]);
}

json_response([
    'status' => 'info',
    'message' => (string) ($result['message'] ?? queue_lang('queue_idle')),
    'reload_page' => true,
]);
