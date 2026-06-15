# Faz 0 - QMS Foundation

Bu klasör, Kirpi QMS+ geliştirmesinde koddan önce bağlayıcı olan ürün ve mimari sözleşmeleri içerir.

## Belgeler

- [Ürün Sözleşmesi](PRODUCT_CONTRACT.md)
- [Terimler ve Entity Registry](GLOSSARY_AND_ENTITY_REGISTRY.md)
- [Adlandırma Standardı](NAMING_STANDARD.md)
- [Numbering Engine Standardı](NUMBERING_STANDARD.md)
- [Yaşam Döngüsü ve Veri Saklama](LIFECYCLE_AND_RETENTION.md)
- [Organizasyon Scope ve Görünürlük](ORGANIZATION_SCOPE.md)
- [Permission Matrisi](PERMISSION_MATRIX.md)
- [Domain Event Sözleşmesi](EVENT_CONTRACT.md)
- [ADR-001 - Ortak Entity Registry](adr/ADR-001-MANAGED-ENTITY-REGISTRY.md)
- [ADR-002 - Ayrı Domain Event Store](adr/ADR-002-DOMAIN-EVENT-STORE.md)
- [ADR-003 - Yetki ve Sahiplik Ayrımı](adr/ADR-003-AUTHORIZATION-OWNERSHIP-SEPARATION.md)
- [ADR-004 - Kontrollü Doküman ve Dosya Ayrımı](adr/ADR-004-CONTROLLED-DOCUMENT-SEPARATION.md)

## Karar Durumu

Bu belgelerdeki kararlar `Accepted` kabul edilir. Değişiklikler mevcut metni sessizce dönüştürmek yerine yeni ADR veya açık sürüm notuyla yapılır.

Foundation karar paketi Organization modülü geliştirmesine başlamak için yeterlidir. Operasyonel modüllerin ayrıntılı alan ve durum geçişleri, ilgili Faz 5 dalgasının giriş kapısında ayrı domain spesifikasyonlarıyla tamamlanacaktır.
