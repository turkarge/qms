<?php

function qms_events_lang(string $key, ?string $fallback = null): string
{
    $lang = function_exists('current_language') ? current_language() : 'tr';
    $d = [
        'tr' => [
            'qms_events' => 'Olay Zaman Cizelgesi',
            'hint' => 'QMS is olaylarini degistirilemez domain event olarak izleyin.',
            'company' => 'Sirket',
            'event_type' => 'Olay Turu',
            'entity' => 'Varlik',
            'actor' => 'Aktor',
            'source_module' => 'Kaynak Modul',
            'occurred_at' => 'Olusma Zamani',
            'recorded_at' => 'Kayit Zamani',
            'correlation_id' => 'Korelasyon',
            'payload' => 'Payload',
            'actor_user' => 'Kullanici',
            'actor_system' => 'Sistem',
            'actor_rule' => 'Kural',
            'actor_integration' => 'Entegrasyon',
            'type_managed_entity_registered_v1' => 'Varlik Kaydi Olusturuldu',
            'type_entity_relationship_created_v1' => 'Varlik Iliskisi Kuruldu',
            'type_requirement_mapped_v1' => 'Gereklilik Eslemesi Yapildi',
            'type_evidence_attached_v1' => 'Kanit Iliskilendirildi',
            'type_risk_created_v1' => 'Risk Olusturuldu',
            'type_controlled_document_published_v1' => 'Kontrollu Dokuman Yayinlandi',
            'type_capa_opened_v1' => 'CAPA Acildi',
        ],
        'en' => [
            'qms_events' => 'Event Timeline',
            'hint' => 'Track QMS business events in the immutable domain event store.',
            'company' => 'Company',
            'event_type' => 'Event Type',
            'entity' => 'Entity',
            'actor' => 'Actor',
            'source_module' => 'Source Module',
            'occurred_at' => 'Occurred At',
            'recorded_at' => 'Recorded At',
            'correlation_id' => 'Correlation',
            'payload' => 'Payload',
            'actor_user' => 'User',
            'actor_system' => 'System',
            'actor_rule' => 'Rule',
            'actor_integration' => 'Integration',
        ],
    ];

    return $d[$lang][$key] ?? $d['tr'][$key] ?? $fallback ?? $key;
}
