# QMS Domain Event Sözleşmesi

## Amaç

Domain event, gerçekleşmiş bir QMS durumunu değiştirilemez ve sürümlü biçimde kaydeder. Event komut değildir ve audit log yerine geçmez.

## Event Envelope v1

```json
{
  "event_id": "uuid",
  "event_type": "controlled_document.published.v1",
  "entity_type": "controlled_document",
  "entity_id": 123,
  "occurred_at": "2026-06-15T12:00:00Z",
  "recorded_at": "2026-06-15T12:00:01Z",
  "actor": {
    "type": "user",
    "user_id": 7
  },
  "organization": {
    "company_id": 1,
    "facility_id": 2,
    "department_id": 5
  },
  "correlation_id": "uuid",
  "causation_id": "uuid-or-null",
  "payload_version": 1,
  "payload": {},
  "metadata": {
    "source_module": "controlled_documents",
    "request_id": "string-or-null"
  }
}
```

## Zorunlu Kurallar

- `event_id`, `event_type`, `entity_type`, `entity_id`, `occurred_at`, `recorded_at`, `actor`, `company_id`, `payload_version` ve `source_module` zorunludur.
- Event type adında major payload sürümü bulunur.
- Payload gerçeğin minimum gerekli snapshot'ını taşır; tüm domain kaydı kopyalanmaz.
- Şifre, token, secret, ham kişisel veri veya dosya içeriği payload'a yazılmaz.
- Event oluşturulduktan sonra update/delete yapılmaz.
- Aynı business transaction içindeki eventler aynı `correlation_id` kullanır.
- Bir event başka event sonucu oluştuysa `causation_id` önceki `event_id` değeridir.

## Aktör Türleri

- `user`: kullanıcı action'ı
- `system`: scheduler veya internal automation
- `rule`: Rules Engine execution
- `integration`: doğrulanmış dış sistem

AI aktörü doğrudan domain kararı veremez. Alfred önerisi kullanıcı tarafından kabul edilirse domain event'in aktörü kullanıcı, metadata kaynağı Alfred olur.

## Sürümleme

- Geriye uyumlu payload alanı eklemek aynı major sürüm içinde yapılabilir.
- Alan anlamı değişirse veya alan kaldırılırsa yeni event type major sürümü çıkarılır.
- Tüketiciler bilmedikleri payload alanlarını yok sayar.
- Event type yeniden adlandırılmaz; yeni type yayınlanır ve eski consumer geçişi belgelenir.

## İlk Event Registry

### Organization

- `company.created.v1`
- `facility.created.v1`
- `facility.activated.v1`
- `department.created.v1`
- `position.assigned.v1`
- `team.created.v1`
- `organization_assignment.changed.v1`

### Governance

- `ownership.changed.v1`
- `responsibility.assigned.v1`
- `delegation.started.v1`
- `delegation.expired.v1`
- `approval.granted.v1`
- `approval.rejected.v1`

### Quality

- `controlled_document.published.v1`
- `risk.created.v1`
- `risk.accepted.v1`
- `nonconformity.created.v1`
- `capa.opened.v1`
- `capa.closed.v1`
- `quality_audit.completed.v1`
- `audit_finding.created.v1`
- `training.completed.v1`
- `competency.expired.v1`
- `calibration.expired.v1`

### Compliance

- `requirement.mapped.v1`
- `evidence.attached.v1`
- `compliance_gap.detected.v1`
- `requirement.satisfied.v1`
- `audit_readiness.changed.v1`

## Yayınlama Transaction'ı

Domain mutasyonu, audit kaydı ve event insert mümkün olduğunda aynı DB transaction içinde tamamlanır. Queue consumer çalıştırılması transaction sonrasına bırakılır. İlk sürümde outbox gereksinimi oluşana kadar event store aynı veritabanında tutulur.
