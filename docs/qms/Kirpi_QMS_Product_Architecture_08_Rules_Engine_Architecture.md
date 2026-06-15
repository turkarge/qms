# Kirpi QMS+ Product Architecture
## Bölüm 08 - Rules Engine Architecture
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ içerisinde çalışan Kural Motoru (Rules Engine) mimarisini tanımlar.

Temel prensip:

"Sistem davranışları kod ile değil, kurallar ile yönetilmelidir."

---

# Rules Engine Nedir?

Rules Engine sistemde meydana gelen olaylara karşı otomatik aksiyonlar üretir.

Örnek:

CAPA Created
 -> Owner Assignment
 -> Notification
 -> SLA Start

---

# Temel Bileşenler

## Event Listener

Olayları dinler.

Örnek:

- RiskCreated
- CAPAOpened
- AuditCompleted
- DocumentPublished

---

## Condition Engine

Koşulları değerlendirir.

Örnek:

Risk Score > 80

Department = Production

---

## Action Engine

Koşul sağlandığında çalışır.

Örnek:

- Görev Oluştur
- Bildirim Gönder
- SLA Başlat
- Risk Güncelle

---

## Escalation Engine

Süre aşımı veya ihlal durumlarını yönetir.

Örnek:

CAPA Due Date Passed
 -> Escalation

---

# Rule Structure

Her kural aşağıdaki bölümlerden oluşur:

- Rule Name
- Trigger Event
- Conditions
- Actions
- Priority
- Active Status

---

# Rule Categories

## Notification Rules

Bildirim gönderir.

---

## Assignment Rules

Sorumlu atar.

---

## Escalation Rules

Üst yönetime taşır.

---

## Compliance Rules

Uyumluluk kontrolü yapar.

---

## Audit Rules

Denetim hazırlığı sağlar.

---

# Standard Independent Design

Kurallar standartlardan bağımsızdır.

Örnek:

ISO 9001
veya

ISO 27001

aynı kural motorunu kullanır.

---

# Rule Templates

Sistem hazır şablonlar sunabilir.

Örnek:

CAPA Escalation Template

Audit Reminder Template

Training Expiration Template

---

# Rule Execution Log

Tüm kural çalışmaları kayıt altına alınır.

Alanlar:

- Rule Id
- Event Id
- Execution Date
- Result
- Error

---

# Alfred Integration

Alfred doğrudan karar vermez.

Rules Engine çıktılarından faydalanır.

Örnek:

TrainingExpired

 -> Rule Triggered

 -> Alfred Suggestion

---

# Event Intelligence Integration

Kuralların sonuçları Event Store'a yazılır.

Bu veriler:

- Trend Analizi
- Pattern Detection
- Quality Memory

tarafından kullanılabilir.

---

# Sonuç

Rules Engine Architecture;

Kirpi QMS+ içerisindeki tüm otomasyon davranışlarının merkezi kontrol katmanıdır.

Kod bağımlılığını azaltır ve sistemi genişletilebilir hale getirir.
