# Kirpi QMS+ Product Architecture
## Bölüm 09 - Event Intelligence Architecture
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ içerisinde çalışan Event Intelligence mimarisini tanımlar.

Temel prensip:

"Olaylar bilgi değildir. Olaylar arasındaki ilişkiler bilgidir."

---

# Event Intelligence Nedir?

Event Intelligence;

Event Store içerisinde biriken olayları analiz ederek içgörü üretir.

Amaç:

- Trendleri görmek
- Tekrarlayan problemleri bulmak
- Riskleri erken tespit etmek
- Kurumsal hafıza oluşturmak

---

# Veri Kaynakları

Event Intelligence aşağıdaki kaynaklardan beslenir:

- Events
- Entities
- Relationships
- Standards
- Compliance Data
- Audit Data
- CAPA Data
- Risk Data

---

# Temel Bileşenler

## Event Correlation Engine

İlişkili olayları bulur.

Örnek:

CalibrationExpired
 -> MeasurementError
 -> CustomerComplaint

---

## Trend Analysis Engine

Zaman içerisindeki eğilimleri belirler.

Örnek:

- Artan DÖF sayısı
- Artan riskler
- Düşen tedarikçi performansı

---

## Pattern Detection Engine

Tekrarlayan olay desenlerini tespit eder.

Örnek:

Audit
 -> Finding
 -> CAPA
 -> Repeat Finding

---

## Organizational Memory Engine

Geçmiş olayları ilişkilendirir.

Örnek:

Benzer olay daha önce yaşandı mı?

---

## Early Warning Engine

Olası problemleri önceden tahmin eder.

Örnek:

Supplier Performance Down
+
Complaint Increase

= Potential Quality Risk

---

# Insight Model

Event Intelligence çıktıları Insight olarak saklanır.

Alanlar:

- Insight Id
- Insight Type
- Confidence Score
- Related Entities
- Related Events
- Recommendation

---

# Insight Categories

## Operational Insight

Günlük operasyonel öneriler.

---

## Compliance Insight

Uyumluluk odaklı öneriler.

---

## Audit Insight

Denetim hazırlığı önerileri.

---

## Risk Insight

Risk eğilimleri.

---

## Supplier Insight

Tedarikçi performans eğilimleri.

---

# Alfred Integration

Alfred Event Intelligence sonuçlarını kullanıcıya sunar.

Örnek:

"Benzer kök nedene sahip 4 DÖF bulundu."

---

# Explainable Intelligence

Her öneri açıklanabilir olmalıdır.

Örnek:

Öneri Nedeni:

- 3 Audit Finding
- 2 CAPA
- 1 Risk Record

---

# Quality Memory

Kurumsal hafıza aşağıdaki bilgileri tutabilir:

- Lessons Learned
- Historical Decisions
- Previous Incidents
- Previous Corrective Actions

---

# Sonuç

Event Intelligence Architecture;

Kirpi QMS+'ın kayıt tutan bir sistemden öğrenen bir sisteme dönüşmesini sağlayan temel zekâ katmanıdır.
