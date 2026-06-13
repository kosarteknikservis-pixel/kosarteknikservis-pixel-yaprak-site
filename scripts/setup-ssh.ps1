# SSH anahtari olustur (GitHub Actions + manuel deploy icin)
# Kullanim: .\scripts\setup-ssh.ps1

$ErrorActionPreference = "Stop"
$keyPath = Join-Path $env:USERPROFILE ".ssh\yaprak_deploy"
$pubPath = "$keyPath.pub"

Write-Host "`n=== SSH ANAHTAR KURULUMU ===" -ForegroundColor Cyan
Write-Host "Hostinger VPS: 187.124.22.157 (root)" -ForegroundColor Gray

if (Test-Path $keyPath) {
    Write-Host "`nAnahtar zaten var: $keyPath" -ForegroundColor Yellow
} else {
    $sshDir = Split-Path $keyPath
    if (-not (Test-Path $sshDir)) {
        New-Item -ItemType Directory -Path $sshDir -Force | Out-Null
    }
    ssh-keygen -t ed25519 -f $keyPath -N '""' -C "yaprak-github-deploy"
    Write-Host "`n[OK] Yeni SSH anahtari olusturuldu." -ForegroundColor Green
}

Write-Host "`n--- PUBLIC KEY (sunucuya eklenecek) ---" -ForegroundColor Cyan
Get-Content $pubPath
Write-Host "----------------------------------------`n" -ForegroundColor Cyan

Write-Host "1) Asagidaki komutu VPS'te calistirin (root sifrenizle baglanin):" -ForegroundColor Yellow
Write-Host "   ssh root@187.124.22.157" -ForegroundColor White
Write-Host ""
Write-Host "2) VPS'te public key'i authorized_keys'e ekleyin:" -ForegroundColor Yellow
Write-Host "   mkdir -p ~/.ssh && chmod 700 ~/.ssh" -ForegroundColor White
Write-Host "   echo 'PASTE_PUBLIC_KEY' >> ~/.ssh/authorized_keys" -ForegroundColor White
Write-Host "   chmod 600 ~/.ssh/authorized_keys" -ForegroundColor White
Write-Host ""
Write-Host "3) GitHub repo Secrets'a ekleyin:" -ForegroundColor Yellow
Write-Host "   SSH_HOST        = 187.124.22.157" -ForegroundColor White
Write-Host "   SSH_USERNAME    = root" -ForegroundColor White
Write-Host "   SSH_REMOTE_DIR  = /home/admin/domains/DOMAIN/public_html/" -ForegroundColor White
Write-Host "   SSH_PRIVATE_KEY = (asagidaki private key tamami)" -ForegroundColor White
Write-Host ""
Write-Host "--- PRIVATE KEY (GitHub Secret: SSH_PRIVATE_KEY) ---" -ForegroundColor Red
Get-Content $keyPath
Write-Host "----------------------------------------------------`n" -ForegroundColor Red

Write-Host "Test:" -ForegroundColor Cyan
Write-Host "  ssh -i `"$keyPath`" root@187.124.22.157 `"echo baglanti OK`"" -ForegroundColor White
