<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/api/language.php';

require_action('POST', false);

$actor = api_require_token('users.status', 'users:status');
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error(422, api_lang('invalid_user_id'));
}

$input = api_json_input();
$isActiveRaw = $input['is_active'] ?? null;

if ($isActiveRaw === null) {
    api_error(422, api_lang('is_active_required'));
}

$isActive = filter_var($isActiveRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if ($isActive === null) {
    $isActive = ((int) $isActiveRaw === 1);
}

try {
    $stmt = db()->prepare("\n        SELECT\n            u.id,\n            u.is_active,\n            r.name AS role_name\n        FROM users u\n        LEFT JOIN roles r ON r.id = u.role_id\n        WHERE u.id = :id\n        LIMIT 1\n    ");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        api_error(404, api_lang('user_not_found'));
    }

    $isSuperAdminUser = ((string) ($user['role_name'] ?? '')) === 'Super Admin';
    if ($isSuperAdminUser && !$isActive) {
        api_error(422, api_lang('super_admin_cannot_disable'));
    }

    $updateStmt = db()->prepare("UPDATE users SET is_active = :is_active WHERE id = :id");
    $updateStmt->execute([
        ':is_active' => $isActive ? 1 : 0,
        ':id' => $id,
    ]);

    kirpi_audit_log('api_status', 'users', [
        'actor_user_id' => (int) ($actor['id'] ?? 0),
        'target_user_id' => $id,
        'is_active' => $isActive,
    ], 'user', $id, 'success');

    api_response(200, api_lang('user_status_updated'), [
        'user' => [
            'id' => $id,
            'is_active' => $isActive,
        ],
    ]);
} catch (Throwable $e) {
    error_log('api users status error: ' . $e->getMessage());
    api_error(500, api_lang('user_status_failed'));
}


