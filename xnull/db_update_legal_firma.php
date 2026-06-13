<?php
include_once 'controller/config.php';

if (empty($_SESSION['kullanici_adi'])) {
	header('Location: login.php');
	exit;
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font:14px/1.5 monospace;padding:16px">';

if (!isset($db)) {
	die("Hata: Veritabani baglantisi yok.\n");
}

require_once __DIR__ . '/../include/legal-pages.php';
legal_pages_ensure_schema($db);

$cols = array('ayar_firma_unvan', 'ayar_firma_tel', 'ayar_firma_adresi', 'ayar_firma_email');
foreach ($cols as $col) {
	$chk = $db->query('SHOW COLUMNS FROM `ayar` LIKE ' . $db->quote($col));
	echo ($chk && $chk->rowCount() > 0 ? 'OK' : 'EKSIK') . "  $col\n";
}

$row = $db->query('SELECT ayar_firma_unvan, ayar_firma_tel, ayar_firma_adresi, ayar_firma_email FROM ayar WHERE ayar_id=0')->fetch(PDO::FETCH_ASSOC);
echo "\nMevcut degerler (ayar_id=0):\n";
print_r($row ?: array());

echo "\nTamam. Panel → Yasal Sayfalar (PayTR) → bilgileri tekrar kaydedin.\n";
echo '</pre>';
