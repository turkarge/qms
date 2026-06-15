<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

$statusCode = 200;
$checks = [
    'app' => 'ok',
    'db' => 'ok',
];

try {
    db()->query('SELECT 1');
} catch (Throwable $e) {
    $checks['db'] = 'error';
    $statusCode = 503;
}

json_response([
    'status' => $statusCode === 200 ? 'ok' : 'degraded',
    'checks' => $checks,
    'timestamp' => date('c'),
], $statusCode);
