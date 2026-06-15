# ADR-003 - Yetki ve Sahiplik Ayrımı

- Durum: Accepted
- Tarih: 2026-06-15

## Bağlam

Core role/permission sistemi bir kullanıcının yapabileceği işlemleri belirler. QMS ayrıca süreç, standart ve kayıt sorumluluğunu modellemelidir. Bu iki kavram birleştirilirse kullanıcı sorumlu olduğu için otomatik olarak yetkili hale gelir.

## Karar

Authorization Core permission sistemiyle, ownership ve RACI `governance` modülüyle yönetilecektir. Erişim kararı permission, organization scope ve gerekiyorsa workflow assignment birleşimidir.

## Sonuçlar

- Sahiplik tek başına işlem yetkisi vermez.
- Yetkili kullanıcı scope dışındaki kaydı göremez.
- Approval adımları permission yanında aktif workflow assignment gerektirir.
- Delegasyon role permission kaydını değiştirmez.
