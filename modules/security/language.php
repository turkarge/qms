<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function security_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                // Sistem Kontrolleri (Checks)
                'check_app_env_name' => 'Uygulama ortamı',
                'check_app_env_hint' => 'APP_ENV production olmalıdır.',
                'check_debug_name' => 'Debug modu',
                'check_debug_hint' => 'Production ortamında APP_DEBUG false olmalıdır.',
                'check_proxy_name' => 'Proxy güvenliği',
                'check_proxy_hint' => 'Reverse proxy kullanımında APP_TRUST_PROXY true önerilir.',
                'check_web_setup_name' => 'Web setup',
                'check_web_setup_hint' => 'Kurulumdan sonra AUTO_WEB_SETUP=false yapın.',
                'check_setup_key_name' => 'Setup key',
                'check_setup_key_hint' => 'SETUP_KEY boş olmamalıdır.',
                'check_session_secure_name' => 'Session secure cookie',
                'check_session_secure_hint' => 'HTTPS için session.cookie_secure=1 olmalıdır.',
                'check_session_samesite_name' => 'Session samesite',
                'check_session_samesite_hint' => 'session.cookie_samesite=Lax önerilir.',

                // Durum Etiketleri
                'enabled' => 'enabled',
                'disabled' => 'disabled',
                'configured' => 'configured',
                'empty' => 'empty',
                'yes' => 'Evet',
                'no' => 'Hayır',

                // Sayfa Başlıkları
                'page_pretitle' => 'Sistem Yönetimi',
                'page_title' => 'Güvenlik İzleme',
                'security_checks_title' => 'Güvenlik Kontrolleri',

                // Tablo Başlıkları
                'col_check' => 'Kontrol',
                'col_value' => 'Değer',
                'col_status' => 'Durum',
                'col_note' => 'Not',
                'status_warn' => 'Uyarı',

                // Dosya ve Klasör İzinleri
                'dirs_title' => 'Dosya ve Klasör İzinleri',
                'col_folder' => 'Klasör',
                'col_path' => 'Yol',
                'col_exists' => 'Var mı',
                'col_writable' => 'Yazılabilir mi',
                'col_perm' => 'İzin (Perm)',

                // Veritabanı
                'db_tables_title' => 'Veritabanı Tabloları',
                'db_empty' => 'Tablo bulunamadı veya veritabanı okunamadı.',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
            ],
            'en' => [
                'check_app_env_name' => 'Application environment',
                'check_app_env_hint' => 'APP_ENV should be production.',
                'check_debug_name' => 'Debug mode',
                'check_debug_hint' => 'APP_DEBUG should be false in production.',
                'check_proxy_name' => 'Trusted proxy',
                'check_proxy_hint' => 'APP_TRUST_PROXY=true is recommended behind reverse proxy.',
                'check_web_setup_name' => 'Web setup',
                'check_web_setup_hint' => 'Set AUTO_WEB_SETUP=false after installation.',
                'check_setup_key_name' => 'Setup key',
                'check_setup_key_hint' => 'SETUP_KEY should not be empty.',
                'check_session_secure_name' => 'Session secure cookie',
                'check_session_secure_hint' => 'session.cookie_secure=1 should be set for HTTPS.',
                'check_session_samesite_name' => 'Session samesite',
                'check_session_samesite_hint' => 'session.cookie_samesite=Lax is recommended.',
                'enabled' => 'enabled',
                'disabled' => 'disabled',
                'configured' => 'configured',
                'empty' => 'empty',
                'page_pretitle' => 'System Management',
                'page_title' => 'Security Monitor',
                'security_checks_title' => 'Security Checks',
                'col_check' => 'Check',
                'col_value' => 'Value',
                'col_status' => 'Status',
                'col_note' => 'Note',
                'status_warn' => 'Warning',
                'dirs_title' => 'File and Folder Permissions',
                'col_folder' => 'Folder',
                'col_path' => 'Path',
                'col_exists' => 'Exists',
                'col_writable' => 'Writable',
                'col_perm' => 'Perm',
                'yes' => 'Yes',
                'no' => 'No',
                'db_tables_title' => 'Database Tables',
                'db_empty' => 'No table found or database could not be read.',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
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
