<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

// Toplu İşlem Mantığı
if (isset($_POST['topluislem'])) {
    if (!$_SESSION['kullanici_adi']) {
        header("Location: index.php?status=no");
        exit();
    }

    $islem = $_POST['islem'];
    $ids = isset($_POST['id']) ? $_POST['id'] : [];

    if ($islem == "hepsini_sil") {
        // Tüm görselleri çek
        $tum_gorseller = $db->prepare("SELECT * FROM resimgaleri");
        $tum_gorseller->execute();
        while ($gorsel = $tum_gorseller->fetch(PDO::FETCH_ASSOC)) {
            $dosya_yolu = "../" . $gorsel['resim_link'];
            if (file_exists($dosya_yolu)) {
                unlink($dosya_yolu);
            }
        }
        // Tüm tabloyu boşalt
        $sil = $db->prepare("DELETE FROM resimgaleri");
        $kontrol = $sil->execute();
        Header("Location:?status=" . ($kontrol ? "ok" : "no"));
        exit();
    } elseif ($islem == "secilenleri_sil" && !empty($ids)) {
        foreach ($ids as $id) {
            // Önce dosya yolunu bul
            $gorselsor = $db->prepare("SELECT * FROM resimgaleri WHERE resim_id=:id");
            $gorselsor->execute(['id' => $id]);
            $gorsel = $gorselsor->fetch(PDO::FETCH_ASSOC);
            
            if ($gorsel) {
                $dosya_yolu = "../" . $gorsel['resim_link'];
                if (file_exists($dosya_yolu)) {
                    unlink($dosya_yolu);
                }
                
                // DB'den sil
                $sil = $db->prepare("DELETE FROM resimgaleri WHERE resim_id=:id");
                $sil->execute(['id' => $id]);
            }
        }
        Header("Location:?status=ok");
        exit();
    }
}

$picsor=$db->prepare("SELECT * from resimgaleri order by sira ASC");
$picsor->execute();
$link = rtrim($settingsprint['ayar_siteurl'], "/")."/xnull/sortable.php";
?>		  

<script>
	$(document).ready( function() {

		$( ".sortable" ).sortable({
			handle: ".gallery-sort-handle",
			items: "> [id^='rank-']",
			placeholder: "gallery-sort-placeholder",
			forcePlaceholderSize: true,
			tolerance: "pointer",
			scroll: true,
			scrollSensitivity: 48,
			cancel: "input,textarea,select,option,a,.sectum",
			// 54MB gibi dev görselleri sürüklerken klonlamayın — tarayıcı donar
			helper: function(e, ui) {
				var $clone = ui.clone();
				$clone.find('img, video, source').remove();
				$clone.css({
					width: ui.outerWidth(),
					opacity: 0.92,
					boxShadow: '0 8px 24px rgba(15,23,42,0.18)'
				});
				return $clone;
			},
			start: function(e, ui) {
				ui.placeholder.height(ui.item.outerHeight());
				$('.gallery-sort-col').addClass('is-sorting');
			},
			stop: function() {
				$('.gallery-sort-col').removeClass('is-sorting');
			}
		});
		$( ".sortable" ).on("sortupdate", function (){
			var blog = $(this).sortable("serialize");
			var url = "<?php echo $link ?>";
			$.post(url, { blog: blog }, function(response) {
				if (response && String(response).indexOf('OK') === -1) {
					alert('Sıra kaydedilemedi. Sayfayı yenileyip tekrar deneyin.');
				}
			}).fail(function() {
				alert('Sıra kaydedilemedi. Oturum veya bağlantıyı kontrol edin.');
			});
		});

        // Hepsi Seç Mantığı
        $("#hepsini-sec").click(function () {
            $(".sectum").prop('checked', $(this).prop('checked'));
        });

        // Buton aksiyonları
        $(".btn-toplu-sil").click(function(e) {
            var islem = $(this).data('islem');
            var onayMetni = islem == 'hepsini_sil' ? 'TÜM galeriyi silmek istediğinize emin misiniz? Bu işlem geri alınamaz!' : 'Seçili görselleri silmek istediğinize emin misiniz?';
            
            if (confirm(onayMetni)) {
                $("#toplu-islem-tipi").val(islem);
                $("#galeri-form").submit();
            }
        });
	} );
</script> 
<section class="main-content container">
	<div class="page-header">
		<h2>İçerikler</h2>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="resim-ekle.php" class="btn btn-success btn-icon"><i class="fa fa-download"></i>Resim Yükle</a>
					</div>
					<div class="pull-right mt-10" style="margin-right: 10px;">
						<a href="video-ekle.php" class="btn btn-success btn-icon"><i class="fa fa-download"></i>Video Yükle</a>
					</div>
                    <!-- Toplu Silme Butonları -->
                    <div class="pull-right mt-10" style="margin-right: 10px;">
                        <button type="button" class="btn btn-danger btn-icon btn-toplu-sil" data-islem="secilenleri_sil"><i class="fa fa-minus-circle"></i> Seçilenleri Sil</button>
                    </div>
                    <div class="pull-right mt-10" style="margin-right: 10px;">
                        <button type="button" class="btn btn-danger btn-icon btn-toplu-sil" data-islem="hepsini_sil"><i class="fa fa-trash"></i> TÜMÜNÜ SİL</button>
                    </div>

					İçerikler Galerisi — sıra: kartın sağ üstündeki çubuk simgesinden sürükleyin (mobil kaydırma için)
				</div>
                
				<div class="card-block" style="background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 15px;">
                    <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="hepsini-sec" style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="hepsini-sec" style="cursor: pointer; font-weight: 700; margin: 0;">Tümünü Seç / Seçimi Kaldır</label>
                    </div>
					<div class="alert alert-info" style="margin-bottom: 0;">
						<h5><i class="fa fa-info-circle"></i> Görsel ve Video Yükleme Özellikleri</h5>
						<ul style="margin-bottom: 0; padding-left: 20px;">
							<li><strong>Desteklenen Formatlar:</strong> JPG, JPEG, PNG, GIF, WebP, MP4, WebM, MOV</li>
							<li><strong>Maksimum Dosya Boyutu:</strong> 100 MB</li>
							<li><strong>Görsel Kalitesi:</strong> 
								<ul>
									<li>JPEG: %100 kalite (sıkıştırma yok)</li>
									<li>PNG: Sıkıştırma yok (en yüksek kalite)</li>
									<li>WebP: %100 kalite</li>
									<li>GIF: Orijinal kalitede korunur</li>
								</ul>
							</li>
							<li><strong>Video Kalitesi:</strong> Orijinal kalitede korunur (MP4, WebM, MOV)</li>
							<li><strong>Sıralama:</strong> Sürükle-bırak ile sıralama yapabilirsiniz. Sıra numarası her görselin altında görüntülenir.</li>
							<li><strong>Not:</strong> Yüklenen görseller ve videolar index.php sayfasında galeri olarak gösterilir.</li>
						</ul>
					</div>
				</div>
				<div class="card-block">
                    <form id="galeri-form" method="POST" action="">
                        <input type="hidden" name="topluislem" value="1">
                        <input type="hidden" id="toplu-islem-tipi" name="islem" value="">
                        <div class="sortable row">
						<?php while ($picprint=$picsor->fetch(PDO::FETCH_ASSOC)) { ?>		
							<div class="col-md-3 col-sm-6 mb-20 gallery-sort-col" id="rank-<?php echo $picprint['resim_id']; ?>" style="margin-bottom: 25px; position: relative;">
								<button type="button" class="gallery-sort-handle" title="Sıra için sürükleyin" aria-label="Sıra değiştir">&#9776;</button>
								<div class="gallery-item-wrap" style="position: relative; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: all 0.3s ease; border: 1px solid #eee;">
                                    <!-- Checkbox overlay -->
                                    <div style="position: absolute; top: 10px; left: 10px; z-index: 20;">
                                        <input type="checkbox" name="id[]" value="<?php echo $picprint['resim_id']; ?>" class="sectum" style="width: 20px; height: 20px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                                    </div>
                                    <?php if ($picprint['video']==1) { ?>
                                        <div style="position: relative; padding-top: 56.25%;">
                                            <video style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" muted>
                                               <source src="<?php echo $picprint['resim_link']; ?>">
                                            </video>
                                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #fff; font-size: 30px; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
                                                <i class="fa fa-play-circle-o"></i>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div style="position: relative; padding-top: 60%;">
                                            <img src="<?php echo $picprint['resim_link']; ?>" loading="lazy" decoding="async" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    <?php } ?>
									<div class="gallery-actions" style="padding: 10px; display: flex; gap: 5px; justify-content: center; background: #fafafa;">
										<a href="controller/function.php?resimsil=ok&resim_id=<?php echo $picprint['resim_id']; ?>&eski_yol=<?php echo $picprint['resim_link']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')" title="Sil"><i class="fa fa-trash"></i></a>
                                        <span class="badge badge-primary" style="padding: 5px 10px; font-size: 12px; background: #667eea; color: #fff; border-radius: 15px;">#<?php echo $picprint['sira']; ?></span>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
                    </form>
				</div>
                <style>
                .gallery-sort-col {
                    padding-top: 4px;
                }
                .gallery-sort-handle {
                    position: absolute;
                    top: 0;
                    right: 8px;
                    z-index: 100;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 40px;
                    height: 40px;
                    margin: 0;
                    padding: 0;
                    border: 2px solid #667eea;
                    border-radius: 10px;
                    background: #fff;
                    box-shadow: 0 3px 12px rgba(102, 126, 234, 0.35);
                    color: #4338ca;
                    font-size: 22px;
                    font-weight: 700;
                    line-height: 1;
                    cursor: grab;
                    touch-action: none;
                    -ms-touch-action: none;
                }
                .gallery-sort-handle:active {
                    cursor: grabbing;
                    background: #eef2ff;
                }
                .gallery-sort-placeholder {
                    border: 2px dashed #667eea !important;
                    background: rgba(102, 126, 234, 0.08) !important;
                    min-height: 120px;
                    border-radius: 12px;
                    visibility: visible !important;
                }
                .gallery-sort-col.is-sorting .gallery-item-wrap {
                    transition: none !important;
                    transform: none !important;
                }
                .gallery-sort-col.is-sorting img,
                .gallery-sort-col.is-sorting video {
                    pointer-events: none;
                }
                .gallery-item-wrap:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
                    border-color: var(--renk2);
                }
                </style>
			</div>
		</div>
	</div> 

	<!--modal text end-->

	<?php include 'footer.php'; ?>
