<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/api/language.php';

require_action('POST', false);

if (!api_token_table_ready()) {
    api_error(503, api_lang('token_table_missing'), 'api_token_table_missing');
}

$input = api_json_input();
$email = strtolower(trim((string) ($input['email'] ?? ($_POST['email'] ?? ''))));
$password = (string) ($input['password'] ?? ($_POST['password'] ?? ''));
$tokenName = trim((string) ($input['token_name'] ?? ($_POST['token_name'] ?? 'default')));
$scopesInput = $input['scopes'] ?? ($_POST['scopes'] ?? ['*']);
$scopes = api_normalize_scopes($scopesInput);

if ($email === '' || $password === '') {
    api_error(422, api_lang('missing_credentials'), 'missing_credentials');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_error(422, api_lang('invalid_email'), 'invalid_email');
}

try {
    $stmt = db()->prepare("\n        SELECT\n            u.id,\n            u.email,\n            u.password,\n            u.is_active,\n            u.role_id,\n            r.name AS role_name,\n            r.is_active AS role_is_active\n        FROM users u\n        LEFT JOIN roles r ON r.id = u.role_id\n        WHERE u.email = :email\n        LIMIT 1\n    ");
    $stmt->execute([
        ':email' => $email,
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, (string) ($user['password'] ?? ''))) {
        kirpi_audit_log('api_token_failed', 'api', [
            'email' => $email,
            'reason' => 'invalid_credentials',
        ], 'api_token', null, 'failed');

        api_error(401, api_lang('invalid_credentials'), 'invalid_credentials');
    }

    if ((int) ($user['is_active'] ?? 0) !== 1) {
        api_error(403, api_lang('user_inactive'), 'user_inactive');
    }

    if (($user['role_id'] ?? null) && isset($user['role_is_active']) && (int) $user['role_is_active'] !== 1) {
        api_error(403, api_lang('role_inactive'), 'role_inactive');
    }

    $issued = api_issue_token_for_user(
        (int) ($user['id'] ?? 0),
        $tokenName !== '' ? $tokenName : 'default',
        null,
        $scopes
    );
    if (!$issued) {
        api_error(500, api_lang('token_create_failed'), 'token_issue_failed');
    }

    kirpi_audit_log('api_token_create', 'api', [
        'user_id' => (int) ($user['id'] ?? 0),
        'email' => $email,
        'token_name' => $tokenName,
        'scopes' => $scopes,
    ], 'api_token', null, 'success');

    api_response(200, api_lang('token_created'), [
        'token_type' => 'Bearer',
        'access_token' => (string) ($issued['token'] ?? ''),
        'expires_at' => (string) ($issued['expires_at'] ?? ''),
        'scopes' => (array) ($issued['scopes'] ?? ['*']),
    ]);
} catch (Throwable $e) {
    error_log('api token create error: ' . $e->getMessage());
    api_error(500, api_lang('token_create_exception'), 'token_issue_exception');
}
