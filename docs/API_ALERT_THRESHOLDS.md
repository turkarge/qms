# KirpiCore API Alert Thresholds

Bu doküman, API Metrics ekranı verilerine göre ilk alarm eşiklerini tanımlar.
Başlangıç değerleri 1-2 haftalık gözlem için konservatif tutulmuştur.

## İzlenecek metrikler

- `total` (toplam istek)
- `status_401`
- `status_403`
- `status_429`
- `status_5xx`
- `avg_duration_ms`

## Önerilen alarm seviyeleri

### 1 Saat penceresi

- `status_5xx >= 10` -> Kritik
- `status_429 >= 40` -> Uyari
- `status_401 >= 60` -> Uyarı (kimlik doğrulamaları artmış olabilir)
- `avg_duration_ms >= 1200` -> Uyari

### 24 Saat penceresi

- `status_5xx >= 50` -> Kritik
- `status_429 >= 300` -> Uyari
- `status_401 >= 500` -> Uyari
- `status_403 >= 500` -> Bilgilendirme/Uyarı (scope veya role değişiklikleri kontrol edilmeli)

## Oran bazlı ek kontrol (önerilir)

- `5xx_oranı = status_5xx / total`
  - `%1+` -> Uyarı
  - `%3+` -> Kritik

- `429_oranı = status_429 / total`
  - `%5+` -> Uyarı (throttle ayarı veya istek paternleri incelenmeli)

## Olay anında hızlı aksiyon

- 401 artışı:
  - Token oluşturma loglarını ve login kaynaklarını kontrol et.
  - Gerekirse `THROTTLE_API_AUTH_*` limitlerini geçici sıkılaştır.

- 403 artışı:
  - Yeni scope/permission dağıtımı var mı kontrol et.
  - Token scope seçenekleri ve role yetkilerini gözden geçir.

- 429 artışı:
  - Trafik piki veya hatalı istemci döngüsü var mı bak.
  - `THROTTLE_API_*` değerlerini veriye göre ayar et.

- 5xx artışı:
  - Son dağıtım diff'ini kontrol et.
  - DB durumu, kuyruk, disk ve mail bağlantılarını hızlı denetle.

## 2 hafta sonra kalibrasyon

- Eşikler canlı trafiğe göre revize edilmeli.
- Hedef: yanlış pozitifleri azaltırken gerçek sorunlarını erken yakalamak.
