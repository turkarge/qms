# QMS Permission Matrisi

## Permission Sözleşmesi

Permission slug biçimi `<module>.<capability>` olur. `manage` geniş yetkisi yalnız küçük yönetim alanlarında kullanılır; kritik domain işlemleri ayrı capability taşır.

Her route Core permission kontrolünden geçer. Permission verilmiş olsa bile organization scope ayrıca kontrol edilir.

## Foundation Modülleri

| Modül | Permission'lar |
|---|---|
| `organization` | `organization.view`, `organization.create`, `organization.edit`, `organization.status`, `organization.assign`, `organization.export` |
| `governance` | `governance.view`, `governance.ownership.manage`, `governance.raci.manage`, `governance.approval.manage`, `governance.delegation.manage` |
| `qms_entities` | `qms_entities.view`, `qms_entities.manage`, `qms_entities.archive` |
| `qms_relationships` | `qms_relationships.view`, `qms_relationships.create`, `qms_relationships.delete` |
| `qms_events` | `qms_events.view`, `qms_events.export`, `qms_events.archive` |
| `standards` | `standards.view`, `standards.create`, `standards.edit`, `standards.publish`, `standards.map`, `standards.export` |
| `compliance` | `compliance.view`, `compliance.evaluate`, `compliance.exclude`, `compliance.export` |
| `rules` | `rules.view`, `rules.create`, `rules.edit`, `rules.activate`, `rules.execute`, `rules.logs.view` |
| `event_intelligence` | `event_intelligence.view`, `event_intelligence.run`, `event_intelligence.manage`, `event_intelligence.export` |
| `alfred` | `alfred.view`, `alfred.use`, `alfred.feedback`, `alfred.manage` |
| `shadow` | `shadow.view`, `shadow.create`, `shadow.download`, `shadow.verify`, `shadow.revoke` |

## Operasyonel Modüller

| Modül | Permission'lar |
|---|---|
| `controlled_documents` | `controlled_documents.view`, `.create`, `.edit`, `.submit`, `.approve`, `.publish`, `.archive`, `.export` |
| `risks` | `risks.view`, `.create`, `.edit`, `.assess`, `.accept`, `.close`, `.export` |
| `nonconformities` | `nonconformities.view`, `.create`, `.edit`, `.review`, `.close`, `.export` |
| `capa` | `capa.view`, `.create`, `.edit`, `.assign`, `.verify`, `.close`, `.reopen`, `.export` |
| `audit_management` | `audit_management.view`, `.create`, `.edit`, `.schedule`, `.conduct`, `.findings.manage`, `.close`, `.export` |
| `training` | `training.view`, `.create`, `.assign`, `.complete`, `.cancel`, `.export` |
| `competencies` | `competencies.view`, `.manage`, `.assess`, `.export` |
| `suppliers` | `suppliers.view`, `.create`, `.edit`, `.evaluate`, `.status`, `.export` |
| `equipment` | `equipment.view`, `.create`, `.edit`, `.status`, `.export` |
| `calibration` | `calibration.view`, `.create`, `.schedule`, `.record`, `.approve`, `.export` |

Tablodaki `.capability` kısaltmaları tam slug'da modül adıyla birleştirilir.

## Kritik Ayrımlar

- `view` export yetkisi anlamına gelmez; QMS modüllerinde export ayrı permission'dır.
- `edit` onay, yayın, kapatma veya yeniden açma yetkisi vermez.
- `approve` kaydın sahibi olmayı gerektirmez; approval workflow ataması ayrıca doğrulanır.
- `assign` sorumluluk atayabilir fakat role permission değiştiremez.
- `compliance.exclude`, `capa.reopen`, `shadow.revoke` ve yayın/onay yetkileri gerekçe ve audit gerektirir.

## Core Entegrasyonu

İlk modül uygulanırken permission grubu `core/permissions.php` kataloguna eklenir ve `sync_permission_catalog()` ile DB'ye senkronlanır. Modül `permissions.sql` dosyası katalogdan bağımsız ikinci bir permission kaynağı oluşturmamalıdır.
