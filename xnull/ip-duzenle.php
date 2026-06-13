<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$urunedit=$db->prepare("SELECT * from ip where id=:urun_id");
$urunedit->execute(array(
	'urun_id' => $_GET['id']
));
$urunwrite=$urunedit->fetch(PDO::FETCH_ASSOC);
?>		
<section class="main-content container">
	<div class="page-header">
		<h2>IP İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="ip-engelle.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
					</div>
					IP Düzenle
				</div>
				<div class="card-block">

					<form method="POST" action="controller/function.php" enctype="multipart/form-data" class="form-horizontal">
                        <input type="hidden" name="id" value="<?php echo $urunwrite['id'] ?>" class="form-control">
                        <div class="form-group">
                            <label>IP</label>
                            <input type="text" name="ip" value="<?php echo $urunwrite['ip'] ?>" class="form-control">
                        </div>
                        <button style="cursor: pointer;" type="submit" name="ipduzenle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
