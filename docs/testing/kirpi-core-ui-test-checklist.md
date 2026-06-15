# Kirpi Core Ekran Test Kontrol Listesi

Test tarihi: `2026-06-13`
Test eden: `Ramazan ÖZKAYNAK`
Ortam / URL: `https://core.kirpinetwork.com`
Commit / sürüm: `a00b93a` (deployment standardizasyonu öncesi uygulama baseline)
Tarayıcı ve cihaz: `Google Chrome Sürüm 149.0.7827.115 (Resmi Derleme) (64 bit) | Windows 11 Pro Build 26200.8655`

## Kullanım

Her testin başına aşağıdaki sonuçlardan birini yazın:

- `[x]` Başarılı
- `[!]` Hatalı
- `[-]` Test edilemedi
- `[ ]` Henüz test edilmedi
- `[N/A]` Bu ortamda uygulanamaz

Hatalı veya test edilemeyen her madde için bölüm sonundaki not alanına test kodunu, hata mesajını ve mümkünse ekran görüntüsü adını yazın.

## 1. Genel Shell ve Dashboard

- `[x] GEN-01` Login ekranı açılıyor; logo, metinler ve form taşmadan görünüyor.
- `[x] GEN-02` Login ekranında Light, Dark ve System tema seçimleri çalışıyor.
- `[x] GEN-03` Geçerli kullanıcıyla giriş başarılı; hatalı şifre doğru uyarıyı gösteriyor.
- `[x] GEN-04` Dashboard hatasız açılıyor; kartlar ve hızlı bağlantılar çalışıyor.
- `[x] GEN-05` Yönetim menüsü grupları doğru sırada açılıp kapanıyor.
- `[x] GEN-06` Kullanıcı menüsündeki Light, Dark ve System seçimleri tüm sayfalara uygulanıyor.
- `[x] GEN-07` Geniş ve dar görünüm seçimi çalışıyor ve sayfa değişiminde korunuyor.
- `[x] GEN-08` Profil bağlantısı, bildirim dropdown'u ve çıkış işlemi çalışıyor.
- `[x] GEN-09` AI balonu açılıyor, kapanıyor ve ekran içeriğiyle çakışmıyor.
- `[x] GEN-10` Mobil görünümde menü, tablolar, formlar ve butonlar kullanılabilir durumda.
- `[x] GEN-11` Türkçe karakterler doğru; bozuk `Ä`, `Å`, `Ã` karakterleri görünmüyor.
- `[x] GEN-12` Tarayıcı konsolunda kritik JavaScript hatası oluşmuyor.

Notlar: `Mobil menü açma/kapatma, dışarı tıklama ve Esc davranışları düzeltildi ve doğrulandı.`

## 2. Kullanıcılar, Roller ve Profil

- `[x] ACC-01` Kullanıcı listesi açılıyor; arama, filtre ve sayfalama çalışıyor.
- `[x] ACC-02` CSV ve XLS kullanıcı export dosyaları indiriliyor ve içerikleri doğru.
- `[x] ACC-03` Test kullanıcısı oluşturma, düzenleme ve aktif/pasif işlemleri çalışıyor.
- `[x] ACC-04` Kullanıcı oturum düşürme ve lock key sıfırlama işlemleri doğru uyarı veriyor.
- `[x] ACC-05` Rol listesi açılıyor; arama, filtre ve sayfalama çalışıyor.
- `[x] ACC-06` Rol oluşturma, düzenleme ve aktif/pasif işlemleri çalışıyor.
- `[x] ACC-07` Permission Catalog ve Role-Permission Matrix ekranları doğru yükleniyor.
- `[x] ACC-08` Rol izinleri güncelleniyor ve ilgili kullanıcı erişimine yansıyor.
- `[x] ACC-09` Rol ve yetki export dosyaları indiriliyor ve içerikleri doğru.
- `[x] ACC-10` Profil bilgileri güncelleniyor; tema/layout tercihleri korunuyor.
- `[x] ACC-11` API token oluşturma ve iptal işlemleri çalışıyor; token sonradan açık gösterilmiyor.
- `[x] ACC-12` Ekran kilitleme, kilit açma ve logout akışları çalışıyor.

Notlar: `Kullanıcı aramasındaki native prepared statement parametre çakışması düzeltildi ve doğrulandı.`

## 3. İçerik Yönetimi

- `[x] CNT-01` Template Registry açılıyor; tür, modül, kod, aktiflik ve arama filtreleri çalışıyor.
- `[x] CNT-02` Email, Print ve Content template türleri doğru listeleniyor.
- `[x] CNT-03` Template oluşturma, düzenleme ve aktif/pasif işlemleri çalışıyor.
- `[x] CNT-04` TinyMCE Light/Dark teması kullanıcı temasıyla eşleşiyor.
- `[x] CNT-05` Template CSV/XLS export mevcut filtreleri koruyor.
- `[x] CNT-06` Documents ekranı açılıyor; arama, belge türü ve entity filtreleri çalışıyor.
- `[x] CNT-07` Test belgesi yükleniyor, indiriliyor ve doğru entity bilgisiyle listeleniyor.
- `[x] CNT-08` Test belgesi silme onayı ve silme işlemi çalışıyor.
- `[x] CNT-09` Documents CSV/XLS export mevcut filtreleri koruyor.
- `[ ] CNT-10` FilePond modalında çoklu dosya seçme, sürükle-bırak, dosya kaldırma ve toplu yükleme çalışıyor.
- `[ ] CNT-11` Geçersiz dosya türü ve limit üstü dosya istemci tarafında reddediliyor; sunucu doğrulaması bypass edilemiyor.
- `[ ] CNT-12` FilePond ilerleme, iptal, yeniden deneme ve başarısız dosya durumları doğru gösteriliyor.
- `[ ] CNT-13` Koleksiyonlar, Son 30 Gün, Bağlantılı Dosyalar ve belge türü filtreleri doğru sonuçları getiriyor.
- `[ ] CNT-14` Grid/liste görünümü korunuyor; dosya seçimi sağdan açılan ayrıntı panelini doğru bilgilerle dolduruyor ve panel kapatılabiliyor.
- `[ ] CNT-15` Light/Dark tema, masaüstü ve mobil Documents görünümü okunabilir ve taşmasız.

Notlar: `Documents v2.0.0 temel akışları doğrulandı. v2.1.0 FilePond ve explorer yenilemesi için CNT-10..15 canlı test bekliyor.`

## 4. İletişim

- `[x] COM-01` Bildirim listesi açılıyor; filtreler ve sayfalama çalışıyor.
- `[x] COM-02` Tek bildirimi ve tüm bildirimleri okundu işaretleme çalışıyor.
- `[x] COM-03` Bildirim ayarları kaydediliyor ve yeniden açıldığında korunuyor.
- `[x] COM-04` Bildirim CSV/XLS export mevcut filtreleri koruyor.
- `[x] COM-05` Mail Test ekranı açılıyor; yapılandırma durumu doğru gösteriliyor.
- `[x] COM-06` Geçerli mail ayarı varsa test maili gönderiliyor; yoksa anlaşılır hata gösteriliyor.
- `[x] COM-07` Mail şablonları listeleniyor; oluşturma, düzenleme ve silme çalışıyor.
- `[x] COM-08` Mail şablonu CSV/XLS export dosyaları doğru.
- `[x] COM-09` İletişim aksiyonları audit ve notification kaydı üretiyor.

Notlar: `____________________________________________________________________`

## 5. Operasyon ve İzleme

- `[x] OPS-01` Backup ekranı açılıyor ve mevcut kayıtları listeliyor.
- `[x] OPS-02` Test backup oluşturma, doğrulama ve indirme işlemleri çalışıyor.
- `[x] OPS-03` Test backup silme işlemi onay sonrası çalışıyor.
- `[x] OPS-04` Backup ve restore log export dosyaları doğru.
- `[x] OPS-05` Restore işlemi yalnız güvenli test ortamında deneniyor ve sonucu audit'e yazılıyor.
- `[x] OPS-06` Queue ekranı açılıyor; durum sayaçları ve kayıt listesi doğru.
- `[x] OPS-07` Test mail job enqueue ve `work once` işlemleri çalışıyor.
- `[x] OPS-08` Başarısız job retry işlemi ve Queue export çalışıyor.
- `[x] OPS-09` Audit Overview açılıyor; modül özetleri ve export çalışıyor.
- `[x] OPS-10` Audit Log arama, modül, aksiyon, durum ve tarih filtreleri çalışıyor.
- `[x] OPS-11` Audit Log CSV/XLS export mevcut filtreleri koruyor.
- `[x] OPS-12` Health ekranında app ve DB kontrolleri sağlıklı gösteriliyor.
- `[x] OPS-13` Security ekranı açılıyor; güvenlik olayları ve export çalışıyor.
- `[x] OPS-14` API Metrics ekranı açılıyor; sayaçlar, filtreler ve export çalışıyor.
- `[x] OPS-15` Env Reader mevcut env anahtarlarını gösteriyor; secret/token/password değerleri maskeli.

Notlar: `Backup işlemlerine görünür işlem durumu, spinner, işlem bazlı bekleme mesajı ve çift/eşzamanlı gönderim kilidi eklendi. Başarı ve hata sonuçları aynı durum alanında gösteriliyor.`

## 6. Sistem Ayarları ve Modüller

- `[x] SYS-01` Ayarlar ekranı açılıyor; sekmeler ve alanlar taşmadan görünüyor.
- `[x] SYS-02` Kritik olmayan ayarlar kaydediliyor ve yeniden açıldığında korunuyor.
- `[x] SYS-03` Secret alanları açık metin olarak geri gösterilmiyor.
- `[x] SYS-04` API Test ekranı geçerli/geçersiz token sonuçlarını doğru gösteriyor.
- `[x] SYS-05` Modüller ekranında tüm Core modülleri ve sürümleri doğru listeleniyor.
- `[x] SYS-06` Eksik kurulum kontrolü ve `install missing` işlemi doğru sonuç veriyor.
- `[x] SYS-07` Test edilebilir bir modülün aç/kapat işlemi menü ve route davranışına yansıyor.
- `[x] SYS-08` Modül registry CSV/XLS export dosyaları doğru.
- `[x] SYS-09` Menü Yönetimi ekranında grup, sıra, başlık, URL ve permission bilgileri doğru.
- `[x] SYS-10` Menü registry CSV/XLS export dosyaları doğru.
- `[x] SYS-11` Sistem ayarı ve modül işlemleri audit/notification kaydı üretiyor.

Notlar: `____________________________________________________________________`

## 7. Kirpi Intelligence v1.0

- `[x] AI-01` AI Dashboard özetleri, hızlı işlemler ve son sync bilgisi doğru.
- `[x] AI-02` Schema Sync başarılı; entity/field/index sayaçları güncelleniyor.
- `[x] AI-03` Schema Discovery arama ve tüm filtreleri çalışıyor.
- `[x] AI-04` Schema JSON/CSV/XLS export mevcut filtreleri koruyor; hassas alanlar varsayılan gizli.
- `[x] AI-05` Schema Quality ekranı ve JSON/CSV/XLS export çalışıyor.
- `[x] AI-06` Query Planner doğru aday tablo ve alanları getiriyor.
- `[x] AI-07` Query Flow yalnız aktif `sql_generation` adapterlarını listeliyor.
- `[x] AI-08` Provider Ayarları kaydediliyor; Adapter Tipi sayfa yenilenince değişmiyor.
- `[x] AI-09` Provider bağlantı testi beklenen JSON sözleşmesini doğruluyor.
- `[x] AI-10` `aktif kullanıcıları listele` testi açık alanlı tek SELECT candidate üretiyor.
- `[x] AI-11` Aday SQL içinde reasoning, açıklama, markdown veya `<think>` görünmüyor.
- `[x] AI-12` `SELECT *`, izin dışı tablo ve izin dışı alan testleri Guard tarafından bloklanıyor.
- `[x] AI-13` Query Flow sonucu Preview Allowed, Execution Kapalı ve Veri Okuma Hayır gösteriyor.
- `[x] AI-14` SQL Guard yakalanan tabloları, alanları ve blok nedenlerini doğru gösteriyor.
- `[x] AI-15` SQL Preview SQL çalıştırmıyor; EXPLAIN env kapalıysa bloklu kalıyor.
- `[x] AI-16` AI Audit Log planner, candidate, preview, guard ve provider test kayıtlarını gösteriyor.
- `[x] AI-17` Debug JSON Kopyala çalışıyor; secret değerleri bulunmuyor veya maskeli.
- `[x] AI-18` AI ekranları Light/Dark tema ve mobil görünümde okunabilir.

Notlar: `____________________________________________________________________`

## 8. API ve Yetki Negatif Testleri

- `[x] SEC-01` Oturumsuz yönetim sayfası isteği login ekranına yönleniyor.
- `[x] SEC-02` Yetkisiz kullanıcı menüde izin verilmeyen ekranları görmüyor.
- `[x] SEC-03` Yetkisiz route isteği `403` veya standart erişim reddi sonucu veriyor.
- `[x] SEC-04` POST aksiyonları geçersiz CSRF token ile reddediliyor.
- `[x] SEC-05` API token olmadan korumalı API endpoint'i erişimi reddediyor.
- `[x] SEC-06` Geçerli token ile `/api/v1/me` doğru kullanıcıyı döndürüyor.
- `[x] SEC-07` Hassas alanlar API/schema/export çıktılarında izin olmadan görünmüyor.
- `[x] SEC-08` Hata ekranları stack trace, DB şifresi veya secret göstermiyor.

Notlar: `____________________________________________________________________`

## 9. Sonuç Özeti

| Alan | Başarılı | Hatalı | Test Edilemedi | N/A |
|---|---:|---:|---:|---:|
| Genel Shell ve Dashboard | 12 | 0 | 0 | 0 |
| Kullanıcılar, Roller ve Profil | 12 | 0 | 0 | 0 |
| İçerik Yönetimi | 9 | 0 | 0 | 0 |
| İletişim | 9 | 0 | 0 | 0 |
| Operasyon ve İzleme | 15 | 0 | 0 | 0 |
| Sistem Ayarları ve Modüller | 11 | 0 | 0 | 0 |
| Kirpi Intelligence | 18 | 0 | 0 | 0 |
| API ve Yetki | 8 | 0 | 0 | 0 |
| **Toplam** | **94** | **0** | **0** | **0** |

Genel karar: `[x] Yayına uygun` `[ ] Düzeltme sonrası tekrar test` `[ ] Bloke`

Kritik bulgular: `Yok.`

Ek notlar / ekran görüntüsü bağlantıları:

`94/94 kontrol başarılı. Deployment standardizasyonu ayrı çalışma fazı olarak izlenecek.`
