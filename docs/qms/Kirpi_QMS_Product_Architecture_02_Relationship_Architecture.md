# Kirpi QMS+ Product Architecture
## Bölüm 02 - Relationship Architecture Specification
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ sisteminde varlıklar arasındaki ilişkilerin nasıl kurulacağını tanımlar.

Temel prensip:

"Bilgi tek başına değer üretmez. İlişkili bilgi değer üretir."

---

# Relationship First Architecture

Kirpi QMS+ içerisinde her kayıt başka kayıtlarla ilişkilendirilebilir.

Örnek:

CAPA
 -> Risk
 -> Audit Finding
 -> Requirement
 -> Evidence
 -> Supplier

---

# Relationship Türleri

## Direct Relationship

Bir kayıt başka bir kayda doğrudan bağlıdır.

Örnek:

Finding
 -> CAPA

---

## Reference Relationship

Bilgi amaçlı bağlantıdır.

Örnek:

Document
 -> Standard Requirement

---

## Evidence Relationship

Kanıt bağlantısıdır.

Örnek:

Requirement
 -> Evidence

---

## Dependency Relationship

Bir kayıt başka bir kayda bağımlıdır.

Örnek:

Training
 -> Document Revision

---

# Universal Relationship Model

Her ilişki aşağıdaki alanları içerir:

- SourceEntity
- TargetEntity
- RelationshipType
- CreatedBy
- CreatedAt
- Status

---

# Standard Mapping Architecture

Requirement
 -> Document

Requirement
 -> Evidence

Requirement
 -> Audit

Requirement
 -> Risk

Bu sayede standartlar modüllere değil,
modüller standartlara bağlanır.

---

# Evidence Graph

Kirpi QMS+ içerisinde her kanıt birden fazla kayıtla ilişkilendirilebilir.

Örnek:

Calibration Certificate
 -> Requirement
 -> Equipment
 -> Audit Finding

---

# Knowledge Graph Foundation

Relationship mimarisi ileride:

- Event Intelligence
- Compliance Center
- Alfred
- Quality Memory

tarafından kullanılacaktır.

---

# Audit Readiness Architecture

Denetim sırasında sistem:

Requirement
 -> Evidence
 -> Audit Record
 -> CAPA

zincirini takip edebilir.

---

# Sonuç

Kirpi QMS+ modüller arası bağlantıları veri tabanı ilişkileri olarak değil,
kurumsal bilgi ağı olarak değerlendirir.

Bu yapı gelecekte Quality Knowledge Graph temelini oluşturacaktır.
