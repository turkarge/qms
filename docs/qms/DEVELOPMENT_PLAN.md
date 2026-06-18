# Kirpi QMS+ Geliştirme Planı

## 1. Amaç

Bu plan, `docs/qms` altındaki 11 ürün mimarisi belgesini Kirpi Core geliştirme standartlarına uygun, bağımlılıkları belirlenmiş ve doğrulanabilir bir uygulama sırasına dönüştürür.

Planın ana ilkesi şudur:

> Önce organizasyon ve ortak veri omurgası, sonra operasyonel kalite modülleri, ardından uyum ve otomasyon, en son zekâ ve taşınabilirlik katmanları geliştirilir.

## 2. Kaynak Belgeler

Plan aşağıdaki mimari belgeleri kapsar:

1. Entity Architecture
2. Relationship Architecture
3. Event Architecture
4. Standards Architecture
5. Compliance Architecture
6. Governance & Ownership Architecture
7. Organization Management Architecture
8. Rules Engine Architecture
9. Event Intelligence Architecture
10. isoAI & Alfred Architecture
11. Shadow Architecture

Uygulama sırasında aşağıdaki Core standartları bağlayıcıdır:

- `docs/MODULE_MANIFEST.md`
- `docs/KIRPI_TABLE.md`
- `docs/TABLER_UI_STANDARD.md`
- `docs/DOCUMENTS_FILE_MANAGER.md`
- `docs/MAIL_TEMPLATES.md`
- `docs/architecture/kirpi-intelligence-platform.md`
- `docs/API_RELEASE_CHECKLIST.md`
- `docs/DEPLOYMENT_STANDARD.md`

## 3. Mimari Sınırlar

### 3.1 Kontrollü doküman ve dosya kaydı ayrımı

Core `documents` modülü dosya saklama ve ek registry'sidir. QMS doküman yaşam döngüsü ayrı bir `controlled_documents` domain modülünde geliştirilmelidir.

Kontrollü doküman kaydı:

- Kod, başlık, revizyon ve durum bilgilerini yönetir.
- Hazırlama, kontrol, onay, yayın ve arşiv süreçlerini yönetir.
- Fiziksel dosyaları Core `documents` modülüne `document_links` üzerinden bağlar.
- Doğrudan `uploads/` dizinine yazmaz.

### 3.2 Audit log ve event store ayrımı

Core `audit_logs` tablosu kullanıcı işlemleri ve güvenlik izi için korunur. QMS iş olayları ayrı, değiştirilemez bir event store içinde tutulur.

- Audit: Kim, hangi teknik işlemi yaptı?
- Event: İş alanında hangi anlamlı durum değişikliği gerçekleşti?

Bir işlem gerektiğinde hem audit hem domain event üretebilir.

### 3.3 Yetki ve sahiplik ayrımı

Core `roles` ve permission sistemi kullanıcının ne yapabileceğini belirler. QMS governance modeli ise kullanıcının hangi kayıt, süreç, standart veya gereklilikten sorumlu olduğunu belirler.

Ownership kayıtları Core rol kayıtlarının yerine geçmez.

### 3.4 Ortak entity modeli

Tüm domain verilerini tek ve geniş bir tabloda toplamak yerine ortak entity registry ile domain tabloları birlikte kullanılmalıdır.

- Registry: ortak kimlik, kod, başlık, durum, organizasyon bağlamı ve yaşam döngüsü.
- Domain tablosu: risk skoru, CAPA kök nedeni veya doküman revizyonu gibi alana özgü bilgiler.
- Relationship tablosu: domainler arası bağlantılar.

### 3.5 Deterministik sistem önceliği

Compliance hesapları, kural çalıştırmaları ve event korelasyonları önce açıklanabilir ve deterministik olarak uygulanır. AI bu sonuçları açıklar ve öneri üretir; kayıt kapatmaz, yayınlamaz veya onaylamaz.

## 4. Core Uyum Sözleşmesi

Her yeni modül aşağıdaki asgari teslimleri sağlamalıdır:

- `module.json`, `language.php` ve `routes.php`
- İhtiyaca göre `pages/`, `actions/`, `modals/`, `partials/` ve `scripts/`
- İdempotent `database/schema.sql`
- İdempotent `database/permissions.sql`
- RBAC kontrollü route ve action'lar
- Tutarlı JSON action cevabı: `status`, `message`, opsiyonel `data`
- Kullanıcı metinleri için eksiksiz Türkçe ve İngilizce dil anahtarları
- Liste ekranlarında KirpiTable standardı
- Büyük listelerde `ajax/<module>/datatable` sunucu endpoint'i
- Filtrelerle aynı anlamı taşıyan CSV/XLS server export'u
- Dosyalarda Core Documents entegrasyonu
- Önemli kullanıcı olaylarında notification/template entegrasyonu
- Tüm mutasyonlarda Core audit kaydı
- AI tarafından keşfedilecek güvenli veriler için `ai/schema.json`
- PHP dosyalarında UTF-8 BOM'suz kodlama
- Statik sözleşme, veritabanı ve route/permission testleri
- Light, dark, system ve mobil UI kontrolü

## 5. Önerilen Modül Haritası

### Platform ve temel modüller

- `organization`: şirket, tesis, konum, birim, pozisyon ve ekipler
- `governance`: sahiplik, sorumluluk, RACI, onay ve delegasyon
- `qms_entities`: ortak managed entity registry ve yaşam döngüsü
- `qms_relationships`: universal relationship ve evidence graph
- `qms_events`: değiştirilemez domain event store ve correlation kimliği
- `standards`: standart, sürüm, madde, gereklilik ve kontrol yapısı
- `compliance`: değerlendirme, coverage, gap ve audit readiness
- `rules`: trigger, condition, action, escalation ve execution log
- `event_intelligence`: korelasyon, trend, pattern, insight ve quality memory
- `alfred`: açıklanabilir öneri ve kullanıcıya gösterim katmanı
- `shadow`: KRP paket, snapshot, bütünlük ve salt okunur export

### Operasyonel kalite modülleri

- `controlled_documents`
- `risks`
- `nonconformities`
- `capa`
- `audit_management`
- `training`
- `competencies`
- `suppliers`
- `equipment`
- `calibration`

`audit_management` adı, mevcut Core `audit` modülüyle teknik ve kavramsal çakışmayı önler.

## 6. Uygulama Fazları

### Faz 0 - Ürün sözleşmesi ve teknik kararlar

Durum: **Foundation karar paketi tamamlandı - 2026-06-15**

Uygulama belgeleri: `docs/qms/foundation/`

Amaç: Kodlamadan önce belirsiz domain kararlarını kapatmak.

Teslimler:

- QMS terimler sözlüğü ve entity type registry
- Stabil entity, relationship ve event adlandırma standardı
- Durum makineleri için ortak sözleşme
- Silme, arşivleme ve veri saklama politikası
- Organizasyon kapsamı ve veri görünürlüğü kuralları
- Domain modülü başına permission matrisi
- Event payload sürümleme standardı
- ADR belgeleri: entity registry, event store, ownership ve controlled document ayrımları

Geçiş kriteri:

- Aynı kavram için çakışan tablo, modül veya event adı kalmamalıdır.
- Faz 1 Organization alanları ve durum geçişleri onaylanmalıdır.
- Operasyonel modüllerin MVP alanları ve ayrıntılı durum geçişleri ilgili Faz 5 dalgasına başlamadan önce onaylanmalıdır.

### Faz 1 - Organization Management

Durum: **Organization çalışma alanı tamamlandı - 2026-06-15**

Tamamlanan ilk teknik dilim:

- `organization` modül manifesti ve iki dilli sözleşme
- Company, organization unit, position ve tarih aralıklı kullanıcı atama şeması
- Merkezi Core permission katalogunda Organization yetkileri
- Organization AI schema manifesti
- İdempotent kurulum ve foundation contract testi
- Scope filtreli şirket, birim, pozisyon ve kullanıcı atama CRUD akışları
- Sunucu taraflı KirpiTable listeleri ve CSV/XLS export
- Scope filtreli organizasyon ağacı
- Audit, permission, durum ve tarih aralığı doğrulamaları
- Docker foundation/workflow testleri ve gerçek HTTP login/CRUD canlı testi

Kaynak: Bölüm 07.

Teslimler:

- Company, facility, location, department, position ve team tabloları
- Hiyerarşi ve çoklu tesis desteği
- Kullanıcı-organizasyon atamaları
- Aktif/pasif yaşam döngüsü
- Organizasyon ağacı ve KirpiTable listeleri
- `organization.view`, `organization.manage`, `organization.assign` yetkileri
- `DepartmentCreated`, `PositionAssigned`, `FacilityActivated` eventleri
- Organizasyon scope helper'ları

Geçiş kriteri:

- Her kullanıcı ve test kaydı bir company bağlamında çözümlenebilmelidir.
- Tesis ve departman bazlı görünürlük testleri geçmelidir.

### Faz 2 - Governance & Ownership

Durum: **İlk teknik dilim tamamlandı - 2026-06-15**

İlk teknik dilim:

- Ownership assignment ve tarih aralıklı delegation şeması
- Organization scope kontrollü sahiplik ve delegasyon çalışma alanı
- Core permission kataloğu ve AI schema sözleşmesi
- Otomatik delegasyon durum çözümleme ve manuel iptal akışı

Kaynak: Bölüm 06.

Teslimler:

- Ownership type ve assignment modeli
- Process, standard, requirement, document, risk ve CAPA sahipliği
- RACI sorumluluk matrisi
- Geçici delegasyon ve tarih aralığı
- Onay zinciri tanımı ve karar kayıtları
- Bağımsızlık ve çakışma kontrolleri
- `OwnershipChanged`, `RoleAssigned`, `ApprovalGranted`, `ApprovalRejected` eventleri

Geçiş kriteri:

- Yetkili olmak ile kayıt sahibi olmak testlerde ayrı davranmalıdır.
- Delegasyon sona erdiğinde sahiplik otomatik olarak asıl sorumluya dönmelidir.

### Faz 3 - Entity, Relationship ve Event omurgası

Durum: **Managed entity registry ve relationship foundation ilk teknik dilimleri tamamlandı - 2026-06-18**

Tamamlanan entity dilimi:

- Stabil entity type registry ve ortak managed entity tablosu
- Company/entity type/yıl kapsamlı transaction-safe Numbering Engine
- Domain modülleri için managed entity register helper API
- Organization scope kontrollü registry ve type listeleri
- Arşivleme, permission, audit ve AI schema sözleşmesi
- Universal relationship type registry ve managed entity ilişki tablosu
- Direct, reference, evidence ve dependency ilişki sınıfları
- Scope kontrollü ilişki CRUD, KirpiTable liste ve AI schema sözleşmesi

Kaynak: Bölüm 01, 02 ve 03.

Teslimler:

- Managed entity registry
- Domain entity type registry
- Ortak organizasyon, owner, status ve zaman alanları
- Universal relationship modeli
- Direct, reference, evidence ve dependency ilişki türleri
- Event store, payload version, actor ve organization context
- Correlation ve causation kimlikleri
- Entity timeline ve relationship explorer
- Event retention ve arşiv politikası
- Ortak helper API'leri

Geçiş kriteri:

- Örnek entity oluşturma, ilişkilendirme ve event üretme işlemi tek transaction sınırında doğrulanmalıdır.
- Event kayıtları uygulama action'larıyla güncellenememeli veya silinememelidir.
- Relationship endpoint'leri yetkisiz entity bilgisini sızdırmamalıdır.

### Faz 4 - Standards Engine

Kaynak: Bölüm 04.

Teslimler:

- Standard ve standard version kayıtları
- Clause, requirement ve control hiyerarşisi
- Requirement mapping API'si
- Çoklu standart eşleştirmesi
- Sürüm karşılaştırma veri modeli
- Requirement değişiklik ve transition plan altyapısı
- Standart içerik import/export formatı

Geçiş kriteri:

- Aynı standardın iki sürümü bağımsız tutulabilmelidir.
- Bir QMS kaydı birden fazla requirement ile ilişkilendirilebilmelidir.
- Standart içeriği kod içine gömülmemelidir.

### Faz 5 - Operasyonel kalite MVP

Kaynak: Bölüm 01, 02, 04, 05, 06 ve 07.

Bu faz dalgalar halinde uygulanır.

#### Dalga 5A - Kontrollü doküman ve kanıt

- Kontrollü doküman yaşam döngüsü
- Revizyon, onay, yayın ve arşiv
- Core Documents üzerinden dosya ve evidence bağlantısı
- Requirement-document-evidence zinciri
- Doküman yayınlandığında eğitim ihtiyacı event'i

#### Dalga 5B - Risk, uygunsuzluk ve CAPA

- Risk kayıtları ve deterministik skorlama
- Uygunsuzluk ve finding kayıtları
- CAPA açma, aksiyon, doğrulama ve kapatma akışı
- Root cause ve effectiveness check
- Risk, finding, CAPA, requirement ve evidence ilişkileri

#### Dalga 5C - Denetim yönetimi

- Denetim programı, planı, kapsamı ve ekipleri
- Checklist, finding ve raporlar
- Denetçi bağımsızlık kontrolü
- Finding-CAPA-evidence-requirement zinciri

#### Dalga 5D - İnsan, tedarikçi ve ekipman

- Eğitim ve yetkinlik
- Tedarikçi kayıtları ve performans girdileri
- Ekipman ve kalibrasyon yaşam döngüsü
- Süresi yaklaşan ve geçen kayıt eventleri

Her dalga için önce ayrı domain spesifikasyonu hazırlanmalıdır. Mevcut mimari belgeler bu modüllerin ayrıntılı alanlarını, durum makinelerini ve kabul kriterlerini tek başına tanımlamaz.

Geçiş kriteri:

- Her domain kaydı organization, ownership, relationship ve event katmanlarına bağlı olmalıdır.
- Dosya kullanan hiçbir domain bağımsız upload altyapısı oluşturmamalıdır.
- Liste, export, audit, notification ve permission testleri geçmelidir.

### Faz 6 - Compliance Center

Kaynak: Bölüm 05.

Teslimler:

- Requirement evaluation ve compliance status
- Compliant, partially compliant, non-compliant, not evaluated ve excluded durumları
- Belge, kanıt, eğitim, risk, denetim ve CAPA coverage ölçümleri
- Gap analysis
- Audit readiness görünümü
- Cross-standard evidence reuse
- Skor formülü sürümleme ve açıklama kaydı
- `ComplianceGapDetected`, `RequirementSatisfied`, `EvidenceMissing`, `AuditReadinessChanged` eventleri

Geçiş kriteri:

- Her compliance sonucu kaynak kayıtlarla açıklanabilmelidir.
- Aynı girdiler aynı skor ve durum sonucunu üretmelidir.
- Hariç tutma kararları yetki, gerekçe ve audit kaydı gerektirmelidir.

### Faz 7 - Rules Engine

Kaynak: Bölüm 08.

Teslimler:

- Event listener ve rule registry
- Sürüm kontrollü condition modeli
- Allowlist tabanlı action registry
- Notification, assignment, escalation ve compliance action'ları
- Öncelik, aktiflik ve çakışma politikası
- Execution log, retry ve idempotency anahtarı
- Rule template'leri
- Dry-run ve rule test ekranı

Geçiş kriteri:

- Kurallar keyfi PHP veya SQL çalıştıramamalıdır.
- Aynı event tekrar işlendiğinde çift görev veya bildirim oluşmamalıdır.
- Her sonuç event, rule version, koşul ve action seviyesinde açıklanabilmelidir.

### Faz 8 - Event Intelligence ve Quality Memory

Kaynak: Bölüm 09.

Teslimler:

- Event correlation kuralları
- Zaman pencereli trend analizleri
- Tekrarlayan pattern tespiti
- Insight ve recommendation kayıtları
- Confidence ve evidence explanation modeli
- Lessons learned, historical decision ve incident memory kayıtları
- Operational, compliance, audit, risk ve supplier insight kategorileri

İlk sürüm deterministik analizlerle başlamalıdır. Model tabanlı analiz daha sonra KIP gateway üzerinden eklenmelidir.

Geçiş kriteri:

- Her insight hangi event ve entity kayıtlarından üretildiğini göstermelidir.
- Confidence skoru formülü veya model kaynağı izlenebilir olmalıdır.
- Kullanıcı yetkisi dışında kalan kayıtlar insight açıklamasına sızmamalıdır.

### Faz 9 - isoAI ve Alfred

Kaynak: Bölüm 10 ve Core KIP mimarisi.

Teslimler:

- Trigger tabanlı Alfred görünürlüğü
- Recommendation presentation API'si
- Writing assistant ve record quality analysis
- Audit, CAPA ve risk yardım yüzeyleri
- Memory keeper entegrasyonu
- Explainable recommendation görünümü
- Kullanıcı feedback ve dismiss kayıtları
- Provider-independent KIP gateway entegrasyonu
- PII masking ve data minimization kontrolleri

Kısıtlar:

- Alfred onay vermez, kayıt kapatmaz, yayınlamaz ve imza atmaz.
- External runtime varsayılan olarak kapalı kalır.
- AI erişimi mevcut RBAC ve AI schema permission sınırlarını aşamaz.
- Secret veya hassas alanlar prompt, response ya da audit kayıtlarına yazılmaz.

Geçiş kriteri:

- Her öneri neden, kaynak kayıtlar, confidence ve model/engine bilgisi göstermelidir.
- AI kapalıyken temel QMS süreçleri eksiksiz çalışmalıdır.

### Faz 10 - Shadow Architecture

Kaynak: Bölüm 11.

Teslimler:

- Sürüm kontrollü KRP package manifesti
- `.belge.krp`, `.audit.krp`, `.shadow.krp` ve `.training.krp` profilleri
- Audit package generator
- Compliance snapshot
- Relationship preservation
- Hash, signature ve package verification
- Salt okunur offline viewer
- Package access, expiry ve revocation politikası
- Felaket kurtarma kullanım sınırları

Geçiş kriteri:

- Paket içeriği kaynak snapshot ile hash seviyesinde doğrulanmalıdır.
- Paket değiştirilirse doğrulama başarısız olmalıdır.
- Offline viewer hiçbir mutasyon yeteneği sunmamalıdır.
- Shadow paketi veritabanı yedeğinin yerine geçtiği izlenimini vermemelidir.

## 7. Yatay İş Paketleri

Her fazda aşağıdaki işler ayrıca yürütülür:

### Güvenlik

- RBAC ve organizasyon scope birlikte uygulanır.
- POST action'larında CSRF doğrulanır.
- SQL sıralama ve filtreleri allowlist kullanır.
- Hassas alanlar AI schema içinde işaretlenir veya yayınlanmaz.
- Approval, exclusion, delegation ve package işlemleri audit edilir.

### Bildirim ve şablon

- Teknik metinler action içine gömülmez.
- Notification metadata içinde `source_module`, `entity_type` ve `entity_id` bulunur.
- E-posta gereken akışlar Template/Mail registry üzerinden çalışır.

### API

- UI action'ları tamamlanmadan genel API yüzeyi açılmaz.
- API endpoint'leri panelle aynı permission ve scope kurallarını kullanır.
- Ortak cevap, throttle, audit ve release checklist uygulanır.

### Gözlemlenebilirlik

- Hatalar domain event olarak değil uygulama logu olarak tutulur.
- Kural, compliance, intelligence ve AI çalışmaları kendi execution kayıtlarına sahip olur.
- Health endpoint'e yalnız anlamlı ve düşük maliyetli kontroller eklenir.

## 8. Test Stratejisi

Her modül için asgari test paketi:

1. Manifest, route ve permission sözleşme testi
2. İdempotent schema kurulum testi
3. CRUD ve durum geçiş testleri
4. Yetkisiz erişim ve organization scope testleri
5. Audit, event ve notification yan etki testleri
6. KirpiTable endpoint ve export eşitliği testi
7. Documents link ve dosya güvenliği testi
8. AI schema kalite testi
9. Light/dark/system ve mobil görsel kontrol
10. Docker üzerinde temiz veritabanı kurulum testi

Faz sonu production kapısı:

- `git diff --check`
- PHP lint ve repo testleri Docker içinde başarılı
- `docker compose config --quiet`
- Temiz volume ile kurulum
- `/healthz` yanıtında app ve db `ok`
- Kritik kullanıcı akışları için manuel smoke test
- Dokploy ortamında migration ve rollback notları

## 9. Teslimat Sırası

Önerilen yayın grupları:

1. Foundation Release: Faz 0-3
2. Standards Release: Faz 4
3. Operational QMS MVP: Faz 5A-5C
4. Operational Expansion: Faz 5D
5. Compliance Release: Faz 6
6. Automation Release: Faz 7
7. Intelligence Release: Faz 8-9
8. Shadow Release: Faz 10

Her yayın grubu bağımsız deploy edilebilir ve önceki grubun çalışan davranışını bozmadan feature flag veya module enable/disable mekanizmasıyla devreye alınabilir olmalıdır.

## 10. İlk Uygulama Backlog'u

Kod geliştirmeye başlanacak ilk sıra:

1. Faz 0 karar belgeleri ve QMS sözlük dosyası
2. `organization` modül manifesti, schema ve permission'ları
3. Organization listesi, ağaç görünümü ve atama akışları
4. Organization scope helper ve testleri
5. `governance` ownership/delegation temeli
6. `qms_entities` registry ve helper API
7. `qms_relationships` modeli ve entity relationship paneli
8. `qms_events` store, event publisher ve timeline
9. Foundation entegrasyon ve temiz kurulum testi
10. Standards Engine domain spesifikasyonu

Bu backlog tamamlanmadan Compliance, Rules Engine, Event Intelligence, Alfred veya Shadow katmanlarında production kodu başlatılmamalıdır.

## 11. Definition of Done

Bir iş yalnız aşağıdaki koşullarda tamamlanmış sayılır:

- Domain kabul kriterleri karşılanmıştır.
- Permission ve organization scope uygulanmıştır.
- Audit ve gerekli domain eventleri üretilmektedir.
- Dil anahtarları Türkçe ve İngilizce tamamdır.
- Liste ve export Core standardına uygundur.
- Dosya ihtiyacı Documents Registry üzerinden çözülmüştür.
- Testler Docker ortamında geçmektedir.
- Modül kullanıcı akışını doğrulamak için idempotent demo/test verisi seed edilmiştir veya mevcut QMS demo seed kapsamına eklenmiştir.
- Teknik ve kullanıcı dokümanı güncellenmiştir.
- Dokploy deployment sonrası health ve smoke test başarılıdır.
