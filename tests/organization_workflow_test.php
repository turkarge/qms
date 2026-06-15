<?php

define('BASE_PATH', dirname(__DIR__));
define('KIRPI_CORE_ENTRY', true);
require BASE_PATH . '/core/config.php';
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/functions.php';
require BASE_PATH . '/modules/organization/helpers.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$routes = require BASE_PATH . '/modules/organization/routes.php';
foreach (['organization/view', 'ajax/organization/datatable', 'ajax/organization/form', 'ajax/organization/tree', 'organization/actions/save', 'organization/actions/toggle-status', 'organization/actions/export'] as $route) {
    $assert(isset($routes[$route]), "Missing route: {$route}");
    if (isset($routes[$route])) $assert(is_file(BASE_PATH . '/' . $routes[$route]['file']), "Missing route file: {$route}");
}

$script = (string) file_get_contents(BASE_PATH . '/modules/organization/scripts/view.js');
$assert(str_contains($script, 'KirpiTable.create'), 'Organization page must use KirpiTable.');
$assert(str_contains($script, 'serverExport'), 'Organization page must define server export.');

$suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
$pdo = db();
$pdo->beginTransaction();
try {
    $userStmt = $pdo->prepare("INSERT INTO users (name, email, password, is_active) VALUES (:name, :email, :password, 1)");
    $userStmt->execute([
        ':name' => 'Organization Test User',
        ':email' => 'organization-test-' . strtolower($suffix) . '@example.test',
        ':password' => password_hash('Test1234!', PASSWORD_DEFAULT),
    ]);
    $userId = (int) $pdo->lastInsertId();

    $companyStmt = $pdo->prepare("INSERT INTO organization_companies (company_code, company_name, status) VALUES (:code, :name, 'active')");
    $companyStmt->execute([':code' => 'T' . $suffix, ':name' => 'Test Company ' . $suffix]);
    $companyId = (int) $pdo->lastInsertId();

    $facilityStmt = $pdo->prepare("INSERT INTO organization_units (company_id, unit_type, unit_code, unit_name, status) VALUES (:company, 'facility', :code, :name, 'active')");
    $facilityStmt->execute([':company' => $companyId, ':code' => 'F' . $suffix, ':name' => 'Test Facility']);
    $facilityId = (int) $pdo->lastInsertId();

    $departmentStmt = $pdo->prepare("INSERT INTO organization_units (company_id, parent_unit_id, unit_type, unit_code, unit_name, status) VALUES (:company, :parent, 'department', :code, :name, 'active')");
    $departmentStmt->execute([':company' => $companyId, ':parent' => $facilityId, ':code' => 'D' . $suffix, ':name' => 'Test Department']);
    $departmentId = (int) $pdo->lastInsertId();

    $positionStmt = $pdo->prepare("INSERT INTO organization_positions (company_id, department_unit_id, position_code, position_name, status) VALUES (:company, :department, :code, :name, 'active')");
    $positionStmt->execute([':company' => $companyId, ':department' => $departmentId, ':code' => 'P' . $suffix, ':name' => 'Test Position']);
    $positionId = (int) $pdo->lastInsertId();

    $assignmentStmt = $pdo->prepare("INSERT INTO organization_user_assignments (user_id, company_id, unit_id, position_id, scope_mode, is_primary, status, starts_at) VALUES (:user, :company, :unit, :position, 'department', 1, 'active', NOW())");
    $assignmentStmt->execute([':user' => $userId, ':company' => $companyId, ':unit' => $departmentId, ':position' => $positionId]);

    $scope = organization_user_scope($userId);
    $assert(in_array($companyId, $scope['company_ids'], true), 'Company must be present in user scope.');
    $assert(in_array($departmentId, $scope['unit_ids'], true), 'Department must be present in user scope.');
    $assert(in_array('department', $scope['modes'], true), 'Department scope mode must be present.');
    $assert(organization_entity_in_scope(['company_id' => $companyId, 'unit_id' => $departmentId], ['id' => $userId, 'role_name' => 'Test User']), 'Assigned entity must be visible.');
    $assert(!organization_entity_in_scope(['company_id' => $companyId + 999, 'unit_id' => $departmentId], ['id' => $userId, 'role_name' => 'Test User']), 'Foreign company must not be visible.');
} finally {
    $pdo->rollBack();
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}
fwrite(STDOUT, "Organization workflow: PASS\n");
