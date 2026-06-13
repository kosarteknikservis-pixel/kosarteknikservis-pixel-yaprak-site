#!/bin/bash
# Hostinger VPS (AlmaLinux 9 + DirectAdmin) tek seferlik kurulum
# Kullanim: bash server-setup.sh

set -euo pipefail

echo "=== YAPRAK SITE - SUNUCU KURULUMU ==="

# DirectAdmin domain klasorlerini listele
echo ""
echo "Mevcut domain public_html yollari:"
find /home -maxdepth 4 -type d -name public_html 2>/dev/null | head -20 || true

read -rp "Deploy hedef public_html yolu (ornek: /home/admin/domains/site.com/public_html/): " REMOTE_DIR
REMOTE_DIR="${REMOTE_DIR%/}/"

if [ ! -d "$REMOTE_DIR" ]; then
  echo "Klasor bulunamadi: $REMOTE_DIR"
  exit 1
fi

# config.local.php (sunucuya ozel - Git'ten gelmez)
CONFIG_FILE="${REMOTE_DIR}xnull/controller/config.local.php"
if [ ! -f "$CONFIG_FILE" ]; then
  read -rp "Canli DB adi: " DB_NAME
  read -rp "Canli DB kullanici: " DB_USER
  read -rsp "Canli DB sifre: " DB_PASS
  echo ""
  cat > "$CONFIG_FILE" <<EOF
<?php
return [
    'dbHost' => '127.0.0.1',
    'dbAdi' => '${DB_NAME}',
    'Kullanici' => '${DB_USER}',
    'Sifre' => '${DB_PASS}',
];
EOF
  chmod 640 "$CONFIG_FILE"
  echo "[OK] config.local.php olusturuldu"
else
  echo "[OK] config.local.php zaten mevcut"
fi

# GeoIP dosyasi icin klasor
mkdir -p "${REMOTE_DIR}js/ajax/libs"

# Izinler
find "$REMOTE_DIR" -type d -exec chmod 755 {} \;
find "$REMOTE_DIR" -type f -exec chmod 644 {} \;

echo ""
echo "=== KURULUM TAMAM ==="
echo "Deploy hedefi: $REMOTE_DIR"
echo ""
echo "GitHub Secrets degerleri:"
echo "  SSH_HOST       = 187.124.22.157"
echo "  SSH_USERNAME   = root"
echo "  SSH_REMOTE_DIR = $REMOTE_DIR"
echo ""
echo "Local'den push: git push origin main"
