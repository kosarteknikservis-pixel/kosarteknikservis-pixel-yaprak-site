<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';


if ( isset( $_POST[ 'ekle' ] ) )
{   
	$ad = htmlspecialchars(trim($_POST[ 'ad' ]));
	$tarih = htmlspecialchars(trim($_POST[ 'tarih' ]));
	$detay = trim($_POST[ 'detay' ]);
	$puan = htmlspecialchars(trim($_POST[ 'puan' ]));

	$uploads_dir = 'assets/img/yorumlar';
	$full_uploads_dir = dirname(__DIR__) . '/' . $uploads_dir;
	
	// Profil Resmi
	$refimgyol = "";
	if ( $_FILES[ 'gorsel' ][ "size" ] > 0 ) {
		@$tmp_name = $_FILES[ 'gorsel' ][ "tmp_name" ];
		$name = $_FILES[ 'gorsel' ][ "name" ];
		$ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg';
		$benzersizsayi1 = rand( 20000, 32000 );
		$benzersizsayi2 = rand( 20000, 32000 );
		$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
		$target_path    = "$full_uploads_dir/$benzersizad.$ext";
		if (@move_uploaded_file( $tmp_name, $target_path )) {
			$refimgyol  = $uploads_dir. "/" . $benzersizad . "." . $ext;
			@convertToWebp($target_path, "$full_uploads_dir/$benzersizad.webp");
		}
	}


	$kaydet = $db->prepare(
		"INSERT INTO yorumlar SET  
		ad=:ad,
		tarih=:tarih,
		detay=:detay,
		puan=:puan,
		gorsel=:gorsel,
        yorum_tip=:yorum_tip,
        sayfa_id=:sayfa_id,
        yorum_onay=:yorum_onay");
	$insert = $kaydet->execute(
		array(
			'ad' => $ad,
			'tarih' => $tarih,
			'detay' => $detay,
			'puan' => $puan,
			'gorsel' => $refimgyol,
            'yorum_tip' => $_POST['yorum_tip'],
            'sayfa_id' => intval($_POST['sayfa_id']),
            'yorum_onay' => 1 // Admin eklediği için varsayılan aktif
		));


	if ( $insert )
	{
		$yorum_id = $db->lastInsertId();

		// Çoklu Galeri Görselleri
		if (isset($_FILES['galeri']) && !empty($_FILES['galeri']['name'][0])) {
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
							'gorsel' => $uploads_dir . "/" . $benzersiz_ad . "." . $ext_gal
						));
					}
				}
			}
		}

		Header( "Location:yorumlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:yorumlar.php?status=no" ); exit;
	}
}
?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->
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
					Yorum Ekle
				</div>
				<div class="card-block">
					<form method="POST" action="" class="form-horizontal" enctype="multipart/form-data">
						<div class="form-group">
							<label>Ad soyad</label>
							<input type="text" name="ad" placeholder="Ad soyad giriniz." class="form-control">
						</div>
						<div class="form-group">
							<label>Tarih</label>
							<input type="date" name="tarih" placeholder="Tarih giriniz." class="form-control">
						</div>
						<div class="form-group">
							<label>Detay</label>
							<textarea class="summernote" name="detay" placeholder="içerik giriniz"></textarea>
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
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5" selected>5</option>
							</select>
						</div>
                        <div class="form-group">
							<label>Yorum Tipi</label>
							<select name="yorum_tip" id="yorum_tip" class="form-control m-b">
								<option value="index">Ana Sayfa</option>
								<option value="sayfa">Sayfa (Yazı/Blog)</option>
							</select>
						</div>
                        <div class="form-group" id="sayfa_secimi" style="display:none;">
							<label>Hangi Sayfaya Eklenecek?</label>
							<select name="sayfa_id" class="form-control m-b">
                                <option value="0">Sayfa Seçiniz</option>
								<?php 
                                $s_sor = $db->prepare("SELECT * FROM sayfalar ORDER BY sayfa_baslik ASC");
                                $s_sor->execute();
                                while($s_cek = $s_sor->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                    <option value="<?php echo $s_cek['sayfa_id']; ?>"><?php echo $s_cek['sayfa_baslik']; ?></option>
                                <?php } ?>
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
						<button style="cursor: pointer;" type="submit" name="ekle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Kaydet</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<?php include 'footer.php'; ?>
