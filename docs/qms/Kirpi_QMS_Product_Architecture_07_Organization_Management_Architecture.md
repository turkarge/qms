# Kirpi QMS+ Product Architecture
## Bölüm 07 - Organization Management Architecture
### Sürüm 1.0

# Amaç

Bu doküman Kirpi QMS+ organizasyon modelini tanımlar.

Temel prensip:

"Tüm kalite kayıtları bir organizasyon bağlamında yaşar."

---

# Organization Hierarchy

Kirpi QMS+ çok seviyeli organizasyon yapısını destekler.

Örnek:

Company
 -> Facility
   -> Department
      -> Team

---

# Company Entity

Şirket seviyesindeki temel kayıt.

Alanlar:

- Company Code
- Company Name
- Tax Information
- Active Standards
- Status

---

# Facility Entity

Tesis veya fabrika tanımı.

Örnek:

- Merkez Fabrika
- Ankara Fabrikası
- Lojistik Merkezi

---

# Location Entity

Fiziksel konumlar.

Örnek:

- Üretim Alanı
- Depo
- Laboratuvar
- Kalite Kontrol

---

# Department Entity

Organizasyon birimleri.

Örnek:

- Kalite
- Üretim
- Satınalma
- İnsan Kaynakları

---

# Position Entity

Pozisyon tanımları.

Örnek:

- Kalite Müdürü
- Üretim Müdürü
- İç Denetçi

---

# Team Entity

Operasyonel ekipler.

Örnek:

- İç Denetim Ekibi
- Kalibrasyon Ekibi
- Risk Ekibi

---

# Process Ownership Integration

Organizasyon yapısı süreç sahipliği ile ilişkilidir.

Örnek:

Satınalma Süreci
 -> Satınalma Müdürü

Kalibrasyon Süreci
 -> Kalite Müdürü

---

# Multi Facility Support

Aynı şirket içerisinde birden fazla tesis desteklenir.

Örnek:

Company

 -> Facility A
 -> Facility B
 -> Facility C

---

# Standard Responsibility Mapping

Standart sorumlulukları organizasyon yapısına bağlanabilir.

Örnek:

ISO 9001
 -> Kalite Müdürü

ISO 27001
 -> Bilgi Güvenliği Ekibi

---

# Organizational Events

Örnek:

DepartmentCreated
PositionAssigned
OwnerChanged
FacilityActivated

---

# Audit Perspective

Her kayıt aşağıdaki bağlamları taşımalıdır:

- Company
- Facility
- Department
- Owner

---

# Future Expansion

İlerleyen sürümlerde:

- Grup Şirketleri
- Holding Yapıları
- Global Organizasyonlar
- Çok Dilli Organizasyon Yapıları

desteklenebilir.

---

# Sonuç

Organization Management Architecture;

Kirpi QMS+ içerisindeki tüm modüllerin bağlanacağı temel organizasyon omurgasını oluşturur.
