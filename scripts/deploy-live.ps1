# Manuel canli deploy - Hostinger VPS (SSH/SFTP)
# Kullanim: .\scripts\deploy-live.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

$configPath = Join-Path $root "deploy.config.json"
if (-not (Test-Path $configPath)) {
    Write-Host "deploy.config.json bulunamadi." -ForegroundColor Red
    Write-Host "deploy.config.example.json dosyasini kopyalayip remoteDir ve anahtar yolunu duzenleyin." -ForegroundColor Yellow
    exit 1
}

$config = Get-Content $configPath -Raw | ConvertFrom-Json
$ssh = $config.ssh

Write-Host "`n=== CANLI DEPLOY (SSH) ===" -ForegroundColor Cyan
Write-Host "Sunucu: $($ssh.host) ($($ssh.username))"
Write-Host "Hedef : $($ssh.remoteDir)"
Write-Host ""

$confirm = Read-Host "Devam etmek istiyor musunuz? (E/H)"
if ($confirm -notmatch '^[Ee]') {
    Write-Host "Iptal edildi." -ForegroundColor Yellow
    exit 0
}

$keyPath = $ssh.privateKeyPath
if (-not (Test-Path $keyPath)) {
    Write-Host "SSH anahtari bulunamadi: $keyPath" -ForegroundColor Red
    Write-Host "Once calistirin: .\scripts\setup-ssh.ps1" -ForegroundColor Yellow
    exit 1
}

$winScp = "${env:ProgramFiles(x86)}\WinSCP\WinSCP.com"
if (-not (Test-Path $winScp)) {
    $winScp = "$env:ProgramFiles\WinSCP\WinSCP.com"
}

if (Test-Path $winScp) {
    $scriptFile = Join-Path $env:TEMP "yaprak-deploy-ssh.txt"
    $remoteDir = $ssh.remoteDir.TrimEnd('/') + '/'
    @"
option batch abort
option confirm off
open sftp://$($ssh.username)@$($ssh.host):$($ssh.port)/ -privatekey="$keyPath"
synchronize remote "$root" "$remoteDir" -delete -criteria="|config.local.php|deploy.config.json|debug_integrity_log.txt|blocked_ips.txt|.git/|.github/|GeoLite2-City.mmdb|*.zip|scripts/|GITHUB-KURULUM.md"
exit
"@ | Set-Content $scriptFile -Encoding UTF8

    & $winScp /script=$scriptFile
    Remove-Item $scriptFile -Force
    Write-Host "`nDeploy tamamlandi (WinSCP SFTP)." -ForegroundColor Green
    exit 0
}

Write-Host "WinSCP bulunamadi. Onerilen: git push origin main (GitHub Actions deploy eder)." -ForegroundColor Yellow
Write-Host "Alternatif: https://winscp.net/ veya WSL ile rsync kullanin." -ForegroundColor Yellow
exit 1
