# Kirpi Intelligence Platform (KIP)

## Core Hazırlık Notu

KIP geliştirmesine geçmeden önce Kirpi Core tarafında aşağıdaki yapısal standartlar tamamlandı:

- AI schema registry temeli ve standart modüllerde `ai/schema.json` yayınlama modeli.
- Yetki kontrollü schema discovery.
- Schema discovery için JSON/CSV/XLS export standardı.
- Ortak notification metadata modeli.
- Template Registry ve Documents Registry.
- Server-side CSV/XLS export standardı.
- Notifications, Documents, Audit, Users, Roles, Mail, Backup, Settings, Template, Queue, API Metrics, Health ve Security modüllerinde export entegrasyonu.
- Roles için Permission Catalog ve Role-Permission Matrix export.
- Standart yönetim aksiyonlarında metadata'lı notification event üretimi.
- Template Registry üzerinde notification target, variable ve default content katalogları.
- UTF-8 çeviri standardı.
- Tema, layout ve PWA temeli.

Bu hazırlıklar KIP Faz 2 ve sonrası için veri keşfi, yetki sınırları, event analizi ve rapor üretimi altyapısını standart hale getirir.

## Uygulanan Core Durumu - 2026-06-04

KIP geliştirmesi öncesindeki Core hazırlık zinciri tamamlandı:

```text
Manifest
  -> Sync
  -> Discovery
  -> Export
  -> Quality Gate
```

Tamamlanan uygulama başlıkları:

* Standart modüller `modules/{module}/ai/schema.json` manifestleriyle schema yayınlar.
* `ai/actions/sync-schema` manifestleri `ai_schema_entities` ve `ai_schema_fields` tablolarına senkronize eder.
* Discovery ekranı modül, entity, tablo, yetki, arama, limit, filterable-only ve sensitive filtrelerini destekler.
* `ai/actions/export-schema` schema bilgisini JSON/CSV/XLS formatlarında dışarı verir.
* `ai/actions/export-quality` schema kalite raporunu JSON/CSV/XLS formatlarında dışarı verir.
* Schema Quality Gate eksik açıklama, eksik yetki, fieldsız entity ve olası hassas alan uyarılarını üretir.
* AI öncesi kapanışta tüm standart modüller schema manifest kapsamına alındı.
* Son doğrulama sonucu `34 entity / 294 field / 4655 index / 0 hata` ve kalite sonucu `0 uyarı / 0 hata` olarak kaydedildi.

Son doğrulama sonucu:

```text
Schema sync: 23 entity, 207 field, 0 hata
Schema quality: 24 uyarı, 0 hata
Health check: app/db ok
```

Sonraki çalışma, kalite uyarılarındaki gürültüyü azaltmak ve gerçek hassas alan işaretlerini netleştirmektir.

## Kirpi Intelligence v1.0 Kapanış Durumu - 2026-06-12

Kirpi Intelligence modülü Core için güvenli AI preview/gateway katmanı olarak `v1.0.0` seviyesinde tamamlandı.

Tamamlanan v1.0 kapsamı:

* Schema Registry, metadata index, Discovery ve Quality Gate
* RBAC sınırlarına göre Query Planner
* OpenAI ve OpenAI-compatible provider adapter yönetimi
* Env veya Settings referanslı secret politikası
* Global ve adapter seviyesinde runtime kill-switch
* Yapılandırılmış JSON çıktı isteği ve uyumsuz provider fallback akışı
* Reasoning/prose temizleyen ve yalnız SQL candidate kabul eden response parser
* Planner tabanlı izinli tablo ve izinli alan doğrulaması
* Read-only SQL Guard, SQL Preview ve kontrollü EXPLAIN kapısı
* AI Audit zinciri ve maskelenmiş Debug JSON export
* Provider JSON sözleşme testi
* Parser ve SQL güvenlik regresyon testleri

Doğrulanan güvenli akış:

```text
Doğal Dil Sorusu
  -> Query Planner
  -> Provider Gateway
  -> Structured SQL Candidate
  -> Table + Field Guard
  -> SQL Preview
  -> Audit
```

v1.0 güvenlik sınırı:

* Üretilen SQL doğrudan çalıştırılmaz.
* Gerçek veri okunmaz.
* `SELECT *`, izin dışı tablo, izin dışı alan, DDL/DML, `UNION`, subquery ve riskli ifadeler bloklanır.
* Secret veya ham provider cevabı audit/debug çıktısına yazılmaz.
* Gerçek SQL execution, Tool Registry, Needle, Event Intelligence ve Insight Engine sonraki bağımsız ürün fazlarıdır; Kirpi Intelligence v1.0 kapanışını engellemez.

Regresyon testi:

```bash
php tests/ai_sql_safety_test.php
```

---

## Amaç

Kirpi Core içerisine entegre edilecek ortak bir yapay zeka ve bilgi erişim platformu geliştirmek.

Bu platformun amacı chatbot üretmek değildir.

Amaç;

* Doğal dil ile veri sorgulama
* Kurumsal veri analizi
* Yetkili veri erişimi
* İş süreçlerinin gözlemlenmesi
* Tool Calling
* Event Driven Intelligence
* Gelecekteki isoAI altyapısının oluşturulması

olarak belirlenmiştir.

---

# Temel Prensipler

## 1. AI ürünlerin içinde değil Core içerisinde bulunmalıdır

Yanlış yaklaşım:

```text
Kalibre+ -> AI
HR+ -> AI
CMS+ -> AI
QDMS -> AI
```

Doğru yaklaşım:

```text
Kalibre+
HR+
CMS+
QDMS
      ↓
Kirpi Intelligence Platform
      ↓
Kirpi Core
```

Bütün ürünler aynı AI altyapısını kullanmalıdır.

---

## 2. Model bağımlılığı oluşturulmamalıdır

Sistem herhangi bir modele bağlı olmamalıdır.

Desteklenmesi planlanan modeller:

* Needle
* Qwen
* OpenAI
* Gemini
* Claude
* DeepSeek

Model değişikliği ürünleri etkilememelidir.

---

## 3. Veri hiçbir zaman doğrudan dış modellere gönderilmemelidir

Temel güvenlik prensibi:

```text
Database
   ↓
Kirpi Core
   ↓
AI Gateway
   ↓
LLM
```

Asla:

```text
LLM
 ↓
Database
```

olmamalıdır.

---

## 4. AI mevcut RBAC sistemini aşamaz

AI;

* Yetki kontrolü yapamaz
* Yetki atlayamaz
* Kullanıcının erişemediği veriyi göremez

Bütün sorgular mevcut izin sisteminden geçmelidir.

---

# Sistem Mimarisi

```text
Kirpi Intelligence Platform

├── Schema Registry
├── Vector Search
├── Query Engine
├── Tool Registry
├── Model Manager
├── AI Gateway
├── AI Permissions
├── AI Audit Log
├── Event Bus
└── Insight Engine
```

---

# Faz 1 — Schema Registry

## Amaç

Sistemdeki tüm modüllerin veri yapılarının merkezi olarak tanımlanması.

Örnek:

```json
{
  "module": "calibration",
  "entity": "device",
  "table": "devices",
  "description": "Calibration devices",
  "fields": [
    {
      "name": "serial_number",
      "description": "Device serial number"
    },
    {
      "name": "next_calibration_date",
      "description": "Next calibration date"
    }
  ]
}
```

Her modül kendi şemasını yayınlayacaktır.

Örnek modüller:

* Kalibre+
* HR+
* CMS+
* QDMS
* Teklifbaz

## Discovery Export Standardı

KIP servisleri schema bilgisini Core üzerinden dışarı almalıdır.

Standart endpoint:

```text
ai/actions/export-schema?format=json
```

Desteklenen formatlar:

* JSON
* CSV
* XLS

Export mevcut discovery filtrelerini kullanır:

* `module`
* `entity`
* `table`
* `permission`
* `discovery_q`
* `filterable_only`
* `include_sensitive`
* `limit`

Hassas alanlar varsayılan olarak dışarı verilmez. `include_sensitive=1` yalnızca `ai.schema.manage` yetkisi olan kullanıcılar için çalışır.

## Schema Quality Gate

KIP Faz 2 metadata indeksleme öncesinde schema kayıtları kalite kontrolünden geçmelidir.

Kontrol edilen başlıklar:

* Eksik entity açıklaması
* Eksik permission slug
* Aktif field içermeyen entity
* Eksik field açıklaması
* Eksik field tipi
* Hassas olabilecek ama `is_sensitive` işaretlenmemiş alanlar

Hassas alan adayları substring ile değil, alan adı desenleriyle taranır. Amaç `action_key`, `module_key`, `metadata_json`, `route_path` gibi teknik metadata alanlarında gürültü üretmemektir.

```text
password, passwd,
token_hash, access_token, refresh_token, secret_token, private_token, api_token,
secret_key, private_key, api_key, access_key, secret_value,
email, *_email, email_address, ip_address,
file_path, storage_path, absolute_path,
payload_json, details_json, data_json,
body, request_body, response_body, html_body,
user_agent,
password_hash, token_hash, secret_hash
```

Kalite raporu endpoint'i:

```text
ai/actions/export-quality?format=json
```

Bu endpoint yalnızca `ai.schema.manage` yetkisi olan kullanıcılar tarafından kullanılmalıdır.

---

# Faz 2 — Vector Search

## Amaç

Doğal dil ile ilgili veri yapılarının bulunması.

Örnek:

Kullanıcı:

```text
Bu ay geciken kalibrasyonlar
```

Vektör arama sonucu:

```text
Calibration
Device
Due Date
Completed Date
Customer
```

ilgili entity'ler döndürülür.

Bu aşamada sadece metadata indekslenir.

Gerçek veriler indekslenmez.

## Uygulanan Metadata Index Standardı

Core tarafında ilk metadata index katmanı `ai_schema_index` tablosu ile uygulanır.

İndeks kaynakları:

* Modül anahtarı
* Entity anahtarı
* Tablo adı
* Entity açıklaması
* Entity alias ve keyword metadata alanları
* Hassas olmayan field adı
* Hassas olmayan field tipi
* Hassas olmayan field açıklaması
* Hassas olmayan field alias ve keyword metadata alanları

Hassas field metadata'sı index'e yazılmaz.

Schema sync sonrası index otomatik yeniden üretilir:

```text
ai/schema.json
  -> ai_schema_entities / ai_schema_fields
  -> ai_schema_index
```

Schema search öncelikle `ai_schema_index` üzerinden çalışır. Index hazır değilse sistem discovery fallback moduna döner.

Search sonucu aşağıdaki ek bilgileri döndürür:

* `mode`: `metadata_index` veya `discovery_fallback`
* `matched_terms`
* `matched_sources`
* field bazında `matched_terms`

---

## Uygulanan Query Plan Preview Standardı

Faz 3'e geçmeden önce güvenli ara katman olarak `Query Planner` uygulanır.

Bu katman doğal dil sorusunu SQL üretmeden metadata tabanlı bir plana dönüştürür:

```text
Soru
 ↓
Schema Search
 ↓
Query Plan Preview
 ↓
Aday Entity / Field / Yetki Listesi
```

Plan çıktısı:

* Birincil aday entity
* Aday tablolar
* Önerilen field listesi
* Yetki slug'ı
* Eşleşen terimler ve kaynaklar
* Güvenlik notları

Query Planner gerçek veri okumaz ve SQL üretmez. Üretilen plan sadece kullanıcı onayı ve sonraki SQL Guard aşaması için ön hazırlıktır.

Her plan önizleme denemesi `query_plan_preview` aksiyonu ile AI audit zincirine yazılır.

---

# Faz 3 — Query Engine

## Amaç

Doğal dil sorgularını güvenli SQL sorgularına dönüştürmek.

Akış:

```text
Soru
 ↓
Schema Search
 ↓
LLM
 ↓
SQL Generation
 ↓
SQL Guard
 ↓
Database
 ↓
Result
 ↓
Summary
```

---

## SQL Guard

Tehlikeli sorgular engellenmelidir.

İzin verilen:

```sql
SELECT
```

Engellenen:

```sql
DELETE
UPDATE
DROP
ALTER
TRUNCATE
```

İlk sürüm yalnızca READ ONLY çalışmalıdır.

## Uygulanan Read-only SQL Guard Standardı

Core tarafında ilk SQL Guard katmanı sıkı read-only modda uygulanır.

Guard sadece sorguyu denetler; SQL çalıştırmaz.

Bloklanan durumlar:

* Boş SQL
* `SELECT` dışında başlayan sorgular
* Noktalı virgül ve çoklu statement riski
* SQL yorumları
* `DELETE`, `UPDATE`, `INSERT`, `DROP`, `ALTER`, `TRUNCATE`, `CREATE`, `GRANT`, `REVOKE` ve benzeri DDL/DML komutları
* `UNION`
* `FROM (...)` veya `JOIN (...)` subquery kullanımı
* `information_schema`, `mysql`, `performance_schema`, `sys` gibi sistem şemaları
* `INTO OUTFILE`, `INTO DUMPFILE`, `LOAD_FILE`, `SLEEP`, `BENCHMARK` gibi riskli ifadeler
* İzinli tablo listesi verildiğinde liste dışı tablo kullanımı

Guard çıktısı:

* `allowed`
* `status`
* `reason`
* `reasons`
* `tables`
* `allowed_tables`

Her guard kontrolü `sql_guard_check` aksiyonu ile AI audit zincirine yazılır.

Query Planner çıktısındaki `allowed_tables` ve `allowed_fields` alanları sonraki aşamada model tarafından üretilecek SQL'in guard tarafından plana göre doğrulanmasını sağlar.

## Planner → Guard Bağlantısı

Query Planner ve SQL Guard birlikte güvenli üretim öncesi kontrol zinciri olarak çalışır.

```text
Doğal Dil Sorusu
 ↓
Query Planner
 ↓
Guard Context
 ↓
SQL Guard
 ↓
Allowed / Blocked
```

Guard Context:

* Kullanıcı sorusu
* İzinli tablo listesi
* Tablo bazlı izinli field listesi

Planner ekranı bu context'i görünür şekilde gösterir ve SQL Guard ekranına aktarır. SQL Guard ekranı context'i form gönderimleri boyunca korur.

Bu birleşik akış hâlâ SQL üretmez ve SQL çalıştırmaz. Ama sonraki Text-to-SQL aşamasında üretilecek SQL için net doğrulama sınırını hazırlar.

## SQL Preview / Dry Run Standardı

SQL Preview katmanı model SQL üretimine geçmeden önceki son güvenli tampon olarak uygulanır.

```text
Planner Context
 ↓
Manuel veya model kaynaklı SQL adayı
 ↓
SQL Guard
 ↓
SQL Preview
 ↓
Preview Allowed / Blocked
```

SQL Preview:

* SQL çalıştırmaz.
* `EXPLAIN` çalıştırmaz.
* Gerçek veri okumaz.
* Guard sonucunu gösterir.
* Yakalanan tabloları gösterir.
* İzinli tablo ve field sınırlarını gösterir.
* Bloklama nedenlerini gösterir.

Preview akışı `sql_preview_check` aksiyonu ile AI audit zincirine yazılır. Guard seviyesi `sql_guard_check`, Preview seviyesi ise üst akış olarak ayrı izlenir.

Bu standarttan sonra model SQL üretimi devreye alınsa bile çıktı doğrudan çalıştırılmaz; önce SQL Preview üzerinden Guard kararına bağlanır.

## SQL Candidate Review Standardı

Model SQL üretimine geçmeden önce candidate formatı standart hale getirilir.

Candidate yapısı:

* `question`
* `planner_context`
* `candidate_sql`
* `model_adapter`
* `confidence`
* `warnings`
* `generated_at`
* `execution_enabled`
* `preview_required`

İlk uygulama model çağırmaz. Kullanıcı manuel SQL adayını girer; sistem bu girdiyi model çıktısı ile aynı yapıya dönüştürür.

```text
Planner Context
 ↓
SQL Candidate
 ↓
SQL Preview
 ↓
SQL Guard
 ↓
Preview Allowed / Blocked
```

Candidate Review SQL çalıştırmaz. Üretilen aday yalnızca Preview ekranına aktarılır.

Her candidate inceleme denemesi `sql_candidate_review` aksiyonu ile AI audit zincirine yazılır.

## Mock SQL Generation Adapter ve Prompt Builder

Gerçek model adapter bağlanmadan önce SQL üretim sözleşmesi mock adapter ile doğrulanır.

Prompt Builder yalnızca aşağıdaki bilgileri kullanır:

* Kullanıcı sorusu
* İzinli tablo listesi
* Tablo bazlı izinli field listesi
* Güvenlik kuralları

Prompt'a gerçek veri, hassas field, kullanıcı credential bilgisi veya tablo dışı schema bilgisi eklenmez.

Prompt Builder çıktısı:

* `prompt`
* `prompt_hash`
* `allowed_tables`
* `allowed_fields`
* `safety_rules`

Mock adapter:

* Adapter key: `mock-sql-generator`
* Provider: `mock`
* Type: `sql_generation`
* External: `false`
* Enabled: `true`

Mock adapter gerçek model çağrısı yapmaz. İlk izinli tabloyu ve o tabloya ait izinli field listesini kullanarak basit bir `SELECT` adayı üretir. Üretilen SQL yine doğrudan çalıştırılmaz; Candidate Review ve SQL Preview zincirine aktarılır.

Mock generation audit aksiyonu:

```text
sql_candidate_generate
```

Bu aşama gerçek model entegrasyonuna geçmeden önce adapter sözleşmesini, prompt sınırlarını ve güvenlik zincirini test etmek için kullanılır.

## SQL Generation Gateway Standardı

SQL candidate üretimi tek gateway fonksiyonu üzerinden yönetilir:

```php
kirpi_ai_generate_sql_candidate($question, $context, $adapterKey)
```

Gateway sorumlulukları:

* Adapter kaydını bulmak
* Adapter aktif mi kontrol etmek
* SQL üretimi için adapter tipinin `sql_generation` olduğunu doğrulamak
* External adapter config kontrolü yapmak
* Mock adapter için güvenli mock üretimi çalıştırmak
* Runtime bağlanmamış adapter'ları bloklamak
* Üretim çıktısını Candidate Review standardında döndürmek

Güvenli blok durumları:

* `adapter_not_found`
* `adapter_disabled`
* `adapter_type_not_supported`
* `external_adapter_not_configured`
* `external_runtime_disabled`

Gateway hiçbir durumda SQL çalıştırmaz. Üretilen veya bloklanan her sonuç Preview/Guard zincirine bağlı kalır.

External adapter gerçek modele bağlanmadan önce `config_json` içinde güvenli key referansı tanımlanmalıdır. Secret değerleri doğrudan audit veya prompt içine yazılmaz.

External runtime kapısı varsayılan olarak kapalıdır:

```text
AI_EXTERNAL_MODEL_RUNTIME_ENABLED=false
```

Secret referansları:

```json
{
  "api_key_env": "OPENAI_API_KEY"
}
```

veya:

```json
{
  "api_key_ref": "ai.openai.api_key"
}
```

Runtime kapalıyken gateway `external_runtime_disabled` sonucu verir. Bu sayede gerçek provider implementasyonu eklenmeden önce adapter registry, secret referans politikası ve audit davranışı production ortamda güvenli kalır.

## Provider Runtime Standardı

İlk gerçek runtime katmanı `openai` ve `openai_compatible` provider tiplerini destekler.

Runtime girişleri:

* Kullanıcı sorusu
* Planner context içindeki izinli tablo listesi
* Planner context içindeki izinli field listesi
* Adapter registry kaydı

Runtime kuralları:

* Runtime çağrısı yalnız `AI_EXTERNAL_MODEL_RUNTIME_ENABLED=true` ve adapter `runtime_enabled=true` olduğunda yapılır.
* Adapter aktif değilse çağrı yapılmaz.
* Adapter tipi `sql_generation` değilse çağrı yapılmaz.
* Secret değeri yalnız `api_key_env` veya `api_key_ref` ile çözülür.
* Secret değeri audit, prompt, notification veya response içine yazılmaz.
* Provider yanıtı JSON veya düz SQL metninden `SQL Candidate` standardına dönüştürülür.
* Üretilen SQL hiçbir koşulda doğrudan çalıştırılmaz.
* Candidate her zaman Preview + Guard zincirine bağlı kalır.

Desteklenen provider blok durumları:

* `provider_runtime_not_supported`
* `provider_base_url_missing`
* `provider_model_missing`
* `provider_request_failed`

OpenAI-compatible provider için `config_json` örneği:

```json
{
  "base_url": "https://example-provider.local/v1",
  "model": "sql-safe-model",
  "api_key_env": "PROVIDER_API_KEY",
  "timeout_seconds": 30,
  "temperature": 0,
  "max_tokens": 700
}
```

## Provider Ayar Yönetimi

Provider production aktivasyonu için operasyonel ayarlar arayüzden yönetilir.

Arayüzden yönetilen alanlar:

* Adapter aktif/pasif durumu
* Adapter runtime onayı
* Provider tipi
* Model adı
* OpenAI-compatible base URL
* Timeout
* Temperature
* Max tokens
* Secret kaynağı (`api_key_ref` veya `api_key_env`)
* `api_key_ref` kullanıldığında secret değerinin `app_settings` tablosuna secret olarak yazılması

Env içinde kalan kritik ayarlar:

* `AI_EXTERNAL_MODEL_RUNTIME_ENABLED`
* `AI_SQL_EXPLAIN_ENABLED`
* Env tabanlı secret kullanılacaksa ilgili API key env değişkeni

Global runtime kapısı kapalıyken adapter arayüzden aktif edilse bile provider çağrısı yapılmaz. Bu standart, production ortamda son onayı deploy/env seviyesinde tutar.

## Provider Canlı Test Standardı

Provider Ayarları ekranında canlı bağlantı testi bulunur.

Test kuralları:

* Test yalnız `ai.adapters.manage` yetkisi ile çalışır.
* Test yalnız `AI_EXTERNAL_MODEL_RUNTIME_ENABLED=true` ve adapter `runtime_enabled=true` olduğunda provider çağrısı yapar.
* Test SQL üretmez.
* Test veri okumaz.
* Test yalnız küçük bir JSON cevap isteği gönderir.
* Secret değeri request dışındaki hiçbir kayıt, response, notification veya audit detayına yazılmaz.
* Ham provider cevabı kullanıcıya veya audit'e yazılmaz.
* Sonuç `provider_runtime_test` olarak genel audit ve AI audit zincirine işlenir.

## Controlled EXPLAIN Gate

SQL Preview içinde kontrollü `EXPLAIN` kapısı bulunur.

Varsayılan davranış:

```text
AI_SQL_EXPLAIN_ENABLED=false
```

Bu durumda `EXPLAIN` çalıştırılmaz ve sonuç `explain_disabled` olarak döner.

Explain akışı:

```text
SQL Candidate
 ↓
SQL Preview
 ↓
SQL Guard
 ↓
Explain Gate
 ↓
explain_disabled / guard_blocked / success
```

Kurallar:

* Guard geçmeden `EXPLAIN` çalışmaz.
* Normal SQL execution her durumda kapalı kalır.
* `EXPLAIN` açılsa bile gerçek veri okunmaz.
* `EXPLAIN` sonucu yalnızca plan bilgisidir.
* Hata veya kapalı durumları audit ve preview çıktısında neden kodu ile izlenir.

İleride production ortamda açılması gerekirse yalnızca kontrollü env/config ile açılmalıdır:

```text
AI_SQL_EXPLAIN_ENABLED=true
```

## KIP Query Flow Ekranı

KIP Query Flow ekranı güvenlik zincirinin tek kullanıcı arayüzüdür.

```text
Question
 ↓
Query Planner
 ↓
Guard Context
 ↓
SQL Generation Gateway
 ↓
SQL Candidate
 ↓
SQL Preview
 ↓
SQL Guard
 ↓
Explain Gate
```

Bu ekran:

* Planner sonucunu gösterir.
* Guard Context sınırlarını gösterir.
* Gateway üzerinden candidate üretir.
* Candidate SQL'i gösterir.
* Preview, Guard ve Explain Gate kararlarını gösterir.
* SQL execution yetkisi eklemez.
* Gerçek veri okumaz.
* Audit zincirini görünür hale getirir.

Query Flow, ayrı teknik ekranları kaldırmaz. Teknik ekranlar hata ayıklama ve parça parça doğrulama için korunur; Query Flow ise ana güvenli kullanım yüzüdür.

## Menü Sadeleştirme Standardı

Kirpi Intelligence yönetim menüsünde yalnızca ana kullanım girişleri gösterilir:

* Dashboard
* Query Flow
* Schema Discovery
* Audit Log

Teknik ekranlar menüde gösterilmez, ancak route olarak korunur ve Dashboard içindeki Teknik Araçlar bölümünden erişilir:

* Query Planner
* Schema Quality
* SQL Candidate
* SQL Preview
* SQL Guard

Bu ayrım, günlük kullanımda menü karmaşasını azaltır ve güvenlik zincirinin parçalarını gerektiğinde erişilebilir tutar.

---

# Faz 4 — AI Gateway

## Amaç

Ürünlerin model bağımsız çalışması.

Ürünler:

```php
AI::ask($question);
```

çağırır.

Model seçimini Core yapar.

Örnek:

```text
Basit veri sorgusu
↓
Text-to-SQL
```

```text
Basit analiz
↓
Qwen Local
```

```text
Derin analiz
↓
OpenAI / Gemini
```

---

# Faz 5 — Tool Registry

## Amaç

Modüllerin fonksiyonlarını AI tarafından çağrılabilir hale getirmek.

Örnek:

```json
{
  "name": "create_calibration",
  "description": "Create calibration task"
}
```

```json
{
  "name": "list_overdue_devices",
  "description": "List overdue devices"
}
```

Her modül kendi araçlarını yayınlar.

---

# Faz 6 — Needle Integration

Needle ana sohbet modeli olarak kullanılmayacaktır.

Needle'ın görevi:

* Intent Detection
* Tool Selection
* Function Calling

Örnek:

Kullanıcı:

```text
Kalibrasyonu geçen cihazları getir
```

Needle:

```json
{
  "tool": "list_overdue_devices"
}
```

üretir.

---

# Faz 7 — Local Intelligence Layer

Yerel çalışan küçük modeller.

Önerilen:

* Qwen3 0.6B
* Qwen3 1.7B

Görevleri:

* Özetleme
* Trend analizi
* Rapor yorumlama
* İçgörü üretimi

Bu katman CPU üzerinde çalışabilmelidir.

---

# Faz 8 — External Intelligence Layer

Sadece gerekli durumlarda kullanılacaktır.

Örnek:

* Kapsamlı rapor üretimi
* Uzun dönem trend analizi
* Karmaşık kalite değerlendirmeleri

Bu katmanda:

* OpenAI
* Gemini
* Claude

kullanılabilir.

---

# Veri Güvenliği Stratejisi

## Veri Minimizasyonu

Dış modele sadece gerekli veri gönderilir.

Yanlış:

```text
Müşteri adı
Personel adı
Telefon
E-posta
Tam kayıtlar
```

Doğru:

```json
{
  "late_devices": 18,
  "late_percentage": 12.4
}
```

---

## PII Masking

Maskeleme uygulanmalıdır.

Örnek:

```text
CUSTOMER_42
USER_17
DEVICE_91
```

---

## AI Data Firewall

Kirpi Core içerisinde bir güvenlik katmanı bulunmalıdır.

Görevleri:

* Veri maskeleme
* Prompt filtreleme
* Çıktı filtreleme
* DLP kontrolleri

---

# AI Audit Log

Bütün AI işlemleri kayıt altına alınmalıdır.

Kayıt örnekleri:

```text
Kim sordu?
Ne sordu?
Hangi model kullanıldı?
Hangi sorgu çalıştı?
Hangi sonuç döndü?
```

Kurumsal izlenebilirlik sağlanmalıdır.

---

# Gelecek Vizyonu — isoAI

Uzun vadeli hedef:

Kullanıcının sürekli soru sorması yerine sistemin olayları gözlemlemesi.

Örnek:

```json
{
  "event": "calibration_overdue",
  "days": 14
}
```

Insight Engine:

```text
Bu durum son 3 ayda artış göstermektedir.
Kök neden analizi önerilir.
```

şeklinde öneriler üretebilir.

---

# İlk 90 Gün Yol Haritası

## Sprint 1

* Core AI Module
* Schema Registry
* AI Audit Log
* Model Adapter Interface

## Sprint 2

* Vector Search
* Entity Discovery
* Metadata Indexing

## Sprint 3

* Text-to-SQL
* SQL Guard
* Result Summarization

## Sprint 4

* Qwen Local Integration
* AI Gateway

## Sprint 5

* Tool Registry
* Needle Integration

---

# Sonuç

Kirpi Intelligence Platform;

bir chatbot sistemi değil,

Kurumsal Bilgiye Erişim,
Kurumsal İçgörü Üretimi,
Tool Calling,
Event Intelligence
ve gelecekteki isoAI altyapısını sağlayan Core seviyesinde stratejik bir platform olarak konumlandırılacaktır.
