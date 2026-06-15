# ADR-001 - Ortak Managed Entity Registry

- Durum: Accepted
- Tarih: 2026-06-15

## Bağlam

QMS kayıtlarının ortak kimlik, organizasyon, sahiplik, ilişki ve timeline davranışına ihtiyacı vardır. Tüm domain alanlarını tek tabloda toplamak ise şema bütünlüğünü ve sorgulanabilirliği zayıflatır.

## Karar

`qms_entities` ortak registry tablosu kullanılacak; alana özgü bilgiler domain tablolarında tutulacaktır. Domain kaydı registry kimliğine bire bir bağlanacaktır.

## Sonuçlar

- Relationship ve event katmanları stabil entity kimliği kullanır.
- Domain tabloları güçlü ve açık kolonlarla kalır.
- Oluşturma/silme işlemleri transaction ve bütünlük helper'ları gerektirir.
- Her küçük join veya execution kaydı managed entity yapılmaz.
