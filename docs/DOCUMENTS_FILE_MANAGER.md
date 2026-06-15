# Kirpi Documents File Manager Standardı

Kirpi Core içindeki dosya yönetimi `Documents` modülü üzerinden yürütülür. FilePond yalnızca kullanıcı yükleme deneyimini sağlar; güvenlik, saklama ve kayıt sahipliği sunucu tarafındaki Documents katmanının sorumluluğundadır.

## Mimari

```text
FilePond upload UI
  -> documents/actions/upload
  -> document_store_upload()
  -> uploads/documents/YYYY/MM
  -> documents kaydı
  -> document_links ilişkisi
  -> audit kaydı
```

Dosya yöneticisi iki kalıcı çalışma bölgesi ve ihtiyaç halinde açılan bir ayrıntı yüzeyinden oluşur:

- Koleksiyon gezgini: tüm dosyalar, son 30 gün, bağlantılı dosyalar ve belge türleri.
- Çalışma alanı: arama, filtre, grid/liste görünümü, seçim ve toplu işlemler.
- Ayrıntı offcanvas'ı: yalnız dosya seçildiğinde açılır; tür, MIME bilgisi, boyut, sahip, tarih ve entity bağlantılarını gösterir.

Ana çalışma yüzeyinde dashboard istatistik kartları kullanılmaz. Arama sürekli görünür; entity ve belge türü filtreleri gelişmiş filtre panelinde ihtiyaç halinde açılır.

## Entegrasyon

Bir modül dosya yükleyecekse bağımsız upload dizini oluşturmamalıdır. Aşağıdaki helper kullanılmalıdır:

```php
$result = document_upload_for_entity(
    $_FILES['document_file'],
    'report',
    'calibration',
    $calibrationId
);
```

Mevcut belgeyi bir kayda bağlamak için:

```php
document_link_existing($documentId, 'calibration', $calibrationId, 'report');
```

Belge türleri kısa, teknik ve stabil anahtarlardır. Kullanıcıya gösterilecek karşılıklar modül dil dosyasından gelmelidir.

## Güvenlik

- İstemci doğrulaması güvenlik sınırı değildir.
- MIME türü `finfo` ile sunucuda tekrar doğrulanır.
- Maksimum dosya boyutu `DOCUMENTS_MAX_UPLOAD_MB` ile yönetilir.
- Dosya adı storage adı olarak kullanılmaz; rastgele güvenli ad üretilir.
- Yükleme endpoint'i CSRF ve `documents.upload` permission kontrolünden geçer.
- Silme ve toplu silme `documents.manage` permission gerektirir.
- Yükleme, indirme ve silme işlemleri audit zincirine yazılır.
- Uygulama web root dışındaki keyfi dizinleri kullanıcıya açmaz.

## Frontend Varlıkları

FilePond varlıkları `assets/vendor/filepond/` altında sabit sürümle tutulur ve yalnız Documents sayfasında yüklenir. CDN bağımlılığı yoktur.

Kullanılan paketler:

- `filepond` 4.32.12
- `filepond-plugin-file-validate-type` 1.2.9
- `filepond-plugin-file-validate-size` 2.2.8

Sayfa davranışı `modules/documents/scripts/view.js`, görünüm uyarlamaları `assets/css/app.css` içindedir.

## Doğrulama

```bash
php tests/documents_filepond_contract_test.php
```

Canlı görsel ve işlev testleri `docs/testing/kirpi-core-ui-test-checklist.md` içindeki `CNT-10..15` maddeleriyle yapılır.
