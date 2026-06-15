<?php

$root = dirname(__DIR__);
$compose = file_get_contents($root . '/docker-compose.yml');
$localCompose = file_get_contents($root . '/docker-compose.local.yml');
$config = file_get_contents($root . '/core/config.php');
$validator = file_get_contents($root . '/scripts/validate-deployment.ps1');
$envExample = file_get_contents($root . '/.env.example');
$deploymentDocs = file_get_contents($root . '/docs/DEPLOYMENT_STANDARD.md');

$assertions = [
    'compose project prefix' => str_contains($compose, 'name: ${KIRPI_APP_PREFIX:-kirpicore}'),
    'network prefix' => str_contains($compose, 'KIRPI_NETWORK_NAME'),
    'database volume override' => str_contains($compose, 'KIRPI_DB_VOLUME_NAME'),
    'uploads volume override' => str_contains($compose, 'KIRPI_UPLOADS_VOLUME_NAME'),
    'logs volume override' => str_contains($compose, 'KIRPI_LOGS_VOLUME_NAME'),
    'no fixed container name' => !str_contains($compose, 'container_name:'),
    'session cookie env' => str_contains($compose, 'SESSION_COOKIE_NAME'),
    'session prefix isolation' => str_contains($config, "env('KIRPI_APP_PREFIX', 'kirpicore')"),
    'session cookie override' => str_contains($config, "env('SESSION_COOKIE_NAME', \$defaultSessionCookieName)"),
    'production compose has no host ports' => !preg_match('/^\s+ports:/m', $compose),
    'local http port override' => str_contains($localCompose, 'KIRPI_APP_HTTP_PORT'),
    'local database port override' => str_contains($localCompose, 'KIRPI_DB_HOST_PORT'),
    'dual instance validator' => str_contains($validator, 'Compose proje adlari ayrismadi')
        && str_contains($validator, 'Volume adlari ayrismadi'),
    'example prefix variable' => str_contains($envExample, "\nKIRPI_APP_PREFIX=kirpicore\n"),
    'derived database name is inactive' => !preg_match('/^DB_NAME=/m', $envExample),
    'derived session name is inactive' => !preg_match('/^SESSION_COOKIE_NAME=/m', $envExample),
    'derived compose project is inactive' => !preg_match('/^COMPOSE_PROJECT_NAME=/m', $envExample),
    'derived resource names are inactive' => !preg_match('/^KIRPI_(NETWORK|DB_VOLUME|UPLOADS_VOLUME|LOGS_VOLUME)_NAME=/m', $envExample),
    'local ports are inactive examples' => str_contains($envExample, '# KIRPI_APP_HTTP_PORT=8080')
        && str_contains($envExample, '# KIRPI_DB_HOST_PORT=3306')
        && !preg_match('/^KIRPI_(APP_HTTP|DB_HOST)_PORT=/m', $envExample),
    'full runtime settings' => str_contains($envExample, 'DOCUMENTS_MAX_UPLOAD_MB=25')
        && str_contains($envExample, 'AI_SQL_EXPLAIN_ENABLED=false')
        && str_contains($envExample, 'AUTO_DB_INSTALL=true'),
    'full runtime compose pass-through' => str_contains($compose, 'DOCUMENTS_MAX_UPLOAD_MB:')
        && str_contains($compose, 'AI_SQL_EXPLAIN_ENABLED:'),
    'legacy migration warning' => str_contains($deploymentDocs, 'Volume adlarını doğrulamadan deploy etmeyin'),
    'dokploy keeps internal ports' => str_contains($deploymentDocs, 'app')
        && str_contains($deploymentDocs, 'db:3306'),
];

$failed = array_keys(array_filter($assertions, static fn (bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, 'Deployment standard contract failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'Deployment standard contract passed (' . count($assertions) . ' assertions).' . PHP_EOL;
