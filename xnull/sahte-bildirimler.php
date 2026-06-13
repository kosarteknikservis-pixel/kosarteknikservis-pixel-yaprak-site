<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$sahtesor=$db->prepare("SELECT * from sahte_bildirimler order by sahte_id DESC");
$sahtesor->execute();
?>		
<section class="main-content container">
	<div class="page-header">
		<h2>Sahte Bildirim İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="sahte-bildirim-ekle.php" class="btn btn-primary btn-icon"><i class="fa fa-plus"></i>Yeni Ekle</a>
					</div>
					Sahte Bildirimler
				</div>
				<div class="card-block">
					<table id="datatable1" class="mobile-table table table-striped table-hover">
						<thead>
							<tr>
								<th class="text-left">
									<strong>Ad Soyad</strong>
								</th>
								<th class="text-center">
									<strong>Şehir</strong>
								</th>
                                <th class="text-center">
									<strong>Süre</strong>
								</th>
                                <th class="text-center">
									<strong>Durum</strong>
								</th>
								<th class="text-center">
									<strong>İşlemler</strong>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							while ($sahtecek=$sahtesor->fetch(PDO::FETCH_ASSOC)) {
								?>
								<tr class="mobile-collapsed">
									<td data-label="Ad Soyad"><?php echo $sahtecek['sahte_ad']; ?></td>
									<td data-label="Şehir" class="text-center"><?php echo $sahtecek['sahte_il']; ?></td>
                                    <td data-label="Süre" class="text-center"><?php echo $sahtecek['sahte_sure']; ?></td>
                                    <td data-label="Durum" class="text-center">
                                        <?php if ($sahtecek['sahte_durum']==1) { ?>
                                            <span class="label label-success">Aktif</span>
                                        <?php } else { ?>
                                            <span class="label label-danger">Pasif</span>
                                        <?php } ?>
                                    </td>
									<td data-label="İşlemler" class="text-center">
										<a href="sahte-bildirim-duzenle.php?sahte_id=<?php echo $sahtecek['sahte_id']; ?>" title="Düzenle" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
										<a href="controller/function.php?sahtebildirimsil=ok&sahte_id=<?php echo $sahtecek['sahte_id']; ?>" title="Sil" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fa fa-trash"></i></a>
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
