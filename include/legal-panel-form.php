<?php
if (!isset($legal_table, $legal_title)) {
	header('Location: index.php?status=no');
	exit;
}

require_once __DIR__ . '/legal-pages.php';

legal_pages_ensure_schema($db);

$settings = $db->prepare('SELECT * FROM ayar WHERE ayar_id=0');
$settings->execute();
$settingsprint = $settings->fetch(PDO::FETCH_ASSOC);
if (!is_array($settingsprint)) {
	$settingsprint = array();
}

$allowed = array('teslimat_kosullari', 'satis_politikasi', 'iptal_iade');
if (!in_array($legal_table, $allowed, true)) {
	header('Location: index.php?status=no');
	exit;
}

$row = legal_pages_fetch_row($db, $legal_table);
if (!is_array($row)) {
	$row = array('id' => 1, 'ad' => $legal_title, 'icerik' => '');
}

$firma = legal_pages_firma_info($settingsprint, null);
$pageBody = $row['icerik'] ?? '';
if (trim(strip_tags((string) $pageBody)) === '') {
	$pageBody = legal_pages_default_content($legal_table, $firma);
}

if (isset($_POST['duzenle'])) {
	if (empty($_SESSION['kullanici_adi'])) {
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
	header('Location: ' . basename($_SERVER['PHP_SELF']) . '?status=' . ($ok ? 'ok' : 'no'));
	exit;
}

$siteBase = legal_pages_site_base($settingsprint);
$slugMap = array(
	'teslimat_kosullari' => 'teslimat-kosullari.php',
	'satis_politikasi'     => 'satis-politikasi.php',
	'iptal_iade'           => 'iptal-iade.php',
);
$publicSlug = $slugMap[$legal_table] ?? '';
$pageAd = trim((string) ($row['ad'] ?? ''));
if ($pageAd === '') {
	$pageAd = $legal_title;
}
?>
<section class="main-content container">
	<div class="page-header">
		<h2><?php echo htmlspecialchars($legal_title, ENT_QUOTES, 'UTF-8'); ?></h2>
		<?php if ($publicSlug !== '' && $siteBase !== '') { ?>
			<p class="text-muted" style="margin-top:8px;">
				Canlı sayfa:
				<a href="<?php echo htmlspecialchars($siteBase . '/' . $publicSlug, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($siteBase . '/' . $publicSlug, ENT_QUOTES, 'UTF-8'); ?></a>
			</p>
		<?php } ?>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default"><?php echo htmlspecialchars($legal_title, ENT_QUOTES, 'UTF-8'); ?> Düzenle</div>
				<div class="card-block">
					<form method="POST" action="" class="form-horizontal">
						<div class="form-group">
							<label>Sayfa Başlık</label>
							<input type="text" name="ad" value="<?php echo htmlspecialchars($pageAd, ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
						</div>
						<div class="form-group">
							<label>İçerik</label>
							<textarea class="summernote" name="icerik"><?php echo $pageBody; ?></textarea>
						</div>
						<button style="cursor:pointer;" type="submit" name="duzenle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o"></i> Güncelle</button>
						<a href="yasal-icerikler.php" class="btn btn-default">Yasal Sayfalar (PayTR)</a>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>
