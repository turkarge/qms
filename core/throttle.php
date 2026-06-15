<?php

function kirpi_throttle_enabled(): bool
{
    return env_bool('THROTTLE_ENABLED', true);
}

function kirpi_throttle_table_ready(): bool
{
    return db_table_exists('request_throttles');
}

function kirpi_client_ip(): string
{
    $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if (!APP_TRUST_PROXY) {
        return $remoteAddr !== '' ? $remoteAddr : '0.0.0.0';
    }

    $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($forwardedFor !== '') {
        $parts = array_map('trim', explode(',', $forwardedFor));
        foreach ($parts as $part) {
            if ($part !== '' && filter_var($part, FILTER_VALIDATE_IP)) {
                return $part;
            }
        }
    }

    $realIp = trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
    if ($realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP)) {
        return $realIp;
    }

    return $remoteAddr !== '' ? $remoteAddr : '0.0.0.0';
}

function kirpi_throttle_compact_key(string $raw): string
{
    return hash('sha256', $raw);
}

function kirpi_throttle_consume(string $bucket, int $limit, int $windowSeconds, int $blockSeconds = 0): array
{
    if (!kirpi_throttle_enabled() || !kirpi_throttle_table_ready()) {
        return [
            'allowed' => true,
            'remaining' => $limit,
            'retry_after' => 0,
        ];
    }

    $limit = max(1, $limit);
    $windowSeconds = max(1, $windowSeconds);
    $blockSeconds = max(0, $blockSeconds);

    $key = kirpi_throttle_compact_key($bucket);
    $now = new DateTimeImmutable('now');
    $nowStr = $now->format('Y-m-d H:i:s');

    try {
        if (random_int(1, 100) === 1) {
            $cleanupBefore = $now->sub(new DateInterval('P2D'))->format('Y-m-d H:i:s');
            $cleanupStmt = db()->prepare("\n                DELETE FROM request_throttles\n                WHERE updated_at < :cleanup_before\n            ");
            $cleanupStmt->execute([
                ':cleanup_before' => $cleanupBefore,
            ]);
        }
    } catch (Throwable $e) {
        // cleanup best effort
    }

    try {
        db()->beginTransaction();

        $selectStmt = db()->prepare("\n            SELECT id, hit_count, window_started_at, blocked_until\n            FROM request_throttles\n            WHERE throttle_key = :throttle_key\n            LIMIT 1\n            FOR UPDATE\n        ");
        $selectStmt->execute([
            ':throttle_key' => $key,
        ]);

        $row = $selectStmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $insertStmt = db()->prepare("\n                INSERT INTO request_throttles (\n                    throttle_key,\n                    hit_count,\n                    window_started_at,\n                    blocked_until,\n                    updated_at\n                ) VALUES (\n                    :throttle_key,\n                    1,\n                    :window_started_at,\n                    NULL,\n                    :updated_at\n                )\n            ");
            $insertStmt->execute([
                ':throttle_key' => $key,
                ':window_started_at' => $nowStr,
                ':updated_at' => $nowStr,
            ]);

            db()->commit();

            return [
                'allowed' => true,
                'remaining' => max(0, $limit - 1),
                'retry_after' => 0,
            ];
        }

        $blockedUntilStr = (string) ($row['blocked_until'] ?? '');
        if ($blockedUntilStr !== '') {
            $blockedUntil = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $blockedUntilStr) ?: null;
            if ($blockedUntil && $blockedUntil > $now) {
                $retryAfter = max(1, $blockedUntil->getTimestamp() - $now->getTimestamp());
                db()->commit();
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'retry_after' => $retryAfter,
                ];
            }
        }

        $windowStartedStr = (string) ($row['window_started_at'] ?? '');
        $windowStarted = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $windowStartedStr) ?: $now;
        $elapsed = max(0, $now->getTimestamp() - $windowStarted->getTimestamp());

        if ($elapsed >= $windowSeconds) {
            $resetStmt = db()->prepare("\n                UPDATE request_throttles\n                SET hit_count = 1,\n                    window_started_at = :window_started_at,\n                    blocked_until = NULL,\n                    updated_at = :updated_at\n                WHERE id = :id\n            ");
            $resetStmt->execute([
                ':window_started_at' => $nowStr,
                ':updated_at' => $nowStr,
                ':id' => (int) $row['id'],
            ]);

            db()->commit();
            return [
                'allowed' => true,
                'remaining' => max(0, $limit - 1),
                'retry_after' => 0,
            ];
        }

        $nextHitCount = ((int) ($row['hit_count'] ?? 0)) + 1;
        if ($nextHitCount > $limit) {
            $retryAfter = max(1, $windowSeconds - $elapsed);
            $newBlockedUntil = null;

            if ($blockSeconds > 0) {
                $retryAfter = max($retryAfter, $blockSeconds);
                $newBlockedUntil = $now->add(new DateInterval('PT' . $blockSeconds . 'S'))->format('Y-m-d H:i:s');
            }

            $blockStmt = db()->prepare("\n                UPDATE request_throttles\n                SET hit_count = :hit_count,\n                    blocked_until = :blocked_until,\n                    updated_at = :updated_at\n                WHERE id = :id\n            ");
            $blockStmt->execute([
                ':hit_count' => $nextHitCount,
                ':blocked_until' => $newBlockedUntil,
                ':updated_at' => $nowStr,
                ':id' => (int) $row['id'],
            ]);

            db()->commit();
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $retryAfter,
            ];
        }

        $updateStmt = db()->prepare("\n            UPDATE request_throttles\n            SET hit_count = :hit_count,\n                updated_at = :updated_at\n            WHERE id = :id\n        ");
        $updateStmt->execute([
            ':hit_count' => $nextHitCount,
            ':updated_at' => $nowStr,
            ':id' => (int) $row['id'],
        ]);

        db()->commit();
        return [
            'allowed' => true,
            'remaining' => max(0, $limit - $nextHitCount),
            'retry_after' => 0,
        ];
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        error_log('Throttle consume error: ' . $e->getMessage());
        return [
            'allowed' => true,
            'remaining' => $limit,
            'retry_after' => 0,
        ];
    }
}

function kirpi_throttle_guard_request(string $requestPath, string $method, ?int $userId = null): array
{
    if (!kirpi_throttle_enabled() || !kirpi_throttle_table_ready()) {
        return ['allowed' => true];
    }

    $method = strtoupper(trim($method));
    $ip = kirpi_client_ip();
    $userScope = $userId && $userId > 0 ? ('user:' . $userId) : ('ip:' . $ip);

    if (str_starts_with($requestPath, 'api/v1/')) {
        $apiLimit = (int) env('THROTTLE_API_LIMIT', '120');
        $apiWindow = (int) env('THROTTLE_API_WINDOW', '60');
        $apiBlock = (int) env('THROTTLE_API_BLOCK', '120');

        $apiCheck = kirpi_throttle_consume('api:' . $requestPath . ':' . $method . ':ip:' . $ip, $apiLimit, $apiWindow, $apiBlock);
        if (!($apiCheck['allowed'] ?? true)) {
            return [
                'allowed' => false,
                'message' => 'API istek limiti asildi. Lutfen daha sonra tekrar deneyin.',
                'retry_after' => (int) ($apiCheck['retry_after'] ?? 30),
            ];
        }

        if ($requestPath === 'api/v1/auth/token' && $method === 'POST') {
            $authLimit = (int) env('THROTTLE_API_AUTH_LIMIT', '10');
            $authWindow = (int) env('THROTTLE_API_AUTH_WINDOW', '300');
            $authBlock = (int) env('THROTTLE_API_AUTH_BLOCK', '600');

            $authCheck = kirpi_throttle_consume('api_auth:ip:' . $ip, $authLimit, $authWindow, $authBlock);
            if (!($authCheck['allowed'] ?? true)) {
                return [
                    'allowed' => false,
                    'message' => 'API token deneme limiti asildi. Lutfen daha sonra tekrar deneyin.',
                    'retry_after' => (int) ($authCheck['retry_after'] ?? 60),
                ];
            }
        }
    }

    if ($requestPath === 'auth/actions/login' && $method === 'POST') {
        $limit = (int) env('THROTTLE_LOGIN_LIMIT', '5');
        $window = (int) env('THROTTLE_LOGIN_WINDOW', '600');
        $block = (int) env('THROTTLE_LOGIN_BLOCK', '900');

        $ipCheck = kirpi_throttle_consume('login:ip:' . $ip, $limit, $window, $block);
        if (!($ipCheck['allowed'] ?? true)) {
            return [
                'allowed' => false,
                'message' => 'Cok fazla giris denemesi yapildi. Lutfen biraz sonra tekrar deneyin.',
                'retry_after' => (int) ($ipCheck['retry_after'] ?? 60),
            ];
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if ($email !== '') {
            $emailCheck = kirpi_throttle_consume('login:ip_email:' . $ip . ':' . hash('sha256', $email), $limit, $window, $block);
            if (!($emailCheck['allowed'] ?? true)) {
                return [
                    'allowed' => false,
                    'message' => 'Cok fazla giris denemesi yapildi. Lutfen biraz sonra tekrar deneyin.',
                    'retry_after' => (int) ($emailCheck['retry_after'] ?? 60),
                ];
            }
        }
    }

    if ($method === 'POST') {
        $criticalRoutes = [
            'backup/actions/create',
            'backup/actions/restore',
            'backup/actions/verify',
            'backup/actions/delete',
            'mail/actions/send-test',
            'mail/actions/send_test',
            'mail/actions/template-create',
            'mail/actions/template-update',
            'mail/actions/template-delete',
            'queue/actions/enqueue-test-mail',
            'queue/actions/work-once',
            'queue/actions/retry-failed',
            'settings/actions/install-missing',
        ];

        if (in_array($requestPath, $criticalRoutes, true)) {
            $limit = (int) env('THROTTLE_CRITICAL_LIMIT', '10');
            $window = (int) env('THROTTLE_CRITICAL_WINDOW', '60');
            $block = (int) env('THROTTLE_CRITICAL_BLOCK', '120');

            $check = kirpi_throttle_consume('critical:' . $requestPath . ':' . $userScope, $limit, $window, $block);
            if (!($check['allowed'] ?? true)) {
                return [
                    'allowed' => false,
                    'message' => 'Bu islem icin istek siniri asildi. Lutfen biraz sonra tekrar deneyin.',
                    'retry_after' => (int) ($check['retry_after'] ?? 30),
                ];
            }
        }

        $globalLimit = (int) env('THROTTLE_GLOBAL_POST_LIMIT', '180');
        $globalWindow = (int) env('THROTTLE_GLOBAL_POST_WINDOW', '60');
        $globalBlock = (int) env('THROTTLE_GLOBAL_POST_BLOCK', '60');

        $globalCheck = kirpi_throttle_consume('global:post:' . $userScope, $globalLimit, $globalWindow, $globalBlock);
        if (!($globalCheck['allowed'] ?? true)) {
            return [
                'allowed' => false,
                'message' => 'Cok fazla istek alindi. Lutfen kisa bir sure sonra tekrar deneyin.',
                'retry_after' => (int) ($globalCheck['retry_after'] ?? 30),
            ];
        }
    }

    return ['allowed' => true];
}
