<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/template/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => template_lang('save_error'),
    ], 419);
}

if (!kirpi_templates_table_ready()) {
    json_response([
        'status' => 'error',
        'message' => template_lang('table_missing'),
    ], 422);
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    json_response(['status' => 'error', 'message' => template_lang('not_found')], 404);
}

try {
    $findStmt = db()->prepare("SELECT id, code, is_active FROM templates WHERE id = :id LIMIT 1");
    $findStmt->execute([':id' => $id]);
    $template = $findStmt->fetch(PDO::FETCH_ASSOC);
    if (!$template) {
        json_response(['status' => 'error', 'message' => template_lang('not_found')], 404);
    }

    $newStatus = (int) ($template['is_active'] ?? 0) === 1 ? 0 : 1;
    $stmt = db()->prepare("UPDATE templates SET is_active = :is_active WHERE id = :id LIMIT 1");
    $stmt->execute([
        ':is_active' => $newStatus,
        ':id' => $id,
    ]);

    kirpi_audit_log('template_status_toggle', 'template', [
        'id' => $id,
        'code' => (string) ($template['code'] ?? ''),
        'is_active' => $newStatus === 1,
    ], 'template', $id, 'success');

    kirpi_notify_current_user('template.status_changed', [
        'id' => $id,
        'code' => (string) ($template['code'] ?? ''),
        'is_active' => $newStatus === 1,
        'status_label' => $newStatus === 1 ? 'aktif' : 'pasif',
    ], [
        'title' => 'Şablon durumu güncellendi',
        'message' => (string) ($template['code'] ?? 'Şablon') . ' ' . ($newStatus === 1 ? 'aktif' : 'pasif') . ' yapıldı.',
        'source_module' => 'template',
        'entity_type' => 'template',
        'entity_id' => $id,
    ]);

    json_response([
        'status' => 'success',
        'message' => template_lang('status_updated'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('template status toggle error: ' . $e->getMessage());
    json_response([
        'status' => 'error',
        'message' => APP_DEBUG ? (template_lang('save_error') . ' [' . $e->getMessage() . ']') : template_lang('save_error'),
    ], 500);
}
