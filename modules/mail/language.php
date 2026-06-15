<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function mail_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                'mail_center' => 'Mail Merkezi',
                'mail_test_status' => 'Mail Test ve Durum',
                'mail_configuration' => 'Mail Konfigürasyonu',
                'mail_templates' => 'Mail Şablonları',
                'manage_templates' => 'Şablonları Yönet',
                'back_to_mail_test' => 'Mail Teste Dön',

                'check' => 'Kontrol',
                'status' => 'Durum',
                'defined' => 'Tanımlı',
                'empty' => 'Boş',
                'invalid' => 'Geçersiz',
                'valid' => 'Geçerli',
                'ready' => 'Hazır',
                'missing' => 'Eksik',
                'sent' => 'Gönderildi',
                'failed' => 'Başarısız',
                'error' => 'Hata',
                'is_active' => 'Aktif',
                'is_system' => 'Sistem',
                'custom' => 'Özel',

                'send_test_email' => 'Test E-posta Gönder',
                'recipient_email' => 'Alıcı E-posta',
                'subject' => 'Konu',
                'message' => 'Mesaj',
                'default_subject' => 'Kirpi Core Test Maili',
                'default_message' => 'Bu mesaj Kirpi Core mail modülü testi için gönderilmiştir.',
                'send_test_button' => 'Test Maili Gönder',

                'recent_mail_logs' => 'Son Mail Logları',
                'date' => 'Tarih',
                'recipient' => 'Alıcı',
                'transport' => 'Transport',
                'no_mail_logs' => 'Henüz mail logu yok.',
                'filters' => 'Filtreler',
                'search' => 'Arama',
                'template_search_placeholder' => 'Key, ad, konu veya içerikte ara...',
                'filter' => 'Filtrele',
                'clear' => 'Temizle',
                'all_statuses' => 'Tüm Durumlar',
                'inactive' => 'Pasif',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
                'template_origin' => 'Kaynak',
                'created_at' => 'Oluşturulma',
                'updated_at' => 'Güncelleme',

                'new_template' => 'Yeni Şablon',
                'template_list' => 'Şablon Listesi',
                'template_key' => 'Şablon Key',
                'template_name' => 'Şablon Adı',
                'html_body' => 'HTML İçerik',
                'placeholders' => 'Placeholders',
                'save_template' => 'Şablonu Kaydet',
                'cancel' => 'İptal',
                'create_template' => 'Şablon Oluştur',
                'update_template' => 'Şablonu Güncelle',
                'delete_template' => 'Şablonu Sil',
                'templates_empty' => 'Henüz şablon kaydı yok.',
                'template_tables_missing' => 'mail_templates tablosu kurulu değil. Ayarlar > Eksikleri Kur çalıştırın.',
                'template_key_format' => 'Küçük harf/rakam ve ._- kullanın. Örnek: auth.password_reset',
                'template_vars_hint' => 'Kullanılabilir değişkenler: {{app_name}}, {{app_url}}, {{year}} ve şablona özel değişkenler.',

                'csrf_failed' => 'Güvenlik doğrulaması başarısız oldu.',
                'required_fields' => 'Alıcı e-posta, konu ve mesaj zorunludur.',
                'send_failed_default' => 'Test maili gönderilemedi.',
                'send_success_default' => 'Test maili gönderildi.',
                'template_required' => 'Şablon alanları zorunludur.',
                'template_key_invalid' => 'Şablon key formatı geçersiz.',
                'template_created' => 'Şablon oluşturuldu.',
                'template_updated' => 'Şablon güncellendi.',
                'template_deleted' => 'Şablon silindi.',
                'template_not_found' => 'Şablon bulunamadı.',
                'template_delete_blocked' => 'Sistem şablonları silinemez.',
                'template_save_error' => 'Şablon kaydedilirken bir hata oluştu.',
                'template_duplicate_key' => 'Bu şablon key zaten kullanılıyor.',
            ],
            'en' => [
                'mail_center' => 'Mail Center',
                'mail_test_status' => 'Mail Test and Status',
                'mail_configuration' => 'Mail Configuration',
                'mail_templates' => 'Mail Templates',
                'manage_templates' => 'Manage Templates',
                'back_to_mail_test' => 'Back to Mail Test',

                'check' => 'Check',
                'status' => 'Status',
                'defined' => 'Defined',
                'empty' => 'Empty',
                'invalid' => 'Invalid',
                'valid' => 'Valid',
                'ready' => 'Ready',
                'missing' => 'Missing',
                'sent' => 'Sent',
                'failed' => 'Failed',
                'error' => 'Error',
                'is_active' => 'Active',
                'is_system' => 'System',
                'custom' => 'Custom',

                'send_test_email' => 'Send Test Email',
                'recipient_email' => 'Recipient Email',
                'subject' => 'Subject',
                'message' => 'Message',
                'default_subject' => 'Kirpi Core Test Email',
                'default_message' => 'This message was sent for Kirpi Core mail module testing.',
                'send_test_button' => 'Send Test Email',

                'recent_mail_logs' => 'Recent Mail Logs',
                'date' => 'Date',
                'recipient' => 'Recipient',
                'transport' => 'Transport',
                'no_mail_logs' => 'No mail logs yet.',
                'filters' => 'Filters',
                'search' => 'Search',
                'template_search_placeholder' => 'Search key, name, subject, or content...',
                'filter' => 'Filter',
                'clear' => 'Clear',
                'all_statuses' => 'All Statuses',
                'inactive' => 'Inactive',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
                'template_origin' => 'Origin',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',

                'new_template' => 'New Template',
                'template_list' => 'Template List',
                'template_key' => 'Template Key',
                'template_name' => 'Template Name',
                'html_body' => 'HTML Content',
                'placeholders' => 'Placeholders',
                'save_template' => 'Save Template',
                'cancel' => 'Cancel',
                'create_template' => 'Create Template',
                'update_template' => 'Update Template',
                'delete_template' => 'Delete Template',
                'templates_empty' => 'No templates yet.',
                'template_tables_missing' => 'mail_templates table is not installed. Run Settings > Install Missing.',
                'template_key_format' => 'Use lowercase/alphanumeric and ._-. Example: auth.password_reset',
                'template_vars_hint' => 'Available variables: {{app_name}}, {{app_url}}, {{year}} and template-specific variables.',

                'csrf_failed' => 'Security validation failed.',
                'required_fields' => 'Recipient email, subject and message are required.',
                'send_failed_default' => 'Test email could not be sent.',
                'send_success_default' => 'Test email sent.',
                'template_required' => 'Template fields are required.',
                'template_key_invalid' => 'Template key format is invalid.',
                'template_created' => 'Template created.',
                'template_updated' => 'Template updated.',
                'template_deleted' => 'Template deleted.',
                'template_not_found' => 'Template not found.',
                'template_delete_blocked' => 'System templates cannot be deleted.',
                'template_save_error' => 'An error occurred while saving template.',
                'template_duplicate_key' => 'This template key is already in use.',
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
