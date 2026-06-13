# Yerel SSH deploy (OpenSSH) — GitHub Actions basarisiz oldugunda kullanin.
# Kullanim: .\scripts\deploy-ssh-tar.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

$configPath = Join-Path $root "deploy.config.json"
if (-not (Test-Path $configPath)) {
    Write-Host "deploy.config.json bulunamadi." -ForegroundColor Red
    exit 1
}

$config = Get-Content $configPath -Raw | ConvertFrom-Json
$ssh = $config.ssh
$keyPath = $ssh.privateKeyPath
$remoteDir = $ssh.remoteDir.TrimEnd('/') + '/'
$hostAddr = $ssh.host
$user = $ssh.username

if (-not (Test-Path $keyPath)) {
    Write-Host "SSH anahtari bulunamadi: $keyPath" -ForegroundColor Red
    exit 1
}

$archive = Join-Path $env:TEMP ("yaprak-deploy-" + [guid]::NewGuid().ToString("n") + ".tar.gz")
Write-Host "`n=== YEREL SSH DEPLOY ===" -ForegroundColor Cyan
Write-Host "Sunucu: ${user}@${hostAddr}"
Write-Host "Hedef : $remoteDir"
Write-Host "Arsiv olusturuluyor..."

$tarArgs = @(
    "-czf", $archive,
    "--exclude=.git",
    "--exclude=.github",
    "--exclude=xnull/controller/config.local.php",
    "--exclude=deploy.config.json",
    "--exclude=debug_integrity_log.txt",
    "--exclude=xnull/debug_integrity_log.txt",
    "--exclude=xnull/cloaker/blocked_ips.txt",
    "--exclude=js/ajax/libs/GeoLite2-City.mmdb",
    "--exclude=*.zip",
    "--exclude=GITHUB-KURULUM.md",
    "--exclude=scripts",
    "--exclude=xnull/assets/img/urunler",
    "--exclude=xnull/assets/img/galeri",
    "--exclude=xnull/assets/img/genel",
    "--exclude=assets/img/genel",
    "."
)
& tar @tarArgs
if ($LASTEXITCODE -ne 0) { throw "tar basarisiz" }

$remoteArchive = "/tmp/yaprak-deploy.tar.gz"
Write-Host "Sunucuya gonderiliyor..."
& scp -i $keyPath -o StrictHostKeyChecking=no $archive "${user}@${hostAddr}:${remoteArchive}"

Write-Host "Sunucuda aciliyor..."
$remoteCmd = "mkdir -p '$remoteDir' && tar xzf '$remoteArchive' -C '$remoteDir' && rm -f '$remoteArchive' && find '$remoteDir' -type d -exec chmod 755 {} \; && find '$remoteDir' -type f -exec chmod 644 {} \;"
& ssh -i $keyPath -o StrictHostKeyChecking=no "${user}@${hostAddr}" $remoteCmd

Remove-Item $archive -Force -ErrorAction SilentlyContinue
Write-Host "`nDeploy tamamlandi." -ForegroundColor Green
