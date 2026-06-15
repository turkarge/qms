<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function api_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                // API Dokümantasyon ve Açıklamalar
                'v1_title' => 'KirpiCore API v1',
                'desc_token' => 'E-posta ve şifre ile bearer token alır',
                'desc_me' => 'Token sahibinin profil bilgisi',
                'desc_users_list' => 'Kullanıcı listesi (users.view)',
                'desc_users_create' => 'Kullanıcı oluşturur (users.create)',
                'desc_users_update' => 'Kullanıcı günceller (users.edit)',
                'desc_users_status' => 'Aktif/pasif durum günceller (users.status)',
                'desc_postman_download' => 'Hazır Postman collection dosyasını indirir',
                'desc_postman_compat' => 'Postman collection için uyumluluk endpointi',

                // API Metrikleri
                'metrics_pretitle' => 'Sistem Yönetimi',
                'metrics_title' => 'API Metrics',
                'window_1h' => '1 Saat',
                'window_24h' => '24 Saat',
                'window_7d' => '7 Gün',
                'total' => 'Toplam',
                'token_unique' => 'Token (Uniq)',
                'avg_ms' => 'Ort. ms',
                'critical_codes' => 'Kritik Kodlar',
                'unique_ip' => 'Unique IP',
                'top_endpoints' => 'En Çok Çağrılan Endpointler',
                'method' => 'Method',
                'path' => 'Path',
                'hit' => 'Hit',
                'recent_errors' => 'Son Hatalar',
                'time' => 'Zaman',
                'ip' => 'IP',
                'error_code' => 'Error Code',
                'no_data' => 'Veri yok',
                'no_error_log' => 'Hata kaydı yok',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',

                // API Hata ve İşlem Mesajları
                'table_missing' => 'api_request_logs tablosu kurulu değil. Ayarlar ekranından Eksikleri Kur çalıştırın.',
                'token_table_missing' => 'API token tablosu hazır değil. Kurulumları tamamlayın.',
                'missing_credentials' => 'E-posta ve şifre zorunludur.',
                'invalid_email' => 'Geçerli bir e-posta girin.',
                'invalid_credentials' => 'Kullanıcı bilgileri hatalı.',
                'user_inactive' => 'Kullanıcı pasif.',
                'role_inactive' => 'Kullanıcı rolü pasif.',
                'token_create_failed' => 'Token oluşturulamadı.',
                'token_created' => 'Token oluşturuldu.',
                'token_create_exception' => 'Token oluşturma sırasında hata oluştu.',
                'users_list_failed' => 'Kullanıcılar listelenemedi.',
                'users_create_required' => 'Ad, e-posta ve şifre zorunludur.',
                'password_min_6' => 'Şifre en az 6 karakter olmalıdır.',
                'password_mismatch' => 'Şifre alanları uyuşmuyor.',
                'role_id_invalid' => 'role_id geçersiz.',
                'email_exists' => 'Bu e-posta zaten kayıtlı.',
                'role_invalid' => 'Seçilen rol geçersiz.',
                'role_inactive_assign' => 'Pasif rol atanamaz.',
                'user_created' => 'Kullanıcı oluşturuldu.',
                'user_create_failed' => 'Kullanıcı oluşturulamadı.',
                'method_not_allowed' => 'Bu endpoint için yöntem desteklenmiyor.',
                'invalid_user_id' => 'Geçersiz kullanıcı ID.',
                'no_fields_to_update' => 'Güncellenecek en az bir alan gönderin.',
                'name_empty' => 'İsim boş olamaz.',
                'email_used_elsewhere' => 'Bu e-posta başka bir kullanıcı tarafından kullanılıyor.',
                'user_not_found' => 'Kullanıcı bulunamadı.',
                'super_admin_cannot_disable' => 'Super Admin kullanıcısı pasife alınamaz.',
                'super_admin_min_one' => 'Sistemde en az bir aktif Super Admin kalmalıdır.',
                'no_updatable_field' => 'Güncellenecek alan bulunamadı.',
                'user_updated' => 'Kullanıcı güncellendi.',
                'user_update_failed' => 'Kullanıcı güncellenemedi.',
                'is_active_required' => 'is_active zorunludur.',
                'user_status_updated' => 'Kullanıcı durumu güncellendi.',
                'user_status_failed' => 'Kullanıcı durumu güncellenemedi.',
                'postman_not_found' => 'Postman collection dosyası bulunamadı.',
                'postman_read_failed' => 'Postman collection dosyası okunamadı.',

                // Ortak Alanlar
                'status' => 'Durum',
                'error' => 'Hata',
            ],
            'en' => [
                'v1_title' => 'KirpiCore API v1',
                'desc_token' => 'Issues bearer token with email+password',
                'desc_me' => 'Returns profile of token owner',
                'desc_users_list' => 'Lists users (users.view)',
                'desc_users_create' => 'Creates a user (users.create)',
                'desc_users_update' => 'Updates a user (users.edit)',
                'desc_users_status' => 'Updates active/passive status (users.status)',
                'desc_postman_download' => 'Downloads prepared Postman collection',
                'desc_postman_compat' => 'Compatibility endpoint for Postman collection',
                'metrics_pretitle' => 'System Management',
                'metrics_title' => 'API Metrics',
                'window_1h' => '1 Hour',
                'window_24h' => '24 Hours',
                'window_7d' => '7 Days',
                'table_missing' => 'api_request_logs table is not installed. Run Install Missing in settings.',
                'total' => 'Total',
                'token_unique' => 'Token (uniq)',
                'avg_ms' => 'Avg ms',
                'critical_codes' => 'Critical Codes',
                'unique_ip' => 'Unique IP',
                'top_endpoints' => 'Top Endpoints',
                'method' => 'Method',
                'path' => 'Path',
                'hit' => 'Hit',
                'error' => 'Error',
                'no_data' => 'No data',
                'recent_errors' => 'Recent Errors',
                'time' => 'Time',
                'status' => 'Status',
                'error_code' => 'Error Code',
                'ip' => 'IP',
                'no_error_log' => 'No error log',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
                'token_table_missing' => 'API token table is not ready. Complete setup first.',
                'missing_credentials' => 'email and password are required.',
                'invalid_email' => 'Enter a valid email address.',
                'invalid_credentials' => 'Credentials are invalid.',
                'user_inactive' => 'User is inactive.',
                'role_inactive' => 'User role is inactive.',
                'token_create_failed' => 'Token could not be created.',
                'token_created' => 'Token created.',
                'token_create_exception' => 'An error occurred while creating token.',
                'users_list_failed' => 'Users could not be listed.',
                'users_create_required' => 'name, email and password are required.',
                'password_min_6' => 'Password must be at least 6 characters.',
                'password_mismatch' => 'Password fields do not match.',
                'role_id_invalid' => 'role_id is invalid.',
                'email_exists' => 'This email is already registered.',
                'role_invalid' => 'Selected role is invalid.',
                'role_inactive_assign' => 'Inactive role cannot be assigned.',
                'user_created' => 'User created.',
                'user_create_failed' => 'User could not be created.',
                'method_not_allowed' => 'Method not supported for this endpoint.',
                'invalid_user_id' => 'Invalid user id.',
                'no_fields_to_update' => 'Send at least one field to update.',
                'name_empty' => 'name cannot be empty.',
                'email_used_elsewhere' => 'This email is used by another user.',
                'user_not_found' => 'User not found.',
                'super_admin_cannot_disable' => 'Super Admin user cannot be disabled.',
                'super_admin_min_one' => 'At least one active Super Admin must remain.',
                'no_updatable_field' => 'No updatable field found.',
                'user_updated' => 'User updated.',
                'user_update_failed' => 'User could not be updated.',
                'is_active_required' => 'is_active is required.',
                'user_status_updated' => 'User status updated.',
                'user_status_failed' => 'User status could not be updated.',
                'postman_not_found' => 'Postman collection file not found.',
                'postman_read_failed' => 'Postman collection file could not be read.',
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
