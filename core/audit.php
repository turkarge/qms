<?php

function kirpi_request_ip(): string
{
    $candidates = [
        (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
        (string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''),
        (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim($candidate);
        if ($candidate === '') {
            continue;
        }

        if (str_contains($candidate, ',')) {
            $parts = array_map('trim', explode(',', $candidate));
            $candidate = (string) ($parts[0] ?? '');
        }

        if ($candidate !== '') {
            return mb_substr($candidate, 0, 45);
        }
    }

    return 'unknown';
}

function kirpi_audit_log(
    string $action,
    string $module,
    array $details = [],
    ?string $entityType = null,
    ?int $entityId = null,
    string $status = 'success'
): void {
    if (!db_table_exists('audit_logs')) {
        return;
    }

    $user = current_user();
    $userId = (int) ($user['id'] ?? 0);
    $routePath = (string) ($GLOBALS['current_route_path'] ?? '');
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $ipAddress = kirpi_request_ip();
    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    $detailsJson = null;
    if (!empty($details)) {
        $encoded = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $detailsJson = $encoded === false ? null : $encoded;
    }

    try {
        $stmt = db()->prepare("
            INSERT INTO audit_logs (
                user_id,
                module_key,
                action_key,
                status,
                entity_type,
                entity_id,
                route_path,
                request_method,
                ip_address,
                user_agent,
                details_json
            ) VALUES (
                :user_id,
                :module_key,
                :action_key,
                :status,
                :entity_type,
                :entity_id,
                :route_path,
                :request_method,
                :ip_address,
                :user_agent,
                :details_json
            )
        ");

        $stmt->execute([
            ':user_id' => $userId > 0 ? $userId : null,
            ':module_key' => mb_substr(trim($module), 0, 80),
            ':action_key' => mb_substr(trim($action), 0, 120),
            ':status' => mb_substr(trim($status), 0, 30),
            ':entity_type' => $entityType !== null ? mb_substr(trim($entityType), 0, 80) : null,
            ':entity_id' => $entityId,
            ':route_path' => mb_substr($routePath, 0, 190),
            ':request_method' => mb_substr($method, 0, 10),
            ':ip_address' => $ipAddress,
            ':user_agent' => mb_substr($userAgent, 0, 255),
            ':details_json' => $detailsJson,
        ]);
    } catch (Throwable $e) {
        error_log('audit log insert error: ' . $e->getMessage());
    }
}
