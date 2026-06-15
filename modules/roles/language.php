<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function roles_lang(string $key, ?string $default = null): string
{
    static $dictionary = null;

    if ($dictionary === null) {
        $dictionary = [
            'tr' => [
                'system_management' => 'Sistem Yönetimi',
                'role_management' => 'Rol Yönetimi',
                'roles' => 'Roller',
                'new_role' => 'Yeni Rol',
                'edit_role' => 'Rol Düzenle',
                'search_placeholder' => 'Rol adı ara...',
                'all_statuses' => 'Tüm Durumlar',
                'active' => 'Aktif',
                'inactive' => 'Pasif',
                'csv_export' => 'CSV',
                'excel_export' => 'Excel',
                'permission_catalog_export' => 'Yetki Kataloğu',
                'permission_matrix_export' => 'Yetki Matrisi',
                'role_name' => 'Rol Adı',
                'role_name_hint' => 'Örnek: İçerik Editörü, Operasyon, Super Admin',
                'role_active_switch' => 'Rol aktif olsun',
                'super_admin_name_hint' => 'Super Admin rol adı sistem davranışı için korunmaktadır.',
                'table_role' => 'Rol',
                'table_status' => 'Durum',
                'table_user_count' => 'Kullanıcı Sayısı',
                'table_permission_count' => 'İzin Sayısı',
                'created_at' => 'Oluşturulma',
                'updated_at' => 'Güncelleme',
                'no_records' => 'Kayıt bulunamadı.',
                'permissions' => 'İzinler',
                'permission_matrix' => 'İzin Matrisi',
                'role_label' => 'Rol',
                'module' => 'Modül',
                'module_title' => 'Modül Başlığı',
                'module_permissions' => 'İzinler',
                'permission_slug' => 'İzin Slug',
                'permission_name' => 'İzin Adı',
                'assigned' => 'Atanmış',
                'yes' => 'Evet',
                'no' => 'Hayır',
                'select_all' => 'Tümünü Seç',
                'clear_all' => 'Tümünü Kaldır',
                'select_group_all' => 'Tümünü seç',
                'super_admin_permissions_info' => 'Super Admin rolü tüm yetkilere sahiptir. Bu rol için izin ataması yapılamaz.',
                'save' => 'Kaydet',
                'update' => 'Güncelle',
                'cancel' => 'İptal',
                'back' => 'Geri Dön',
                'edit' => 'Düzenle',
                'invalid_role_id' => 'Geçersiz rol ID.',
                'role_data_load_error' => 'Rol verileri yüklenemedi.',
                'table_load_error' => 'Rol listesi yüklenirken bir hata oluştu.',
                'table_not_ready' => 'Rol tablosu henüz kurulu değil.',
                'permission_denied' => 'Bu işlem için yetkiniz yok.',
                'permission_tables_missing' => 'Permission tabloları henüz kurulu değil. Önce database/permissions.sql dosyasını çalıştırın veya php shell.php db:permissions:install komutunu kullanın.',
                'page_not_found_title' => '404 - Rol Bulunamadı',
                'page_not_found_message' => 'Geçersiz rol ID.',
                'page_error_title' => '500 - Rol Verileri Yüklenemedi',
                'page_error_message' => 'Rol yetkileri yüklenirken bir hata oluştu.',
            ],
            'en' => [
                'system_management' => 'System Management',
                'roles' => 'Roles',
                'new_role' => 'New Role',
                'search_placeholder' => 'Search role name...',
                'all_statuses' => 'All Statuses',
                'active' => 'Active',
                'inactive' => 'Inactive',
                'csv_export' => 'CSV',
                'excel_export' => 'Excel',
                'permission_catalog_export' => 'Permission Catalog',
                'permission_matrix_export' => 'Permission Matrix',
                'edit_role' => 'Edit Role',
                'invalid_role_id' => 'Invalid role ID.',
                'role_data_load_error' => 'Role data could not be loaded.',
                'role_name' => 'Role Name',
                'role_name_hint' => 'Example: Content Editor, Operations, Super Admin',
                'role_active_switch' => 'Set role active',
                'cancel' => 'Cancel',
                'save' => 'Save',
                'update' => 'Update',
                'super_admin_name_hint' => 'Super Admin role name is reserved for core behavior.',
                'table_role' => 'Role',
                'table_status' => 'Status',
                'table_user_count' => 'User Count',
                'table_permission_count' => 'Permission Count',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
                'no_records' => 'No records found.',
                'permissions' => 'Permissions',
                'edit' => 'Edit',
                'role_management' => 'Role Management',
                'permission_matrix' => 'Permission Matrix',
                'role_label' => 'Role',
                'back' => 'Back',
                'select_all' => 'Select All',
                'clear_all' => 'Clear All',
                'permission_tables_missing' => 'Permission tables are not installed yet. Run database/permissions.sql or use php shell.php db:permissions:install first.',
                'super_admin_permissions_info' => 'Super Admin has all permissions by default. Permission assignment is disabled for this role.',
                'module' => 'Module',
                'module_title' => 'Module Title',
                'module_permissions' => 'Permissions',
                'permission_slug' => 'Permission Slug',
                'permission_name' => 'Permission Name',
                'assigned' => 'Assigned',
                'yes' => 'Yes',
                'no' => 'No',
                'select_group_all' => 'Select all',
                'page_not_found_title' => '404 - Role Not Found',
                'page_not_found_message' => 'Invalid role ID.',
                'page_error_title' => '500 - Role Data Load Failed',
                'page_error_message' => 'An error occurred while loading role permissions.',
                'table_load_error' => 'An error occurred while loading the role list.',
                'table_not_ready' => 'Role table is not installed yet.',
                'permission_denied' => 'You do not have permission for this action.',
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
