# Manuel canlı deploy — GitHub Actions calismiyorsa veya acil gonderim icin.
# Kullanım: .\scripts\deploy-live.ps1
# Gereksinim: deploy.config.json (deploy.config.example.json'dan kopyalayin)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

$configPath = Join-Path $root "deploy.config.json"
if (-not (Test-Path $configPath)) {
    Write-Host "deploy.config.json bulunamadi." -ForegroundColor Red
    Write-Host "deploy.config.example.json dosyasini kopyalayip duzenleyin." -ForegroundColor Yellow
    exit 1
}

$config = Get-Content $configPath -Raw | ConvertFrom-Json
$ftp = $config.ftp

Write-Host "`n=== CANLI DEPLOY (FTP) ===" -ForegroundColor Cyan
Write-Host "Sunucu: $($ftp.server)"
Write-Host "Hedef : $($ftp.remoteDir)"
Write-Host ""

$confirm = Read-Host "Devam etmek istiyor musunuz? (E/H)"
if ($confirm -notmatch '^[Ee]') {
    Write-Host "Iptal edildi." -ForegroundColor Yellow
    exit 0
}

# WinSCP varsa kullan (cPanel hosting icin en guvenilir yontem)
$winScp = "${env:ProgramFiles(x86)}\WinSCP\WinSCP.com"
if (-not (Test-Path $winScp)) {
    $winScp = "$env:ProgramFiles\WinSCP\WinSCP.com"
}

if (Test-Path $winScp) {
    $scriptFile = Join-Path $env:TEMP "yaprak-deploy.txt"
    $remoteDir = $ftp.remoteDir.TrimEnd('/') + '/'
    @"
option batch abort
option confirm off
open ftp://$($ftp.username):$($ftp.password)@$($ftp.server):$($ftp.port)/
synchronize remote "$root" "$remoteDir" -delete -criteria="|config.local.php|deploy.config.json|debug_integrity_log.txt|blocked_ips.txt|.git/"
exit
"@ | Set-Content $scriptFile -Encoding UTF8

    & $winScp /script=$scriptFile
    Remove-Item $scriptFile -Force
    Write-Host "`nDeploy tamamlandi (WinSCP)." -ForegroundColor Green
    exit 0
}

# WinSCP yoksa Git push oner
Write-Host "WinSCP bulunamadi." -ForegroundColor Yellow
Write-Host "Onerilen yontem: degisiklikleri GitHub'a push edin, Actions otomatik deploy eder." -ForegroundColor Cyan
Write-Host ""
Write-Host "  git add ."
Write-Host "  git commit -m `"Aciklama`""
Write-Host "  git push origin main"
Write-Host ""
Write-Host "Alternatif: WinSCP indirin -> https://winscp.net/" -ForegroundColor Yellow
exit 1
