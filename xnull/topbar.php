<!-- ============================================================== -->
<!-- 						Topbar Start 							-->
<!-- ============================================================== -->
<div class="top-bar light-top-bar">
	<div class="container-fluid">
		<div class="row">
			<div class="col">
				<a class="admin-logo" href="index.php" style="padding: 0 12px; width: auto; min-width: auto; margin-left: 0; display: flex; align-items: center; justify-content: center;">
					<i class="fa fa-bug" style="font-size: 24px; color: #667eea; text-shadow: 0 2px 4px rgba(102, 126, 234, 0.3); transition: all 0.3s ease;" onmouseover="this.style.transform='rotate(15deg) scale(1.1)'; this.style.color='#764ba2';" onmouseout="this.style.transform='rotate(0deg) scale(1)'; this.style.color='#667eea';"></i>
				</a>
				<div class="left-nav-toggle" >
					<a  href="#" class="nav-collapse"><i class="fa fa-bars"></i></a>
				</div>
				<div class="left-nav-collapsed" >
					<a  href="#" class="nav-collapsed"><i class="fa fa-bars"></i></a>
				</div>
			</div>
			<div class="col">
				<ul class="list-inline top-right-nav">
					<li class="dropdown icon-dropdown d-none-m" style="margin-right: 20px; margin-left: -5px;">
						<a href="genel-ayarlar.php" style="font-size: 18px; color: #667eea; transition: all 0.3s ease; display: inline-block; padding: 6px 12px; border-radius: 6px; background: rgba(102, 126, 234, 0.1); text-decoration: none;" onmouseover="this.style.color='#764ba2'; this.style.background='rgba(118, 75, 162, 0.15)'; this.style.transform='scale(1.05)'" onmouseout="this.style.color='#667eea'; this.style.background='rgba(102, 126, 234, 0.1)'; this.style.transform='scale(1)'"><i class="icon-settings"></i></a>
					</li>
					<li class="dropdown avtar-dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#">
							<span class="d-none-m" style="margin-right: 8px; font-weight: 600; font-size: 13px; color: #4a5568;"><?php echo $userprint['kullanici_adsoyad']; ?></span>
							<img alt="" class="rounded-circle" src="<?php echo $userprint['kullanici_resim']; ?>" width="30">
						</a>
						<ul class="dropdown-menu top-dropdown">
							<li>
								<a class="dropdown-item" href="user.php"><i class="icon-user"></i> Profil</a>
							</li>
							<li class="dropdown-divider"></li>
							<li>
								<a class="dropdown-item" href="logout.php"><i class="icon-logout"></i> Güvenli Çıkış</a>
							</li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<!-- ============================================================== -->
<!--                        Topbar End                              -->
<!-- ============================================================== -->

