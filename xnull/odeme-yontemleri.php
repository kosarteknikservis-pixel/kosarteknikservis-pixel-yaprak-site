<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$havaleedit=$db->prepare("SELECT * from odeme order by odeme_id DESC");
$havaleedit->execute(array());

if ( isset( $_POST[ 'paytrduzenle' ] ) )
{
	$ayarkaydet = $db->prepare(
		"UPDATE paytr SET
		paytr_magaza=:magaza,
		paytr_key=:key,
		paytr_salt=:salt
		WHERE paytr_id=:id"
	);
	$update     = $ayarkaydet->execute(
		array(
			'magaza'     => $_POST[ 'paytr_magaza' ],
			'key'     => $_POST[ 'paytr_key' ],
			'salt'     => $_POST[ 'paytr_salt' ],
			'id'       => $_POST[ 'paytr_id' ]
		)
	);
	if ( $update )
	{
		Header( "Location:?status=ok" );
		exit;
	}
	else
	{
		Header( "Location:?status=no" );
		exit;

	}

}
?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->
<section class="main-content container">
	<div class="page-header">
		<h2>Ödeme İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					Ödeme Düzenle
				</div>
				<div class="card-block">
					<?php foreach ($havaleedit as $havalewrite) {  ?>
						<form method="POST" action="controller/function.php" class="form-horizontal">
							<div class="form-group">
								<div class="row">
									<input type="hidden" name="odeme_id" value="<?php echo $havalewrite['odeme_id']; ?>"  class="form-control">
									<div class="col-md-3">
										<label>Ödeme Adı</label>
										<input type="text" name="odeme_adi" value="<?php echo $havalewrite['odeme_adi']; ?>" class="form-control">
									</div>
									<div class="col-md-5">
										<label>Ödeme Notu</label>
										<input type="text" name="odeme_not" value="<?php echo $havalewrite['odeme_not']; ?>" class="form-control">
										<small class="text-muted">Not: Başınan " - " ekleyerek isimle ayırabilirsiniz.</small>
									</div>
									<div class="col-md-2">
										<label>Ödeme Durum</label>
										<select name="odeme_durum" class="form-control m-b">
											<?php if ($havalewrite['odeme_durum']==1) { ?>
												<option value="1">Aktif</option>
												<option value="0">Pasif</option>
												<?php
											} else {?>
												<option value="0">Pasif</option>
												<option value="1">Aktif</option>
											<?php }?>
										</select>
									</div>
									<div class="col-md-2">
										<label>*Kaydet</label><div>
											<button style="cursor: pointer;" type="submit" name="odemeduzenle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Kaydet</button></div>
										</div>
									</div>
								</div>
							</form>

						<?php } ?>
					</div>
					
					<div role="tabpanel" class="tab-pane" id="paytr">
						<div class="widget white-bg">
							<!-- FORM BAŞLA -->
							<div class="card-heading card-default">
								PAYTR AYARLARI <small>(Aşağıda bulunan bilgileri PAYTR hesabım sayfasından bulabilirsiniz.)</small>
							</div>
							<?php 
							$paytr=$db->prepare("SELECT * from paytr where paytr_id=?");
							$paytr->execute(array(1));
							$paytrprint=$paytr->fetch(PDO::FETCH_ASSOC);
							?>
							<div class="card-block">
								<form id="signupForm" method="post" class="form-horizontal" action="">
									<input type="hidden" name="paytr_id" value="<?php echo $paytrprint['paytr_id']; ?>" class="form-control form-control-rounded">
									<div class="form-group">
										<label>Bildirim URL (PayTR panelinde)</label>
										<p class="form-control-static" style="padding:8px 12px;background:#f9f9f9;border-radius:4px;font-family:monospace;font-size:12px;word-break:break-all;"><?php echo htmlspecialchars(rtrim($settingsprint['ayar_siteurl'], "/")."/pay_int.php", ENT_QUOTES, 'UTF-8'); ?></p>
										<small class="text-muted">PayTR Mağaza Paneli → Entegrasyon → Bildirim URL / Callback olarak bu adresi kaydedin. (Form ile gönderilmez; yalnızca bilgi.)</small>
									</div>

									<div class="form-group">
										<label>Mağaza numarası (merchant_id)</label>
										<input type="text" name="paytr_magaza" value="<?php echo htmlspecialchars($paytrprint['paytr_magaza'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-rounded" placeholder="PayTR Bilgi sayfasındaki mağaza no">
									</div>

									<div class="form-group">
										<label>Mağaza Parola (merchant_key)</label>
										<input name="paytr_key" type="text" value="<?php echo $paytrprint['paytr_key']; ?>" class="form-control form-control-rounded">
									</div>

									<div class="form-group">
										<label>Mağaza Gizli Anahtar (merchant_salt)</label>
										<input type="text" name="paytr_salt" value="<?php echo $paytrprint['paytr_salt']; ?>" class="form-control form-control-rounded">
									</div>
									<div class="form-group">
										<button type="submit" class="btn btn-primary" name="paytrduzenle" >Güncelle</button>
									</div>
								</form>
							</div>
							<!-- FORM SON -->
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php include 'footer.php'; ?>
