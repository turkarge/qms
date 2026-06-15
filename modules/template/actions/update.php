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
$name = trim((string) ($_POST['name'] ?? ''));
$language = strtolower(trim((string) ($_POST['language'] ?? 'tr')));
$subject = trim((string) ($_POST['subject'] ?? ''));
$body = trim((string) ($_POST['body'] ?? ''));
$variables = kirpi_template_normalize_variables($_POST['variables'] ?? '');
$isActive = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1' ? 1 : 0;

if ($id <= 0 || $name === '' || $language === '' || $body === '') {
    json_response(['status' => 'error', 'message' => template_lang('required_fields')], 422);
}

try {
    $findStmt = db()->prepare("SELECT id, kind, module_key, target_key, code FROM templates WHERE id = :id LIMIT 1");
    $findStmt->execute([':id' => $id]);
    $template = $findStmt->fetch(PDO::FETCH_ASSOC);
    if (!$template) {
        json_response(['status' => 'error', 'message' => template_lang('not_found')], 404);
    }

    if (in_array((string) ($template['kind'] ?? ''), ['email', 'content'], true)) {
        if ($subject === '') {
            json_response(['status' => 'error', 'message' => template_lang('required_fields')], 422);
        }

        $variables = array_values(array_unique(array_merge($variables, kirpi_template_variables_for_target((string) ($template['target_key'] ?? '')))));
        sort($variables);
    }

    $variablesJson = json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmt = db()->prepare("
        UPDATE templates
        SET name = :name,
            language = :language,
            subject = :subject,
            body = :body,
            variables_json = :variables_json,
            is_active = :is_active
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        ':name' => $name,
        ':language' => $language,
        ':subject' => in_array((string) ($template['kind'] ?? ''), ['email', 'content'], true) ? $subject : null,
        ':body' => $body,
        ':variables_json' => $variablesJson ?: null,
        ':is_active' => $isActive,
        ':id' => $id,
    ]);

    kirpi_audit_log('template_update', 'template', [
        'id' => $id,
        'kind' => (string) ($template['kind'] ?? ''),
        'code' => (string) ($template['code'] ?? ''),
        'is_active' => $isActive === 1,
    ], 'template', $id, 'success');

    kirpi_notify_current_user('template.updated', [
        'id' => $id,
        'kind' => (string) ($template['kind'] ?? ''),
        'code' => (string) ($template['code'] ?? ''),
        'name' => $name,
        'is_active' => $isActive === 1,
    ], [
        'title' => 'Şablon güncellendi',
        'message' => '"' . $name . '" şablonu güncellendi.',
        'source_module' => 'template',
        'entity_type' => 'template',
        'entity_id' => $id,
    ]);

    json_response([
        'status' => 'success',
        'message' => template_lang('updated_success'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('template update error: ' . $e->getMessage());
    json_response([
        'status' => 'error',
        'message' => APP_DEBUG ? (template_lang('save_error') . ' [' . $e->getMessage() . ']') : template_lang('save_error'),
    ], 500);
}
