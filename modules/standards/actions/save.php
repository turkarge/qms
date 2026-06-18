<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/standards/helpers.php';
require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) json_response(['status' => 'error', 'message' => 'CSRF'], 419);
if (!check_permission('standards.edit') && !check_permission('standards.create')) json_response(['status' => 'error', 'message' => standards_lang('permission_denied', 'Yetkiniz yok.')], 403);

$resource = trim((string) ($_POST['resource'] ?? ''));
$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0 && !check_permission('standards.create')) json_response(['status' => 'error', 'message' => standards_lang('permission_denied', 'Yetkiniz yok.')], 403);
if ($id > 0 && !check_permission('standards.edit')) json_response(['status' => 'error', 'message' => standards_lang('permission_denied', 'Yetkiniz yok.')], 403);

try {
    $row = standards_save_resource($resource, $_POST);
    kirpi_audit_log($id > 0 ? 'update' : 'create', 'standards', ['resource' => $resource], 'standards_' . $resource, (int) ($row['id'] ?? 0), 'success');
    json_response([
        'status' => 'success',
        'message' => standards_lang($id > 0 ? 'updated_success' : 'created_success'),
        'data' => ['resource' => $resource, 'row' => $row],
    ]);
} catch (InvalidArgumentException $e) {
    json_response(['status' => 'error', 'message' => standards_lang($e->getMessage(), standards_lang('required_fields'))], 422);
} catch (RuntimeException $e) {
    if ($e->getCode() === 403) json_response(['status' => 'error', 'message' => standards_lang('permission_denied', 'Yetkiniz yok.')], 403);
    throw $e;
} catch (Throwable $e) {
    error_log('standards save error: ' . $e->getMessage());
    json_response(['status' => 'error', 'message' => standards_lang('save_error')], 500);
}
