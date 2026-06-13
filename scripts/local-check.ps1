# Yerel kontrol - canliya gondermeden once calistirin.
# Kullanım: .\scripts\local-check.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

Write-Host "`n=== YEREL KONTROL ===" -ForegroundColor Cyan

$errors = 0

# 1) config.local.php var mı?
$configLocal = Join-Path $root "xnull\controller\config.local.php"
if (-not (Test-Path $configLocal)) {
    Write-Host "[HATA] config.local.php yok. config.local.example.php dosyasini kopyalayin." -ForegroundColor Red
    $errors++
} else {
    Write-Host "[OK] config.local.php mevcut" -ForegroundColor Green
}

# 2) Git durumu
if (Test-Path (Join-Path $root ".git")) {
    $status = git status --porcelain 2>&1
    if ($status) {
        Write-Host "[UYARI] Commit edilmemis degisiklikler var:" -ForegroundColor Yellow
        git status -s
    } else {
        Write-Host "[OK] Calisma dizini temiz (commit edilmis)" -ForegroundColor Green
    }
} else {
    Write-Host "[UYARI] Git henuz baslatilmamis (git init)" -ForegroundColor Yellow
}

# 3) PHP syntax kontrolu (XAMPP)
$phpPaths = @(
    "C:\xampp\php\php.exe",
    "C:\laragon\bin\php\php-8.3.12-Win32-vs16-x64\php.exe"
)
$php = $phpPaths | Where-Object { Test-Path $_ } | Select-Object -First 1

if ($php) {
    Write-Host "`nPHP syntax kontrolu..." -ForegroundColor Cyan
    $phpFiles = Get-ChildItem -Path $root -Filter "*.php" -Recurse |
        Where-Object { $_.FullName -notmatch "\\assets\\lib\\" }
    $syntaxErrors = 0
    foreach ($file in $phpFiles) {
        & $php -l $file.FullName 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-Host "[HATA] Syntax: $($file.FullName)" -ForegroundColor Red
            $syntaxErrors++
        }
    }
    if ($syntaxErrors -eq 0) {
        Write-Host "[OK] $($phpFiles.Count) PHP dosyasi syntax OK" -ForegroundColor Green
    } else {
        $errors += $syntaxErrors
    }
} else {
    Write-Host "[UYARI] PHP bulunamadi, syntax kontrolu atlandi" -ForegroundColor Yellow
}

# 4) Hassas dosya Git'e eklenmis mi?
if (Test-Path (Join-Path $root ".git")) {
    $tracked = git ls-files "xnull/controller/config.local.php" 2>&1
    if ($tracked) {
        Write-Host "[HATA] config.local.php Git'te izleniyor! .gitignore kontrol edin." -ForegroundColor Red
        $errors++
    } else {
        Write-Host "[OK] Hassas config dosyasi Git disinda" -ForegroundColor Green
    }
}

Write-Host "`n=== SONUC ===" -ForegroundColor Cyan
if ($errors -gt 0) {
    Write-Host "Kontrol BASARISIZ - $errors hata bulundu." -ForegroundColor Red
    exit 1
}
Write-Host "Kontrol BASARILI - canliya gondermeye hazir." -ForegroundColor Green
exit 0
