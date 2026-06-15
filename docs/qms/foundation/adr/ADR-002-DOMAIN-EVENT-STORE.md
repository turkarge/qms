# ADR-002 - Ayrı Domain Event Store

- Durum: Accepted
- Tarih: 2026-06-15

## Bağlam

Core `audit_logs` teknik kullanıcı işlemlerini kaydeder. Event Intelligence ve Rules Engine ise semantik, sürümlü ve korelasyon bilgisi taşıyan iş olaylarına ihtiyaç duyar.

## Karar

QMS domain eventleri `qms_events` modülünün append-only store'unda tutulacaktır. Audit log ve event store ayrı sorumluluklara sahip olacaktır.

## Sonuçlar

- Aynı action hem audit hem event üretebilir.
- Event payload sözleşmesi ve sürüm yönetimi zorunludur.
- Eventler kullanıcı action'larıyla güncellenemez veya silinemez.
- Hata logları domain event olarak kaydedilmez.
