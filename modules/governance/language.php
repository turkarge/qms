<?php

function governance_lang(string $key, ?string $fallback = null): string
{
    $lang = function_exists('current_language') ? current_language() : 'tr';
    $translations = [
        'tr' => [
            'governance' => 'Yönetişim', 'governance_hint' => 'Yetkiden bağımsız sahiplik, sorumluluk ve geçici delegasyon kayıtları.',
            'ownerships' => 'Sahiplikler', 'delegations' => 'Delegasyonlar', 'new_ownership' => 'Yeni Sahiplik', 'new_delegation' => 'Yeni Delegasyon',
            'edit_ownership' => 'Sahipliği Düzenle', 'edit_delegation' => 'Delegasyonu Düzenle', 'company' => 'Şirket', 'subject_type' => 'Kayıt Türü',
            'subject_id' => 'Kayıt No', 'subject_title' => 'Kayıt Başlığı', 'ownership_type' => 'Sahiplik Türü', 'owner' => 'Sorumlu',
            'from_user' => 'Asıl Sorumlu', 'to_user' => 'Vekil', 'starts_on' => 'Başlangıç', 'ends_on' => 'Bitiş', 'reason' => 'Gerekçe',
            'status' => 'Durum', 'active' => 'Aktif', 'inactive' => 'Pasif', 'expired' => 'Süresi Doldu', 'revoked' => 'İptal Edildi',
            'save' => 'Kaydet', 'update' => 'Güncelle', 'cancel' => 'Vazgeç', 'edit' => 'Düzenle', 'revoke' => 'İptal Et',
            'select_company' => 'Şirket seçin', 'select_user' => 'Kullanıcı seçin', 'select_subject' => 'Kayıt türü seçin',
            'required_fields' => 'Zorunlu alanları eksiksiz doldurun.', 'invalid_date_range' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',
            'invalid_record' => 'Kayıt bulunamadı.', 'invalid_resource' => 'Geçersiz kaynak.', 'permission_denied' => 'Bu işlem için yetkiniz veya organizasyon kapsamınız yok.',
            'same_delegate' => 'Asıl sorumlu ile vekil aynı kullanıcı olamaz.', 'created_success' => 'Kayıt oluşturuldu.', 'updated_success' => 'Kayıt güncellendi.',
            'revoked_success' => 'Delegasyon iptal edildi.', 'save_error' => 'Kayıt işlemi tamamlanamadı.',
            'process' => 'Süreç', 'standard' => 'Standart', 'requirement' => 'Gereklilik', 'controlled_document' => 'Kontrollü Doküman', 'risk' => 'Risk', 'capa' => 'DÖF',
            'process_owner' => 'Süreç Sahibi', 'standard_owner' => 'Standart Sahibi', 'requirement_owner' => 'Gereklilik Sahibi', 'document_owner' => 'Doküman Sahibi', 'risk_owner' => 'Risk Sahibi', 'capa_owner' => 'DÖF Sahibi',
        ],
        'en' => [
            'governance' => 'Governance', 'governance_hint' => 'Ownership, accountability and temporary delegation records independent of authorization.',
            'ownerships' => 'Ownerships', 'delegations' => 'Delegations', 'new_ownership' => 'New Ownership', 'new_delegation' => 'New Delegation',
            'edit_ownership' => 'Edit Ownership', 'edit_delegation' => 'Edit Delegation', 'company' => 'Company', 'subject_type' => 'Record Type',
            'subject_id' => 'Record ID', 'subject_title' => 'Record Title', 'ownership_type' => 'Ownership Type', 'owner' => 'Owner',
            'from_user' => 'Principal', 'to_user' => 'Delegate', 'starts_on' => 'Starts', 'ends_on' => 'Ends', 'reason' => 'Reason',
            'status' => 'Status', 'active' => 'Active', 'inactive' => 'Inactive', 'expired' => 'Expired', 'revoked' => 'Revoked',
            'save' => 'Save', 'update' => 'Update', 'cancel' => 'Cancel', 'edit' => 'Edit', 'revoke' => 'Revoke',
            'select_company' => 'Select company', 'select_user' => 'Select user', 'select_subject' => 'Select record type',
            'required_fields' => 'Complete all required fields.', 'invalid_date_range' => 'End date cannot be before start date.',
            'invalid_record' => 'Record not found.', 'invalid_resource' => 'Invalid resource.', 'permission_denied' => 'You do not have permission or organization scope for this action.',
            'same_delegate' => 'Principal and delegate cannot be the same user.', 'created_success' => 'Record created.', 'updated_success' => 'Record updated.',
            'revoked_success' => 'Delegation revoked.', 'save_error' => 'The record operation could not be completed.',
            'process' => 'Process', 'standard' => 'Standard', 'requirement' => 'Requirement', 'controlled_document' => 'Controlled Document', 'risk' => 'Risk', 'capa' => 'CAPA',
            'process_owner' => 'Process Owner', 'standard_owner' => 'Standard Owner', 'requirement_owner' => 'Requirement Owner', 'document_owner' => 'Document Owner', 'risk_owner' => 'Risk Owner', 'capa_owner' => 'CAPA Owner',
        ],
    ];
    return $translations[$lang][$key] ?? $translations['tr'][$key] ?? $fallback ?? $key;
}
