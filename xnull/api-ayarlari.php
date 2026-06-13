<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$motor=$db->prepare("SELECT * from motor");
$motor->execute();
$motorprint=$motor->fetch(PDO::FETCH_ASSOC);
?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->
<section class="main-content container">
	<div class="page-header">
		<h2>Api Ayarları</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					Api Entegrasyonları
				</div>
				<div class="card-block">
					<!-- AYAR  -->
					<form method="POST" action="controller/function.php" class="form-horizontal">
						<input type="hidden" name="motor_id" value="1">
						<input type="hidden" name="motor_metrika" value="<?php echo htmlspecialchars(isset($motorprint['motor_metrika']) ? (string) $motorprint['motor_metrika'] : '1', ENT_QUOTES, 'UTF-8'); ?>">
						<div class="form-group">
							<div class="row">
								<input type="hidden" name="widget_id" value="1"  class="form-control">
								<div class="col-md-12">
									<label><i class="fa fa-code"></i> <strong>&lt;head&gt; Kodu</strong> &mdash; Her sayfada yüklenir. <small class="text-muted">(Meta/TikTok/Google init kodları buraya)</small></label>
									<textarea style="height: 100px;" type="text" name="motor_gonay" class="form-control"><?php echo $motorprint['motor_gonay']; ?></textarea>
								</div>
							</div>
						</div>
						<div class="form-group">
							<div class="row">
								<input type="hidden" name="widget_id" value="1"  class="form-control">
								<div class="col-md-12">
									<label><i class="fa fa-code"></i> <strong>&lt;body&gt; ViewContent / Conversion Kodu</strong> &mdash; Ana sayfa + Sipariş onayında çalışır.<br>
									<small class="text-muted">Kullanılabilir değişkenler: <code>{tutar}</code> = ürün/sipariş fiyatı &nbsp;|&nbsp; <code>{currency}</code> = TRY &nbsp;|&nbsp; <code>{urun_adi}</code> = ürün adı &nbsp;|&nbsp; <code>{siparis_id}</code> = sipariş no (sadece onay sayfasında)</small></label>
									<textarea style="height: 120px;" type="text" name="motor_yonay" class="form-control"><?php echo $motorprint['motor_yonay']; ?></textarea>
								</div>
							</div>
						</div>
						<div class="form-group">
							<div class="row">
								<input type="hidden" name="widget_id" value="1"  class="form-control">
								<div class="col-md-12">
									<label><i class="fa fa-code"></i> <strong>Footer Analitik Kodu</strong> &mdash; Her sayfanın altında çalışır (ViewContent event için).<br>
									<small class="text-muted">Kullanılabilir değişkenler: <code>{tutar}</code> = ilk ürün fiyatı &nbsp;|&nbsp; <code>{currency}</code> = TRY</small></label>
									<textarea style="height: 120px;" type="text" name="motor_analitik" class="form-control"><?php echo $motorprint['motor_analitik']; ?></textarea>
								</div>
							</div>
						</div>


						<hr>
						<p class="text-muted" style="margin-bottom: 15px;">
							<strong>Sunucu tarafı dönüşüm (Events API):</strong> Aşağıdaki alanlar yalnızca <code>siparis-onay.php</code> üzerinde, sipariş başarılı sayfasında tetiklenir
							(Meta: <code>Purchase</code> + tarayıcıda <code>fbq('track','Purchase')</code> aynı <code>event_id</code> ile eşleşir; TikTok: <code>CompletePayment</code>).
							Ana sayfada ViewContent / InitiateCheckout için yukarıdaki pixel kodları (<code>motor_gonay</code> + otomatik scriptler) kullanılır.
							Meta ve TikTok arayüzünde bu olayların <strong>alışveriş / e-ticaret</strong> kategorisinde görünmesi beklenen davranıştır; yalnızca “Shopping” API’si kurulmuş gibi görünmesi hata değildir.
						</p>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label><i class="fa fa-facebook"></i> <strong>Meta Pixel / Dataset ID (Conversions API)</strong></label>
									<input type="text" name="motor_meta_pixel_id" value="<?php echo isset($motorprint['motor_meta_pixel_id']) ? htmlspecialchars($motorprint['motor_meta_pixel_id']) : ''; ?>" class="form-control" placeholder="Örn: 809273668137251 (Events Manager’daki Pixel ID)">
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label><i class="fa fa-key"></i> <strong>Meta sistem kullanıcısı erişim jetonu (CAPI)</strong></label>
									<input type="text" name="motor_meta_token" value="<?php echo isset($motorprint['motor_meta_token']) ? htmlspecialchars($motorprint['motor_meta_token']) : ''; ?>" class="form-control" placeholder="EAAb...">
								</div>
							</div>
						</div>

						<hr>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label><i class="fa fa-tiktok"></i> <strong>TikTok Pixel kodu (Events API)</strong></label>
									<input type="text" name="motor_tiktok_pixel_id" value="<?php echo isset($motorprint['motor_tiktok_pixel_id']) ? htmlspecialchars($motorprint['motor_tiktok_pixel_id']) : ''; ?>" class="form-control" placeholder="Örn: C1234567890ABCDEF (Events Manager)">
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label><i class="fa fa-key"></i> <strong>TikTok Events API erişim jetonu</strong></label>
									<input type="text" name="motor_tiktok_token" value="<?php echo isset($motorprint['motor_tiktok_token']) ? htmlspecialchars($motorprint['motor_tiktok_token']) : ''; ?>" class="form-control" placeholder="Business Center → Events API token">
								</div>
							</div>
						</div>

						<button style="cursor: pointer;" type="submit" name="motorduzenle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Güncelle</button>
					</form>
					<!--#AYAR  -->
				</div>
			</div>
		</div>
	</div>
	<?php include 'footer.php'; ?>
