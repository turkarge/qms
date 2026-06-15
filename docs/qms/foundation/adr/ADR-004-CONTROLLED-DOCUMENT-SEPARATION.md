# ADR-004 - Kontrollü Doküman ve Dosya Ayrımı

- Durum: Accepted
- Tarih: 2026-06-15

## Bağlam

Core `documents` modülü güvenli dosya saklama ve entity link registry'sidir. QMS kontrollü dokümanları ise kod, revizyon, onay, yayın, dağıtım ve arşiv yaşam döngüsü taşır.

## Karar

Kontrollü doküman domain kayıtları `controlled_documents` modülünde tutulacak; fiziksel dosyalar Core Documents Registry üzerinden bağlanacaktır.

## Sonuçlar

- QMS modülü doğrudan `uploads/` dizinine yazmaz.
- Bir doküman revizyonu bir veya daha fazla Core document dosyasına bağlanabilir.
- Dosyanın silinmesi aktif kontrollü doküman bağlantısı varken engellenir.
- Core Documents ekranı domain workflow ekranının yerine geçmez.
