<?php

function api_is_enabled(): bool
{
    if (function_exists('kirpi_settings_table_ready') && kirpi_settings_table_ready()) {
        return kirpi_setting_bool('api.enabled', env_bool('API_ENABLED', true));
    }

    return env_bool('API_ENABLED', true);
}

function api_json_input(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $raw = (string) file_get_contents('php://input');
    if (trim($raw) === '') {
        $cached = [];
        return $cached;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $cached = [];
        return $cached;
    }

    $cached = $decoded;
    return $cached;
}

function api_response(int $statusCode, string $message, array $data = [], array $meta = [], ?string $errorCode = null): never
{
    $payload = [
        'status' => $statusCode >= 200 && $statusCode < 300 ? 'success' : 'error',
        'message' => $message,
        'data' => $data,
    ];

    if ($statusCode < 200 || $statusCode >= 300) {
        if ($errorCode === null || trim($errorCode) === '') {
            $errorCode = match ($statusCode) {
                400 => 'bad_request',
                401 => 'unauthorized',
                403 => 'forbidden',
                404 => 'not_found',
                405 => 'method_not_allowed',
                409 => 'conflict',
                422 => 'validation_error',
                429 => 'rate_limited',
                500 => 'internal_error',
                503 => 'service_unavailable',
                default => 'api_error',
            };
        }

        $payload['error_code'] = $errorCode;
    }

    if (!empty($meta)) {
        $payload['meta'] = $meta;
    }

    api_log_response($statusCode, $errorCode);
    json_response($payload, $statusCode);
}

function api_error(int $statusCode, string $message, array|string $metaOrErrorCode = [], array $meta = []): never
{
    $errorCode = null;
    $resolvedMeta = $meta;

    if (is_string($metaOrErrorCode)) {
        $errorCode = trim($metaOrErrorCode) !== '' ? trim($metaOrErrorCode) : null;
    } else {
        $resolvedMeta = $metaOrErrorCode;
    }

    api_response($statusCode, $message, [], $resolvedMeta, $errorCode);
}

function api_extract_bearer_token(): ?string
{
    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($header === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = (string) ($headers['Authorization'] ?? $headers['authorization'] ?? '');
    }

    if ($header === '') {
        return null;
    }

    if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $header, $matches) !== 1) {
        return null;
    }

    $token = trim((string) ($matches[1] ?? ''));
    return $token !== '' ? $token : null;
}

function api_token_table_ready(): bool
{
    return db_table_exists('api_tokens');
}

function api_token_scope_table_ready(): bool
{
    return db_table_exists('api_token_scopes');
}

function api_request_log_table_ready(): bool
{
    return db_table_exists('api_request_logs');
}

function api_request_log_retention_days(): int
{
    $days = (int) env('API_REQUEST_LOG_RETENTION_DAYS', '90');
    if ($days < 1) {
        $days = 1;
    }
    if ($days > 3650) {
        $days = 3650;
    }

    return $days;
}

function api_cleanup_request_logs_if_needed(): void
{
    if (!api_request_log_table_ready()) {
        return;
    }

    try {
        if (random_int(1, 100) !== 1) {
            return;
        }

        $retentionDays = api_request_log_retention_days();
        $cleanupStmt = db()->prepare("
            DELETE FROM api_request_logs
            WHERE created_at < (NOW() - INTERVAL :retention_days DAY)
        ");
        $cleanupStmt->bindValue(':retention_days', $retentionDays, PDO::PARAM_INT);
        $cleanupStmt->execute();
    } catch (Throwable $e) {
        error_log('api request log cleanup error: ' . $e->getMessage());
    }
}

function api_log_response(int $statusCode, ?string $errorCode = null): void
{
    $requestPath = (string) ($GLOBALS['current_route_path'] ?? '');
    if (!str_starts_with($requestPath, 'api/v1/')) {
        return;
    }

    if (!api_request_log_table_ready()) {
        return;
    }

    static $logged = false;
    if ($logged) {
        return;
    }
    $logged = true;

    $auth = (array) ($GLOBALS['api_last_auth'] ?? []);
    $tokenId = (int) ($auth['token_id'] ?? 0);
    $userId = (int) ($auth['user_id'] ?? 0);
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $ipAddress = function_exists('kirpi_request_ip') ? kirpi_request_ip() : '';
    $startedAt = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
    $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

    try {
        api_cleanup_request_logs_if_needed();

        $stmt = db()->prepare("
            INSERT INTO api_request_logs (
                route_path,
                request_method,
                status_code,
                error_code,
                user_id,
                token_id,
                ip_address,
                duration_ms
            ) VALUES (
                :route_path,
                :request_method,
                :status_code,
                :error_code,
                :user_id,
                :token_id,
                :ip_address,
                :duration_ms
            )
        ");

        $stmt->execute([
            ':route_path' => mb_substr($requestPath, 0, 190),
            ':request_method' => mb_substr($method, 0, 10),
            ':status_code' => $statusCode,
            ':error_code' => $errorCode !== null ? mb_substr((string) $errorCode, 0, 80) : null,
            ':user_id' => $userId > 0 ? $userId : null,
            ':token_id' => $tokenId > 0 ? $tokenId : null,
            ':ip_address' => mb_substr((string) $ipAddress, 0, 45),
            ':duration_ms' => max(0, $durationMs),
        ]);
    } catch (Throwable $e) {
        error_log('api request log insert error: ' . $e->getMessage());
    }
}

function api_token_hash(string $plainToken): string
{
    return hash('sha256', $plainToken);
}

function api_normalize_scopes(array|string|null $input): array
{
    $rawValues = [];

    if (is_array($input)) {
        $rawValues = $input;
    } elseif (is_string($input)) {
        $split = preg_split('/[\s,]+/', $input) ?: [];
        $rawValues = $split;
    }

    $scopes = [];
    foreach ($rawValues as $value) {
        $scope = strtolower(trim((string) $value));
        if ($scope === '') {
            continue;
        }

        // Allow wildcard and scope tokens like users:read
        if ($scope !== '*' && preg_match('/^[a-z0-9:_-]{2,80}$/', $scope) !== 1) {
            continue;
        }

        $scopes[] = $scope;
    }

    $scopes = array_values(array_unique($scopes));
    if (empty($scopes)) {
        return ['*'];
    }

    if (in_array('*', $scopes, true)) {
        return ['*'];
    }

    return $scopes;
}

function api_save_token_scopes(int $tokenId, array $scopes): void
{
    if ($tokenId <= 0 || !api_token_scope_table_ready()) {
        return;
    }

    $normalized = api_normalize_scopes($scopes);

    $insertStmt = db()->prepare("\n        INSERT INTO api_token_scopes (\n            token_id,\n            scope_key\n        ) VALUES (\n            :token_id,\n            :scope_key\n        )\n    ");

    foreach ($normalized as $scope) {
        try {
            $insertStmt->execute([
                ':token_id' => $tokenId,
                ':scope_key' => $scope,
            ]);
        } catch (Throwable $e) {
            // Ignore duplicate rows to keep idempotent behaviour.
            if (stripos($e->getMessage(), 'duplicate') === false) {
                error_log('api save token scope error: ' . $e->getMessage());
            }
        }
    }
}

function api_list_scopes_for_token(int $tokenId): array
{
    if ($tokenId <= 0 || !api_token_scope_table_ready()) {
        return [];
    }

    try {
        $stmt = db()->prepare("\n            SELECT scope_key\n            FROM api_token_scopes\n            WHERE token_id = :token_id\n            ORDER BY scope_key ASC\n        ");
        $stmt->execute([
            ':token_id' => $tokenId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return api_normalize_scopes($rows);
    } catch (Throwable $e) {
        error_log('api list token scopes error: ' . $e->getMessage());
        return [];
    }
}

function api_issue_token_for_user(int $userId, ?string $tokenName = null, ?int $ttlSeconds = null, array|string|null $scopes = null): ?array
{
    if ($userId <= 0 || !api_token_table_ready()) {
        return null;
    }

    $plain = bin2hex(random_bytes(32));
    $hash = api_token_hash($plain);
    $name = trim((string) ($tokenName ?? 'default')) ?: 'default';
    $ttl = $ttlSeconds ?? (int) env('API_TOKEN_TTL_SECONDS', '2592000');
    $isUnlimited = $ttlSeconds !== null && $ttlSeconds < 0;
    $normalizedScopes = api_normalize_scopes($scopes ?? ['*']);

    if ($isUnlimited) {
        $expiresAt = '2099-12-31 23:59:59';
    } else {
        if ($ttl <= 0) {
            $ttl = 2592000;
        }
        $expiresAt = (new DateTimeImmutable('now'))->add(new DateInterval('PT' . $ttl . 'S'))->format('Y-m-d H:i:s');
    }

    $stmt = db()->prepare("\n        INSERT INTO api_tokens (\n            user_id,\n            token_name,\n            token_hash,\n            expires_at,\n            last_used_at\n        ) VALUES (\n            :user_id,\n            :token_name,\n            :token_hash,\n            :expires_at,\n            NULL\n        )\n    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':token_name' => mb_substr($name, 0, 120),
        ':token_hash' => $hash,
        ':expires_at' => $expiresAt,
    ]);

    $tokenId = (int) db()->lastInsertId();
    api_save_token_scopes($tokenId, $normalizedScopes);

    return [
        'token_id' => $tokenId,
        'token' => $plain,
        'expires_at' => $expiresAt,
        'is_unlimited' => $isUnlimited,
        'scopes' => $normalizedScopes,
    ];
}

function api_list_tokens_for_user(int $userId, int $limit = 50): array
{
    if ($userId <= 0 || !api_token_table_ready()) {
        return [];
    }

    $limit = max(1, min(200, $limit));

    try {
        $stmt = db()->prepare("\n            SELECT\n                id,\n                token_name,\n                last_used_at,\n                expires_at,\n                revoked_at,\n                created_at,\n                updated_at\n            FROM api_tokens\n            WHERE user_id = :user_id\n            ORDER BY id DESC\n            LIMIT :limit\n        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $tokenId = (int) ($row['id'] ?? 0);
            $scopes = api_list_scopes_for_token($tokenId);
            // Backward compatible: old tokens without scope rows are full access.
            $row['scopes'] = !empty($scopes) ? $scopes : ['*'];
        }
        unset($row);

        return $rows;
    } catch (Throwable $e) {
        error_log('api list tokens error: ' . $e->getMessage());
        return [];
    }
}

function api_revoke_token_for_user(int $tokenId, int $userId): bool
{
    if ($tokenId <= 0 || $userId <= 0 || !api_token_table_ready()) {
        return false;
    }

    try {
        $stmt = db()->prepare("\n            UPDATE api_tokens\n            SET revoked_at = NOW(),\n                updated_at = NOW()\n            WHERE id = :id\n              AND user_id = :user_id\n              AND revoked_at IS NULL\n        ");
        $stmt->execute([
            ':id' => $tokenId,
            ':user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        error_log('api revoke token error: ' . $e->getMessage());
        return false;
    }
}

function api_authenticate_by_token(string $plainToken): ?array
{
    if ($plainToken === '' || !api_token_table_ready()) {
        return null;
    }

    $hash = api_token_hash($plainToken);

    $stmt = db()->prepare("\n        SELECT\n            t.id AS token_id,\n            t.user_id,\n            t.expires_at,\n            t.revoked_at,\n            u.id,\n            u.name,\n            u.email,\n            u.avatar,\n            u.is_active,\n            u.role_id,\n            r.name AS role_name,\n            r.is_active AS role_is_active\n        FROM api_tokens t\n        INNER JOIN users u ON u.id = t.user_id\n        LEFT JOIN roles r ON r.id = u.role_id\n        WHERE t.token_hash = :token_hash\n        LIMIT 1\n    ");
    $stmt->execute([
        ':token_hash' => $hash,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    if ((string) ($row['revoked_at'] ?? '') !== '') {
        return null;
    }

    $expiresAt = (string) ($row['expires_at'] ?? '');
    if ($expiresAt !== '' && strtotime($expiresAt) !== false && strtotime($expiresAt) < time()) {
        return null;
    }

    if ((int) ($row['is_active'] ?? 0) !== 1) {
        return null;
    }

    if (($row['role_id'] ?? null) && isset($row['role_is_active']) && (int) $row['role_is_active'] !== 1) {
        return null;
    }

    $tokenId = (int) ($row['token_id'] ?? 0);
    $tokenScopes = api_list_scopes_for_token($tokenId);
    if (empty($tokenScopes)) {
        $tokenScopes = ['*'];
    }

    $tokenUpdateStmt = db()->prepare("\n        UPDATE api_tokens\n        SET last_used_at = NOW(),\n            updated_at = NOW()\n        WHERE id = :id\n    ");
    $tokenUpdateStmt->execute([
        ':id' => $tokenId,
    ]);

    $user = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'avatar' => $row['avatar'] ?? null,
        'role_id' => isset($row['role_id']) ? (int) $row['role_id'] : null,
        'role_name' => $row['role_name'] ?? null,
        'permissions' => load_user_permissions(
            isset($row['role_id']) ? (int) $row['role_id'] : null,
            $row['role_name'] ?? null
        ),
    ];

    return [
        'user' => $user,
        'token_id' => $tokenId,
        'token_scopes' => $tokenScopes,
    ];
}

function api_user_has_permission(array $user, ?string $permission): bool
{
    if ($permission === null || trim($permission) === '') {
        return true;
    }

    if (($user['role_name'] ?? null) === 'Super Admin') {
        return true;
    }

    return in_array($permission, (array) ($user['permissions'] ?? []), true);
}

function api_token_has_scope(array $auth, ?string $requiredScope): bool
{
    if ($requiredScope === null || trim($requiredScope) === '') {
        return true;
    }

    $requiredScope = strtolower(trim($requiredScope));
    $tokenScopes = api_normalize_scopes((array) ($auth['token_scopes'] ?? []));

    if (in_array('*', $tokenScopes, true)) {
        return true;
    }

    return in_array($requiredScope, $tokenScopes, true);
}

function api_require_token(?string $requiredPermission = null, ?string $requiredScope = null): array
{
    $token = api_extract_bearer_token();
    if ($token === null) {
        api_error(401, 'Bearer token gereklidir.', 'missing_bearer_token');
    }

    $auth = api_authenticate_by_token($token);
    if (!$auth) {
        api_error(401, 'Geçersiz veya süresi dolmuş token.', 'invalid_or_expired_token');
    }

    $user = (array) ($auth['user'] ?? []);
    if (!api_user_has_permission($user, $requiredPermission)) {
        api_error(403, 'Bu endpoint icin yetkiniz yok.', 'permission_denied');
    }

    if (!api_token_has_scope((array) $auth, $requiredScope)) {
        api_error(403, 'Bu token scope nedeniyle bu endpointe erisemez.', 'scope_denied');
    }

    $GLOBALS['api_last_auth'] = [
        'token_id' => (int) ($auth['token_id'] ?? 0),
        'user_id' => (int) ($user['id'] ?? 0),
        'scopes' => (array) ($auth['token_scopes'] ?? []),
    ];

    return $user;
}
