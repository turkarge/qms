<?php
define('BASE_PATH', dirname(__DIR__));
define('KIRPI_CORE_ENTRY', true);
require BASE_PATH . '/core/config.php';
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/functions.php';
require BASE_PATH . '/modules/standards/helpers.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$manifest = json_decode((string) file_get_contents(BASE_PATH . '/modules/standards/module.json'), true);
$assert(($manifest['key'] ?? '') === 'standards', 'Invalid standards manifest.');
foreach (['organization', 'qms_entities', 'qms_relationships', 'qms_events'] as $dependency) {
    $assert(in_array($dependency, (array) ($manifest['requires'] ?? []), true), "Missing dependency: {$dependency}");
}
foreach (['standards_catalog', 'standards_versions', 'standards_clauses', 'standards_requirements', 'standards_controls'] as $table) {
    $assert(db_table_exists($table), "Missing standards table: {$table}");
}
$permissions = db()->query("SELECT slug FROM permissions WHERE slug LIKE 'standards.%'")->fetchAll(PDO::FETCH_COLUMN);
foreach (['standards.view', 'standards.create', 'standards.edit', 'standards.publish', 'standards.map', 'standards.export'] as $permission) {
    $assert(in_array($permission, $permissions, true), "Missing permission: {$permission}");
}
$routes = require BASE_PATH . '/modules/standards/routes.php';
foreach (['standards/view', 'ajax/standards/datatable', 'ajax/standards/form', 'ajax/standards/mapping-form', 'standards/actions/save', 'standards/actions/map', 'standards/actions/unmap'] as $route) {
    $assert(isset($routes[$route]), "Missing route: {$route}");
}

$pdo = db();
$pdo->beginTransaction();
try {
    $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $userStmt = $pdo->prepare("INSERT INTO users(name, email, password, is_active) VALUES(:name, :email, :password, 1)");
    $userStmt->execute([':name' => 'Standards Tester', ':email' => 'standards-' . strtolower($suffix) . '@example.test', ':password' => password_hash('Test1234!', PASSWORD_DEFAULT)]);
    $_SESSION['user'] = ['id' => (int) $pdo->lastInsertId(), 'role_name' => 'Super Admin', 'permissions' => ['*']];
    $companyStmt = $pdo->prepare("INSERT INTO organization_companies(company_code, company_name, status) VALUES(:code, :name, 'active')");
    $companyStmt->execute([':code' => 'S' . $suffix, ':name' => 'Standards Test ' . $suffix]);
    $companyId = (int) $pdo->lastInsertId();
    $standard = standards_find_or_create_catalog(['company_id' => $companyId, 'standard_code' => 'ISO 9001', 'standard_name' => 'Quality Management Systems']);
    $version = standards_find_or_create_version((int) $standard['id'], ['version_label' => '2015', 'status' => 'published']);
    $clause = standards_find_or_create_clause((int) $version['id'], ['clause_code' => '7.2', 'title' => 'Competence']);
    $requirement = standards_find_or_create_requirement((int) $version['id'], (int) $clause['id'], ['requirement_code' => '7.2.a', 'title' => 'Determine competence', 'requirement_text' => 'Determine necessary competence.']);
    $control = standards_find_or_create_control((int) $requirement['id'], ['control_code' => '7.2-CTRL-1', 'title' => 'Competence matrix', 'control_text' => 'Maintain competence matrix.']);
    $risk = qms_entities_register(['company_id' => $companyId, 'entity_type' => 'risk', 'domain_table' => 'standards_test_risks', 'domain_record_id' => 9101, 'title' => 'Standards Mapping Risk', 'status' => 'active']);
    $mapping = standards_map_requirement(['requirement_id' => (int) $requirement['id'], 'source_entity_id' => (int) $risk['id'], 'relationship_type' => 'satisfies_requirement', 'description' => 'Test mapping']);
    $updatedRequirement = standards_save_requirement([
        'id' => (int) $requirement['id'],
        'version_id' => (int) $version['id'],
        'clause_id' => (int) $clause['id'],
        'requirement_code' => '7.2.a',
        'title' => 'Determine and update competence',
        'requirement_text' => 'Determine and maintain necessary competence.',
        'status' => 'active',
    ]);
    $assert(($standard['standard_code'] ?? '') === 'ISO 9001', 'Standard must be created.');
    $assert(($version['version_label'] ?? '') === '2015', 'Version must be created.');
    $assert(($requirement['requirement_code'] ?? '') === '7.2.a', 'Requirement must be created.');
    $assert(($updatedRequirement['title'] ?? '') === 'Determine and update competence', 'Requirement must be editable.');
    $assert(($control['control_code'] ?? '') === '7.2-CTRL-1', 'Control must be created.');
    $assert((int) ($mapping['source_entity_id'] ?? 0) === (int) $risk['id'], 'Requirement mapping must use source entity.');
    $assert(count(standards_requirement_mappings((int) $requirement['id'])) === 1, 'Requirement mapping must be listed.');
    standards_unmap_requirement((int) $mapping['id']);
    $assert(count(standards_requirement_mappings((int) $requirement['id'])) === 0, 'Requirement mapping must be archived.');
    $entityStmt = $pdo->prepare("SELECT COUNT(*) FROM qms_entities WHERE domain_table='standards_requirements' AND domain_record_id=:id AND entity_type='requirement'");
    $entityStmt->execute([':id' => (int) $requirement['id']]);
    $assert((int) $entityStmt->fetchColumn() === 1, 'Requirement must register managed entity.');
} finally {
    $pdo->rollBack();
}

$script = (string) file_get_contents(BASE_PATH . '/modules/standards/scripts/view.js');
$assert(str_contains($script, 'standards-requirements-table'), 'Standards script must initialize requirements table.');
$assert(str_contains($script, 'btn-modal-trigger'), 'Standards script must expose edit modal action.');
$form = (string) file_get_contents(BASE_PATH . '/modules/standards/modals/form.php');
$assert(str_contains($form, 'standards-form'), 'Standards form modal must exist.');
$mappingForm = (string) file_get_contents(BASE_PATH . '/modules/standards/modals/mapping_form.php');
$assert(str_contains($mappingForm, 'standards-mapping-form'), 'Requirement mapping form must exist.');
$sync = kirpi_ai_sync_schema_registry_from_manifests();
$assert(in_array(($sync['status'] ?? ''), ['success', 'partial'], true), 'AI schema sync failed.');
$entities = db()->query("SELECT entity_key FROM ai_schema_entities WHERE module_key='standards'")->fetchAll(PDO::FETCH_COLUMN);
foreach (['standard', 'standard_requirement'] as $entity) {
    $assert(in_array($entity, $entities, true), "Missing AI entity: {$entity}");
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}
fwrite(STDOUT, "Standards foundation: PASS\n");
