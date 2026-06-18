<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function organization_lang(string $key, ?string $default = null): string
{
    static $dictionary = [
        'tr' => [
            'organization' => 'Organizasyon',
            'organization_hint' => 'Şirket, tesis, konum, departman, ekip, pozisyon ve kullanıcı kapsamlarını yönetin.',
            'companies' => 'Şirketler', 'units' => 'Organizasyon Birimleri', 'positions' => 'Pozisyonlar', 'assignments' => 'Kullanıcı Atamaları',
            'new_company' => 'Yeni Şirket', 'new_unit' => 'Yeni Birim', 'new_position' => 'Yeni Pozisyon', 'new_assignment' => 'Yeni Atama',
            'edit_company' => 'Şirketi Düzenle', 'edit_unit' => 'Birimi Düzenle', 'edit_position' => 'Pozisyonu Düzenle', 'edit_assignment' => 'Atamayı Düzenle',
            'company' => 'Şirket', 'company_code' => 'Şirket Kodu', 'company_name' => 'Şirket Adı', 'legal_name' => 'Ticari Unvan', 'tax_office' => 'Vergi Dairesi', 'tax_number' => 'Vergi Numarası',
            'unit' => 'Birim', 'unit_type' => 'Birim Türü', 'unit_code' => 'Birim Kodu', 'unit_name' => 'Birim Adı', 'parent_unit' => 'Üst Birim',
            'facility' => 'Tesis', 'location' => 'Konum', 'department' => 'Departman', 'team' => 'Ekip',
            'position' => 'Pozisyon', 'position_code' => 'Pozisyon Kodu', 'position_name' => 'Pozisyon Adı',
            'user' => 'Kullanıcı', 'scope_mode' => 'Görünürlük Kapsamı', 'is_primary' => 'Birincil Atama', 'starts_at' => 'Başlangıç', 'ends_at' => 'Bitiş', 'assignment_reason' => 'Atama Gerekçesi',
            'scope_self' => 'Yalnız kendi kayıtları', 'scope_team' => 'Ekip', 'scope_department' => 'Departman', 'scope_department_descendants' => 'Departman ve alt birimler', 'scope_facility' => 'Tesis', 'scope_company' => 'Şirket', 'scope_global' => 'Global',
            'status' => 'Durum', 'active' => 'Aktif', 'inactive' => 'Pasif', 'archived' => 'Arşivlendi', 'expired' => 'Süresi Doldu', 'revoked' => 'İptal Edildi',
            'description' => 'Açıklama', 'sort_order' => 'Sıra', 'created_at' => 'Oluşturulma', 'updated_at' => 'Güncellenme', 'actions' => 'İşlemler',
            'save' => 'Kaydet', 'update' => 'Güncelle', 'cancel' => 'Vazgeç', 'edit' => 'Düzenle', 'all' => 'Tümü', 'none' => 'Yok', 'hierarchy' => 'Organizasyon Ağacı', 'no_units' => 'Bu şirkete bağlı organizasyon birimi yok.',
            'created_success' => 'Organizasyon kaydı oluşturuldu.', 'updated_success' => 'Organizasyon kaydı güncellendi.', 'status_updated' => 'Kayıt durumu güncellendi.',
            'required_fields' => 'Zorunlu alanları doldurun.', 'invalid_resource' => 'Geçersiz organizasyon kaynağı.', 'invalid_record' => 'Organizasyon kaydı bulunamadı.',
            'invalid_code' => 'Kod yalnız büyük/küçük harf, rakam, tire ve alt çizgi içerebilir.', 'duplicate_code' => 'Bu kod aynı kapsamda zaten kullanılıyor.',
            'invalid_hierarchy' => 'Seçilen üst birim bu şirket veya birim türü için uygun değil.', 'invalid_date_range' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',
            'save_error' => 'Organizasyon kaydı kaydedilirken bir hata oluştu.', 'load_error' => 'Organizasyon verileri yüklenemedi.', 'permission_denied' => 'Bu işlem için yetkiniz yok.',
            'select_company' => 'Şirket seçin', 'select_unit' => 'Birim seçin', 'select_position' => 'Pozisyon seçin', 'select_user' => 'Kullanıcı seçin',
            'code_hint' => 'Stabil teknik kod. Sonradan değiştirmemek önerilir.', 'no_parent' => 'Üst birim yok', 'export' => 'Dışa Aktar',
        ],
        'en' => [
            'organization' => 'Organization',
            'organization_hint' => 'Manage companies, facilities, locations, departments, teams, positions, and user scopes.',
            'companies' => 'Companies', 'units' => 'Organization Units', 'positions' => 'Positions', 'assignments' => 'User Assignments',
            'new_company' => 'New Company', 'new_unit' => 'New Unit', 'new_position' => 'New Position', 'new_assignment' => 'New Assignment',
            'edit_company' => 'Edit Company', 'edit_unit' => 'Edit Unit', 'edit_position' => 'Edit Position', 'edit_assignment' => 'Edit Assignment',
            'company' => 'Company', 'company_code' => 'Company Code', 'company_name' => 'Company Name', 'legal_name' => 'Legal Name', 'tax_office' => 'Tax Office', 'tax_number' => 'Tax Number',
            'unit' => 'Unit', 'unit_type' => 'Unit Type', 'unit_code' => 'Unit Code', 'unit_name' => 'Unit Name', 'parent_unit' => 'Parent Unit',
            'facility' => 'Facility', 'location' => 'Location', 'department' => 'Department', 'team' => 'Team',
            'position' => 'Position', 'position_code' => 'Position Code', 'position_name' => 'Position Name',
            'user' => 'User', 'scope_mode' => 'Visibility Scope', 'is_primary' => 'Primary Assignment', 'starts_at' => 'Starts At', 'ends_at' => 'Ends At', 'assignment_reason' => 'Assignment Reason',
            'scope_self' => 'Own records only', 'scope_team' => 'Team', 'scope_department' => 'Department', 'scope_department_descendants' => 'Department and descendants', 'scope_facility' => 'Facility', 'scope_company' => 'Company', 'scope_global' => 'Global',
            'status' => 'Status', 'active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived', 'expired' => 'Expired', 'revoked' => 'Revoked',
            'description' => 'Description', 'sort_order' => 'Order', 'created_at' => 'Created At', 'updated_at' => 'Updated At', 'actions' => 'Actions',
            'save' => 'Save', 'update' => 'Update', 'cancel' => 'Cancel', 'edit' => 'Edit', 'all' => 'All', 'none' => 'None', 'hierarchy' => 'Organization Tree', 'no_units' => 'No organization units are linked to this company.',
            'created_success' => 'Organization record created.', 'updated_success' => 'Organization record updated.', 'status_updated' => 'Record status updated.',
            'required_fields' => 'Complete the required fields.', 'invalid_resource' => 'Invalid organization resource.', 'invalid_record' => 'Organization record not found.',
            'invalid_code' => 'Code may contain letters, numbers, dashes, and underscores only.', 'duplicate_code' => 'This code is already used in the same scope.',
            'invalid_hierarchy' => 'The selected parent unit is not valid for this company or unit type.', 'invalid_date_range' => 'End date cannot be before start date.',
            'save_error' => 'An error occurred while saving the organization record.', 'load_error' => 'Organization data could not be loaded.', 'permission_denied' => 'You do not have permission for this action.',
            'select_company' => 'Select company', 'select_unit' => 'Select unit', 'select_position' => 'Select position', 'select_user' => 'Select user',
            'code_hint' => 'Stable technical code. Avoid changing it later.', 'no_parent' => 'No parent unit', 'export' => 'Export',
        ],
    ];

    $dictionary['tr'] += [
        'active_company' => 'Aktif Firma',
        'default_company' => 'Varsayilan Firma',
        'save_as_default' => 'Varsayilan olarak kaydet',
        'active_company_updated' => 'Aktif firma guncellendi.',
        'active_company_update_error' => 'Aktif firma guncellenirken bir hata olustu.',
        'csrf_failed' => 'Guvenlik dogrulamasi basarisiz oldu.',
    ];
    $dictionary['en'] += [
        'active_company' => 'Active Company',
        'default_company' => 'Default Company',
        'save_as_default' => 'Save as default',
        'active_company_updated' => 'Active company updated.',
        'active_company_update_error' => 'An error occurred while updating active company.',
        'csrf_failed' => 'Security validation failed.',
    ];

    $locale = strtolower((string) env('APP_LOCALE', 'tr'));
    if (!isset($dictionary[$locale])) {
        $locale = 'tr';
    }
    return $dictionary[$locale][$key] ?? $dictionary['tr'][$key] ?? $default ?? $key;
}
