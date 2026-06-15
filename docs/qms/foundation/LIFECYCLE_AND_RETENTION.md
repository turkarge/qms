# Yaşam Döngüsü ve Veri Saklama Standardı

## Ortak Yaşam Döngüsü

Managed entity ortak durumları:

```text
draft -> active -> closed -> archived
```

Opsiyonel durumlar:

- `pending_approval`: onay bekleyen kayıt
- `rejected`: onaydan dönmüş kayıt
- `cancelled`: iş gerekçesiyle iptal edilmiş kayıt
- `superseded`: yeni revizyon/sürüm tarafından geçersiz kılınmış kayıt

Her domain yalnız ihtiyacı olan durumları kullanır ve izin verilen geçişleri kendi spesifikasyonunda tanımlar.

## Ortak Geçiş Kuralları

- `archived` son kullanıcı için salt okunur durumdur.
- `closed` kayıt yeniden açılacaksa özel permission, gerekçe, audit ve event gerekir.
- `rejected` kayıt doğrudan `active` olamaz; düzeltilmiş taslak/onay akışından geçer.
- `superseded` kayıt silinmez ve yeni kayda ilişki taşır.
- Durum değişikliği aynı transaction içinde audit ve domain event üretir.

## Silme Politikası

- QMS domain kayıtlarında varsayılan hard delete yasaktır.
- Taslak ve hiçbir bağımlılığı olmayan kayıtlar özel `<module>.delete` yetkisiyle soft delete edilebilir.
- Yayınlanmış, onaylanmış, kapanmış veya denetim kanıtı olmuş kayıtlar silinemez; arşivlenir veya geçersiz kılınır.
- Event store kayıtları uygulama üzerinden silinmez.
- Core Documents içindeki fiziksel dosya, aktif QMS bağlantısı varken silinemez.
- Kişisel veri silme talepleri kayıt bütünlüğünü bozmadan anonimleştirme politikasıyla ele alınır.

## Zaman Alanları

Ortak alanlar:

- `created_at`, `created_by_user_id`
- `updated_at`, `updated_by_user_id`
- `closed_at`, `closed_by_user_id`
- `archived_at`, `archived_by_user_id`
- `deleted_at`, `deleted_by_user_id` yalnız soft delete destekleniyorsa

DB zamanı UTC olarak saklanır; kullanıcı gösterimi `APP_TIMEZONE` ile yapılır.

## Saklama Sınıfları

| Sınıf | Kapsam | Varsayılan |
|---|---|---|
| `permanent` | Domain event, onay, standard snapshot, audit package manifest | Süresiz |
| `regulated` | Kontrollü doküman, CAPA, denetim, eğitim, kalibrasyon | Organizasyon politikası; başlangıç değeri 10 yıl |
| `operational` | Risk çalışma kayıtları, supplier değerlendirme ayrıntıları | Başlangıç değeri 5 yıl |
| `transient` | Preview, geçici import, başarısız execution ayrıntıları | 30-90 gün |

Saklama süresi env içine dağınık yazılmaz; ileride organization policy kaydıyla sürümlenir.

## İlk Domain Durumları

| Entity | Durumlar |
|---|---|
| Company/facility/department/team | `active`, `inactive`, `archived` |
| Ownership assignment | `active`, `expired`, `revoked` |
| Controlled document | `draft`, `pending_approval`, `published`, `superseded`, `archived` |
| Risk | `draft`, `active`, `accepted`, `mitigated`, `closed`, `archived` |
| Nonconformity | `open`, `under_review`, `capa_required`, `closed`, `archived` |
| CAPA | `draft`, `open`, `in_progress`, `verification`, `effective`, `ineffective`, `closed`, `cancelled` |
| Quality audit | `draft`, `scheduled`, `in_progress`, `completed`, `closed`, `cancelled` |
| Training record | `planned`, `assigned`, `in_progress`, `completed`, `failed`, `expired`, `cancelled` |
| Calibration record | `planned`, `due`, `in_progress`, `passed`, `failed`, `expired`, `cancelled` |

Bu liste Faz 5 domain spesifikasyonlarında ayrıntılandırılır; Faz 0'da stabil anahtar rezervasyonu sağlar.
