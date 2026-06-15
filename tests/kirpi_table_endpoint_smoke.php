<?php

$module = (string) ($argv[1] ?? '');
$allowed = ['users', 'roles', 'audit', 'notifications'];
if (!in_array($module, $allowed, true)) {
    fwrite(STDERR, "Usage: php tests/kirpi_table_endpoint_smoke.php <users|roles|audit|notifications>\n");
    exit(2);
}

define('BASE_PATH', dirname(__DIR__));
define('KIRPI_CORE_ENTRY', true);

require BASE_PATH . '/core/config.php';
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/functions.php';

$userId = (int) db()->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
if ($userId <= 0) {
    fwrite(STDERR, "KirpiTable endpoint smoke test requires one user.\n");
    exit(3);
}

$_SESSION['user'] = [
    'id' => $userId,
    'role_name' => 'Super Admin',
    'permissions' => [],
];
$_SERVER['REQUEST_METHOD'] = 'GET';

$primaryColumn = match ($module) {
    'users', 'roles' => ['data' => 'name', 'name' => 'name'],
    'audit' => ['data' => 'id', 'name' => 'id'],
    'notifications' => ['data' => 'created_at_display', 'name' => 'created_at'],
};
$primaryColumn['search'] = ['value' => ''];

$_GET = [
    'draw' => 1,
    'start' => 0,
    'length' => 10,
    'columns' => [$primaryColumn],
    'order' => [['column' => 0, 'dir' => $module === 'roles' ? 'asc' : 'desc']],
    'search' => ['value' => ''],
];

require BASE_PATH . "/modules/{$module}/actions/datatable.php";
