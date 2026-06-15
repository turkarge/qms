# Kirpi QMS+ Product Architecture
## Bölüm 05 - Compliance Architecture Specification
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ içerisinde uyumluluk (Compliance) mimarisini tanımlar.

Temel prensip:

"Uyumluluk bir sonuçtur. Kanıtlanabilir bir sonuç."

---

# Compliance Center

Compliance Center tüm standartlar, gereklilikler ve kanıtlar arasında köprü görevi görür.

Amaç:

- Uyum durumunu göstermek
- Eksikleri tespit etmek
- Denetim hazırlığını ölçmek
- Geçiş süreçlerini yönetmek

---

# Compliance Model

Standard
 -> Requirement
 -> Evidence
 -> Compliance Status

---

# Compliance Status Türleri

- Compliant
- Partially Compliant
- Non-Compliant
- Not Evaluated
- Excluded

---

# Requirement Coverage

Her gereklilik aşağıdaki açılardan değerlendirilir:

- Doküman
- Kanıt
- Eğitim
- Risk
- Denetim
- DÖF

---

# Evidence First Principle

Bir gereklilik kanıt ile desteklenmelidir.

Örnek:

ISO 9001 / 7.2 Yetkinlik

Kanıtlar:

- Eğitim Kaydı
- Yetkinlik Matrisi
- Sınav Sonucu

---

# Compliance Score

Uyum puanı aşağıdaki bileşenlerden hesaplanabilir:

- Requirement Coverage
- Evidence Coverage
- Audit Results
- CAPA Status
- Risk Exposure

Not:
Kullanıcıya ham skor göstermek zorunlu değildir.

---

# Gap Analysis Engine

Amaç:

Eksik noktaları belirlemek.

Örnek:

- Eksik Kanıt
- Eksik Eğitim
- Eksik Doküman
- Eksik Denetim

---

# Audit Readiness

Sistem denetim hazırlık durumunu hesaplayabilir.

Örnek:

- Açık Majör DÖF
- Eksik Kanıt
- Süresi Geçmiş Eğitim
- Süresi Geçmiş Kalibrasyon

---

# Cross Standard Compliance

Aynı kanıt birden fazla gerekliliği karşılayabilir.

Örnek:

Eğitim Kaydı

 -> ISO 9001
 -> ISO 14001
 -> ISO 45001

---

# Compliance Events

Örnek olaylar:

ComplianceGapDetected
RequirementSatisfied
EvidenceMissing
AuditReadinessChanged

Bu olaylar Event Intelligence ve Alfred tarafından kullanılabilir.

---

# Alfred Destekli Uyum Rehberliği

Alfred aşağıdaki durumlarda öneri sunabilir:

- Eksik kanıt
- Yaklaşan denetim
- Düşen uyum oranı
- Yeni standart sürümü

---

# Sonuç

Compliance Architecture;

Standards Engine ile Operational Quality katmanları arasında çalışan,
denetime hazır ve açıklanabilir uyumluluk yapısını oluşturur.
