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

if (!kirpi_mail_templates_table_ready()) {
    json_response([
        'status' => 'error',
        'message' => mail_lang('template_tables_missing'),
    ], 422);
}

$templateKey = strtolower(trim((string) ($_POST['template_key'] ?? '')));
$name = trim((string) ($_POST['name'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$htmlBody = trim((string) ($_POST['html_body'] ?? ''));
$isActive = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1' ? 1 : 0;

if ($templateKey === '' || $name === '' || $subject === '' || $htmlBody === '') {
    json_response([
        'status' => 'error',
        'message' => mail_lang('template_required'),
    ], 422);
}

if (preg_match('/^[a-z0-9._-]{3,120}$/', $templateKey) !== 1) {
    json_response([
        'status' => 'error',
        'message' => mail_lang('template_key_invalid'),
    ], 422);
}

try {
    $stmt = db()->prepare("\n        INSERT INTO mail_templates (template_key, name, subject, html_body, is_active, is_system)\n        VALUES (:template_key, :name, :subject, :html_body, :is_active, 0)\n    ");
    $stmt->execute([
        ':template_key' => $templateKey,
        ':name' => $name,
        ':subject' => $subject,
        ':html_body' => $htmlBody,
        ':is_active' => $isActive,
    ]);

    $createdTemplateId = (int) db()->lastInsertId();
    kirpi_audit_log('mail_template_create', 'mail', [
        'template_key' => $templateKey,
        'is_active' => $isActive === 1,
    ], 'mail_template', $createdTemplateId, 'success');

    kirpi_notify_current_user('mail.template_created', [
        'template_key' => $templateKey,
        'name' => $name,
        'is_active' => $isActive === 1,
    ], [
        'title' => 'Mail şablonu oluşturuldu',
        'message' => '"' . $name . '" mail şablonu oluşturuldu.',
        'source_module' => 'mail',
        'entity_type' => 'mail_template',
        'entity_id' => $createdTemplateId,
    ]);

    json_response([
        'status' => 'success',
        'message' => mail_lang('template_created'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    if (stripos($e->getMessage(), 'Duplicate') !== false) {
        json_response([
            'status' => 'error',
            'message' => mail_lang('template_duplicate_key'),
        ], 422);
    }

    error_log('mail template create error: ' . $e->getMessage());
    json_response([
        'status' => 'error',
        'message' => APP_DEBUG ? (mail_lang('template_save_error') . ' [' . $e->getMessage() . ']') : mail_lang('template_save_error'),
    ], 500);
}
