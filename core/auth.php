<?php

function kirpi_auth_lock_schema_ready(): bool
{
    return db_table_exists('users')
        && db_column_exists('users', 'lock_enabled')
        && db_column_exists('users', 'lock_pin_hash')
        && db_column_exists('users', 'session_version');
}

function kirpi_user_sessions_table_ready(): bool
{
    return db_table_exists('user_sessions');
}

function kirpi_session_lock_state(): bool
{
    return !empty($_SESSION['_auth_lock']['is_locked']);
}

function kirpi_lock_session(): void
{
    $_SESSION['_auth_lock'] = [
        'is_locked' => true,
        'locked_at' => date('Y-m-d H:i:s'),
    ];

    if (kirpi_user_sessions_table_ready() && is_user_logged_in()) {
        try {
            $stmt = db()->prepare("
                UPDATE user_sessions
                SET is_locked = 1,
                    locked_at = NOW(),
                    last_activity_at = NOW(),
                    updated_at = NOW()
                WHERE session_id = :session_id
                LIMIT 1
            ");
            $stmt->execute([
                ':session_id' => session_id(),
            ]);
        } catch (Throwable $e) {
            error_log('lock session update error: ' . $e->getMessage());
        }
    }
}

function kirpi_unlock_session(): void
{
    unset($_SESSION['_auth_lock']);

    if (kirpi_user_sessions_table_ready() && is_user_logged_in()) {
        try {
            $stmt = db()->prepare("
                UPDATE user_sessions
                SET is_locked = 0,
                    locked_at = NULL,
                    last_activity_at = NOW(),
                    updated_at = NOW()
                WHERE session_id = :session_id
                LIMIT 1
            ");
            $stmt->execute([
                ':session_id' => session_id(),
            ]);
        } catch (Throwable $e) {
            error_log('unlock session update error: ' . $e->getMessage());
        }
    }
}

function kirpi_register_user_session(int $userId): void
{
    if ($userId <= 0 || !kirpi_user_sessions_table_ready()) {
        return;
    }

    try {
        $stmt = db()->prepare("
            INSERT INTO user_sessions (
                session_id,
                user_id,
                is_locked,
                locked_at,
                last_activity_at,
                ip_address,
                user_agent
            ) VALUES (
                :session_id,
                :user_id,
                :is_locked,
                :locked_at,
                NOW(),
                :ip_address,
                :user_agent
            )
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                is_locked = VALUES(is_locked),
                locked_at = VALUES(locked_at),
                last_activity_at = NOW(),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                updated_at = NOW()
        ");
        $stmt->execute([
            ':session_id' => session_id(),
            ':user_id' => $userId,
            ':is_locked' => kirpi_session_lock_state() ? 1 : 0,
            ':locked_at' => kirpi_session_lock_state() ? date('Y-m-d H:i:s') : null,
            ':ip_address' => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ':user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    } catch (Throwable $e) {
        error_log('register user session error: ' . $e->getMessage());
    }
}

function kirpi_touch_user_session(): void
{
    if (!is_user_logged_in() || !kirpi_user_sessions_table_ready()) {
        return;
    }

    try {
        $stmt = db()->prepare("
            UPDATE user_sessions
            SET last_activity_at = NOW(),
                is_locked = :is_locked,
                updated_at = NOW()
            WHERE session_id = :session_id
            LIMIT 1
        ");
        $stmt->execute([
            ':is_locked' => kirpi_session_lock_state() ? 1 : 0,
            ':session_id' => session_id(),
        ]);

        if ($stmt->rowCount() === 0) {
            kirpi_register_user_session((int) (current_user()['id'] ?? 0));
        }
    } catch (Throwable $e) {
        error_log('touch user session error: ' . $e->getMessage());
    }
}

function kirpi_delete_current_user_session(): void
{
    if (!kirpi_user_sessions_table_ready()) {
        return;
    }

    try {
        $stmt = db()->prepare("
            DELETE FROM user_sessions
            WHERE session_id = :session_id
            LIMIT 1
        ");
        $stmt->execute([
            ':session_id' => session_id(),
        ]);
    } catch (Throwable $e) {
        error_log('delete current user session error: ' . $e->getMessage());
    }
}

function kirpi_route_allows_locked_session(string $path): bool
{
    $path = trim($path, '/');

    $allowed = [
        'auth/lock',
        'auth/actions/unlock',
        'auth/actions/logout',
    ];

    return in_array($path, $allowed, true);
}

function is_user_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function check_permission(?string $permissionKey): bool
{
    if ($permissionKey === null || $permissionKey === '') {
        return true;
    }

    if (!is_user_logged_in()) {
        return false;
    }

    $user = current_user();

    if (($user['role_name'] ?? null) === 'Super Admin') {
        return true;
    }

    return in_array($permissionKey, $user['permissions'] ?? [], true);
}

function require_login(): void
{
    if (!is_user_logged_in()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] ?? BASE_URL;
        set_flash_message('info', 'Devam etmek icin lutfen giris yapin.');
        redirect(base_url('auth/login'));
    }
}

function validate_active_session_user(): bool
{
    if (!is_user_logged_in()) {
        return false;
    }

    if (!db_table_exists('users')) {
        return true;
    }

    $userId = (int) ($_SESSION['user']['id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }

    try {
        $hasLockSchema = kirpi_auth_lock_schema_ready();
        $lockSelectSql = $hasLockSchema
            ? "u.lock_enabled, u.session_version,"
            : "0 AS lock_enabled, 0 AS session_version,";
        $hasDefaultCompanySchema = db_column_exists('users', 'default_company_id');
        $defaultCompanySelectSql = $hasDefaultCompanySchema
            ? "u.default_company_id,"
            : "NULL AS default_company_id,";

        $stmt = db()->prepare("
            SELECT
                u.id,
                u.name,
                u.email,
                u.avatar,
                u.is_active,
                u.role_id,
                {$defaultCompanySelectSql}
                {$lockSelectSql}
                r.name AS role_name,
                r.is_active AS role_is_active
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([
            ':id' => $userId,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return false;
        }

        if ((int) ($user['is_active'] ?? 0) !== 1) {
            return false;
        }

        if (($user['role_id'] ?? null) && isset($user['role_is_active']) && (int) $user['role_is_active'] !== 1) {
            return false;
        }

        $sessionVersionInDb = isset($user['session_version']) ? (int) $user['session_version'] : 0;
        $sessionVersionInSession = (int) ($_SESSION['user']['session_version'] ?? 0);
        if ($hasLockSchema && $sessionVersionInDb !== $sessionVersionInSession) {
            return false;
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'avatar' => $user['avatar'] ?? null,
            'role_id' => isset($user['role_id']) ? (int) $user['role_id'] : null,
            'role_name' => $user['role_name'] ?? null,
            'default_company_id' => isset($user['default_company_id']) ? (int) $user['default_company_id'] : null,
            'lock_enabled' => (int) ($user['lock_enabled'] ?? 0) === 1,
            'session_version' => $sessionVersionInDb,
            'permissions' => load_user_permissions(
                isset($user['role_id']) ? (int) $user['role_id'] : null,
                $user['role_name'] ?? null
            ),
        ];

        kirpi_touch_user_session();

        return true;
    } catch (Throwable $e) {
        error_log('Session user validation error: ' . $e->getMessage());
        return false;
    }
}
