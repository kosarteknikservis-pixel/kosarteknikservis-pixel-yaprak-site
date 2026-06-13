<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$whatsapp=$db->prepare("SELECT * from whatsapp where whats_id=0");
$whatsapp->execute();
$whatsappprint = $whatsapp->fetch(PDO::FETCH_ASSOC);
$whatsappprint = is_array($whatsappprint) ? $whatsappprint : array();
$whatsappprint = array_merge(
	array(
		'whats_tel'             => '',
		'whats_durum'           => 0,
		'whats_tiklaara'        => '',
		'whats_tiklaaradurum'   => 0,
		'whats_iletisimdurum'   => 0,
	),
	$whatsappprint
);
?>		
<section class="main-content container">
	<div class="page-header">
		<h2>Kolay İletişim İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<?php if (isset($_GET['status']) && $_GET['status'] === 'ok') { ?>
			<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
				Ayarlar kaydedildi.
			</div>
			<?php } elseif (isset($_GET['status']) && $_GET['status'] === 'no') { ?>
			<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
				Kaydedilemedi. Oturumunuzu veya veritabanı kaydını kontrol edin (whatsapp tablosunda whats_id=0 satırı olmalı).
			</div>
			<?php } ?>
			<div class="card">
				<div class="card-heading card-default">
					Kolay İletişim Düzenle
				</div>
				<div class="card-block">
					<form method="POST" action="controller/function.php" class="form-horizontal">
						<div class="form-group">
							<div class="row">
								<input type="hidden" name="whats_id" value="0"  class="form-control">
								<div class="col-md-6">
									<label>WHATSAPP SİPARİŞ:</label> <small>(Başında 0 'Sıfır' olmadan giriniz.)</small>
									<input type="text" name="whats_tel" value="<?php echo $whatsappprint['whats_tel']; ?>" class="form-control">
								</div>	
								<div class="col-md-6">
									<label>Modül Durum</label>
									<select name="whats_durum" class="form-control m-b">
										<?php if ($whatsappprint['whats_durum']==1) { ?>
											<option value="1">Aktif</option>
											<option value="0">Pasif</option>
											<?php
										} else {?>
											<option value="0">Pasif</option>
											<option value="1">Aktif</option>
										<?php }?>
									</select>
								</div>	
								<div style="margin-top: 20px;" class="col-md-6">
									<label>TELEFONLA SİPARİŞ:</label> <small>(Başında 0 'Sıfır' olmadan giriniz.)</small>
									<input type="text" name="whats_tiklaara" value="<?php echo $whatsappprint['whats_tiklaara']; ?>" class="form-control">
								</div>	
								<div style="margin-top: 20px;" class="col-md-6">
									<label>Modül Durum</label>
									<select name="whats_tiklaaradurum" class="form-control m-b">
										<?php if ($whatsappprint['whats_tiklaaradurum']==1) { ?>
											<option value="1">Aktif</option>
											<option value="0">Pasif</option>
											<?php
										} else {?>
											<option value="0">Pasif</option>
											<option value="1">Aktif</option>
										<?php }?>
									</select>
								</div>		
								<input type="hidden" name="whats_iletisimdurum" value="<?php echo $whatsappprint['whats_iletisimdurum']; ?>" class="form-control">
							</div>
						</div>
						<button style="cursor: pointer;" type="submit" name="whatsappduzenle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Güncelle</button>
					</form>
					<!--#AYAR  -->
				</div>
			</div>
		</div>
	</div>
	<?php include 'footer.php'; ?>
