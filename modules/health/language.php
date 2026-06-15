<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function health_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                // Genel Başlıklar
                'system_management' => 'Sistem Yönetimi',
                'health_metrics' => 'Health + Metrics',
                'system_matrix' => 'Sistem Matrix',
                'env_reader' => 'Env Reader',
                'env_reader_detail' => 'Çalışan uygulama ortamındaki environment değişkenleri. Hassas anahtarların değerleri maskelenir.',

                // Tablo Başlıkları
                'last_check' => 'Last Check',
                'component' => 'Bileşen',
                'status' => 'Status',
                'latency' => 'Latency',
                'detail' => 'Detay',
                'key' => 'Anahtar',
                'value' => 'Değer',
                'source' => 'Kaynak',
                'env_count' => 'Env Sayısı',
                'masked' => 'Maskeli',
                'visible' => 'Görünür',
                'no_env_item' => 'Environment değişkeni bulunamadı.',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',

                // Durum ve Hata Mesajları
                'db_connection_ok' => 'Bağlantı başarılı',
                'db_query_failed' => 'DB sorgusu başarısız',
                'queue_table_missing' => 'Queue tablosu yok',
                'queue_metrics_unreadable' => 'Queue metrikleri okunamadı',
                'mail_host_empty' => 'MAIL_HOST boş',
                'mail_host_defined_prefix' => 'SMTP host tanımlı: ',
                'backup_table_missing' => 'Backup tablosu yok',
                'backup_metrics_unreadable' => 'Backup metrikleri okunamadı',
                'disk_unreadable' => 'Disk bilgisi okunamadı',
                'throttle_disabled_or_missing' => 'Throttle devre dışı veya tablo yok',
                'throttle_metrics_unreadable' => 'Throttle metrikleri okunamadı',
            ],
            'en' => [
                'system_management' => 'System Management',
                'health_metrics' => 'Health + Metrics',
                'system_matrix' => 'System Matrix',
                'env_reader' => 'Env Reader',
                'env_reader_detail' => 'Environment variables visible to the running application. Sensitive key values are masked.',
                'last_check' => 'Last Check',
                'component' => 'Component',
                'status' => 'Status',
                'latency' => 'Latency',
                'detail' => 'Detail',
                'key' => 'Key',
                'value' => 'Value',
                'source' => 'Source',
                'env_count' => 'Env Count',
                'masked' => 'Masked',
                'visible' => 'Visible',
                'no_env_item' => 'No environment variable found.',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
                'db_connection_ok' => 'Connection successful',
                'db_query_failed' => 'Database query failed',
                'queue_table_missing' => 'Queue table is missing',
                'queue_metrics_unreadable' => 'Queue metrics could not be read',
                'mail_host_empty' => 'MAIL_HOST is empty',
                'mail_host_defined_prefix' => 'SMTP host defined: ',
                'backup_table_missing' => 'Backup table is missing',
                'backup_metrics_unreadable' => 'Backup metrics could not be read',
                'disk_unreadable' => 'Disk info could not be read',
                'throttle_disabled_or_missing' => 'Throttle is disabled or table is missing',
                'throttle_metrics_unreadable' => 'Throttle metrics could not be read',
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
