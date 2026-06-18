<?php
define('BASE_PATH', dirname(__DIR__));
define('KIRPI_CORE_ENTRY', true);
require BASE_PATH . '/core/config.php';
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/functions.php';
require BASE_PATH . '/modules/qms_events/helpers.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$manifest = json_decode((string) file_get_contents(BASE_PATH . '/modules/qms_events/module.json'), true);
$assert(($manifest['key'] ?? '') === 'qms_events', 'Invalid qms_events manifest.');
foreach (['organization', 'qms_entities', 'qms_relationships'] as $dependency) {
    $assert(in_array($dependency, (array) ($manifest['requires'] ?? []), true), "Missing dependency: {$dependency}");
}
$assert(db_table_exists('qms_domain_events'), 'Missing qms_domain_events table.');

$permissions = db()->query("SELECT slug FROM permissions WHERE slug LIKE 'qms_events.%'")->fetchAll(PDO::FETCH_COLUMN);
$assert(in_array('qms_events.view', $permissions, true), 'Missing qms_events.view permission.');

$routes = require BASE_PATH . '/modules/qms_events/routes.php';
foreach (['qms_events/view', 'ajax/qms_events/datatable'] as $route) {
    $assert(isset($routes[$route]), "Missing route: {$route}");
}
$assert(!isset($routes['qms_events/actions/save']), 'qms_events must not expose update route.');
$assert(!isset($routes['qms_events/actions/archive']), 'qms_events must not expose archive route.');

$pdo = db();
$pdo->beginTransaction();
try {
    $suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $userStmt = $pdo->prepare("INSERT INTO users(name, email, password, is_active) VALUES(:name, :email, :password, 1)");
    $userStmt->execute([':name' => 'Event Tester', ':email' => 'event-' . strtolower($suffix) . '@example.test', ':password' => password_hash('Test1234!', PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();
    $_SESSION['user'] = ['id' => $userId, 'role_name' => 'Super Admin', 'permissions' => ['*']];
    $companyStmt = $pdo->prepare("INSERT INTO organization_companies(company_code, company_name, status) VALUES(:code, :name, 'active')");
    $companyStmt->execute([':code' => 'E' . $suffix, ':name' => 'Event Test ' . $suffix]);
    $companyId = (int) $pdo->lastInsertId();
    $entity = qms_entities_register(['company_id' => $companyId, 'entity_type' => 'risk', 'domain_table' => 'risks', 'domain_record_id' => 8001, 'title' => 'Event Risk', 'status' => 'active']);
    $event = qms_events_publish([
        'event_type' => 'risk.created.v1',
        'entity_type' => 'risk',
        'entity_id' => (int) $entity['id'],
        'company_id' => $companyId,
        'actor_type' => 'user',
        'payload_version' => 1,
        'payload' => ['title' => 'Event Risk'],
        'metadata' => ['test' => true],
        'source_module' => 'qms_events_test',
    ]);
    $assert(($event['event_id'] ?? '') !== '', 'Event must have UUID.');
    $assert(($event['correlation_id'] ?? '') !== '', 'Event must have correlation id.');
    $assert(($event['event_type_name'] ?? '') === 'Risk Olusturuldu', 'Event type label must be localized.');
    $assert((int) ($event['actor_user_id'] ?? 0) === $userId, 'User actor must be stored.');
    try {
        qms_events_publish(['event_type' => 'risk.created.v1', 'entity_type' => 'risk']);
        $assert(false, 'Incomplete event must fail.');
    } catch (InvalidArgumentException $e) {
        $assert($e->getMessage() === 'required_fields', 'Incomplete event must return required_fields.');
    }
} finally {
    $pdo->rollBack();
}

$script = (string) file_get_contents(BASE_PATH . '/modules/qms_events/scripts/view.js');
$assert(str_contains($script, 'qms-events-table'), 'Event timeline script must initialize table.');
$assert(!str_contains($script, 'actions/save'), 'Event timeline script must not save events.');
$assert(!str_contains($script, 'actions/archive'), 'Event timeline script must not archive events.');

$sync = kirpi_ai_sync_schema_registry_from_manifests();
$assert(in_array(($sync['status'] ?? ''), ['success', 'partial'], true), 'AI schema sync failed.');
$entities = db()->query("SELECT entity_key FROM ai_schema_entities WHERE module_key = 'qms_events'")->fetchAll(PDO::FETCH_COLUMN);
$assert(in_array('domain_event', $entities, true), 'Missing AI entity: domain_event');

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}
fwrite(STDOUT, "QMS events foundation: PASS\n");
