<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function kirpi_templates_table_ready(): bool
{
    return db_table_exists('templates');
}

function kirpi_template_kinds(): array
{
    return ['email', 'print', 'content'];
}

function kirpi_template_supported_modules(): array
{
    return [
        'auth' => 'Auth',
        'mail' => 'Mail',
        'users' => 'Users',
        'notifications' => 'Notifications',
        'queue' => 'Queue',
        'backup' => 'Backup',
        'documents' => 'Documents',
        'settings' => 'Settings',
        'template' => 'Template',
        'api' => 'API',
        'profile' => 'Profile',
        'security' => 'Security',
        'health' => 'Health',
        'audit' => 'Audit',
        'ai' => 'Kirpi Intelligence',
        'core' => 'Core',
    ];
}

function kirpi_template_supported_targets(string $kind): array
{
    $targets = [
        'email' => [
            'auth.password_reset' => 'Şifre sıfırlama',
            'mail.test_manual' => 'Manuel test maili',
            'queue.test_mail' => 'Queue test maili',
            'users.session_dropped' => 'Kullanıcı oturum düşürme',
            'users.lock_key_reset' => 'Kullanıcı lock key sıfırlama',
            'notifications.generic' => 'Genel bildirim',
            'audit.summary' => 'Audit özeti',
            'ai.summary' => 'AI özeti',
        ],
        'print' => [
            'audit.overview' => 'Audit genel görünüm',
            'users.list' => 'Kullanıcı listesi',
            'roles.list' => 'Rol listesi',
            'system.report' => 'Sistem raporu',
        ],
        'content' => [
            'notifications.generic' => 'Genel bildirim',
            'users.session_dropped' => 'Kullanıcı oturum düşürme bildirimi',
            'users.lock_key_reset' => 'Kullanıcı lock key sıfırlama bildirimi',
            'backup.completed' => 'Backup tamamlandı bildirimi',
            'backup.restored' => 'Backup restore bildirimi',
            'ai.schema_synced' => 'AI schema sync bildirimi',
            'queue.job_failed' => 'Queue job hata bildirimi',
            'queue.retry_failed' => 'Queue retry bildirimi',
            'dashboard.notice' => 'Dashboard duyurusu',
            'system.footer' => 'Sistem footer içeriği',
            'terms.content' => 'Kullanım koşulları',
        ],
    ];

    if ($kind === 'content') {
        $targets['content'] = ($targets['content'] ?? []) + kirpi_template_notification_target_catalog();
    }

    return $targets[$kind] ?? [];
}

function kirpi_template_notification_target_catalog(): array
{
    return [
        'users.created' => 'Kullanıcı oluşturma bildirimi',
        'users.updated' => 'Kullanıcı güncelleme bildirimi',
        'users.status_changed' => 'Kullanıcı durum bildirimi',
        'roles.created' => 'Rol oluşturma bildirimi',
        'roles.updated' => 'Rol güncelleme bildirimi',
        'roles.status_changed' => 'Rol durum bildirimi',
        'roles.permissions_updated' => 'Rol izin bildirimi',
        'documents.uploaded' => 'Doküman yükleme bildirimi',
        'documents.deleted' => 'Doküman silme bildirimi',
        'mail.test_sent' => 'Test mail bildirimi',
        'mail.template_created' => 'Mail şablonu oluşturma bildirimi',
        'mail.template_updated' => 'Mail şablonu güncelleme bildirimi',
        'mail.template_deleted' => 'Mail şablonu silme bildirimi',
        'backup.verified' => 'Backup doğrulama bildirimi',
        'backup.deleted' => 'Backup silme bildirimi',
        'api.token_created' => 'API token oluşturma bildirimi',
        'api.token_revoked' => 'API token iptal bildirimi',
        'queue.mail_enqueued' => 'Queue mail işi bildirimi',
        'settings.updated' => 'Ayar güncelleme bildirimi',
        'settings.module_toggled' => 'Modül durum bildirimi',
        'settings.schema_installed' => 'Eksik kurulum bildirimi',
        'template.created' => 'Şablon oluşturma bildirimi',
        'template.updated' => 'Şablon güncelleme bildirimi',
        'template.status_changed' => 'Şablon durum bildirimi',
    ];
}

function kirpi_template_normalize_code(string $value): string
{
    return strtolower(trim($value));
}

function kirpi_template_normalize_variables(string|array|null $variables): array
{
    if (is_array($variables)) {
        $items = $variables;
    } else {
        $items = preg_split('/[\s,]+/', trim((string) $variables)) ?: [];
    }

    $normalized = [];
    foreach ($items as $item) {
        $item = trim((string) $item);
        $item = trim($item, '{} ');
        if ($item === '') {
            continue;
        }
        $normalized[] = $item;
    }

    $normalized = array_values(array_unique($normalized));
    sort($normalized);

    return $normalized;
}

function kirpi_template_variables_for_target(string $targetKey): array
{
    $variables = [
        'app_name',
        'app_url',
        'year',
    ];

    $targetVariables = [
        'auth.password_reset' => ['user_name', 'reset_link', 'expires_minutes'],
        'mail.test_manual' => ['message_html'],
        'queue.test_mail' => ['user_name', 'sent_at'],
        'users.session_dropped' => ['user_name'],
        'users.lock_key_reset' => ['user_name'],
        'notifications.generic' => ['title', 'message', 'action_url'],
        'backup.completed' => ['label', 'file_name', 'file_size'],
        'backup.restored' => ['backup_id'],
        'ai.schema_synced' => ['entity_count', 'field_count'],
        'queue.job_failed' => ['job_type', 'error_message'],
        'queue.retry_failed' => ['affected_count'],
        'audit.summary' => ['period', 'total_events', 'failed_events'],
        'ai.summary' => ['entity_count', 'field_count', 'audit_count'],
        'audit.overview' => ['generated_at', 'total_events'],
        'users.list' => ['generated_at', 'user_count'],
        'roles.list' => ['generated_at', 'role_count'],
        'system.report' => ['generated_at', 'app_version'],
    ];
    $targetVariables += kirpi_template_notification_variable_catalog();

    return kirpi_template_normalize_variables(array_merge($variables, $targetVariables[$targetKey] ?? []));
}

function kirpi_template_notification_variable_catalog(): array
{
    return [
        'users.created' => ['name', 'email', 'role_id', 'is_active'],
        'users.updated' => ['name', 'email', 'role_id', 'is_active', 'password_changed', 'avatar_changed'],
        'users.status_changed' => ['target_user_id', 'is_active', 'status_label'],
        'roles.created' => ['name', 'is_active'],
        'roles.updated' => ['name', 'is_active'],
        'roles.status_changed' => ['name', 'is_active', 'status_label'],
        'roles.permissions_updated' => ['name', 'permission_count', 'permission_slugs'],
        'documents.uploaded' => ['document_id', 'document_type', 'entity_type', 'entity_id'],
        'documents.deleted' => ['document_id', 'original_name'],
        'mail.test_sent' => ['recipient_email', 'subject', 'transport'],
        'mail.template_created' => ['template_key', 'name', 'is_active'],
        'mail.template_updated' => ['template_id', 'template_key', 'name', 'is_active'],
        'mail.template_deleted' => ['template_id', 'template_key'],
        'backup.verified' => ['backup_id', 'checksum', 'dry_run', 'dry_run_table_count'],
        'backup.deleted' => ['backup_id', 'file_name'],
        'api.token_created' => ['token_id', 'token_name', 'expires_at', 'ttl_option', 'scope_option'],
        'api.token_revoked' => ['token_id'],
        'queue.mail_enqueued' => ['job_id', 'recipient_email'],
        'settings.updated' => ['changed_keys', 'changed_count'],
        'settings.module_toggled' => ['module_key', 'is_enabled', 'status_label'],
        'settings.schema_installed' => ['before_missing_table_count', 'after_missing_table_count', 'before_missing_column_count', 'after_missing_column_count', 'before_missing_index_count', 'after_missing_index_count'],
        'template.created' => ['kind', 'module_key', 'target_key', 'code', 'name'],
        'template.updated' => ['id', 'kind', 'code', 'name', 'is_active'],
        'template.status_changed' => ['id', 'code', 'is_active', 'status_label'],
    ];
}

function kirpi_template_render_string(string $body, array $context, bool $escape = true): string
{
    $flat = [];
    $walker = function (array $data, string $prefix = '') use (&$walker, &$flat, $escape): void {
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
            if (is_array($value)) {
                $walker($value, $path);
                continue;
            }

            $stringValue = (string) ($value ?? '');
            $flat['{{' . $path . '}}'] = $escape ? htmlspecialchars($stringValue, ENT_QUOTES, 'UTF-8') : $stringValue;
            $flat['{{{' . $path . '}}}'] = $stringValue;
        }
    };
    $walker($context);

    return strtr($body, $flat);
}

function kirpi_template_extract_placeholders(string $content): array
{
    if ($content === '') {
        return [];
    }

    if (preg_match_all('/\{\{\{?\s*([a-zA-Z0-9_.-]+)\s*\}?\}\}/', $content, $matches) !== 1) {
        return [];
    }

    return kirpi_template_normalize_variables($matches[1] ?? []);
}

function kirpi_template_kind_module_for_key(string $templateKey): string
{
    $parts = explode('.', $templateKey, 2);
    $moduleKey = trim((string) ($parts[0] ?? ''));

    return array_key_exists($moduleKey, kirpi_template_supported_modules()) ? $moduleKey : 'mail';
}

function kirpi_template_default_notification_templates(): array
{
    return [
        'notifications.generic' => [
            'name' => 'Notifications - Generic',
            'subject' => '{{title}}',
            'body' => '{{message}}',
        ],
        'users.session_dropped' => [
            'name' => 'Users - Session Dropped Notification',
            'subject' => 'Oturum sonlandırıldı',
            'body' => 'Yetkili bir kullanıcı tüm aktif oturumlarınızı sonlandırdı. Lütfen yeniden giriş yapın.',
        ],
        'users.lock_key_reset' => [
            'name' => 'Users - Lock Key Reset Notification',
            'subject' => 'Lock key sıfırlandı',
            'body' => 'Yetkili bir kullanıcı oturum kilitleme keyinizi sıfırladı ve özelliği pasif yaptı.',
        ],
        'backup.completed' => [
            'name' => 'Backup - Completed Notification',
            'subject' => 'Backup tamamlandı',
            'body' => '{{label}} backup işlemi tamamlandı. Dosya: {{file_name}}',
        ],
        'backup.restored' => [
            'name' => 'Backup - Restored Notification',
            'subject' => 'Backup restore tamamlandı',
            'body' => 'Backup #{{backup_id}} geri yükleme işlemi tamamlandı.',
        ],
        'ai.schema_synced' => [
            'name' => 'AI - Schema Synced Notification',
            'subject' => 'AI schema registry güncellendi',
            'body' => '{{entity_count}} entity ve {{field_count}} field senkronize edildi.',
        ],
        'queue.job_failed' => [
            'name' => 'Queue - Job Failed Notification',
            'subject' => 'Queue job başarısız oldu',
            'body' => '{{job_type}} job çalışırken hata oluştu: {{error_message}}',
        ],
        'queue.retry_failed' => [
            'name' => 'Queue - Retry Failed Jobs Notification',
            'subject' => 'Queue retry başlatıldı',
            'body' => '{{affected_count}} başarısız queue işi yeniden kuyruğa alındı.',
        ],
    ];
}

function kirpi_template_extra_notification_templates(): array
{
    return [
        'users.created' => ['name' => 'Users - Created Notification', 'subject' => 'Kullanıcı oluşturuldu', 'body' => '{{name}} kullanıcısı oluşturuldu.'],
        'users.updated' => ['name' => 'Users - Updated Notification', 'subject' => 'Kullanıcı güncellendi', 'body' => '{{name}} kullanıcısı güncellendi.'],
        'users.status_changed' => ['name' => 'Users - Status Notification', 'subject' => 'Kullanıcı durumu güncellendi', 'body' => 'Kullanıcı #{{target_user_id}} {{status_label}} yapıldı.'],
        'roles.created' => ['name' => 'Roles - Created Notification', 'subject' => 'Rol oluşturuldu', 'body' => '{{name}} rolü oluşturuldu.'],
        'roles.updated' => ['name' => 'Roles - Updated Notification', 'subject' => 'Rol güncellendi', 'body' => '{{name}} rolü güncellendi.'],
        'roles.status_changed' => ['name' => 'Roles - Status Notification', 'subject' => 'Rol durumu güncellendi', 'body' => '{{name}} rolü {{status_label}} yapıldı.'],
        'roles.permissions_updated' => ['name' => 'Roles - Permissions Notification', 'subject' => 'Rol izinleri güncellendi', 'body' => '{{name}} rolünün {{permission_count}} izni güncellendi.'],
        'documents.uploaded' => ['name' => 'Documents - Uploaded Notification', 'subject' => 'Doküman yüklendi', 'body' => 'Doküman #{{document_id}} yüklendi.'],
        'documents.deleted' => ['name' => 'Documents - Deleted Notification', 'subject' => 'Doküman silindi', 'body' => '{{original_name}} dokümanı silindi.'],
        'mail.test_sent' => ['name' => 'Mail - Test Sent Notification', 'subject' => 'Test mail gönderildi', 'body' => '{{recipient_email}} adresine test maili gönderildi.'],
        'mail.template_created' => ['name' => 'Mail - Template Created Notification', 'subject' => 'Mail şablonu oluşturuldu', 'body' => '{{name}} mail şablonu oluşturuldu.'],
        'mail.template_updated' => ['name' => 'Mail - Template Updated Notification', 'subject' => 'Mail şablonu güncellendi', 'body' => '{{name}} mail şablonu güncellendi.'],
        'mail.template_deleted' => ['name' => 'Mail - Template Deleted Notification', 'subject' => 'Mail şablonu silindi', 'body' => '{{template_key}} mail şablonu silindi.'],
        'backup.verified' => ['name' => 'Backup - Verified Notification', 'subject' => 'Yedek doğrulandı', 'body' => 'Yedek #{{backup_id}} doğrulandı.'],
        'backup.deleted' => ['name' => 'Backup - Deleted Notification', 'subject' => 'Yedek silindi', 'body' => '{{file_name}} yedeği silindi.'],
        'api.token_created' => ['name' => 'API - Token Created Notification', 'subject' => 'API token oluşturuldu', 'body' => '{{token_name}} API token kaydı oluşturuldu.'],
        'api.token_revoked' => ['name' => 'API - Token Revoked Notification', 'subject' => 'API token iptal edildi', 'body' => 'API token #{{token_id}} iptal edildi.'],
        'queue.mail_enqueued' => ['name' => 'Queue - Mail Enqueued Notification', 'subject' => 'Mail işi kuyruğa alındı', 'body' => 'Mail işi #{{job_id}} kuyruğa alındı.'],
        'settings.updated' => ['name' => 'Settings - Updated Notification', 'subject' => 'Ayarlar güncellendi', 'body' => '{{changed_count}} ayar güncellendi.'],
        'settings.module_toggled' => ['name' => 'Settings - Module Toggled Notification', 'subject' => 'Modül durumu güncellendi', 'body' => '{{module_key}} modülü {{status_label}} yapıldı.'],
        'settings.schema_installed' => ['name' => 'Settings - Schema Installed Notification', 'subject' => 'Eksik kurulum kontrolü tamamlandı', 'body' => 'Eksik kurulum kontrolü tamamlandı.'],
        'template.created' => ['name' => 'Template - Created Notification', 'subject' => 'Şablon oluşturuldu', 'body' => '{{name}} şablonu oluşturuldu.'],
        'template.updated' => ['name' => 'Template - Updated Notification', 'subject' => 'Şablon güncellendi', 'body' => '{{name}} şablonu güncellendi.'],
        'template.status_changed' => ['name' => 'Template - Status Notification', 'subject' => 'Şablon durumu güncellendi', 'body' => '{{code}} şablonu {{status_label}} yapıldı.'],
    ];
}

function kirpi_template_sync_notification_defaults(): void
{
    $language = strtolower((string) env('APP_LOCALE', 'tr'));
    $language = $language !== '' ? $language : 'tr';

    $templates = kirpi_template_default_notification_templates() + kirpi_template_extra_notification_templates();

    foreach ($templates as $templateKey => $template) {
        $key = kirpi_template_normalize_code((string) $templateKey);
        if ($key === '') {
            continue;
        }

        kirpi_template_upsert_system(
            'content',
            kirpi_template_kind_module_for_key($key),
            $key,
            $key,
            (string) ($template['name'] ?? $key),
            $language,
            (string) ($template['subject'] ?? ''),
            (string) ($template['body'] ?? ''),
            kirpi_template_variables_for_target($key)
        );
    }
}

function kirpi_template_find_content_template(string $templateKey, bool $mustBeActive = true): ?array
{
    if (!kirpi_templates_table_ready()) {
        return null;
    }

    $templateKey = kirpi_template_normalize_code($templateKey);
    if ($templateKey === '') {
        return null;
    }

    $language = strtolower((string) env('APP_LOCALE', 'tr'));
    $language = $language !== '' ? $language : 'tr';

    try {
        $sql = "
            SELECT id, code, name, subject, body, is_active, is_system, updated_at
            FROM templates
            WHERE kind = 'content'
              AND language = :language
              AND (code = :template_key OR target_key = :target_key)
        ";
        if ($mustBeActive) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY code = :template_key_sort DESC LIMIT 1";

        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':language' => $language,
            ':template_key' => $templateKey,
            ':target_key' => $templateKey,
            ':template_key_sort' => $templateKey,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('template content lookup error: ' . $e->getMessage());
        return null;
    }
}

function kirpi_template_find_active(string $kind, string $moduleKey, string $targetKey, string $code, string $language = 'tr'): ?array
{
    if (!kirpi_templates_table_ready()) {
        return null;
    }

    try {
        $stmt = db()->prepare("
            SELECT *
            FROM templates
            WHERE kind = :kind
              AND module_key = :module_key
              AND target_key = :target_key
              AND code = :code
              AND language = :language
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':kind' => $kind,
            ':module_key' => $moduleKey,
            ':target_key' => $targetKey,
            ':code' => $code,
            ':language' => $language,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('template find error: ' . $e->getMessage());
        return null;
    }
}

function kirpi_template_upsert_system(
    string $kind,
    string $moduleKey,
    string $targetKey,
    string $code,
    string $name,
    string $language,
    ?string $subject,
    string $body,
    array $variables = []
): void {
    if (!kirpi_templates_table_ready()) {
        return;
    }

    $variablesJson = json_encode(kirpi_template_normalize_variables($variables), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    try {
        $stmt = db()->prepare("
            INSERT INTO templates (
                kind, module_key, target_key, code, name, language, subject, body, variables_json, is_system, is_active
            ) VALUES (
                :kind, :module_key, :target_key, :code, :name, :language, :subject, :body, :variables_json, 1, 1
            )
            ON DUPLICATE KEY UPDATE
                is_system = 1,
                variables_json = VALUES(variables_json),
                updated_at = updated_at
        ");
        $stmt->execute([
            ':kind' => $kind,
            ':module_key' => $moduleKey,
            ':target_key' => $targetKey,
            ':code' => $code,
            ':name' => $name,
            ':language' => $language,
            ':subject' => $subject,
            ':body' => $body,
            ':variables_json' => $variablesJson ?: null,
        ]);
    } catch (Throwable $e) {
        error_log('template system upsert error: ' . $e->getMessage());
    }
}

function kirpi_template_sync_mail_defaults(array $defaults): void
{
    $language = strtolower((string) env('APP_LOCALE', 'tr'));
    $language = $language !== '' ? $language : 'tr';

    foreach ($defaults as $templateKey => $template) {
        $key = kirpi_template_normalize_code((string) $templateKey);
        if ($key === '') {
            continue;
        }

        kirpi_template_upsert_system(
            'email',
            kirpi_template_kind_module_for_key($key),
            $key,
            $key,
            (string) ($template['name'] ?? $key),
            $language,
            (string) ($template['subject'] ?? ''),
            (string) ($template['html_body'] ?? ''),
            kirpi_template_variables_for_target($key)
        );
    }
}

function kirpi_template_find_mail_template(string $templateKey, bool $mustBeActive = true): ?array
{
    if (!kirpi_templates_table_ready()) {
        return null;
    }

    $templateKey = kirpi_template_normalize_code($templateKey);
    if ($templateKey === '') {
        return null;
    }

    $language = strtolower((string) env('APP_LOCALE', 'tr'));
    $language = $language !== '' ? $language : 'tr';

    try {
        $sql = "
            SELECT id, code, name, subject, body, is_active, is_system, updated_at
            FROM templates
            WHERE kind = 'email'
              AND language = :language
              AND (code = :template_key OR target_key = :target_key)
        ";
        if ($mustBeActive) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY code = :template_key_sort DESC LIMIT 1";

        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':language' => $language,
            ':template_key' => $templateKey,
            ':target_key' => $templateKey,
            ':template_key_sort' => $templateKey,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'id' => $row['id'] ?? null,
            'template_key' => (string) ($row['code'] ?? $templateKey),
            'name' => (string) ($row['name'] ?? $templateKey),
            'subject' => (string) ($row['subject'] ?? ''),
            'html_body' => (string) ($row['body'] ?? ''),
            'is_active' => (int) ($row['is_active'] ?? 0),
            'is_system' => (int) ($row['is_system'] ?? 0),
            'updated_at' => $row['updated_at'] ?? null,
        ];
    } catch (Throwable $e) {
        error_log('template mail lookup error: ' . $e->getMessage());
        return null;
    }
}
