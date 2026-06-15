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

$id = (int) ($_POST['id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$htmlBody = trim((string) ($_POST['html_body'] ?? ''));
$isActive = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1' ? 1 : 0;

if ($id <= 0 || $name === '' || $subject === '' || $htmlBody === '') {
    json_response([
        'status' => 'error',
        'message' => mail_lang('template_required'),
    ], 422);
}

try {
    $findStmt = db()->prepare("SELECT id, template_key FROM mail_templates WHERE id = :id LIMIT 1");
    $findStmt->execute([':id' => $id]);
    $template = $findStmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        json_response([
            'status' => 'error',
            'message' => mail_lang('template_not_found'),
        ], 404);
    }

    $updateStmt = db()->prepare("\n        UPDATE mail_templates\n        SET name = :name,\n            subject = :subject,\n            html_body = :html_body,\n            is_active = :is_active\n        WHERE id = :id\n        LIMIT 1\n    ");
    $updateStmt->execute([
        ':name' => $name,
        ':subject' => $subject,
        ':html_body' => $htmlBody,
        ':is_active' => $isActive,
        ':id' => $id,
    ]);

    kirpi_audit_log('mail_template_update', 'mail', [
        'template_id' => $id,
        'template_key' => (string) ($template['template_key'] ?? ''),
        'is_active' => $isActive === 1,
    ], 'mail_template', $id, 'success');

    kirpi_notify_current_user('mail.template_updated', [
        'template_id' => $id,
        'template_key' => (string) ($template['template_key'] ?? ''),
        'name' => $name,
        'is_active' => $isActive === 1,
    ], [
        'title' => 'Mail şablonu güncellendi',
        'message' => '"' . $name . '" mail şablonu güncellendi.',
        'source_module' => 'mail',
        'entity_type' => 'mail_template',
        'entity_id' => $id,
    ]);

    json_response([
        'status' => 'success',
        'message' => mail_lang('template_updated'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('mail template update error: ' . $e->getMessage());
    json_response([
        'status' => 'error',
        'message' => APP_DEBUG ? (mail_lang('template_save_error') . ' [' . $e->getMessage() . ']') : mail_lang('template_save_error'),
    ], 500);
}
