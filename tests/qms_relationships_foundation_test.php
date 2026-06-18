<?php
define('BASE_PATH', dirname(__DIR__));
define('KIRPI_CORE_ENTRY', true);
require BASE_PATH . '/core/config.php';
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/functions.php';
require BASE_PATH . '/modules/qms_relationships/helpers.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$manifest = json_decode((string) file_get_contents(BASE_PATH . '/modules/qms_relationships/module.json'), true);
$assert(($manifest['key'] ?? '') === 'qms_relationships', 'Invalid qms_relationships manifest.');
$assert(in_array('qms_entities', (array) ($manifest['requires'] ?? []), true), 'qms_relationships must depend on qms_entities.');
foreach (['qms_relationship_types', 'qms_entity_relationships'] as $table) {
    $assert(db_table_exists($table), "Missing table: {$table}");
}

$permissions = db()->query("SELECT slug FROM permissions WHERE slug LIKE 'qms_relationships.%'")->fetchAll(PDO::FETCH_COLUMN);
foreach (['qms_relationships.view', 'qms_relationships.manage', 'qms_relationships.archive'] as $permission) {
    $assert(in_array($permission, $permissions, true), "Missing permission: {$permission}");
}

$routes = require BASE_PATH . '/modules/qms_relationships/routes.php';
foreach (['qms_relationships/view', 'ajax/qms_relationships/datatable', 'ajax/qms_relationships/form', 'qms_relationships/actions/save', 'qms_relationships/actions/archive'] as $route) {
    $assert(isset($routes[$route]), "Missing route: {$route}");
}

$types = db()->query("SELECT relationship_type FROM qms_relationship_types")->fetchAll(PDO::FETCH_COLUMN);
foreach (['satisfies_requirement', 'provides_evidence_for', 'depends_on', 'references'] as $type) {
    $assert(in_array($type, $types, true), "Missing relationship type: {$type}");
}
$assert(qms_relationships_type_label('satisfies_requirement') === 'Gerekliliği Karşılar', 'Relationship type labels must be localized.');
$assert(qms_relationships_kind_label('evidence') === 'Kanıt', 'Relationship kind labels must be localized.');

$pdo = db();
$pdo->beginTransaction();
try {
    $suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $userStmt = $pdo->prepare("INSERT INTO users(name, email, password, is_active) VALUES(:name, :email, :password, 1)");
    $userStmt->execute([':name' => 'Relationship Tester', ':email' => 'relationship-' . strtolower($suffix) . '@example.test', ':password' => password_hash('Test1234!', PASSWORD_DEFAULT)]);
    $_SESSION['user'] = ['id' => (int) $pdo->lastInsertId(), 'role_name' => 'Super Admin', 'permissions' => ['*']];
    $companyStmt = $pdo->prepare("INSERT INTO organization_companies(company_code, company_name, status) VALUES(:code, :name, 'active')");
    $companyStmt->execute([':code' => 'R' . $suffix, ':name' => 'Relationship Test ' . $suffix]);
    $company = (int) $pdo->lastInsertId();
    $source = qms_entities_register(['company_id' => $company, 'entity_type' => 'risk', 'domain_table' => 'risks', 'domain_record_id' => 7001, 'title' => 'Relationship Risk', 'status' => 'active']);
    $target = qms_entities_register(['company_id' => $company, 'entity_type' => 'requirement', 'domain_table' => 'requirements', 'domain_record_id' => 7002, 'title' => 'Relationship Requirement', 'status' => 'active']);
    $relationship = qms_relationships_save(['company_id' => $company, 'source_entity_id' => $source['id'], 'target_entity_id' => $target['id'], 'relationship_type' => 'satisfies_requirement', 'status' => 'active']);
    $assert(($relationship['relationship_uid'] ?? '') !== '', 'Relationship must have a UUID.');
    $assert(($relationship['relationship_kind'] ?? '') === 'direct', 'Relationship must inherit kind from type.');
    $assert(($relationship['relationship_type_name'] ?? '') === 'Gerekliliği Karşılar', 'Relationship row must expose localized type label.');
    $assert((int) ($relationship['company_id'] ?? 0) === $company, 'Relationship company must match entity company.');
    try {
        qms_relationships_save(['company_id' => $company, 'source_entity_id' => $source['id'], 'target_entity_id' => $source['id'], 'relationship_type' => 'references', 'status' => 'active']);
        $assert(false, 'Same source/target relationship must fail.');
    } catch (InvalidArgumentException $e) {
        $assert($e->getMessage() === 'same_entity_error', 'Same entity validation must return same_entity_error.');
    }
} finally {
    $pdo->rollBack();
}

$script = (string) file_get_contents(BASE_PATH . '/modules/qms_relationships/scripts/view.js');
$assert(str_contains($script, 'data-company-entity'), 'Relationship form script must filter entities by company.');
$assert(str_contains($script, 'js-qms-relationship-archive'), 'Relationship table must expose archive action.');

$sync = kirpi_ai_sync_schema_registry_from_manifests();
$assert(in_array(($sync['status'] ?? ''), ['success', 'partial'], true), 'AI schema sync failed.');
$entities = db()->query("SELECT entity_key FROM ai_schema_entities WHERE module_key = 'qms_relationships'")->fetchAll(PDO::FETCH_COLUMN);
foreach (['relationship_type', 'entity_relationship'] as $entity) {
    $assert(in_array($entity, $entities, true), "Missing AI entity: {$entity}");
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}
fwrite(STDOUT, "QMS relationships foundation: PASS\n");
