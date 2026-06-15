# QMS Terimler Sözlüğü ve Entity Registry

## Amaç

Bu belge, Kirpi QMS+ içinde aynı kavramın farklı modül, tablo veya event adlarıyla tekrar üretilmesini engeller.

## Temel Terimler

| Terim | Teknik karşılık | Tanım |
|---|---|---|
| Yönetilen Varlık | Managed Entity | Ortak kimlik, organizasyon, sahiplik ve yaşam döngüsü taşıyan QMS kaydı |
| Domain Kaydı | Domain Record | Risk skoru veya doküman revizyonu gibi alana özgü alanları taşıyan kayıt |
| İlişki | Relationship | İki managed entity arasındaki tipli ve izlenebilir bağlantı |
| Kanıt | Evidence | Bir requirement veya kalite kararını destekleyen kayıt ya da dosya bağlantısı |
| İş Olayı | Domain Event | QMS alanında gerçekleşmiş, geçmiş zamanlı ve değiştirilemez anlamlı durum |
| Audit Kaydı | Audit Log | Kullanıcının veya sistemin yaptığı teknik işlemin güvenlik izi |
| Yetki | Authorization | Kullanıcının hangi işlemi yapabileceği |
| Sahiplik | Ownership | Kullanıcının hangi kayıt veya alandan sorumlu olduğu |
| Scope | Visibility Scope | Kullanıcının hangi organizasyon bağlamındaki kayıtları görebildiği |
| Kontrollü Doküman | Controlled Document | Revizyon, onay, yayın ve arşiv yaşam döngüsü olan QMS kaydı |
| Dosya | Document File | Core Documents Registry tarafından saklanan fiziksel içerik |
| Requirement | Requirement | Bir standart sürümündeki doğrulanabilir gereklilik |
| Compliance Değerlendirmesi | Compliance Evaluation | Requirement için açıklanabilir durum ve coverage sonucu |
| Insight | Insight | Event ve entity verilerinden üretilen açıklanabilir içgörü |
| Öneri | Recommendation | Kullanıcı kararını destekleyen, bağlayıcı olmayan çıktı |

## Entity Type Registry

Entity type anahtarları küçük harfli `snake_case`, tekil ve stabil olmalıdır.

### Organizasyon

| Entity type | Sahip modül | MVP |
|---|---|---|
| `company` | `organization` | Evet |
| `facility` | `organization` | Evet |
| `location` | `organization` | Evet |
| `department` | `organization` | Evet |
| `position` | `organization` | Evet |
| `team` | `organization` | Evet |

### Yönetişim ve temel platform

| Entity type | Sahip modül | MVP |
|---|---|---|
| `process` | `governance` | Evet |
| `ownership_assignment` | `governance` | Evet |
| `responsibility_assignment` | `governance` | Evet |
| `approval_workflow` | `governance` | Evet |
| `approval_request` | `governance` | Evet |
| `delegation` | `governance` | Evet |

### Standart ve uyum

| Entity type | Sahip modül | MVP |
|---|---|---|
| `standard` | `standards` | Evet |
| `standard_version` | `standards` | Evet |
| `clause` | `standards` | Evet |
| `requirement` | `standards` | Evet |
| `control` | `standards` | Evet |
| `compliance_evaluation` | `compliance` | Evet |
| `compliance_gap` | `compliance` | Evet |

### Operasyonel kalite

| Entity type | Sahip modül | MVP |
|---|---|---|
| `controlled_document` | `controlled_documents` | Evet |
| `document_revision` | `controlled_documents` | Evet |
| `evidence` | `controlled_documents` | Evet |
| `risk` | `risks` | Evet |
| `nonconformity` | `nonconformities` | Evet |
| `capa` | `capa` | Evet |
| `capa_action` | `capa` | Evet |
| `quality_audit` | `audit_management` | Evet |
| `audit_finding` | `audit_management` | Evet |
| `training_record` | `training` | Evet |
| `competency_record` | `competencies` | Evet |
| `supplier` | `suppliers` | Evet |
| `equipment` | `equipment` | Evet |
| `calibration_record` | `calibration` | Evet |

### Otomasyon ve zekâ

| Entity type | Sahip modül | MVP |
|---|---|---|
| `automation_rule` | `rules` | Sonraki faz |
| `insight` | `event_intelligence` | Sonraki faz |
| `recommendation` | `alfred` | Sonraki faz |
| `memory_record` | `event_intelligence` | Sonraki faz |
| `shadow_package` | `shadow` | Sonraki faz |

## Kayıt Kuralları

- Entity type anahtarı yayınlandıktan sonra yeniden adlandırılmaz; alias/migration uygulanır.
- Core `documents`, `audit`, `roles` ve `users` kayıtları QMS entity type registry içine kopyalanmaz.
- Kullanıcı entity bağlantılarında `user_id`, dosya bağlantılarında Core `document_id` kullanılır.
- Her domain kaydının managed entity olması zorunlu değildir. Geçici token, execution detail veya join kayıtları registry'ye alınmaz.
- Registry'ye alınan kayıt kullanıcı tarafından anlamlı şekilde görüntülenebilir, ilişkilendirilebilir veya event üretebilir olmalıdır.
