# Organizasyon Scope ve Veri Görünürlüğü

## İlke

Permission kullanıcının işlemi yapabilmesini, organization scope ise işlemi hangi kayıtlar üzerinde yapabilmesini belirler. İkisi birlikte başarılı değilse erişim reddedilir.

```text
allowed = has_permission AND entity_in_scope
```

## Organizasyon Hiyerarşisi

İlk sürüm tek şirket bağlamında çalışsa da model çoklu company destekler:

```text
company
  facility
    location
    department
      team
```

Position hiyerarşinin düğümü değil, kullanıcı atamalarında kullanılan organizasyon tanımıdır.

## Scope Modları

| Scope | Anlam |
|---|---|
| `self` | Kullanıcının sahibi veya sorumlusu olduğu kayıtlar |
| `team` | Kullanıcının aktif ekip atamalarındaki kayıtlar |
| `department` | Aktif departman atamasındaki kayıtlar |
| `department_descendants` | Departman ve alt organizasyon kayıtları |
| `facility` | Aktif tesis içindeki kayıtlar |
| `company` | Aktif şirket içindeki kayıtlar |
| `global` | Tüm şirketler; yalnız açıkça atanmış yönetim yetkisi |

## Kullanıcı Atamaları

- Kullanıcı birden fazla organization unit'e atanabilir.
- Her atama başlangıç/bitiş tarihi ve primary flag taşır.
- Scope hesabında yalnız aktif tarih aralığındaki atamalar kullanılır.
- Role kaydı organization scope üretmez.
- Super Admin teknik bypass davranışı ayrı kalır ve audit edilir.

## Entity Organization Context

Her managed entity en az `company_id` taşır. Domain ihtiyacına göre aşağıdakileri de taşır:

- `facility_id`
- `department_id`
- `team_id`
- `owner_user_id`

Alt alan boşsa scope en yakın dolu üst bağlamdan hesaplanır. Company bilgisi hiçbir zaman boş bırakılamaz.

## Okuma ve Yazma Kuralları

- Liste sorguları scope filtresini SQL seviyesinde uygular; sonuç geldikten sonra istemcide filtrelenmez.
- Tekil kayıt route'u ayrıca entity scope kontrolü yapar.
- Export, UI listesiyle aynı scope ve filtreleri kullanır.
- Relationship görünümü hem source hem target entity için scope kontrolü yapar.
- Event timeline yalnız kullanıcının okuyabildiği entity ve event payload alanlarını gösterir.
- AI discovery permission yanında organization scope filtresi de uygulamalıdır.

## Scope Yükseltme

Geçici kapsam yükseltme yalnız delegation veya açık organization assignment ile yapılır. Kullanıcı kendine scope veremez.

Değişiklikler:

- Başlangıç ve bitiş zamanı taşır.
- Gerekçe gerektirir.
- Audit ve `organization_assignment.changed.v1` event'i üretir.

## İlk Organization Kabul Kriterleri

- İki facility verisi birbirinden izole edilebilir.
- Department kullanıcısı başka department kayıtlarını göremez.
- Facility yöneticisi kendi facility altındaki department kayıtlarını görür.
- Company scope kullanıcısı aynı company içindeki tüm facility kayıtlarını görür.
- Export ve API sonucu UI ile aynı kayıt kümesini döndürür.
