<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function notifications_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                'communication_center' => 'İletişim Merkezi',
                'notifications' => 'Bildirimler',
                'settings' => 'Ayarlar',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
                'search_placeholder' => 'Başlık veya mesaj ara...',
                'all_statuses' => 'Tüm Durumlar',
                'all_sources' => 'Tüm Kaynaklar',
                'all_templates' => 'Tüm Şablonlar',
                'status_unread' => 'Okunmadı',
                'status_read' => 'Okundu',
                'table_notification' => 'Bildirim',
                'table_source' => 'Kaynak',
                'table_channel' => 'Kanal',
                'table_status' => 'Durum',
                'table_date' => 'Tarih',
                'no_records' => 'Kayıt bulunamadı.',
                'mark_read' => 'Okundu Yap',
                'mark_all_read' => 'Tümünü Okundu Yap',
                'settings_center' => 'Bildirim Merkezi',
                'settings_title' => 'Bildirim Ayarları',
                'back_to_list' => 'Listeye Dön',
                'email_enabled' => 'E-posta bildirimleri açık olsun',
                'in_app_enabled' => 'Uygulama içi bildirimler açık olsun',
                'save_settings' => 'Ayarları Kaydet',
                'default_channel' => 'in_app',
                'tables_missing' => 'Bildirim tabloları henüz kurulu değil. Önce modules/notifications/database/schema.sql dosyasını çalıştırın.',
                'table_missing_short' => 'Bildirim tablosu henüz kurulu değil.',
                'table_waiting' => 'Bildirim tablosu hazır olduğunda liste burada görünecek.',
                'settings_table_missing' => 'Bildirim ayarları tablosu henüz kurulu değil. Önce database/notifications.sql dosyasını çalıştırın.',
                'table_not_ready' => 'Bildirim tablosu henüz kurulu değil.',
                'settings_table_not_ready' => 'Bildirim ayarları tablosu henüz kurulu değil.',
                'csrf_failed' => 'Güvenlik doğrulaması başarısız oldu.',
                'invalid_request' => 'Geçersiz istek.',
                'invalid_session' => 'Geçersiz kullanıcı oturumu.',
                'mark_read_success' => 'Bildirim okundu olarak işaretlendi.',
                'mark_read_error' => 'Bildirim güncellenirken bir hata oluştu.',
                'mark_all_read_success' => 'Tüm bildirimler okundu olarak işaretlendi.',
                'mark_all_read_error' => 'Bildirimler güncellenirken bir hata oluştu.',
                'settings_update_success' => 'Bildirim ayarları başarıyla güncellendi.',
                'settings_update_error' => 'Bildirim ayarları güncellenirken bir hata oluştu.',
                'list_load_error' => 'Bildirim listesi yüklenirken bir hata oluştu.',
                'nav_bell_aria' => 'Bildirimleri göster',
                'nav_new_badge' => 'Yeni',
                'nav_empty' => 'Henüz bildiriminiz bulunmuyor.',
                'nav_view_all' => 'Tüm bildirimleri gör',
                'nav_mark_read' => 'Okundu olarak işaretle',
                'nav_mark_all_read' => 'Tümünü okundu yap',
                'nav_unread_count' => 'okunmamış bildirim',
                'nav_open_notification' => 'Bildirimi aç',
            ],
            'en' => [
                'communication_center' => 'Communication Center',
                'notifications' => 'Notifications',
                'settings' => 'Settings',
                'export_csv' => 'CSV',
                'export_excel' => 'Excel',
                'mark_all_read' => 'Mark All as Read',
                'tables_missing' => 'Notification tables are not installed yet. Run modules/notifications/database/schema.sql first.',
                'search_placeholder' => 'Search title or message...',
                'all_statuses' => 'All Statuses',
                'all_sources' => 'All Sources',
                'all_templates' => 'All Templates',
                'status_unread' => 'Unread',
                'status_read' => 'Read',
                'table_waiting' => 'The list will appear here once the notifications table is ready.',
                'settings_center' => 'Notification Center',
                'settings_title' => 'Notification Settings',
                'back_to_list' => 'Back to List',
                'settings_table_missing' => 'Notification settings table is not installed yet. Run database/notifications.sql first.',
                'email_enabled' => 'Enable email notifications',
                'in_app_enabled' => 'Enable in-app notifications',
                'save_settings' => 'Save Settings',
                'table_missing_short' => 'Notification table is not installed yet.',
                'table_notification' => 'Notification',
                'table_source' => 'Source',
                'table_channel' => 'Channel',
                'table_status' => 'Status',
                'table_date' => 'Date',
                'no_records' => 'No records found.',
                'default_channel' => 'in_app',
                'mark_read' => 'Mark as Read',
                'csrf_failed' => 'Security validation failed.',
                'invalid_request' => 'Invalid request.',
                'invalid_session' => 'Invalid user session.',
                'table_not_ready' => 'Notification table is not installed yet.',
                'settings_table_not_ready' => 'Notification settings table is not installed yet.',
                'mark_read_success' => 'Notification marked as read.',
                'mark_read_error' => 'An error occurred while updating notification.',
                'mark_all_read_success' => 'All notifications marked as read.',
                'mark_all_read_error' => 'An error occurred while updating notifications.',
                'settings_update_success' => 'Notification settings updated successfully.',
                'settings_update_error' => 'An error occurred while updating notification settings.',
                'list_load_error' => 'An error occurred while loading notifications list.',
                'nav_bell_aria' => 'Show notifications',
                'nav_new_badge' => 'New',
                'nav_empty' => 'You have no notifications yet.',
                'nav_view_all' => 'View all notifications',
                'nav_mark_read' => 'Mark as read',
                'nav_mark_all_read' => 'Mark all as read',
                'nav_unread_count' => 'unread notifications',
                'nav_open_notification' => 'Open notification',
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
