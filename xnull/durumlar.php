<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$urunsor=$db->prepare("SELECT * from durum order by siralama ASC, id ASC");
$urunsor->execute();
?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->
<section class="main-content container">
	<div class="page-header">
		<h2>Durum İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="durum-ekle.php" class="btn btn-primary btn-icon"><i class="fa fa-plus"></i>Durum Ekle</a>
					</div>
					Durumlar
				</div>
				<div class="card-block">
					<!-- Toplam Durum İstatistikleri -->
					<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 20px; color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 20px;">
						<div style="text-align: center;">
							<?php 
							$toplamDurum = $db->prepare("SELECT COUNT(*) as toplam FROM durum");
							$toplamDurum->execute();
							$toplamDurumSayi = $toplamDurum->fetch(PDO::FETCH_ASSOC)['toplam'];
							?>
							<div style="font-size: 36px; font-weight: 800; margin-bottom: 5px;"><?php echo $toplamDurumSayi; ?></div>
							<div style="font-size: 14px; opacity: 0.9;">Toplam Durum</div>
						</div>
					</div>
					
					<table id="datatable1" class="table table-striped dt-responsive nowrap table-hover mobile-table">
						<thead>
							<tr>
								<th class="text-left">
									<strong>Sıra</strong>
								</th>
								<th class="text-left">
									<strong>Ad</strong>
								</th>
								<th class="text-center">
									<strong>Sipariş Sayısı</strong>
								</th>
								<th class="text-center">
									<strong>İşlemler</strong>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							// Sorguyu tekrar çalıştır (fetch ile tüketildi)
							$urunsor2=$db->prepare("SELECT * from durum order by siralama ASC, id ASC");
							$urunsor2->execute();
							while ($uruncek=$urunsor2->fetch(PDO::FETCH_ASSOC)) {
								// Her durum için sipariş sayısı
								$durumSiparisSayi = $db->prepare("SELECT COUNT(*) as toplam FROM siparis WHERE siparis_durum=:durum");
								$durumSiparisSayi->execute(array('durum' => $uruncek['id']));
								$durumSiparisSayiSonuc = $durumSiparisSayi->fetch(PDO::FETCH_ASSOC)['toplam'];
								?>
								<tr>
									<td><?php echo $uruncek['siralama']; ?></td>
									<td><?php echo $uruncek['ad']; ?></td>
									<td class="text-center">
										<span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 5px 15px; border-radius: 20px; font-weight: 700; font-size: 13px;"><?php echo $durumSiparisSayiSonuc; ?></span>
									</td>
									<td class="text-center">
										<a href="durum-duzenle.php?durum_id=<?php echo $uruncek['id']; ?>" title="Düzenle" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>
										<?php 
										$sahte_durum = ($uruncek['id'] == 18);
										if (!$sahte_durum) { ?>
											<a href="controller/function.php?durumsil=ok&durum_id=<?php echo $uruncek['id']; ?>" title="Sil" class="btn btn-sm btn-danger" onclick="return confirm('Bu durumu silmek istediğinizden emin misiniz?');"><i class="fa fa-trash"></i></a>
										<?php } else { ?>
											<span class="btn btn-sm btn-danger disabled" title="Sahte durumu silinemez" style="opacity: 0.5; cursor: not-allowed;"><i class="fa fa-trash"></i></span>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<?php include 'footer.php'; ?>
