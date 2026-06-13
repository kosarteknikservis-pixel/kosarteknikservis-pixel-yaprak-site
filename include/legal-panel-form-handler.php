<?php
if (!isset($legal_table, $legal_title)) {
	return;
}

require_once __DIR__ . '/legal-pages.php';

if (!isset($_POST['duzenle'])) {
	return;
}

if (empty($_SESSION['kullanici_adi'])) {
	header('Location: index.php?status=no');
	exit;
}

legal_pages_ensure_schema($db);

$allowed = array('teslimat_kosullari', 'satis_politikasi', 'iptal_iade');
if (!in_array($legal_table, $allowed, true)) {
	header('Location: index.php?status=no');
	exit;
}

try {
	$upd = $db->prepare("UPDATE {$legal_table} SET ad=:ad, icerik=:icerik WHERE id=1");
	$ok = $upd->execute(array(
		'ad'     => trim((string) ($_POST['ad'] ?? $legal_title)),
		'icerik' => (string) ($_POST['icerik'] ?? ''),
	));
} catch (Throwable $e) {
	$ok = false;
}

$self = basename($_SERVER['PHP_SELF'] ?? 'index.php');
header('Location: ' . $self . '?status=' . ($ok ? 'ok' : 'no'));
exit;
