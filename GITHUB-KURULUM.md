# GitHub + Local → Canlı Deploy (Hostinger VPS)

**Sunucu:** Hostinger VPS — AlmaLinux 9 + DirectAdmin  
**IP:** `187.124.22.157`  
**SSH:** `root@srv1454757.hstgr.cloud`  
**Repo:** https://github.com/kosarteknikservis-pixel/kosarteknikservis-pixel-yaprak-site

## Akış

```
Local (XAMPP) → git push → GitHub (main) → GitHub Actions → VPS (SSH/rsync)
```

---

## 1. SSH Anahtarı Oluştur (Bir Kez)

PowerShell'de:

```powershell
cd "c:\xampp\htdocs\yaprak\public_html\public_html (21)"
.\scripts\setup-ssh.ps1
```

Script size **public key** ve **private key** verir.

### VPS'e public key ekle

```bash
ssh root@187.124.22.157
mkdir -p ~/.ssh && chmod 700 ~/.ssh
echo 'PUBLIC_KEY_BURAYA' >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

Test (local):

```powershell
ssh -i "$env:USERPROFILE\.ssh\yaprak_deploy" root@187.124.22.157 "echo baglanti OK"
```

---

## 2. Sunucu Kurulumu (Bir Kez)

VPS'e bağlanıp:

```bash
# Domain public_html yollarını görmek için:
find /home -maxdepth 4 -type d -name public_html

# Kurulum scripti (config.local.php oluşturur):
bash server-setup.sh
```

> `config.local.php` deploy sırasında **asla silinmez / üzerine yazılmaz**.

---

## 3. GitHub Secrets

Repo → **Settings** → **Secrets and variables** → **Actions**

| Secret | Değer |
|---|---|
| `SSH_HOST` | `187.124.22.157` |
| `SSH_USERNAME` | `root` |
| `SSH_REMOTE_DIR` | `/home/admin/domains/kosarvantilator.com/public_html/` |
| `SSH_PRIVATE_KEY` | `setup-ssh.ps1` çıktısındaki private key (tamamı) |
| `SSH_PORT` | `22` (opsiyonel) |

DirectAdmin'de doğru yolu bulmak için VPS'te:

```bash
find /home -maxdepth 4 -type d -name public_html
```

---

## 4. Günlük Kullanım

```powershell
.\scripts\local-check.ps1
git add .
git commit -m "Degisiklik aciklamasi"
git push origin main
```

GitHub → **Actions** sekmesinden deploy durumunu izleyin.

### Acil manuel deploy

```powershell
# deploy.config.example.json → deploy.config.json (remoteDir + key yolu)
.\scripts\deploy-live.ps1
```

---

## 5. Önemli Notlar

- `config.local.php` ve `deploy.config.json` Git'e eklenmez
- `GeoLite2-City.mmdb` Git'te yok — GeoIP için sunucuya manuel yükleyin: `js/ajax/libs/`
- `kosar_vant.zip` gibi büyük arşivler repoda tutulmaz
- DirectAdmin'de site sahibi `admin` değilse yol `/home/KULLANICI/domains/...` olabilir

---

## Sorun Giderme

| Sorum | Çözüm |
|---|---|
| SSH bağlanamıyor | Hostinger panel → VPS → SSH keys / firewall 22 portu |
| Permission denied | Public key `authorized_keys`'e doğru eklendi mi? |
| Deploy path hatası | `SSH_REMOTE_DIR` yolunu `find` ile doğrulayın |
| Canlıda DB hatası | Sunucuda `xnull/controller/config.local.php` var mı? |
| Actions fail | Secrets → private key baştan sona (-----BEGIN...END-----) |
