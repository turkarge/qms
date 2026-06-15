<?php

define('BASE_PATH', __DIR__);
define('KIRPI_CORE_ENTRY', true);

require_once BASE_PATH . '/core/config.php';
require_once BASE_PATH . '/core/database.php';
require_once BASE_PATH . '/core/functions.php';
require_once BASE_PATH . '/core/setup.php';

if (env_bool('SECURITY_HEADERS_ENABLED', true)) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://static.cloudflareinsights.com https://cdn.jsdelivr.net; connect-src 'self' https: https://cloudflareinsights.com;");
}

kirpi_try_auto_setup_if_empty();
kirpi_try_auto_setup_if_missing();

require_once BASE_PATH . '/core/routes.php';

$request_path = trim($_GET['url'] ?? '', '/');

if ($request_path === '') {
    $requestUriPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $requestUriPath = trim($requestUriPath, '/');

    $scriptDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

    if ($scriptDir !== '') {
        if ($requestUriPath === $scriptDir) {
            $requestUriPath = '';
        } elseif (str_starts_with($requestUriPath, $scriptDir . '/')) {
            $requestUriPath = substr($requestUriPath, strlen($scriptDir) + 1);
        }
    }

    if ($requestUriPath === 'index.php') {
        $requestUriPath = '';
    } elseif (str_starts_with($requestUriPath, 'index.php/')) {
        $requestUriPath = substr($requestUriPath, strlen('index.php/'));
    }

    $request_path = trim($requestUriPath, '/');
}

$request_path = $request_path !== '' ? $request_path : APP_DEFAULT_ROUTE;

$segments = explode('/', $request_path);
$module = $segments[0] ?? 'dashboard';

$route_info = $routes[$request_path] ?? null;

if (!$route_info) {
    foreach ($routes as $routeKey => $routeDefinition) {
        if (strpos((string) $routeKey, '{') === false) {
            continue;
        }

        $routeKeyString = (string) $routeKey;
        $pattern = '';
        $offset = 0;
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $routeKeyString, $placeholderMatches, PREG_OFFSET_CAPTURE) === false) {
            continue;
        }

        foreach (($placeholderMatches[0] ?? []) as $placeholderMatch) {
            $placeholder = (string) ($placeholderMatch[0] ?? '');
            $position = (int) ($placeholderMatch[1] ?? 0);
            $pattern .= preg_quote(substr($routeKeyString, $offset, $position - $offset), '#');
            $pattern .= '([^/]+)';
            $offset = $position + strlen($placeholder);
        }
        $pattern .= preg_quote(substr($routeKeyString, $offset), '#');

        if (preg_match('#^' . $pattern . '$#', $request_path, $matches) !== 1) {
            continue;
        }

        $paramNames = [];
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', (string) $routeKey, $nameMatches) === 1 || !empty($nameMatches[1])) {
            $paramNames = $nameMatches[1];
        }

        array_shift($matches);
        foreach ($paramNames as $index => $paramName) {
            $_GET[$paramName] = isset($matches[$index]) ? urldecode((string) $matches[$index]) : null;
        }

        $route_info = $routeDefinition;
        break;
    }
}

global $current_module;
$current_module = $module;

if (!$route_info) {
    display_error_page(
        '404 - Sayfa Bulunamadi',
        'Aradiginiz sayfa bulunamadi.',
        404,
        true
    );
}

$route_method = strtoupper($route_info['method'] ?? 'GET');
$current_method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($route_method !== 'ANY' && $route_method !== $current_method) {
    display_error_page(
        '405 - Yontem Desteklenmiyor',
        'Bu istek yontemi desteklenmiyor.',
        405,
        false
    );
}

$render_layout = (bool)($route_info['layout'] ?? false);
$required_permission = $route_info['permission'] ?? null;
$auth_required = $route_info['auth'] ?? true;

if (str_starts_with($request_path, 'api/v1/') && !api_is_enabled()) {
    api_error(503, 'API gecici olarak devre disi birakildi.');
}

if ($auth_required && !is_user_logged_in()) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/' . $request_path);
    set_flash_message('info', 'Devam etmek icin lutfen giris yapin.');
    redirect(base_url('auth/login'));
}

if ($auth_required && is_user_logged_in() && !validate_active_session_user()) {
    kirpi_delete_current_user_session();
    unset($_SESSION['user']);
    unset($_SESSION['_auth_lock']);
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/' . $request_path);
    set_flash_message('warning', 'Hesabinizin veya rolunuzun durumu degismis. Lutfen tekrar giris yapin.');
    redirect(base_url('auth/login'));
}

if (is_user_logged_in() && kirpi_session_lock_state() && !kirpi_route_allows_locked_session($request_path)) {
    if ($request_path !== 'auth/lock') {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/' . $request_path);
    }

    redirect(base_url('auth/lock'));
}

$throttleResult = kirpi_throttle_guard_request(
    $request_path,
    $current_method,
    is_user_logged_in() ? (int) (current_user()['id'] ?? 0) : null
);

if (!($throttleResult['allowed'] ?? true)) {
    $retryAfter = max(1, (int) ($throttleResult['retry_after'] ?? 30));
    $message = (string) ($throttleResult['message'] ?? 'Istek siniri asildi. Lutfen daha sonra tekrar deneyin.');

    header('Retry-After: ' . $retryAfter);

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax || !$render_layout) {
        json_response([
            'status' => 'error',
            'message' => $message,
            'retry_after' => $retryAfter,
        ], 429);
    }

    display_error_page(
        '429 - Cok Fazla Istek',
        $message,
        429,
        $render_layout
    );
}

if ($required_permission && !check_permission($required_permission)) {
    display_error_page(
        '403 - Yetkisiz Erisim',
        'Bu sayfayi goruntuleme yetkiniz bulunmamaktadir.',
        403,
        $render_layout
    );
}

$target_file_relative_path = $route_info['file'] ?? '';
$target_file_full_path = BASE_PATH . '/' . ltrim($target_file_relative_path, '/');

if (!is_file($target_file_full_path)) {
    display_error_page(
        '500 - Ic Sunucu Hatasi',
        'Tanimli rota icin hedef dosya bulunamadi.',
        500,
        $render_layout
    );
}

global $current_route;
$current_route = $route_info;
$GLOBALS['current_route'] = $route_info;
$GLOBALS['current_route_path'] = $request_path;

if ($render_layout) {
    require_once BASE_PATH . '/layouts/header.php';
    require $target_file_full_path;
    require_once BASE_PATH . '/layouts/footer.php';
} else {
    require $target_file_full_path;
}
