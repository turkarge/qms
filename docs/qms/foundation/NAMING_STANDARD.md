# QMS Adlandırma Standardı

## Teknik Anahtarlar

| Alan | Kural | Örnek |
|---|---|---|
| Modül | küçük harf `snake_case` | `audit_management` |
| Tablo | çoğul `snake_case`, QMS platform tablolarında `qms_` prefix | `organization_units`, `qms_entities` |
| Entity type | tekil `snake_case` | `audit_finding` |
| Permission | `<module>.<capability>` | `organization.view` |
| Route | `<module>/<resource-or-action>` | `organization/view` |
| Event type | `<entity>.<past_tense_action>.v<major>` | `facility.activated.v1` |
| Relationship type | küçük harf `snake_case` | `supported_by` |
| Document type | kısa ve stabil `snake_case` | `audit_evidence` |
| Template key | `<module>.<purpose>` | `capa.assignment_notice` |
| AI entity key | entity type ile aynı | `controlled_document` |

## Kod ve Veritabanı

- PHP fonksiyonları `kirpi_qms_<domain>_<verb>()` veya modül özel `<module>_<verb>()` biçimindedir.
- Primary key adı `id`; foreign key adı `<entity>_id` biçimindedir.
- Boolean alanlar `is_`, durum belirtmeleri `status`, zaman alanları `_at` son ekini kullanır.
- Kod alanları `<entity>_code`, insan okunur adlar `name` veya `title` kullanır.
- JSON alanları yalnız yapısı değişken metadata/payload için kullanılır; sorgulanacak domain alanları kolon olmalıdır.
- Enum davranışı DB `ENUM` yerine doğrulanan `VARCHAR` ve uygulama sabitleriyle yönetilir.

## Event Adlandırma

Event adı geçmişte gerçekleşmiş bir olayı anlatır. Emir veya gelecekteki niyet kullanılmaz.

Doğru:

- `department.created.v1`
- `ownership.changed.v1`
- `controlled_document.published.v1`
- `capa.closed.v1`

Yanlış:

- `CreateDepartment`
- `department.create`
- `publish_document`
- `CAPAClosed` yeni event standardında kullanılmaz

## Relationship Registry

İlk ilişki anahtarları:

| Anahtar | Ters yön | Kategori |
|---|---|---|
| `belongs_to` | `contains` | direct |
| `owned_by` | `owns` | direct |
| `assigned_to` | `assigned_from` | direct |
| `related_to` | `related_to` | reference |
| `implements` | `implemented_by` | reference |
| `satisfies` | `satisfied_by` | evidence |
| `supported_by` | `supports` | evidence |
| `caused_by` | `causes` | dependency |
| `results_in` | `result_of` | dependency |
| `depends_on` | `required_by` | dependency |
| `supersedes` | `superseded_by` | lifecycle |

Yeni ilişki türü kod içinde serbest metinle oluşturulmaz; registry migration'ı gerektirir.

## Kullanıcıya Gösterilen Adlar

- Teknik anahtarlar çevrilmez.
- UI etiketleri modül `language.php` dosyasından gelir.
- Türkçe ve İngilizce anahtar setleri eşit tutulur.
- QMS ürün adı her yerde `Kirpi QMS+` olarak yazılır.
