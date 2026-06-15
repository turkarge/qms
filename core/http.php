<?php

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit();
}

function json_response(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function require_action(string $method = 'POST', bool $requireLogin = true): void
{
    if (!defined('KIRPI_CORE_ENTRY')) {
        exit();
    }

    $currentMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $expectedMethod = strtoupper($method);

    if ($currentMethod !== $expectedMethod) {
        http_response_code(405);
        exit();
    }

    if ($requireLogin && !is_user_logged_in()) {
        http_response_code(403);
        exit();
    }
}

function display_error_page(string $title, string $message, int $http_code, bool $render_layout = true): never
{
    http_response_code($http_code);

    $display_message = $message;

    if (!APP_DEBUG) {
        $display_message = match ($http_code) {
            403 => 'Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.',
            404 => 'Aradığınız sayfa bulunamadı.',
            405 => 'İstek yöntemi desteklenmiyor.',
            default => 'Beklenmedik bir hata oluştu. Lütfen sistem yöneticisi ile iletişime geçin.',
        };
    }

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (!$render_layout || $isAjax) {
        json_response([
            'status' => 'error',
            'message' => $display_message,
        ], $http_code);
    }

    require_once BASE_PATH . '/layouts/header.php';
    ?>
    <div class="page-body">
        <div class="container-xl">
            <div class="alert alert-danger" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-alert-circle"></i></div>
                    <div>
                        <h4 class="alert-title"><?php echo e($title); ?></h4>
                        <div class="text-secondary"><?php echo $display_message; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once BASE_PATH . '/layouts/footer.php';
    exit();
}