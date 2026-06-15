<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_action('GET', false);

$user = api_require_token(null, 'profile:read');

api_response(200, 'OK', [
    'user' => [
        'id' => (int) ($user['id'] ?? 0),
        'name' => (string) ($user['name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'avatar_url' => !empty($user['avatar']) ? base_url('uploads/avatars/' . ltrim((string) $user['avatar'], '/')) : null,
        'role_id' => $user['role_id'] ?? null,
        'role_name' => $user['role_name'] ?? null,
        'permissions' => array_values((array) ($user['permissions'] ?? [])),
    ],
]);


