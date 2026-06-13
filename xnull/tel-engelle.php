<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

// tel_engelle tablosu yoksa oluştur
try {
    $db->exec("CREATE TABLE IF NOT EXISTS tel_engelle (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        tel VARCHAR(20) NOT NULL,
        UNIQUE KEY tel (tel)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci");
} catch (Exception $e) {}

$telsor=$db->prepare("SELECT * from tel_engelle ORDER BY id DESC");
$telsor->execute();
$telsay=$telsor->rowCount();
?>  
<section class="main-content container">
	<div class="page-header">
		<h2>Telefon Engelleme</h2>
	</div>
	<div class="row">
		<div class="col-md-12 col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					TELEFON NUMARASI EKLE
				</div>
				<div class="card-block">
					<form method="POST" class="form-horizontal" action="controller/function.php">
						<div class="form-group">
							<label>Telefon Numarası</label>
							<input type="text" name="tel" class="form-control" placeholder="Örn: 5551234567" required>
							<small class="text-muted">Engellenecek telefon numarasını giriniz.</small>
						</div>
						<div class="form-group">
							<button type="submit" name="teleklex" class="btn btn-primary">Telefon Numarası Ekle</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12 col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					ENGELLENEN TELEFON NUMARALARI (<?php echo $telsay; ?>)
				</div>
				<div class="card-block">
					<div class="table-responsive">
						<table class="table table-striped table-bordered table-hover" id="datatable2">
							<thead>
								<tr>
									<th>ID</th>
									<th>Telefon Numarası</th>
									<th>İşlemler</th>
								</tr>
							</thead>
							<tbody>
								<?php 
								while($telprint=$telsor->fetch(PDO::FETCH_ASSOC)) {
								?>
								<tr>
									<td><?php echo $telprint['id']; ?></td>
									<td><?php echo htmlspecialchars($telprint['tel']); ?></td>
									<td>
										<a href="controller/function.php?telsilx=ok&id=<?php echo $telprint['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu telefon numarasını engellemeden kaldırmak istediğinize emin misiniz?');">Sil</a>
									</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php include 'footer.php'; ?>

