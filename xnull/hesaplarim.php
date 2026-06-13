<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$hesapsor=$db->prepare("SELECT * from hesap order by hesap_id DESC");
$hesapsor->execute();
?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->
<section class="main-content container">
	<div class="page-header">
		<h2>Hesap İşlemleri</h2>
	</div>
	<div class="row">
		<!-- İLETİŞİM MESAJLARI -->
		<div class="col-md-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="hesap-ekle.php" class="btn btn-primary btn-icon"><i class="fa fa-plus"></i> Hesap Ekle</a>
					</div>
					Hesaplar
				</div>

				<div class="card-block">
					<table id="datatable1" class="table table-striped dt-responsive nowrap table-hover mobile-table">
						<thead>
							<tr>
								<th class="text-left">
									<strong>Banka Adı</strong>
								</th>
								<th class="text-left">
									<strong>Hesap Adı</strong>
								</th>
								<th class="text-left">
									<strong>Hesap İban</strong>
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
									<td><?php echo $hesapcek['hesap_banka']; ?></td>
									<td><?php echo $hesapcek['hesap_isim']; ?></td>
									<td><?php echo $hesapcek['hesap_iban']; ?></td>
									<td class="text-center">
										<a href="hesap-duzenle.php?hesap_id=<?php echo $hesapcek['hesap_id']; ?>" title="Düzenle" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>
										<a href="controller/function.php?hesapsil=ok&hesap_id=<?php echo $hesapcek['hesap_id']; ?>" title="Sil" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>
									</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<!-- İLETİŞİM MESAJLARI -->
		</div>

		<?php include 'footer.php'; ?>
