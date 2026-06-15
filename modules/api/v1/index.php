<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/api/language.php';

require_action('GET', false);

api_response(200, api_lang('v1_title'), [
    'enabled' => api_is_enabled(),
    'endpoints' => [
        [
            'method' => 'POST',
            'path' => '/api/v1/auth/token',
            'description' => api_lang('desc_token'),
        ],
        [
            'method' => 'GET',
            'path' => '/api/v1/me',
            'description' => api_lang('desc_me'),
            'required_scope' => 'profile:read',
        ],
        [
            'method' => 'GET',
            'path' => '/api/v1/ai/schema',
            'description' => 'Discover AI schema metadata allowed for the current token.',
            'required_scope' => 'ai:schema:read',
        ],
        [
            'method' => 'GET',
            'path' => '/api/v1/ai/schema/search',
            'description' => 'Search AI schema metadata allowed for the current token.',
            'required_scope' => 'ai:schema:read',
        ],
        [
            'method' => 'GET',
            'path' => '/api/v1/users',
            'description' => api_lang('desc_users_list'),
            'required_scope' => 'users:read',
        ],
        [
            'method' => 'POST',
            'path' => '/api/v1/users',
            'description' => api_lang('desc_users_create'),
            'required_scope' => 'users:create',
        ],
        [
            'method' => 'PATCH',
            'path' => '/api/v1/users/{id}',
            'description' => api_lang('desc_users_update'),
            'required_scope' => 'users:update',
        ],
        [
            'method' => 'POST',
            'path' => '/api/v1/users/{id}/status',
            'description' => api_lang('desc_users_status'),
            'required_scope' => 'users:status',
        ],
        [
            'method' => 'GET',
            'path' => '/api/v1/postman-collection',
            'description' => api_lang('desc_postman_download'),
        ],
        [
            'method' => 'GET',
            'path' => '/api/v1/postman',
            'description' => api_lang('desc_postman_compat'),
        ],
        [
            'method' => 'GET',
            'path' => '/api/v1/postman-collection.json',
            'description' => api_lang('desc_postman_compat'),
        ],
    ],
]);
