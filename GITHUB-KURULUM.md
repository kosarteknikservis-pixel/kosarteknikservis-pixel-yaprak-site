# GitHub + Local → Canlı Deploy Kurulumu

Bu proje için **local geliştirme → GitHub → canlı sunucu** akışı kurulmuştur.

## Akış Özeti

```
[Local XAMPP]  →  git commit  →  GitHub (main)  →  GitHub Actions  →  Canlı FTP
      ↑                                                                    ↓
  local-check.ps1                                              config.local.php (sunucuda kalır)
```

---

## 1. İlk Kurulum (Bir Kez)

### A) Git başlat ve GitHub'a bağla

PowerShell'de proje klasöründe:

```powershell
cd "c:\xampp\htdocs\yaprak\public_html\public_html (21)"

git init
git branch -M main
git add .
git commit -m "Ilk commit: proje GitHub'a hazir"
```

### B) GitHub'da yeni repo oluştur

1. https://github.com/new adresine gidin
2. Repo adı: örn. `yaprak-site` veya `kosarticaret`
3. **Private** seçin (önerilir)
4. README / .gitignore eklemeyin (zaten var)
5. **Create repository**

### C) Remote ekle ve push et

```powershell
git remote add origin https://github.com/kosarteknikservis-pixel/REPO-ADI.git
git push -u origin main
```

> GitHub kullanıcı adı/şifre yerine **Personal Access Token** gerekebilir:
> GitHub → Settings → Developer settings → Personal access tokens → Generate new token (repo yetkisi)

---

## 2. Canlı Sunucu Ayarları (Bir Kez)

### A) Sunucuda config.local.php oluşturun

Canlı sunucuda `xnull/controller/config.local.php` dosyasını oluşturun (FTP/cPanel ile):

```php
<?php
return [
    'dbHost' => '127.0.0.1',
    'dbAdi' => 'CANLI_DB_ADI',
    'Kullanici' => 'CANLI_DB_KULLANICI',
    'Sifre' => 'CANLI_DB_SIFRE',
];
```

> Bu dosya deploy sırasında **asla üzerine yazılmaz** — sunucuya özel kalır.

### B) GitHub Secrets (FTP bilgileri)

GitHub repo → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

| Secret adı | Değer |
|---|---|
| `FTP_SERVER` | `ftp.domain.com` |
| `FTP_USERNAME` | FTP kullanıcı adı |
| `FTP_PASSWORD` | FTP şifresi |
| `FTP_REMOTE_DIR` | `/public_html/` (cPanel yolunuza göre) |
| `FTP_PORT` | `21` (opsiyonel) |

---

## 3. Günlük Kullanım

### Local'de geliştir → kontrol et → gönder

```powershell
# 1) XAMPP'te test et (tarayıcı)
# 2) Yerel kontrol scripti
.\scripts\local-check.ps1

# 3) Commit ve push
git add .
git commit -m "Degisiklik aciklamasi"
git push origin main
```

Push sonrası GitHub **Actions** sekmesinden deploy durumunu izleyin.

### Manuel deploy (acil durum)

```powershell
# deploy.config.example.json → deploy.config.json kopyala, FTP bilgilerini gir
.\scripts\deploy-live.ps1
```

---

## 4. Dal (Branch) Stratejisi

| Dal | Amaç |
|---|---|
| `main` | Canlı — push = otomatik deploy |
| `develop` | Test/geliştirme (opsiyonel, deploy etmez) |

Geliştirme için:

```powershell
git checkout -b develop
# ... calisma ...
git checkout main
git merge develop
git push origin main
```

---

## 5. Önemli Notlar

- `config.local.php` ve `deploy.config.json` **Git'e eklenmez** (şifreler korunur)
- Yüklenen görseller (`assets/img/genel/`) repoda tutulmaz, canlıda kalır
- Veritabanı değişiklikleri `sql/` klasöründeki dosyalarla takip edilir; canlıda manuel uygulanır
- cPanel LiteSpeed için `.htaccess` içindeki PHP handler satırını barındırıcıya göre ayarlayın

---

## Sorun Giderme

| Sorun | Çözüm |
|---|---|
| Push reddediliyor | GitHub token veya SSH key kontrol edin |
| Deploy başarısız | Actions log → FTP bilgilerini kontrol edin |
| Canlıda DB hatası | Sunucuda `config.local.php` var mı kontrol edin |
| Local'de DB hatası | `config.local.php` oluşturun (example'dan kopyala) |
