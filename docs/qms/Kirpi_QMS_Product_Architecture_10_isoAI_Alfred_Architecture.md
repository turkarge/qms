# Kirpi QMS+ Product Architecture
## Bölüm 10 - isoAI & Alfred Architecture
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ içerisindeki isoAI katmanını ve Alfred davranış modelini tanımlar.

Temel prensip:

"Yapay zeka görünmez olmalı, faydası görünür olmalıdır."

---

# Alfred Nedir?

Alfred bir chatbot değildir.

Alfred bir Quality Companion'dır.

Görevi:

- Rehberlik etmek
- Hatırlatmak
- Öneride bulunmak
- Kalite hafızasını görünür kılmak

---

# Alfred Prensibi

Alfred:

- Konuşkan değildir
- Karar vermez
- İş akışını durdurmaz
- Gerektiğinde ortaya çıkar
- Kullanıcının önüne geçmez

---

# Trigger Based Presence

Alfred sürekli görünmez.

Yalnızca belirli olaylar gerçekleştiğinde görünür.

Örnek:

- Benzer DÖF bulundu
- Risk yükseldi
- Kanıt eksik
- Eğitim süresi doldu
- Denetim yaklaşıyor

---

# Visibility Rules

## Görünebilir

- Bilgilendirme
- Öneri
- İyileştirme fırsatı
- Uyarı

---

## Görünemez

- Zorlayıcı kararlar
- Otomatik onaylar
- Yetki aşımı işlemleri

---

# Recommendation Engine

Alfred önerilerini aşağıdaki kaynaklardan üretir:

- Event Intelligence
- Rules Engine
- Standards Engine
- Compliance Center
- Organizational Memory

---

# Writing Assistant

Amaç:

Kalite kayıtlarını iyileştirmek.

Kontroller:

- Yazım hataları
- Eksik açıklamalar
- Objektif olmayan ifadeler
- Eksik kanıtlar

---

# Record Quality Analysis

Her kayıt analiz edilebilir.

Örnek kriterler:

- Açıklık
- Detay Seviyesi
- Kanıt Varlığı
- Objektiflik

---

# Audit Assistant

Örnek öneriler:

- Eksik kanıt bulundu
- Eksik eğitim kaydı bulundu
- Açık majör uygunsuzluk mevcut

---

# CAPA Assistant

Örnek öneriler:

- Benzer geçmiş kayıtlar bulundu
- Olası kök nedenler bulundu
- Tekrarlayan problem tespit edildi

---

# Risk Assistant

Örnek öneriler:

- Benzer riskler
- İlgili DÖF kayıtları
- Tedarikçi etkileri

---

# Memory Keeper

Kurumsal hafızayı kullanır.

Örnek:

"Benzer olay 18 ay önce yaşandı."

---

# Explainable AI

Her öneri açıklanabilir olmalıdır.

Örnek:

Öneri nedeni:

- 3 geçmiş DÖF
- 2 denetim bulgusu
- 1 risk kaydı

---

# Confidence Model

Her öneri güven skoru taşıyabilir.

Örnek:

Confidence: 87%

Bu skor karar değil, tavsiyedir.

---

# Human First Principle

Son karar her zaman kullanıcıya aittir.

Alfred:

- Onay vermez
- Kapatmaz
- Yayınlamaz
- İmza atmaz

---

# Sonuç

isoAI ve Alfred;

Kirpi QMS+ içerisinde görünmez zekâ prensibini uygulayan,
kurumsal hafızadan beslenen ve kullanıcıya sessiz rehberlik sunan kalite refakatçisi katmanıdır.
