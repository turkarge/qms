<?php
define('BASE_PATH', dirname(__DIR__));
define('KIRPI_CORE_ENTRY', true);
require BASE_PATH . '/core/config.php';
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/functions.php';
require BASE_PATH . '/modules/qms_entities/demo_seed.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$expectedModules = [
    'organization' => ['organization_companies', 'organization_units', 'organization_positions', 'organization_user_assignments'],
    'governance' => ['governance_ownership_assignments', 'governance_delegations'],
    'qms_entities' => ['qms_entity_types', 'qms_entity_type_settings', 'qms_entities', 'qms_number_sequences'],
    'qms_relationships' => ['qms_relationship_types', 'qms_entity_relationships'],
    'qms_events' => ['qms_domain_events'],
];

foreach ($expectedModules as $module => $tables) {
    $manifestPath = BASE_PATH . '/modules/' . $module . '/module.json';
    $assert(is_file($manifestPath), "Missing manifest: {$module}");
    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    $assert(($manifest['key'] ?? '') === $module, "Invalid manifest key: {$module}");
    foreach ($tables as $table) {
        $assert(db_table_exists($table), "Missing foundation table: {$table}");
    }
}

$expectedPermissions = [
    'organization.view',
    'governance.view',
    'qms_entities.view',
    'qms_relationships.view',
    'qms_events.view',
];
$permissions = db()->query("SELECT slug FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
foreach ($expectedPermissions as $permission) {
    $assert(in_array($permission, $permissions, true), "Missing permission: {$permission}");
}

$routes = [];
foreach (array_keys($expectedModules) as $module) {
    $routePath = BASE_PATH . '/modules/' . $module . '/routes.php';
    if (is_file($routePath)) {
        $routes += require $routePath;
    }
}
foreach (['organization/view', 'governance/view', 'qms_entities/view', 'qms_relationships/view', 'qms_events/view'] as $route) {
    $assert(isset($routes[$route]), "Missing foundation route: {$route}");
}

$pdo = db();
$pdo->beginTransaction();
try {
    $first = qms_demo_seed_data();
    $second = qms_demo_seed_data();
    $assert((int) ($first['company_id'] ?? 0) > 0, 'Demo seed must create a company.');
    $assert(count((array) ($first['entities'] ?? [])) >= 5, 'Demo seed must create foundation entities.');
    $assert(count((array) ($first['relationships'] ?? [])) >= 4, 'Demo seed must create foundation relationships.');
    $assert(count((array) ($first['events'] ?? [])) >= 5, 'Demo seed must create foundation events.');
    $assert(count((array) ($first['events'] ?? [])) === count((array) ($second['events'] ?? [])), 'Demo seed events must be idempotent.');
} finally {
    $pdo->rollBack();
}

$dashboard = (string) file_get_contents(BASE_PATH . '/modules/dashboard/pages/view.php');
$assert(str_contains($dashboard, 'qms_summary'), 'Dashboard must expose QMS summary block.');
$assert(str_contains($dashboard, 'qms_domain_events'), 'Dashboard must count QMS events.');

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}
fwrite(STDOUT, "QMS foundation integration: PASS\n");
