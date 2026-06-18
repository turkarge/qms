<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from CLI.' . PHP_EOL);
}

define('BASE_PATH', __DIR__);
define('KIRPI_CORE_ENTRY', true);

require_once BASE_PATH . '/core/config.php';
require_once BASE_PATH . '/core/functions.php';

function shell_output(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function shell_error(string $message, int $code = 1): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

function shell_usage(): void
{
    shell_output('Kirpi Core Shell');
    shell_output('');
    shell_output('Usage:');
    shell_output('  php shell.php hash:password <password>');
    shell_output('  php shell.php db:create');
    shell_output('  php shell.php db:status');
    shell_output('  php shell.php db:tables');
    shell_output('  php shell.php db:query "<sql>"');
    shell_output('  php shell.php db:core:install');
    shell_output('  php shell.php db:modules:install [module]');
    shell_output('  php shell.php db:install');
    shell_output('  php shell.php db:permissions:install');
    shell_output('  php shell.php db:notifications:install');
    shell_output('  php shell.php db:notifications:seed-demo <user_id>');
    shell_output('  php shell.php qms:seed-demo');
    shell_output('  php shell.php queue:work-once [queue_name]');
    shell_output('  php shell.php queue:work [max_jobs] [queue_name]');
    shell_output('  php shell.php backup:create [label]');
    shell_output('  php shell.php backup:restore <backup_id>');
    shell_output('  php shell.php backup:verify <backup_id>');
    shell_output('  php shell.php backup:cleanup [keep_count]');
    shell_output('  php shell.php api:smoke [base_url] <email> <password>');
    shell_output('');
    shell_output('Examples:');
    shell_output('  php shell.php hash:password 123456');
    shell_output('  php shell.php db:create');
    shell_output('  php shell.php db:status');
    shell_output('  php shell.php db:tables');
    shell_output('  php shell.php db:query "SHOW TABLES"');
    shell_output('  php shell.php db:core:install');
    shell_output('  php shell.php db:modules:install notifications');
    shell_output('  php shell.php db:install');
    shell_output('  php shell.php db:permissions:install');
    shell_output('  php shell.php db:notifications:install');
    shell_output('  php shell.php db:notifications:seed-demo 1');
    shell_output('  php shell.php qms:seed-demo');
    shell_output('  php shell.php queue:work-once default');
    shell_output('  php shell.php queue:work 20 default');
    shell_output('  php shell.php backup:create deploy_oncesi');
    shell_output('  php shell.php backup:restore 1');
    shell_output('  php shell.php backup:verify 1');
    shell_output('  php shell.php backup:cleanup 20');
    shell_output('  php shell.php api:smoke https://core.kirpinetwork.com admin@kirpi.local 123456');
}

function shell_render_rows(array $rows): void
{
    if (empty($rows)) {
        shell_output('No rows returned.');
        return;
    }

    foreach ($rows as $row) {
        shell_output(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

function shell_boot_database(): void
{
    static $booted = false;
    global $pdo;

    if ($booted) {
        return;
    }

    require_once BASE_PATH . '/core/database.php';

    if (!isset($pdo) || !$pdo instanceof PDO) {
        shell_error('Database bootstrap failed: PDO instance is unavailable.');
    }

    $booted = true;
}

function shell_create_database_if_not_exists(): void
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $dbName = str_replace('`', '``', DB_NAME);
    $charset = preg_replace('/[^a-zA-Z0-9_]/', '', DB_CHARSET) ?: 'utf8mb4';

    $pdo->exec(sprintf(
        "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s_unicode_ci",
        $dbName,
        $charset,
        $charset
    ));
}

function shell_run_sql_file(string $filePath): int
{
    $schemaSql = file_get_contents($filePath);
    if ($schemaSql === false) {
        shell_error('SQL file could not be read: ' . $filePath);
    }

    $count = 0;

    foreach (array_filter(array_map('trim', explode(';', $schemaSql))) as $statement) {
        if ($statement === '') {
            continue;
        }

        db()->exec($statement);
        $count++;
    }

    return $count;
}

function shell_module_schema_files(?string $moduleName = null): array
{
    $paths = [];

    if ($moduleName !== null && $moduleName !== '') {
        $moduleSchemaPattern = BASE_PATH . '/modules/' . $moduleName . '/database/*.sql';
        $paths = glob($moduleSchemaPattern) ?: [];
        sort($paths);
        return $paths;
    }

    $moduleDirs = glob(BASE_PATH . '/modules/*', GLOB_ONLYDIR) ?: [];
    sort($moduleDirs);

    foreach ($moduleDirs as $moduleDir) {
        $schemaFiles = glob($moduleDir . '/database/*.sql') ?: [];
        sort($schemaFiles);

        foreach ($schemaFiles as $schemaFile) {
            $paths[] = $schemaFile;
        }
    }

    return $paths;
}

function shell_http_request(string $url, string $method = 'GET', array $headers = [], ?string $body = null): array
{
    $headerLines = [];
    foreach ($headers as $key => $value) {
        $headerLines[] = $key . ': ' . $value;
    }

    $httpOptions = [
        'method' => strtoupper($method),
        'header' => implode("\r\n", $headerLines),
        'ignore_errors' => true,
        'timeout' => 20,
    ];

    if ($body !== null) {
        $httpOptions['content'] = $body;
    }

    $options = [
        'http' => $httpOptions,
    ];

    if (stripos($url, 'https://') === 0 && env_bool('API_SMOKE_INSECURE', false)) {
        $options['ssl'] = [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ];
    }

    $context = stream_context_create($options);
    $responseBody = @file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];

    $statusCode = 0;
    if (!empty($responseHeaders[0]) && preg_match('#HTTP/\S+\s+(\d{3})#', (string) $responseHeaders[0], $m) === 1) {
        $statusCode = (int) $m[1];
    }

    $textBody = $responseBody === false ? '' : (string) $responseBody;
    $json = null;
    if ($textBody !== '') {
        $decoded = json_decode($textBody, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }
    }

    return [
        'status_code' => $statusCode,
        'headers' => $responseHeaders,
        'body' => $textBody,
        'json' => $json,
    ];
}

function shell_api_assert(string $title, int $actualStatus, int $expectedStatus, ?array $json = null, ?string $expectedErrorCode = null): bool
{
    $ok = $actualStatus === $expectedStatus;

    if ($ok && $expectedErrorCode !== null) {
        $actualErrorCode = (string) ($json['error_code'] ?? '');
        $ok = $actualErrorCode === $expectedErrorCode;
    }

    if ($ok) {
        shell_output('[OK] ' . $title . ' -> HTTP ' . $actualStatus);
        return true;
    }

    $message = '[FAIL] ' . $title . ' -> expected HTTP ' . $expectedStatus . ', got HTTP ' . $actualStatus;
    if ($expectedErrorCode !== null) {
        $message .= ' (expected error_code=' . $expectedErrorCode . ')';
    }
    shell_output($message);

    if (is_array($json)) {
        shell_output('  response: ' . json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    return false;
}

function shell_api_smoke(string $baseUrl, string $email, string $password): bool
{
    $baseUrl = rtrim(trim($baseUrl), '/');
    if ($baseUrl === '') {
        shell_error('Base URL is required for api:smoke.');
    }

    if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
        shell_error('Base URL is invalid: ' . $baseUrl);
    }

    $commonHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    $fullTokenResp = shell_http_request(
        $baseUrl . '/api/v1/auth/token',
        'POST',
        $commonHeaders,
        json_encode([
            'email' => $email,
            'password' => $password,
            'token_name' => 'smoke-full',
            'scopes' => ['*'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    $ok = true;
    $ok = shell_api_assert('Auth token (full scope)', (int) $fullTokenResp['status_code'], 200, is_array($fullTokenResp['json']) ? $fullTokenResp['json'] : null) && $ok;

    $fullToken = (string) (($fullTokenResp['json']['data']['access_token'] ?? ''));
    if ($fullToken === '') {
        shell_output('[FAIL] access_token alinmadi, smoke test durduruldu.');
        return false;
    }

    $meResp = shell_http_request(
        $baseUrl . '/api/v1/me',
        'GET',
        [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $fullToken,
        ]
    );
    $ok = shell_api_assert('/api/v1/me', (int) $meResp['status_code'], 200, is_array($meResp['json']) ? $meResp['json'] : null) && $ok;

    $usersResp = shell_http_request(
        $baseUrl . '/api/v1/users?page=1&per_page=5',
        'GET',
        [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $fullToken,
        ]
    );
    $ok = shell_api_assert('/api/v1/users', (int) $usersResp['status_code'], 200, is_array($usersResp['json']) ? $usersResp['json'] : null) && $ok;

    $limitedTokenResp = shell_http_request(
        $baseUrl . '/api/v1/auth/token',
        'POST',
        $commonHeaders,
        json_encode([
            'email' => $email,
            'password' => $password,
            'token_name' => 'smoke-limited',
            'scopes' => ['profile:read', 'users:read'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    $ok = shell_api_assert('Auth token (limited scope)', (int) $limitedTokenResp['status_code'], 200, is_array($limitedTokenResp['json']) ? $limitedTokenResp['json'] : null) && $ok;

    $limitedToken = (string) (($limitedTokenResp['json']['data']['access_token'] ?? ''));
    if ($limitedToken !== '') {
        $scopeDenyResp = shell_http_request(
            $baseUrl . '/api/v1/users',
            'POST',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $limitedToken,
            ],
            json_encode([
                'name' => 'Smoke Scope Deny',
                'email' => 'scope-deny@example.local',
                'password' => '123456',
                'password_confirm' => '123456',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $ok = shell_api_assert(
            'Scope deny test (limited token -> POST /users)',
            (int) $scopeDenyResp['status_code'],
            403,
            is_array($scopeDenyResp['json']) ? $scopeDenyResp['json'] : null,
            'scope_denied'
        ) && $ok;
    } else {
        shell_output('[FAIL] limited token alinmadi, scope deny testi atlandi.');
        $ok = false;
    }

    return $ok;
}

$command = $argv[1] ?? null;

if ($command === null || in_array($command, ['help', '--help', '-h'], true)) {
    shell_usage();
    exit(0);
}

try {
    switch ($command) {
        case 'hash:password':
            $password = $argv[2] ?? null;

            if ($password === null || $password === '') {
                shell_error('Password is required.');
            }

            shell_output(password_hash($password, PASSWORD_DEFAULT));
            break;

        case 'db:create':
            shell_create_database_if_not_exists();
            shell_output('Database ensured: ' . DB_NAME);
            break;

        case 'db:status':
            shell_boot_database();

            $stmt = db()->query('SELECT DATABASE() AS database_name, NOW() AS server_time');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                shell_error('Database connection is active but no status row was returned.');
            }

            shell_output('Database connection successful.');
            shell_output('Database: ' . ($row['database_name'] ?? '-'));
            shell_output('Server Time: ' . ($row['server_time'] ?? '-'));
            break;

        case 'db:tables':
            shell_boot_database();

            $stmt = db()->query('SHOW TABLES');
            $rows = $stmt->fetchAll(PDO::FETCH_NUM);

            if (empty($rows)) {
                shell_output('No tables found.');
                break;
            }

            foreach ($rows as $row) {
                shell_output((string) ($row[0] ?? ''));
            }
            break;

        case 'db:query':
            $sql = $argv[2] ?? null;

            if ($sql === null || trim($sql) === '') {
                shell_error('SQL query is required.');
            }

            shell_boot_database();

            $stmt = db()->query($sql);

            if ($stmt instanceof PDOStatement) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                shell_render_rows($rows);
                break;
            }

            shell_output('Query executed.');
            break;

        case 'db:core:install':
            shell_create_database_if_not_exists();
            shell_boot_database();

            $coreSchemaPath = BASE_PATH . '/database/core.sql';
            $statementCount = shell_run_sql_file($coreSchemaPath);

            shell_output('Core schema installed. Statements: ' . $statementCount);
            break;

        case 'db:modules:install':
            shell_create_database_if_not_exists();
            shell_boot_database();

            $moduleName = $argv[2] ?? null;
            $schemaFiles = shell_module_schema_files($moduleName);

            if (empty($schemaFiles)) {
                $target = $moduleName ? ('module "' . $moduleName . '"') : 'all modules';
                shell_output('No schema files found for ' . $target . '.');
                break;
            }

            $totalStatements = 0;
            foreach ($schemaFiles as $schemaFile) {
                $count = shell_run_sql_file($schemaFile);
                $totalStatements += $count;

                $relativePath = str_replace(BASE_PATH . '/', '', $schemaFile);
                shell_output('Installed: ' . $relativePath . ' (' . $count . ' statements)');
            }

            if (db_table_exists('permissions') && db_table_exists('role_permissions')) {
                sync_permission_catalog();
                shell_output('Permission catalog synced.');
            }

            shell_output('Module schemas installed. Total statements: ' . $totalStatements);
            break;

        case 'db:install':
            shell_create_database_if_not_exists();
            shell_boot_database();

            $coreSchemaPath = BASE_PATH . '/database/core.sql';
            $coreStatementCount = shell_run_sql_file($coreSchemaPath);
            shell_output('Core schema installed. Statements: ' . $coreStatementCount);

            $schemaFiles = shell_module_schema_files();
            $moduleStatementCount = 0;

            foreach ($schemaFiles as $schemaFile) {
                $count = shell_run_sql_file($schemaFile);
                $moduleStatementCount += $count;

                $relativePath = str_replace(BASE_PATH . '/', '', $schemaFile);
                shell_output('Installed: ' . $relativePath . ' (' . $count . ' statements)');
            }

            if (db_table_exists('permissions') && db_table_exists('role_permissions')) {
                sync_permission_catalog();
                shell_output('Permission catalog synced.');
            }

            shell_output('Database setup completed. Core statements: ' . $coreStatementCount . ', Module statements: ' . $moduleStatementCount);
            break;

        case 'db:permissions:install':
            shell_create_database_if_not_exists();
            shell_boot_database();

            $statementCount = shell_run_sql_file(BASE_PATH . '/modules/roles/database/permissions.sql');

            sync_permission_catalog();
            shell_output('Permission schema installed. Statements: ' . $statementCount);
            shell_output('Core permissions synced.');
            break;

        case 'db:notifications:install':
            shell_create_database_if_not_exists();
            shell_boot_database();

            $statementCount = shell_run_sql_file(BASE_PATH . '/modules/notifications/database/schema.sql');

            shell_output('Notification schema installed. Statements: ' . $statementCount);
            break;

        case 'db:notifications:seed-demo':
            shell_boot_database();

            if (!db_table_exists('notifications')) {
                shell_error('notifications table is not installed. Run db:notifications:install first.');
            }

            $userId = (int) ($argv[2] ?? 0);
            if ($userId <= 0) {
                shell_error('User ID is required.');
            }

            $userStmt = db()->prepare('SELECT id, name, email FROM users WHERE id = :id LIMIT 1');
            $userStmt->execute([
                ':id' => $userId,
            ]);

            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                shell_error('User not found.');
            }

            $notifications = [
                [
                    'title' => 'Hoş geldiniz',
                    'message' => ($user['name'] ?? 'Kullanıcı') . ' için demo bildirim oluşturuldu.',
                    'channel' => 'in_app',
                    'read_at' => null,
                ],
                [
                    'title' => 'Rol güncellendi',
                    'message' => 'Kullanıcı rol değişikliği başarıyla tamamlandı.',
                    'channel' => 'in_app',
                    'read_at' => null,
                ],
                [
                    'title' => 'Güvenlik bildirimi',
                    'message' => 'Son giriş hareketiniz kaydedildi.',
                    'channel' => 'email',
                    'read_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'title' => 'Sistem duyurusu',
                    'message' => 'Bildirim modülü test verisi başarıyla eklendi.',
                    'channel' => 'in_app',
                    'read_at' => null,
                ],
            ];

            $insertStmt = db()->prepare("
                INSERT INTO notifications (user_id, title, message, channel, read_at)
                VALUES (:user_id, :title, :message, :channel, :read_at)
            ");

            foreach ($notifications as $notification) {
                $insertStmt->execute([
                    ':user_id' => $userId,
                    ':title' => $notification['title'],
                    ':message' => $notification['message'],
                    ':channel' => $notification['channel'],
                    ':read_at' => $notification['read_at'],
                ]);
            }

            shell_output('Demo notifications inserted for user #' . $userId . '.');
            break;

        case 'qms:seed-demo':
            shell_boot_database();

            require_once BASE_PATH . '/modules/qms_entities/demo_seed.php';
            $result = qms_demo_seed_data();

            shell_output('QMS demo seed completed.');
            shell_output('Company ID: ' . (int) ($result['company_id'] ?? 0));
            shell_output('Users: ' . count((array) ($result['users'] ?? [])));
            shell_output('Units: ' . count((array) ($result['units'] ?? [])));
            shell_output('Entities: ' . count((array) ($result['entities'] ?? [])));
            shell_output('Relationships: ' . count((array) ($result['relationships'] ?? [])));
            shell_output('Events: ' . count((array) ($result['events'] ?? [])));
            shell_output('Standards: ' . count((array) ($result['standards'] ?? [])));
            break;

        case 'queue:work-once':
            shell_boot_database();

            $queueName = trim((string) ($argv[2] ?? 'default'));
            if ($queueName === '') {
                $queueName = 'default';
            }

            $result = kirpi_queue_work_once($queueName);
            shell_output(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            break;

        case 'queue:work':
            shell_boot_database();

            $maxJobs = (int) ($argv[2] ?? 10);
            $queueName = trim((string) ($argv[3] ?? 'default'));
            if ($maxJobs <= 0) {
                $maxJobs = 10;
            }
            if ($maxJobs > 1000) {
                $maxJobs = 1000;
            }
            if ($queueName === '') {
                $queueName = 'default';
            }

            $processed = 0;
            for ($i = 0; $i < $maxJobs; $i++) {
                $result = kirpi_queue_work_once($queueName);
                shell_output(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                if (($result['status'] ?? '') === 'processed') {
                    $processed++;
                    continue;
                }

                if (($result['status'] ?? '') === 'failed') {
                    continue;
                }

                break;
            }

            shell_output('Queue worker finished. Processed: ' . $processed);
            break;

        case 'backup:create':
            shell_boot_database();

            $label = trim((string) ($argv[2] ?? ''));
            $result = kirpi_backup_create($label !== '' ? $label : null, null);
            shell_output(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            if (!($result['success'] ?? false)) {
                shell_error((string) ($result['message'] ?? 'Backup create failed.'), 2);
            }
            break;

        case 'backup:restore':
            shell_boot_database();

            $backupId = (int) ($argv[2] ?? 0);
            if ($backupId <= 0) {
                shell_error('Backup ID is required.');
            }

            $result = kirpi_backup_restore($backupId, null);
            shell_output(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            if (!($result['success'] ?? false)) {
                shell_error((string) ($result['message'] ?? 'Backup restore failed.'), 2);
            }
            break;

        case 'backup:verify':
            shell_boot_database();

            $backupId = (int) ($argv[2] ?? 0);
            if ($backupId <= 0) {
                shell_error('Backup ID is required.');
            }

            $result = kirpi_backup_verify($backupId, null);
            shell_output(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            if (!($result['success'] ?? false)) {
                shell_error((string) ($result['message'] ?? 'Backup verify failed.'), 2);
            }
            break;

        case 'backup:cleanup':
            shell_boot_database();

            $keepCount = (int) ($argv[2] ?? 0);
            $result = kirpi_backup_apply_retention($keepCount > 0 ? $keepCount : null);
            shell_output(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            break;

        case 'api:smoke':
            $baseArg = trim((string) ($argv[2] ?? ''));
            $emailArg = trim((string) ($argv[3] ?? ''));
            $passwordArg = (string) ($argv[4] ?? '');

            if ($passwordArg === '' && $emailArg !== '' && strpos($emailArg, '@') !== false) {
                // Full form expected: api:smoke <base_url> <email> <password>
                shell_error('Password is required. Usage: php shell.php api:smoke [base_url] <email> <password>');
            }

            if ($baseArg !== '' && strpos($baseArg, '@') !== false) {
                // Short form: api:smoke <email> <password>
                $passwordArg = (string) ($argv[3] ?? '');
                $emailArg = $baseArg;
                $baseArg = BASE_URL;
            }

            if ($baseArg === '') {
                $baseArg = BASE_URL;
            }

            if ($emailArg === '' || $passwordArg === '') {
                shell_error('Usage: php shell.php api:smoke [base_url] <email> <password>');
            }

            $smokeOk = shell_api_smoke($baseArg, $emailArg, $passwordArg);
            if (!$smokeOk) {
                shell_error('API smoke test failed.', 2);
            }

            shell_output('API smoke test passed.');
            break;

        default:
            shell_error('Unknown command: ' . $command);
    }
} catch (Throwable $e) {
    shell_error('Shell error: ' . $e->getMessage());
}
