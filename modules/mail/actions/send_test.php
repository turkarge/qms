<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/mail/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => mail_lang('csrf_failed'),
    ], 419);
}

$recipientEmail = trim((string) ($_POST['recipient_email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($recipientEmail === '' || $subject === '' || $message === '') {
    json_response([
        'status' => 'error',
        'message' => mail_lang('required_fields'),
    ], 422);
}

$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);

$sendResult = kirpi_send_templated_mail(
    $recipientEmail,
    'mail.test_manual',
    [
        'message_html' => nl2br(e($message)),
        'recipient_email' => $recipientEmail,
    ],
    $userId > 0 ? $userId : null,
    $subject
);

if (!($sendResult['success'] ?? false)) {
    kirpi_audit_log('send_test_failed', 'mail', [
        'recipient_email' => $recipientEmail,
        'subject' => $subject,
        'transport' => (string) ($sendResult['transport'] ?? ''),
        'message' => (string) ($sendResult['message'] ?? ''),
    ], 'mail', null, 'failed');

    json_response([
        'status' => 'error',
        'message' => (string) ($sendResult['message'] ?? mail_lang('send_failed_default')),
    ], 422);
}

kirpi_audit_log('send_test', 'mail', [
    'recipient_email' => $recipientEmail,
    'subject' => $subject,
    'transport' => (string) ($sendResult['transport'] ?? ''),
], 'mail', null, 'success');

kirpi_notify_current_user('mail.test_sent', [
    'recipient_email' => $recipientEmail,
    'subject' => $subject,
    'transport' => (string) ($sendResult['transport'] ?? ''),
], [
    'title' => 'Test mail gönderildi',
    'message' => $recipientEmail . ' adresine test maili gönderildi.',
    'source_module' => 'mail',
    'entity_type' => 'mail',
]);

json_response([
    'status' => 'success',
    'message' => (string) ($sendResult['message'] ?? mail_lang('send_success_default')),
    'reload_page' => true,
]);
