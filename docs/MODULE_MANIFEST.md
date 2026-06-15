# KirpiCore Module Manifest (`module.json`)

Bu doküman, modüller için opsiyonel manifest yapısını tanımlar.
Mevcut sistemi bozmaz; `module.json` olmayan modül de varsayılan değerlerle yüklenir.
Bu belge aynı zamanda KirpiCore modüllerinin geliştirme standardını tanımlar.

## Dosya Konumu

- `modules/<module_key>/module.json`

## Modül Geliştirme Standardı

Her modül, aşağıdaki yapıdan ihtiyacına uygun olan dosyaları içermelidir:

- `modules/<module_key>/module.json`
- `modules/<module_key>/language.php`
- `modules/<module_key>/routes.php`
- `modules/<module_key>/pages/`
- `modules/<module_key>/actions/`
- `modules/<module_key>/modals/`
- `modules/<module_key>/partials/`
- `modules/<module_key>/scripts/`
- `modules/<module_key>/database/schema.sql`
- `modules/<module_key>/database/permissions.sql`

Notlar:

- `language.php` dosyası modül seviyesinde zorunlu standarttır.
- Bir modülde UI veya action yoksa ilgili klasörlerin boş olması sorun değildir.
- `database/*` dosyaları yoksa setup bu modülü veritabanı adımında pas geçer.

## Örnek

```json
{
  "key": "users",
  "name": "Users",
  "description": "Kullanıcı yönetimi",
  "version": "1.0.0",
  "enabled": true,
  "core": true,
  "load_order": 30,
  "requires": [],
  "author": "Kirpi Core"
}
```

## Alanlar

- `key` (string): Modül teknik anahtarı.
- `name` (string): Panel/insan okunur ad.
- `description` (string): Kısa açıklama.
- `version` (string): Modül versiyonu.
- `enabled` (bool): `false` ise module ait `routes.php` yüklenmez.
- `core` (bool): Core modül mü bilgisi.
- `load_order` (int): Modül yükleme sırası (küçükten büyüğe).
- `requires` (array<string>): Gelecekte bağımlılık kontrolü için ayrılan alan.
- `author` (string): Modül geliştirici bilgisi.
- `menu` (array<object>): Modülün navigasyona eklemek istediği menü öğeleri.

## Menü Standardı (`module.json > menu`)

Kirpi Core, menüleri artık modül manifestlerinden üretir.

Sabit kurallar:

- `Dashboard` her zaman ilk sıradadır (`weight=1`, sabit).
- `Yönetim` her zaman son sıradadır (`weight=999`, sabit).
- Modüllerden gelen tüm menüler bu iki sabit öğe arasına veya `Yönetim` altına yerleşir.

`menu` öğesi alanları:

- `title` (string, zorunlu): Menüde görünen başlık
- `title_key` (string, opsiyonel): Modül `language.php` içindeki çeviri anahtarı. Varsa başlık bununla çözülür.
- `icon` (string, opsiyonel): Tabler icon class (örn: `ti ti-users`)
- `url` (string, zorunlu): Route path (örn: `users/view`)
- `permission` (string|null, opsiyonel): Yetki kontrol anahtarı
- `placement` (string): `top` veya `management`
- `group` (string): `management` içindeki grup anahtarı (`default`, `monitoring`, ...)
- `weight` (int): Sıralama ağırlığı (küçük değer önce gelir)

Notlar:

- `placement=top`: Dashboard ile Yönetim arasında üst menüde gösterilir.
- `placement=management`: Yönetim dropdown içinde gösterilir.
- `group=monitoring`: Yönetim altında `Monitoring / İzleme` alt grubuna otomatik alınır.
- `title_key` kullanıldığında menü etiketi `<module>_lang('<title_key>')` ile çekilir.
- Route mevcut değilse veya kullanıcının yetkisi yoksa menü öğesi otomatik gizlenir.

Örnek:

```json
{
  "key": "users",
  "name": "Users",
  "menu": [
    {
      "title": "Kullanicilar",
      "icon": "ti ti-users",
      "url": "users/view",
      "permission": "users.view",
      "placement": "management",
      "group": "default",
      "weight": 100
    }
  ]
}
```

## Dil Dosyası Standardı (`language.php`)

Her modül, kendi çeviri fonksiyonunu sağlar:

- Fonksiyon adı modül bazlı olmalıdır. Örnek: `users_lang()`, `auth_lang()`, `api_lang()`.
- İmza: `function <module>_lang(string $key, ?string $default = null): string`
- `tr` ve `en` sözlükleri aynı anahtar setini korumaya çalışmalıdır.
- Locale kaynağı: `APP_LOCALE` (`tr` varsayılan).
- Bulunamayan anahtarlarda geri dönüş sırası:
  - aktif locale
  - `tr`
  - `$default`
  - `$key`

Kullanım:

- Sayfa/action başında: `require_once BASE_PATH . '/modules/<module_key>/language.php';`
- Sabit metinler doğrudan yazılmak yerine `*_lang('key')` ile okunur.

## Liste, Rapor ve Export Standardı

Liste ekranı olan core modüllerde aşağıdaki standart uygulanır:

- Uygulama tabloları `docs/KIRPI_TABLE.md` içindeki KirpiTable sözleşmesini kullanmalıdır.
- Statik tablolar `standard`, `report`, `compact` veya `matrix` profillerinden biriyle işaretlenmelidir.
- Büyük veya aksiyon içeren listeler `ajax/<module_key>/datatable` endpoint'i ve `core/kirpi_table.php` yardımcılarıyla sunucu taraflı çalışmalıdır.
- Global arama ve kolon filtreleri tablo toolbar'ında yer alır; aynı işi yapan ayrı üst filtre paneli oluşturulmaz.
- Global arama ve tablo araçları tek Bootstrap `input-group` içinde render edilir; modüller kendi toolbar veya tekrar eden export butonlarını üretmez.
- Filtre parametreleri tablo endpoint'ine ve export endpoint'ine aynı anlamla taşınmalıdır.
- Export endpoint standardı: `modules/<module_key>/actions/export.php`
- Route standardı: `<module_key>/actions/export`
- Yetki standardı:
  - Liste export için `<module_key>.view`
  - Hassas matris/katalog export için ilgili özel yetki (`roles.permissions` gibi)
- CSV ve XLS çıktıları `core/export.php` içindeki helper'lar ile üretilir.
- KirpiTable istemci export'u görünen veriyi; server export'u tüm filtrelenmiş sonucu üretir.
- Export dosyaları en fazla makul bir sınırla üretilmelidir. Mevcut standart limit `5000` kayıttır.

Tamamlanan server-side export modülleri:

- `notifications`
- `documents`
- `audit`
- `users`
- `roles`
- `mail`
- `backup`
- `settings`
- `template`
- `queue`
- `api`
- `health`
- `security`

`roles` modülünde ek olarak Permission Catalog ve Role-Permission Matrix export standarttır.

## Notification Event Standardı

Modüller kullanıcıya veya sisteme dönük önemli olaylarda `kirpi_notify_user()` kullanmalıdır.

Oturumdaki kullanıcıya dönük yönetim aksiyonlarında `kirpi_notify_current_user()` tercih edilmelidir.

Metadata alanları:

- `template_key`
- `source_module`
- `entity_type`
- `entity_id`
- `data`

Bu metadata notification listesinde filtreleme ve daha sonra AI/KIP tarafında olay analizi için kullanılır.

## Template ve Document Standardı

Yeni modüller, içerik üretimi veya kullanıcıya gönderilecek metinlerde Template Registry kullanmalıdır.

Dosya/ek yönetimi gereken modüller Documents Registry ile çalışmalıdır:

- Dosya saklama modül içinde dağınık yapılmamalıdır.
- Entity bağlantıları `document_links` üzerinden kurulmalıdır.
- Belge tipi teknik anahtarı kısa ve stabil olmalıdır (`attachment`, `report`, `user_document` gibi).
- Kullanıcı yüklemeleri FilePond arayüzü üzerinden yapılır; dosya saklama ve doğrulama yine Documents backend'i tarafından yürütülür.
- Yeni modüller bağımsız dosya yöneticisi veya doğrudan `uploads/` yazma mekanizması eklememelidir.
- MIME, dosya boyutu, CSRF, permission ve audit kontrolleri istemci tarafına bırakılamaz.

Ayrıntılı entegrasyon sözleşmesi: `docs/DOCUMENTS_FILE_MANAGER.md`

## AI Schema Standardı

AI/KIP için veri yayınlayacak modüller aşağıdaki dosyayı sağlamalıdır:

- `modules/<module_key>/ai/schema.json`

Schema tanımı en az şu bilgileri içermelidir:

- `module`
- `entity`
- `table`
- `permission`
- `fields`
- hassas alan işaretleri

AI discovery, kullanıcının mevcut RBAC yetkilerini aşamaz.

AI öncesi Core kapanışında tüm standart modüller `ai/schema.json` kapsamına alınmıştır. DB tablosu olmayan ekranlar için hayali tablo yayınlanmaz; yalnız mevcut Core tablolarından türetilen read-only metadata entity'leri kullanılabilir.

## Geriye Uyumluluk

- `module.json` yoksa default değerler kullanılır.
- Mevcut route yapısı ve modül dizin yapısı aynen korunur.
- `language.php` olmayan eski modüller teknik olarak çalışabilir; ancak yeni standartta eklenmesi gerekir.

## Registry ve Runtime

- DB registry tablosu: `app_modules`
- Runtime'da modül listesi:
  - Manifest değerleri
  - `app_modules` override değerleri (`is_enabled`, `load_order`, `is_core`)
- Route yükleme yalnızca `enabled=true` modüller için yapılır.

## Kurulum ve Schema Davranışı

- Core kurulum: `database/core.sql`
- Modül schema kurulumları: `modules/*/database/schema.sql`
- Modül permission kurulumları: `modules/*/database/permissions.sql`
- Setup şu kurallarla çalışır:
  - Dosya yoksa atlanır.
  - Dosya varsa statement bazında çalıştırılır.
  - Idempotent SQL tercih edilir (`IF NOT EXISTS`, `INSERT IGNORE`, vb.).

## Yönetim Ekranı

- Route: `settings/modules`
- Core modüller (`is_core=1`) disable edilemez.
- Bir modül diğer aktif modüller tarafından `requires` ile kullanılıyorsa disable edilmez.

## Kodlama Kuralları (Özet)

- Modül, yalnız kendi alanındaki dil anahtarlarını kullanır.
- Action cevapları tutarlı JSON formatında olur (`status`, `message`, opsiyonel `data`).
- UI metinleri ve tablo başlıkları dil dosyasından gelir.
- Yeni modül eklerken önce `module.json` + `language.php` oluşturulur, sonra route/page/action yazılır.
- Tüm PHP dosyaları `UTF-8 (BOM'suz)` formatta kaydedilmelidir.
- `language.php` dosyalarında da aynı kodlama standardı zorunludur; BOM karakteri header/layout çıkışında boşluk sorununa neden olabilir.
