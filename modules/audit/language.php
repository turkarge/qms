<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function audit_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                'system_management' => 'Sistem Yönetimi',
                'audit_log' => 'Audit Log',
                'audit_overview' => 'Audit Özeti',
                'audit_overview_hint' => 'Son denetim olaylarını modül, durum ve kullanıcı bazında özetler.',
                'records' => 'Audit Kayıtları',
                'filters' => 'Filtreler',
                'status' => 'Durum',
                'all' => 'Tümü',
                'module' => 'Modül',
                'action' => 'İşlem',
                'user_id' => 'Kullanıcı ID',
                'date' => 'Tarih',
                'user' => 'Kullanıcı',
                'route' => 'Rota',
                'ip' => 'IP',
                'detail' => 'Detay',
                'view' => 'Gör',
                'entity' => 'Entity',
                'no_records' => 'Kayıt bulunamadı.',
                'table_missing' => 'Audit log tablosu kurulu değil. Kurulum için setup veya db:install çalıştırın.',
                'table_missing_short' => 'Audit log tablosu henüz kurulu değil.',
                'table_waiting' => 'Audit tablosu hazır olduğunda liste burada görünecek.',
                'load_error' => 'Audit kayıtları yüklenirken bir hata oluştu.',
                'failed' => 'failed',
                'print_pdf' => 'Yazdır',
                'email' => 'E-posta',
                'csv_export' => 'CSV',
                'excel_export' => 'Excel',
                'server_excel_export' => 'Excel İndir',
                'events_24h' => 'Son 24 Saat',
                'events_7d' => 'Son 7 Gün',
                'failed_7d' => '7 Gün Hata',
                'active_modules_7d' => 'Aktif Modül',
                'module_distribution' => 'Modül Dağılımı',
                'event_count' => 'Olay',
                'success_count' => 'Başarılı',
                'failed_count' => 'Hatalı',
                'last_event' => 'Son Olay',
                'first_event' => 'İlk Olay',
                'user_count' => 'Kullanıcı Sayısı',
                'overview_export' => 'Özet Excel',
                'audit_search_placeholder' => 'Audit kayıtlarında ara',
            ],
            'en' => [
                'system_management' => 'System Management',
                'audit_log' => 'Audit Log',
                'audit_overview' => 'Audit Overview',
                'audit_overview_hint' => 'Summarizes recent audit events by module, status, and user.',
                'records' => 'Audit Records',
                'table_missing' => 'Audit log table is not installed. Run setup or db:install.',
                'filters' => 'Filters',
                'status' => 'Status',
                'all' => 'All',
                'module' => 'Module',
                'action' => 'Action',
                'user_id' => 'User ID',
                'table_waiting' => 'The list will appear here when audit table is ready.',
                'table_missing_short' => 'Audit log table is not installed yet.',
                'date' => 'Date',
                'user' => 'User',
                'route' => 'Route',
                'ip' => 'IP',
                'detail' => 'Detail',
                'no_records' => 'No records found.',
                'view' => 'View',
                'entity' => 'Entity',
                'failed' => 'failed',
                'load_error' => 'An error occurred while loading audit records.',
                'print_pdf' => 'Print',
                'email' => 'Email',
                'csv_export' => 'CSV',
                'excel_export' => 'Excel',
                'server_excel_export' => 'Download Excel',
                'events_24h' => 'Last 24 Hours',
                'events_7d' => 'Last 7 Days',
                'failed_7d' => '7 Day Failures',
                'active_modules_7d' => 'Active Modules',
                'module_distribution' => 'Module Distribution',
                'event_count' => 'Events',
                'success_count' => 'Success',
                'failed_count' => 'Failed',
                'last_event' => 'Last Event',
                'first_event' => 'First Event',
                'user_count' => 'User Count',
                'overview_export' => 'Summary Excel',
                'audit_search_placeholder' => 'Search audit records',
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
