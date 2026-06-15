# QMS Numbering Engine Standardı

## Amaç

QMS kayıt kodları modül action'larında elle birleştirilmez. Kod üretimi company, entity type, dönem ve sıra bilgisini kullanan merkezi ve transaction-safe Numbering Engine üzerinden yapılır.

## Kod Biçimi

Varsayılan şablon:

```text
{company_code}-{entity_prefix}-{year}-{sequence:5}
```

Örnekler:

- `KRP-DOC-2026-00001`
- `KRP-RSK-2026-00012`
- `KRP-CAPA-2026-00007`
- `KRP-AUD-2026-00003`

## İlk Prefix Registry

| Entity type | Prefix |
|---|---|
| `controlled_document` | `DOC` |
| `risk` | `RSK` |
| `nonconformity` | `NC` |
| `capa` | `CAPA` |
| `quality_audit` | `AUD` |
| `audit_finding` | `FND` |
| `training_record` | `TRN` |
| `supplier` | `SUP` |
| `equipment` | `EQP` |
| `calibration_record` | `CAL` |

Organization kayıtları insan tarafından yönetilen stabil code alanları kullanır; otomatik sequence zorunlu değildir.

## Sequence Scope

Varsayılan sıra kapsamı:

```text
company + entity_type + calendar_year
```

Gerekirse facility kapsamı template ayarıyla açılabilir. Yayına alınmış bir sequence scope geriye dönük değiştirilmez.

## Teknik Kurallar

- Sequence tahsisi DB transaction ve row lock ile atomik yapılır.
- Üretilmiş kod yeniden kullanılmaz.
- Kayıt iptal veya soft delete edilse bile sıra geri verilmez.
- Manuel kod yalnız özel permission ve benzersizlik kontrolüyle mümkündür.
- Kod değişikliği yayınlanmış/kapanmış kayıtta yasaktır.
- Template ve son sıra değerleri organization policy olarak sürümlenir.
- Preview işlemi sıra tüketmez.

## Önerilen Platform Yapısı

`qms_number_sequences`:

- `company_id`
- `facility_id` nullable
- `entity_type`
- `period_key`
- `template`
- `last_sequence`
- `updated_at`

Unique key: company, facility, entity type ve period birleşimi.

Numbering Engine ilk managed entity altyapısıyla birlikte `qms_entities` modülünde ortak helper olarak uygulanacaktır.
