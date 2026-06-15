# Kirpi QMS+ Product Architecture
## Bölüm 11 - Shadow Architecture
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ Shadow Architecture yapısını tanımlar.

Temel prensip:

"Kalite sistemi, yazılım erişilemez olduğunda bile yaşamaya devam etmelidir."

---

# Shadow Architecture Nedir?

Shadow Architecture;

Kirpi QMS+ sisteminin kritik kalite bilgilerini taşınabilir ve bağımsız paketler halinde sunmasını sağlar.

Amaç:

- Denetim hazırlığı
- Felaket kurtarma
- Offline erişim
- Kurumsal hafıza koruma

---

# Shadow Layer Bileşenleri

## KRP Package Engine

Sistemin paketleme motorudur.

Paket türleri:

- .belge.krp
- .audit.krp
- .shadow.krp
- .training.krp

---

## Shadow Viewer

KRP paketlerini görüntüleyen istemci.

Özellikler:

- Offline çalışma
- Arama
- Filtreleme
- Kanıt görüntüleme

---

## Audit Package Generator

Denetim için özel paket üretir.

Örnek içerik:

- Dokümanlar
- Kanıtlar
- Eğitim kayıtları
- Denetim kayıtları
- CAPA kayıtları

---

## Compliance Snapshot

Belirli bir tarihteki uyum durumunun dondurulmuş görüntüsü.

Amaç:

- Belgelendirme
- Müşteri denetimi
- Hukuki kayıt

---

# KRP Formatı

KRP Kirpi Quality Package formatıdır.

İçerebilir:

- Metadata
- Dokümanlar
- Kanıtlar
- İlişkiler
- Snapshot bilgileri

---

# Relationship Preservation

Paket oluşturulurken ilişkiler korunur.

Örnek:

Requirement
 -> Evidence
 -> Audit
 -> CAPA

---

# Quality Memory Export

Kurumsal hafıza taşınabilir hale getirilebilir.

Örnek:

- Lessons Learned
- Historical Events
- Major Findings

---

# Offline Audit Mode

Denetçi sisteme erişmeden inceleme yapabilir.

Özellikler:

- Salt okunur
- İmzalı paket
- Doğrulanabilir içerik

---

# Package Integrity

Her paket doğrulanabilir olmalıdır.

Örnek:

- Hash
- Digital Signature
- Package Version

---

# Disaster Recovery Support

Shadow paketleri felaket kurtarma amacıyla kullanılabilir.

Amaç:

- Veri kurtarma
- Bilgi koruma
- Kurumsal hafıza koruma

---

# Alfred Integration

Alfred Shadow paketlerini analiz edebilir.

Örnek:

Eksik kanıt tespiti
Eksik eğitim tespiti
Eksik denetim kaydı tespiti

---

# Future Vision

İlerleyen sürümlerde:

- Mobil Shadow Viewer
- Web Shadow Viewer
- Self Contained Audit Portal
- Secure Customer Audit Package

desteklenebilir.

---

# Sonuç

Shadow Architecture;

Kirpi QMS+ sistemini klasik bir web uygulamasından çıkararak,
taşınabilir ve denetim odaklı bir kalite ekosistemine dönüştürür.
