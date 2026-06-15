# Kirpi Core Yol Haritası

Bu belge, Core geliştirme sırasını ve tamamlanan standartları izlemek için kullanılır.

## Tamamlanan Core Standartları

### UI ve Shell

- Kullanıcı menüsünden tema seçimi: `light`, `dark`, `system`.
- Kullanıcı menüsünden görünüm seçimi: geniş/dar layout tercihi.
- Login ekranında tema seçimi.
- Kilit ekranı dört haneli, otomatik ilerleyen ve tamamlandığında otomatik doğrulanan modern PIN akışına geçirildi.
- Tema varlıkları ve kullanıcı tercihleri kalıcı hale getirildi.
- Menü üretimi `module.json > menu` standardına bağlandı.
- Content menü grubu Türkçeleştirildi.
- Yönetim menüsü Core alanlarına ayrıldı: Erişim Yönetimi, İletişim, İçerik Yönetimi, Kirpi Intelligence, Sistem, Operasyon ve Monitoring / İzleme.
- Core stabilizasyon turunda standart modül registry açıklamaları, permission katalog adları, kullanıcı/rol aksiyon mesajları, mail/backup/helper hata metinleri ve temel fallback metinleri Türkçe/UTF-8 standardına çekildi.

### Kurulum ve Docker

- Dokploy uyumlu Docker akışı güçlendirildi.
- Compose tarafında host port publish kaldırıldı; proxy üzerinden `app:80` standardı netleştirildi.
- Container bağımlılıkları `zip/unzip` eksikleri için düzeltildi.
- Health check ve otomatik DB kurulum akışı doğrulandı.

### Modül Standartları

- Her modül için `module.json` ve `language.php` standardı netleştirildi.
- Türkçe çevirilerde `UTF-8 (BOM'suz)` standardı zorunlu hale getirildi.
- Tarih gösterimi `kirpi_format_datetime()` standardına taşındı.
- Standart modüllere AI schema manifestleri eklenmeye başlandı.

### PWA

- `manifest.webmanifest` ve `service-worker.js` temeli eklendi.
- Offline fallback ve temel asset cache davranışı standardize edildi.

### Template ve Documents

- Core Template Registry eklendi.
- Core Documents Registry eklendi.
- Template ve Documents modülleri Content menü grubu altına alındı.
- Standart modüller yeni template/document altyapısına uyumlu hale getirilmeye başlandı.
- Documents listesi ve export çıktıları entity bağlantı özetini gösterir hale getirildi.

### Notifications

- Template tabanlı notification render akışı eklendi.
- Standart modül olayları notification sistemine bağlandı.
- Navbar bildirim merkezi sayısal okunmamış sayacı, kaynak ikonları, responsive dropdown ve light/dark uyumlu görünümle yenilendi.
- Navbar, özel tema yüzeylerinden arındırılarak native Tabler marka, collapse, dropdown, badge ve card yapısına geçirildi; proje geneli için Tabler-first UI standardı belgelendi.
- Uygulama geneli arka plan, kart, modal, metin, yönetim tabloları ve auth yüzeyleri Tabler-first temel standardına geçirildi.
- Navbar içinde tek bildirimi veya tüm bildirimleri sayfadan ayrılmadan okundu işaretleme standardı eklendi.
- Bildirim listesinde tekil okundu aksiyonu doğrudan ikon düğmesine taşındı; dropdown bağımlılığı kaldırıldı.
- Okundu aksiyonları güncel `unread_count` değerini döndürür ve navbar, dropdown ile liste durumunu eşzamanlı günceller.
- Notification metadata alanları eklendi:
  - `template_key`
  - `source_module`
  - `entity_type`
  - `entity_id`
  - `data_json`
- Notification listesine metadata filtreleri eklendi.

### Report ve Export

- Ortak export helper eklendi: `core/export.php`.
- CSV ve XLS çıktıları sunucu tarafında üretilecek standart endpoint yapısına bağlandı.
- Export butonları gerçek link olarak çalışır; JS varsa mevcut filtreler URL'e eklenir.
- AI schema discovery çıktısı JSON/CSV/XLS formatlarında export edilebilir hale getirildi.
- AI schema kalite kontrol çıktısı JSON/CSV/XLS formatlarında export edilebilir hale getirildi.
- Mail şablonları listesi CSV/XLS export standardına bağlandı.
- Backup kayıtları ve restore logları CSV/XLS export standardına bağlandı.
- Audit Overview için modül bazlı operasyon özeti XLS/CSV export standardına bağlandı.
- Aşağıdaki modüllerde server-side export tamamlandı:
  - `notifications`
  - `documents`
  - `audit`
  - `users`
  - `roles`
  - `mail`
- Roles tarafında ek exportlar:
  - Permission Catalog
  - Role-Permission Matrix

## Devam Eden Standartlaştırma

- Yeni Core tabanlı uygulamalarda deployment standardının proje başlangıcında uygulanması.

## Yarın Planı

1. **Template export ve filtre standardı** - Tamamlandı
   - Template listesine CSV/XLS export eklendi.
   - Template türü, modül, kod, aktiflik ve metin arama filtreleri standardize edildi.

2. **Documents filtre standardı** - Tamamlandı
   - Documents ekranına arama, belge türü, entity türü ve entity ID filtreleri eklendi.
   - Mevcut export endpoint'i bu filtreleri okuyacak şekilde genişletildi.

3. **Settings/Modules export** - Tamamlandı
   - Modül registry listesine CSV/XLS export eklendi.
   - Menü registry görünümüne CSV/XLS export eklendi.

4. **Standard UI kontrolü** - Tamamlandı
   - Export butonlarının ilgili ekranlarda gerçek `<a>` link olarak kaldığı doğrulandı.
   - JS'in yalnızca filtre ekleme sorumluluğu taşıdığı doğrulandı.
   - Audit ve Notifications JS fallback hata metinleri UTF-8 Türkçe standardına çekildi.

5. **KIP hazırlığı** - Tamamlandı
   - AI schema manifestleri kalan standart modüllerde tamamlandı.
   - Schema discovery ekranında export/metadata kullanımı için manifest kapsamı genişletildi.
   - Discovery filtreleri ve JSON/CSV/XLS schema export endpoint'i eklendi.
   - Schema quality check ve kalite raporu export endpoint'i eklendi.

6. **KIP Faz 2 metadata indexing** - Tamamlandı
   - `ai_schema_index` tablosu eklendi.
   - Schema sync sonrası metadata index otomatik yeniden üretilir hale getirildi.
   - Schema search indeks tabanlı skorlamaya geçirildi.
   - Search sonuçlarına eşleşen terim ve kaynak bilgisi eklendi.

7. **Kirpi Intelligence modül ayrımı** - Tamamlandı
   - Yönetim menüsü altında Kirpi Intelligence kendi alt menü grubuna taşındı.
   - Dashboard, Schema Discovery, Schema Quality ve Audit Log ayrı sayfalara bölündü.
   - Ana dashboard yalnızca özet kartlar, hızlı işlemler ve son sync bilgisini gösterir hale getirildi.

8. **KIP Query Planner taslağı** - Tamamlandı
   - Doğal dil soruları SQL üretmeden metadata tabanlı aday plana dönüştürülür.
   - Plan çıktısı aday entity, tablo, yetki, önerilen alanlar ve eşleşen terimleri gösterir.
   - Query Planner ayrı Kirpi Intelligence alt menüsü ve sayfası olarak eklendi.
   - Her plan önizleme denemesi AI audit zincirine yazılır.

9. **KIP Read-only SQL Guard** - Tamamlandı
   - SQL Guard yalnızca tekil `SELECT` sorgularına izin verecek şekilde sıkılaştırıldı.
   - Yorumlar, noktalı virgül, DDL/DML komutları, `UNION`, subquery, sistem şemaları ve riskli fonksiyonlar bloklanır.
   - Plan veya kullanıcı tarafından verilen izinli tablo listesine göre tablo kontrolü yapılır.
   - SQL Guard test ekranı Kirpi Intelligence alt menüsüne eklendi.
   - Her guard kontrolü AI audit zincirine `sql_guard_check` olarak yazılır.

10. **Planner → Guard birleşik akışı** - Tamamlandı
   - Query Planner ekranında Guard Context bölümü eklendi.
   - Planner çıktısındaki `allowed_tables` ve `allowed_fields` kullanıcıya açık gösterilir hale getirildi.
   - SQL Guard ekranı Planner context bilgisini URL üzerinden alıp korur.
   - Bu akış SQL üretmez ve SQL çalıştırmaz; yalnızca güvenli üretim öncesi sınırları netleştirir.

11. **SQL Preview / Dry Run katmanı** - Tamamlandı
   - SQL Preview ekranı Planner context ve SQL Guard sonucunu tek yerde değerlendirir.
   - Preview akışı SQL çalıştırmaz, `EXPLAIN` çalıştırmaz ve gerçek veri okumaz.
   - Guard sonucu, yakalanan tablolar, blok nedenleri ve yürütme kararları görünür hale getirildi.
   - Preview denemeleri AI audit zincirine `sql_preview_check` olarak yazılır.

12. **SQL Candidate Review katmanı** - Tamamlandı
   - Model SQL çıktısı için standart candidate yapısı eklendi.
   - İlk sürüm model çağırmaz; manuel SQL adayı model çıktısı gibi değerlendirilir.
   - Candidate çıktısı Preview zincirine aktarılır ve SQL yine çalıştırılmaz.
   - Candidate review denemeleri AI audit zincirine `sql_candidate_review` olarak yazılır.

13. **Mock SQL Generation Adapter + Prompt Builder** - Tamamlandı
   - SQL üretimi için güvenli prompt builder standardı eklendi.
   - `mock-sql-generator` adapter seed olarak eklendi.
   - Mock generator yalnızca Planner context içindeki izinli tablo ve field listesinden aday üretir.
   - Mock üretim çıktısı Candidate Review ve SQL Preview zincirine bağlıdır; SQL çalıştırılmaz.
   - Mock üretim denemeleri AI audit zincirine `sql_candidate_generate` olarak yazılır.

14. **SQL Generation Gateway** - Tamamlandı
   - SQL candidate üretimi tek gateway fonksiyonuna bağlandı.
   - Mock adapter mevcut güvenli üretim akışını kullanır.
   - External veya disabled adapter durumları güvenli şekilde bloklanır.
   - Config olmayan external adapter `external_adapter_not_configured` sonucu verir.
   - Runtime kapalı adapter `external_runtime_disabled` sonucu verir.

15. **Controlled EXPLAIN Gate** - Tamamlandı
   - SQL Preview içine kontrollü EXPLAIN kapısı eklendi.
   - Varsayılan durumda `AI_SQL_EXPLAIN_ENABLED=false` olduğu için EXPLAIN çalıştırılmaz.
   - Guard bloklarsa EXPLAIN de `guard_blocked` nedeni ile bloklanır.
   - EXPLAIN açılsa bile normal SQL execution kapalı kalır ve gerçek veri okunmaz.

16. **KIP Query Flow birleşik ekranı** - Tamamlandı
   - Planner, Guard Context, SQL Candidate, SQL Preview, SQL Guard ve Explain Gate tek ekranda birleştirildi.
   - Query Flow ekranı SQL execution yetkisi eklemez; tüm zincir read-only ve preview modunda kalır.
   - Mock/gateway üzerinden candidate üretimi ve preview sonucu aynı akışta görünür hale getirildi.
   - Audit zinciri görünürlüğü eklendi.

17. **Kirpi Intelligence menü sadeleştirme** - Tamamlandı
   - Yönetim menüsünde yalnızca ana girişler bırakıldı: Dashboard, Query Flow, Schema Discovery ve Audit Log.
   - Query Planner, Schema Quality, SQL Guard, SQL Preview ve SQL Candidate teknik araç olarak Dashboard altında korundu.
   - Route'lar kaldırılmadı; teknik ekranlar doğrudan bağlantı ve dashboard üzerinden erişilebilir kaldı.

18. **SQL Generation Runtime Gate** - Tamamlandı
   - SQL generation gateway, adapter tipi `sql_generation` olmayan kayıtları `adapter_type_not_supported` ile bloklar.
   - External adapter secret kontrolü yalnız `api_key_env` veya `api_key_ref` referansları üzerinden yapılır; secret değerleri audit'e yazılmaz.
   - Gerçek external runtime varsayılan olarak `AI_EXTERNAL_MODEL_RUNTIME_ENABLED=false` ile kapalıdır.
   - Runtime kapalıyken sonuç `external_runtime_disabled` olarak audit zincirine yazılır.
   - `openai-sql-placeholder` seed adapter kaydı eklendi; varsayılan olarak pasif kalır.

19. **Core standart kapanış turu** - Tamamlandı
   - Queue, API Metrics, Health ve Security ekranları server-side CSV/XLS export standardına bağlandı.
   - Settings, Profile API token, Mail test ve Queue enqueue aksiyonları metadata'lı notification event üretir hale getirildi.
   - Template Registry notification target/variable/default katalogları yeni standart event key'lerini kapsayacak şekilde genişletildi.
   - Module manifest standardı tamamlanan export ve notification davranışını yansıtacak şekilde güncellendi.

20. **AI öncesi schema/metadata kapanış turu** - Tamamlandı
   - Eksik kalan standart modül manifestleri eklendi: Auth, Dashboard, Health, Profile ve Security.
   - Manifestler yalnız mevcut Core tablolarına bağlandı; hayali/virtual tablo yayınlanmadı.
   - Hassas alanlar `is_sensitive` ve gerektiğinde `is_filterable=false` olarak işaretlendi.
   - Schema sync sonucu: `34 entity / 294 field / 4655 index / 0 hata`.
   - Schema quality sonucu: `0 uyarı / 0 hata`.

21. **Provider runtime implementasyonu** - Tamamlandı
   - SQL Generation Gateway, `openai` ve `openai_compatible` provider runtime akışını destekler hale getirildi.
   - Secret çözümü yalnız `api_key_env` veya `api_key_ref` üzerinden yapılır; secret audit'e veya prompt'a yazılmaz.
   - Runtime çağrısı varsayılan kapalı `AI_EXTERNAL_MODEL_RUNTIME_ENABLED=false` kapısının arkasında kalır.
   - Provider yanıtı JSON veya düz SQL metninden standart `SQL Candidate` formatına dönüştürülür.
   - Üretilen aday SQL yine çalıştırılmaz; Preview + Guard zinciri zorunlu kalır.

22. **Provider ayar yönetimi** - Tamamlandı
   - Kirpi Intelligence altına Provider Ayarları ekranı eklendi.
   - Provider, model, base URL, timeout, temperature, max tokens, adapter aktifliği ve adapter runtime onayı arayüzden yönetilir.
   - API key değeri `api_key_ref` ile `app_settings` tablosuna secret olarak yazılabilir; audit ve response içinde secret değeri gösterilmez.
   - Env tabanlı secret kullanılacaksa `api_key_env` arayüzden seçilir, gerçek env değeri deploy ortamında kalır.
   - Global kill-switch `AI_EXTERNAL_MODEL_RUNTIME_ENABLED` kritik env ayarı olarak zorunlu kaldı; adapter runtime onayı bu kapıyı baypas edemez.

23. **Provider canlı test altyapısı** - Tamamlandı
   - Provider Ayarları ekranına `Bağlantıyı Test Et` aksiyonu eklendi.
   - Test çağrısı global `AI_EXTERNAL_MODEL_RUNTIME_ENABLED` ve adapter `runtime_enabled` kapıları arkasında çalışır.
   - Test SQL üretmez, veri okumaz ve secret/ham provider cevabını response veya audit içine yazmaz.
   - Test sonucu genel audit ve AI audit zincirine `provider_runtime_test` olarak yazılır.

24. **Env Reader izleme ekranı** - Tamamlandı
   - Monitoring / İzleme altına `Env Reader` sayfası eklendi.
   - Sayfa çalışan container içindeki env değerlerini `getenv`, `$_ENV` ve güvenli `$_SERVER` kaynaklarından okur.
   - `password`, `secret`, `token`, `key`, `cookie`, `session`, `dsn` gibi hassas anahtarlar maskelenir.
   - `AI_EXTERNAL_MODEL_RUNTIME_ENABLED` docker compose üzerinden app container'ına aktarılır hale getirildi.

25. **Query Flow adapter seçimi sıkılaştırması** - Tamamlandı
   - Query Flow ve SQL Candidate ekranları yalnız `sql_generation` tipindeki aktif adapter'ları listeler.
   - Chat/genel amaçlı adapter'lar SQL üretim akışında seçilemez hale getirildi.
   - Yanlış adapter URL ile gelirse ekran otomatik güvenli SQL generation adapter seçimine düşer.

26. **Provider çıktı güvenliği sıkılaştırması** - Tamamlandı
   - Provider yanıtındaki `<think>...</think>` reasoning bloğu candidate parser içinde temizlenir.
   - JSON yanıt reasoning metninden sonra gelse bile güvenli JSON bloğu ayrıştırılır.
   - SQL üretim prompt'u `SELECT *` ve `table.*` kullanımını açıkça yasaklar.
   - Candidate ve SQL Guard katmanları wildcard select kullanımını `wildcard_select_not_allowed` ile bloklar.
   - Açıklama/prose çıktıları artık SQL candidate olarak kabul edilmez; gerçek `SELECT ... FROM ...` yoksa candidate boş döner.
   - Query Flow Aday SQL kutusu light/dark tema uyumlu okunur kod bloğu standardına alındı.
   - SQL adapter secret çözümü adapter ref, provider ref ve aynı provider'daki yapılandırılmış adapter fallback sırasıyla çalışır.
   - Query Flow model adapter listesi secret/runtime eksiklerini dropdown ve uyarı alanında görünür kılar.
   - Aktif ama `chat` tipindeki adapterlar Query Flow'da neden gizlendiğini açıklayan bilgi alanında listelenir.

27. **Query Flow debug export ve adapter seed koruması** - Tamamlandı
   - Query Flow ekranına `Debug JSON Kopyala` aksiyonu eklendi.
   - Debug JSON; soru, limit, runtime kapıları, seçili adapter, gizlenen adapterlar, tüm adapter tanımları, planner, candidate, preview, guard ve explain sonucunu tek pakette toplar.
   - Secret değerleri JSON içinde maskelenir veya yalnız referans olarak gösterilir.
   - AI adapter seed akışı mevcut provider/model/adapter tipi/runtime ayarlarını `ON DUPLICATE KEY UPDATE` ile ezmeyecek şekilde düzeltildi.

28. **Kirpi Intelligence v1.0 güvenlik kapanışı** - Tamamlandı
   - Gerçek provider debug çıktısı doğrulandı: reasoning temizlendi, açık alanlı SQL üretildi, Preview ve Guard başarıyla geçti, veri çalıştırılmadı.
   - OpenAI/OpenAI-compatible isteklerine yapılandırılmış JSON çıktı modu eklendi; desteklemeyen servisler güvenli düz metin fallback akışına alınır.
   - Provider bağlantı testi artık yalnız cevap varlığını değil beklenen JSON sözleşmesini doğrular.
   - SQL Guard tablo sınırına ek olarak Planner tarafından yayınlanan `allowed_fields` listesini de zorunlu uygular.
   - İzin dışı alanlar `field_not_allowed` ile bloklanır ve Guard/Query Flow ekranlarında görünür hale gelir.
   - Candidate, Preview aşamasından önce Guard kontrolünden geçer; bloklanan candidate audit zincirine başarı olarak yazılmaz.
   - Reasoning, prose, fenced JSON, gömülü JSON, wildcard, izin dışı tablo ve izin dışı alan senaryolarını kapsayan regresyon testi eklendi.
   - AI modül sürümü `1.0.0` olarak kapatıldı.

## Gün Sonu Notu - 2026-06-04

Bugünkü KIP hazırlık çalışması tamamlandı. Core tarafında schema zinciri aşağıdaki hale getirildi:

```text
ai/schema.json manifestleri
  -> Schema Sync
  -> Yetki kontrollü Discovery
  -> JSON/CSV/XLS Schema Export
  -> Schema Quality Check
  -> JSON/CSV/XLS Quality Export
```

Doğrulanan son durum:

- Standart modüller için AI manifest kapsamı tamamlandı.
- Schema sync testi `23 entity / 207 field / 0 hata` sonucu verdi.
- Schema quality check `24 uyarı / 0 hata` sonucu verdi.
- Docker build ve `/healthz` kontrolleri başarılı geçti.
- Yapılan her aşama commit edilip `origin/main` dalına push edildi.

## Kısa Vadeli Sonraki Sıra

- Template export
- Documents filtre/export iyileştirmesi
- Settings/Modules export
- Mail templates export - Tamamlandı
- Backup/audit operasyon raporları - Tamamlandı
- Queue/API/Health/Security export - Tamamlandı
- Notification event yayılımı - Tamamlandı
- Template notification katalog entegrasyonu - Tamamlandı
- AI öncesi schema/metadata kapsamı - Tamamlandı
- Gerçek model adapter ile SQL candidate üretimi
- Gerçek model adapter runtime bağlama tasarımı - Tamamlandı
- Gerçek provider runtime implementasyonu - Tamamlandı
- Provider ayar yönetimi - Tamamlandı
- Provider canlı test altyapısı - Tamamlandı
- Env Reader izleme ekranı - Tamamlandı
- Query Flow adapter seçimi sıkılaştırması - Tamamlandı
- Provider çıktı güvenliği sıkılaştırması - Tamamlandı
- Gerçek provider canlı sağlayıcı doğrulaması - Tamamlandı

## Gün Sonu Notu - 2026-06-11

Bugünkü çalışma AI modülü üzerinde gerçek provider entegrasyonu ve Query Flow teşhis kabiliyetiyle kapatıldı.

Tamamlanan son durum:

- Provider ayarları canlı test akışı başarılı doğrulandı.
- Query Flow yalnız `sql_generation` adapterlarını listeleyecek şekilde sıkılaştırıldı.
- Chat tipindeki aktif adapterların Query Flow'da neden gizlendiği görünür hale getirildi.
- Provider response parser `<think>`, açıklama metni, prose ve wildcard SQL risklerine karşı sertleştirildi.
- Aday SQL kutusu light/dark tema uyumlu okunur hale getirildi.
- `Debug JSON Kopyala` aksiyonu eklendi; test sonucu ve mevcut konfigürasyonlar tek JSON paketi olarak paylaşılabilir hale geldi.
- AI adapter seed akışının kullanıcı/provider ayarlarını ezmesi engellendi.

## Plan - Core Test Turu ve Deployment Standardizasyonu

1. **AI modülü tek oturum kapanışı** - Tamamlandı
   - Kirpi Intelligence `v1.0.0` production-ready preview/gateway seviyesiyle kapatıldı.
   - Gerçek SQL yürütme ve gerçek veri okuma bilinçli olarak kapalı tutuldu.

2. **Kirpi Core ekran ekran test turu** - Tamamlandı
   - Yönetim, Erişim Yönetimi, İçerik Yönetimi, İletişim, Operasyon, Monitoring / İzleme ve Kirpi Intelligence grupları test edildi.
   - Liste, filtre, export, temel aksiyon, audit/notification etkisi ve tema uyumu kontrolleri tamamlandı.
   - Manuel test sonuçları `docs/testing/kirpi-core-ui-test-checklist.md` içinde `94/94` başarılı ve yayına uygun olarak kapatıldı.
   - Backup uzun işlemlerine görünür durum, spinner ve çift gönderim kilidi eklendi.
   - Documents modülü `v2.0.0` kurumsal dosya yöneticisine yükseltildi: çoklu sürükle-bırak yükleme, progress, kart/liste görünümü, seçim, toplu silme/indirme, istatistik ve sayfalama.

3. **Deployment standardizasyon tasarımı** - Tamamlandı
   - Her yeni Core tabanlı uygulamada servis çakışmasını önleyen env tabanlı uygulama prefix standardı hazırlandı.
   - Docker Compose proje, image, container, network ve volume isimleri bu prefix üzerinden ayrıştırıldı.
   - Varsayılan örnek: `KIRPI_APP_PREFIX=kirpicore`.
   - Çalışma portları env üzerinden konfigüre edildi: `KIRPI_APP_HTTP_PORT`, `KIRPI_DB_HOST_PORT`.
   - DB adı, session cookie adı, network/volume adları ve servis portları izolasyon standardına bağlandı.
   - Production Compose host port yayınlamaz; yerel HTTP/DB portları `docker-compose.local.yml` üzerinden env ile yönetilir.
   - Mevcut kurulumların gerçek volume adlarını koruyan override sözleşmesi eklendi.
   - İki farklı prefix ve portla eşzamanlı çalışan iki stack Docker üzerinde doğrulandı.
   - Geçiş ve yeni uygulama standardı `docs/DEPLOYMENT_STANDARD.md` içinde belgelendi.

4. **KirpiTable standart tablo sistemi** - Tamamlandı
   - DataTables 2.3.8 ve Bootstrap 5 entegrasyonu Core içine yerel asset olarak eklendi.
   - Ortak `KirpiTable` adaptörü; server-side veri, global/kolon arama, sıralama, sayfalama, responsive görünüm, seçim, kolon görünürlüğü, kolon sıralama, sabit başlık, klavye navigasyonu ve state kaydı sağlar.
   - CSV, Excel, yazdırma, kopyalama ve tüm filtrelenmiş sonuçları sunucu tarafında export etme akışları standardize edildi.
   - Kullanıcılar, Roller, Audit ve Bildirimler listeleri ortak server-side endpoint sözleşmesine geçirildi.
   - Core içindeki tüm uygulama tabloları `standard`, `report`, `compact`, `matrix` veya manuel server-side profile bağlandı.
   - Ortak PHP istek, sıralama, parametre bağlama ve JSON response yardımcıları `core/kirpi_table.php` içinde standardize edildi.
   - Statik sözleşme ve gerçek MySQL endpoint smoke testleri eklendi.
   - Geliştirici standardı `docs/KIRPI_TABLE.md` içinde belgelendi.

5. **Documents v2.1 profesyonel dosya yöneticisi** - Tamamlandı
   - FilePond 4.32.12 ve dosya türü/boyutu doğrulama eklentileri yerel vendor asset olarak eklendi.
   - Çoklu dosya yükleme; dosya bazında ilerleme, iptal, yeniden deneme ve paralel yükleme desteğine geçirildi.
   - Mevcut Documents storage, permission, CSRF, MIME, boyut ve audit zinciri korundu.
   - Sayfa; koleksiyon gezgini, merkezi grid/liste çalışma alanı ve dosya ayrıntı paneli olarak yeniden düzenlendi.
   - Görsel yoğunluk azaltıldı: istatistik kartları kaldırıldı, gelişmiş filtreler açılır panele ve dosya ayrıntıları offcanvas yüzeyine taşındı.
   - Son 30 gün, bağlantılı dosyalar ve belge türü koleksiyonları gerçek sorgu filtrelerine bağlandı.
   - Geliştirici ve güvenlik standardı `docs/DOCUMENTS_FILE_MANAGER.md` içinde belgelendi.
