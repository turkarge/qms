# Mail Şablon Sistemi

KirpiCore mail altyapısında artık şablon tabanlı gönderim desteklenir.

## Genel Mimari

- Şablon tablosu: `mail_templates`
- Core render/gönderim fonksiyonları: `core/mail.php`
- Yönetim ekranı: `mail/templates`

Şablon seçenekleri:

- `template_key` (benzersiz teknik anahtar)
- `name` (gösterim adı)
- `subject` (placeholder destekli konu)
- `html_body` (placeholder destekli HTML gövde)
- `is_active` (aktif/pasif)
- `is_system` (core şablonu, silinemez)

## Varsayılan Core Şablonları

İlk kurulumda/ilk kullanımda sistem tarafından senkronlanan ana şablonlar:

- `auth.password_reset`
- `queue.test_mail`
- `mail.test_manual`
- `users.session_dropped`
- `users.lock_key_reset`

Not:

- `is_system=1` şablonlar silinemez.
- İsterseniz konu/gövde alanları güncellenebilir.

## Placeholder Kuralları

Standart değişkenler:

- `{{app_name}}`
- `{{app_url}}`
- `{{year}}`

Şablon-özel değişkenler:

- `auth.password_reset`: `{{user_name}}`, `{{reset_link}}`, `{{expires_minutes}}`
- `queue.test_mail`: `{{user_name}}`, `{{sent_at}}`
- `users.session_dropped`: `{{user_name}}`
- `users.lock_key_reset`: `{{user_name}}`

Render kuralı:

- `{{var}}` -> HTML escape uygulanır
- `{{{var}}}` -> raw HTML olarak yazılır

`mail.test_manual` şablonunda bilerek `{{{message_html}}}` kullanılır.

## TinyMCE Editor

Şablon düzenleme ekranında (`mail/templates`) `html_body` alanları TinyMCE ile açılır.

- WYSIWYG + HTML code görünümü
- Form submit öncesi editor içeriği otomatik textarea'ya yazılır
- CDN: `https://cdn.jsdelivr.net/npm/tinymce@7.2.1/tinymce.min.js`

Lisans:

- `license_key: 'gpl'` olarak ayarlıdır.

## İzinler ve Route'lar

Ekran:

- `GET mail/templates` -> `mail.view`

Actionlar:

- `POST mail/actions/template-create` -> `mail.view`
- `POST mail/actions/template-update` -> `mail.view`
- `POST mail/actions/template-delete` -> `mail.view`

Not:

- Sistem şablonları action tarafında ek kontrolle silinemez.

## Teknik Fonksiyonlar

`core/mail.php`:

- `kirpi_mail_templates_table_ready()`
- `kirpi_mail_default_templates()`
- `kirpi_mail_sync_system_templates()`
- `kirpi_mail_get_template()`
- `kirpi_mail_render_placeholders()`
- `kirpi_mail_extract_placeholders()`
- `kirpi_send_templated_mail()`

## Entegrasyon Noktaları

- Auth forgot-password maili -> `auth.password_reset`
- Queue test mail job'i -> `queue.test_mail`
- Users oturum düşürme bildirimi -> `users.session_dropped`
- Users lock key sıfırlama bildirimi -> `users.lock_key_reset`
- Mail test ekranı manuel gönderim -> `mail.test_manual`

## Sorun Giderme

### Şablon kaydederken hata

1. `Ayarlar > Eksikleri Kur` çalıştırın (`mail_templates` tablosu için)
2. Tarayıcı önbelleğini temizleyin (`Ctrl+F5`)
3. CSP nedeniyle editor script'i engelleniyor mu console'dan kontrol edin

### JSON parse hatası (`Unexpected token '﻿'`)

- `assets/js/app.js` tarafında BOM temizleme aktif.
- Action dosyalarının UTF-8 (BOM'suz) kayıtlı olduğunu doğrulayın.

### TinyMCE lisans uyarısı

- Editor init config'te `license_key: 'gpl'` ayarı bulunur.
