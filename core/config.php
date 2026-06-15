<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/core/helpers.php';

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

if (class_exists(\Dotenv\Dotenv::class)) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->safeLoad();
}

date_default_timezone_set(env('APP_TIMEZONE', 'Europe/Istanbul'));

$appEnv = env('APP_ENV', 'production');
$appDebug = env_bool('APP_DEBUG', false);
$appTrustProxy = env_bool('APP_TRUST_PROXY', true);
$appVer = (string) env('APP_VER', '1.0.15');

if ($appEnv === 'development' || $appDebug === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

ini_set('error_log', BASE_PATH . '/logs/php-errors.log');

if (session_status() === PHP_SESSION_NONE) {
    $sessionCookieDomain = env('SESSION_COOKIE_DOMAIN', '');
    $isHttpsRequest = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $sessionIdleTimeout = max(300, (int) env('SESSION_IDLE_TIMEOUT_SECONDS', 7200));
    $sessionRotateSeconds = max(60, (int) env('SESSION_ID_ROTATE_SECONDS', 900));
    $sessionVersion = preg_replace('/[^a-zA-Z0-9]/', '', $appVer);
    $appPrefix = preg_replace('/[^a-zA-Z0-9]/', '', (string) env('KIRPI_APP_PREFIX', 'kirpicore'));

    if ($sessionVersion === '' || $sessionVersion === null) {
        $sessionVersion = '100';
    }

    if ($appPrefix === '' || $appPrefix === null) {
        $appPrefix = 'kirpicore';
    }

    $defaultSessionCookieName = strtoupper($appPrefix) . 'SESSID_' . $sessionVersion;
    $sessionCookieName = preg_replace(
        '/[^a-zA-Z0-9]/',
        '',
        (string) env('SESSION_COOKIE_NAME', $defaultSessionCookieName)
    );

    if ($sessionCookieName === '' || $sessionCookieName === null) {
        $sessionCookieName = $defaultSessionCookieName;
    }

    if (!$isHttpsRequest && $appTrustProxy) {
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $forwardedSsl = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
        $isHttpsRequest = $forwardedProto === 'https' || $forwardedSsl === 'on';
    }

    if ($sessionCookieDomain !== '') {
        ini_set('session.cookie_domain', $sessionCookieDomain);
    }

    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.sid_length', '64');
    ini_set('session.sid_bits_per_character', '6');
    session_name($sessionCookieName);

    if ($isHttpsRequest) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();

    if (!isset($_SESSION['_meta'])) {
        $_SESSION['_meta'] = [];
    }

    $nowTs = time();
    $lastActivityTs = (int) ($_SESSION['_meta']['last_activity_at'] ?? 0);
    if ($lastActivityTs > 0 && ($nowTs - $lastActivityTs) > $sessionIdleTimeout) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['_meta'] = [];
    }

    $lastRotateTs = (int) ($_SESSION['_meta']['last_rotate_at'] ?? 0);
    if ($lastRotateTs <= 0 || ($nowTs - $lastRotateTs) >= $sessionRotateSeconds) {
        session_regenerate_id(true);
        $_SESSION['_meta']['last_rotate_at'] = $nowTs;
    }

    $_SESSION['_meta']['last_activity_at'] = $nowTs;
}

define('APP_NAME', env('APP_NAME', 'Kirpi Core'));
define('APP_VER', $appVer);
define('APP_ENV', $appEnv);
define('APP_DEBUG', $appDebug);
define('APP_TRUST_PROXY', $appTrustProxy);
define('KIRPI_APP_PREFIX', env('KIRPI_APP_PREFIX', 'kirpicore'));
define('SESSION_IDLE_TIMEOUT_SECONDS', max(300, (int) env('SESSION_IDLE_TIMEOUT_SECONDS', 7200)));
define('SESSION_ID_ROTATE_SECONDS', max(60, (int) env('SESSION_ID_ROTATE_SECONDS', 900)));

define('APP_DEFAULT_ROUTE', env('APP_DEFAULT_ROUTE', 'dashboard/view'));
define('BASE_URL', rtrim(env('BASE_URL', 'http://localhost'), '/'));
define('AUTH_LOGIN_COVER_IMAGE', env('AUTH_LOGIN_COVER_IMAGE', ''));
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'kirpicore'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

define('MAIL_HOST', env('MAIL_HOST', ''));
define('MAIL_PORT', env('MAIL_PORT', '587'));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', ''));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', APP_NAME));

define('DEBUG_MODE', APP_DEBUG);
