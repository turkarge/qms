# Kirpi Core Setup Guide

Bu doküman, Kirpi Core sisteminin lokal ve Dokploy üretim ortamında standart kurulum akışını tanımlar.

## 1. Kurulum Modları

- Lokal Docker kurulumu (geliştirme/test)
- Dokploy üzerinden production kurulumu
- CLI ile veritabanı kurulum/onarım işlemleri

## 2. Lokal Kurulum (Docker)

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

Erişim:

- Uygulama: `http://localhost:8080`
- Setup: `http://localhost:8080/setup.php`

## 3. Dokploy Kurulumu

### 3.1 Uygulama Oluşturma

1. Dokploy panelinde yeni `Compose Application` oluşturun.
2. Repo olarak Kirpi Core reposunu seçin.
3. Compose file: `docker-compose.yml`.

### 3.2 Environment Settings

Aşağıdaki blok, Dokploy için referans production baseline'dir.

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

# Mevcut Dokploy kurulumunda gerçek volume adları zorunludur.
KIRPI_NETWORK_NAME=
KIRPI_DB_VOLUME_NAME=
KIRPI_UPLOADS_VOLUME_NAME=
KIRPI_LOGS_VOLUME_NAME=

SESSION_COOKIE_DOMAIN=
SESSION_IDLE_TIMEOUT_SECONDS=7200
SESSION_ID_ROTATE_SECONDS=900
SECURITY_HEADERS_ENABLED=true
AUTH_LOGIN_COVER_IMAGE=https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1400&q=80

DB_HOST=db
DB_PORT=3306
DB_USER=root
DB_PASS=CHANGE_ME
AUTO_DB_INSTALL=true
DB_SSL_MODE=DISABLED
AUTO_DB_ENSURE_MISSING=false
AUTO_DB_ENSURE_INDEXES=true

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_mail@example.com
MAIL_PASSWORD=CHANGE_ME
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_mail@example.com
MAIL_FROM_NAME="Kirpi Core"

AUTO_WEB_SETUP=false
SETUP_KEY=

BACKUP_RETENTION_COUNT=20
BACKUP_VERIFY_DRY_RUN=true
BACKUP_INCLUDE_SYSTEM_TABLES=false

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

API_TOKEN_TTL_SECONDS=2592000
API_REQUEST_LOG_RETENTION_DAYS=90
```

Yeni kurulumda `DB_NAME`, `SESSION_COOKIE_NAME`, `COMPOSE_PROJECT_NAME` ve Docker kaynak adlarını tanımlamayın. Bu değerler `KIRPI_APP_PREFIX` üzerinden otomatik üretilir.

### 3.3 Deploy Sonrası Akış

Container açılışında sistem:

1. DB bağlantısını bekler
2. `php shell.php db:install` çalıştırır
3. Uygulamayı ayağa kaldırır

Saglik endpoint:

- `GET /healthz`

## 4. Web Setup (Opsiyonel)

`AUTO_WEB_SETUP=true` iken:

- `/setup.php` üzerinden setup key ile kurulum yapılabilir.

Güvenlik önerisi:

- Kurulum bittikten sonra `AUTO_WEB_SETUP=false`
- `SETUP_KEY` değerini boşaltın veya değiştirin

## 5. CLI Setup ve Bakım Komutları

Tüm kurulum:

```bash
php shell.php db:install
```

Parçalı kurulum:

```bash
php shell.php db:create
php shell.php db:core:install
php shell.php db:modules:install
```

Tek modül kurulumu:

```bash
php shell.php db:modules:install notifications
```

Durum kontrol:

```bash
php shell.php db:status
php shell.php db:tables
```

## 6. Doğrulama Checklist

- `KIRPI_APP_PREFIX` bu uygulamaya özel ve stabil mi?
- Mevcut kurulumda üç legacy volume adı env içine doğru yazıldı mı?
- `BASE_URL` doğru mu?
- `DB_*` bilgileri doğru mu?
- `MAIL_*` bilgileri doğru mu?
- `APP_ENV=production` ve `APP_DEBUG=false` mi?
- `/healthz` endpoint'i `200` dönüyor mu?
- Admin girişi ve temel sayfalar açılıyor mu?
- PHP dosyalari `UTF-8 (BOM'suz)` mu? (ozellikle `language.php` ve layout dosyalari)

## 7. Sık Karşılaşılan Sorunlar

### "Güvenlik doğrulaması başarısız oldu"

1. Tarayıcı cookie temizleyin
2. `SESSION_COOKIE_DOMAIN` değerini boş bırakın
3. Yeniden deploy edin

### MySQL unhealthy / privilege table hataları

- Eski/uyumsuz MySQL startup argümanlarını kaldırın
- Bozuk/eski DB volume kullanıyorsanız temiz volume ile yeniden başlatın

### "Yükleme dizini oluşturulamadı"

- `uploads`, `uploads/avatars`, `logs`, `storage` dizinlerini ve yazma izinlerini kontrol edin

## 8. Ilgili Dokumanlar

- [README.md](README.md)
- [docs/DEPLOYMENT_STANDARD.md](docs/DEPLOYMENT_STANDARD.md)
- [docs/MODULE_MANIFEST.md](docs/MODULE_MANIFEST.md)
- [docs/API_USERS.md](docs/API_USERS.md)
- [docs/MAIL_TEMPLATES.md](docs/MAIL_TEMPLATES.md)
