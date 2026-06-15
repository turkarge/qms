<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/notifications/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => notifications_lang('csrf_failed'),
    ], 419);
}

if (!db_table_exists('notifications')) {
    json_response([
        'status' => 'error',
        'message' => notifications_lang('table_not_ready'),
    ], 422);
}

$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || $userId <= 0) {
    json_response([
        'status' => 'error',
        'message' => notifications_lang('invalid_request'),
    ], 422);
}

try {
    $stmt = db()->prepare("
        UPDATE notifications
        SET read_at = NOW()
        WHERE id = :id
          AND user_id = :user_id
          AND read_at IS NULL
    ");
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $userId,
    ]);

    $unreadCount = get_unread_notifications_count($userId);

    json_response([
        'status' => 'success',
        'message' => notifications_lang('mark_read_success'),
        'updated' => $stmt->rowCount() > 0,
        'unread_count' => $unreadCount,
    ]);
} catch (Throwable $e) {
    error_log('notifications mark read error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => notifications_lang('mark_read_error'),
    ], 500);
}
