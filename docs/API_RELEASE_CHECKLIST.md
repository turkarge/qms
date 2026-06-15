# KirpiCore API Release Checklist

Bu doküman, API değişikliklerini üretim ortamına güvenli şekilde almak için kullanılır.

## 1) Pre-Release (Kod Hazırlık)

- [ ] Endpoint değişiklikleri `docs/API_USERS.md` içinde güncellendi.
- [ ] Yeni hata senaryoları için `error_code` tanımları eklendi.
- [ ] Scope değişiklikleri (`profile:read`, `users:*`) doğrulandı.
- [ ] `php -l` ile değişen PHP dosyalarında syntax kontrolü temiz.
- [ ] Gerekli DB şema dosyaları (`modules/*/database/*.sql`) güncellendi.

## 2) Dağıtım Sonrası Teknik Kontrol

- [ ] `Ayarlar -> Eksikleri Kur` çalıştırıldı.
- [ ] API root endpoint: `GET /api/v1` -> `200`.
- [ ] Postman collection endpoint: `GET /api/v1/postman-collection` -> `200`.
- [ ] API Metrics sayfası açılıyor: `api/metrics`.

## 3) Smoke Test

- [ ] CLI smoke komutu başarılı:

```bash
php shell.php api:smoke https://core.kirpinetwork.com admin@kirpi.local <SIFRE>
```

- [ ] Beklenen adımlar:
  - full-scope token alınır
  - `/api/v1/me` ve `/api/v1/users` 200 döner
  - limited token ile `POST /api/v1/users` -> `403 scope_denied`

## 4) Operasyonel Kontrol (ilk 24 saat)

- [ ] `Yönetim -> API Metrics` ekranında 5xx artışı yok.
- [ ] 401/403 oranları beklenen seviyede.
- [ ] 429 oranında beklenmeyen sıçrama yok.
- [ ] Ortalama response süresi kabul edilebilir seviyede.

## 5) Rollback Kararı (Gerekirse)

- [ ] 5xx oranında kalıcı yükseliş varsa rollback planına geç.
- [ ] Kritik endpointlerde sürekli 401/403 varsa scope/permission değişikliklerini geri al.
- [ ] DB migration kaynaklı problemede son güvenli versiyonuna dön + yedekten doğrula.
