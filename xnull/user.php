<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>	
<section class="main-content container">

	<div class="page-header">
		<h2>Profil İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-md-3 col-sm-12">
			<div class="widget padding-0 white-bg">
				<div class="bg-primary" style="height: 120px"></div>
				<div class="thumb-over">
					<img src="<?php echo $userprint['kullanici_resim']; ?>" alt="" width="100" class="rounded-circle">
				</div>
				<div class="padding-20 text-center">
					<p class="lead font-500 margin-b-0"><?php echo $userprint['kullanici_adsoyad'] ?></p>
					<p class="text-muted">Administrator</p>
					<p class="text-sm margin-b-0">Bu sayfa full site ayarları yapılandırma için kullanılan giriş bilgilerini içerir, güvenliğiniz için kimse ile paylaşmayınız</p>
					<hr>
				</div>
			</div>
		</div>
		<div class="col-md-9 col-sm-12">
			<div class="tabs">
				<ul class="nav nav-tabs">
					<li class="nav-item" role="presentation"><a class="nav-link  active" href="#home" aria-controls="home" role="tab" data-toggle="tab">Kullanıcı</a></li>
					<li class="nav-item" role="presentation"><a class="nav-link" href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Şifre</a></li>
					<li class="nav-item" role="presentation"><a class="nav-link" href="#picture" aria-controls="picture" role="tab" data-toggle="tab">Resim</a></li>
				</ul>
				<div class="tab-content">
					<div role="tabpanel" class="tab-pane active" id="home">
						<div class="widget white-bg">
							<!-- FORM BAŞLA -->
							<div class="card-heading card-default">
								KULLANICI BİLGİLERİ
							</div>
							<div class="card-block">
								<form id="signupForm1" method="post" class="form-horizontal" action="controller/function.php">

									<div class="form-group">
										<label for="firstname1">Ad Soyad</label>
										<input type="text" class="form-control" id="firstname1" name="kullanici_adsoyad" value="<?php echo $userprint['kullanici_adsoyad'] ?>" />
									</div>

									<div class="form-group">
										<label for="lastname1">Kullanıcı Adı</label>
										<input type="text" class="form-control" id="lastname1" name="kullanici_adi" value="<?php echo $userprint['kullanici_adi'] ?>" />
									</div>


									<div class="form-group">
										<div class="col-sm-9 col-sm-offset-4">
											<button type="submit" class="btn btn-primary" name="kullaniciduzenle" value="Sign up">Güncelle</button>
										</div>
									</div>
								</form>
							</div>
							<!-- FORM SON -->
						</div>
					</div>
					<div role="tabpanel" class="tab-pane" id="profile">
						<div class="widget white-bg">
							<!-- FORM BAŞLA -->
							<div class="card-heading card-default">
								ŞİFRE BİLGİLERİ
							</div>
							<div class="card-block">
								<form method="post" class="form-horizontal" action="controller/function.php">

									<div class="form-group">
										<label for="firstname1">ŞİFRE</label>
										<input type="password" class="form-control" name="kullanici_pass" placeholder="Yeni şifre" />
									</div>

									<div class="form-group">
										<label for="lastname1">ŞİFRE TEKRAR</label>
										<input type="password" class="form-control" name="kullanici_pass_tekrar" placeholder="Yeni şifre tekrar" />
									</div>


									<div class="form-group">
										<div class="col-sm-9 col-sm-offset-4">
											<button type="submit" class="btn btn-primary" name="kullanicisifre" value="Sign up">Güncelle</button>
										</div>
									</div>
								</form>
							</div>
							<!-- FORM SON -->
						</div>
					</div>
					<div role="tabpanel" class="tab-pane" id="picture">
						<div class="widget white-bg">
							<!-- FORM BAŞLA -->
							<div class="card-heading card-default">
								PROFİL RESMİ
							</div>
							<div class="card-block">
								<form method="post" class="form-horizontal" enctype="multipart/form-data" action="controller/function.php">

									<div class="form-group">
										<label>Resim Seç</label>
										<div class="fileinput-new" data-provides="fileinput">
											<div class="fileinput-preview" data-trigger="fileinput" style="width: 163px; height:163px;"><img style="width: 150px; height:150px;" src="<?php echo $userprint['kullanici_resim']; ?>"></div>
											<span class="btn btn-primary  btn-file">
												<span class="fileinput-new">Yeni Seç</span>
												<span class="fileinput-exists">Değiştir</span>
												<input type="file" id="image" name="kullanici_resim">
											</span>
											<a href="#" class="btn btn-danger fileinput-exists" data-dismiss="fileinput">Sil</a>
										</div>
									</div>

									<div class="form-group">
										<input type="hidden" class="form-control" name="eski_yol" value="<?php echo $userprint['kullanici_resim']; ?>" />
									</div>

									<div class="form-group">
										<div class="col-sm-9 col-sm-offset-4">
											<button type="submit" class="btn btn-primary" name="profilresimduzenle" >Güncelle</button>
										</div>
									</div>
								</form>
							</div>
							<!-- FORM SON -->
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
	<?php include 'footer.php'; ?>
