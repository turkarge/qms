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

$recipientEmail = trim((string) ($_POST['recipient_email'] ?? ''));
if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'status' => 'error',
        'message' => queue_lang('invalid_email'),
    ], 422);
}

$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);

$jobId = kirpi_queue_push('mail.send', [
    'recipient_email' => $recipientEmail,
    'template_key' => 'queue.test_mail',
    'template_vars' => [
        'user_name' => (string) ($currentUser['name'] ?? 'Kullanıcı'),
        'sent_at' => date('Y-m-d H:i:s'),
    ],
    'user_id' => $userId > 0 ? $userId : null,
]);

kirpi_audit_log('enqueue_test_mail', 'queue', [
    'job_id' => $jobId,
    'recipient_email' => $recipientEmail,
], 'queue_job', $jobId, 'success');

kirpi_notify_current_user('queue.mail_enqueued', [
    'job_id' => $jobId,
    'recipient_email' => $recipientEmail,
], [
    'title' => 'Mail işi kuyruğa alındı',
    'message' => 'Mail işi #' . $jobId . ' kuyruğa alındı.',
    'source_module' => 'queue',
    'entity_type' => 'queue_job',
    'entity_id' => $jobId,
]);

json_response([
    'status' => 'success',
    'message' => queue_lang('enqueue_success_prefix') . $jobId,
    'reload_page' => true,
]);
