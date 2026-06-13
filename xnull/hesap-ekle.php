<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
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
					Hesap Ekle
				</div>
				<div class="card-block">

					<form method="POST" action="controller/function.php" class="form-horizontal">
						<div class="form-group">
							<label>Banka Adı</label>
							<input type="text" name="hesap_banka" placeholder="Banka adı giriniz." class="form-control">
						</div>
						<div class="form-group">
							<label>Hesap Adı</label>
							<input type="text" name="hesap_isim" placeholder="Hesap adı giriniz." class="form-control">
						</div>
						<div class="form-group">
							<label>Şube Adı/Kodu</label>
							<input type="text" name="hesap_sube" placeholder="Şuba adı ve kodu giriniz." class="form-control">
						</div>
						<div class="form-group">
							<label>Hesap No</label>
							<input type="text" name="hesap_no" placeholder="Hesap no giriniz." class="form-control">
						</div>
						<div class="form-group">
							<label>İban No</label>
							<input type="text" name="hesap_iban" placeholder="İban numarası giriniz." class="form-control">
						</div>
						<button style="cursor: pointer;" type="submit" name="hesapekle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Kaydet</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<?php include 'footer.php'; ?>
