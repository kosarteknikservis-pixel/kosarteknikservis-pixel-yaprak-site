<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$hesapedit=$db->prepare("SELECT * from hesap where hesap_id=:hesap_id");
$hesapedit->execute(array(
	'hesap_id' => $_GET['hesap_id']
));
$hesapwrite=$hesapedit->fetch(PDO::FETCH_ASSOC);

?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->
<section class="main-content container">
	<div class="page-header">
		<h2>Hesap İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="hesaplarim.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
					</div>
					Hesap Düzenle
				</div>
				<div class="card-block">

					<form method="POST" action="controller/function.php" class="form-horizontal">
						<div class="form-group">
							<input type="hidden" name="hesap_id" value="<?php echo $hesapwrite['hesap_id']; ?>">
						</div>
						<div class="form-group">
							<label>Banka Adı</label>
							<input type="text" name="hesap_banka" value="<?php echo $hesapwrite['hesap_banka']; ?>" class="form-control">
						</div>
						<div class="form-group">
							<label>Hesap Adı</label>
							<input type="text" name="hesap_isim" value="<?php echo $hesapwrite['hesap_isim']; ?>" class="form-control">
						</div>
						<div class="form-group">
							<label>Şube Adı/Kodu</label>
							<input type="text" name="hesap_sube" value="<?php echo $hesapwrite['hesap_sube']; ?>" class="form-control">
						</div>
						<div class="form-group">
							<label>Hesap No</label>
							<input type="text" name="hesap_no" value="<?php echo $hesapwrite['hesap_no']; ?>" class="form-control">
						</div>
						<div class="form-group">
							<label>İban No</label>
							<input type="text" name="hesap_iban" value="<?php echo $hesapwrite['hesap_iban']; ?>" class="form-control">
						</div>
						<button style="cursor: pointer;" type="submit" name="hesapduzenle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Güncelle</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<?php include 'footer.php'; ?>
