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

$kind = strtolower(trim((string) ($_POST['kind'] ?? '')));
$moduleKey = strtolower(trim((string) ($_POST['module_key'] ?? '')));
$targetKey = strtolower(trim((string) ($_POST['target_key'] ?? '')));
$code = kirpi_template_normalize_code((string) ($_POST['code'] ?? ''));
$name = trim((string) ($_POST['name'] ?? ''));
$language = strtolower(trim((string) ($_POST['language'] ?? 'tr')));
$subject = trim((string) ($_POST['subject'] ?? ''));
$body = trim((string) ($_POST['body'] ?? ''));
$variables = kirpi_template_normalize_variables($_POST['variables'] ?? '');

if (!in_array($kind, kirpi_template_kinds(), true)) {
    json_response(['status' => 'error', 'message' => template_lang('invalid_kind')], 422);
}

if (in_array($kind, ['email', 'content'], true)) {
    $variables = array_values(array_unique(array_merge($variables, kirpi_template_variables_for_target($targetKey))));
    sort($variables);
}

if ($moduleKey === '' || $targetKey === '' || $code === '' || $name === '' || $language === '' || $body === '' || (in_array($kind, ['email', 'content'], true) && $subject === '')) {
    json_response(['status' => 'error', 'message' => template_lang('required_fields')], 422);
}

if (preg_match('/^[a-z0-9._-]{3,120}$/', $code) !== 1) {
    json_response(['status' => 'error', 'message' => template_lang('invalid_code')], 422);
}

$currentUser = current_user();
$createdBy = (int) ($currentUser['id'] ?? 0);
$variablesJson = json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

try {
    $stmt = db()->prepare("
        INSERT INTO templates (
            kind, module_key, target_key, code, name, language, subject, body, variables_json, is_system, is_active, created_by_user_id
        ) VALUES (
            :kind, :module_key, :target_key, :code, :name, :language, :subject, :body, :variables_json, 0, 1, :created_by_user_id
        )
    ");
    $stmt->execute([
        ':kind' => $kind,
        ':module_key' => $moduleKey,
        ':target_key' => $targetKey,
        ':code' => $code,
        ':name' => $name,
        ':language' => $language,
        ':subject' => in_array($kind, ['email', 'content'], true) ? $subject : null,
        ':body' => $body,
        ':variables_json' => $variablesJson ?: null,
        ':created_by_user_id' => $createdBy > 0 ? $createdBy : null,
    ]);

    $createdTemplateId = (int) db()->lastInsertId();
    kirpi_audit_log('template_create', 'template', [
        'kind' => $kind,
        'module_key' => $moduleKey,
        'target_key' => $targetKey,
        'code' => $code,
    ], 'template', $createdTemplateId, 'success');

    kirpi_notify_current_user('template.created', [
        'kind' => $kind,
        'module_key' => $moduleKey,
        'target_key' => $targetKey,
        'code' => $code,
        'name' => $name,
    ], [
        'title' => 'Şablon oluşturuldu',
        'message' => '"' . $name . '" şablonu oluşturuldu.',
        'source_module' => 'template',
        'entity_type' => 'template',
        'entity_id' => $createdTemplateId,
    ]);

    json_response([
        'status' => 'success',
        'message' => template_lang('created_success'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    if (stripos($e->getMessage(), 'Duplicate') !== false) {
        json_response(['status' => 'error', 'message' => template_lang('duplicate')], 422);
    }

    error_log('template create error: ' . $e->getMessage());
    json_response([
        'status' => 'error',
        'message' => APP_DEBUG ? (template_lang('save_error') . ' [' . $e->getMessage() . ']') : template_lang('save_error'),
    ], 500);
}
