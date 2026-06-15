# Kirpi Core Tabler UI Standardı

Kirpi Core arayüzünün görsel tasarım sistemi Tabler'dir. Core içinde Tabler'in sunduğu bir bileşen için ikinci bir tema katmanı oluşturulmaz.

## Temel Kurallar

- Bileşenler önce resmi Tabler HTML yapısı ve sınıflarıyla kurulur.
- Light ve dark görünüm Tabler tema tokenları tarafından yönetilir.
- `assets/css/app.css` yalnızca Kirpi Core'a özgü yerleşim, davranış ve Tabler'de bulunmayan işlevsel ihtiyaçları kapsar.
- Tabler bileşenlerinde özel arka plan, border, shadow, hover veya renk tanımı eklenmez.
- JavaScript hook sınıfları `js-` ön ekiyle kullanılır ve görsel stil taşımaz.
- Tabler Icons mevcutsa elle SVG üretilmez.

## Navbar Pilotu

Navbar, bu standarda geçen ilk Core bölümüdür:

- Header, marka, collapse ve dropdown yapilari native Tabler siniflarini kullanir.
- Mobil menü Bootstrap/Tabler collapse davranışıyla çalışır; ek bir mobil menü yöneticisi bulunmaz.
- Bildirim sayacı `badge badge-sm bg-red text-red-fg` standardını kullanır.
- Bildirim dropdown'u `dropdown-menu-card`, `card`, `list-group` ve `card-footer` yapısına dayanır.
- Okundu aksiyonu arka plan hover'i kullanmaz; hover yalnızca check ikonunu vurgular.
- "Tüm bildirimleri gör" aksiyonu card footer içinde düz bağlantıdır.

Sonraki ekran geçişlerinde aynı yaklaşım uygulanacak ve mevcut özel CSS yalnızca davranış veya yerleşim için gerekli olduğu kanıtlanırsa korunacaktır.

## Ortak UI Temeli

- Uygulama arka planı doğrudan `--tblr-body-bg` kullanır.
- Kart, modal ve secondary text için global Kirpi görünüm override'ı uygulanmaz.
- Eski `--kirpi-*` değişkenleri geçiş süresince yalnızca Tabler tokenlarına işaret eden uyumluluk alias'larıdır.
- Light ve dark renk değerleri ayrı bir Kirpi paletinde tanımlanmaz; Tabler'ın `data-bs-theme` tokenları esas alınır.

## Yönetim Ekranları

- Profil sekmelerinde kart içinde kart kullanılmaz; tek dış kart ve native `nav-tabs` yapısı korunur.
- `data-kirpi-table` kullanan ekranlarda ayrıca CSV/XLS buton takımı gösterilmez; arama, kolon, yenileme ve dışa aktarma tek tablo toolbar'ında sunulur.
- Sistem Sağlığı, Queue, Güvenlik İzleme, Modül Yönetimi ve Menü Yönetimi ortak tablo standardına bağlıdır.

## Auth Yüzeyleri

- Login bölünmüş yerleşimi ürün deneyimi olarak korunur; form ve boş görsel yüzeyleri Tabler tema tokenlarını kullanır.
- Tema seçim butonlarının active durumu özel renk CSS'iyle yeniden çizilmez.
- Login, şifre yenileme ve kilit ekranı aynı `data-bs-theme` tercihini uygular.
- Genel metin linkleri light/dark temada çevresindeki metin rengini devralır ve hover sırasında altı çizilmez; özel renk yalnızca açıkça `text-*` veya `link-*` sınıfı verilen semantik bağlantılarda kullanılır.
- Parola görünürlüğü bağlantı değil, erişilebilir `button` tabanlı icon control olarak uygulanır.
