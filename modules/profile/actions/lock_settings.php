<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/profile/language.php';

require_action('POST', true);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response([
        'status' => 'error',
        'message' => profile_lang('csrf_failed'),
    ], 419);
}

$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);
$lockEnabled = isset($_POST['lock_enabled']) ? 1 : 0;
$lockPin = trim((string) ($_POST['lock_pin'] ?? ''));
$lockPinConfirm = trim((string) ($_POST['lock_pin_confirm'] ?? ''));

if ($userId <= 0) {
    json_response([
        'status' => 'error',
        'message' => profile_lang('valid_session_required'),
    ], 403);
}

if (!kirpi_auth_lock_schema_ready()) {
    json_response([
        'status' => 'error',
        'message' => profile_lang('lock_infra_missing'),
    ], 422);
}

try {
    $currentStmt = db()->prepare("\n        SELECT lock_pin_hash\n        FROM users\n        WHERE id = :id\n        LIMIT 1\n    ");
    $currentStmt->execute([
        ':id' => $userId,
    ]);
    $currentRow = $currentStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $currentPinHash = (string) ($currentRow['lock_pin_hash'] ?? '');

    $updatePin = ($lockPin !== '' || $lockPinConfirm !== '');
    if ($updatePin) {
        if (!preg_match('/^\d{4}$/', $lockPin)) {
            json_response([
                'status' => 'error',
                'message' => profile_lang('key_format_error'),
            ], 422);
        }

        if ($lockPin !== $lockPinConfirm) {
            json_response([
                'status' => 'error',
                'message' => profile_lang('key_repeat_error'),
            ], 422);
        }
    }

    if ($lockEnabled === 1 && !$updatePin && $currentPinHash === '') {
        json_response([
            'status' => 'error',
            'message' => profile_lang('key_required_for_enable'),
        ], 422);
    }

    $fields = ['lock_enabled = :lock_enabled'];
    $params = [
        ':id' => $userId,
        ':lock_enabled' => $lockEnabled,
    ];

    if ($updatePin) {
        $fields[] = 'lock_pin_hash = :lock_pin_hash';
        $params[':lock_pin_hash'] = password_hash($lockPin, PASSWORD_DEFAULT);
    }

    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $_SESSION['user']['lock_enabled'] = $lockEnabled === 1;
    if ($lockEnabled !== 1 && kirpi_session_lock_state()) {
        kirpi_unlock_session();
    }

    kirpi_audit_log('lock_settings_update', 'profile', [
        'target_user_id' => $userId,
        'lock_enabled' => $lockEnabled,
        'pin_updated' => $updatePin,
    ], 'user', $userId, 'success');

    json_response([
        'status' => 'success',
        'message' => profile_lang('lock_settings_updated'),
        'reload_page' => true,
    ]);
} catch (Throwable $e) {
    error_log('profile lock settings update error: ' . $e->getMessage());

    json_response([
        'status' => 'error',
        'message' => profile_lang('settings_update_error'),
    ], 500);
}
