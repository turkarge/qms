# Kirpi Core

Kurumsal, modüler ve Docker-odaklı PHP yönetim altyapısı.

Bu doküman, Kirpi Core sistemini yerel ortamda ve Dokploy üzerinden üretim ortamında çalıştırmak için tek kaynak kurulum kılavuzudur.

## 1. Genel Bakış

Kirpi Core modülleri:

- `auth`
- `dashboard`
- `users`
- `roles`
- `profile`
- `notifications`
- `mail`
- `audit`
- `settings`
- `queue`
- `backup`
- `health`
- `throttle`
- `api`
- `ai`
- `template`
- `documents`

Yapı özellikleri:

- Modül manifest desteği (`modules/<module>/module.json`)
- Dinamik modül etkinleştirme/devre dışı bırakma
- Web tabanlı setup + shell tabanlı setup
- API token ve scope yönetimi
- Backup/restore, health metrics, throttle ve audit log
- Server-side CSV/XLS export standardı
- DataTables 2 tabanlı ortak KirpiTable liste ve rapor standardı
- Template, Documents ve Notifications registry altyapısı
- PWA temeli

## 2. Ön Koşullar

- Docker + Docker Compose
- Dokploy (üretim kurulumu için)
- MySQL 8.x (compose ile gelir)
- Domain + TLS (üretim ortamı için önerilir)

## 3. Hızlı Başlangıç (Yerel Docker)

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

Erişim:

- Uygulama: `http://localhost:8080`
- DB host (container içi): `db:3306`

Yerel host portları `.env` içindeki `KIRPI_APP_HTTP_PORT` ve `KIRPI_DB_HOST_PORT` ile değiştirilebilir. Production Compose host port yayınlamaz.

İlk kurulum:

- `http://localhost:8080/setup.php`

## 4. Dokploy ile Kurulum

### 4.1 Uygulama Oluşturma

1. Dokploy panelinde yeni bir **Compose Application** oluşturun.
2. Repository olarak bu projeyi seçin.
3. Compose file olarak `docker-compose.yml` belirleyin.

Not:

- Dokploy domain/proxy kullanımında servis `app`, port `80` seçilmelidir.
- `docker-compose.yml` host port publish etmez; sadece container iç portu `80` olarak bildirir.

### 4.2 Environment Settings

Aşağıdaki değerleri Dokploy `Environment Settings` alanına giriniz.

### Zorunlu Değişkenler

```env
KIRPI_APP_PREFIX=kirpicore
APP_NAME="Kirpi Core"
APP_VER=1.0.15
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Europe/Istanbul
APP_DEFAULT_ROUTE=dashboard/view
BASE_URL=https://core.kirpinetwork.com
APP_TRUST_PROXY=true
APP_LOCALE=tr

DB_HOST=db
DB_PORT=3306
DB_APP_HOST=db
DB_APP_PORT=3306
DB_USER=root
DB_PASS=CHANGE_ME
AUTO_DB_INSTALL=true
DB_SSL_MODE=DISABLED

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_mail@example.com
MAIL_PASSWORD=CHANGE_ME
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_mail@example.com
MAIL_FROM_NAME="Kirpi Core"
```

`DB_NAME`, `SESSION_COOKIE_NAME`, Compose proje adı, network ve volume adları `KIRPI_APP_PREFIX` üzerinden otomatik üretilir. Yeni kurulumda bunları tanımlamayın.

Mevcut bir Dokploy kurulumunu bu standarda geçiriyorsanız deploy öncesinde mevcut DB, uploads ve logs volume adlarını env içine sabitlemeniz zorunludur. Ayrıntılı ve veri kayıpsız geçiş adımları için [Deployment Standardı](docs/DEPLOYMENT_STANDARD.md) belgesini izleyin.

### Güvenlik ve Session

```env
SESSION_COOKIE_DOMAIN=
SESSION_IDLE_TIMEOUT_SECONDS=7200
SESSION_ID_ROTATE_SECONDS=900
SECURITY_HEADERS_ENABLED=true
AUTH_LOGIN_COVER_IMAGE=https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1400&q=80
```

### Kaynak İzolasyonu

Yeni kurulumlarda aşağıdaki adlar `KIRPI_APP_PREFIX` üzerinden otomatik üretilir. Mevcut kurulumlarda gerçek legacy volume adları yazılmalıdır:

```env
KIRPI_NETWORK_NAME=
KIRPI_DB_VOLUME_NAME=
KIRPI_UPLOADS_VOLUME_NAME=
KIRPI_LOGS_VOLUME_NAME=
```

### Kurulum ve Otomatik DB Kontrolleri

```env
AUTO_WEB_SETUP=false
SETUP_KEY=
AUTO_DB_ENSURE_MISSING=false
AUTO_DB_ENSURE_INDEXES=true
```

Not:

- `AUTO_WEB_SETUP=true` yaparsanız `setup.php` web arayüzü üretimde açık olur.
- Üretim için setup tamamlandıktan sonra `AUTO_WEB_SETUP=false` önerilir.

### Backup Ayarları

```env
BACKUP_RETENTION_COUNT=20
BACKUP_VERIFY_DRY_RUN=true
BACKUP_INCLUDE_SYSTEM_TABLES=false
```

### Throttle Ayarları

```env
THROTTLE_ENABLED=true
THROTTLE_LOGIN_LIMIT=5
THROTTLE_LOGIN_WINDOW=600
THROTTLE_LOGIN_BLOCK=900
THROTTLE_CRITICAL_LIMIT=10
THROTTLE_CRITICAL_WINDOW=60
THROTTLE_CRITICAL_BLOCK=120
THROTTLE_GLOBAL_POST_LIMIT=180
THROTTLE_GLOBAL_POST_WINDOW=60
THROTTLE_GLOBAL_POST_BLOCK=60
THROTTLE_API_LIMIT=120
THROTTLE_API_WINDOW=60
THROTTLE_API_BLOCK=120
THROTTLE_API_AUTH_LIMIT=10
THROTTLE_API_AUTH_WINDOW=300
THROTTLE_API_AUTH_BLOCK=600
```

### API Ayarları

```env
API_TOKEN_TTL_SECONDS=2592000
API_REQUEST_LOG_RETENTION_DAYS=90
```

### 4.3 Dağıtım

Dağıtım sonrası container açılış akışı:

1. DB bağlantısını bekler
2. `php shell.php db:install` ile core + modül şemalarını kurar
3. Uygulamayı Apache ile ayağa kaldırır

Sağlık uç noktası:

- `GET /healthz`

## 5. Kurulum (Web ve CLI)

### Web Kurulumu

- URL: `/setup.php`
- Beklenen: kurulum anahtarı + admin bilgileri
- Sonuç: temel tablolar, roller, admin kullanıcısı

### CLI Kurulumu

```bash
php shell.php db:install
```

Parçalı komutlar:

```bash
php shell.php db:create
php shell.php db:core:install
php shell.php db:modules:install
php shell.php db:status
php shell.php db:tables
```

## 6. Yönetim Menüsü ve Kritik Modüller

- `settings/view`: genel ayarlar + eksik tablo/index kurulum aracı
- `settings/modules`: modül yönetimi (enable/disable, bağımlılık kontrolü)
- `settings/menu-management`: menü yönetimi (manifestten gelen menü öğeleri, ağırlık ve yerleşim görünümü)
- `security/view`: güvenlik izleme paneli
- `health/view`: sağlık + metrikler paneli
- `backup/view`: backup/restore/download/delete
- `queue/view`: iş kuyruğu yönetimi
- `mail/test` ve `mail/templates`: posta testi + şablon yönetimi

Menü davranış standardı:

- `Dashboard` her zaman ilk sıradadır (sabit).
- `Yönetim` her zaman son sıradadır (sabit).
- Diğer menüler `modules/<module>/module.json` içindeki `menu` alanından üretilir.
- `placement=management` ve `group=monitoring` olan öğeler Yönetim altında `Monitoring / İzleme` grubuna alınır.
- Menü etiketleri için `title_key` + `<module>_lang()` kullanımı önerilen standarttır.

Core liste/export standardı:

- Liste export endpointleri `<module>/actions/export` formatındadır.
- CSV/XLS çıktıları `core/export.php` helper'ları ile üretilir.
- Export butonları gerçek link olarak çalışır; JavaScript varsa mevcut filtreleri linke ekler.
- Notifications, Documents, Audit, Users ve Roles export standardına geçirilmiştir.
- Roles modülünde Permission Catalog ve Role-Permission Matrix export bulunur.

## 7. REST API (v1)

Temel rota: `api/v1`

Temel endpointler:

- `POST /api/v1/auth/token`
- `GET /api/v1/me`
- `GET /api/v1/users`
- `POST /api/v1/users`
- `PATCH /api/v1/users/{id}`
- `POST /api/v1/users/{id}/status`

Ek endpointler:

- `GET /api/v1`
- `GET /api/v1/postman-collection`
- `GET /api/v1/postman`
- `GET /api/v1/postman-collection.json`

Dokümanlar:

- `docs/API_USERS.md`
- `docs/API_RELEASE_CHECKLIST.md`
- `docs/API_ALERT_THRESHOLDS.md`

Smoke test:

```bash
php shell.php api:smoke https://core.kirpinetwork.com admin@kirpi.local 123456
```

## 8. Backup ve Restore

CLI:

```bash
php shell.php backup:create [label]
php shell.php backup:restore <backup_id>
php shell.php backup:verify <backup_id>
php shell.php backup:cleanup [keep_count]
```

Panel:

- Oluştur
- Doğrula (checksum + dry-run)
- Geri Yükle
- İndir
- Sil

## 9. Sorun Giderme

### 9.1 "Güvenlik doğrulaması başarısız oldu"

Neden:

- Eski/yanlış çerez
- Domain değişikliği sonrası oturum uyumsuzluğu

Çözüm:

1. Tarayıcı çerezini temizleyin
2. `SESSION_COOKIE_DOMAIN` değerini boş bırakın (önerilen)
3. Yeniden dağıtın
4. Tekrar giriş yapın

### 9.2 MySQL kapsayıcısı sağlıklı değil

Logda `unknown variable 'default-authentication-plugin=mysql_native_password'` görülüyorsa:

- MySQL 8.4 ile uyumsuz eski başlatma argümanlarını kaldırın.
- DB birimi bozuk/eski ise temiz birim ile yeniden başlatın.

### 9.3 Yükleme dizini oluşturulamadı

Kontrol edin:

- `uploads`
- `uploads/avatars`
- `logs`
- `storage`

Kapsayıcı kullanıcısının (`www-data`) yazma izni olmalı.

## 10. Güvenlik Önerileri (Üretim)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `AUTO_WEB_SETUP=false`
- `SETUP_KEY` boş veya döndürülmüş
- Güçlü `DB_PASS`
- Güçlü SMTP şifresi / uygulama şifresi
- Ters proxy arkasında `APP_TRUST_PROXY=true`
- Düzenli yedekleme + geri yükleme testi

## 11. İlgili Dokümanlar

- [SETUP.md](SETUP.md)
- [docs/MODULE_MANIFEST.md](docs/MODULE_MANIFEST.md)
- [docs/KIRPI_TABLE.md](docs/KIRPI_TABLE.md)
- [docs/ROADMAP.md](docs/ROADMAP.md)
- [docs/API_USERS.md](docs/API_USERS.md)
- [docs/MAIL_TEMPLATES.md](docs/MAIL_TEMPLATES.md)

## 12. Notlar

- Modül mimarisinde `module.json` geriye uyumlu tasarlanmıştır; olmayan modüller yine çalışır.
- Çekirdek modül bağımlılıkları nedeniyle bazı modüller devre dışı bırakılamaz.
- Projedeki tüm PHP dosyaları `UTF-8 (BOM'suz)` formatta tutulmalıdır.
