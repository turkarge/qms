<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_action('GET', false);

$user = api_require_token('ai.view', 'ai:schema:read');
$query = trim((string) ($_GET['q'] ?? $_GET['query'] ?? ''));
$limit = (int) ($_GET['limit'] ?? 10);

$result = kirpi_ai_search_schema($query, [
    'limit' => $limit,
], $user);

if (($result['status'] ?? '') !== 'success') {
    api_error(503, 'AI schema search is not available.', 'ai_schema_search_unavailable');
}

api_response(200, 'OK', [
    'query' => (string) ($result['query'] ?? $query),
    'tokens' => (array) ($result['tokens'] ?? []),
    'results' => (array) ($result['results'] ?? []),
], (array) ($result['meta'] ?? []));
