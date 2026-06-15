<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_action('GET', false);

$user = api_require_token('ai.view', 'ai:schema:read');

$includeSensitive = filter_var($_GET['include_sensitive'] ?? false, FILTER_VALIDATE_BOOLEAN);
$filterableOnly = filter_var($_GET['filterable_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
$search = trim((string) ($_GET['search'] ?? ''));
$limit = (int) ($_GET['limit'] ?? 50);

$result = kirpi_ai_discover_schema([
    'include_sensitive' => $includeSensitive,
    'filterable_only' => $filterableOnly,
    'search' => $search,
    'limit' => $limit,
], $user);

if (($result['status'] ?? '') !== 'success') {
    api_error(503, 'AI schema registry is not ready.', 'ai_schema_unavailable');
}

api_response(200, 'OK', [
    'entities' => (array) ($result['entities'] ?? []),
], (array) ($result['meta'] ?? []));
