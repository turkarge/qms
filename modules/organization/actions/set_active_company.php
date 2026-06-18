<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/organization/language.php';
require_once BASE_PATH . '/modules/organization/helpers.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => organization_lang('csrf_failed', 'Guvenlik dogrulamasi basarisiz oldu.'),
    ], 419);
}

$companyId = (int) ($_POST['active_company_id'] ?? 0);
$saveAsDefault = isset($_POST['save_as_default']);

if ($companyId <= 0) {
    json_response([
        'status' => 'error',
        'message' => organization_lang('select_company'),
    ], 422);
}

try {
    $company = organization_set_active_company($companyId, $saveAsDefault);

    kirpi_audit_log('set_active_company', 'organization', [
        'company_id' => $companyId,
        'save_as_default' => $saveAsDefault,
    ], 'organization_company', $companyId, 'success');

    json_response([
        'status' => 'success',
        'message' => organization_lang('active_company_updated'),
        'reload_page' => true,
        'data' => [
            'company' => $company,
        ],
    ]);
} catch (RuntimeException $e) {
    if ($e->getCode() === 403) {
        json_response([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 403);
    }

    throw $e;
} catch (Throwable $e) {
    error_log('set active company error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => organization_lang('active_company_update_error'),
    ], 500);
}
