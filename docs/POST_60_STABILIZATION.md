# Post-60 Stabilization Checklist

Bu doküman, 60 slice tamamlandıktan sonra sistemi demo çekirdeğinden production-aday ürüne taşımak için uygulanacak sırayı verir.

## 1. Repo ve release sabitleme
- main branch temiz mi kontrol et
- tüm commitler GitHub'a pushlandı mı kontrol et
- `php artisan test` son kez çalıştır
- release etiketi oluştur: `v0.1.0-core` gibi
- `.env.example` ve production örnek ayarlarını gözden geçir

## 2. Production altyapı doğrulaması
- gerçek PostgreSQL kur ve proje bağlantısını doğrula
- gerçek Redis kur ve queue/broadcast/cache bağlantılarını doğrula
- pgvector uzantısını kur ve `CREATE EXTENSION vector;` doğrula
- storage disk stratejisini netleştir
- worker supervision yapısını doğrula

## 3. Güvenlik
- auth akışını production modunda doğrula
- rol/yetki kontrollerini doğrula
- gizli anahtarların loglara sızmadığını doğrula
- session/cookie/APP_KEY güvenliğini gözden geçir
- rate limit ve brute-force korumasını ekle veya kontrol et

## 4. Operasyonel doğrulama
- queue worker gerçekten çalışıyor mu kontrol et
- retry politikası gerçek hatalarda doğru mu kontrol et
- audit event'ler doluyor mu kontrol et
- realtime fallback ve gerçek broadcast davranışı kontrol et
- artifact/document/knowledge akışları uçtan uca test et

## 5. LLM ve memory doğrulama
- gerçek embedding provider bağla
- retrieval kalite kontrolü yap
- provider fallback senaryolarını test et
- maliyet/usage kayıtlarını doğrula
- prompt version takibini doğrula

## 6. UI doğrulama
- admin login/logout akışı
- agent management UI
- task queue UI
- execution monitor UI
- dashboard ve unified detail ekranları
- document/knowledge ve company loop ekranları

## 7. End-to-end acceptance
- coordinator intake çalışıyor mu
- decomposition doğru child task üretiyor mu
- specialist agent çıktıları persist oluyor mu
- company loop final report artifact oluşuyor mu
- communication log ve audit trail tam mı

## 8. CI/CD ve bakım
- test pipeline
- deploy pipeline
- backup/restore akışı
- log rotation ve disk kontrolü
- rollback planı

## 9. İlk production backlog
- gerçek tenant izolasyonu sertleştirme
- human approval gates
- policy engine hardening
- observability metrics/tracing
- billing ve usage dashboard
- harici entegrasyon gateway

## 10. Önerilen sıra
1. PostgreSQL + Redis + pgvector gerçek kurulum
2. worker supervision ve queue doğrulama
3. auth/role hardening
4. embedding provider ve retrieval kalite doğrulama
5. dashboard + unified operational visibility
6. company loop production acceptance test
7. CI/CD + backup/restore + rollout

## Çıkış kriteri
Sistem production-aday sayılırsa:
- testler temiz
- ana runtime akışları gerçek altyapıda çalışıyor
- güvenlik temel kontrolleri tamam
- audit ve observability mevcut
- company loop uçtan uca doğrulanmış
