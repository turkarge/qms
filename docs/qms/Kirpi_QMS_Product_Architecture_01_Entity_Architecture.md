# Kirpi QMS+ Product Architecture
## Bölüm 01 - Entity Architecture Specification
### Sürüm 1.0

## Amaç

Bu doküman Kirpi QMS+ sisteminin temel varlık (Entity) mimarisini tanımlar.

Temel prensip:

"Sistemdeki her modül ortak bir Yönetilen Varlık (Managed Entity) modelinden türetilir."

---

# Managed Entity

Her varlık aşağıdaki ortak alanlara sahip olacaktır:

- Id
- EntityType
- EntityCode
- Title
- Description
- Status
- OwnerId
- DepartmentId
- CreatedAt
- UpdatedAt
- ClosedAt
- Tags
- Attachments
- EvidenceLinks
- StandardMappings

---

# Temel Entity Türleri

## Organization Entities

- Company
- Facility
- Location
- Department
- Position
- Team

## Governance Entities

- Role
- Responsibility
- Policy
- ApprovalRule

## Quality Entities

- Document
- Evidence
- CAPA
- Nonconformity
- Risk
- Audit
- Finding
- Supplier
- Training
- Competency
- Equipment
- Calibration

## Standards Entities

- Standard
- Requirement
- Control
- ComplianceRecord

## Intelligence Entities

- Event
- Insight
- Recommendation
- MemoryRecord

---

# Entity Relationship Prensibi

Hiçbir kayıt yalnız yaşamaz.

Örnek:

CAPA
 -> Risk
 -> Audit Finding
 -> Evidence
 -> Requirement

Document
 -> Requirement
 -> Training
 -> Evidence

---

# Ownership Model

Her kayıt aşağıdakilerden en az birine bağlı olmalıdır:

- Kullanıcı
- Departman
- Süreç
- Standart

---

# Event Driven Mimari

Her entity olay üretir.

Örnek:

DocumentPublished
RiskCreated
AuditCompleted
CAPAClosed

Bu olaylar:

Rules Engine
Event Intelligence
isoAI

tarafından kullanılır.

---

# Sonuç

Kirpi QMS+ modüller üzerine değil,
ortak bir Entity ve Relationship mimarisi üzerine inşa edilir.
