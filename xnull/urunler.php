<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$urunsor=$db->prepare("SELECT * from urunler order by urun_siralama ASC, urun_id ASC");
$urunsor->execute();
?>		
<section class="main-content container">
	<div class="page-header">
		<h2>Ürün İşlemleri</h2>
	</div>
	<?php if (isset($_GET['toplu_sil']) && $_GET['toplu_sil'] === 'ok') { ?>
		<div class="alert alert-success alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Seçilen ürünler silindi.</div>
	<?php } elseif (isset($_GET['toplu_sil']) && $_GET['toplu_sil'] === 'bos') { ?>
		<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Silinecek satır seçilmedi.</div>
	<?php } ?>
	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="urun-ekle.php" class="btn btn-primary btn-icon"><i class="fa fa-plus"></i>Ürün Ekle</a>
					</div>
					Ürünler
				</div>
				<div class="card-block">
					<form method="post" action="controller/function.php" id="urun-toplu-form" onsubmit="return urunTopluSilOnay();">
						<p class="text-muted" style="margin-bottom:10px;"><small><strong>Çoğalt</strong> şu alanları kopyalar: başlık (+ &quot; + 1&quot;), alt başlık, etiket, SEO, fiyatlar, sıra, <strong>seçenekler (JSON)</strong>, görsel yolları. Görseller diske tekrar yazılmaz (aynı dosya). Slug yeniden üretilir.</small></p>
						<div class="form-group" style="margin-bottom:12px;">
							<button type="submit" name="urun_toplu_sil" value="1" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Seçilenleri sil</button>
							<label class="checkbox-inline" style="margin-left:12px;"><input type="checkbox" id="urun-tumunu-sec"> Tümünü seç</label>
						</div>
					<table id="datatable1" class="mobile-table table table-striped table-hover">
						<thead>
							<tr>
								<th class="text-center" style="width:42px;">
									<input type="checkbox" id="urun-tumunu-sec-head" title="Tümünü seç">
								</th>
								<th class="text-left">
									<strong>Ürün İsmi</strong>
								</th>
								<th class="text-center">
									<strong>Sıra</strong>
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
								<tr class="mobile-collapsed">
									<td data-label="Seç" class="text-center">
										<input type="checkbox" class="urun-sil-cb" name="urun_id[]" value="<?php echo (int)$uruncek['urun_id']; ?>">
									</td>
									<td data-label="Ürün İsmi"><?php echo htmlspecialchars(strip_tags($uruncek['urun_baslik'])); ?></td>
									<td data-label="Sıra" class="text-center"><?php echo (int)$uruncek['urun_siralama']; ?></td>
									<td data-label="İşlemler" class="text-center">

										<a href="urun-secenek-duzenle.php?urun_id=<?php echo $uruncek['urun_id']; ?>" title="Ürün Seçenek Düzenle" class="btn btn-sm btn-success"><i class="fa fa-list"></i></a>
										<a href="urun-duzenle.php?urun_id=<?php echo $uruncek['urun_id']; ?>" title="Düzenle" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>
                                        <a href="controller/function.php?uruncogalt=ok&urun_id=<?php echo $uruncek['urun_id']; ?>" title="Çoğalt" class="btn btn-sm btn-info" onclick="return confirm('Bu ürünü çoğaltmak istiyor musunuz?')"><i class="fa fa-copy"></i></a>
										<a href="controller/function.php?urunsil=ok&urun_id=<?php echo $uruncek['urun_id']; ?>&urun_resim=<?php echo urlencode($uruncek['urun_resim']); ?>" title="Sil" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fa fa-trash"></i></a>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
					</form>
					<script>
					function urunTopluSilOnay() {
						var n = document.querySelectorAll('#urun-toplu-form .urun-sil-cb:checked').length;
						if (n === 0) { alert('Lütfen silmek için en az bir ürün işaretleyin.'); return false; }
						return confirm(n + ' ürün kalıcı olarak silinecek. Emin misiniz?');
					}
					function urunTumunuSecToggle(checked) {
						document.querySelectorAll('#urun-toplu-form .urun-sil-cb').forEach(function(cb) { cb.checked = checked; });
						var h = document.getElementById('urun-tumunu-sec-head');
						var f = document.getElementById('urun-tumunu-sec');
						if (h) h.checked = checked;
						if (f) f.checked = checked;
					}
					document.getElementById('urun-tumunu-sec').addEventListener('change', function() { urunTumunuSecToggle(this.checked); });
					document.getElementById('urun-tumunu-sec-head').addEventListener('change', function() { urunTumunuSecToggle(this.checked); });
					</script>
				</div>
			</div>
		</div>
	</div>
	<?php include 'footer.php'; ?>

