<?php
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

require_once __DIR__ . '/../include/legal-pages.php';
legal_pages_ensure_schema($db);

$settings = $db->prepare('SELECT * FROM ayar WHERE ayar_id=0');
$settings->execute();
$settingsprint = $settings->fetch(PDO::FETCH_ASSOC);
if (!is_array($settingsprint)) {
	$settingsprint = array();
}

if (isset($_POST['firma_kaydet'])) {
	if (empty($_SESSION['kullanici_adi'])) {
		header('Location: index.php?status=no');
		exit;
	}
	$upd = $db->prepare(
		'UPDATE ayar SET
			ayar_firma_unvan=:unvan,
			ayar_firma_tel=:tel,
			ayar_firma_adresi=:adres,
			ayar_firma_email=:email
		WHERE ayar_id=0'
	);
	$ok = $upd->execute(array(
		'unvan' => trim((string) ($_POST['ayar_firma_unvan'] ?? '')),
		'tel'   => trim((string) ($_POST['ayar_firma_tel'] ?? '')),
		'adres' => trim((string) ($_POST['ayar_firma_adresi'] ?? '')),
		'email' => trim((string) ($_POST['ayar_firma_email'] ?? '')),
	));
	header('Location: yasal-icerikler.php?status=' . ($ok ? 'ok' : 'no'));
	exit;
}

$firma = legal_pages_firma_info($settingsprint, null);
$siteBase = legal_pages_site_base($settingsprint);
$checks = array(
	array('label' => 'Telefon numarası', 'ok' => $firma['tel'] !== ''),
	array('label' => 'Adres', 'ok' => $firma['adres'] !== ''),
	array('label' => 'Teslimat koşulları sayfası', 'ok' => $siteBase !== ''),
	array('label' => 'Satış politikası sayfası', 'ok' => $siteBase !== ''),
	array('label' => 'İptal ve iade prosedürü sayfası', 'ok' => $siteBase !== ''),
);
?>
<section class="main-content container">
	<div class="page-header">
		<h2>Yasal Sayfalar (PayTR)</h2>
		<p class="text-muted">PayTR mağaza onayı için zorunlu iletişim bilgileri ve yasal sayfalar.</p>
	</div>

	<div class="row">
		<div class="col-md-5">
			<div class="card">
				<div class="card-heading card-default">Firma / İletişim Bilgileri</div>
				<div class="card-block">
					<form method="POST" action="">
						<div class="form-group">
							<label>Firma Unvanı</label>
							<input type="text" name="ayar_firma_unvan" class="form-control" value="<?php echo htmlspecialchars($firma['unvan'], ENT_QUOTES, 'UTF-8'); ?>">
						</div>
						<div class="form-group">
							<label>Telefon *</label>
							<input type="text" name="ayar_firma_tel" class="form-control" value="<?php echo htmlspecialchars($firma['tel'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="0xxx xxx xx xx">
						</div>
						<div class="form-group">
							<label>E-posta</label>
							<input type="email" name="ayar_firma_email" class="form-control" value="<?php echo htmlspecialchars($firma['email'], ENT_QUOTES, 'UTF-8'); ?>">
						</div>
						<div class="form-group">
							<label>Adres *</label>
							<textarea name="ayar_firma_adresi" class="form-control" rows="4" placeholder="Açık posta adresi"><?php echo htmlspecialchars($firma['adres'], ENT_QUOTES, 'UTF-8'); ?></textarea>
						</div>
						<button type="submit" name="firma_kaydet" class="btn btn-success btn-icon"><i class="fa fa-floppy-o"></i> Kaydet</button>
					</form>
				</div>
			</div>
		</div>

		<div class="col-md-7">
			<div class="card">
				<div class="card-heading card-default">PayTR Kontrol Listesi</div>
				<div class="card-block">
					<ul class="list-group">
						<?php foreach ($checks as $c) { ?>
							<li class="list-group-item">
								<i class="fa fa-<?php echo $c['ok'] ? 'check text-success' : 'times text-danger'; ?>"></i>
								<?php echo htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8'); ?>
							</li>
						<?php } ?>
					</ul>
				</div>
			</div>

			<div class="card" style="margin-top:16px;">
				<div class="card-heading card-default">Yasal Sayfa Düzenleme</div>
				<div class="card-block">
					<?php if ($siteBase !== '') { ?>
						<p><a href="<?php echo htmlspecialchars($siteBase . '/teslimat-kosullari', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Teslimat Koşulları</a> — <a href="teslimat-kosullari-yonet.php">Düzenle</a></p>
						<p><a href="<?php echo htmlspecialchars($siteBase . '/satis-politikasi', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Satış Politikası</a> — <a href="satis-politikasi-yonet.php">Düzenle</a></p>
						<p><a href="<?php echo htmlspecialchars($siteBase . '/iptal-iade', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">İptal ve İade</a> — <a href="iptal-iade-yonet.php">Düzenle</a></p>
					<?php } else { ?>
						<p class="text-muted">Site URL tanımlı değil. Genel ayarlardan site adresini kontrol edin.</p>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</section>
<?php include 'footer.php'; ?>
