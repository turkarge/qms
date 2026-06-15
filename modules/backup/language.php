<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function backup_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                // Genel Başlıklar
                'system_management' => 'Sistem Yönetimi',
                'backup_restore' => 'Backup / Restore',
                'new_backup' => 'Yeni Backup',
                'recent_backups' => 'Son Backup Kayıtları',
                'recent_restores' => 'Son Restore Logları',

                // Form Alanları ve Etiketler
                'label' => 'Etiket',
                'label_placeholder' => 'örnek: deploy_öncesi',
                'create_backup' => 'Backup Oluştur',

                // Tablo Başlıkları
                'file' => 'Dosya',
                'size' => 'Boyut',
                'status' => 'Durum',
                'date' => 'Tarih',
                'created_by' => 'Oluşturan',
                'restored_by' => 'Restore Eden',
                'no_records' => 'Kayıt bulunamadı.',
                'created_at' => 'Oluşturulma',
                'updated_at' => 'Güncelleme',
                'restore_output' => 'Restore Çıktısı',
                'export_backups_csv' => 'Backup CSV',
                'export_backups_excel' => 'Backup Excel',
                'export_restores_excel' => 'Restore Excel',

                // Aksiyonlar
                'download' => 'İndir',
                'verify' => 'Doğrula',
                'restore' => 'Restore',
                'delete' => 'Sil',

                // Onay Mesajları
                'verify_confirm' => 'Bu backup dosyası checksum ve dry-run restore ile doğrulanacak. Emin misiniz?',
                'restore_confirm' => 'Bu backup geri yüklenecek. Emin misiniz?',
                'delete_confirm' => 'Bu backup kaydı silinecek. Emin misiniz?',

                // İşlem Durumu
                'working_create' => 'Backup oluşturuluyor. Bu işlem birkaç dakika sürebilir.',
                'working_verify' => 'Backup doğrulanıyor. Lütfen işlem tamamlanana kadar bekleyin.',
                'working_restore' => 'Backup geri yükleniyor. Bu işlem birkaç dakika sürebilir.',
                'working_delete' => 'Backup siliniyor. Lütfen bekleyin.',
                'working_default' => 'Backup işlemi yürütülüyor. Lütfen bekleyin.',
                'operation_failed' => 'Backup işlemi tamamlanamadı. Ayrıntılar için bildirimi kontrol edin.',

                // Hata ve Bilgilendirme Mesajları
                'backup_tables_missing' => 'Backup tabloları kurulu değil. Kurulum için setup veya db:install çalıştırın.',
                'table_not_ready' => 'Backup tablosu henüz kurulu değil.',
                'record_not_found' => 'Backup kaydı bulunamadı.',
                'invalid_backup_record' => 'Geçersiz backup kaydı.',
                'file_path_invalid' => 'Backup dosya yolu geçersiz.',
                'file_not_found' => 'Backup dosyası bulunamadı.',
                'download_error' => 'Backup indirilirken bir hata oluştu.',
                'create_failed_default' => 'Backup oluşturulamadı.',
                'delete_failed' => 'Backup silinirken bir hata oluştu.',
                'restore_failed_default' => 'Restore işlemi başarısız.',
                'verify_failed_default' => 'Backup doğrulama başarısız.',
                'csrf_failed' => 'Güvenlik doğrulaması başarısız oldu.',

                // Başarı Mesajları ve Log Önekleri
                'delete_success' => 'Backup kaydı silindi.',
                'restore_success' => 'Restore komutu çalıştırıldı.',
                'verify_success_default' => 'Backup doğrulandı.',
                'create_success_prefix' => 'Backup oluşturuldu. ID: ',
                'retention_deleted_prefix' => ' Retention temizliği: ',
                'retention_deleted_suffix' => ' eski backup silindi.',
                'checksum_prefix' => ' SHA256: ',
                'dry_run_prefix' => ' Dry-run tablo: ',
                'dry_run_suffix' => '.',
            ],
            'en' => [
                'system_management' => 'System Management',
                'backup_restore' => 'Backup / Restore',
                'backup_tables_missing' => 'Backup tables are not installed. Run setup or db:install.',
                'new_backup' => 'New Backup',
                'label' => 'Label',
                'label_placeholder' => 'example: before_deploy',
                'create_backup' => 'Create Backup',
                'recent_backups' => 'Recent Backups',
                'file' => 'File',
                'size' => 'Size',
                'status' => 'Status',
                'date' => 'Date',
                'created_by' => 'Created By',
                'no_records' => 'No records found.',
                'download' => 'Download',
                'verify' => 'Verify',
                'restore' => 'Restore',
                'delete' => 'Delete',
                'verify_confirm' => 'This backup will be validated with checksum and dry-run restore. Continue?',
                'restore_confirm' => 'This backup will be restored. Continue?',
                'delete_confirm' => 'This backup record will be deleted. Continue?',
                'working_create' => 'Backup is being created. This may take a few minutes.',
                'working_verify' => 'Backup is being verified. Please wait until the operation completes.',
                'working_restore' => 'Backup is being restored. This may take a few minutes.',
                'working_delete' => 'Backup is being deleted. Please wait.',
                'working_default' => 'A backup operation is in progress. Please wait.',
                'operation_failed' => 'The backup operation could not be completed. Check the notification for details.',
                'recent_restores' => 'Recent Restore Logs',
                'restored_by' => 'Restored By',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
                'restore_output' => 'Restore Output',
                'export_backups_csv' => 'Backup CSV',
                'export_backups_excel' => 'Backup Excel',
                'export_restores_excel' => 'Restore Excel',
                'invalid_backup_record' => 'Invalid backup record.',
                'table_not_ready' => 'Backup table is not installed yet.',
                'record_not_found' => 'Backup record not found.',
                'file_path_invalid' => 'Backup file path is invalid.',
                'file_not_found' => 'Backup file not found.',
                'download_error' => 'An error occurred while downloading backup.',
                'csrf_failed' => 'Security validation failed.',
                'create_failed_default' => 'Backup could not be created.',
                'delete_failed' => 'An error occurred while deleting backup.',
                'restore_failed_default' => 'Restore process failed.',
                'verify_failed_default' => 'Backup verification failed.',
                'delete_success' => 'Backup record deleted.',
                'restore_success' => 'Restore command executed.',
                'verify_success_default' => 'Backup verified.',
                'create_success_prefix' => 'Backup created. ID: ',
                'retention_deleted_prefix' => ' Retention cleanup: ',
                'retention_deleted_suffix' => ' old backups deleted.',
                'checksum_prefix' => ' SHA256: ',
                'dry_run_prefix' => ' Dry-run tables: ',
                'dry_run_suffix' => '.',
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
