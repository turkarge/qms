# Kirpi Core Deployment Standardı

Bu standart, aynı Docker sunucusunda birden fazla Kirpi Core tabanlı uygulamanın container, network, volume, port, veritabanı ve session çakışması olmadan çalışmasını sağlar.

## 1. Uygulama Kimliği

Her uygulama benzersiz ve stabil bir prefix kullanır:

```env
KIRPI_APP_PREFIX=kirpicore
```

Yeni kurulumda bu tek değer otomatik olarak Compose proje adını, `DB_NAME`, session cookie adını, network ve volume adlarını üretir. Bu türetilen değerleri env içinde tekrar tanımlamayın.

Prefix kuralları:

- 2-32 karakter olmalıdır.
- Küçük harf, rakam, tire ve alt çizgi kullanılabilir.
- Sunucudaki başka bir uygulamayla aynı olmamalıdır.
- Uygulama yayına çıktıktan sonra değiştirilmemelidir.

## 2. Docker Kaynakları

Yeni kurulumlarda aşağıdaki kaynak adları prefix'ten üretilir:

```text
<prefix>_network
<prefix>_mysql_data
<prefix>_uploads
<prefix>_logs
```

Kaynak adları gerektiğinde açıkça sabitlenebilir:

```env
KIRPI_NETWORK_NAME=kirpicore_network
KIRPI_DB_VOLUME_NAME=kirpicore_mysql_data
KIRPI_UPLOADS_VOLUME_NAME=kirpicore_uploads
KIRPI_LOGS_VOLUME_NAME=kirpicore_logs
```

`container_name` kullanılmaz. Container adlarını Compose proje adı yönetir; servis discovery adları `app` ve `db` olarak stabil kalır.

## 3. Port Standardı

Production ve Dokploy kurulumu yalnız `docker-compose.yml` kullanır. Host port publish edilmez:

- Proxy hedef servis: `app`
- Proxy hedef port: `80`
- Uygulama içi DB adresi: `db:3306`

Yerel kullanımda override dosyası eklenir:

```env
KIRPI_APP_HTTP_PORT=8080
KIRPI_DB_HOST_PORT=3306
```

```powershell
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

İkinci uygulama örneği `8081` ve `3307` gibi farklı host portları kullanmalıdır.

## 4. Mevcut Dokploy Kurulumunu Taşıma

Bu bölüm mevcut veriyi korumak için zorunludur. Yeni compose dosyasını deploy etmeden önce kullanılan volume adlarını tespit edin.

Önce container adlarını bulun:

```bash
docker ps --format '{{.Names}}'
```

DB container mount bilgisi:

```bash
docker inspect <DB_CONTAINER> --format '{{range .Mounts}}{{println .Destination "=" .Name}}{{end}}'
```

App container mount bilgisi:

```bash
docker inspect <APP_CONTAINER> --format '{{range .Mounts}}{{println .Destination "=" .Name}}{{end}}'
```

Çıktıdaki gerçek adları Dokploy Environment Settings alanına yazın:

```env
KIRPI_APP_PREFIX=kirpicore
SESSION_COOKIE_NAME=KIRPICORESESSID

KIRPI_DB_VOLUME_NAME=<var/lib/mysql için görünen volume adı>
KIRPI_UPLOADS_VOLUME_NAME=<var/www/html/uploads için görünen volume adı>
KIRPI_LOGS_VOLUME_NAME=<var/www/html/logs için görünen volume adı>
KIRPI_NETWORK_NAME=kirpicore_network
```

Volume adlarını doğrulamadan deploy etmeyin. Yanlış veya boş bir legacy volume override değeri uygulamanın yeni ve boş bir veritabanıyla açılmasına neden olabilir.

Session cookie adı değiştiğinde kullanıcıların bir kez yeniden giriş yapması beklenir.

## 5. Yeni Uygulama Kurulumu

Yeni bir Core tabanlı uygulamada volume override değişkenleri gerekli değildir:

```env
KIRPI_APP_PREFIX=kalibre
APP_NAME="Kirpi Kalibre"
BASE_URL=https://kalibre.example.com
DB_PASS=CHANGE_WITH_A_STRONG_PASSWORD
```

Bu değerlerle DB adı `kalibre`, session cookie adı sürüm ekiyle `KALIBRESESSID...` ve Docker kaynakları `kalibre_*` adlarıyla otomatik oluşturulur.

## 6. Doğrulama

Yerel config izolasyon testi:

```powershell
.\scripts\validate-deployment.ps1
```

Beklenen sonuç:

```text
Result : PASS
```

Deploy sonrası:

```bash
curl -fsS https://alan-adiniz/healthz
```

Beklenen cevapta hem `app` hem `db` değeri `ok` olmalıdır.

## 7. Güvenlik Notları

- `DB_PASS`, `MAIL_PASSWORD`, API key ve token değerleri repoya yazılmaz.
- `AUTO_WEB_SETUP=false` production varsayılanıdır.
- `SETUP_KEY` setup kapalıyken boş bırakılabilir; setup açılacaksa güçlü ve geçici bir değer kullanılmalıdır.
- Dokploy production kurulumunda `KIRPI_APP_HTTP_PORT` ve `KIRPI_DB_HOST_PORT` tanımlanmaz.
