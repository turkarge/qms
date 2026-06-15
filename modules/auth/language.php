<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function auth_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                // Giriş Sayfası
                'login_title' => 'Giriş Yap',
                'login_heading' => 'Hesabınıza giriş yapın',
                'email' => 'E-posta adresi',
                'email_placeholder' => 'ornek@alanadi.com',
                'password' => 'Şifre',
                'forgot_password' => 'Şifremi unuttum',
                'password_placeholder' => 'Şifreniz',
                'show_password' => 'Şifreyi göster',
                'remember_me' => 'Bu cihazda beni hatırla',
                'login_button' => 'Giriş Yap',
                'login_other_account' => 'Farklı hesap ile giriş yap',
                'terms_accept_prefix' => 'Giriş yaparak',
                'terms_accept_link' => 'kullanım şartlarını',
                'terms_accept_suffix' => 'kabul etmiş olursunuz.',

                // Şifremi Unuttum
                'forgot_title' => 'Şifremi Unuttum',
                'forgot_heading' => 'Şifrenizi mi unuttunuz?',
                'forgot_description' => 'E-posta adresinizi girin. Şifre sıfırlama sürecini sonraki adımda bağlayacağız.',
                'forgot_send' => 'Sıfırlama Bağlantısı Gönder',
                'forgot_email_sent' => 'Eğer e-posta kayıtlıysa şifre sıfırlama bağlantısı gönderildi.',
                'forgot_email_send_error' => 'Şifre sıfırlama e-postası gönderilemedi. Mail ayarlarını kontrol edin.',
                'forgot_table_missing' => 'Şifre sıfırlama altyapısı hazır değil. Ayarlar > Eksikleri Kur çalıştırın.',
                'back_to_login' => 'Giriş ekranına dön',
                'reset_title' => 'Şifre Sıfırla',
                'reset_heading' => 'Yeni şifre belirleyin',
                'reset_token_missing' => 'Sıfırlama bağlantısı eksik veya geçersiz.',
                'reset_token_invalid' => 'Sıfırlama bağlantısı geçersiz veya süresi dolmuş.',
                'reset_password' => 'Yeni Şifre',
                'reset_password_confirm' => 'Yeni Şifre Tekrar',
                'reset_submit' => 'Şifreyi Güncelle',
                'reset_success' => 'Şifreniz güncellendi. Giriş yapabilirsiniz.',
                'reset_error' => 'Şifre sıfırlanırken bir hata oluştu.',
                'password_min_6' => 'Şifre en az 6 karakter olmalıdır.',
                'password_mismatch' => 'Şifre alanları uyuşmuyor.',

                // Kullanım Şartları
                'terms_title' => 'Kullanım Şartları',
                'back_to_login_button' => 'Girişe Dön',
                'terms_h1' => '1. Genel Hükümler',
                'terms_p1' => 'Bu uygulamayı kullanan tüm kullanıcılar, sistemin güvenli ve yetkili kullanımından sorumludur.',
                'terms_h2' => '2. Hesap Güvenliği',
                'terms_p2' => 'Kullanıcılar, oturum bilgilerini korumakla yükümlüdür. Yetkisiz erişim şüphesi halinde sistem yöneticisine bilgi verilmelidir.',
                'terms_h3' => '3. Veri Kullanımı',
                'terms_p3' => 'Sistem üzerinde oluşturulan, görüntülenen veya işlenen tüm veriler kurum politikalarına ve ilgili mevzuata uygun şekilde kullanılmalıdır.',
                'terms_h4' => '4. Son Hüküm',
                'terms_p4' => 'Bu metin başlangıç sürümüdür. Nihai kullanım şartları daha sonra uygulamaya özel şekilde genişletilebilir.',

                // Oturum Kilidi (Lock)
                'lock_title' => 'Oturum Kilidi',
                'lock_info' => 'Devam etmek için 4 haneli PIN kodunuzu girin.',
                'lock_key_label' => '4 haneli PIN',
                'lock_pin_digit' => 'PIN hanesi :digit',
                'unlock_button' => 'Kilidi Aç',
                'nav_lock_session' => 'Oturumu Kilitle',
                'nav_logout' => 'Cikis',

                // Hata ve Bilgilendirme Mesajları
                'csrf_failed' => 'Güvenlik doğrulaması başarısız oldu.',
                'csrf_failed_refresh' => 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.',
                'email_password_required' => 'E-posta ve şifre alanları zorunludur.',
                'invalid_email' => 'Geçerli bir e-posta adresi girin.',
                'invalid_credentials' => 'E-posta veya şifre hatalı.',
                'role_inactive' => 'Bu kullanıcıya bağlı rol pasif durumda.',
                'login_success_redirect' => 'Giriş başarılı. Yönlendiriliyorsunuz.',
                'login_error' => 'Giriş işlemi sırasında bir hata oluştu.',
                'logout_success' => 'Oturum kapatıldı.',
                'invalid_session' => 'Geçerli bir oturum bulunamadı.',
                'session_already_open' => 'Oturum zaten açık.',
                'session_locked' => 'Oturum kilitlendi.',

                // Lock (Kilitleme) Hata ve İşlemleri
                'lock_infra_missing' => 'Oturum kilitleme altyapısı hazır değil. Ayarlar > Eksikleri Kur çalıştırın.',
                'lock_infra_not_ready' => 'Oturum kilitleme altyapısı hazır değil.',
                'lock_not_active' => 'Oturum kilitleme aktif değil. Profilinizden 4 haneli key tanımlayın.',
                'lock_error' => 'Oturum kilitlenirken bir hata oluştu.',
                'lock_disabled_session_opened' => 'Kilitleme ayarı devre dışı. Oturum açıldı.',
                'lock_opened' => 'Oturum kilidi açıldı.',
                'unlock_error' => 'Oturum kilidi açılırken bir hata oluştu.',
                'key_must_be_4_digits' => 'Key 4 haneli sayısal olmalıdır.',
                'key_wrong' => 'Key hatalı.',
            ],
            'en' => [
                'login_title' => 'Sign In',
                'login_heading' => 'Sign in to your account',
                'email' => 'Email address',
                'email_placeholder' => 'example@domain.com',
                'password' => 'Password',
                'forgot_password' => 'Forgot password',
                'password_placeholder' => 'Your password',
                'show_password' => 'Show password',
                'remember_me' => 'Remember me on this device',
                'login_button' => 'Sign In',
                'terms_accept_prefix' => 'By signing in you accept',
                'terms_accept_link' => 'the terms of use',
                'terms_accept_suffix' => '.',
                'lock_title' => 'Session Lock',
                'lock_info' => 'Enter your 4-digit PIN to continue.',
                'lock_key_label' => '4-digit PIN',
                'lock_pin_digit' => 'PIN digit :digit',
                'unlock_button' => 'Unlock',
                'nav_lock_session' => 'Lock Session',
                'nav_logout' => 'Logout',
                'login_other_account' => 'Sign in with another account',
                'forgot_title' => 'Forgot Password',
                'forgot_heading' => 'Forgot your password?',
                'forgot_description' => 'Enter your email address. We will start password reset in the next step.',
                'forgot_send' => 'Send Reset Link',
                'forgot_email_sent' => 'If the email exists, a reset link has been sent.',
                'forgot_email_send_error' => 'Reset email could not be sent. Check mail configuration.',
                'forgot_table_missing' => 'Password reset infrastructure is not ready. Run Settings > Install Missing.',
                'back_to_login' => 'Back to login',
                'reset_title' => 'Reset Password',
                'reset_heading' => 'Set a new password',
                'reset_token_missing' => 'Reset link is missing or invalid.',
                'reset_token_invalid' => 'Reset link is invalid or expired.',
                'reset_password' => 'New Password',
                'reset_password_confirm' => 'Repeat New Password',
                'reset_submit' => 'Update Password',
                'reset_success' => 'Your password has been updated. You can sign in now.',
                'reset_error' => 'An error occurred while resetting password.',
                'password_min_6' => 'Password must be at least 6 characters.',
                'password_mismatch' => 'Password fields do not match.',
                'terms_title' => 'Terms of Use',
                'back_to_login_button' => 'Back to Login',
                'terms_h1' => '1. General Terms',
                'terms_p1' => 'All users are responsible for secure and authorized system use.',
                'terms_h2' => '2. Account Security',
                'terms_p2' => 'Users must protect session credentials and report suspicious access.',
                'terms_h3' => '3. Data Usage',
                'terms_p3' => 'All data must be used in compliance with policies and regulations.',
                'terms_h4' => '4. Final Provision',
                'terms_p4' => 'This is an initial draft and can be expanded for application needs.',
                'csrf_failed_refresh' => 'Security validation failed. Refresh and try again.',
                'email_password_required' => 'Email and password are required.',
                'invalid_email' => 'Enter a valid email address.',
                'invalid_credentials' => 'Email or password is incorrect.',
                'role_inactive' => 'The role assigned to this user is inactive.',
                'login_success_redirect' => 'Login successful. Redirecting.',
                'login_error' => 'An error occurred during login.',
                'csrf_failed' => 'Security validation failed.',
                'logout_success' => 'Session ended.',
                'invalid_session' => 'No valid session found.',
                'lock_infra_missing' => 'Session lock infrastructure is not ready. Run Settings > Install Missing.',
                'lock_not_active' => 'Session lock is not active. Set a 4-digit key in profile.',
                'session_locked' => 'Session locked.',
                'lock_error' => 'An error occurred while locking session.',
                'session_already_open' => 'Session is already open.',
                'key_must_be_4_digits' => 'Key must be a 4-digit number.',
                'lock_infra_not_ready' => 'Session lock infrastructure is not ready.',
                'lock_disabled_session_opened' => 'Lock setting is disabled. Session opened.',
                'key_wrong' => 'Key is incorrect.',
                'lock_opened' => 'Session lock opened.',
                'unlock_error' => 'An error occurred while unlocking session.',
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

