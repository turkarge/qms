<?php

define('BASE_PATH', dirname(__DIR__));
define('KIRPI_CORE_ENTRY', true);

require BASE_PATH . '/core/config.php';
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/functions.php';

$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$manifestPath = BASE_PATH . '/modules/organization/module.json';
$manifest = json_decode((string) file_get_contents($manifestPath), true);
$assert(is_array($manifest), 'Organization manifest must be valid JSON.');
$assert(($manifest['key'] ?? null) === 'organization', 'Organization manifest key is invalid.');
$assert(($manifest['version'] ?? null) === '0.1.0', 'Organization manifest version is invalid.');

$expectedTables = [
    'organization_companies',
    'organization_units',
    'organization_positions',
    'organization_user_assignments',
];

foreach ($expectedTables as $table) {
    $assert(db_table_exists($table), "Missing organization table: {$table}");
}

$expectedColumns = [
    'organization_companies' => ['company_code', 'company_name', 'status'],
    'organization_units' => ['company_id', 'parent_unit_id', 'unit_type', 'unit_code', 'unit_name'],
    'organization_positions' => ['company_id', 'department_unit_id', 'position_code', 'position_name'],
    'organization_user_assignments' => ['user_id', 'company_id', 'unit_id', 'position_id', 'scope_mode', 'starts_at', 'ends_at'],
];

foreach ($expectedColumns as $table => $columns) {
    $actual = db()->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($columns as $column) {
        $assert(in_array($column, $actual, true), "Missing column {$table}.{$column}");
    }
}

$expectedPermissions = [
    'organization.view',
    'organization.create',
    'organization.edit',
    'organization.status',
    'organization.assign',
    'organization.export',
];

$permissionStmt = db()->query("SELECT slug FROM permissions WHERE slug LIKE 'organization.%' ORDER BY slug ASC");
$actualPermissions = $permissionStmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($expectedPermissions as $permission) {
    $assert(in_array($permission, $actualPermissions, true), "Missing permission: {$permission}");
}

$syncResult = kirpi_ai_sync_schema_registry_from_manifests();
$assert(in_array(($syncResult['status'] ?? ''), ['success', 'partial'], true), 'AI schema sync failed.');

$entityStmt = db()->query("SELECT entity_key FROM ai_schema_entities WHERE module_key = 'organization' ORDER BY entity_key ASC");
$actualEntities = $entityStmt->fetchAll(PDO::FETCH_COLUMN);
foreach (['company', 'organization_unit', 'organization_user_assignment', 'position'] as $entity) {
    $assert(in_array($entity, $actualEntities, true), "Missing AI schema entity: {$entity}");
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Organization foundation: PASS\n");
