<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$hesapsor=$db->prepare("SELECT * from yorumlar order by id DESC");
$hesapsor->execute();

// Toplu Silme İşlemi
if (isset($_POST['toplusil'])) {
    if (!$_SESSION['kullanici_adi']) {
        header("Location: index.php?status=no");
        exit();
    }
    
    $silineceks = $_POST['yorum_ids'];
    $basarili = 0;
    
    if (is_array($silineceks) && count($silineceks) > 0) {
        foreach ($silineceks as $id) {
            $SilID = htmlspecialchars(trim(strip_tags($id)));
            
            // Görseli bul ve sil
            $hesapedit=$db->prepare("SELECT * from yorumlar where id=:urun_id");
            $hesapedit->execute(array('urun_id' => $SilID));
            $yrmSon=$hesapedit->fetch(PDO::FETCH_ASSOC);
            
            if ($yrmSon && !empty($yrmSon['gorsel'])) {
                $resimsilunlink = dirname(__DIR__) . '/' . $yrmSon['gorsel'];
                if (file_exists($resimsilunlink)) {
                    @unlink($resimsilunlink);
                }
            }

            // Galeri görsellerini bul ve sil
            $galSor = $db->prepare("SELECT * FROM yorum_gorsel WHERE yorum=:id");
            $galSor->execute(['id' => $SilID]);
            while ($galCek = $galSor->fetch(PDO::FETCH_ASSOC)) {
                $galUnlink = dirname(__DIR__) . '/' . $galCek['gorsel'];
                if (!empty($galCek['gorsel']) && file_exists($galUnlink)) {
                    @unlink($galUnlink);
                }
            }
            $galSil = $db->prepare("DELETE FROM yorum_gorsel WHERE yorum=:id");
            $galSil->execute(['id' => $SilID]);
            
            $sil = $db->prepare("DELETE from yorumlar where id=:urun_id");
            $kontrol = $sil->execute(array('urun_id' => $SilID));
            
            if ($kontrol) {
                $basarili++;
            }
        }
    }
    
    if ($basarili > 0) {
        Header("Location:?status=ok");
        exit;
    } else {
        Header("Location:?status=no"); exit;
    }
    exit;
}

if ( isset($_GET[ 'yorumsil' ]) && $_GET[ 'yorumsil' ] == "ok" )
{
	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: index.php?status=no" );
		exit();
	}
	$SilID = htmlspecialchars(trim(strip_tags($_GET[ 'yorum_id' ])));

	$hesapedit=$db->prepare("SELECT * from yorumlar where id=:urun_id");
	$hesapedit->execute(array(
		'urun_id' => $SilID
	));
	$yrmSon=$hesapedit->fetch(PDO::FETCH_ASSOC);

	$sil     = $db->prepare( "DELETE from yorumlar where id=:urun_id" );
	$kontrol = $sil->execute(
		array(
			'urun_id' => $SilID
		)
	);

	if ( $kontrol )
	{
		if ($yrmSon && !empty($yrmSon['gorsel'])) {
			$resimsilunlink = dirname(__DIR__) . '/' . $yrmSon['gorsel'];
			if (file_exists($resimsilunlink)) {
				@unlink($resimsilunlink);
			}
		}

		// Galeri görsellerini temizle
		$galSor = $db->prepare("SELECT * FROM yorum_gorsel WHERE yorum=:id");
		$galSor->execute(['id' => $SilID]);
		while ($galCek = $galSor->fetch(PDO::FETCH_ASSOC)) {
			$galUnlink = dirname(__DIR__) . '/' . $galCek['gorsel'];
			if (!empty($galCek['gorsel']) && file_exists($galUnlink)) {
				@unlink($galUnlink);
			}
		}
		$galSil = $db->prepare("DELETE FROM yorum_gorsel WHERE yorum=:id");
		$galSil->execute(['id' => $SilID]);

		Header( "Location:?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:?status=no" ); exit;
	}
}
?>	
<section class="main-content container">
	<div class="page-header">
		<h2>Yorum İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
                        <button type="button" onclick="topluSil()" class="btn btn-danger btn-icon" style="margin-right: 5px;"><i class="fa fa-trash"></i> Seçilenleri Sil</button>
						<a href="yorum-ekle.php" class="btn btn-primary btn-icon"><i class="fa fa-plus"></i> Yorum Ekle</a>
					</div>
					Yorumlar
				</div>

				<div class="card-block">
                    <form id="bulkDeleteForm" method="POST" action="">
                        <input type="hidden" name="toplusil" value="1">
                        <table id="datatable1" class="mobile-table table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 40px;">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th class="text-left">
                                        <strong>Tarih</strong>
                                    </th>
                                    <th class="text-left">
                                        <strong>Tip / Sayfa</strong>
                                    </th>
                                    <th class="text-left">
                                        <strong>Ad</strong>
                                    </th>
                                    <th class="text-left">
                                        <strong>Durum</strong>
                                    </th>
                                    <th class="text-center">
                                        <strong>İşlemler</strong>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                while ($hesapcek=$hesapsor->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="yorum_ids[]" value="<?php echo $hesapcek['id']; ?>" class="selectItem">
                                        </td>
                                        <td><?php echo $hesapcek['tarih']; ?></td>
                                        <td>
                                            <strong><?php echo ucfirst($hesapcek['yorum_tip'] ?? 'Index'); ?></strong><br>
                                            <small class="text-muted">
                                                <?php 
                                                if ($hesapcek['yorum_tip'] == 'sayfa' && $hesapcek['sayfa_id'] > 0) {
                                                    $s_sor = $db->prepare("SELECT sayfa_baslik FROM sayfalar WHERE sayfa_id=:id");
                                                    $s_sor->execute(['id' => $hesapcek['sayfa_id']]);
                                                    $s_cek = $s_sor->fetch(PDO::FETCH_ASSOC);
                                                    echo ($s_cek ? $s_cek['sayfa_baslik'] : 'Silinmiş Sayfa');
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </small>
                                        </td>
                                        <td><?php echo $hesapcek['ad']; ?></td>
                                        <td>
                                            <?php if ($hesapcek['yorum_onay'] == 1) { ?>
                                                <span class="label label-success">Yayında</span>
                                            <?php } else { ?>
                                                <span class="label label-danger">Onay Bekliyor</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="yorum-duzenle.php?yorum_id=<?php echo $hesapcek['id']; ?>" title="Düzenle" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>
                                            <button type="button" onclick="tekSil(<?php echo $hesapcek['id']; ?>)" title="Sil" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </form>
				</div>
			</div>
		</div>
		<!-- İLETİŞİM MESAJLARI -->
	</div>

	<?php include 'footer.php'; ?>

    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
    // Tümünü Seçme Mantığı
    document.getElementById('selectAll').addEventListener('change', function(e) {
        var checkboxes = document.querySelectorAll('.selectItem');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = e.target.checked;
        });
    });

    // Toplu Silme Onayı
    function topluSil() {
        var selectedInfo = document.querySelectorAll('.selectItem:checked').length;
        if (selectedInfo === 0) {
            swal("Uyarı", "Lütfen silinecek yorumları seçiniz.", "warning");
            return;
        }

        swal({
            title: "Emin misiniz?",
            text: selectedInfo + " adet yorumu silmek üzeresiniz. Bu işlem geri alınamaz!",
            icon: "warning",
            buttons: ["İptal", "Evet, Sil"],
            dangerMode: true,
        })
        .then((willDelete) => {
            if (willDelete) {
                document.getElementById('bulkDeleteForm').submit();
            }
        });
    }

    // Tekli Silme Onayı
    function tekSil(id) {
        swal({
            title: "Emin misiniz?",
            text: "Bu yorumu silmek istediğinize emin misiniz?",
            icon: "warning",
            buttons: ["İptal", "Evet, Sil"],
            dangerMode: true,
        })
        .then((willDelete) => {
            if (willDelete) {
                window.location.href = "?yorumsil=ok&yorum_id=" + id;
            }
        });
    }

    // MOBİL TABLO DÜZENLEYİCİ
    $(document).ready(function() {
        // Tablo ID'sini otomatik algıla veya manuel belirt
        var tableId = '#datatable1';
        
        $(tableId + ' thead th').each(function(i) {
            var title = $(this).text().trim();
            // Checkbox sütunu için başlık yoksa atla
            if(i === 0) title = "Seç"; 
            
            $(tableId + ' tbody tr').each(function() {
                var $td = $(this).find('td').eq(i);
                if(!$td.attr('data-label')) {
                    $td.attr('data-label', title);
                }
            });
        });
        
        // DataTables draw event listener
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).on('draw.dt', function () {
               $(tableId + ' thead th').each(function(i) {
                    var title = $(this).text().trim();
                    if(i === 0) title = "Seç";
                    
                    $(tableId + ' tbody tr').each(function() {
                        var $td = $(this).find('td').eq(i);
                        if(!$td.attr('data-label')) {
                            $td.attr('data-label', title);
                        }
                    });
                }); 
            });
        }
    });
    </script>
    <style>
    /* MOBİL KART GÖRÜNÜMÜ CSS V3 (OVERFLOW FIX) */
    @media screen and (max-width: 768px) {
        /* ... existing styles ... */
        /* Checkbox ortalama */
        #datatable1 td:first-child {
            justify-content: space-between !important;
            text-align: right;
        }
    }
    </style>
