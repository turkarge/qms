<?php

define('BASE_PATH', dirname(__DIR__));
define('KIRPI_CORE_ENTRY', true);

require BASE_PATH . '/core/config.php';
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/functions.php';
require BASE_PATH . '/modules/notifications/language.php';

$userId = (int) db()->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
if ($userId <= 0) {
    fwrite(STDERR, "Notification mark-read smoke test requires one user.\n");
    exit(2);
}

$insert = db()->prepare("INSERT INTO notifications (user_id, title, message, channel) VALUES (:user_id, :title, :message, 'in_app')");
$insert->execute([
    ':user_id' => $userId,
    ':title' => 'Notification Smoke Test',
    ':message' => 'Temporary mark-read verification.',
]);
$notificationId = (int) db()->lastInsertId();

$_SESSION['user'] = [
    'id' => $userId,
    'role_name' => 'Super Admin',
    'permissions' => [],
];
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_POST = [
    'id' => $notificationId,
    'csrf_token' => $_SESSION['csrf_token'],
];

ob_start();
register_shutdown_function(static function () use ($notificationId): void {
    $output = (string) ob_get_clean();
    $delete = db()->prepare('DELETE FROM notifications WHERE id = :id');
    $delete->execute([':id' => $notificationId]);

    $result = json_decode($output, true);
    $passed = is_array($result)
        && ($result['status'] ?? '') === 'success'
        && ($result['updated'] ?? false) === true
        && isset($result['unread_count']);

    if (!$passed) {
        fwrite(STDERR, 'Notification mark-read smoke test failed: ' . $output . PHP_EOL);
        return;
    }

    echo 'Notification mark-read smoke test passed. Unread count: ' . (int) $result['unread_count'] . PHP_EOL;
});

require BASE_PATH . '/modules/notifications/actions/mark_read.php';
