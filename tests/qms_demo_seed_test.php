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

$pdo = db();
$pdo->beginTransaction();
try {
    $first = qms_demo_seed_data();
    $second = qms_demo_seed_data();
    $assert((int) ($first['company_id'] ?? 0) > 0, 'Demo seed must create company.');
    $assert((int) ($first['company_id'] ?? 0) === (int) ($second['company_id'] ?? 0), 'Demo seed must be idempotent for company.');
    $assert(count((array) ($first['entities'] ?? [])) >= 5, 'Demo seed must create representative QMS entities.');
    $assert(count((array) ($second['entities'] ?? [])) === count((array) ($first['entities'] ?? [])), 'Demo seed must not duplicate entities.');
    if (db_table_exists('qms_entity_relationships')) {
        $assert(count((array) ($first['relationships'] ?? [])) >= 3, 'Demo seed must create relationship samples.');
        $assert(count((array) ($second['relationships'] ?? [])) === count((array) ($first['relationships'] ?? [])), 'Demo seed must not duplicate relationships.');
    }
} finally {
    $pdo->rollBack();
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}

fwrite(STDOUT, "QMS demo seed: PASS\n");
