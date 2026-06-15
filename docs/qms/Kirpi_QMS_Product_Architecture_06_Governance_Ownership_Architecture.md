# Kirpi QMS+ Product Architecture
## Bölüm 06 - Governance & Ownership Architecture
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ içerisinde yönetişim, sahiplik ve sorumluluk modelini tanımlar.

Temel prensip:

"Yetki ile sorumluluk birbirinden farklıdır."

---

# Governance Framework

Governance Framework aşağıdaki alanları yönetir:

- Yetkilendirme
- Sorumluluklar
- Süreç Sahipliği
- Standart Sahipliği
- Onay Mekanizmaları
- Hesap Verebilirlik

---

# Yetki ve Sorumluluk Ayrımı

## Authorization

Sistemde ne yapabileceğini belirler.

Örnek:

- Doküman yayınlayabilir
- DÖF kapatabilir
- Risk oluşturabilir

---

## Ownership

Neyden sorumlu olduğunu belirler.

Örnek:

- Satınalma Süreci Sahibi
- ISO 9001 Temsilcisi
- İç Denetim Sorumlusu

---

# Multi Standard Ownership

Aynı kişi farklı standartlarda farklı roller üstlenebilir.

Örnek:

Kullanıcı A

ISO 9001
 -> Süreç Sahibi

ISO 27001
 -> İç Denetçi

ISO 14001
 -> Risk Sorumlusu

---

# Ownership Types

## Process Owner

Bir süreçten sorumludur.

---

## Standard Owner

Bir standarttan sorumludur.

---

## Requirement Owner

Belirli bir gereklilikten sorumludur.

---

## Document Owner

Bir dokümandan sorumludur.

---

## Risk Owner

Bir riskten sorumludur.

---

## CAPA Owner

Bir DÖF kaydından sorumludur.

---

# Responsibility Matrix

Sistem RACI benzeri modelleri destekleyebilir.

Roller:

- Responsible
- Accountable
- Consulted
- Informed

---

# Approval Architecture

Onay mekanizmaları sahiplikten bağımsızdır.

Örnek:

Doküman Sahibi
 -> Hazırlar

Kalite Müdürü
 -> Onaylar

Genel Müdür
 -> Nihai Onay

---

# Delegation Model

Sorumluluklar geçici olarak devredilebilir.

Örnek:

İzinli kullanıcı yerine vekil atama.

---

# Governance Events

Örnek:

OwnershipChanged
RoleAssigned
ApprovalGranted
ApprovalRejected

---

# Alfred Kullanımı

Alfred aşağıdaki durumlarda öneri verebilir:

- Sahipsiz kayıtlar
- Çakışan roller
- Bağımsızlık ihlalleri
- Eksik onay zincirleri

---

# Audit Perspective

Her kayıt için aşağıdakiler izlenebilir olmalıdır:

- Kim oluşturdu
- Kim güncelledi
- Kim onayladı
- Kim sorumluydu

---

# Sonuç

Governance & Ownership Architecture;

ACL yapısını,
süreç sahipliğini,
standart sahipliğini ve
denetim izlenebilirliğini birbirinden ayıran temel mimari katmandır.
