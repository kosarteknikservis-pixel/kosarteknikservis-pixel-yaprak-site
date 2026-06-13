<?php
/**
 * Kredi kartı → ortak panel akışını doğrulama (CLI veya tarayıcı).
 * Kullanım: php scripts/check-common-panel-flow.php
 */
require_once __DIR__ . '/../xnull/controller/config.php';
require_once __DIR__ . '/../common_panel_sender.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Ortak Panel — Kredi Kartı Akış Kontrolü ===\n\n";

$settings = $db->query('SELECT ayar_common_panel_status, ayar_common_panel_url, ayar_common_panel_key, ayar_siteurl FROM ayar WHERE ayar_id=0')->fetch(PDO::FETCH_ASSOC);
$active = isset($settings['ayar_common_panel_status']) && (int) $settings['ayar_common_panel_status'] === 1;
echo 'Entegrasyon durumu: ' . ($active ? 'AKTİF' : 'PASİF') . "\n";
echo 'Panel URL: ' . ($settings['ayar_common_panel_url'] ?? '(boş)') . "\n";
echo 'API Key: ' . (trim((string) ($settings['ayar_common_panel_key'] ?? '')) !== '' ? '(tanımlı)' : '(boş)') . "\n\n";

echo "--- Akış özeti ---\n";
echo "Kapıda ödeme: sipariş formu → front-order-flow-process-post.php → sendOrderToCommonPanel (hemen)\n";
echo "Kredi kartı:  sipariş formu → (GÖNDERİLMEZ) → PayTR → pay_int.php → order_send_admin_new_order_notifications → sendOrderToCommonPanel\n\n";

$lastCard = $db->query(
    "SELECT s.* FROM siparis s
     WHERE s.siparis_odemeid = 6
     ORDER BY s.siparis_id DESC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!$lastCard) {
    echo "Son kredi kartı siparişi: bulunamadı (odeme_id=6).\n";
    exit(0);
}

echo "--- Son kredi kartı siparişi (#{$lastCard['siparis_id']}) ---\n";
echo 'Durum (siparis_durum): ' . ($lastCard['siparis_durum'] ?? '?') . " (-3=ödeme bekliyor, 0=yeni gelen)\n";
echo 'Ödeme onayı (siparis_durumpay): ' . ($lastCard['siparis_durumpay'] ?? '0') . " (1=PayTR başarılı)\n";
echo 'Müşteri: ' . ($lastCard['siparis_ad'] ?? '') . ' / ' . ($lastCard['siparis_tel'] ?? '') . "\n";
echo 'Tutar: ' . ($lastCard['siparis_fiyat'] ?? '') . " TL\n\n";

if ((int) ($lastCard['siparis_durumpay'] ?? 0) !== 1) {
    echo "NOT: Bu sipariş henüz PayTR onayı almamış → ortak panele gitmemiş olmalı.\n";
    exit(0);
}

if (!$active) {
    echo "UYARI: Ortak panel pasif — canlıda gönderim yapılmaz.\n";
    exit(0);
}

$host = parse_url((string) ($settings['ayar_siteurl'] ?? ''), PHP_URL_HOST) ?: 'localhost';
$testPayload = function_exists('order_build_common_panel_payload')
    ? order_build_common_panel_payload($lastCard, array('settings' => $settings, 'site_origin' => $host))
    : array();

echo "--- Test payload (ortak panele gidecek veri) ---\n";
$prepaid = function_exists('order_common_panel_is_prepaid') && order_common_panel_is_prepaid($lastCard);
echo 'Kredi kartı / prepaid: ' . ($prepaid ? 'EVET → order_total=0' : 'HAYIR → tam tutar') . "\n";
echo json_encode($testPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if (isset($argv[1]) && $argv[1] === '--send-test') {
    echo "Canlı test gönderimi (--send-test)...\n";
    unset($testPayload['dry_run']);
    $ok = sendOrderToCommonPanel($testPayload, $settings);
    echo $ok ? "Sonuç: HTTP 200 — ortak panel yanıt verdi.\n" : "Sonuç: BAŞARISIZ (timeout veya HTTP != 200).\n";
}
