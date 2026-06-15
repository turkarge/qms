# Kirpi QMS+ Ürün Sözleşmesi

## Ürün Tanımı

Kirpi QMS+, kalite kayıtlarını yalnız saklayan bir doküman yönetim sistemi değil; organizasyon, sorumluluk, standart, kanıt, olay ve karar bağlamlarını ilişkilendiren bir Quality Operating System'dir.

## Ürün İlkeleri

1. Relationship first: Kayıtlar tek başına değil, ilişkileriyle değer üretir.
2. Evidence first: Uyum ve kararlar doğrulanabilir kanıta dayanır.
3. Event driven: Anlamlı durum değişiklikleri sürümlü domain event üretir.
4. Human first: Otomasyon ve AI kullanıcı kararını destekler, devralmaz.
5. Explainable by default: Skor, kural, insight ve öneriler kaynaklarını gösterir.
6. Standard independent: Standart içeriği kod değil, sürümlü veri olarak yönetilir.
7. Organization scoped: Her kalite kaydı en az bir company bağlamında yaşar.
8. Audit ready: Kimlik, sahiplik, onay, değişiklik ve kanıt zinciri izlenebilir olur.
9. Core native: Kirpi Core permission, audit, Documents, Template, Notification, KirpiTable ve KIP altyapıları yeniden yazılmaz.
10. Offline survivability: Kritik kalite bilgisi ileride doğrulanabilir Shadow paketleriyle taşınabilir olur.

## MVP Sınırı

İlk ürün sürümü aşağıdaki uçtan uca zinciri çalıştırmalıdır:

```text
Organization
  -> Ownership
  -> Controlled Document / Risk / Nonconformity
  -> Audit Finding
  -> CAPA
  -> Requirement
  -> Evidence
  -> Compliance Evaluation
```

Rules Engine, Event Intelligence, Alfred ve Shadow bu zincirin üzerine eklenen sonraki katmanlardır. Foundation tamamlanmadan bu katmanlar üretim bağımlılığı haline getirilemez.

## Ürün Dışı Kapsam

- Genel amaçlı ERP veya CRM geliştirmek
- Core role/permission sistemini değiştirmek
- Core Documents yerine ikinci dosya yöneticisi yazmak
- AI'ya onay, yayın, kapatma veya imza yetkisi vermek
- Standart metinlerini uygulama koduna gömmek
- Shadow paketini veritabanı yedeği gibi konumlandırmak

## Başarı Ölçütleri

- Bir requirement'tan ilgili kanıt, denetim bulgusu ve CAPA zincirine gidilebilir.
- Her kritik kaydın organizasyon bağlamı ve sorumlusu bulunur.
- Compliance sonucu kaynak verilerle yeniden üretilebilir.
- Yetkisiz veya scope dışı veri UI, export, API ve AI katmanlarından sızmaz.
- Temel kalite süreçleri AI ve dış provider kapalıyken çalışır.
