<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function profile_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                // Hata ve Durum Mesajları
                'forbidden_title' => '403 - Yetkisiz Erişim',
                'forbidden_message' => 'Profil bilgilerine erişilemedi.',
                'load_error_title' => '500 - Profil Yüklenemedi',
                'load_error_message' => 'Profil verileri yüklenirken bir hata oluştu.',
                'no_role' => 'Rol Yok',
                'active' => 'Aktif',
                'passive' => 'Pasif',

                // Profil Yönetimi
                'my_account' => 'Hesabım',
                'profile' => 'Profil',
                'nav_user_menu' => 'Kullanıcı Menüsü',
                'user_fallback' => 'Kullanıcı',
                'profile_info' => 'Profil Bilgileri',
                'name_surname' => 'Ad Soyad',
                'email' => 'E-posta',
                'new_password' => 'Yeni Şifre',
                'new_password_repeat' => 'Yeni Şifre Tekrar',
                'password_placeholder' => 'Boş bırakılırsa değişmez',
                'password_hint' => 'Şifre değiştirmek istemiyorsanız boş bırakın.',
                'avatar' => 'Profil Görseli',
                'avatar_hint' => 'JPG, PNG veya WEBP. Maksimum 2 MB.',
                'update_profile' => 'Profili Güncelle',

                // Oturum Kilitleme (Lock) Ayarları
                'lock_key_title' => 'Oturum Kilitleme Key',
                'lock_enabled' => 'Oturum kilitleme aktif',
                'lock_hint' => "Navbar'daki user-key ikonu ile ekranı kilitleyebilirsiniz.",
                'new_key' => 'Yeni Key (4 hane)',
                'key_repeat' => 'Key Tekrar',
                'key_placeholder' => 'Örnek: 1234',
                'save_key' => 'Key Ayarını Kaydet',

                // API Token Yönetimi (Super Admin)
                'api_tokens' => 'API Tokenleri',
                'api_token_management' => 'API Token Yönetimi (Super Admin)',
                'token_once_warning' => 'Bu token sadece bir kez gösterilir. Güvenli bir yerde saklayın.',
                'token' => 'Token',
                'token_name' => 'Token Name',
                'expires_at' => 'Expires At',
                'unlimited' => 'Sınırsız',
                'scopes' => 'Scopes',
                'validity' => 'Geçerlilik',
                'scope' => 'Scope',
                'all_permissions' => 'Tüm Yetki (*)',
                'profile_only' => 'Sadece Profil',
                'users_read' => 'Users Read',
                'users_manage' => 'Users Manage',
                'create_api_token' => 'API Token Oluştur',

                // API Token Tablo Başlıkları
                'created' => 'Created',
                'last_used' => 'Last Used',
                'expires' => 'Expires',
                'status' => 'Status',
                'revoked' => 'Revoked',
                'expired' => 'Expired',
                'active_en' => 'Active',

                // Aksiyonlar ve Kopyalama
                'copy' => 'Kopyala',
                'copy_title' => 'Kopyala',
                'revoke' => 'Revoke',
                'revoke_confirm' => 'Bu API token iptal edilecek. Emin misiniz?',
                'copy_disabled_title' => 'Güvenlik nedeniyle sadece bu oturumda oluşturulan tokenlar kopyalanabilir',
                'copy_not_allowed' => 'Bu token bu oturumda kopyalanamaz.',
                'copy_success' => 'Token panoya kopyalandı.',
                'copy_error' => 'Token kopyalanamadı.',

                // Hata ve Başarı Bildirimleri (Profil & Ayarlar)
                'csrf_failed' => 'Güvenlik doğrulaması başarısız oldu.',
                'invalid_session' => 'Geçersiz kullanıcı oturumu.',
                'required_fields' => 'Ad soyad ve e-posta alanları zorunludur.',
                'invalid_email' => 'Geçerli bir e-posta adresi girin.',
                'password_min' => 'Yeni şifre en az 6 karakter olmalıdır.',
                'password_mismatch' => 'Yeni şifreler uyuşmuyor.',
                'user_not_found' => 'Kullanıcı bulunamadı.',
                'email_in_use' => 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.',
                'profile_updated' => 'Profil başarıyla güncellendi.',
                'profile_update_error' => 'Profil güncellenirken bir hata oluştu.',
                'valid_session_required' => 'Geçerli kullanıcı oturumu bulunamadı.',
                'lock_infra_missing' => 'Oturum kilitleme altyapısı hazır değil. Ayarlar > Eksikleri Kur çalıştırın.',
                'key_format_error' => 'Key sadece rakam olmalı ve 4 hane olmalıdır.',
                'key_repeat_error' => 'Key tekrar alanı uyuşmuyor.',
                'key_required_for_enable' => 'Oturum kilitlemeyi açmak için önce bir key tanımlamalısınız.',
                'lock_settings_updated' => 'Oturum kilitleme ayarları güncellendi.',
                'settings_update_error' => 'Ayarlar güncellenirken bir hata oluştu.',

                // Hata ve Başarı Bildirimleri (API Token)
                'api_disabled_warning' => 'API şu an Ayarlar ekranından kapatıldı.',
                'api_table_warning' => '`api_tokens` tablosu hazır değil. Ayarlar > Eksikleri Kur çalıştırın.',
                'no_tokens' => 'API token kaydı yok.',
                'super_admin_only_create' => 'Sadece Super Admin API token oluşturabilir.',
                'api_disabled_token' => 'API devre dışı olduğu için token oluşturulamadı.',
                'api_table_not_ready' => 'API token tablosu hazır değil. Önce Eksikleri Kur çalıştırın.',
                'token_create_failed' => 'API token oluşturulamadı.',
                'token_created_once' => 'API token oluşturuldu. Profil sayfasında bir kez gösterilecek.',
                'token_create_error' => 'API token oluşturulurken bir hata oluştu.',
                'super_admin_only_manage' => 'Sadece Super Admin API token yönetebilir.',
                'invalid_token_record' => 'Geçersiz token kaydı.',
                'token_table_not_ready' => 'API token tablosu hazır değil.',
                'token_not_found_or_revoked' => 'Token bulunamadı veya zaten iptal edilmiş.',
                'token_revoked' => 'API token iptal edildi.',
                'token_revoke_error' => 'Token iptal edilirken bir hata oluştu.',
            ],
            'en' => [
                'forbidden_title' => '403 - Unauthorized Access',
                'forbidden_message' => 'Profile information could not be accessed.',
                'load_error_title' => '500 - Profile Load Failed',
                'load_error_message' => 'An error occurred while loading profile data.',
                'no_role' => 'No Role',
                'active' => 'Active',
                'passive' => 'Passive',
                'my_account' => 'My Account',
                'profile' => 'Profile',
                'nav_user_menu' => 'User Menu',
                'user_fallback' => 'User',
                'profile_info' => 'Profile Information',
                'api_tokens' => 'API Tokens',
                'name_surname' => 'Full Name',
                'email' => 'Email',
                'new_password' => 'New Password',
                'new_password_repeat' => 'Repeat New Password',
                'password_placeholder' => 'Leave blank to keep unchanged',
                'password_hint' => 'Leave blank if you do not want to change password.',
                'avatar' => 'Profile Image',
                'avatar_hint' => 'JPG, PNG or WEBP. Maximum 2 MB.',
                'update_profile' => 'Update Profile',
                'api_token_management' => 'API Token Management (Super Admin)',
                'token_once_warning' => 'This token is shown only once. Store it securely.',
                'token' => 'Token',
                'copy' => 'Copy',
                'copy_title' => 'Copy',
                'token_name' => 'Token Name',
                'expires_at' => 'Expires At',
                'unlimited' => 'Unlimited',
                'scopes' => 'Scopes',
                'validity' => 'Validity',
                'scope' => 'Scope',
                'all_permissions' => 'Full Access (*)',
                'profile_only' => 'Profile Only',
                'users_read' => 'Users Read',
                'users_manage' => 'Users Manage',
                'create_api_token' => 'Create API Token',
                'api_disabled_warning' => 'API is currently disabled from Settings.',
                'api_table_warning' => '`api_tokens` table is not ready. Run Settings > Install Missing.',
                'no_tokens' => 'No API tokens found.',
                'created' => 'Created',
                'last_used' => 'Last Used',
                'expires' => 'Expires',
                'status' => 'Status',
                'revoked' => 'Revoked',
                'expired' => 'Expired',
                'active_en' => 'Active',
                'copy_disabled_title' => 'For security, only tokens created in this session can be copied',
                'revoke_confirm' => 'This API token will be revoked. Continue?',
                'revoke' => 'Revoke',
                'lock_key_title' => 'Session Lock Key',
                'lock_enabled' => 'Session lock enabled',
                'lock_hint' => 'You can lock the screen with the user-key icon in navbar.',
                'new_key' => 'New Key (4 digits)',
                'key_repeat' => 'Repeat Key',
                'key_placeholder' => 'Example: 1234',
                'save_key' => 'Save Key Settings',
                'copy_not_allowed' => 'This token cannot be copied in this session.',
                'copy_success' => 'Token copied to clipboard.',
                'copy_error' => 'Token could not be copied.',
                'csrf_failed' => 'Security validation failed.',
                'invalid_session' => 'Invalid user session.',
                'required_fields' => 'Full name and email are required.',
                'invalid_email' => 'Enter a valid email address.',
                'password_min' => 'New password must be at least 6 characters.',
                'password_mismatch' => 'New passwords do not match.',
                'user_not_found' => 'User not found.',
                'email_in_use' => 'This email is already used by another user.',
                'profile_updated' => 'Profile updated successfully.',
                'profile_update_error' => 'An error occurred while updating profile.',
                'valid_session_required' => 'Valid user session not found.',
                'lock_infra_missing' => 'Session lock infrastructure is not ready. Run Settings > Install Missing.',
                'key_format_error' => 'Key must be numeric and exactly 4 digits.',
                'key_repeat_error' => 'Key repeat does not match.',
                'key_required_for_enable' => 'You must define a key before enabling session lock.',
                'lock_settings_updated' => 'Session lock settings updated.',
                'settings_update_error' => 'An error occurred while updating settings.',
                'super_admin_only_create' => 'Only Super Admin can create API tokens.',
                'api_disabled_token' => 'API is disabled, token could not be created.',
                'api_table_not_ready' => 'API token table is not ready. Run Install Missing first.',
                'token_create_failed' => 'API token could not be created.',
                'token_created_once' => 'API token created. It will be shown once on profile page.',
                'token_create_error' => 'An error occurred while creating API token.',
                'super_admin_only_manage' => 'Only Super Admin can manage API tokens.',
                'invalid_token_record' => 'Invalid token record.',
                'token_table_not_ready' => 'API token table is not ready.',
                'token_not_found_or_revoked' => 'Token not found or already revoked.',
                'token_revoked' => 'API token revoked.',
                'token_revoke_error' => 'An error occurred while revoking token.',
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

