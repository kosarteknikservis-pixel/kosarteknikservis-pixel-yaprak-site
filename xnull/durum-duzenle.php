<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$urunedit=$db->prepare("SELECT * from durum where id=:urun_id");
$urunedit->execute(array(
	'urun_id' => $_GET['durum_id']
));
$urunwrite=$urunedit->fetch(PDO::FETCH_ASSOC);
?>		
<section class="main-content container">
	<div class="page-header">
		<h2>Durum İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="durumlar.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
					</div>
					Durum Düzenle
				</div>
				<div class="card-block">

					<form method="POST" action="controller/function.php" enctype="multipart/form-data" class="form-horizontal">
                        <input type="hidden" name="id" value="<?php echo $urunwrite['id'] ?>" class="form-control">
                        <div class="form-group">
                            <label>Ad</label>
                            <input type="text" name="ad" value="<?php echo $urunwrite['ad'] ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Sıra</label>
                            <input type="number" name="siralama" value="<?php echo $urunwrite['siralama'] ?>" class="form-control">
                        </div>
                        <button style="cursor: pointer;" type="submit" name="durumduzenle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
