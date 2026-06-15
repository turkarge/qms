# Kirpi QMS+ Product Architecture
## Bölüm 03 - Event Architecture Specification
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ sisteminin olay (Event) mimarisini tanımlar.

Temel prensip:

"Sistemde gerçekleşen her anlamlı işlem bir olaydır."

---

# Event Driven Architecture

Kirpi QMS+ olay tabanlı çalışır.

Bir kayıt oluşturulması, güncellenmesi veya kapatılması olay üretir.

Örnek:

DocumentPublished
RiskCreated
AuditCompleted
CAPAClosed

---

# Event Tanımı

Her event aşağıdaki alanları içermelidir:

- EventId
- EventType
- EntityType
- EntityId
- Timestamp
- UserId
- DepartmentId
- Payload

---

# Event Kategorileri

## Lifecycle Events

DocumentCreated
DocumentUpdated
DocumentPublished
DocumentArchived

---

## Quality Events

RiskCreated
RiskAccepted
CAPAOpened
CAPAClosed
FindingCreated

---

## Training Events

TrainingAssigned
TrainingCompleted
CompetencyExpired

---

## Audit Events

AuditScheduled
AuditStarted
AuditCompleted

---

## Compliance Events

RequirementMapped
EvidenceAttached
ComplianceGapDetected

---

# Event Store

Tüm olaylar merkezi Event Store içerisinde saklanır.

Amaç:

- İzlenebilirlik
- Analiz
- Kurumsal Hafıza
- Yapay Zeka Besleme Katmanı

---

# Event Consumers

Olaylar aşağıdaki katmanlar tarafından tüketilir:

## Rules Engine

Örnek:

RiskCreated
 -> Notification

---

## Event Intelligence

Örnek:

5 CAPA
+
3 Audit Finding

= Trend Analizi

---

## Alfred

Örnek:

DocumentPublished

-> Eğitim ihtiyacı oluştu

---

# Event Retention

Event kayıtları silinmez.

Arşivlenebilir ancak yok edilmez.

---

# Event Correlation

Birden fazla olay ilişkilendirilebilir.

Örnek:

CalibrationExpired
 -> MeasurementError
 -> CustomerComplaint

Bu yapı Event Intelligence tarafından kullanılacaktır.

---

# Kurumsal Hafıza

Event mimarisi sayesinde sistem:

- Geçmiş kararları
- Değişimleri
- Eğilimleri
- Tekrarlayan problemleri

tespit edebilir.

---

# Sonuç

Event Architecture;

Rules Engine,
Event Intelligence,
Alfred,
Quality Memory

katmanlarının temel veri kaynağıdır.

Kirpi QMS+ yaşayan bir sistem olarak tasarlanır.
