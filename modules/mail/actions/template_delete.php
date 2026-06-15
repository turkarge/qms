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
if ($id <= 0) {
    json_response([
        'status' => 'error',
        'message' => mail_lang('template_not_found'),
    ], 404);
}

try {
    $findStmt = db()->prepare("SELECT id, template_key, is_system FROM mail_templates WHERE id = :id LIMIT 1");
    $findStmt->execute([':id' => $id]);
    $template = $findStmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        json_response([
            'status' => 'error',
            'message' => mail_lang('template_not_found'),
        ], 404);
    }

    if ((int) ($template['is_system'] ?? 0) === 1) {
        json_response([
            'status' => 'error',
            'message' => mail_lang('template_delete_blocked'),
        ], 422);
    }

    $deleteStmt = db()->prepare("DELETE FROM mail_templates WHERE id = :id LIMIT 1");
    $deleteStmt->execute([':id' => $id]);

    kirpi_audit_log('mail_template_delete', 'mail', [
        'template_id' => $id,
        'template_key' => (string) ($template['template_key'] ?? ''),
    ], 'mail_template', $id, 'success');

    kirpi_notify_current_user('mail.template_deleted', [
        'template_id' => $id,
        'template_key' => (string) ($template['template_key'] ?? ''),
    ], [
        'title' => 'Mail şablonu silindi',
        'message' => (string) ($template['template_key'] ?? 'Mail şablonu') . ' silindi.',
        'source_module' => 'mail',
        'entity_type' => 'mail_template',
        'entity_id' => $id,
    ]);

    json_response([
        'status' => 'success',
        'message' => mail_lang('template_deleted'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('mail template delete error: ' . $e->getMessage());
    json_response([
        'status' => 'error',
        'message' => APP_DEBUG ? (mail_lang('template_save_error') . ' [' . $e->getMessage() . ']') : mail_lang('template_save_error'),
    ], 500);
}
