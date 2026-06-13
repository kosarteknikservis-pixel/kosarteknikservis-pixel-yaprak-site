<?php 
$link = $_SERVER['REQUEST_URI'];
// GET parametrelerini temizle (sadece dosya adını kontrol et)
$link_path = parse_url($link, PHP_URL_PATH);
$current_page = basename($link_path);

$siparis_menu_pages = [
    'siparisler.php', 'siparis-detay.php', 'yarim-kalanlar.php', 'siparis-ekle.php', 'siparis-arsivi.php', 'aras_kargo.php',
    'durumlar.php', 'durum-ekle.php', 'durum-duzenle.php',
    'hesaplarim.php', 'hesap-ekle.php', 'hesap-duzenle.php',
    'odeme-yontemleri.php', 'iade-nedenleri.php', 'kargolar.php',
    'il-ilce-yonetimi.php',
];
$siparis_menu_active = in_array($current_page, $siparis_menu_pages, true);

$carkifelek_menu_pages = ['carkifelek-ayarlari.php', 'carkifelek-log.php'];
$carkifelek_menu_active = in_array($current_page, $carkifelek_menu_pages, true);
?>
<div class="main-sidebar-nav default-navigation">
	<div class="nano">
		<div class="nano-content sidebar-nav">
			<ul class="metisMenu nav flex-column" id="menu">
				<div class="card-block border-bottom text-center nav-profile">
					<img alt="profile" class="rounded-circle margin-b-10 circle-border " src="<?php echo $userprint['kullanici_resim']; ?>" width="80">
					<p class="lead margin-b-0 toggle-none"><small>sayın<br> </small><?php echo $userprint['kullanici_adsoyad']; ?></p>
					<p class="text-muted mv-0 toggle-none">Hoşgeldin</p>
					<div class="btn-group mt-15" role="group" style="margin-top: 15px;">
						<a href="user.php" class="btn btn-xs btn-primary btn-outline" style="padding: 4px 10px; font-size: 11px; border-radius: 4px 0 0 4px;"><i class="fa fa-user"></i> Profilim</a>
						<a href="logout.php" class="btn btn-xs btn-danger btn-outline" style="padding: 4px 10px; font-size: 11px; border-radius: 0 4px 4px 0;"><i class="fa fa-sign-out"></i> Çıkış Yap</a>
					</div>
				</div>	
				<li class="nav-heading">
					<b>Menü</b>			
				</li>
				<li class="nav-item <?php if ($current_page == "index.php") { echo "active";} ?>"><a class="nav-link" href="index.php"><i class="icon-home"></i> <span class="toggle-none">Panel Anasayfa </span> </a></li>	

				<li class="nav-item <?php if ($current_page == "notlar.php") { echo "active";} ?>"><a class="nav-link" href="notlar.php"><i class="fa fa-pencil-square-o"></i> <span class="toggle-none">Hızlı Notlar </span> </a></li>	

				<li class="nav-item"><a class="nav-link" target="_blank" href="<?php echo $settingsprint['ayar_siteurl']; ?>"><i class="icon-link"></i> <span class="toggle-none">Siteye Git </span> </a></li>	

				<li class="nav-item <?php if ($current_page == "urunler.php" || $current_page == "urun-ekle.php" || $current_page == "urun-duzenle.php") { echo "active";} ?>"><a class="nav-link" href="urunler.php"><i class="icon-basket-loaded"></i> <span class="toggle-none">Ürün Yönetimi</span></a></li>


				<li class="nav-item <?php if (strstr($link, "sayfa")) { echo "active";} ?>"><a class="nav-link" href="sayfalar.php"><i class="fa fa-file-text-o"></i> <span class="toggle-none">Sayfa Yönetimi</span></a></li>


				<li class="nav-item <?php echo $siparis_menu_active ? 'active' : ''; ?>">
					<a class="nav-link" href="javascript: void(0);" aria-expanded="<?php echo $siparis_menu_active ? 'true' : 'false'; ?>"><i class="fa fa-credit-card-alt"></i> <span class="toggle-none">Sipariş Yönetimi<span style="float: right;" class="fa fa-angle-down"></span></span></a>
					<ul class="nav-second-level nav flex-column sub-menu<?php echo $siparis_menu_active ? ' in' : ''; ?>" aria-expanded="<?php echo $siparis_menu_active ? 'true' : 'false'; ?>">
						<li class="nav-item <?php echo ($current_page === 'siparisler.php' || $current_page === 'siparis-detay.php') ? 'active' : ''; ?>"><a class="nav-link" href="siparisler.php">Sipariş Yönetimi</a></li>
						<li class="nav-item <?php echo $current_page === 'yarim-kalanlar.php' ? 'active' : ''; ?>"><a class="nav-link" href="yarim-kalanlar.php">Yarım Kalan Siparişler</a></li>
						<li class="nav-item <?php echo $current_page === 'siparis-ekle.php' ? 'active' : ''; ?>"><a class="nav-link" href="siparis-ekle.php">Manuel Sipariş Ekle</a></li>
						<li class="nav-item <?php echo $current_page === 'siparis-arsivi.php' ? 'active' : ''; ?>"><a class="nav-link" href="siparis-arsivi.php">Sipariş Arşivi</a></li>
						<li class="nav-item <?php echo $current_page === 'aras_kargo.php' ? 'active' : ''; ?>"><a class="nav-link" href="aras_kargo.php">Aras Kargo Excel</a></li>
						<li class="nav-item <?php echo ($current_page === 'durumlar.php' || $current_page === 'durum-ekle.php' || $current_page === 'durum-duzenle.php') ? 'active' : ''; ?>"><a class="nav-link" href="durumlar.php">Durum Yönetimi</a></li>
						<li class="nav-item <?php echo ($current_page === 'hesaplarim.php' || $current_page === 'hesap-ekle.php' || $current_page === 'hesap-duzenle.php') ? 'active' : ''; ?>"><a class="nav-link" href="hesaplarim.php">Hesap Bilgileri</a></li>
						<li class="nav-item <?php echo $current_page === 'odeme-yontemleri.php' ? 'active' : ''; ?>"><a class="nav-link" href="odeme-yontemleri.php">Ödeme Yöntemleri</a></li>
						<li class="nav-item <?php echo $current_page === 'il-ilce-yonetimi.php' ? 'active' : ''; ?>"><a class="nav-link" href="il-ilce-yonetimi.php">İl / İlçe (Manuel)</a></li>
					</ul>
				</li>
				<li class="nav-item <?php if (strstr($link, "sozlesme")) { echo "active";} ?>"><a class="nav-link" href="sozlesme.php"><i class="fa fa-file-text-o"></i> <span class="toggle-none">M Sözleşme</span></a></li>
				<li class="nav-item <?php if (strstr($link, "gizlilik")) { echo "active";} ?>"><a class="nav-link" href="gizlilik.php"><i class="fa fa-file-text-o"></i> <span class="toggle-none">Gizlilik Politikası</span></a></li>
				<li class="nav-item <?php echo ($current_page === 'teslimat-kosullari-yonet.php') ? 'active' : ''; ?>"><a class="nav-link" href="teslimat-kosullari-yonet.php"><i class="fa fa-truck"></i> <span class="toggle-none">Teslimat Koşulları</span></a></li>
				<li class="nav-item <?php echo ($current_page === 'satis-politikasi-yonet.php') ? 'active' : ''; ?>"><a class="nav-link" href="satis-politikasi-yonet.php"><i class="fa fa-shopping-cart"></i> <span class="toggle-none">Satış Politikası</span></a></li>
				<li class="nav-item <?php echo ($current_page === 'iptal-iade-yonet.php') ? 'active' : ''; ?>"><a class="nav-link" href="iptal-iade-yonet.php"><i class="fa fa-undo"></i> <span class="toggle-none">İptal ve İade</span></a></li>
				<li class="nav-item <?php echo ($current_page === 'yasal-icerikler.php') ? 'active' : ''; ?>"><a class="nav-link" href="yasal-icerikler.php"><i class="fa fa-balance-scale"></i> <span class="toggle-none">Yasal Sayfalar (PayTR)</span></a></li>
				<li class="nav-item <?php if (strstr($link, "ip-")) { echo "active";} ?>"><a class="nav-link" href="ip-engelle.php"><i class="fa fa-shield"></i> <span class="toggle-none">İp Engelleme</span></a></li>
				<li class="nav-item <?php if (strstr($link, "cloaker.php") && strpos($link, 'cloaker-traffic') === false) { echo "active";} ?>"><a class="nav-link" href="cloaker.php"><i class="fa fa-user-secret"></i> <span class="toggle-none">Cloaker (Bot Koruması)</span></a></li>
				<li class="nav-item <?php if ($current_page === 'cloaker-traffic.php') { echo 'active';} ?>"><a class="nav-link" href="cloaker-traffic.php"><i class="fa fa-list-alt"></i> <span class="toggle-none">Cloaker trafik günlüğü</span></a></li>
				<li class="nav-item <?php if (strstr($link, "tel-")) { echo "active";} ?>"><a class="nav-link" href="tel-engelle.php"><i class="fa fa-phone"></i> <span class="toggle-none">Telefon Engelleme</span></a></li>
				<li class="nav-item <?php echo $carkifelek_menu_active ? 'active' : ''; ?>"><a class="nav-link" href="javascript: void(0);" aria-expanded="<?php echo $carkifelek_menu_active ? 'true' : 'false'; ?>"><i class="fa fa-gift"></i> <span class="toggle-none">Çarkıfelek<span style="float: right;" class="fa fa-angle-down"></span></span></a>
					<ul class="nav-second-level nav flex-column sub-menu<?php echo $carkifelek_menu_active ? ' in' : ''; ?>" aria-expanded="<?php echo $carkifelek_menu_active ? 'true' : 'false'; ?>">
						<li class="nav-item <?php echo $current_page === 'carkifelek-ayarlari.php' ? 'active' : ''; ?>"><a class="nav-link" href="carkifelek-ayarlari.php">Çarkıfelek Ayarları</a></li>
						<li class="nav-item <?php echo $current_page === 'carkifelek-log.php' ? 'active' : ''; ?>"><a class="nav-link" href="carkifelek-log.php">Çarkıfelek Logları</a></li>
					</ul>
				</li>
				<li class="nav-item <?php if (strstr($link, "kolay")) { echo "active";} ?>"><a class="nav-link" href="kolay-iletisim.php"><i class="fa fa-whatsapp"></i> <span class="toggle-none">Kolay Sipariş Butonları</span></a></li>
				<li class="nav-item <?php if (strstr($link, "gorseller") || strstr($link, "resim-ekle") || strstr($link, "video-ekle")) { echo "active";} ?>"><a class="nav-link" href="gorseller.php"><i class="fa fa-picture-o"></i> <span class="toggle-none">Görsel Ayarları</span></a></li>
				<li class="nav-item <?php if (strstr($link, "yorum")) { echo "active";} ?>"><a class="nav-link" href="yorumlar.php"><i class="fa fa-comment-o"></i> <span class="toggle-none">Yorum Yönetimi</span></a></li>
				<li class="nav-item <?php if (strstr($link, "telegram")) { echo "active";} ?>"><a class="nav-link" href="telegram.php"><i class="fa fa-paper-plane"></i> <span class="toggle-none">Telegram Ayarları</span></a></li>
				<li class="nav-item <?php if (strstr($link, "temalar")) { echo "active";} ?>"><a class="nav-link" href="temalar.php"><i class="fa fa-paint-brush"></i> <span class="toggle-none">Tema / Şablon Yönetimi</span></a></li>
				<li class="nav-item <?php if (strstr($link, "api")) { echo "active";} ?>"><a class="nav-link" href="api-ayarlari.php"><i class="fa fa-code"></i> <span class="toggle-none">Api Yönetimi</span></a></li>

                <li class="nav-item <?php if ($current_page == "sahte-bildirimler.php" || $current_page == "sahte-bildirim-ekle.php") { echo "active";} ?>"><a class="nav-link" href="sahte-bildirimler.php"><i class="fa fa-bell"></i> <span class="toggle-none">Sahte Bildirimler</span></a></li>
                <li class="nav-item <?php if (strstr($link, "form-")) { echo "active";} ?>"><a class="nav-link" href="form-yonetimi.php"><i class="fa fa-list-alt"></i> <span class="toggle-none">Form Yönetimi</span></a></li>
				<li class="nav-item <?php if (strstr($link, "genel-ayar")) { echo "active";} ?>"><a class="nav-link" href="genel-ayarlar.php"><i class="icon-wrench"></i> <span class="toggle-none">Site Ayarları</span></a></li>
				<li class="nav-item <?php if ($current_page == "admin-loglar.php") { echo "active";} ?>"><a class="nav-link" href="admin-loglar.php"><i class="fa fa-history"></i> <span class="toggle-none">Yönetici Logları</span></a></li>
			</ul>
		</div>
	</div>
</div>
