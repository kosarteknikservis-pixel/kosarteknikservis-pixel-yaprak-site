# Deploy baglantisini test et (GitHub Actions oncesi)
# Kullanim: .\scripts\test-deploy.ps1

$ErrorActionPreference = "Stop"
$key = Join-Path $env:USERPROFILE ".ssh\yaprak_deploy"
$host_ = "187.124.22.157"
$user = "root"
$remote = "/home/admin/domains/kosarvantilator.com/public_html/"

Write-Host "`n=== DEPLOY TEST ===" -ForegroundColor Cyan

Write-Host "[1/3] SSH baglantisi..." -NoNewline
$ssh = ssh -i $key -o BatchMode=yes -o ConnectTimeout=10 "${user}@${host_}" "echo OK" 2>&1
if ($ssh -match "OK") { Write-Host " OK" -ForegroundColor Green } else { Write-Host " FAIL" -ForegroundColor Red; Write-Host $ssh; exit 1 }

Write-Host "[2/3] Hedef klasor..." -NoNewline
$dir = ssh -i $key "${user}@${host_}" "test -d '$remote' && echo OK" 2>&1
if ($dir -match "OK") { Write-Host " OK" -ForegroundColor Green } else { Write-Host " FAIL" -ForegroundColor Red; exit 1 }

Write-Host "[3/3] Dosya yazma (SCP)..." -NoNewline
$tmp = Join-Path $env:TEMP "deploy-test.txt"
"TEST $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" | Set-Content $tmp
scp -i $key $tmp "${user}@${host_}:${remote}deploy-test-local.txt" 2>&1 | Out-Null
if ($LASTEXITCODE -eq 0) { Write-Host " OK" -ForegroundColor Green } else { Write-Host " FAIL" -ForegroundColor Red; exit 1 }

Write-Host "`nLocal deploy altyapisi CALISIYOR." -ForegroundColor Green
Write-Host "GitHub Actions icin Secrets eklediyseniz Actions'tan Re-run yapin.`n" -ForegroundColor Yellow
