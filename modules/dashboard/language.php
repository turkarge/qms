<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function dashboard_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                // Genel Başlıklar ve Dashboard
                'brand' => 'Kirpi Core',
                'dashboard' => 'Dashboard',
                'summary' => 'Core sistem özeti ve canlı sağlık durumu.',
                'settings' => 'Ayarlar',
                'close' => 'Kapat',

                // Kullanıcı ve Yetkilendirme
                'users' => 'Kullanıcılar',
                'active_prefix' => 'Aktif: ',
                'roles' => 'Roller',
                'roles_hint' => 'Yetki yapısı hazır',
                'unread_notifications' => 'Okunmamış Bildirim',
                'user_based_active' => 'Aktif kullanıcı bazlı',

                // Modül ve API Metrikleri
                'modules' => 'Modüller',
                'active_module_count' => 'Aktif modül sayısı',
                'health_metrics' => 'Health Metrics',
                'api_calls_24h' => 'API Çağrı (24s)',
                'api_calls_24h_hint' => 'Son 24 saatte toplam API istek sayısı',
                'active_throttle_blocks' => 'Aktif Throttle Blok',
                'throttle_blocks_hint' => 'Rate limit nedeniyle geçici bloklanan anahtarlar',

                // Sistem Kontrol Listesi
                'system_checklist' => 'Sistem Kontrol Listesi',
                'check_default' => 'Kontrol',
                'front_controller' => 'Front controller',
                'front_controller_ok' => 'index.php route akışı çalışıyor.',
                'database_schema' => 'Database schema',
                'database_ok' => 'Temel tablolar ulaşılabilir.',
                'database_missing' => 'Temel tablolar eksik görünüyor.',
                'upload_folder' => 'Upload klasörü',
                'upload_ok' => 'Upload dizini yazılabilir.',
                'upload_warn' => 'uploads dizini yazma izni kontrol edilmeli.',

                // API ve Throttle Durumu
                'api_status' => 'API durumu',
                'api_on' => 'API aktif durumda.',
                'api_off' => 'API kapalı durumda.',
                'throttle_protection' => 'Throttle koruması',
                'throttle_on' => 'Rate limit koruması aktif.',
                'throttle_off' => 'Throttle devre dışı.',

                // Hakkında Bilgileri
                'about_title' => 'Kirpi Core Hakkında',
                'about_app' => 'Uygulama',
                'about_env' => 'Ortam',
                'about_debug' => 'Debug',
                'about_debug_on' => 'Açık',
                'about_debug_off' => 'Kapalı',
                'about_description' => 'Açıklama',
                'about_text' => 'Kirpi Core; modüler, hızlı geliştirilebilir ve tekrar kullanılabilir PHP uygulamaları üretmek için hazırlanmış çekirdek uygulama yapısıdır.',
            ],
            'en' => [
                'brand' => 'Kirpi Core',
                'dashboard' => 'Dashboard',
                'summary' => 'Core system summary and live health status.',
                'health_metrics' => 'Health Metrics',
                'settings' => 'Settings',
                'users' => 'Users',
                'active_prefix' => 'Active: ',
                'roles' => 'Roles',
                'roles_hint' => 'Permission structure ready',
                'unread_notifications' => 'Unread Notifications',
                'user_based_active' => 'Active user based',
                'modules' => 'Modules',
                'active_module_count' => 'Active module count',
                'api_calls_24h' => 'API Calls (24h)',
                'api_calls_24h_hint' => 'Total API requests in the last 24 hours',
                'active_throttle_blocks' => 'Active Throttle Blocks',
                'throttle_blocks_hint' => 'Temporarily blocked keys due to rate limits',
                'system_checklist' => 'System Checklist',
                'check_default' => 'Check',
                'front_controller' => 'Front controller',
                'front_controller_ok' => 'index.php route flow is working.',
                'database_schema' => 'Database schema',
                'database_ok' => 'Core tables are reachable.',
                'database_missing' => 'Core tables appear to be missing.',
                'upload_folder' => 'Upload folder',
                'upload_ok' => 'Upload directory is writable.',
                'upload_warn' => 'Check write permission for uploads.',
                'api_status' => 'API status',
                'api_on' => 'API is enabled.',
                'api_off' => 'API is disabled.',
                'throttle_protection' => 'Throttle protection',
                'throttle_on' => 'Rate limit protection is enabled.',
                'throttle_off' => 'Throttle is disabled.',
                'about_title' => 'About Kirpi Core',
                'about_app' => 'Application',
                'about_env' => 'Environment',
                'about_debug' => 'Debug',
                'about_debug_on' => 'On',
                'about_debug_off' => 'Off',
                'about_description' => 'Description',
                'about_text' => 'Kirpi Core is a core application structure built for modular, rapidly developable, and reusable PHP applications.',
                'close' => 'Close',
            ],
        ];
    }

    $locale = strtolower((string) env('APP_LOCALE', 'tr'));
    if (!isset($dictionary[$locale])) {
        $locale = 'tr';
    }

    if (isset($dictionary[$locale][$key])) {
        return $dictionary[$locale][$key];
    }

    if (isset($dictionary['tr'][$key])) {
        return $dictionary['tr'][$key];
    }

    return $default ?? $key;
}
