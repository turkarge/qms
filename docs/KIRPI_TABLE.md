# KirpiTable Geliştirici Standardı

Kirpi Core içindeki tüm uygulama tabloları `KirpiTable` standardını kullanır. Altyapı DataTables 2, Bootstrap 5 ve Core tarafından sağlanan istemci/sunucu adaptörlerinden oluşur.

## Dosyalar

- İstemci adaptörü: `assets/js/kirpi-table.js`
- Tema ve yerleşim: `assets/css/kirpi-table.css`
- Sunucu yardımcıları: `core/kirpi_table.php`
- Global asset yükleme: `layouts/header.php`, `layouts/footer.php`
- Statik sözleşme testi: `tests/kirpi_table_standard_test.php`
- Gerçek DB endpoint testi: `tests/kirpi_table_endpoint_smoke.php`

## Tablo Türleri

### Standard

Orta büyüklükteki, sayfa içinde üretilen listeler içindir. Arama, sayfalama, kolon görünürlüğü, kolon sıralama ve export aktiftir.

```html
<table data-kirpi-table="standard" data-table-title="Modül Registry" class="table table-vcenter table-striped">
```

Seçim gerekiyorsa `data-selectable="true"`, varsayılan sayfa boyutu için `data-page-length="25"` kullanılabilir.

### Report

Rapor ve salt okunur sonuç tabloları içindir. Seçim kapalıdır; arama, sayfalama ve export açıktır.

```html
<table data-kirpi-table="report" data-table-title="API Metrikleri" class="table table-vcenter table-striped">
```

### Compact

Küçük durum ve özet tabloları içindir. Toolbar, arama, sayfalama ve kolon taşıma kapalıdır.

```html
<table data-kirpi-table="compact" data-table-title="Sistem Kontrolleri" class="table table-vcenter">
```

### Matrix

Yetki matrisi gibi geniş tablolarda kullanılır. Arama açıktır; sayfalama, responsive kolon katlama ve kolon taşıma kapalıdır.

```html
<table data-kirpi-table="matrix" data-table-title="Rol Yetki Matrisi" class="table table-vcenter">
```

## Sunucu Taraflı Liste

Büyük, sürekli büyüyen veya aksiyon içeren listeler `KirpiTable.create()` ile açıkça başlatılır. Route standardı `ajax/<module_key>/datatable`, endpoint dosyası `modules/<module_key>/actions/datatable.php` olur.

Sayfa tablosu `kirpi-data-table` sınıfını kullanır. Kolon başlıkları HTML içinde tanımlanır, veri ve render kuralları modülün sayfa scriptinde verilir.

```js
const table = KirpiTable.create(element, {
    ajax: { url: config.endpoint },
    select: false,
    order: [[0, "asc"]],
    columns: [
        { data: "name", name: "name" },
        { data: "is_active", name: "is_active" }
    ],
    columnFilters: [
        { placeholder: "Ad ara", label: "Ada göre filtrele" },
        { type: "select", options: statusOptions }
    ],
    exportColumns: [0, 1],
    exportTitle: "Kayıtlar",
    stateKey: "module-records"
});
```

Sunucu endpoint'i Core yardımcılarını kullanmalıdır:

```php
$request = kirpi_table_request();
$searches = kirpi_table_column_searches($request);
$orderSql = kirpi_table_order_sql($request, $columnMap, 't.id DESC');
kirpi_table_bind($statement, $params);
kirpi_table_response($request, $total, $filtered, $data);
```

SQL kolonları yalnız sabit bir `$columnMap` üzerinden sıralanır. İstemciden gelen SQL kolon adı veya yön ifadesi doğrudan sorguya eklenmez.

## Filtre ve Export

- Ayrı bir üst filtre paneli oluşturulmaz; global arama ve kolon filtreleri KirpiTable içinde tutulur.
- Global arama etiketsiz ve tam genişlikte gösterilir; tablo araçları arama alanının sağında birleşik input-group eki olarak yer alır.
- Dışa aktarma, kolon yönetimi ve yenileme aksiyonları metin düğmeleri yerine tooltip içeren kompakt ikon grubu olarak sunulur.
- Toolbar, DataTables 2 `layout.top` alanına verilen özel bir DOM düğümüyle ilk render sırasında oluşturulur.
- Arama girdisi ve `DataTable.Buttons` container'ı aynı Bootstrap `input-group` düğümünün doğrudan çocuklarıdır.
- DataTables'ın ayrı `topStart` ve `topEnd` hücreleri kullanılmaz; başlatma sonrasında kontrol taşıyan DOM manipülasyonu yapılmaz.
- Arama girdisi `table.search(value).draw()` API'sini debounce ile çağırır ve state içindeki mevcut global aramayla senkronize edilir.
- `columnFilters` dizisi `columns` dizisiyle aynı sırada olmalıdır.
- Tam sonuç export'u gerekiyorsa `serverExport.endpoint` tanımlanır.
- Export endpoint'i global arama ve kolon filtreleriyle aynı parametreleri kabul eder.
- Görünen veri export'u istemcide; tüm filtrelenmiş sonuçların export'u sunucuda yapılır.
- Sunucu export limiti varsayılan olarak en fazla `5000` kayıttır.

## Satır Aksiyonları

- Son kolon başlığı metin yerine `ti-settings` ikonu kullanır.
- Her satırdaki aksiyon tetikleyicisi `ti-dots-vertical` ikonudur.
- Menü kapsayıcısı `kirpi-row-actions`, tetikleyici `js-kirpi-row-menu` sınıfını kullanır.
- Yetkisiz aksiyon istemciye hiç render edilmez.
- POST işlemleri `KirpiTable.post()` ile gönderilir; CSRF token Core tarafından otomatik eklenir.
- Modal, yönlendirme veya POST başlamadan önce açık satır menüsü kapanır.

## Kalite Kuralları

- Uygulama sayfasındaki her `<table>` ya `data-kirpi-table` profiline ya da manuel `kirpi-data-table` kurulumuna sahip olmalıdır.
- Boş durum için `colspan` kullanan statik tablolar otomatik başlatılmaz; DataTables geçersiz kolon uyarısı üretmez.
- Tablo metinleri modül dil dosyasından gelmelidir.
- Light, Dark ve System temalarında toolbar, dropdown, satır ve filtre kontrastı kontrol edilmelidir.
- Mobil görünümde yatay taşma, satır aksiyonları ve responsive detay görünümü doğrulanmalıdır.
- Toolbar doğrulamasında arama ve araçların iki ayrı grid hücresinde değil, tek `.kirpi-table-control.input-group` içinde olduğu kontrol edilmelidir.

## Doğrulama

```powershell
docker run --rm --entrypoint php -v "${PWD}:/var/www/html" kirpicore-app /var/www/html/tests/kirpi_table_standard_test.php

foreach ($module in @('users','roles','audit','notifications')) {
    docker compose run --rm -T -v "${PWD}:/var/www/html" --entrypoint php app `
        /var/www/html/tests/kirpi_table_endpoint_smoke.php $module
}
```

Yeni tablo ekleyen modül, ilgili profil veya sunucu endpoint sözleşmesini ve gerekli regresyon testini aynı değişiklik içinde eklemelidir.
