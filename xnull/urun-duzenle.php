<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$urunedit=$db->prepare("SELECT * from urunler where urun_id=:urun_id");
$urunedit->execute(array(
	'urun_id' => $_GET['urun_id']
));
$urunwrite=$urunedit->fetch(PDO::FETCH_ASSOC);

if (!$urunwrite) {
    header("Location: urunler.php?status=notfound");
    exit();
}
?>		
<section class="main-content container">
	<div class="page-header">
		<h2>Ürün İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="urunler.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
					</div>
					Ürün Düzenle
				</div>
				<div class="card-block">

					<form method="POST" action="controller/function.php" enctype="multipart/form-data" class="form-horizontal">
						<div class="form-group">
							<input type="hidden" name="urun_id" value="<?php echo $urunwrite['urun_id']; ?>">
						</div>					
						<div class="form-group">
							<label>Ürün Başlık</label>
							<textarea class="summernote" name="urun_baslik"><?php echo $urunwrite['urun_baslik']; ?></textarea>
						</div>
                        <div class="form-group">
                            <label>Ürün Alt Başlık (Yeni Tasarım İçin)</label>
                            <input type="text" name="urun_alt_baslik" value="<?php echo $urunwrite['urun_alt_baslik']; ?>" class="form-control" placeholder="Örn: Kargo Ücretsiz">
                        </div>
                        <div class="form-group">
                            <label>Ürün Etiketi (Badge)</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" name="urun_etiket" value="<?php echo $urunwrite['urun_etiket']; ?>" class="form-control" placeholder="Etiket Metni">
                                </div>
                                <div class="col-md-3">
                                    <label><small>Arkaplan Rengi</small></label>
                                    <input type="color" name="urun_etiket_bg" class="form-control" value="<?php echo !empty($urunwrite['urun_etiket_bg']) ? $urunwrite['urun_etiket_bg'] : '#2c3e50'; ?>" style="height: 38px; padding: 2px;">
                                </div>
                                <div class="col-md-3">
                                    <label><small>Yazı Rengi</small></label>
                                    <input type="color" name="urun_etiket_color" class="form-control" value="<?php echo !empty($urunwrite['urun_etiket_color']) ? $urunwrite['urun_etiket_color'] : '#ffffff'; ?>" style="height: 38px; padding: 2px;">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h4>SEO Ayarları / Etiketler</h4>
                        <div class="form-group">
                            <label>SEO Başlık (Title)</label> <small>Google'da görünecek başlık</small>
                            <input type="text" name="urun_seo_baslik" value="<?php echo $urunwrite['urun_seo_baslik']; ?>" class="form-control" placeholder="Örn: En Ucuz X Ürünü - Marka Adı">
                        </div>
                        <div class="form-group">
                            <label>SEO Açıklama (Description)</label> <small>Google arama sonuçlarında çıkan kısa açıklama</small>
                            <textarea name="urun_seo_aciklama" class="form-control" rows="2" placeholder="Ürün içeriği hakkında kısa bilgi..."><?php echo $urunwrite['urun_seo_aciklama']; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Etiketler / Anahtar Kelimeler (Keywords)</label> <small>Virgül ile ayırın: etiket1, etiket2, etiket3</small>
                            <input type="text" name="urun_seo_anahtar" value="<?php echo $urunwrite['urun_seo_anahtar']; ?>" class="form-control" placeholder="tag1, tag2, tag3">
                        </div>
                        <hr>

						<div class="form-group">
							<label>Ürün Fiyat</label>
							<div class="input-group col-md-4">
								<span class="input-group-addon"><i class="fa fa-try"></i></span>
								<input type="text" name="urun_fiyat" value="<?php echo $urunwrite['urun_fiyat']; ?>" class="form-control">
							</div>     	
						</div>	
                        <div class="form-group">
                            <label>Eski Fiyat (Üstü Çizili)</label>
                            <div class="input-group col-md-4">
                                <span class="input-group-addon"><i class="fa fa-try"></i></span>
                                <input type="text" class="form-control" name="urun_eski_fiyat" value="<?php echo $urunwrite['urun_eski_fiyat']; ?>" placeholder="Örn: 1500">
                            </div>
                        </div>

                        <?php
                        $_ubg = !empty($urunwrite['urun_fiyat_birim_goster']);
                        $_ubo = isset($urunwrite['urun_fiyat_birim_olcek']) ? (float) $urunwrite['urun_fiyat_birim_olcek'] : 1.0;
                        if ($_ubo < 0.5) { $_ubo = 0.5; }
                        if ($_ubo > 2.5) { $_ubo = 2.5; }
                        $_ubr_def = trim((string)($urunwrite['urun_fiyat_birim_renk'] ?? ''));
                        if ($_ubr_def === '' || !preg_match('/^#[0-9A-Fa-f]{3,8}$/', $_ubr_def)) {
                            $_ubr_def = '#64748b';
                        }
                        ?>
                        <div class="card" style="margin-bottom: 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden;">
                            <div class="card-heading card-default" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #e2e8f0; padding: 12px 16px; margin: 0;">
                                <strong style="color: #334155;"><i class="fa fa-tag" style="color: #0d9488;"></i> Fiyat birimi (vitrin)</strong>
                                <small class="text-muted" style="display: block; margin-top: 4px; font-weight: normal;">Kapalıyken sitede gösterilmez. Açıkken metin doluysa fiyat altında görünür.</small>
                            </div>
                            <div class="card-block" style="padding: 16px 18px; background: #fff;">
                                <div class="form-group" style="margin-bottom: 14px;">
                                    <label class="checkbox-inline" style="font-weight: 600; cursor: pointer; user-select: none;">
                                        <input type="hidden" name="urun_fiyat_birim_goster" value="0">
                                        <input type="checkbox" name="urun_fiyat_birim_goster" value="1" <?php echo $_ubg ? 'checked' : ''; ?> style="margin-top: 2px;">
                                        Vitrinde göster (açık / kapalı)
                                    </label>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Birim metni</label>
                                            <input type="text" name="urun_fiyat_birim_metin" class="form-control" maxlength="64" value="<?php echo htmlspecialchars((string)($urunwrite['urun_fiyat_birim_metin'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="örn: / 1 m²">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Yazı rengi</label>
                                            <input type="color" name="urun_fiyat_birim_renk" class="form-control" value="<?php echo htmlspecialchars($_ubr_def, ENT_QUOTES, 'UTF-8'); ?>" style="height: 38px; padding: 2px;">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Yazı büyüklüğü</label>
                                            <input type="number" name="urun_fiyat_birim_olcek" class="form-control" step="0.05" min="0.5" max="2.5" value="<?php echo htmlspecialchars(number_format($_ubo, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>" title="0.5 – 2.5 arası çarpan">
                                            <small class="text-muted">1.0 = normal, 1.2 = %20 büyük</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

						<?php if (!empty($urunwrite['urun_resim'])) { 
							// Görsel yolu düzelt - assets/img/urunler veya upload/ olabilir
							$resim_yol = $urunwrite['urun_resim'];
							if (strpos($resim_yol, 'assets/img/urunler') !== false) {
								// Zaten assets/img/urunler formatında
								$resim_yol = $resim_yol;
							} elseif (strpos($resim_yol, 'upload/') === 0) {
								// Eski upload/ formatında, assets/img/urunler'e taşı
								$resim_yol = 'assets/img/urunler/' . str_replace('upload/', '', $resim_yol);
							} else {
								// Diğer durumlarda assets/img/urunler ekle
								$resim_yol = 'assets/img/urunler/' . ltrim($resim_yol, '/');
							}
						?>
						<div class="form-group">
							<label>Mevcut Ürün Resmi</label>
							<img style="max-height: 150px;max-width: 150px; border: 2px solid #ddd; border-radius: 8px; padding: 5px; display: block; margin-bottom: 10px;" class="img-responsive" src="../<?php echo htmlspecialchars($resim_yol); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
							<div style="display:none; color: #d32f2f; font-size: 12px; margin-top: 5px;">Görsel bulunamadı: <?php echo htmlspecialchars($resim_yol); ?></div>
						</div>
						<?php } ?>
						<div class="form-group">
							<label>Ürün Resmi <?php if (!empty($urunwrite['urun_resim'])) { ?>(Değiştirilmeyecekse boş bırakın)<?php } ?></label>
							<input class="form-control" type="file" name="urun_resim">
						</div>
						<?php if (!empty($urunwrite['urun_resimsec'])) { 
							// Görsel yolu düzelt - assets/img/urunler veya upload/ olabilir
							$resimsec_yol = $urunwrite['urun_resimsec'];
							if (strpos($resimsec_yol, 'assets/img/urunler') !== false) {
								// Zaten assets/img/urunler formatında
								$resimsec_yol = $resimsec_yol;
							} elseif (strpos($resimsec_yol, 'upload/') === 0) {
								// Eski upload/ formatında, assets/img/urunler'e taşı
								$resimsec_yol = 'assets/img/urunler/' . str_replace('upload/', '', $resimsec_yol);
							} else {
								// Diğer durumlarda assets/img/urunler ekle
								$resimsec_yol = 'assets/img/urunler/' . ltrim($resimsec_yol, '/');
							}
						?>
						<div class="form-group">
							<label>Mevcut Ürün Seçildi Resmi</label>
							<img style="max-height: 150px;max-width: 150px; border: 2px solid #ddd; border-radius: 8px; padding: 5px; display: block; margin-bottom: 10px;" class="img-responsive" src="../<?php echo htmlspecialchars($resimsec_yol); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
							<div style="display:none; color: #d32f2f; font-size: 12px; margin-top: 5px;">Görsel bulunamadı: <?php echo htmlspecialchars($resimsec_yol); ?></div>
						</div>
						<?php } ?>
						<div class="form-group">
							<label>Ürün Seçildi Resmi (Değiştirilmeyecekse boş bırakın)</label>
							<input class="form-control" type="file" name="urun_resimsec">
						</div>
						<hr style="margin: 24px 0;">
						<h4 style="margin-bottom: 10px;">Sipariş tamamlama sayfası (sepet özeti)</h4>
						<p class="text-muted" style="font-size: 13px; margin-bottom: 14px;">Bu görsel yalnızca <strong>sipariş tamamlama</strong> ekranında bu ürün için gösterilir; vitrin / ana sayfa görselinden bağımsızdır. Boş bırakırsanız sipariş sayfasında vitrin ürün resmi kullanılır.</p>
						<input type="hidden" name="eskiyol_urun_siparis_kart" value="<?php echo htmlspecialchars(isset($urunwrite['urun_siparis_kart']) ? (string) $urunwrite['urun_siparis_kart'] : '', ENT_QUOTES, 'UTF-8'); ?>">
						<?php
						$usk = isset($urunwrite['urun_siparis_kart']) ? trim((string) $urunwrite['urun_siparis_kart']) : '';
						if ($usk !== '') {
							$usk_yol = $usk;
							if (strpos($usk_yol, 'assets/img/urunler') !== false) {
								$usk_yol = $usk_yol;
							} elseif (strpos($usk_yol, 'upload/') === 0) {
								$usk_yol = 'assets/img/urunler/' . str_replace('upload/', '', $usk_yol);
							} else {
								$usk_yol = 'assets/img/urunler/' . ltrim($usk_yol, '/');
							}
						?>
						<div class="form-group">
							<label>Mevcut sipariş kartı görseli</label>
							<img style="max-height: 150px; max-width: 150px; border: 2px solid #ddd; border-radius: 8px; padding: 5px; display: block; margin-bottom: 10px; background: linear-gradient(160deg,#1e293b 0%,#0f172a 100%); object-fit: contain;" class="img-responsive" src="../<?php echo htmlspecialchars($usk_yol, ENT_QUOTES, 'UTF-8'); ?>" alt="">
						</div>
						<?php } ?>
						<div class="form-group">
							<label>Sipariş kartı görseli <?php if ($usk !== '') { ?>(Değiştirilmeyecekse boş bırakın)<?php } ?></label>
							<input class="form-control" type="file" name="urun_siparis_kart" accept="image/jpeg,image/png,image/webp,image/gif">
						</div>
						<?php if ($usk !== '') { ?>
						<div class="form-group">
							<label class="checkbox-inline" style="font-weight: normal;">
								<input type="checkbox" name="sil_urun_siparis_kart" value="1" style="margin-top: 2px;"> Sipariş kartı görselini sil (siparişte vitrin resmi kullanılsın)
							</label>
						</div>
						<?php } ?>
                        <div class="form-group">
                            <label>Ürün Sırası</label>
                            <input type="number" name="urun_siralama" class="form-control" value="<?php echo $urunwrite['urun_siralama']; ?>">
                        </div>
						<button name="urunduzenle" style="cursor: pointer;" class="btn btn-success btn-icon btn-save"><i class="fa fa-floppy-o "></i>Güncelle</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<?php include 'footer.php'; ?>
