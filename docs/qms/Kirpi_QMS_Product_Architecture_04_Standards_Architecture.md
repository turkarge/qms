# Kirpi QMS+ Product Architecture
## Bölüm 04 - Standards Architecture Specification
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ içerisinde standartların nasıl yönetileceğini tanımlar.

Temel prensip:

"Standartlar modül değildir. Standartlar bilgi katmanıdır."

---

# Standard Independent Design

Kirpi QMS+ hiçbir standarda bağımlı değildir.

Desteklenen standartlar sisteme içerik olarak eklenir.

Örnek:

- ISO 9001
- ISO 14001
- ISO 45001
- ISO 27001
- ISO 13485
- IATF 16949

---

# Standard Model

Her standart aşağıdaki yapılardan oluşur:

Standard
 -> Clause
 -> Requirement
 -> Control
 -> Evidence

---

# Requirement Architecture

Requirement sistemin temel standart birimidir.

Örnek:

ISO 9001
 -> 7.2 Yetkinlik

ISO 9001
 -> 7.5 Dokümante Edilmiş Bilgi

---

# Requirement Mapping

Requirement aşağıdaki varlıklara bağlanabilir:

- Document
- Evidence
- Audit
- Risk
- Training
- CAPA
- Supplier
- Calibration

---

# Compliance Graph

Requirement
 -> Evidence

Requirement
 -> Audit

Requirement
 -> CAPA

Requirement
 -> Risk

Bu yapı Compliance Center tarafından kullanılır.

---

# Multi Standard Support

Aynı kayıt birden fazla standarda bağlanabilir.

Örnek:

Training Record

 -> ISO 9001 / 7.2
 -> ISO 14001 / 7.2
 -> ISO 45001 / 7.2

---

# Standard Versioning

Her standart sürüm bilgisi taşır.

Örnek:

ISO 9001:2015
ISO 9001:2026

Sürümler birbirinden bağımsız tutulur.

---

# Standard Upgrade Engine

Amaç:

Yeni standart yayımlandığında etkileri belirlemek.

Örnek:

ISO 9001:2026

 -> Requirement Changes
 -> Impact Analysis
 -> Transition Plan

---

# Gap Analysis

Sistem aşağıdakileri tespit edebilir:

- Eksik Kanıt
- Eksik Doküman
- Eksik Eğitim
- Eksik Denetim

---

# Compliance Calculation

Uyum oranı aşağıdaki verilere göre hesaplanabilir:

- Requirement Coverage
- Evidence Coverage
- Audit Status
- CAPA Status

---

# Sonuç

Standards Architecture sayesinde Kirpi QMS+ standartlara bağımlı değil,
standartları yönetebilen bir Quality Operating System haline gelir.
