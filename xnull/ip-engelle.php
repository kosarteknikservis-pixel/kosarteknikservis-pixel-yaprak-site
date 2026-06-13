<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$urunsor=$db->prepare("SELECT * from ip order by id ASC");
$urunsor->execute();
?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->
<section class="main-content container">
	<div class="page-header">
		<h2>IP İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="ip-ekle.php" class="btn btn-primary btn-icon"><i class="fa fa-plus"></i>Yeni İP Engelle</a>
					</div>
					Engellenen ip adresleri
				</div>
				<div class="card-block">
					<table id="datatable1" class="table table-striped dt-responsive nowrap table-hover">
						<thead>
							<tr>
								<th class="text-left">
									<strong>IP</strong>
								</th>
								<th class="text-center">
									<strong>İşlemler</strong>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							while ($uruncek=$urunsor->fetch(PDO::FETCH_ASSOC)) {
								?>
								<tr>
									<td><?php echo $uruncek['ip']; ?></td>
									<td class="text-center">
										<a href="ip-duzenle.php?id=<?php echo $uruncek['id']; ?>" title="Düzenle" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>
										<a href="controller/function.php?ipsil=ok&id=<?php echo $uruncek['id']; ?>" title="Sil" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>
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
