<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$hesapedit=$db->prepare("SELECT * from yorumlar where id=:hesap_id");
$hesapedit->execute(array(
	'hesap_id' => $_GET['yorum_id']
));
$yrm=$hesapedit->fetch(PDO::FETCH_ASSOC);

if (!$yrm) {
	Header("Location:yorumlar.php?status=no");
	exit();
}

$Id =$yrm['id'];
if ( isset( $_POST[ 'yorumduzenle' ] ) )
{
	if ( $_FILES[ 'gorsel' ][ "size" ] > 0 )
	{

		$folder_name = 'assets/img/yorumlar';
        $uploads_dir = dirname(__DIR__) . '/' . $folder_name;
		@$tmp_name = $_FILES[ 'gorsel' ][ "tmp_name" ];
		$name = $_FILES[ 'gorsel' ][ "name" ];
		$ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg';
		$benzersizsayi1 = rand( 20000, 32000 );
		$benzersizsayi2 = rand( 20000, 32000 );
		$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
		$refimgyol      = $folder_name . "/" . $benzersizad . "." . $ext;
		
		if (@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad.$ext" )) {
			@convertToWebp("$uploads_dir/$benzersizad.$ext", "$uploads_dir/$benzersizad.webp");

			$duzenle = $db->prepare(
				"UPDATE yorumlar SET
				gorsel=:resim
				WHERE id=:id"
			);
			$update_img  = $duzenle->execute(
				array(
					'resim'  => $refimgyol,
					'id' => $yrm['id']
				)
			);
		}

	}

	// Çoklu Galeri Görselleri
	if (isset($_FILES['galeri']) && !empty($_FILES['galeri']['name'][0])) {
		$yorum_id = intval($_GET['yorum_id']);
		$uploads_base = 'assets/img/yorumlar';
		$full_uploads_dir = dirname(__DIR__) . '/' . $uploads_base;
		
		for ($i = 0; $i < count($_FILES['galeri']['name']); $i++) {
			if ($_FILES['galeri']['size'][$i] > 0) {
				@$tmp_name_gal = $_FILES['galeri']['tmp_name'][$i];
				$name_gal = $_FILES['galeri']['name'][$i];
				$ext_gal = pathinfo($name_gal, PATHINFO_EXTENSION) ?: 'jpg';
				$benzersiz1 = rand(20000, 32000);
				$benzersiz2 = rand(20000, 32000);
				$benzersiz_ad = $benzersiz1 . $benzersiz2;
				$target_path_gal = "$full_uploads_dir/$benzersiz_ad.$ext_gal";
				
				if (@move_uploaded_file($tmp_name_gal, $target_path_gal)) {
					@convertToWebp($target_path_gal, "$full_uploads_dir/$benzersiz_ad.webp");
					$gal_kaydet = $db->prepare("INSERT INTO yorum_gorsel SET yorum=:yorum, gorsel=:gorsel");
					$gal_kaydet->execute(array(
						'yorum' => $yorum_id,
						'gorsel' => $uploads_base . "/" . $benzersiz_ad . "." . $ext_gal
					));
				}
			}
		}
	}

	$duzenle = $db->prepare(
		"UPDATE yorumlar SET
		ad=:ad,
		tarih=:tarih,
		detay=:detay,
		puan=:puan,
        yorum_tip=:yorum_tip,
        sayfa_id=:sayfa_id,
        yorum_onay=:yorum_onay
		WHERE id=:id"
	);
	$update  = $duzenle->execute(
		array(
			'ad' => $_POST[ 'ad' ],
			'tarih' => $_POST[ 'tarih' ],
			'detay' => $_POST[ 'detay' ],
			'puan'  => $_POST[ 'puan' ],
            'yorum_tip' => $_POST['yorum_tip'],
            'sayfa_id' => intval($_POST['sayfa_id']),
            'yorum_onay' => intval($_POST['yorum_onay']),
			'id' => $yrm['id']
		)
	);


	if ( $update )
	{


		Header( "Location:?yorum_id=$Id&status=ok" );
		exit;
	}
	else
	{

		Header( "Location:?yorum_id=$Id&status=no" );
		exit;
	}
	
}


// Sıralama kaydetme
if (isset($_POST['yorum_gorsel_sira'])) {
	if (!$_SESSION['kullanici_adi']) {
		header("Location: index.php?status=no");
		exit();
	}
	
	parse_str($_POST['yorum_gorsel_sira'], $siraData);
	$siraArray = $siraData['rank'];
	
	foreach ($siraArray as $key => $value) {
		$kaydet = $db->prepare("UPDATE yorum_gorsel SET sira=:sira WHERE id=:id");
		$kaydet->execute(array(
			'sira' => $key,
			'id' => $value
		));
	}
	
	echo json_encode(array('success' => true));
	exit();
}

// Profil resmi silme
if ( isset($_GET[ 'profilsil' ]) && $_GET[ 'profilsil' ] == "ok" )
{   
	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: index.php?status=no" );
		exit();
	}
	$yorum = htmlspecialchars(trim(strip_tags($yrm[ 'id' ])));

	if (!empty($yrm['gorsel'])) {
		$resimsilunlink = dirname(__DIR__) . '/' . $yrm['gorsel'];
		if (file_exists($resimsilunlink)) {
			@unlink($resimsilunlink);
		}
		
		$duzenle = $db->prepare("UPDATE yorumlar SET gorsel='' WHERE id=:id");
		$duzenle->execute(array('id' => $yorum));
	}

	Header( "Location:?yorum_id=$yorum&status=ok" );
	exit;
}

// Ek görsel silme
if ( isset($_GET[ 'ekgorselsil' ]) && $_GET[ 'ekgorselsil' ] == "ok" )
{   
	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: index.php?status=no" );
		exit();
	}
	$yorum = htmlspecialchars(trim(strip_tags($yrm[ 'id' ])));

	if (!empty($yrm['gorsel_ek'])) {
		$resimsilunlink = dirname(__DIR__) . '/' . $yrm['gorsel_ek'];
		if (file_exists($resimsilunlink)) {
			@unlink($resimsilunlink);
		}
		
		$duzenle = $db->prepare("UPDATE yorumlar SET gorsel_ek='' WHERE id=:id");
		$duzenle->execute(array('id' => $yorum));
	}

	Header( "Location:?yorum_id=$yorum&status=ok" );
	exit;
}

if ( isset($_GET[ 'resimsil' ]) && $_GET[ 'resimsil' ] == "ok" )
{   
    

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: index.php?status=no" );
		exit();
	}
	$SilID = htmlspecialchars(trim(strip_tags($_GET[ 'id' ])));
	$yorum = htmlspecialchars(trim(strip_tags($yrm[ 'id' ])));

	$hesapedit=$db->prepare("SELECT * from yorum_gorsel where id=:urun_id");
	$hesapedit->execute(array(
		'urun_id' => $SilID
	));
	$yrmSon=$hesapedit->fetch(PDO::FETCH_ASSOC);




	$sil     = $db->prepare( "DELETE from yorum_gorsel where id=:urun_id" );
	$kontrol = $sil->execute(
		array(
			'urun_id' => $SilID
		)
	);

	if ( $kontrol && $yrmSon )
	{
		$resimsilunlink = dirname(__DIR__) . '/' . $yrmSon['gorsel'];
		if (!empty($yrmSon['gorsel']) && file_exists($resimsilunlink)) {
			@unlink($resimsilunlink);
		}

		Header( "Location:?yorum_id=$yorum&status=ok" );
		exit;
	}
	else
	{

		Header( "Location:?yorum_id=$yorum&status=no" ); exit;
	}
}

?>		
<section class="main-content container">
	<div class="page-header">
		<h2>Yorum İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="yorumlar.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
					</div>
					Yorum Düzenle
				</div>
				<div class="card-block">

					<form method="POST" action="" class="form-horizontal" enctype="multipart/form-data">
						<div class="form-group">
							<label>Ad soyad</label>
							<input type="text" name="ad" value="<?=$yrm['ad']?>" class="form-control">
						</div>
						<div class="form-group">
							<label>Tarih</label>
							<?php 
								$formatted_date = "";
								if (!empty($yrm['tarih'])) {
									$formatted_date = date('Y-m-d', strtotime($yrm['tarih']));
								}
							?>
							<input type="date" name="tarih" value="<?=$formatted_date?>" class="form-control">
						</div>
						<div class="form-group">
							<label>Detay</label>
							<textarea class="summernote" name="detay" placeholder="içerik giriniz"><?=$yrm['detay']?></textarea>
						</div>
					<div class="form-group">
						<label>Yüklü Resim (Profil Resmi)</label>
						<?php if(!empty($yrm['gorsel'])) { 
							// Görsel yolu düzelt - xnull/ öneki ekle
							$gorsel_yol = $yrm['gorsel'];
							if (strpos($gorsel_yol, 'http') === false && strpos($gorsel_yol, '/') !== 0) {
								$gorsel_yol = ltrim($gorsel_yol, '/');
							}
						?>
							<p>
								<img style="max-height: 100px;max-width: 100px; border: 2px solid #ddd; border-radius: 8px; padding: 5px;" src="<?=SITE_URL?><?php echo $gorsel_yol; ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
								<div style="display:none; color: #d32f2f; font-size: 12px; margin-top: 5px;">Görsel bulunamadı: <?php echo htmlspecialchars($gorsel_yol); ?></div>
								<br><br>
								<a href="yorum-duzenle.php?yorum_id=<?=$yrm['id']?>&profilsil=ok" 
								   class="btn btn-xs btn-danger" 
								   onclick="return confirm('Profil resmini silmek istediğinize emin misiniz?')">
									<i class="fa fa-trash"></i> Profil Resmini Sil
								</a>
							</p>
						<?php } else { ?>
							<p class="text-muted">Profil resmi yüklenmemiş.</p>
						<?php } ?>
					</div>
						<div class="form-group">
							<label>Profil Resim</label>
							<div class="fileinput fileinput-new input-group col-md-3" data-provides="fileinput">
								<div class="form-control" data-trigger="fileinput"><span class="fileinput-filename"></span></div>
								<span class="input-group-addon btn btn-primary btn-file ">
									<span class="fileinput-new">Yükle</span>
									<span class="fileinput-exists">Değiştir</span>
									<input type="file" name="gorsel">
								</span>
								<a href="#" class="input-group-addon btn btn-danger  hover fileinput-exists" data-dismiss="fileinput">Sil</a>
							</div>
						</div>
                        
						<div class="form-group">
							<label>Galeri Görselleri (Çoklu Seçim Yapabilirsiniz)</label>
							<div class="fileinput fileinput-new input-group col-md-3" data-provides="fileinput">
								<div class="form-control" data-trigger="fileinput"><span class="fileinput-filename"></span></div>
								<span class="input-group-addon btn btn-primary btn-file ">
									<span class="fileinput-new">Yükle</span>
									<span class="fileinput-exists">Değiştir</span>
									<input type="file" name="galeri[]" multiple>
								</span>
								<a href="#" class="input-group-addon btn btn-danger  hover fileinput-exists" data-dismiss="fileinput">Sil</a>
							</div>
						</div>
						<div class="form-group">
							<label>Puan</label>
							<select name="puan" class="form-control m-b">
								<option value="1" <?php if ($yrm['puan']==1) { ?> selected <?php } ?>>1</option>
								<option value="2" <?php if ($yrm['puan']==2) { ?> selected <?php } ?>>2</option>
								<option value="3" <?php if ($yrm['puan']==3) { ?> selected <?php } ?>>3</option>
								<option value="4" <?php if ($yrm['puan']==4) { ?> selected <?php } ?>>4</option>
								<option value="5" <?php if ($yrm['puan']==5) { ?> selected <?php } ?>>5</option>
							</select>
						</div>
                        <div class="form-group">
							<label>Yorum Tipi</label>
							<select name="yorum_tip" id="yorum_tip" class="form-control m-b">
								<option value="index" <?php echo ($yrm['yorum_tip'] == 'index' || empty($yrm['yorum_tip'])) ? 'selected' : ''; ?>>Ana Sayfa</option>
								<option value="sayfa" <?php echo ($yrm['yorum_tip'] == 'sayfa') ? 'selected' : ''; ?>>Sayfa (Yazı/Blog)</option>
							</select>
						</div>
                        <div class="form-group" id="sayfa_secimi" style="<?php echo ($yrm['yorum_tip'] == 'sayfa') ? '' : 'display:none;'; ?>">
							<label>Hangi Sayfaya Eklenecek?</label>
							<select name="sayfa_id" class="form-control m-b">
                                <option value="0">Sayfa Seçiniz</option>
								<?php 
                                $s_sor = $db->prepare("SELECT * FROM sayfalar ORDER BY sayfa_baslik ASC");
                                $s_sor->execute();
                                while($s_cek = $s_sor->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                    <option value="<?php echo $s_cek['sayfa_id']; ?>" <?php echo ($yrm['sayfa_id'] == $s_cek['sayfa_id']) ? 'selected' : ''; ?>><?php echo $s_cek['sayfa_baslik']; ?></option>
                                <?php } ?>
							</select>
						</div>
                        <div class="form-group">
							<label>Durum (Onay)</label>
							<select name="yorum_onay" class="form-control m-b">
								<option value="1" <?php echo ($yrm['yorum_onay'] == 1) ? 'selected' : ''; ?>>Aktif (Yayında)</option>
								<option value="0" <?php echo ($yrm['yorum_onay'] == 0) ? 'selected' : ''; ?>>Pasif (İncelemede)</option>
							</select>
						</div>
                        <script>
                        document.getElementById('yorum_tip').addEventListener('change', function() {
                            if (this.value == 'sayfa') {
                                document.getElementById('sayfa_secimi').style.display = 'block';
                            } else {
                                document.getElementById('sayfa_secimi').style.display = 'none';
                            }
                        });
                        </script>
						<button style="cursor: pointer;" type="submit" name="yorumduzenle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Kaydet</button>
					</form>
					<br>
					<hr>
					<h6>Resim eklediğinizde sayfayı yenileyiniz. Sıra: sol üstteki çubuk simgesinden sürükleyin (mobilde sayfa kaydırması için).</h6>
					<div class="card-block">
						<div class="lightboxGallery sortable" style="display: flex; flex-wrap: wrap; gap: 15px;">
							<?php
							// Önce sira sütununu kontrol et ve yoksa ekle
							try {
								$db->query("ALTER TABLE yorum_gorsel ADD COLUMN sira INT(11) DEFAULT 0");
							} catch(PDOException $e) {
								// Sütun zaten varsa hata vermez
							}
							
							$picsor=$db->prepare("SELECT * from yorum_gorsel where yorum=:ID order by COALESCE(sira, id) ASC, id ASC");
							$picsor->execute(array('ID' => $yrm['id']));
							while ($picprint=$picsor->fetch(PDO::FETCH_ASSOC)) { 
								// Görsel yolu düzelt - xnull/ öneki ekle
								$pic_gorsel_yol = $picprint['gorsel'];
								if (strpos($pic_gorsel_yol, 'http') === false && strpos($pic_gorsel_yol, '/') !== 0) {
									$pic_gorsel_yol = ltrim($pic_gorsel_yol, '/');
								}
							?>
								<div id="rank-<?php echo $picprint['id']; ?>" style="position: relative; border: 3px solid #ddd; border-radius: 8px; padding: 5px; background: #fff; cursor: default; transition: all 0.3s ease;">
									<span class="yorum-gorsel-sort-handle" title="Sıra için sürükleyin" role="button" aria-label="Sıra değiştir" style="position: absolute; top: 5px; left: 5px; z-index: 6;"><i class="fa fa-bars" style="pointer-events: none;"></i></span>
									<a href="#" data-toggle="modal" data-target="#textModal<?php echo $picprint['id']; ?>" style="display: block;">
										<img style="max-width: 200px; max-height: 200px; display: block; border-radius: 4px;" src="<?=SITE_URL?><?php echo $pic_gorsel_yol; ?>" alt="" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'padding:20px;text-align:center;color:#999;\'>Görsel bulunamadı</div>';">
									</a>
									<div style="position: absolute; top: 5px; right: 5px; display: flex; gap: 5px;">
										<a href="yorum-duzenle.php?yorum_id=<?=$yrm['id']?>&resimsil=ok&id=<?php echo $picprint['id']; ?>" 
										   class="btn btn-xs btn-danger" 
										   onclick="return confirm('Bu görseli silmek istediğinize emin misiniz?')" 
										   style="padding: 5px 10px; border-radius: 4px;"
										   title="Sil">
											<i class="fa fa-trash"></i>
										</a>
									</div>
									<div class="modal fade bs-example-modal-lg" id="textModal<?php echo $picprint['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="textModal">
										<div class="modal-dialog modal-lg" role="document">
											<div class="modal-content">
												<div class="modal-body">
													<p>
														<img style="max-width: 100%; max-height: 600px;" src="<?=SITE_URL?><?php echo $pic_gorsel_yol; ?>" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'padding:20px;text-align:center;color:#999;\'>Görsel bulunamadı</div>';">
													</p><br>
													<div class="text-center">
														<a href="yorum-duzenle.php?yorum_id=<?=$yrm['id']?>&resimsil=ok&id=<?php echo $picprint['id']; ?>" 
														   class="btn btn-danger btn-icon"
														   onclick="return confirm('Bu görseli silmek istediğinize emin misiniz?')">
															<i class="fa fa-trash-o"></i> Resmi Sil
														</a>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
					<script>
					$(document).ready(function() {
						$(".sortable").sortable({
							handle: ".yorum-gorsel-sort-handle",
							placeholder: "ui-state-highlight",
							scroll: true,
							scrollSensitivity: 48,
							update: function(event, ui) {
								var sira = $(this).sortable("serialize");
								$.ajax({
									url: 'yorum-duzenle.php',
									type: 'POST',
									data: { yorum_gorsel_sira: sira },
									success: function(response) {
										// Sessizce kaydedildi
									}
								});
							}
						});
						$(".sortable").disableSelection();
					});
					</script>
					<style>
					.yorum-gorsel-sort-handle {
						cursor: grab;
						touch-action: none;
						-ms-touch-action: none;
						padding: 4px 8px;
						background: rgba(255,255,255,0.95);
						border-radius: 4px;
						box-shadow: 0 1px 4px rgba(0,0,0,0.15);
						color: #555;
						font-size: 14px;
						line-height: 1;
					}
					.yorum-gorsel-sort-handle:active {
						cursor: grabbing;
					}
					.ui-state-highlight {
						height: 200px;
						border: 3px dashed #007bff !important;
						background: #f0f8ff !important;
					}
					.sortable > div:hover {
						border-color: #007bff !important;
						box-shadow: 0 4px 8px rgba(0,0,0,0.2);
						transform: translateY(-2px);
					}
					</style>
					<hr>
				</div>
			</div>
		</div>
	</div>

	<?php include 'footer.php'; ?>
