<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$mesajedit=$db->prepare("SELECT * from mesajlar where mesaj_id=:mesaj_id");
$mesajedit->execute(array(
	'mesaj_id' => $_GET['mesaj_id']
));
$mesajwrite=$mesajedit->fetch(PDO::FETCH_ASSOC);

?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->
<section class="main-content container">
	<div class="page-header">
		<h2>Mesaj Detayları</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					Gelen Mesaj
				</div>
				<div class="card-block">

					<form method="POST" action="controller/function.php" class="form-horizontal">
						<div class="form-group">
							<label>Ad Soyad</label>
							<input type="text" name="mesaj_ad" readonly="readonly" value="<?php echo $mesajwrite['mesaj_ad']; ?>" class="form-control">
						</div>
						<div class="form-group">
							<label>Mail</label>
							<input type="text" name="mesaj_mail" readonly="readonly" value="<?php echo $mesajwrite['mesaj_mail']; ?>" class="form-control">
						</div>
						<div class="form-group">
							<label>Mesaj</label>
							<textarea type="text" class="summernote" readonly="readonly" name="mesaj_icerik" class="form-control"><?php echo $mesajwrite['mesaj_icerik']; ?></textarea>
						</div>
							<a href="index.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
						</form>
					</div>
				</div>
			</div>
		</div>

		<?php include 'footer.php'; ?>
