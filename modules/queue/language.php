<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function queue_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                // --- Sistem Yönetimi ve Dashboard ---
                'system_management'   => 'Sistem Yönetimi',
                'brand'               => 'Kirpi Core',
                'dashboard'           => 'Dashboard',
                'summary'             => 'Core sistem özeti ve canlı sağlık durumu.',
                'health_metrics'      => 'Health + Metrics',
                'settings'            => 'Ayarlar',
                'users'               => 'Kullanıcılar',
                'roles'               => 'Roller',
                'roles_hint'          => 'Yetki yapısı hazır',
                'active_prefix'       => 'Aktif: ',
                'modules'             => 'Modüller',
                'active_module_count' => 'Aktif modül sayısı',

                // --- API ve Metrics ---
                'v1_title'               => 'KirpiCore API v1',
                'api_calls_24h'          => 'API Çağrı (24s)',
                'api_calls_24h_hint'     => 'Son 24 saatte toplam API istek sayısı',
                'active_throttle_blocks' => 'Aktif Throttle Blok',
                'throttle_blocks_hint'   => 'Rate limit nedeniyle geçici bloklanan anahtarlar',
                'api_status'             => 'API durumu',
                'api_on'                 => 'API aktif durumda.',
                'api_off'                => 'API kapalı durumda.',
                'throttle_protection'    => 'Throttle koruması',
                'throttle_on'            => 'Rate limit koruması aktif.',
                'throttle_off'           => 'Throttle devre dışı.',

                // --- Kullanıcı ve Profil ---
                'my_account'          => 'Hesabım',
                'profile'             => 'Profil',
                'profile_info'        => 'Profil Bilgileri',
                'api_tokens'          => 'API Tokenleri',
                'name_surname'        => 'Ad Soyad',
                'email'               => 'E-posta',
                'new_password'        => 'Yeni Şifre',
                'new_password_repeat' => 'Yeni Şifre Tekrar',
                'avatar'              => 'Profil Görseli',
                'update_profile'      => 'Profili Güncelle',

                // --- İletişim ve Bildirimler ---
                'communication_center' => 'İletişim Merkezi',
                'notifications'        => 'Bildirimler',
                'unread_notifications' => 'Okunmamış Bildirim',
                'mark_all_read'        => 'Tümünü Okundu Yap',
                'mail_center'          => 'Mail Merkezi',
                'mail_test_status'     => 'Mail Test ve Durum',
                'mail_configuration'   => 'Mail Konfigürasyonu',

                // --- Kuyruk (Queue) ve İşlemler ---
                'backup_restore'      => 'Backup / Restore',
                'jobs_queue'          => 'Jobs Queue',
                'queue_operations'    => 'Queue İşlemleri',
                'queue'               => 'Kuyruk',
                'last_50_jobs'        => 'Son 50 İş',
                'queued'              => 'Kuyrukta',
                'processing'          => 'İşleniyor',
                'completed'           => 'Tamamlandı',
                'failed'              => 'Başarısız',
                'test_mail_recipient' => 'Test Mail Alıcısı',
                'enqueue_mail_job'    => 'Mail İşini Kuyruğa Ekle',
                'worker_run_once'     => 'Worker Bir Kez Çalıştır',
                'retry_failed_jobs'   => 'Başarısız İşleri Yeniden Dene',
                'attempts'            => 'Deneme',

                // --- Ortak Arayüz ---
                'type'       => 'Tip',
                'error'      => 'Hata',
                'date'       => 'Tarih',
                'status'     => 'Durum',
                'detail'     => 'Detay',
                'actions'    => 'İşlemler',
                'no_records' => 'Kayıt bulunamadı.',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
                'save'       => 'Kaydet',
                'delete'     => 'Sil',
                'cancel'     => 'İptal',
                'close'      => 'Kapat',

                // --- Sistem Mesajları ---
                'csrf_failed'            => 'Güvenlik doğrulaması başarısız oldu.',
                'table_missing'          => 'Gerekli tablo kurulu değil. Kurulum için setup veya db:install çalıştırın.',
                'table_not_ready'        => 'Queue tablosu henüz kurulu değil.',
                'required_fields'        => 'Zorunlu alanları doldurun.',
                'invalid_request'        => 'Geçersiz istek.',
                'invalid_email'          => 'Lütfen geçerli bir e-posta adresi girin.',
                'error_occurred'         => 'Bir hata oluştu.',
                'test_subject'           => 'Kirpi Queue Test Mail',
                'test_body_html'         => '<p>Bu e-posta kuyruk üzerinden gönderildi.</p>',
                'enqueue_success_prefix' => 'Mail işi kuyruğa alındı. İş ID: ',
                'work_failed_default'    => 'Queue işi başarısız oldu.',
                'work_processed_prefix'  => 'Queue işi işlendi. İş ID: ',
                'queue_idle'             => 'Queue boş.',
                'retry_success_prefix'   => 'Yeniden deneme için güncellenen başarısız iş sayısı: ',
            ],
            'en' => [
                // --- System Management and Dashboard ---
                'system_management'   => 'System Management',
                'brand'               => 'Kirpi Core',
                'dashboard'           => 'Dashboard',
                'summary'             => 'Core system summary and live health status.',
                'health_metrics'      => 'Health + Metrics',
                'settings'            => 'Settings',
                'users'               => 'Users',
                'roles'               => 'Roles',
                'roles_hint'          => 'Permission structure is ready',
                'active_prefix'       => 'Active: ',
                'modules'             => 'Modules',
                'active_module_count' => 'Active module count',

                // --- API and Metrics ---
                'v1_title'               => 'KirpiCore API v1',
                'api_calls_24h'          => 'API Calls (24h)',
                'api_calls_24h_hint'     => 'Total API request count in the last 24 hours',
                'active_throttle_blocks' => 'Active Throttle Blocks',
                'throttle_blocks_hint'   => 'Keys temporarily blocked due to rate limit',
                'api_status'             => 'API status',
                'api_on'                 => 'API is active.',
                'api_off'                => 'API is disabled.',
                'throttle_protection'    => 'Throttle protection',
                'throttle_on'            => 'Rate limit protection is active.',
                'throttle_off'           => 'Throttle is disabled.',

                // --- User and Profile ---
                'my_account'          => 'My Account',
                'profile'             => 'Profile',
                'profile_info'        => 'Profile Information',
                'api_tokens'          => 'API Tokens',
                'name_surname'        => 'Full Name',
                'email'               => 'Email',
                'new_password'        => 'New Password',
                'new_password_repeat' => 'Repeat New Password',
                'avatar'              => 'Profile Image',
                'update_profile'      => 'Update Profile',

                // --- Communication and Notifications ---
                'communication_center' => 'Communication Center',
                'notifications'        => 'Notifications',
                'unread_notifications' => 'Unread Notifications',
                'mark_all_read'        => 'Mark All as Read',
                'mail_center'          => 'Mail Center',
                'mail_test_status'     => 'Mail Test and Status',
                'mail_configuration'   => 'Mail Configuration',

                // --- Queue and Jobs ---
                'backup_restore'   => 'Backup / Restore',
                'jobs_queue'       => 'Jobs Queue',
                'queue_operations' => 'Queue Operations',
                'queue'            => 'Queue',
                'last_50_jobs'     => 'Last 50 Jobs',
                'queued'           => 'Queued',
                'processing'       => 'Processing',
                'completed'        => 'Completed',
                'failed'           => 'Failed',
                'test_mail_recipient' => 'Test Mail Recipient',
                'enqueue_mail_job'    => 'Enqueue Mail Job',
                'worker_run_once'     => 'Worker Run Once',
                'retry_failed_jobs'   => 'Retry Failed Jobs',
                'attempts'            => 'Attempts',

                // --- Common Interface ---
                'type'       => 'Type',
                'error'      => 'Error',
                'date'       => 'Date',
                'status'     => 'Status',
                'detail'     => 'Detail',
                'actions'    => 'Actions',
                'no_records' => 'No records found.',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
                'save'       => 'Save',
                'delete'     => 'Delete',
                'cancel'     => 'Cancel',
                'close'      => 'Close',

                // --- System Messages ---
                'csrf_failed'            => 'Security validation failed.',
                'table_missing'          => 'Queue table is not installed. Run setup or db:install.',
                'table_not_ready'        => 'Queue table is not installed yet.',
                'required_fields'        => 'Please fill in required fields.',
                'invalid_request'        => 'Invalid request.',
                'invalid_email'          => 'Please enter a valid email address.',
                'error_occurred'         => 'An error occurred.',
                'test_subject'           => 'Kirpi Queue Test Mail',
                'test_body_html'         => '<p>This email was sent via queue.</p>',
                'enqueue_success_prefix' => 'Mail job enqueued. Job ID: ',
                'work_failed_default'    => 'Queue job failed.',
                'work_processed_prefix'  => 'Queue job processed. Job ID: ',
                'queue_idle'             => 'Queue idle.',
                'retry_success_prefix'   => 'Failed jobs updated for retry: ',
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
