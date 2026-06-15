# KirpiCore API v1 - Users

Bu doküman, KirpiCore `users` API endpointlerinin pratik kullanımını anlatır.

## Base URL

- Üretim: `https://core.kirpinetwork.com`

## Hızlı Test Yöntemleri

### 1) Admin panelinden API Test Merkezi

- Yol: `Yönetim -> API Test`
- Sayfa: `settings/api-test`
- Bu ekranda:
  - Method seçersin
  - Endpoint girersin
  - Bearer token eklersin
  - JSON body gönderebilirsin
  - HTTP status + response görürsün

### 2) PowerShell ile test

```powershell
$base = "https://core.kirpinetwork.com"
$token = "BURAYA_BEARER_TOKEN"

Invoke-RestMethod -Uri "$base/api/v1/me" -Headers @{ Authorization = "Bearer $token" } -Method GET
Invoke-RestMethod -Uri "$base/api/v1/users?page=1&per_page=5" -Headers @{ Authorization = "Bearer $token" } -Method GET
```

## Auth

- Header: `Authorization: Bearer <access_token>`
- Token endpoint: `POST /api/v1/auth/token`

Token alma örneği:

```bash
curl -X POST "https://core.kirpinetwork.com/api/v1/auth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@kirpi.local",
    "password": "123456",
    "token_name": "api-test",
    "scopes": ["profile:read", "users:read"]
  }'
```

Not:
- `401 Kullanıcı bilgileri hatalı.` dönülürse email/şifre yanlıştır.

## Ortak Cevap Formatı

Başarılı cevap:

```json
{
  "status": "success",
  "message": "OK",
  "data": {},
  "meta": {}
}
```

Hata cevabı:

```json
{
  "status": "error",
  "message": "Açıklayıcı hata mesajı",
  "error_code": "validation_error",
  "data": {}
}
```

Not:
- Tüm hata cevaplarında `error_code` bulunur.

## Endpointler

### 1) Kullanıcıları Listele

- Method: `GET`
- Path: `/api/v1/users`
- Permission: `users.view`
- Scope: `users:read`

Query parametreleri:

- `page` (int, default `1`)
- `per_page` (int, default `20`, max `100`)
- `search` (string)
- `role_id` (int)
- `status` (`0` veya `1`)

Örnek:

```bash
curl "https://core.kirpinetwork.com/api/v1/users?page=1&per_page=20" \
  -H "Authorization: Bearer <TOKEN>"
```

### 2) Kullanıcı Oluştur

- Method: `POST`
- Path: `/api/v1/users`
- Permission: `users.create`
- Scope: `users:create`

Body (JSON):

```json
{
  "name": "Test User",
  "email": "test.user@kirpi.local",
  "password": "123456",
  "password_confirm": "123456",
  "role_id": 2,
  "is_active": true
}
```

Notlar:

- `name`, `email`, `password` zorunludur.
- `password` min 6 karakter.
- `password_confirm` verilmezse `password` ile aynı kabul edilir.
- `role_id` opsiyonel.
- `is_active` opsiyonel (default `true`).

### 3) Kullanıcı Güncelle

- Method: `PATCH`
- Path: `/api/v1/users/{id}`
- Permission: `users.edit`
- Scope: `users:update`

Body (JSON) - en az bir alan:

```json
{
  "name": "Yeni Ad",
  "email": "yeni.mail@kirpi.local",
  "password": "newpass123",
  "password_confirm": "newpass123",
  "role_id": 2,
  "is_active": true
}
```

Notlar:

- Super Admin kullanıcı pasife alınamaz.
- Sistemde en az 1 aktif Super Admin kalacak şekilde kontrol vardır.

### 4) Kullanıcı Durumunu Güncelle

- Method: `POST`
- Path: `/api/v1/users/{id}/status`
- Permission: `users.status`
- Scope: `users:status`

Body (JSON):

```json
{
  "is_active": false
}
```

Not:

- Super Admin kullanıcı pasife alınamaz.

## Postman Collection

Aşağıdaki URL'lerden biriyle collection indirebilirsin:

- `/api/v1/postman-collection`
- `/api/v1/postman`
- `/api/v1/postman-collection.json`

Tam URL örneği:

`https://core.kirpinetwork.com/api/v1/postman-collection`

## Scope Notları

- `*` -> tüm API scope'ları açık
- `profile:read` -> `/api/v1/me`
- `users:read` -> `GET /api/v1/users`
- `users:create` -> `POST /api/v1/users`
- `users:update` -> `PATCH /api/v1/users/{id}`
- `users:status` -> `POST /api/v1/users/{id}/status`

## Sık HTTP Kodları

- `200` Başarılı
- `201` Kayıt oluşturuldu
- `401` Token yok/geçersiz/süresi dolmuş veya kimlik bilgisi yanlış
- `403` Yetki yok
- `404` Kayıt bulunamadı
- `422` Doğrulama hatası
- `429` Rate limit aşıldı
- `500` Sunucu hatası

## Throttle

API endpointleri şu limitlere tabidir:

- `THROTTLE_API_*` genel API limiti
- `THROTTLE_API_AUTH_*` token endpoint limiti

## CLI Smoke Test

Tek komutla temel API akışlarını test edebilirsin:

```bash
php shell.php api:smoke https://core.kirpinetwork.com admin@kirpi.local 123456
```

Bu komut şu kontrolleri yapar:
- Token alma (full scope)
- `GET /api/v1/me`
- `GET /api/v1/users`
- Limited token ile scope deny kontrolü (`POST /api/v1/users` -> `403 scope_denied`)

## Operasyon Dokümanları

- Release checklist: `docs/API_RELEASE_CHECKLIST.md`
- Alarm eşikleri: `docs/API_ALERT_THRESHOLDS.md`
