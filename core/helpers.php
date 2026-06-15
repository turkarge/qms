<?php

function env(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    if (function_exists('apache_getenv')) {
        $apacheValue = apache_getenv($key, true);
        if ($apacheValue !== false) {
            return $apacheValue;
        }
    }

    return $default;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = env($key);

    if ($value === null) {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

function db(): PDO
{
    global $pdo;
    return $pdo;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function route_exists(string $path): bool
{
    global $routes;

    return isset($routes[$path]);
}

function db_table_exists(string $tableName, bool $refresh = false): bool
{
    static $cache = [];

    if ($refresh) {
        unset($cache[$tableName]);
    }

    if (isset($cache[$tableName])) {
        return $cache[$tableName];
    }

    try {
        $stmt = db()->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
        ");
        $stmt->execute([
            ':table_name' => $tableName,
        ]);

        $cache[$tableName] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$tableName] = false;
    }

    return $cache[$tableName];
}

function db_column_exists(string $tableName, string $columnName, bool $refresh = false): bool
{
    static $cache = [];
    $cacheKey = $tableName . '.' . $columnName;

    if ($refresh) {
        unset($cache[$cacheKey]);
    }

    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    try {
        $stmt = db()->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
              AND column_name = :column_name
        ");
        $stmt->execute([
            ':table_name' => $tableName,
            ':column_name' => $columnName,
        ]);

        $cache[$cacheKey] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

function get_roles_for_select(?int $selectedRoleId = null, bool $activeOnly = false): array
{
    if (!db_table_exists('roles')) {
        return [];
    }

    try {
        $hasIsActiveColumn = db_column_exists('roles', 'is_active');

        if ($hasIsActiveColumn && $activeOnly) {
            $sql = "
                SELECT id, name, is_active
                FROM roles
                WHERE is_active = 1
            ";

            $params = [];

            if ($selectedRoleId && $selectedRoleId > 0) {
                $sql .= " OR id = :selected_role_id";
                $params[':selected_role_id'] = $selectedRoleId;
            }

            $sql .= " ORDER BY is_active DESC, name ASC";

            $stmt = db()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($hasIsActiveColumn) {
            $stmt = db()->query('SELECT id, name, is_active FROM roles ORDER BY is_active DESC, name ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = db()->query('SELECT id, name, 1 AS is_active FROM roles ORDER BY name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Role select load error: ' . $e->getMessage());
        return [];
    }
}

function load_user_permissions(?int $roleId, ?string $roleName = null): array
{
    static $cache = [];

    if (($roleName ?? null) === 'Super Admin') {
        return ['*'];
    }

    if (!$roleId) {
        return [];
    }

    if (isset($cache[$roleId])) {
        return $cache[$roleId];
    }

    if (!db_table_exists('permissions') || !db_table_exists('role_permissions')) {
        $cache[$roleId] = [];
        return $cache[$roleId];
    }

    try {
        $stmt = db()->prepare("
            SELECT p.slug
            FROM role_permissions rp
            INNER JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id
            ORDER BY p.slug ASC
        ");
        $stmt->execute([
            ':role_id' => $roleId,
        ]);

        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $cache[$roleId] = array_values(array_unique(array_filter($permissions)));
    } catch (Throwable $e) {
        error_log('Permission load error: ' . $e->getMessage());
        $cache[$roleId] = [];
    }

    return $cache[$roleId];
}

function get_unread_notifications_count(?int $userId): int
{
    if (!$userId || !db_table_exists('notifications')) {
        return 0;
    }

    try {
        $stmt = db()->prepare("
            SELECT COUNT(id)
            FROM notifications
            WHERE user_id = :user_id
              AND read_at IS NULL
        ");
        $stmt->execute([
            ':user_id' => $userId,
        ]);

        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('Unread notifications count error: ' . $e->getMessage());
        return 0;
    }
}

function kirpi_create_notification(int $userId, string $title, string $message, string $channel = 'in_app', array $metadata = []): bool
{
    if ($userId <= 0 || !db_table_exists('notifications')) {
        return false;
    }

    $title = trim($title);
    $message = trim($message);
    $channel = trim($channel);

    if ($title === '' || $message === '') {
        return false;
    }

    if ($channel === '') {
        $channel = 'in_app';
    }

    try {
        $columns = ['user_id', 'title', 'message', 'channel'];
        $placeholders = [':user_id', ':title', ':message', ':channel'];
        $params = [
            ':user_id' => $userId,
            ':title' => mb_substr($title, 0, 150),
            ':message' => $message,
            ':channel' => mb_substr($channel, 0, 50),
        ];

        $optionalColumns = [
            'template_key' => isset($metadata['template_key']) ? mb_substr(trim((string) $metadata['template_key']), 0, 120) : null,
            'source_module' => isset($metadata['source_module']) ? mb_substr(trim((string) $metadata['source_module']), 0, 80) : null,
            'entity_type' => isset($metadata['entity_type']) ? mb_substr(trim((string) $metadata['entity_type']), 0, 80) : null,
            'entity_id' => isset($metadata['entity_id']) && (int) $metadata['entity_id'] > 0 ? (int) $metadata['entity_id'] : null,
        ];

        foreach ($optionalColumns as $column => $value) {
            if (db_column_exists('notifications', $column) && $value !== null && $value !== '') {
                $columns[] = $column;
                $placeholders[] = ':' . $column;
                $params[':' . $column] = $value;
            }
        }

        if (db_column_exists('notifications', 'data_json')) {
            $data = $metadata['data'] ?? null;
            if (is_array($data) && !empty($data)) {
                $encodedData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($encodedData !== false) {
                    $columns[] = 'data_json';
                    $placeholders[] = ':data_json';
                    $params[':data_json'] = $encodedData;
                }
            }
        }

        $sql = sprintf(
            'INSERT INTO notifications (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return true;
    } catch (Throwable $e) {
        error_log('create notification error: ' . $e->getMessage());
        return false;
    }
}

function kirpi_notification_settings(int $userId): array
{
    $settings = [
        'in_app_enabled' => true,
        'email_enabled' => true,
    ];

    if ($userId <= 0 || !db_table_exists('notification_settings')) {
        return $settings;
    }

    try {
        $stmt = db()->prepare("
            SELECT in_app_enabled, email_enabled
            FROM notification_settings
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return $settings;
        }

        return [
            'in_app_enabled' => (int) ($row['in_app_enabled'] ?? 1) === 1,
            'email_enabled' => (int) ($row['email_enabled'] ?? 1) === 1,
        ];
    } catch (Throwable $e) {
        error_log('notification settings read error: ' . $e->getMessage());
        return $settings;
    }
}

function kirpi_notification_user_email(int $userId): ?string
{
    if ($userId <= 0 || !db_table_exists('users')) {
        return null;
    }

    try {
        $stmt = db()->prepare("SELECT email FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $email = trim((string) $stmt->fetchColumn());

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    } catch (Throwable $e) {
        error_log('notification user email read error: ' . $e->getMessage());
        return null;
    }
}

function kirpi_render_notification_template(string $templateKey, array $variables, string $fallbackTitle, string $fallbackMessage): array
{
    if (function_exists('kirpi_template_sync_notification_defaults')) {
        kirpi_template_sync_notification_defaults();
    }

    $title = trim($fallbackTitle);
    $message = trim($fallbackMessage);

    if (function_exists('kirpi_template_find_content_template')) {
        $template = kirpi_template_find_content_template($templateKey, true);
        if ($template) {
            $subjectTemplate = trim((string) ($template['subject'] ?? ''));
            $bodyTemplate = trim((string) ($template['body'] ?? ''));

            if ($subjectTemplate !== '' && function_exists('kirpi_template_render_string')) {
                $title = trim(kirpi_template_render_string($subjectTemplate, $variables));
            }

            if ($bodyTemplate !== '' && function_exists('kirpi_template_render_string')) {
                $message = trim(kirpi_template_render_string($bodyTemplate, $variables));
            }
        }
    }

    return [
        'title' => $title,
        'message' => $message,
    ];
}

function kirpi_notify_user(int $userId, string $templateKey, array $variables = [], array $options = []): array
{
    if ($userId <= 0) {
        return [
            'success' => false,
            'in_app' => false,
            'email' => false,
            'message' => 'invalid_user',
        ];
    }

    $templateKey = trim($templateKey);
    $fallbackTitle = (string) ($options['title'] ?? ($variables['title'] ?? $templateKey));
    $fallbackMessage = (string) ($options['message'] ?? ($variables['message'] ?? $fallbackTitle));
    $settings = kirpi_notification_settings($userId);
    $rendered = kirpi_render_notification_template($templateKey, $variables, $fallbackTitle, $fallbackMessage);
    $channel = (string) ($options['channel'] ?? 'in_app');
    $sourceModule = (string) ($options['source_module'] ?? (explode('.', $templateKey, 2)[0] ?? ''));
    $entityType = (string) ($options['entity_type'] ?? '');
    $entityId = isset($options['entity_id']) ? (int) $options['entity_id'] : null;
    $metadata = [
        'template_key' => $templateKey,
        'source_module' => $sourceModule,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'data' => $options['data'] ?? $variables,
    ];

    $inAppCreated = false;
    if (($options['in_app'] ?? true) && $settings['in_app_enabled']) {
        $inAppCreated = kirpi_create_notification($userId, $rendered['title'], $rendered['message'], $channel, $metadata);
    }

    $emailSent = false;
    if (($options['email'] ?? false) && $settings['email_enabled'] && function_exists('kirpi_send_templated_mail')) {
        $recipient = trim((string) ($options['recipient_email'] ?? ''));
        if ($recipient === '') {
            $recipient = (string) kirpi_notification_user_email($userId);
        }

        if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $mailTemplateKey = trim((string) ($options['email_template_key'] ?? $templateKey));
            $mailResult = kirpi_send_templated_mail($recipient, $mailTemplateKey, $variables, $userId);
            $emailSent = (bool) ($mailResult['success'] ?? false);
        }
    }

    return [
        'success' => $inAppCreated || $emailSent,
        'in_app' => $inAppCreated,
        'email' => $emailSent,
        'title' => $rendered['title'],
        'message' => $rendered['message'],
    ];
}

function kirpi_notify_current_user(string $templateKey, array $variables = [], array $options = []): array
{
    $currentUser = current_user();
    $userId = (int) ($currentUser['id'] ?? 0);

    if ($userId <= 0) {
        return [
            'success' => false,
            'in_app' => false,
            'email' => false,
            'message' => 'invalid_user',
        ];
    }

    return kirpi_notify_user($userId, $templateKey, $variables, $options);
}

function get_recent_notifications(?int $userId, int $limit = 5): array
{
    if (!$userId || !db_table_exists('notifications')) {
        return [];
    }

    $limit = max(1, min(20, $limit));

    try {
        $metaSelect = db_column_exists('notifications', 'template_key')
            && db_column_exists('notifications', 'source_module')
            && db_column_exists('notifications', 'entity_type')
            && db_column_exists('notifications', 'entity_id')
            ? 'template_key, source_module, entity_type, entity_id,'
            : 'NULL AS template_key, NULL AS source_module, NULL AS entity_type, NULL AS entity_id,';

        $stmt = db()->prepare("
            SELECT
                id,
                title,
                message,
                channel,
                {$metaSelect}
                created_at,
                read_at
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Recent notifications fetch error: ' . $e->getMessage());
        return [];
    }
}

function app_name(): string
{
    if (function_exists('kirpi_setting_get')) {
        $name = trim((string) kirpi_setting_get('app.name', APP_NAME));
        if ($name !== '') {
            return $name;
        }
    }

    return APP_NAME;
}

function app_ver(): string
{
    return APP_VER;
}

function base_url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base : $base . '/' . $path;
}

function asset_url(string $path): string
{
    $normalizedPath = ltrim($path, '/');
    $assetFile = BASE_PATH . '/assets/' . $normalizedPath;
    $version = is_file($assetFile) ? (string) filemtime($assetFile) : APP_VER;

    return base_url('assets/' . $normalizedPath) . '?v=' . rawurlencode($version);
}

function kirpi_date_format(string $style = 'short'): string
{
    $style = strtolower(trim($style));
    $locale = strtolower((string) env('APP_LOCALE', 'tr'));

    $defaultShort = str_starts_with($locale, 'tr') ? 'd.m.Y' : 'Y-m-d';
    $defaultLong = str_starts_with($locale, 'tr') ? 'd.m.Y H:i' : 'Y-m-d H:i';

    if ($style === 'long' || $style === 'datetime') {
        return (string) env('APP_DATE_FORMAT_LONG', $defaultLong);
    }

    return (string) env('APP_DATE_FORMAT_SHORT', $defaultShort);
}

function kirpi_format_date(null|string|DateTimeInterface $value, string $style = 'short', string $empty = '-'): string
{
    if ($value === null || $value === '') {
        return $empty;
    }

    try {
        $date = $value instanceof DateTimeInterface ? $value : new DateTimeImmutable((string) $value);
        return $date->format(kirpi_date_format($style));
    } catch (Throwable) {
        return $empty;
    }
}

function kirpi_format_datetime(null|string|DateTimeInterface $value, string $style = 'long', string $empty = '-'): string
{
    return kirpi_format_date($value, $style, $empty);
}

function set_flash_message(string $type, string $message): void
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash_message(): ?array
{
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }

    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);

    return $message;
}

function kirpi_upload_avatar(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'Gorsel yuklenemedi.',
        ];
    }

    $maxSize = 2 * 1024 * 1024;
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (($file['size'] ?? 0) > $maxSize) {
        return [
            'success' => false,
            'message' => 'Gorsel boyutu 2 MB sinirini asiyor.',
        ];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!isset($allowedMimeTypes[$mimeType])) {
        return [
            'success' => false,
            'message' => 'Desteklenmeyen gorsel formati.',
        ];
    }

    $extension = $allowedMimeTypes[$mimeType];
    $fileName = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $extension;

    $uploadsRoot = BASE_PATH . '/uploads';
    $uploadDir = BASE_PATH . '/uploads/avatars';

    if (!is_dir($uploadsRoot) && !mkdir($uploadsRoot, 0775, true) && !is_dir($uploadsRoot)) {
        return [
            'success' => false,
            'message' => 'Yükleme kök dizini oluşturulamadı.',
        ];
    }

    if (!is_writable($uploadsRoot)) {
        return [
            'success' => false,
            'message' => 'Yükleme kök dizini yazılabilir değil.',
        ];
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return [
            'success' => false,
            'message' => 'Yükleme dizini oluşturulamadı.',
        ];
    }

    if (!is_writable($uploadDir)) {
        return [
            'success' => false,
            'message' => 'Yükleme dizini yazılabilir değil.',
        ];
    }

    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success' => false,
            'message' => 'Gorsel sunucuya kaydedilemedi.',
        ];
    }

    return [
        'success' => true,
        'file_name' => $fileName,
    ];
}

function get_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', (string) $token);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function render_pagination(int $current_page, int $total_pages, int $links_to_show = 2): void
{
    if ($total_pages <= 1) {
        return;
    }

    echo '<ul class="pagination m-0 ms-auto">';

    $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
    echo "<li class='page-item {$prev_disabled}'>
            <a class='page-link' href='#' data-page='" . ($current_page - 1) . "'>Onceki</a>
          </li>";

    for ($i = 1; $i <= $total_pages; $i++) {
        if (
            $i === 1 ||
            $i === $total_pages ||
            ($i >= $current_page - $links_to_show && $i <= $current_page + $links_to_show)
        ) {
            $active_class = ($i === $current_page) ? 'active' : '';
            echo "<li class='page-item {$active_class}'>
                    <a class='page-link' href='#' data-page='{$i}'>{$i}</a>
                  </li>";
        } elseif (
            $i === $current_page - ($links_to_show + 1) ||
            $i === $current_page + ($links_to_show + 1)
        ) {
            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }

    $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
    echo "<li class='page-item {$next_disabled}'>
            <a class='page-link' href='#' data-page='" . ($current_page + 1) . "'>Sonraki</a>
          </li>";

    echo '</ul>';
}

function resolve_page_script(?string $routeFile): ?string
{
    if (!$routeFile) {
        return null;
    }

    $scriptPath = str_replace(['pages/', '.php'], ['scripts/', '.js'], $routeFile);
    $fullPath = BASE_PATH . '/' . $scriptPath;

    return is_file($fullPath) ? $scriptPath : null;
}

function page_script_url(string $scriptPath): string
{
    $normalizedPath = ltrim($scriptPath, '/');
    $scriptFile = BASE_PATH . '/' . $normalizedPath;
    $version = is_file($scriptFile) ? (string) filemtime($scriptFile) : APP_VER;

    return base_url($normalizedPath) . '?v=' . rawurlencode($version);
}
