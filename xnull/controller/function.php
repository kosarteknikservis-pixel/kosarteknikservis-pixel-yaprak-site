<?php
ob_start();
session_start();
include 'config.php';

if (!function_exists('seolink')) {
    function seolink($s) {
        $tr = array('ş','Ş','ı','I','İ','ğ','Ğ','ü','Ü','ö','Ö','Ç','ç','(',')','/',' ',',','?');
        $eng = array('s','s','i','i','i','g','g','u','u','o','o','c','c','','','-','-','','');
        $s = str_replace($tr,$eng,$s);
        $s = strtolower($s);
        $s = preg_replace('/&amp;amp;amp;amp;amp;amp;amp;amp;amp;.+?;/', '', $s);
        $s = preg_replace('/\s+/', '-', $s);
        $s = preg_replace('|-+|', '-', $s);
        $s = preg_replace('/#/', '', $s);
        $s = str_replace('.', '', $s);
        $s = trim($s, '-');
        return $s;
    }
}

if (!function_exists('panel_mb_substr_safe')) {
    function panel_mb_substr_safe($str, $start, $length) {
        $str = (string) $str;
        if (function_exists('mb_substr')) {
            return mb_substr($str, (int) $start, (int) $length, 'UTF-8');
        }
        return substr($str, (int) $start, (int) $length);
    }
}

if (!function_exists('panel_ensure_urunler_columns')) {
    function panel_ensure_urunler_columns(PDO $db) {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $alters = array(
            "ALTER TABLE urunler ADD COLUMN urun_alt_baslik VARCHAR(255) NOT NULL DEFAULT ''",
            "ALTER TABLE urunler ADD COLUMN urun_etiket VARCHAR(128) NOT NULL DEFAULT ''",
            "ALTER TABLE urunler ADD COLUMN urun_etiket_bg VARCHAR(32) NOT NULL DEFAULT ''",
            "ALTER TABLE urunler ADD COLUMN urun_etiket_color VARCHAR(32) NOT NULL DEFAULT ''",
            "ALTER TABLE urunler ADD COLUMN urun_seo_baslik VARCHAR(255) NOT NULL DEFAULT ''",
            'ALTER TABLE urunler ADD COLUMN urun_seo_aciklama TEXT',
            "ALTER TABLE urunler ADD COLUMN urun_seo_anahtar VARCHAR(512) NOT NULL DEFAULT ''",
            "ALTER TABLE urunler ADD COLUMN urun_eski_fiyat VARCHAR(64) NOT NULL DEFAULT ''",
            "ALTER TABLE urunler ADD COLUMN urun_fiyat_birim_metin VARCHAR(64) NOT NULL DEFAULT ''",
            "ALTER TABLE urunler ADD COLUMN urun_fiyat_birim_renk VARCHAR(32) NOT NULL DEFAULT ''",
            'ALTER TABLE urunler ADD COLUMN urun_fiyat_birim_goster TINYINT(1) NOT NULL DEFAULT 0',
            'ALTER TABLE urunler ADD COLUMN urun_fiyat_birim_olcek DECIMAL(4,2) NOT NULL DEFAULT 1.00',
            "ALTER TABLE urunler ADD COLUMN urun_siparis_kart VARCHAR(512) NOT NULL DEFAULT ''",
        );
        foreach ($alters as $sql) {
            try {
                $db->exec($sql);
            } catch (Throwable $e) {
            }
        }
    }
}

date_default_timezone_set( 'Europe/Istanbul' );
$settings=$db->prepare("SELECT * from ayar where ayar_id=?");
$settings->execute(array(0));
$settingsprint=$settings->fetch(PDO::FETCH_ASSOC);
if (!is_array($settingsprint)) {
	$settingsprint = array();
}
$link = isset($settingsprint['ayar_siteurl']) ? $settingsprint['ayar_siteurl'] : '';

// Admin Log Tablosunu Oluştur (Vursa hata vermez)
try {
    $db->query("CREATE TABLE IF NOT EXISTS admin_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_ad VARCHAR(255),
        islem VARCHAR(50),
        ip_adresi VARCHAR(50),
        tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
} catch(PDOException $e) {}

if ( isset( $_POST[ 'login' ] ) )
{
    // 1. Rate Limiting (Hız Limiti)
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
    }

    // 15 dakika (900 saniye) kilit süresi
    if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 900) {
        Header("Location: https://google.com"); exit; // Direkt Google'a fırlat
        exit;
    }

    // Süre dolduysa sıfırla
    if ((time() - $_SESSION['last_attempt_time']) >= 900) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
    }

    // 2. Honeypot (Bal Küpü) Kontrolü
    // Eğer gizli 'website_url' alanı doldurulduysa, bu bir bottur.
    if (!empty($_POST['website_url'])) {
        Header("Location: https://google.com"); exit; // Direkt Google'a fırlat
        exit;
    }

    // 3. Basit Matematik CAPTCHA Kontrolü
    if (!isset($_POST['security_check']) || $_POST['security_check'] != $_SESSION['security_result']) {
        $_SESSION['login_attempts']++; // Hatalı giriş sayısını artır
        $_SESSION['last_attempt_time'] = time();
        Header("Location:../login.php?status=captcha"); exit; // Captcha hatalı
        exit;
    }

    $kullanici_adi  = htmlspecialchars(trim($_POST[ 'kullanici_adi' ]));
    $kullanici_pass = htmlspecialchars(trim(md5( $_POST[ 'kullanici_pass' ] )));
	if ( $kullanici_adi && $kullanici_pass )
	{
		$kullanicisor = $db->prepare( "SELECT * from kullanici where kullanici_adi=:adi and kullanici_pass=:pass" );
		$kullanicisor->execute(
			array(
				'adi'  => $kullanici_adi,
				'pass' => $kullanici_pass
			)
		);
		$say = $kullanicisor->rowCount();
        if ( $say > 0 )
		{
            // Session Fixation Koruması: Oturum ID'sini yenile
            session_regenerate_id(true);

			$_SESSION[ 'kullanici_adi' ] = $kullanici_adi;
            
            // Başarılı girişte sayaçları sıfırla
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt_time']);
            unset($_SESSION['security_result']); // Captcha sonucunu da temizle

            // Log Kaydı (Giriş)
            $log_islem = "Giriş";
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_ekle = $db->prepare("INSERT INTO admin_log SET kullanici_ad=:ad, islem=:islem, ip_adresi=:ip");
            $log_ekle->execute([
                'ad' => $kullanici_adi,
                'islem' => $log_islem,
                'ip' => $ip
            ]);

			Header( "Location:../?status=ok" );
			exit;
		}
		else
		{
			Header( "Location:../?status=no" );
			exit;
			
		}
	}
}

if ( isset( $_POST[ 'whatsappduzenle' ] ) )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}

	$whats_id = isset( $_POST['whats_id'] ) ? (int) $_POST['whats_id'] : 0;
	$mevcut   = $db->prepare( "SELECT * FROM whatsapp WHERE whats_id = ? LIMIT 1" );
	$mevcut->execute( array( $whats_id ) );
	$row      = $mevcut->fetch( PDO::FETCH_ASSOC );

	if ( ! $row ) {
		Header( "Location:../kolay-iletisim.php?status=no" );
		exit;
	}

	// kolay-iletisim.php sadece bir kısım alan gönderiyor; eksik POST anahtarları PHP 8+ hata ve PDO bozulması yapıyordu
	$strKeys = array(
		'whats_tel',
		'whats_cdestek',
		'whats_tiklaara',
		'whats_skype',
		'whats_mail',
	);
	$intKeys = array(
		'whats_durum',
		'whats_cdestekdurum',
		'whats_tiklaaradurum',
		'whats_skypedurum',
		'whats_maildurum',
		'whats_sssdurum',
		'whats_iletisimdurum',
	);

	foreach ( $strKeys as $k ) {
		if ( array_key_exists( $k, $_POST ) ) {
			$row[ $k ] = trim( (string) $_POST[ $k ] );
		}
	}
	foreach ( $intKeys as $k ) {
		if ( array_key_exists( $k, $_POST ) ) {
			$row[ $k ] = (int) $_POST[ $k ];
		}
	}

	try {
		$ayarkaydet = $db->prepare(
			"UPDATE whatsapp SET
			whats_tel=:tel,
			whats_cdestek=:cdestek,
			whats_cdestekdurum=:cdestekdurum,
			whats_tiklaara=:tiklaara,
			whats_tiklaaradurum=:tiklaaradurum,
			whats_skype=:skype,
			whats_skypedurum=:skypedurum,
			whats_mail=:mail,
			whats_maildurum=:maildurum,
			whats_sssdurum=:sssdurum,
			whats_iletisimdurum=:iletisimdurum,
			whats_durum=:durum
			WHERE whats_id=:id"
		);
		$update = $ayarkaydet->execute(
			array(
				'tel'            => $row['whats_tel'],
				'cdestek'        => $row['whats_cdestek'],
				'cdestekdurum'   => $row['whats_cdestekdurum'],
				'tiklaara'       => $row['whats_tiklaara'],
				'tiklaaradurum'  => $row['whats_tiklaaradurum'],
				'skype'          => $row['whats_skype'],
				'skypedurum'     => $row['whats_skypedurum'],
				'mail'           => $row['whats_mail'],
				'maildurum'      => $row['whats_maildurum'],
				'sssdurum'       => $row['whats_sssdurum'],
				'iletisimdurum'  => $row['whats_iletisimdurum'],
				'durum'          => $row['whats_durum'],
				'id'             => $whats_id,
			)
		);
	} catch ( PDOException $e ) {
		$update = false;
	}

	if ( $update ) {
		Header( "Location:../kolay-iletisim.php?status=ok" );
		exit;
	}

	Header( "Location:../kolay-iletisim.php?status=no" );
	exit;
}
if ( isset( $_POST[ 'genelayar' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}

	// Video ayarları kolonlarını kontrol et ve ekle (yoksa)
	try {
		$check_video_autoplay = $db->query("SHOW COLUMNS FROM `ayar` LIKE 'ayar_video_autoplay'")->rowCount();
		if ($check_video_autoplay == 0) {
			$db->exec("ALTER TABLE `ayar` ADD `ayar_video_autoplay` INT(1) NOT NULL DEFAULT 0");
		}
		$check_video_muted = $db->query("SHOW COLUMNS FROM `ayar` LIKE 'ayar_video_muted'")->rowCount();
		if ($check_video_muted == 0) {
			$db->exec("ALTER TABLE `ayar` ADD `ayar_video_muted` INT(1) NOT NULL DEFAULT 1");
		}
		$check_video_loop = $db->query("SHOW COLUMNS FROM `ayar` LIKE 'ayar_video_loop'")->rowCount();
		if ($check_video_loop == 0) {
			$db->exec("ALTER TABLE `ayar` ADD `ayar_video_loop` INT(1) NOT NULL DEFAULT 0");
		}
		$check_yt_autoplay = $db->query("SHOW COLUMNS FROM `ayar` LIKE 'ayar_youtube_autoplay'")->rowCount();
		if ($check_yt_autoplay == 0) {
			$db->exec("ALTER TABLE `ayar` ADD `ayar_youtube_autoplay` INT(1) NOT NULL DEFAULT 0");
		}
		$check_yt_muted = $db->query("SHOW COLUMNS FROM `ayar` LIKE 'ayar_youtube_muted'")->rowCount();
		if ($check_yt_muted == 0) {
			$db->exec("ALTER TABLE `ayar` ADD `ayar_youtube_muted` INT(1) NOT NULL DEFAULT 1");
		}
		$check_yt_loop = $db->query("SHOW COLUMNS FROM `ayar` LIKE 'ayar_youtube_loop'")->rowCount();
		if ($check_yt_loop == 0) {
			$db->exec("ALTER TABLE `ayar` ADD `ayar_youtube_loop` INT(1) NOT NULL DEFAULT 0");
		}
		$check_urun_sablon = $db->query("SHOW COLUMNS FROM `ayar` LIKE 'ayar_urun_sablon'")->rowCount();
		if ($check_urun_sablon == 0) {
			$db->exec("ALTER TABLE `ayar` ADD `ayar_urun_sablon` INT(1) NOT NULL DEFAULT 1");
		}
		$check_kur_fat = $db->query("SHOW COLUMNS FROM `ayar` LIKE 'ayar_kurumsal_fatura_on'")->rowCount();
		if ($check_kur_fat == 0) {
			$db->exec("ALTER TABLE `ayar` ADD `ayar_kurumsal_fatura_on` INT(1) NOT NULL DEFAULT 0");
		}
		$check_otp_verify = $db->query("SHOW COLUMNS FROM `ayar` LIKE 'ayar_siparis_dogrulama_on'")->rowCount();
		if ($check_otp_verify == 0) {
			$db->exec("ALTER TABLE `ayar` ADD `ayar_siparis_dogrulama_on` INT(1) NOT NULL DEFAULT 1");
		}
		foreach ( array(
			'ayar_firma_unvan'  => "VARCHAR(255) NOT NULL DEFAULT ''",
			'ayar_firma_tel'    => "VARCHAR(64) NOT NULL DEFAULT ''",
			'ayar_firma_adresi' => 'TEXT NULL',
			'ayar_firma_email'  => "VARCHAR(255) NOT NULL DEFAULT ''",
		) as $firmaCol => $firmaDef ) {
			$check_firma = $db->query( "SHOW COLUMNS FROM `ayar` LIKE " . $db->quote( $firmaCol ) )->rowCount();
			if ( $check_firma == 0 ) {
				$db->exec( "ALTER TABLE `ayar` ADD `$firmaCol` $firmaDef" );
			}
		}
	} catch (Exception $e) {
		// Kolonlar zaten varsa veya hata olursa sessizce devam et
	}

	// Handle Logo Upload or Deletion
	$refimgyol = $_POST['eskiyol_logo'];
	if (isset($_POST['sil_logo']) && $_POST['sil_logo'] == '1') {
		@unlink("../".basename($_POST["eskiyol_logo"]));
		$refimgyol = "";
	}
	if ( $_FILES[ 'ayar_logo' ][ "size" ] > 0 ) {
		$uploads_dir = '../assets/img/genel';
		@$tmp_name = $_FILES[ 'ayar_logo' ][ "tmp_name" ];
		$benzersizsayi = rand( 20000, 32000 );
		$uzanti = '.webp';
		$target_path = "$uploads_dir/$benzersizsayi$uzanti";
		if (convertToWebp($tmp_name, $target_path, 85)) {
			$refimgyol = substr($target_path, 3);
		} else {
			move_uploaded_file($tmp_name, "$uploads_dir/$benzersizsayi.jpg");
			$refimgyol = substr($uploads_dir, 3) . "/$benzersizsayi.jpg";
		}
		@unlink("../".basename($_POST["eskiyol_logo"]));
	}

	// Handle Alt Gorsel Upload or Deletion
	$refimgyol_alt = $_POST['eskiyol_altgorsel'];
	if (isset($_POST['sil_altgorsel']) && $_POST['sil_altgorsel'] == '1') {
		@unlink("../".basename($_POST["eskiyol_altgorsel"]));
		$refimgyol_alt = "";
	}
	if ( $_FILES[ 'ayar_altgorsel' ][ "size" ] > 0 ) {
		$uploads_dir = '../assets/img/genel';
		@$tmp_name = $_FILES[ 'ayar_altgorsel' ][ "tmp_name" ];
		$benzersizsayi = rand( 33000, 45000 );
		$uzanti = '.webp';
		$target_path = "$uploads_dir/$benzersizsayi$uzanti";
		if (convertToWebp($tmp_name, $target_path, 85)) {
			$refimgyol_alt = substr($target_path, 3);
		} else {
			move_uploaded_file($tmp_name, "$uploads_dir/$benzersizsayi.jpg");
			$refimgyol_alt = substr($uploads_dir, 3) . "/$benzersizsayi.jpg";
		}
		@unlink("../".basename($_POST["eskiyol_altgorsel"]));
	}

	// Handle Favicon Upload
	$refimgyol_fav = $_POST['eskiyol_fav'];
	if ( $_FILES[ 'ayar_fav' ][ "size" ] > 0 ) {
		$uploads_dir = '../assets/img/genel';
		@$tmp_name = $_FILES[ 'ayar_fav' ][ "tmp_name" ];
		$benzersizsayi = rand( 46000, 58000 );
		$uzanti = '.webp';
		$target_path = "$uploads_dir/$benzersizsayi$uzanti";
		if (convertToWebp($tmp_name, $target_path, 85)) {
			$refimgyol_fav = substr($target_path, 3);
		} else {
			move_uploaded_file($tmp_name, "$uploads_dir/$benzersizsayi.jpg");
			$refimgyol_fav = substr($uploads_dir, 3) . "/$benzersizsayi.jpg";
		}
		@unlink("../".basename($_POST["eskiyol_fav"]));
	}

	$ayarkaydet = $db->prepare(
		"UPDATE ayar SET
		ayar_siteurl=:siteurl,
		ayar_il=:il,

		ayar_adres=:adres,
		ayar_kod=:kod,
		ayar_fav2=:fav2,
		ayar_logo=:logo,
		ayar_altgorsel=:altgorsel,
		ayar_fav=:fav,
		ayar_harita=:harita,
		ayar_video_autoplay=:video_autoplay,
		ayar_video_muted=:video_muted,
		ayar_video_loop=:video_loop,
		ayar_youtube_autoplay=:youtube_autoplay,
		ayar_youtube_muted=:youtube_muted,
		ayar_youtube_loop=:youtube_loop,
		ayar_yukari_cik_on=:yukari_cik_on,
		ayar_urun_sablon=:urun_sablon,
		ayar_logo_tip=:logo_tip,
		ayar_logo_metin=:logo_metin,
		ayar_logo_icon=:logo_icon,
		ayar_common_panel_status=:common_panel_status,
		ayar_common_panel_url=:common_panel_url,
		ayar_common_panel_key=:common_panel_key,
		ayar_common_query_status=:common_query_status,
		ayar_siparis_dogrulama_on=:siparis_dogrulama_on,
		ayar_il_sure=:il_sure
		WHERE ayar_id=0"
	);
	try { $db->query("ALTER TABLE ayar ADD COLUMN ayar_il_sure INT DEFAULT 24"); } catch(PDOException $e) {}

	$update = $ayarkaydet->execute(
		array(
			'siteurl' => $_POST['ayar_siteurl'],
			'il' => $_POST['ayar_il'],

			'adres' => $_POST['ayar_adres'],
			'kod' => "#".$_POST['ayar_kod'],
			'fav2' => "#".$_POST['ayar_fav2'],
			'logo' => $refimgyol,
			'altgorsel' => $refimgyol_alt,
			'fav' => $refimgyol_fav,
			'harita' => $_POST['ayar_harita'],
			'video_autoplay' => isset($_POST['ayar_video_autoplay']) ? $_POST['ayar_video_autoplay'] : 0,
			'video_muted' => isset($_POST['ayar_video_muted']) ? $_POST['ayar_video_muted'] : 1,
			'video_loop' => isset($_POST['ayar_video_loop']) ? $_POST['ayar_video_loop'] : 0,
			'youtube_autoplay' => isset($_POST['ayar_youtube_autoplay']) ? $_POST['ayar_youtube_autoplay'] : 0,
			'youtube_muted' => isset($_POST['ayar_youtube_muted']) ? $_POST['ayar_youtube_muted'] : 1,
			'youtube_loop' => isset($_POST['ayar_youtube_loop']) ? $_POST['ayar_youtube_loop'] : 0,
			'yukari_cik_on' => isset($_POST['ayar_yukari_cik_on']) ? $_POST['ayar_yukari_cik_on'] : 1,
			'urun_sablon' => isset($_POST['ayar_urun_sablon']) ? (int)$_POST['ayar_urun_sablon'] : 1,
			'logo_tip' => $_POST['ayar_logo_tip'],
			'logo_metin' => $_POST['ayar_logo_metin'],
			'logo_icon' => $_POST['ayar_logo_icon'],
			'common_panel_status' => $_POST['ayar_common_panel_status'],
			'common_panel_url' => $_POST['ayar_common_panel_url'],
			'common_panel_key' => $_POST['ayar_common_panel_key'],
			'common_query_status' => $_POST['ayar_common_query_status'],
			'siparis_dogrulama_on' => isset($_POST['ayar_siparis_dogrulama_on']) ? (int)$_POST['ayar_siparis_dogrulama_on'] : 1,
			'il_sure' => isset($_POST['ayar_il_sure']) ? min(999999, max(1, intval($_POST['ayar_il_sure']))) : 24
		)
	);

	if ( $update ) {
		Header( "Location:../genel-ayarlar.php?status=ok" );
		exit;
	} else {
		Header( "Location:../genel-ayarlar.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'modulyonetimi' ] ) )
{
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: ../index.php?status=no" ); exit(); exit; }

	// Önce ayar tablosuna yeni sütunları ekle (varsa hata vermez)
	try {
		$db->query("ALTER TABLE ayar ADD COLUMN ayar_sonraki_adim_on INT(1) DEFAULT 1");
	} catch(PDOException $e) {
		// Sütun zaten varsa hata vermez
	}
	try {
		$db->query("ALTER TABLE ayar ADD COLUMN ayar_sonraki_adim_text TEXT");
	} catch(PDOException $e) {
		// Sütun zaten varsa hata vermez
	}
	
	try {
		$db->query("ALTER TABLE ayar ADD COLUMN ayar_sorgula_on INT(1) DEFAULT 1");
	} catch(PDOException $e) {}
	
	try {
		$db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_on INT(1) DEFAULT 0");
	} catch(PDOException $e) {}
	
	try {
		$db->query("ALTER TABLE ayar ADD COLUMN ayar_yukari_cik_on INT(1) DEFAULT 1");
	} catch(PDOException $e) {}

    // Yeni Eklenen Sütunlar (Footer, Neden Biz, Alt Görsel, Ana Sayfa Yorum)
    try { $db->query("ALTER TABLE ayar ADD COLUMN ayar_nedenbiz_on INT(1) DEFAULT 1"); } catch(PDOException $e) {}
    try { $db->query("ALTER TABLE ayar ADD COLUMN ayar_footer_on INT(1) DEFAULT 1"); } catch(PDOException $e) {}
    try { $db->query("ALTER TABLE ayar ADD COLUMN ayar_altgorsel_on INT(1) DEFAULT 1"); } catch(PDOException $e) {}
    try { $db->query("ALTER TABLE ayar ADD COLUMN ayar_yorum_anasayfa_on INT(1) DEFAULT 0"); } catch(PDOException $e) {}
	try { $db->query("ALTER TABLE ayar ADD COLUMN ayar_kurumsal_fatura_on INT(1) NOT NULL DEFAULT 0"); } catch(PDOException $e) {}
	
	$modkaydet = $db->prepare(
		"UPDATE ayar SET
		ayar_sozlesme_on=:sozlesme,
		ayar_sonraki_adim_on=:sonraki_on,
		ayar_sonraki_adim_text=:sonraki_text,
		ayar_sorgula_on=:sorgula,
		ayar_fomo_on=:fomo_on,
		ayar_fomo_saat=:fomo_saat,
		ayar_bildirim_on=:bildirim_on,
		ayar_ip_sehir_on=:ip_sehir_on,
		ayar_wa_sablon=:wa_sablon,
		ayar_stok_on=:stok_on,
		ayar_stok_sayi=:stok_sayi,
		ayar_gizlilik_on=:gizlilik_on,
		ayar_cookie_on=:cookie_on,
		ayar_cookie_sure=:cookie_sure,
		ayar_yukari_cik_on=:yukari_cik_on,
		ayar_urun_ad_on=:urun_ad_on,
		ayar_urun_fiyat_on=:urun_fiyat_on,
		ayar_urun_ad_boyut=:urun_ad_boyut,
		ayar_urun_fiyat_boyut=:urun_fiyat_boyut,
		ayar_nedenbiz_on=:nedenbiz_on,
		ayar_footer_on=:footer_on,
		ayar_altgorsel_on=:altgorsel_on,
		ayar_yorum_anasayfa_on=:yorum_anasayfa_on,
		ayar_urun_secenek_on=:urun_secenek_on,
		ayar_siparis_bar=:siparis_bar,
		ayar_kurumsal_fatura_on=:kurumsal_fatura_on
		WHERE ayar_id=0"
	);
	$update = $modkaydet->execute(
		array(
			'sozlesme' => $_POST[ 'ayar_sozlesme_on' ],
			'sonraki_on' => isset($_POST['ayar_sonraki_adim_on']) ? $_POST['ayar_sonraki_adim_on'] : 1,
			'sonraki_text' => isset($_POST['ayar_sonraki_adim_text']) ? $_POST['ayar_sonraki_adim_text'] : '',
			'sorgula' => $_POST[ 'ayar_sorgula_on' ],
			'fomo_on' => $_POST[ 'ayar_fomo_on' ],
			'fomo_saat' => $_POST[ 'ayar_fomo_saat' ],
			'bildirim_on' => $_POST[ 'ayar_bildirim_on' ],
			'ip_sehir_on' => $_POST[ 'ayar_ip_sehir_on' ],
			'wa_sablon' => $_POST[ 'ayar_wa_sablon' ],
			'stok_on' => $_POST[ 'ayar_stok_on' ],
				'stok_sayi'      => $_POST[ 'ayar_stok_sayi' ],
				'gizlilik_on'    => $_POST[ 'ayar_gizlilik_on' ],
				'cookie_on'      => $_POST[ 'ayar_cookie_on' ],
				'cookie_sure'    => min( 99999999, max( 1, intval( isset( $_POST['ayar_cookie_sure'] ) ? $_POST['ayar_cookie_sure'] : 1440 ) ) ),
			'yukari_cik_on' => isset($_POST['ayar_yukari_cik_on']) ? $_POST['ayar_yukari_cik_on'] : 1,
			'urun_ad_on' => isset($_POST['ayar_urun_ad_on']) ? $_POST['ayar_urun_ad_on'] : 1,
			'urun_fiyat_on' => isset($_POST['ayar_urun_fiyat_on']) ? $_POST['ayar_urun_fiyat_on'] : 1,
			'urun_ad_boyut' => isset($_POST['ayar_urun_ad_boyut']) ? $_POST['ayar_urun_ad_boyut'] : '1.4',
			'urun_fiyat_boyut' => isset($_POST['ayar_urun_fiyat_boyut']) ? $_POST['ayar_urun_fiyat_boyut'] : '1.5',
            'nedenbiz_on' => isset($_POST['ayar_nedenbiz_on']) ? $_POST['ayar_nedenbiz_on'] : 1,
            'footer_on' => isset($_POST['ayar_footer_on']) ? $_POST['ayar_footer_on'] : 1,
            'altgorsel_on' => isset($_POST['ayar_altgorsel_on']) ? $_POST['ayar_altgorsel_on'] : 1,
			'yorum_anasayfa_on' => isset($_POST['ayar_yorum_anasayfa_on']) ? $_POST['ayar_yorum_anasayfa_on'] : 0,
            'urun_secenek_on' => isset($_POST['ayar_urun_secenek_on']) ? $_POST['ayar_urun_secenek_on'] : 1,
            'siparis_bar' => isset($_POST['ayar_siparis_bar']) ? $_POST['ayar_siparis_bar'] : 1,
			'kurumsal_fatura_on' => isset($_POST['ayar_kurumsal_fatura_on']) ? (int)$_POST['ayar_kurumsal_fatura_on'] : 0
		)
	);

	if ( $update ) { Header( "Location:../genel-ayarlar.php?status=ok" ); exit; }
	else { Header( "Location:../genel-ayarlar.php?status=no" ); exit; }
}
if ( isset( $_POST[ 'arkaplan' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	if ( $_FILES[ 'ayar_resimcounter' ][ "size" ] > 0 || $_FILES[ 'ayar_resimparalax' ][ "size" ] > 0)
	{ 

		if ( $_FILES[ 'ayar_resimparalax' ][ "size" ] > 0 )
		{ 
			$uploads_dir = '../assets/img/genel';
			@$tmp_name = $_FILES[ 'ayar_resimparalax' ][ "tmp_name" ];
			$benzersizsayi4 = rand( 20000, 32000 );
			$uzanti = '.jpg';
			$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizsayi4 . $uzanti;

			@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$uzanti" );
			@convertToWebp("$uploads_dir/$benzersizsayi4$uzanti", "$uploads_dir/$benzersizsayi4.webp");

			$ayarkaydet = $db->prepare(
				"UPDATE ayar SET
				ayar_resimparalax=:logo

				WHERE ayar_id=0"
			);
			$update     = $ayarkaydet->execute(
				array(
					'logo' => $refimgyol
				)
			);

			if ( $update )
			{
				$resimsilunlink = $_POST[ 'eskiyol_paralax' ];
				unlink( "../$resimsilunlink" );

				Header( "Location:../genel-ayarlar.php?status=ok" );
				exit;
			}
			else
			{

				Header( "Location:../genel-ayarlar.php?status=no" );
				exit;
			}
		} 

		if ( $_FILES[ 'ayar_resimcounter' ][ "size" ] > 0 )
		{ 
			$uploads_dir = '../assets/img/genel';
			@$tmp_name = $_FILES[ 'ayar_resimcounter' ][ "tmp_name" ];
			$benzersizsayi4 = rand( 20000, 32000 );
			$uzanti = '.jpg';
			$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizsayi4 . $uzanti;

			@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$uzanti" );

			$ayarkaydet = $db->prepare(
				"UPDATE ayar SET
				ayar_resimcounter=:fav

				WHERE ayar_id=0"
			);
			$update     = $ayarkaydet->execute(
				array(
					'fav' => $refimgyol
				)
			);

			if ( $update )
			{
				$resimsilunlink = $_POST[ 'eskiyol_counter' ];
				unlink( "../$resimsilunlink" );

				Header( "Location:../genel-ayarlar.php?status=ok" );
				exit;
			}
			else
			{

				Header( "Location:../genel-ayarlar.php?status=no" );
				exit;
			}
		} 
	}else {
		Header( "Location:../genel-ayarlar.php?status=eksik" );
		exit;
	}

}
if ( isset( $_POST[ 'seoayar' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$ayarkaydet = $db->prepare(
		"UPDATE ayar SET
		ayar_title=:title,
		ayar_description=:description,
		ayar_keywords=:keywords
		WHERE ayar_id=0"
	);
	$update     = $ayarkaydet->execute(
		array(
			'title'       => $_POST[ 'ayar_title' ],
			'description' => $_POST[ 'ayar_description' ],
			'keywords' => $_POST[ 'ayar_keywords' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../genel-ayarlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../genel-ayarlar.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'renkayar' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$ayarkaydet = $db->prepare(
		"UPDATE ayar SET
		ayar_mobil=:mobil,
		ayar_renk=:renk
		WHERE ayar_id=0"
	);
	$update     = $ayarkaydet->execute(
		array(
			'mobil'       => $_POST[ 'ayar_mobil' ],
			'renk'       => $_POST[ 'ayar_renk' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../genel-ayarlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../genel-ayarlar.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'motorduzenle' ] ) )
{
	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}

	// Meta CAPI için tablo kolonlarını kontrol et/ekle
	try {
		$db->query("ALTER TABLE motor ADD COLUMN motor_meta_token TEXT");
	} catch(PDOException $e) {}
	try {
		$db->query("ALTER TABLE motor ADD COLUMN motor_meta_pixel_id VARCHAR(255)");
	} catch(PDOException $e) {}

	// TikTok API için tablo kolonlarını kontrol et/ekle
	try {
		$db->query("ALTER TABLE motor ADD COLUMN motor_tiktok_token TEXT");
	} catch(PDOException $e) {}
	try {
		$db->query("ALTER TABLE motor ADD COLUMN motor_tiktok_pixel_id VARCHAR(255)");
	} catch(PDOException $e) {}

	$ayarkaydet = $db->prepare(
		"UPDATE motor SET
		motor_analitik=:analitik,
		motor_metrika=:metrika,
		motor_gonay=:gonay,
		motor_yonay=:yonay,
		motor_meta_token=:meta_token,
		motor_meta_pixel_id=:meta_pixel_id,
		motor_tiktok_token=:tiktok_token,
		motor_tiktok_pixel_id=:tiktok_pixel_id
		WHERE motor_id=:id"
	);
	$update     = $ayarkaydet->execute(
		array(
			'analitik' => $_POST[ 'motor_analitik' ],
			'metrika' => $_POST[ 'motor_metrika' ],
			'gonay' => $_POST[ 'motor_gonay' ],
			'yonay' => $_POST[ 'motor_yonay' ],
			'meta_token' => $_POST[ 'motor_meta_token' ],
			'meta_pixel_id' => $_POST[ 'motor_meta_pixel_id' ],
			'tiktok_token' => $_POST[ 'motor_tiktok_token' ],
			'tiktok_pixel_id' => $_POST[ 'motor_tiktok_pixel_id' ],
			'id' => $_POST[ 'motor_id' ]
		)
	);

	if ( $update )
	{
		Header( "Location:../api-ayarlari.php?status=ok" );
		exit;
	}
	else
	{
		Header( "Location:../api-ayarlari.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'logoduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$uploads_dir = '../assets/img/genel';
	@$tmp_name = $_FILES[ 'ayar_logo' ][ "tmp_name" ];
	@$name = $_FILES[ 'ayar_logo' ][ "name" ];
	$benzersizsayi4 = rand( 20000, 32000 );
	$refimgyol      = substr( $uploads_dir, 6 ) . "/" . $benzersizsayi4 . $name;

	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$name" );
	@convertToWebp("$uploads_dir/$benzersizsayi4$name", "$uploads_dir/".preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $benzersizsayi4.$name));

	$ayarkaydet = $db->prepare(
		"UPDATE ayar SET
		ayar_logo=:logo
		WHERE ayar_id=0"
	);
	$update     = $ayarkaydet->execute(
		array(
			'logo' => $refimgyol
		)
	);

	if ( $update )
	{
		$resimsilunlink = $_POST[ 'eski_yol' ];
		unlink(SITE_ROOT . "/$resimsilunlink" );

		Header( "Location:../genel-ayarlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../genel-ayarlar.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'favduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$uploads_dir = '../../img';
	@$tmp_name = $_FILES[ 'ayar_fav' ][ "tmp_name" ];
	@$name = $_FILES[ 'ayar_fav' ][ "name" ];
	$benzersizsayi4 = rand( 20000, 32000 );
	$refimgyol2     = substr( $uploads_dir, 6 ) . "/" . $benzersizsayi4 . $name;

	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$name" );

	$ayarkaydet = $db->prepare(
		"UPDATE ayar SET
		ayar_fav=:fav
		WHERE ayar_id=0"
	);
	$update     = $ayarkaydet->execute(
		array(
			'fav' => $refimgyol2,
		)
	);

	if ( $update )
	{

		$resimsilunlink = $_POST[ 'eski_yol2' ];
		unlink(SITE_ROOT . "/$resimsilunlink" );

		Header( "Location:../genel-ayarlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../genel-ayarlar.php?status=no" );
		exit;
	}
}


if ( isset( $_POST[ 'sosyalekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$ayarkaydet = $db->prepare(
		"INSERT INTO sosyal SET
		sosyal_link=:link,
		sosyal_icon=:icon
		"
	);
	$update     = $ayarkaydet->execute(
		array(
			'link' => $_POST[ 'sosyal_link' ],
			'icon' => $_POST[ 'sosyal_icon' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../sosyal-medya.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../sosyal-medya.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'sahtebildirimduzenle' ] ) )
{
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: ../index.php?status=no" ); exit(); exit; }

	$ayarkaydet = $db->prepare(
		"UPDATE sahte_bildirimler SET
		sahte_ad=:ad,
		sahte_il=:il,
		sahte_sure=:sure,
		sahte_durum=:durum
		WHERE sahte_id=:id"
	);
	$update = $ayarkaydet->execute(
		array(
			'ad' => $_POST[ 'sahte_ad' ],
			'il' => $_POST[ 'sahte_il' ],
			'sure' => $_POST[ 'sahte_sure' ],
			'durum' => $_POST[ 'sahte_durum' ],
			'id' => $_POST[ 'sahte_id' ]
		)
	);

	if ( $update ) { Header( "Location:../sahte-bildirimler.php?status=ok" ); exit; }
	else { Header( "Location:../sahte-bildirimler.php?status=no" ); exit; }
}

if ( isset( $_POST[ 'sahtebildirimkaydet' ] ) )
{
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: ../index.php?status=no" ); exit(); exit; }

	$ayarkaydet = $db->prepare(
		"INSERT INTO sahte_bildirimler SET
		sahte_ad=:ad,
		sahte_il=:il,
		sahte_sure=:sure,
		sahte_durum=:durum
		"
	);
	$update = $ayarkaydet->execute(
		array(
			'ad' => $_POST[ 'sahte_ad' ],
			'il' => $_POST[ 'sahte_il' ],
			'sure' => $_POST[ 'sahte_sure' ],
			'durum' => $_POST[ 'sahte_durum' ]
		)
	);

	if ( $update ) { Header( "Location:../sahte-bildirimler.php?status=ok" ); exit; }
	else { Header( "Location:../sahte-bildirimler.php?status=no" ); exit; }
}

if ( isset( $_GET[ 'sahtebildirimsil' ] ) )
{
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: ../index.php?status=no" ); exit(); exit; }

	$sil = $db->prepare("DELETE from sahte_bildirimler where sahte_id=:id");
	$kontrol = $sil->execute(array(
		'id' => $_GET['sahte_id']
	));

	if ($kontrol) { Header("Location:../sahte-bildirimler.php?durum=ok"); exit; }
	else { Header("Location:../sahte-bildirimler.php?durum=no"); exit; }
}

if ( isset( $_POST[ 'mailayarlari' ] ) )
{


	$ayarkaydet = $db->prepare(
		"UPDATE mail SET
		mail_user=:user,
		mail_host=:host,
		mail_pass=:pass,
		mail_bildirim=:bildirim,
		mail_name=:name,
		mail_sender=:sender,
		mail_secure=:secure,
		mail_port=:port
		WHERE mail_id=0"
	);
	$update     = $ayarkaydet->execute(
		array(
			'user' => $_POST[ 'mail_user' ],
			'host' => $_POST[ 'mail_host' ],
			'pass' => $_POST[ 'mail_pass' ],
			'bildirim' => $_POST[ 'mail_bildirim' ],
			'name' => $_POST[ 'mail_name' ],
			'sender' => $_POST[ 'mail_sender' ],
			'secure' => $_POST[ 'mail_secure' ],
			'port' => $_POST[ 'mail_port' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../genel-ayarlar.php?status=ok&tab=mail" );
		exit;
	}
	else
	{

		Header( "Location:../genel-ayarlar.php?status=no&tab=mail" );
		exit;
	}
}

if ( isset( $_POST['mail_test_gonder'] ) ) {
	if ( empty( $_SESSION['kullanici_adi'] ) ) {
		header( 'Location: ../login.php?status=no' );
		exit;
	}

	$test_to = isset( $_POST['mail_test_adres'] ) ? trim( (string) $_POST['mail_test_adres'] ) : '';
	if ( $test_to === '' || ! filter_var( $test_to, FILTER_VALIDATE_EMAIL ) ) {
		header( 'Location: ../genel-ayarlar.php?tab=mail&mail_test_err=' . rawurlencode( 'Geçerli bir alıcı e-posta adresi girin.' ) );
		exit;
	}

	$hn  = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
	$sub = 'SMTP test — ' . $hn;
	$htm = '<p>Bu bir <strong>SMTP test</strong> e-postasıdır.</p><p>Tarih: ' . htmlspecialchars( date( 'd.m.Y H:i:s' ), ENT_QUOTES, 'UTF-8' ) . '</p><p>Host: ' . htmlspecialchars( $hn, ENT_QUOTES, 'UTF-8' ) . '</p>';
	$pln = 'SMTP test. ' . date( 'd.m.Y H:i:s' ) . ' ' . $hn;

	if ( function_exists( 'panelSmtpSendHtml' ) ) {
		$res = panelSmtpSendHtml( $test_to, $sub, $htm, $pln );
		if ( ! empty( $res['success'] ) ) {
			header( 'Location: ../genel-ayarlar.php?tab=mail&mail_test_ok=1' );
			exit;
		}
		$err = isset( $res['error'] ) ? $res['error'] : 'Gönderilemedi';
		header( 'Location: ../genel-ayarlar.php?tab=mail&mail_test_err=' . rawurlencode( $err ) );
		exit;
	}

	header( 'Location: ../genel-ayarlar.php?tab=mail&mail_test_err=' . rawurlencode( 'panelSmtpSendHtml tanımlı değil (config.php).' ) );
	exit;
}

if ( isset( $_POST[ 'smsayarlari' ] ) )
{
	try {
		$db->query( "ALTER TABLE sms ADD COLUMN sms_provider VARCHAR(20) NOT NULL DEFAULT 'mutlucell'" );
	} catch ( Exception $e ) {}

	$ayarkaydet = $db->prepare(
		"UPDATE sms SET
		sms_kullanici=:kullanici,
		sms_sifre=:sifre,
		sms_baslik=:baslik,
        sms_durum=:durum,
		sms_provider=:provider
		WHERE sms_id=:id"
	);
	$update     = $ayarkaydet->execute(
		array(
			'kullanici' => $_POST[ 'sms_kullanici' ],
			'sifre'     => $_POST[ 'sms_sifre' ],
			'baslik'    => $_POST[ 'sms_baslik' ],
            'durum'     => $_POST[ 'sms_durum' ],
			'provider'  => ( isset( $_POST['sms_provider'] ) && $_POST['sms_provider'] === 'netgsm' ) ? 'netgsm' : 'mutlucell',
			'id'        => $_POST[ 'sms_id' ]
		)
	);

	if ( $update )
	{
		Header( "Location:../genel-ayarlar.php?status=ok&tab=sms" );
		exit;
	}
	else
	{
		Header( "Location:../genel-ayarlar.php?status=no&tab=sms" );
		exit;
	}
}

if ( isset( $_POST['sms_test_gonder'] ) ) {
	if ( empty( $_SESSION['kullanici_adi'] ) ) {
		header( 'Location: ../login.php?status=no' );
		exit;
	}

	$rawTel = isset( $_POST['sms_test_tel'] ) ? trim( (string) $_POST['sms_test_tel'] ) : '';
	$digits = preg_replace( '/\D+/', '', $rawTel );
	if ( strlen( $digits ) === 12 && strncmp( $digits, '90', 2 ) === 0 ) {
		$digits = substr( $digits, 2 );
	}
	if ( strlen( $digits ) === 11 && $digits[0] === '0' ) {
		$digits = substr( $digits, 1 );
	}
	if ( strlen( $digits ) !== 10 || $digits[0] !== '5' ) {
		header( 'Location: ../genel-ayarlar.php?tab=sms&sms_test_err=' . rawurlencode( 'Geçerli bir GSM girin (05XXXXXXXXX).' ) );
		exit;
	}

	try {
		$sq = $db->prepare( 'SELECT sms_provider, sms_durum FROM sms WHERE sms_id=0 LIMIT 1' );
		$sq->execute();
		$sr = $sq->fetch( PDO::FETCH_ASSOC );
		$provider = isset( $sr['sms_provider'] ) ? strtoupper( (string) $sr['sms_provider'] ) : 'MUTLUCELL';
		$isActive = isset( $sr['sms_durum'] ) ? (int) $sr['sms_durum'] : 0;
		if ( $isActive !== 1 ) {
			header( 'Location: ../genel-ayarlar.php?tab=sms&sms_test_err=' . rawurlencode( 'SMS gönderimi pasif. Önce aktif edin.' ) );
			exit;
		}

		$msg = 'Test SMS basarili. Saglayici: ' . $provider . '. Tarih: ' . date( 'd.m.Y H:i:s' );
		$ok = function_exists( 'sendTransactionalSms' ) ? sendTransactionalSms( $digits, $msg ) : false;
		if ( $ok ) {
			header( 'Location: ../genel-ayarlar.php?tab=sms&sms_test_ok=1' );
			exit;
		}
		$detail = function_exists( 'smsLastErrorGet' ) ? trim( (string) smsLastErrorGet() ) : '';
		$errMsg = 'Gönderim başarısız. API kullanıcı/şifre/başlık veya yetkiyi kontrol edin.';
		if ( $detail !== '' ) {
			$errMsg .= ' Detay: ' . $detail;
		}
		header( 'Location: ../genel-ayarlar.php?tab=sms&sms_test_err=' . rawurlencode( $errMsg ) );
		exit;
	} catch ( Exception $e ) {
		header( 'Location: ../genel-ayarlar.php?tab=sms&sms_test_err=' . rawurlencode( $e->getMessage() ) );
		exit;
	}
}

if ( isset( $_POST['sms_api_test_gonder'] ) ) {
	if ( empty( $_SESSION['kullanici_adi'] ) ) {
		header( 'Location: ../login.php?status=no' );
		exit;
	}

	$rawTel = isset( $_POST['sms_api_test_tel'] ) ? trim( (string) $_POST['sms_api_test_tel'] ) : '';
	$digits = preg_replace( '/\D+/', '', $rawTel );
	if ( strlen( $digits ) === 12 && strncmp( $digits, '90', 2 ) === 0 ) {
		$digits = substr( $digits, 2 );
	}
	if ( strlen( $digits ) === 11 && $digits[0] === '0' ) {
		$digits = substr( $digits, 1 );
	}
	if ( strlen( $digits ) !== 10 || $digits[0] !== '5' ) {
		header( 'Location: ../genel-ayarlar.php?tab=sms&sms_api_test_err=' . rawurlencode( 'Geçerli bir GSM girin (05XXXXXXXXX).' ) );
		exit;
	}

	try {
		$sq = $db->prepare( 'SELECT sms_provider, sms_durum FROM sms WHERE sms_id=0 LIMIT 1' );
		$sq->execute();
		$sr = $sq->fetch( PDO::FETCH_ASSOC );
		$provider = isset( $sr['sms_provider'] ) ? strtoupper( (string) $sr['sms_provider'] ) : 'MUTLUCELL';
		$isActive = isset( $sr['sms_durum'] ) ? (int) $sr['sms_durum'] : 0;
		if ( $isActive !== 1 ) {
			header( 'Location: ../genel-ayarlar.php?tab=sms&sms_api_test_err=' . rawurlencode( 'SMS gönderimi pasif. Önce aktif edin.' ) );
			exit;
		}

		$msg = 'API TEST ' . date( 'Y-m-d H:i:s' );
		$ok = function_exists( 'sendTransactionalSms' ) ? sendTransactionalSms( $digits, $msg ) : false;
		$resp = function_exists( 'smsLastResponseGet' ) ? trim( (string) smsLastResponseGet() ) : '';
		$err = function_exists( 'smsLastErrorGet' ) ? trim( (string) smsLastErrorGet() ) : '';

		if ( $ok ) {
			$info = 'Sağlayıcı: ' . $provider . '. Yanıt: ' . ( $resp !== '' ? $resp : 'OK' );
			header( 'Location: ../genel-ayarlar.php?tab=sms&sms_api_test_ok=' . rawurlencode( $info ) );
			exit;
		}
		$info = 'Sağlayıcı: ' . $provider . '. Hata: ' . ( $err !== '' ? $err : 'Bilinmeyen hata' );
		if ( $resp !== '' ) {
			$info .= ' | Yanıt: ' . $resp;
		}
		header( 'Location: ../genel-ayarlar.php?tab=sms&sms_api_test_err=' . rawurlencode( $info ) );
		exit;
	} catch ( Exception $e ) {
		header( 'Location: ../genel-ayarlar.php?tab=sms&sms_api_test_err=' . rawurlencode( $e->getMessage() ) );
		exit;
	}
}
if ( isset( $_POST[ 'profilresimduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$uploads_dir = SITE_ROOT . '/xnull/assets/img/genel';
	@$tmp_name = $_FILES[ 'kullanici_resim' ][ "tmp_name" ];
	$uzanti='.jpg';
	$benzersizsayi4 = rand( 20000, 32000 );
	$refimgyol      = "assets/img/genel/" . $benzersizsayi4 . $uzanti;

	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$uzanti" );

	$ayarkaydet = $db->prepare(
		"UPDATE kullanici SET
		kullanici_resim=:resim
		WHERE kullanici_id=0"
	);
	$update     = $ayarkaydet->execute(
		array(
			'resim' => $refimgyol
		)
	);

	if ( $update )
	{


		Header( "Location:../user.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../user.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'kullaniciduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$ayarkaydet = $db->prepare(
		"UPDATE kullanici SET
		kullanici_adsoyad=:adsoyad,
		kullanici_adi=:adi
		WHERE kullanici_id=0"
	);
	$update     = $ayarkaydet->execute(
		array(
			'adsoyad' => $_POST[ 'kullanici_adsoyad' ],
			'adi'     => $_POST[ 'kullanici_adi' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../user.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../user.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'kullanicisifre' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$kullanici_pass = md5( $_POST[ 'kullanici_pass' ] );

	$ayarkaydet = $db->prepare(
		"UPDATE kullanici SET
		kullanici_pass=:pass
		WHERE kullanici_id=0"
	);
	$update     = $ayarkaydet->execute(
		array(
			'pass' => $kullanici_pass
		)
	);

	if ( $update )
	{

		Header( "Location:../user.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../user.php?status=no" );
		exit;
	}
}


if ( isset( $_POST[ 'sssduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$sss_id = $_POST[ 'id' ];

	$ayarkaydet = $db->prepare(
		"UPDATE sss SET
		sss_soru=:soru,
		sss_sira=:sira,
		sss_cevap=:cevap
		WHERE id=:id"
	);
	$update     = $ayarkaydet->execute(
		array(
			'soru'  => $_POST[ 'sss_soru' ],
			'sira'  => $_POST[ 'sss_sira' ],
			'cevap' => $_POST[ 'sss_cevap' ],
			'id'    => $sss_id
		)
	);

	if ( $update )
	{

		Header( "Location:../sss.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../sss.php?status=ok" );
		exit;
	}
}
if ( isset( $_POST[ 'kategoriekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$ayarkaydet = $db->prepare(
		"INSERT INTO kategoriler SET
		kategori_ad=:ad,
		kategori_title=:title,
		kategori_descr=:descr,
		kategori_keyword=:keyword,
		kategori_siraid=:sira
		"
	);
	$update     = $ayarkaydet->execute(
		array(
			'ad'     => $_POST[ 'kategori_ad' ],
			'title'     => $_POST[ 'kategori_title' ],
			'descr'     => $_POST[ 'kategori_descr' ],
			'keyword'     => $_POST[ 'kategori_keyword' ],
			'sira'    => $_POST[ 'kategori_siraid' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../kategoriler.php?status=ok" );
		exit;
	}
	else
	{
		Header( "Location:../kategoriler.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'kategoriduzenle' ] ) )
{
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: ../index.php?status=no" ); exit(); exit; }

	$duzenle = $db->prepare(
		"UPDATE kategoriler SET
		kategori_ad=:ad,
		kategori_title=:title,
		kategori_descr=:descr,
		kategori_keyword=:keyword,
		kategori_siraid=:sira
		WHERE id=:id"
	);
	$update  = $duzenle->execute(
		array(
			'ad'     => $_POST[ 'kategori_ad' ],
			'title'     => $_POST[ 'kategori_title' ],
			'descr'     => $_POST[ 'kategori_descr' ],
			'keyword'     => $_POST[ 'kategori_keyword' ],
			'sira'    => $_POST[ 'kategori_siraid' ],
			'id'     => $_POST['id']
		)
	);

	if ( $update ) { Header( "Location:../kategoriler.php?status=ok" ); exit; } else { Header( "Location:../kategoriler.php?status=no" ); exit; }
	exit;
}

if ( isset( $_POST[ 'sssekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$ayarkaydet = $db->prepare(
		"INSERT INTO sss SET
		sss_soru=:soru,
		sss_cevap=:cevap,
		sss_sira=:sira
		"
	);
	$update     = $ayarkaydet->execute(
		array(
			'soru'  => $_POST[ 'sss_soru' ],
			'cevap' => $_POST[ 'sss_cevap' ],
			'sira'  => $_POST[ 'sss_sira' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../sss.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../sss.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'omenuekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$ust=$_POST[ 'omenu_ust' ];
	if ($ust==0) {
		$ayarkaydet = $db->prepare(
			"INSERT INTO omenu SET
			omenu_ad=:ad,
			omenu_link=:link,
			omenu_ust=:ust,
			omenu_durum=:durum,
			omenu_sira=:sira
			"
		);
		$update     = $ayarkaydet->execute(
			array(
				'ad'  => $_POST[ 'omenu_ad' ],
				'link' => $_POST[ 'omenu_link' ],
				'ust' => $_POST[ 'omenu_ust' ],
				'durum' => '0',
				'sira'  => $_POST[ 'omenu_sira' ]
			)
		);
	} else {
		$ayarkaydet = $db->prepare(
			"INSERT INTO omenu SET
			omenu_ad=:ad,
			omenu_link=:link,
			omenu_ust=:ust,
			omenu_durum=:durum,
			omenu_sira=:sira
			"
		);
		$update     = $ayarkaydet->execute(
			array(
				'ad'  => $_POST[ 'omenu_ad' ],
				'link' => $_POST[ 'omenu_link' ],
				'ust' => $_POST[ 'omenu_ust' ],
				'durum' => $_POST[ 'omenu_ust' ],
				'sira'  => $_POST[ 'omenu_sira' ]
			)
		);
	}
	if ( $update )
	{
		$ayarkaydet = $db->prepare(
			"UPDATE omenu SET
			omenu_durum=:durum
			WHERE omenu_id=:id"
		);
		$update     = $ayarkaydet->execute(
			array(
				'durum' => $_POST[ 'omenu_ust' ],
				'id'    => $_POST[ 'omenu_ust' ]
			)
		);

		Header( "Location:../menu.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../menu.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'flinkekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$ayarkaydet = $db->prepare(
		"INSERT INTO flink SET
		flink_ad=:ad,
		flink_link=:link
		"
	);
	$update     = $ayarkaydet->execute(
		array(
			'ad'  => $_POST[ 'flink_ad' ],
			'link' => $_POST[ 'flink_link' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../alt-link.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../alt-link.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'fmenuekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$ayarkaydet = $db->prepare(
		"INSERT INTO fmenu SET
		fmenu_ad=:ad,
		fmenu_link=:link,
		fmenu_sira=:sira
		"
	);
	$update     = $ayarkaydet->execute(
		array(
			'ad'  => $_POST[ 'fmenu_ad' ],
			'link' => $_POST[ 'fmenu_link' ],
			'sira'  => $_POST[ 'fmenu_sira' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../alt-menu.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../alt-menu.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'slaytresimduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$uploads_dir = '../../img/slayt';
	@$tmp_name = $_FILES[ 'slayt_resim' ][ "tmp_name" ];
	@$name = $_FILES[ 'slayt_resim' ][ "name" ];
	$benzersizsayi4 = rand( 20000, 32000 );
	$refimgyol2     = substr( $uploads_dir, 6 ) . "/" . $benzersizsayi4 . $name;

	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$name" );

	$slayt_id = $_POST[ 'slayt_id' ];

	$ayarkaydet = $db->prepare(
		"UPDATE slayt SET
		slayt_resim=:resim
		WHERE slayt_id={$_POST['slayt_id']}"
	);
	$update     = $ayarkaydet->execute(
		array(
			'resim' => $refimgyol2,
		)
	);

	if ( $update )
	{

		$resimsilunlink = $_POST[ 'eski_yol' ];
		unlink(SITE_ROOT . "/$resimsilunlink" );

		Header( "Location:../slayt.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../slayt.php?status=no" );
		exit;
	}
}


if ( isset( $_POST[ 'hizmetresimduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$uploads_dir = '../../img/hizmetler';
	@$tmp_name = $_FILES[ 'hizmet_resim' ][ "tmp_name" ];
	@$name = $_FILES[ 'hizmet_resim' ][ "name" ];
	$benzersizsayi4 = rand( 20000, 32000 );
	$refimgyol2     = substr( $uploads_dir, 6 ) . "/" . $benzersizsayi4 . $name;

	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$name" );

	$slayt_id = $_POST[ 'hizmet_id' ];

	$ayarkaydet = $db->prepare(
		"UPDATE hizmetler SET
		hizmet_resim=:resim
		WHERE hizmet_id={$_POST['hizmet_id']}"
	);
	$update     = $ayarkaydet->execute(
		array(
			'resim' => $refimgyol2,
		)
	);

	if ( $update )
	{

		$resimsilunlink = $_POST[ 'eski_yol' ];
		unlink(SITE_ROOT . "/$resimsilunlink" );

		Header( "Location:../hizmetler.php?durum=ok" );
		exit;
	}
	else
	{

		Header( "Location:../hizmetler.php?durum=no" );
		exit;
	}
}
if ( isset( $_POST[ 'yorumresimduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$uploads_dir = SITE_ROOT . '/img/yorum';
	@$tmp_name = $_FILES[ 'yorum_resim' ][ "tmp_name" ];
	@$name = $_FILES[ 'yorum_resim' ][ "name" ];
	$benzersizsayi4 = rand( 20000, 32000 );
	$refimgyol2     = "img/yorum/" . $benzersizsayi4 . $name;

	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$name" );

	$slayt_id = $_POST[ 'yorum_id' ];

	$ayarkaydet = $db->prepare(
		"UPDATE yorumlar SET
		yorum_resim=:resim
		WHERE yorum_id={$_POST['yorum_id']}"
	);
	$update     = $ayarkaydet->execute(
		array(
			'resim' => $refimgyol2,
		)
	);

	if ( $update )
	{

		$resimsilunlink = $_POST[ 'eski_yol' ];
		if (!empty($resimsilunlink)) {
			@unlink( SITE_ROOT . "/" . ltrim($resimsilunlink, '/') );
		}

		Header( "Location:../yorumlar.php?durum=ok" );
		exit;
	}
	else
	{

		Header( "Location:../yorumlar.php?durum=no" );
		exit;
	}
}
if ( isset( $_POST[ 'urunresimekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$uploads_dir = '../assets/img/urunler';
	$tmp_name = $_FILES[ 'resim_link' ][ "tmp_name" ];
	$benzersizsayi1 = rand( 20000, 32000 );
	$benzersizsayi2 = rand( 20000, 32000 );
	$uzanti = '.jpg';
	$benzersizad    = $benzersizsayi1 . $benzersizsayi2 ;
	$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
	move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

	$kaydet = $db->prepare(
		"INSERT INTO resim SET
		resim_urun=:urun,
		resim_link=:link
		");
	$insert = $kaydet->execute(
		array(
			'urun'     => $_POST[ 'resim_urun' ],
			'link'    => $refimgyol
		));

	if ( $insert )
	{

		Header( "Location:../urunler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../urunler.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'projeresimduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$uploads_dir = '../../img/projeler';
	@$tmp_name = $_FILES[ 'proje_resim' ][ "tmp_name" ];
	@$name = $_FILES[ 'proje_resim' ][ "name" ];
	$benzersizsayi4 = rand( 20000, 32000 );
	$refimgyol2     = substr( $uploads_dir, 6 ) . "/" . $benzersizsayi4 . $name;

	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$name" );



	$ayarkaydet = $db->prepare(
		"UPDATE projeler SET
		proje_resim=:resim
		WHERE proje_id={$_POST['proje_id']}"
	);
	$update     = $ayarkaydet->execute(
		array(
			'resim' => $refimgyol2,
		)
	);

	if ( $update )
	{

		$resimsilunlink = $_POST[ 'eski_yol' ];
		unlink(SITE_ROOT . "/$resimsilunlink" );

		Header( "Location:../projeler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../projeler.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'slaytduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	if ( $_FILES[ 'slayt_resim' ][ "size" ] > 0 )
	{

		$uploads_dir = '../assets/img/slayt';
		@$tmp_name = $_FILES[ 'slayt_resim' ][ "tmp_name" ];
		$benzersizsayi4 = rand( 20000, 32000 );
		$uzanti = '.jpg';
		$refimgyol2     = substr( $uploads_dir, 3 ) . "/" . $benzersizsayi4 . $uzanti;

		@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizsayi4$uzanti" );

		$ayarkaydet = $db->prepare(
			"UPDATE slayt SET
			slayt_sira=:sira,
			slayt_baslik=:baslik,
			slayt_butonlink=:butonlink,
			slayt_renk=:renk,
			slayt_butonad=:butonad,
			slayt_aciklama=:aciklama,
			slayt_resim=:resim
			WHERE slayt_id={$_POST['slayt_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'sira'     => $_POST[ 'slayt_sira' ],
				'baslik'     => $_POST[ 'slayt_baslik' ],
				'butonlink'     => $_POST[ 'slayt_butonlink' ],
				'renk'     => $_POST[ 'slayt_renk' ],
				'butonad'     => $_POST[ 'slayt_butonad' ],
				'aciklama' => $_POST[ 'slayt_aciklama' ],
				'resim' => $refimgyol2
			)
		);

		if ( $update )
		{
			$resimsilunlink = $_POST[ 'eski_yol' ];
			unlink( "../$resimsilunlink" );

			Header( "Location:../slayt.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../slayt.php?status=no" );
			exit;
		}
	} else {
		$ayarkaydet = $db->prepare(
			"UPDATE slayt SET
			slayt_sira=:sira,
			slayt_baslik=:baslik,
			slayt_butonlink=:butonlink,
			slayt_renk=:renk,
			slayt_butonad=:butonad,
			slayt_aciklama=:aciklama
			WHERE slayt_id={$_POST['slayt_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'sira'     => $_POST[ 'slayt_sira' ],
				'baslik'     => $_POST[ 'slayt_baslik' ],
				'butonlink'     => $_POST[ 'slayt_butonlink' ],
				'renk'     => $_POST[ 'slayt_renk' ],
				'butonad'     => $_POST[ 'slayt_butonad' ],
				'aciklama' => $_POST[ 'slayt_aciklama' ]
			)
		);

		if ( $update )
		{


			Header( "Location:../slayt.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../slayt.php?status=no" );
			exit;
		}
	}
}


if ( isset( $_POST[ 'hizmetduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	if ( $_FILES[ 'hizmet_resim' ][ "size" ] > 0 )
	{
		$uploads_dir = '../assets/img/hizmetler';
		@$tmp_name = $_FILES[ 'hizmet_resim' ][ "tmp_name" ];
		@$name = $_FILES[ 'hizmet_resim' ][ "name" ];
		$benzersizsayi1 = rand( 20000, 32000 );
		$benzersizsayi2 = rand( 20000, 32000 );
		$uzanti='.jpg';
		$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
		$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
		@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

		$ayarkaydet = $db->prepare(
			"UPDATE hizmetler SET
			hizmet_baslik=:baslik,
			hizmet_icerik=:icerik,
			hizmet_title=:title,
			hizmet_descr=:descr,
			hizmet_keyword=:keyword,
			hizmet_vitrin=:vitrin,
			hizmet_resim=:resim,
			hizmet_icon=:icon
			WHERE hizmet_id={$_POST['hizmet_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'baslik'     => $_POST[ 'hizmet_baslik' ],
				'icerik'     => $_POST[ 'hizmet_icerik' ],
				'title'     => $_POST[ 'hizmet_title' ],
				'descr'     => $_POST[ 'hizmet_descr' ],
				'keyword'     => $_POST[ 'hizmet_keyword' ],
				'vitrin'     => $_POST[ 'hizmet_vitrin' ],
				'resim'     => $refimgyol,
				'icon' => $_POST[ 'hizmet_icon' ]
			)
		);

		if ( $update )
		{
			$resimsilunlink = $_POST[ 'eski_yol' ];
			unlink( "../$resimsilunlink" );

			Header( "Location:../hizmetler.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../hizmetler.php?status=no" );
			exit;
		}
	} else {

		$ayarkaydet = $db->prepare(
			"UPDATE hizmetler SET
			hizmet_baslik=:baslik,
			hizmet_icerik=:icerik,
			hizmet_title=:title,
			hizmet_descr=:descr,
			hizmet_keyword=:keyword,
			hizmet_vitrin=:vitrin,
			hizmet_icon=:icon
			WHERE hizmet_id={$_POST['hizmet_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'baslik'     => $_POST[ 'hizmet_baslik' ],
				'icerik'     => $_POST[ 'hizmet_icerik' ],
				'title'     => $_POST[ 'hizmet_title' ],
				'descr'     => $_POST[ 'hizmet_descr' ],
				'keyword'     => $_POST[ 'hizmet_keyword' ],
				'vitrin'     => $_POST[ 'hizmet_vitrin' ],
				'icon' => $_POST[ 'hizmet_icon' ]
			)
		);

		if ( $update )
		{


			Header( "Location:../hizmetler.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../hizmetler.php?status=no" );
			exit;
		}
	}
}
if ( isset( $_POST[ 'markaduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	if ( $_FILES[ 'hizmet_resim' ][ "size" ] > 0 )
	{
		$uploads_dir = '../assets/img/hizmetler';
		@$tmp_name = $_FILES[ 'hizmet_resim' ][ "tmp_name" ];
		@$name = $_FILES[ 'hizmet_resim' ][ "name" ];
		$benzersizsayi1 = rand( 20000, 32000 );
		$benzersizsayi2 = rand( 20000, 32000 );
		$uzanti='.jpg';
		$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
		$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
		@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

		$ayarkaydet = $db->prepare(
			"UPDATE markalar SET
			hizmet_baslik=:baslik,
			hizmet_icerik=:icerik,
			hizmet_title=:title,
			hizmet_descr=:descr,
			hizmet_keyword=:keyword,
			hizmet_resim=:resim,
			hizmet_icon=:icon
			WHERE hizmet_id={$_POST['hizmet_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'baslik'     => $_POST[ 'hizmet_baslik' ],
				'icerik'     => $_POST[ 'hizmet_icerik' ],
				'title'     => $_POST[ 'hizmet_title' ],
				'descr'     => $_POST[ 'hizmet_descr' ],
				'keyword'     => $_POST[ 'hizmet_keyword' ],
				'resim'     => $refimgyol,
				'icon' => $_POST[ 'hizmet_icon' ]
			)
		);

		if ( $update )
		{
			$resimsilunlink = $_POST[ 'eski_yol' ];
			unlink( "../$resimsilunlink" );

			Header( "Location:../markalar.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../markalar.php?status=no" );
			exit;
		}
	} else {

		$ayarkaydet = $db->prepare(
			"UPDATE markalar SET
			hizmet_baslik=:baslik,
			hizmet_icerik=:icerik,
			hizmet_title=:title,
			hizmet_descr=:descr,
			hizmet_keyword=:keyword,
			hizmet_vitrin=:vitrin,
			hizmet_icon=:icon
			WHERE hizmet_id={$_POST['hizmet_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'baslik'     => $_POST[ 'hizmet_baslik' ],
				'icerik'     => $_POST[ 'hizmet_icerik' ],
				'title'     => $_POST[ 'hizmet_title' ],
				'descr'     => $_POST[ 'hizmet_descr' ],
				'keyword'     => $_POST[ 'hizmet_keyword' ],
				'vitrin'     => $_POST[ 'hizmet_vitrin' ],
				'icon' => $_POST[ 'hizmet_icon' ]
			)
		);

		if ( $update )
		{


			Header( "Location:../markalar.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../markalar.php?status=no" );
			exit;
		}
	}
}

if ( isset( $_POST[ 'yorumduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$ayarkaydet = $db->prepare(
		"UPDATE yorumlar SET
		yorum_isim=:isim,
		yorum_icerik=:icerik,
		yorum_mail=:mail,
		yorum_onay=:onay
		WHERE yorum_id={$_POST['yorum_id']}"
	);
	$update     = $ayarkaydet->execute(
		array(
			'isim'     => $_POST[ 'yorum_isim' ],
			'icerik'     => $_POST[ 'yorum_icerik' ],
			'mail' => $_POST[ 'yorum_mail' ],
			'onay' => $_POST[ 'yorum_onay' ]

		)
	);

	if ( $update )
	{

		Header( "Location:../yorumlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../yorumlar.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'urunduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}

	panel_ensure_urunler_columns($db);

	$birim_goster = (isset($_POST['urun_fiyat_birim_goster']) && (string)$_POST['urun_fiyat_birim_goster'] === '1') ? 1 : 0;
	$birim_metin = panel_mb_substr_safe(trim((string)($_POST['urun_fiyat_birim_metin'] ?? '')), 0, 64);
	$birim_renk_raw = trim((string)($_POST['urun_fiyat_birim_renk'] ?? ''));
	$birim_renk = preg_match('/^#[0-9A-Fa-f]{3,8}$/', $birim_renk_raw) ? $birim_renk_raw : '';
	$birim_olcek = floatval(str_replace(',', '.', (string)($_POST['urun_fiyat_birim_olcek'] ?? '1')));
	if ($birim_olcek < 0.5) { $birim_olcek = 0.5; }
	if ($birim_olcek > 2.5) { $birim_olcek = 2.5; }

	$folder_name = 'assets/img/urunler';
	$uploads_dir = dirname(__DIR__) . '/' . $folder_name;
	
	// Klasör yoksa oluştur
	if (!is_dir($uploads_dir)) {
		@mkdir($uploads_dir, 0777, true);
	}

	if(isset($_FILES['urun_resim']) && $_FILES['urun_resim']["name"]!='' && $_FILES['urun_resim']["error"] == 0)
	{
		@$tmp_name = $_FILES['urun_resim']["tmp_name"];
		@$name = $_FILES['urun_resim']["name"];
		$benzersizsayi1=rand(20000,32000);
		$uzanti = '.webp';
		$benzersizad=$benzersizsayi1.$uzanti;
		$refimgyol=$folder_name."/".$benzersizad;

		if (!convertToWebp($tmp_name, "$uploads_dir/$benzersizad", 85)) {
            @move_uploaded_file($tmp_name, "$uploads_dir/$benzersizad.jpg");
            $benzersizad = $benzersizsayi1.".jpg";
            $refimgyol = $folder_name."/".$benzersizad;
        }

		$ayarkaydet = $db->prepare(
			"UPDATE urunler SET
			urun_resim=:urun_resim
			WHERE urun_id={$_POST['urun_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'urun_resim'     => $refimgyol
			)
		);
	}


	if(isset($_FILES['urun_resimsec']) && $_FILES['urun_resimsec']["name"]!='' && $_FILES['urun_resimsec']["error"] == 0)
	{
		@$tmp_name1 = $_FILES['urun_resimsec']["tmp_name"];
		@$name1 = $_FILES['urun_resimsec']["name"];
		$benzersizsayi2=rand(20000,32000);
		$uzanti = '.webp';
		$benzersizad1=$benzersizsayi2.$uzanti;
		$refimgyol1=$folder_name."/".$benzersizad1;

		if (!convertToWebp($tmp_name1, "$uploads_dir/$benzersizad1", 85)) {
            @move_uploaded_file($tmp_name1, "$uploads_dir/$benzersizad1.jpg");
            $benzersizad1 = $benzersizsayi2.".jpg";
            $refimgyol1 = $folder_name."/".$benzersizad1;
        }

		$ayarkaydet = $db->prepare(
			"UPDATE urunler SET
			urun_resimsec=:urun_resimsec
			WHERE urun_id={$_POST['urun_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'urun_resimsec'     => $refimgyol1
			)
		);
	}

	$urun_id_edit = (int) $_POST['urun_id'];
	$sip_kart_upload = isset($_FILES['urun_siparis_kart']) && $_FILES['urun_siparis_kart']['name'] !== '' && (int) $_FILES['urun_siparis_kart']['error'] === 0;
	$sip_kart_clear = !empty($_POST['sil_urun_siparis_kart']) && (string) $_POST['sil_urun_siparis_kart'] === '1' && !$sip_kart_upload;
	if ($sip_kart_clear) {
		try {
			$db->exec("ALTER TABLE urunler ADD COLUMN urun_siparis_kart VARCHAR(512) NOT NULL DEFAULT ''");
		} catch (Throwable $e) {
		}
		$old_sip = isset($_POST['eskiyol_urun_siparis_kart']) ? trim((string) $_POST['eskiyol_urun_siparis_kart']) : '';
		if ($old_sip !== '' && strpos($old_sip, '..') === false) {
			$rel = ltrim(str_replace('\\', '/', $old_sip), '/');
			$full = dirname(__DIR__) . '/' . $rel;
			if (is_file($full)) {
				@unlink($full);
			}
		}
		$db->prepare("UPDATE urunler SET urun_siparis_kart='' WHERE urun_id=:id")->execute(array('id' => $urun_id_edit));
	}
	if ($sip_kart_upload) {
		try {
			$db->exec("ALTER TABLE urunler ADD COLUMN urun_siparis_kart VARCHAR(512) NOT NULL DEFAULT ''");
		} catch (Throwable $e) {
		}
		@$tmp_sk = $_FILES['urun_siparis_kart']['tmp_name'];
		$benzersizsayi_sk = rand(62000, 64999);
		$webp_name = $benzersizsayi_sk . '.webp';
		$ref_sip_kart = $folder_name . '/' . $webp_name;
		$saved_sip = false;
		if (convertToWebp($tmp_sk, "$uploads_dir/$webp_name", 85)) {
			$saved_sip = true;
		} else {
			$jpg_name = $benzersizsayi_sk . '.jpg';
			if (@move_uploaded_file($tmp_sk, "$uploads_dir/$jpg_name")) {
				$ref_sip_kart = $folder_name . '/' . $jpg_name;
				$saved_sip = true;
			}
		}
		if ($saved_sip) {
			$old_sip = isset($_POST['eskiyol_urun_siparis_kart']) ? trim((string) $_POST['eskiyol_urun_siparis_kart']) : '';
			if ($old_sip !== '' && $old_sip !== $ref_sip_kart && strpos($old_sip, '..') === false) {
				$rel = ltrim(str_replace('\\', '/', $old_sip), '/');
				$full = dirname(__DIR__) . '/' . $rel;
				if (is_file($full)) {
					@unlink($full);
				}
			}
			$db->prepare('UPDATE urunler SET urun_siparis_kart=:p WHERE urun_id=:id')->execute(array('p' => $ref_sip_kart, 'id' => $urun_id_edit));
		}
	}

	$ayarkaydet = $db->prepare(
		"UPDATE urunler SET
		urun_baslik=:baslik,
		urun_alt_baslik=:alt_baslik,
		urun_etiket=:etiket,
		urun_etiket_bg=:etiket_bg,
		urun_etiket_color=:etiket_color,
		urun_seo_baslik=:seo_baslik,
		urun_seo_aciklama=:seo_aciklama,
		urun_seo_anahtar=:seo_anahtar,
		urun_fiyat=:fiyat,
		urun_eski_fiyat=:eski_fiyat,
		urun_fiyat_birim_metin=:birim_metin,
		urun_fiyat_birim_renk=:birim_renk,
		urun_fiyat_birim_goster=:birim_goster,
		urun_fiyat_birim_olcek=:birim_olcek,
		urun_siralama=:siralama,
		urun_slug=:slug
		WHERE urun_id={$_POST['urun_id']}"
	);
	$slug_out = seoFriendlySlug(isset($_POST['urun_baslik']) ? $_POST['urun_baslik'] : '');
	if ($slug_out === '') {
		$slug_out = 'urun-' . (int) $_POST['urun_id'];
	}
	$update     = $ayarkaydet->execute(
		array(
			'baslik'     => $_POST[ 'urun_baslik' ],
			'alt_baslik' => $_POST[ 'urun_alt_baslik' ],
			'etiket'     => $_POST[ 'urun_etiket' ],
			'etiket_bg'  => $_POST[ 'urun_etiket_bg' ],
			'etiket_color' => $_POST[ 'urun_etiket_color' ],
			'seo_baslik' => $_POST[ 'urun_seo_baslik' ],
			'seo_aciklama' => $_POST[ 'urun_seo_aciklama' ],
			'seo_anahtar' => $_POST[ 'urun_seo_anahtar' ],
			'fiyat' => $_POST[ 'urun_fiyat' ],
			'eski_fiyat' => $_POST[ 'urun_eski_fiyat' ],
			'birim_metin' => $birim_metin,
			'birim_renk' => $birim_renk,
			'birim_goster' => $birim_goster,
			'birim_olcek' => $birim_olcek,
			'siralama' => $_POST[ 'urun_siralama' ],
			'slug' => $slug_out
		)
	);

	if ( $update )
	{
		Header( "Location:../urun-duzenle.php?urun_id={$_POST['urun_id']}&status=ok" );
		exit;
	}
	else
	{
		Header( "Location:../urun-duzenle.php?urun_id={$_POST['urun_id']}&status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'projeduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	if ( $_FILES[ 'proje_resim' ][ "size" ] > 0 )
	{
		$uploads_dir = '../assets/img/projeler';
		@$tmp_name = $_FILES[ 'proje_resim' ][ "tmp_name" ];
		@$name = $_FILES[ 'proje_resim' ][ "name" ];
		$benzersizsayi1 = rand( 20000, 32000 );
		$benzersizsayi2 = rand( 20000, 32000 );
		$uzanti='.jpg';
		$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
		$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
		@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );


		$ayarkaydet = $db->prepare(
			"UPDATE projeler SET
			proje_baslik=:baslik,
			proje_icerik=:icerik,
			proje_resim=:resim,
			proje_title=:title,
			proje_descr=:descr,
			proje_keyword=:keyword
			WHERE proje_id={$_POST['proje_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'baslik'     => $_POST[ 'proje_baslik' ],
				'icerik'     => $_POST[ 'proje_icerik' ],
				'resim'     => $refimgyol,
				'title'     => $_POST[ 'proje_title' ],
				'descr'     => $_POST[ 'proje_descr' ],
				'keyword'     => $_POST[ 'proje_keyword' ]
			)
		);

		if ( $update )
		{
			$resimsilunlink = $_POST[ 'eski_yol' ];
			unlink( "../$resimsilunlink" );

			Header( "Location:../projeler.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../projeler.php?status=no" );
			exit;
		}
	}
	else {
		$ayarkaydet = $db->prepare(
			"UPDATE projeler SET
			proje_baslik=:baslik,
			proje_icerik=:icerik,
			proje_vitrin=:vitrin,
			proje_title=:title,
			proje_descr=:descr,
			proje_keyword=:keyword
			WHERE proje_id={$_POST['proje_id']}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'baslik'     => $_POST[ 'proje_baslik' ],
				'icerik'     => $_POST[ 'proje_icerik' ],
				'vitrin'     => $_POST[ 'proje_vitrin' ],
				'title'     => $_POST[ 'proje_title' ],
				'descr'     => $_POST[ 'proje_descr' ],
				'keyword'     => $_POST[ 'proje_keyword' ]
			)
		);

		if ( $update )
		{


			Header( "Location:../projeler.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../projeler.php?status=no" );
			exit;
		}
	}
}

if ( isset( $_POST[ 'hosduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$ayarkaydet = $db->prepare(
		"UPDATE hosgeldiniz SET
		hosgeldiniz_baslik=:baslik,
		hosgeldiniz_aciklama=:icerik
		WHERE hos_id={$_POST['hosgeldiniz_id']}"
	);
	$update     = $ayarkaydet->execute(
		array(
			'baslik'     => $_POST[ 'hosgeldiniz_baslik' ],
			'icerik'     => $_POST[ 'hosgeldiniz_aciklama' ]
		)
	);

	if ( $update )
	{


		Header( "Location:../hosgeldiniz.php?durum=ok" );
		exit;
	}
	else
	{

		Header( "Location:../hosgeldiniz.php?durum=no" );
		exit;
	}
}
if ( isset($_GET[ 'uruncogalt' ]) && $_GET[ 'uruncogalt' ] == "ok" )
{
	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




    // Fetch original product
    $urunsor = $db->prepare("SELECT * FROM urunler WHERE urun_id=:id");
    $urunsor->execute(['id' => $_GET['urun_id']]);
    $uruncek = $urunsor->fetch(PDO::FETCH_ASSOC);

    if ($uruncek) {
        panel_ensure_urunler_columns($db);

        $yenibaslik = $uruncek['urun_baslik'] . " + 1";
        
        $cogalt = $db->prepare("INSERT INTO urunler SET 
            urun_baslik=:baslik,
            urun_alt_baslik=:alt_baslik,
            urun_etiket=:etiket,
            urun_etiket_bg=:etiket_bg,
            urun_etiket_color=:etiket_color,
            urun_seo_baslik=:seo_baslik,
            urun_seo_aciklama=:seo_aciklama,
            urun_seo_anahtar=:seo_anahtar,
            urun_resim=:resim,
            urun_resimsec=:resimsec,
            urun_siparis_kart=:siparis_kart,
            urun_fiyat=:fiyat,
            urun_eski_fiyat=:eski_fiyat,
            urun_fiyat_birim_metin=:birim_metin,
            urun_fiyat_birim_renk=:birim_renk,
            urun_fiyat_birim_goster=:birim_goster,
            urun_fiyat_birim_olcek=:birim_olcek,
            urun_siralama=:siralama,
            urun_slug=:slug,
            secenekler=:secenekler
        ");
        
        $kontrol = $cogalt->execute([
            'baslik'       => $yenibaslik,
            'alt_baslik'   => $uruncek['urun_alt_baslik'],
            'etiket'       => $uruncek['urun_etiket'],
            'etiket_bg'    => $uruncek['urun_etiket_bg'],
            'etiket_color' => $uruncek['urun_etiket_color'],
            'seo_baslik'   => $uruncek['urun_seo_baslik'],
            'seo_aciklama' => $uruncek['urun_seo_aciklama'],
            'seo_anahtar'  => $uruncek['urun_seo_anahtar'],
            'resim'        => $uruncek['urun_resim'],
            'resimsec'     => $uruncek['urun_resimsec'],
            'siparis_kart' => isset($uruncek['urun_siparis_kart']) ? $uruncek['urun_siparis_kart'] : '',
            'fiyat'        => $uruncek['urun_fiyat'],
            'eski_fiyat'   => $uruncek['urun_eski_fiyat'],
            'birim_metin'  => $uruncek['urun_fiyat_birim_metin'] ?? '',
            'birim_renk'   => $uruncek['urun_fiyat_birim_renk'] ?? '',
            'birim_goster' => isset($uruncek['urun_fiyat_birim_goster']) ? (int)$uruncek['urun_fiyat_birim_goster'] : 0,
            'birim_olcek'  => isset($uruncek['urun_fiyat_birim_olcek']) ? $uruncek['urun_fiyat_birim_olcek'] : 1,
            'siralama'     => $uruncek['urun_siralama'],
            'slug'         => seolink(strip_tags($yenibaslik)), // Use global seolink function for consistency
            'secenekler'   => $uruncek['secenekler']
        ]);

        if ($kontrol) {
            Header("Location:../urunler.php?status=ok");
            exit;
        }
    }
    Header("Location:../urunler.php?status=no");
    exit;
}

if ( isset($_GET[ 'urunsil' ]) && $_GET[ 'urunsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}

	$uid = (int) $_GET['urun_id'];
	$st  = $db->prepare( 'SELECT urun_resim, urun_resimsec, urun_siparis_kart FROM urunler WHERE urun_id=:id LIMIT 1' );
	$st->execute( array( 'id' => $uid ) );
	$row = $st->fetch( PDO::FETCH_ASSOC );

	if ( ! $row ) {
		Header( "Location:../urunler.php?status=no" );
		exit;
	}

	$sil     = $db->prepare( "DELETE from urunler where urun_id=:urun_id" );
	$kontrol = $sil->execute( array( 'urun_id' => $uid ) );

	if ( $kontrol )
	{
		$base = dirname( __DIR__ );
		foreach ( array( 'urun_resim', 'urun_resimsec', 'urun_siparis_kart' ) as $col ) {
			if ( empty( $row[ $col ] ) || strpos( (string) $row[ $col ], '..' ) !== false ) {
				continue;
			}
			$rel = str_replace( '\\', '/', $row[ $col ] );
			$rel = ltrim( $rel, '/' );
			$full = $base . '/' . $rel;
			if ( is_file( $full ) ) {
				@unlink( $full );
			}
		}

		Header( "Location:../urunler.php?status=ok" );
		exit;
	}

	Header( "Location:../urunler.php?status=no" );
	exit;
}

if ( isset( $_POST[ 'urun_toplu_sil' ] ) )
{
	if ( empty( $_SESSION[ 'kullanici_adi' ] ) ) {
		header( "Location: ../index.php?status=no" );
		exit();
	}

	$ids = isset( $_POST[ 'urun_id' ] ) ? $_POST[ 'urun_id' ] : array();
	if ( ! is_array( $ids ) ) {
		$ids = array( $ids );
	}
	$silinen = 0;
	$base     = dirname( __DIR__ );

	foreach ( $ids as $raw_id ) {
		$id = (int) $raw_id;
		if ( $id <= 0 ) {
			continue;
		}

		$st = $db->prepare( "SELECT urun_resim, urun_resimsec, urun_siparis_kart FROM urunler WHERE urun_id=:id LIMIT 1" );
		$st->execute( array( 'id' => $id ) );
		$row = $st->fetch( PDO::FETCH_ASSOC );
		if ( ! $row ) {
			continue;
		}

		$del = $db->prepare( "DELETE FROM urunler WHERE urun_id=:id" );
		if ( ! $del->execute( array( 'id' => $id ) ) ) {
			continue;
		}
		$silinen++;

		foreach ( array( 'urun_resim', 'urun_resimsec', 'urun_siparis_kart' ) as $col ) {
			if ( empty( $row[ $col ] ) || strpos( (string) $row[ $col ], '..' ) !== false ) {
				continue;
			}
			$rel = str_replace( '\\', '/', $row[ $col ] );
			$rel = ltrim( $rel, '/' );
			$full = $base . '/' . $rel;
			if ( is_file( $full ) ) {
				@unlink( $full );
			}
		}
	}

	Header( "Location:../urunler.php?toplu_sil=" . ( $silinen > 0 ? 'ok' : 'bos' ) );
	exit;
}

if ( isset($_GET[ 'urunresimdetaysil' ]) && $_GET[ 'urunresimdetaysil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from resim where resim_id=:resim_id" );
	$kontrol = $sil->execute(
		array(
			'resim_id' => $_GET[ 'resim_id' ]
		)
	);

	if ( $kontrol )
	{
		$resimsilunlink=$_GET['resim_link'];
		unlink("../$resimsilunlink");

		Header( "Location:../urunler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../urunler.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'referanssil' ]) && $_GET[ 'referanssil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from referanslar where referans_id=:referans_id" );
	$kontrol = $sil->execute(
		array(
			'referans_id' => $_GET[ 'referans_id' ]
		)
	);

	if ( $kontrol )
	{
		$resimsilunlink=$_GET['referans_resim1'];
		unlink("../$resimsilunlink");

		Header( "Location:../referanslar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../referanslar.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'slaytsil' ]) && $_GET[ 'slaytsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from slayt where slayt_id=:slayt_id" );
	$kontrol = $sil->execute(
		array(
			'slayt_id' => $_GET[ 'slayt_id' ]
		)
	);

	if ( $kontrol )
	{
		$resimsilunlink=$_GET['slayt_resim'];
		unlink("../$resimsilunlink");

		Header( "Location:../slayt.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../slayt.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'videosil' ]) && $_GET[ 'videosil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from videogaleri where video_id=:video_id" );
	$kontrol = $sil->execute(
		array(
			'video_id' => $_GET[ 'video_id' ]
		)
	);

	if ( $kontrol )
	{
		$resimsilunlink=$_GET['video_resim'];
		unlink("../$resimsilunlink");

		Header( "Location:../videolar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../videolar.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'resimsil' ]) && $_GET[ 'resimsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from resimgaleri where resim_id=:resim_id" );
	$kontrol = $sil->execute(
		array(
			'resim_id' => $_GET[ 'resim_id' ]
		)
	);

	if ( $kontrol )
	{
		$resimsilunlink=$_GET['eski_yol'];
		unlink("../$resimsilunlink");

		Header( "Location:../gorseller.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../gorseller.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'kargosil' ]) && $_GET[ 'kargosil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from kargo where yorum_id=:yorum_id" );
	$kontrol = $sil->execute(
		array(
			'yorum_id' => $_GET[ 'yorum_id' ]
		)
	);

	if ( $kontrol )
	{
		$resimsilunlink=$_GET['yorum_resim'];
		unlink("../$resimsilunlink");

		Header( "Location:../kargolar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../kargolar.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'yorumsil' ]) && $_GET[ 'yorumsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from yorumlar where yorum_id=:yorum_id" );
	$kontrol = $sil->execute(
		array(
			'yorum_id' => $_GET[ 'yorum_id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../yorumlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../yorumlar.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'markasil' ]) && $_GET[ 'markasil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from markalar where hizmet_id=:hizmet_id" );
	$kontrol = $sil->execute(
		array(
			'hizmet_id' => $_GET[ 'hizmet_id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../markalar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../markalar.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'hizmetsil' ]) && $_GET[ 'hizmetsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from hizmetler where hizmet_id=:hizmet_id" );
	$kontrol = $sil->execute(
		array(
			'hizmet_id' => $_GET[ 'hizmet_id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../hizmetler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../hizmetler.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'projesil' ]) && $_GET[ 'projesil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from projeler where proje_id=:proje_id" );
	$kontrol = $sil->execute(
		array(
			'proje_id' => $_GET[ 'proje_id' ]
		)
	);

	if ( $kontrol )
	{
		$resimsilunlink=$_GET['proje_resim'];
		unlink(SITE_ROOT . "/$resimsilunlink");

		Header( "Location:../projeler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../projeler.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'sosyalsil' ]) && $_GET[ 'sosyalsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from sosyal where sosyal_id=:sosyal_id" );
	$kontrol = $sil->execute(
		array(
			'sosyal_id' => $_GET[ 'sosyal_id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../sosyal-medya.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../sosyal-medya.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'mesajsil' ]) && $_GET[ 'mesajsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from mesajlar where mesaj_id=:mesaj_id" );
	$kontrol = $sil->execute(
		array(
			'mesaj_id' => $_GET[ 'mesaj_id' ]
		)
	);

	if ( $kontrol )
	{


		header("Location: ../index.php?status=ok" );
		exit;
	}
	else
	{

		header("Location: ../index.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'iadesil' ]) && $_GET[ 'iadesil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from iade where iade_id=:iade_id" );
	$kontrol = $sil->execute(
		array(
			'iade_id' => $_GET[ 'iade_id' ]
		)
	);

	if ( $kontrol )
	{
		header("Location: ../index.php?status=ok" );
		exit;
	}
	else
	{
		header("Location: ../index.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'kategorisil' ]) && $_GET[ 'kategorisil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from kategoriler where kategori_sira=:kategori_sira" );
	$kontrol = $sil->execute(
		array(
			'kategori_sira' => $_POST['kategori_sira']
		)
	);

	if ( $kontrol )
	{


		Header( "Location:../kategoriler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../kategoriler.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'flinksil' ]) && $_GET[ 'flinksil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from flink where flink_id=:flink_id" );
	$kontrol = $sil->execute(
		array(
			'flink_id' => $_GET[ 'flink_id' ]
		)
	);

	if ( $kontrol )
	{


		Header( "Location:../alt-link.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../alt-link.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'fmenusil' ]) && $_GET[ 'fmenusil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from fmenu where fmenu_id=:fmenu_id" );
	$kontrol = $sil->execute(
		array(
			'fmenu_id' => $_GET[ 'fmenu_id' ]
		)
	);

	if ( $kontrol )
	{


		Header( "Location:../alt-menu.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../alt-menu.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'omenusil' ]) && $_GET[ 'omenusil' ] == "ok" )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from omenu where omenu_id=:omenu_id" );
	$kontrol = $sil->execute(
		array(
			'omenu_id' => $_GET[ 'omenu_id' ]
		)
	);




	if ( $kontrol )
	{
		$eski=$_GET[ 'omenu_ust' ];
		$ayarkaydet = $db->prepare(
			"UPDATE omenu SET
			omenu_durum=:durum
			WHERE omenu_id={$eski}"
		);
		$update     = $ayarkaydet->execute(
			array(
				'durum' => '0'
			)
		);

		Header( "Location:../menu.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../menu.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'ssssil' ]) && $_GET[ 'ssssil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from sss where sss_id=:sss_id" );
	$kontrol = $sil->execute(
		array(
			'sss_id' => $_GET[ 'sss_id' ]
		)
	);

	if ( $kontrol )
	{


		Header( "Location:../sss.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../sss.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'hizmetekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$uploads_dir = '../assets/img/hizmetler';
	@$tmp_name = $_FILES[ 'hizmet_resim' ][ "tmp_name" ];
	$benzersizsayi1 = rand( 20000, 32000 );
	$benzersizsayi2 = rand( 20000, 32000 );
	$uzanti = '.jpg';
	$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
	$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

	$kaydet = $db->prepare(
		"INSERT INTO hizmetler SET
		hizmet_baslik=:baslik,
		hizmet_icerik=:icerik,
		hizmet_title=:title,
		hizmet_descr=:descr,
		hizmet_keyword=:keyword,
		hizmet_vitrin=:vitrin,
		hizmet_resim=:resim");
	$insert = $kaydet->execute(
		array(
			'baslik'     => $_POST[ 'hizmet_baslik' ],
			'icerik'     => $_POST[ 'hizmet_icerik' ],
			'title'     => $_POST[ 'hizmet_title' ],
			'descr'     => $_POST[ 'hizmet_descr' ],
			'keyword'     => $_POST[ 'hizmet_keyword' ],
			'vitrin'     => $_POST[ 'hizmet_vitrin' ],
			'resim'     => $refimgyol
		));

	if ( $insert )
	{

		Header( "Location:../hizmetler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../hizmetler.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'markaekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$uploads_dir = '../assets/img/hizmetler';
	@$tmp_name = $_FILES[ 'hizmet_resim' ][ "tmp_name" ];
	$benzersizsayi1 = rand( 20000, 32000 );
	$benzersizsayi2 = rand( 20000, 32000 );
	$uzanti = '.jpg';
	$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
	$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

	$kaydet = $db->prepare(
		"INSERT INTO markalar SET
		hizmet_baslik=:baslik,
		hizmet_icerik=:icerik,
		hizmet_title=:title,
		hizmet_descr=:descr,
		hizmet_keyword=:keyword,
		hizmet_vitrin=:vitrin,
		hizmet_resim=:resim");
	$insert = $kaydet->execute(
		array(
			'baslik'     => $_POST[ 'hizmet_baslik' ],
			'icerik'     => $_POST[ 'hizmet_icerik' ],
			'title'     => $_POST[ 'hizmet_title' ],
			'descr'     => $_POST[ 'hizmet_descr' ],
			'keyword'     => $_POST[ 'hizmet_keyword' ],
			'vitrin'     => $_POST[ 'hizmet_vitrin' ],
			'resim'     => $refimgyol
		));

	if ( $insert )
	{

		Header( "Location:../markalar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../markalar.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'kargoekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$uploads_dir = '../assets/img/yorumlar';
	@$tmp_name = $_FILES[ 'yorum_resim' ][ "tmp_name" ];
	$benzersizsayi1 = rand( 20000, 32000 );
	$benzersizsayi2 = rand( 20000, 32000 );
	$benzersizsayi3 = rand( 20000, 32000 );
	$benzersizsayi4 = rand( 20000, 32000 );
	$uzanti = '.jpg';
	$benzersizad    = $benzersizsayi1 . $benzersizsayi2 . $benzersizsayi3 . $benzersizsayi4;
	$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

	$kaydet = $db->prepare(
		"INSERT INTO kargo SET
		yorum_isim=:isim,
		yorum_link=:link,
		yorum_resim=:resim");
	$insert = $kaydet->execute(
		array(
			'isim'     => $_POST[ 'yorum_isim' ],
			'link' => $_POST[ 'yorum_link' ],
			'resim'    => $refimgyol
		));

	if ( $insert )
	{

		Header( "Location:../kargolar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../kargolar.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'yorumekleuye' ] ) )
{

	$yorum_isim = htmlspecialchars(trim($_POST[ 'yorum_isim' ]));
	$yorum_mail = htmlspecialchars(trim($_POST[ 'yorum_mail' ]));
	$yorum_icerik = htmlspecialchars(trim($_POST[ 'yorum_icerik' ]));

	$kaydet = $db->prepare(
		"INSERT INTO yorumlar SET
		yorum_isim=:isim,
		yorum_onay=:onay,
		yorum_mail=:mail,
		yorum_icerik=:icerik
		");
	$insert = $kaydet->execute(
		array(
			'isim'     => $yorum_isim,
			'onay' => '0',
			'mail' => $yorum_mail,
			'icerik'     => $yorum_icerik
		));

	if ( $insert )
	{

		Header( "Location:../../phpmail/yorum.php?iletisimform=ok" );
		exit;
	}
	else
	{

		header("Location: ../index.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'yorumekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$kaydet = $db->prepare(
		"INSERT INTO yorumlar SET
		yorum_isim=:isim,
		yorum_onay=:onay,
		yorum_mail=:mail,
		yorum_icerik=:icerik
		");
	$insert = $kaydet->execute(
		array(
			'isim'     => $_POST[ 'yorum_isim' ],
			'onay' => $_POST[ 'yorum_onay' ],
			'mail' => $_POST[ 'yorum_mail' ],
			'icerik'     => $_POST[ 'yorum_icerik' ]
		));

	if ( $insert )
	{

		Header( "Location:../yorumlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../yorumlar.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'projeekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$uploads_dir = '../assets/img/projeler';
	@$tmp_name = $_FILES[ 'proje_resim' ][ "tmp_name" ];
	$benzersizsayi1 = rand( 20000, 32000 );
	$benzersizsayi2 = rand( 20000, 32000 );
	$uzanti='.jpg';
	$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
	$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

	$kaydet = $db->prepare(
		"INSERT INTO projeler SET
		proje_baslik=:baslik,
		proje_icerik=:icerik,
		proje_resim=:resim,
		proje_title=:title,
		proje_descr=:descr,
		proje_keyword=:keyword");
	$insert = $kaydet->execute(
		array(
			'baslik'     => $_POST[ 'proje_baslik' ],
			'icerik'     => $_POST[ 'proje_icerik' ],
			'resim'     => $refimgyol,
			'title'     => $_POST[ 'proje_title' ],
			'descr'     => $_POST[ 'proje_descr' ],
			'keyword'     => $_POST[ 'proje_keyword' ]
		));

	if ( $insert )
	{

		Header( "Location:../projeler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../projeler.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'slaytekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$uploads_dir = '../assets/img/slayt';
	$tmp_name = $_FILES[ 'slayt_resim' ][ "tmp_name" ];
	$benzersizsayi1 = rand( 20000, 32000 );
	$benzersizsayi2 = rand( 20000, 32000 );
	$uzanti = '.jpg';
	$benzersizad    = $benzersizsayi1 . $benzersizsayi2 ;
	$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
	move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

	$kaydet = $db->prepare(
		"INSERT INTO slayt SET
		slayt_baslik=:baslik,
		slayt_aciklama=:aciklama,
		slayt_renk=:renk,
		slayt_sira=:sira,
		slayt_butonad=:butonad,
		slayt_butonlink=:butonlink,
		slayt_resim=:resim");
	$insert = $kaydet->execute(
		array(
			'baslik' => $_POST[ 'slayt_baslik' ],
			'aciklama' => $_POST[ 'slayt_aciklama' ],
			'renk' => $_POST[ 'slayt_renk' ],
			'butonad'     => $_POST[ 'slayt_butonad' ],
			'butonlink' => $_POST[ 'slayt_butonlink' ],
			'sira'     => $_POST[ 'slayt_sira' ],
			'resim'    => $refimgyol
		));

	if ( $insert )
	{

		Header( "Location:../slayt.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../slayt.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'urunekle' ] ) )
{


	if (empty($_SESSION['kullanici_adi'])) {
		header("Location: ../index.php?status=no" );
		exit();
	}

	panel_ensure_urunler_columns($db);

	$birim_goster = (isset($_POST['urun_fiyat_birim_goster']) && (string)$_POST['urun_fiyat_birim_goster'] === '1') ? 1 : 0;
	$birim_metin = panel_mb_substr_safe(trim((string)($_POST['urun_fiyat_birim_metin'] ?? '')), 0, 64);
	$birim_renk_raw = trim((string)($_POST['urun_fiyat_birim_renk'] ?? ''));
	$birim_renk = preg_match('/^#[0-9A-Fa-f]{3,8}$/', $birim_renk_raw) ? $birim_renk_raw : '';
	$birim_olcek = floatval(str_replace(',', '.', (string)($_POST['urun_fiyat_birim_olcek'] ?? '1')));
	if ($birim_olcek < 0.5) { $birim_olcek = 0.5; }
	if ($birim_olcek > 2.5) { $birim_olcek = 2.5; }

	$folder_name = 'assets/img/urunler';
	$uploads_dir = dirname(__DIR__) . '/' . $folder_name;
	
	// Klasör yoksa oluştur
	if (!is_dir($uploads_dir)) {
		@mkdir($uploads_dir, 0777, true);
	}

	// Ürün resmi yükleme
	$refimgyol = '';
	if(isset($_FILES['urun_resim']) && $_FILES['urun_resim']["name"]!='' && $_FILES['urun_resim']["error"] == 0)
	{
		@$tmp_name = $_FILES['urun_resim']["tmp_name"];
		@$name = $_FILES['urun_resim']["name"];
		$benzersizsayi1=rand(20000,32000);
		$uzanti = '.webp';
		$benzersizad=$benzersizsayi1.$uzanti;
		$refimgyol=$folder_name."/".$benzersizad;

		if (!convertToWebp($tmp_name, "$uploads_dir/$benzersizad", 85)) {
            @move_uploaded_file($tmp_name, "$uploads_dir/$benzersizad.jpg");
            $benzersizad = $benzersizsayi1.".jpg";
            $refimgyol = $folder_name."/".$benzersizad;
        }
	}

	// Ürün seçildi resmi yükleme
	$refimgyol1 = '';
	if(isset($_FILES['urun_resimsec']) && $_FILES['urun_resimsec']["name"]!='' && $_FILES['urun_resimsec']["error"] == 0)
	{
		@$tmp_name1 = $_FILES['urun_resimsec']["tmp_name"];
		@$name1 = $_FILES['urun_resimsec']["name"];
		$benzersizsayi2=rand(20000,32000);
		$uzanti = '.webp';
		$benzersizad1=$benzersizsayi2.$uzanti;
		$refimgyol1=$folder_name."/".$benzersizad1;

		if (!convertToWebp($tmp_name1, "$uploads_dir/$benzersizad1", 85)) {
            @move_uploaded_file($tmp_name1, "$uploads_dir/$benzersizad1.jpg");
            $benzersizad1 = $benzersizsayi2.".jpg";
            $refimgyol1 = $folder_name."/".$benzersizad1;
        }
	}

	// Sipariş tamamlama kartı görseli (vitrin görselinden bağımsız)
	$refimgyol_sip_kart = '';
	if (isset($_FILES['urun_siparis_kart']) && $_FILES['urun_siparis_kart']['name'] !== '' && (int) $_FILES['urun_siparis_kart']['error'] === 0) {
		@$tmp_sk = $_FILES['urun_siparis_kart']['tmp_name'];
		$benzersizsayi_sk = rand(62000, 64999);
		$webp_name = $benzersizsayi_sk . '.webp';
		$refimgyol_sip_kart = $folder_name . '/' . $webp_name;
		if (convertToWebp($tmp_sk, "$uploads_dir/$webp_name", 85)) {
			// yol $refimgyol_sip_kart (.webp)
		} else {
			$jpg_name = $benzersizsayi_sk . '.jpg';
			if (@move_uploaded_file($tmp_sk, "$uploads_dir/$jpg_name")) {
				$refimgyol_sip_kart = $folder_name . '/' . $jpg_name;
			} else {
				$refimgyol_sip_kart = '';
			}
		}
	}

	// Sadece yüklenen görselleri ekle
	$insert_fields = "urun_baslik=:baslik, urun_alt_baslik=:alt_baslik, urun_etiket=:etiket, urun_etiket_bg=:etiket_bg, urun_etiket_color=:etiket_color, urun_seo_baslik=:seo_baslik, urun_seo_aciklama=:seo_aciklama, urun_seo_anahtar=:seo_anahtar, urun_fiyat=:fiyat, urun_eski_fiyat=:eski_fiyat, urun_fiyat_birim_metin=:birim_metin, urun_fiyat_birim_renk=:birim_renk, urun_fiyat_birim_goster=:birim_goster, urun_fiyat_birim_olcek=:birim_olcek, urun_siralama=:siralama, urun_slug=:slug, secenekler=:secenekler";
	
	$insert_params = array(
		'baslik'     => isset($_POST['urun_baslik']) ? $_POST['urun_baslik'] : '',
		'alt_baslik' => isset($_POST['urun_alt_baslik']) ? $_POST['urun_alt_baslik'] : '',
		'etiket'     => isset($_POST['urun_etiket']) ? $_POST['urun_etiket'] : '',
		'etiket_bg'  => isset($_POST['urun_etiket_bg']) ? $_POST['urun_etiket_bg'] : '',
		'etiket_color' => isset($_POST['urun_etiket_color']) ? $_POST['urun_etiket_color'] : '',
		'seo_baslik' => isset($_POST['urun_seo_baslik']) ? $_POST['urun_seo_baslik'] : '',
		'seo_aciklama' => isset($_POST['urun_seo_aciklama']) ? $_POST['urun_seo_aciklama'] : '',
		'seo_anahtar' => isset($_POST['urun_seo_anahtar']) ? $_POST['urun_seo_anahtar'] : '',
		'fiyat'      => isset($_POST['urun_fiyat']) ? $_POST['urun_fiyat'] : '',
		'eski_fiyat' => isset($_POST['urun_eski_fiyat']) ? $_POST['urun_eski_fiyat'] : '',
		'birim_metin' => $birim_metin,
		'birim_renk' => $birim_renk,
		'birim_goster' => $birim_goster,
		'birim_olcek' => $birim_olcek,
		'siralama'   => isset($_POST['urun_siralama']) ? $_POST['urun_siralama'] : 0,
		'slug'       => '',
		'secenekler' => ''
	);
	$slug_try = seoFriendlySlug(isset($_POST['urun_baslik']) ? $_POST['urun_baslik'] : '');
	$insert_params['slug'] = ($slug_try !== '') ? $slug_try : ('urun-' . dechex((int) (microtime(true) * 1000) % 0xffffff));

	if (!empty($refimgyol)) {
		$insert_fields .= ", urun_resim=:urun_resim";
		$insert_params['urun_resim'] = $refimgyol;
	}
	if (!empty($refimgyol1)) {
		$insert_fields .= ", urun_resimsec=:urun_resimsec";
		$insert_params['urun_resimsec'] = $refimgyol1;
	}
	if (!empty($refimgyol_sip_kart)) {
		$insert_fields .= ', urun_siparis_kart=:urun_siparis_kart';
		$insert_params['urun_siparis_kart'] = $refimgyol_sip_kart;
	}

	$kaydet = $db->prepare("INSERT INTO urunler SET " . $insert_fields);
	$insert = $kaydet->execute($insert_params);

	if ( $insert )
	{

		Header( "Location:../urunler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../urunler.php?status=no" );
		exit;
	}
}


if ( isset( $_POST[ 'durumekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$kaydet = $db->prepare(
		"INSERT INTO durum SET
		ad=:ad,
		siralama=:siralama");
	$insert = $kaydet->execute(
		array(
			'ad'     => $_POST[ 'ad' ],
			'siralama' => $_POST[ 'siralama' ]
		));

	if ( $insert )
	{

		Header( "Location:../durumlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../durumlar.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'ipekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$kaydet = $db->prepare(
		"INSERT INTO ip SET
		ip=:ad");
	$insert = $kaydet->execute(
		array(
			'ad'     => $_POST[ 'ip' ]
		));

	if ( $insert )
	{

		Header( "Location:../ip-engelle.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../ip-engelle.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'ipeklex' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$kaydet = $db->prepare(
		"INSERT INTO ip SET
		ip=:ad");
	$insert = $kaydet->execute(
		array(
			'ad'     => $_POST[ 'ip' ]
		));
	$IdSip = $_POST[ 'id' ];
	if ( $insert )
	{

		Header( "Location:../siparis-detay.php?siparis_id=$IdSip&status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../siparis-detay.php?siparis_id=$IdSip&status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'teleklex' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	// tel_engelle tablosu yoksa oluştur
	try {
		$db->exec("CREATE TABLE IF NOT EXISTS tel_engelle (
			id INT(11) AUTO_INCREMENT PRIMARY KEY,
			tel VARCHAR(20) NOT NULL,
			UNIQUE KEY tel (tel)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	} catch (Exception $e) {}

	$IdSip = isset($_POST[ 'id' ]) ? intval($_POST[ 'id' ]) : 0;
	$tel = isset($_POST[ 'tel' ]) ? trim($_POST[ 'tel' ]) : '';
	
	if (empty($tel)) {
		// tel-engelle.php sayfasından mı yoksa siparis-detay.php'den mi kontrol et
		if ($IdSip > 0) {
			Header( "Location:../siparis-detay.php?siparis_id=$IdSip&status=no" );
			exit;
		} else {
			Header( "Location:../tel-engelle.php?status=no" ); exit;
		}
		exit();
	}
	
	// Önce telefon zaten engellenmiş mi kontrol et
	$kontrol = $db->prepare("SELECT id FROM tel_engelle WHERE tel=:tel");
	$kontrol->execute(array('tel' => $tel));
	if ($kontrol->rowCount() > 0) {
		// Zaten engellenmiş, başarılı say
		if ($IdSip > 0) {
			Header( "Location:../siparis-detay.php?siparis_id=$IdSip&status=ok" );
			exit;
		} else {
			Header( "Location:../tel-engelle.php?status=ok" ); exit;
		}
		exit();
	}
	
	$kaydet = $db->prepare(
		"INSERT INTO tel_engelle SET
		tel=:tel");
	$insert = $kaydet->execute(
		array(
			'tel'     => $tel
		));
	
	// Başarılı veya başarısız olsun, zaten engellenmiş olabilir
	if ($IdSip > 0) {
		Header( "Location:../siparis-detay.php?siparis_id=$IdSip&status=ok" );
		exit;
	} else {
		Header( "Location:../tel-engelle.php?status=ok" ); exit;
	}
	exit();
}

if ( isset( $_POST[ 'durumduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$duzenle = $db->prepare(
		"UPDATE durum SET
		ad=:ad,
		siralama=:siralama
		WHERE id={$_POST['id']}"
	);
	$update  = $duzenle->execute(
		array(
			'ad'   => $_POST[ 'ad' ],
			'siralama' => $_POST[ 'siralama' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../durumlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../durumlar.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'ipduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$duzenle = $db->prepare(
		"UPDATE ip SET
		ip=:ad
		WHERE id={$_POST['id']}"
	);
	$update  = $duzenle->execute(
		array(
			'ad'   => $_POST[ 'ip' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../ip-engelle.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../ip-engelle.php?status=no" );
		exit;
	}
}


if ( isset($_GET[ 'durumsil' ]) && $_GET[ 'durumsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	
	// Sahte durumunu silmeyi engelle (ID 18)
	if ($_GET['durum_id'] == 18) {
		Header( "Location:../durumlar.php?durum=silme_engellendi" );
		exit();
	}
	
	$sil     = $db->prepare( "DELETE from durum where id=:id" );
	$kontrol = $sil->execute(
		array(
			'id' => $_GET[ 'durum_id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../durumlar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../durumlar.php?status=no" );
		exit;
	}
}

if ( isset($_GET[ 'ipsil' ]) && $_GET[ 'ipsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from ip where id=:id" );
	$kontrol = $sil->execute(
		array(
			'id' => $_GET[ 'id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../ip-engelle.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../ip-engelle.php?status=no" );
		exit;
	}
}

if ( isset($_GET[ 'ipsilx' ]) && $_GET[ 'ipsilx' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from ip where id=:id" );
	$kontrol = $sil->execute(
		array(
			'id' => $_GET[ 'id' ]
		)
	);
	$sip = $_GET[ 'sip' ];
	if ( $kontrol )
	{

		Header( "Location:../siparis-detay.php?siparis_id=$sip&status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../siparis-detay.php?siparis_id=$sip&status=no" );
		exit;
	}
}

if ( isset($_GET[ 'telsilx' ]) && $_GET[ 'telsilx' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}

	$id = isset($_GET[ 'id' ]) ? intval($_GET[ 'id' ]) : 0;
	$sip = isset($_GET[ 'sip' ]) ? intval($_GET[ 'sip' ]) : 0;
	
	if (empty($id)) {
		Header( "Location:../tel-engelle.php?status=no" );
		exit();
	}

	$sil = $db->prepare( "DELETE from tel_engelle where id=:id" );
	$kontrol = $sil->execute(
		array(
			'id' => $id
		)
	);
	
	// Silme işlemi başarılı olsun veya olmasın (zaten silinmiş olabilir), başarılı göster
	if ($sip > 0) {
		Header( "Location:../siparis-detay.php?siparis_id=$sip&status=ok" );
		exit;
	} else {
		Header( "Location:../tel-engelle.php?status=ok" ); exit;
	}
	exit();
}

if ( isset( $_POST[ 'refekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$uploads_dir = '../assets/img/referanslar';
	@$tmp_name = $_FILES[ 'referans_resim1' ][ "tmp_name" ];
	$benzersizsayi1 = rand( 20000, 32000 );
	$benzersizsayi2 = rand( 20000, 32000 );
	$uzanti = '.jpg';
	$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
	$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
	@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );


	$kaydet = $db->prepare(
		"INSERT INTO referanslar SET
		referans_adi=:adi,
		referans_kategori=:kategori,
		referans_link=:link,
		referans_resim1=:resim1
		");
	$insert = $kaydet->execute(
		array(
			'adi'    => $_POST[ 'referans_adi' ],
			'kategori'    => $_POST[ 'referans_kategori' ],
			'link'    => $_POST[ 'referans_link' ],
			'resim1'    => $refimgyol
		));

	if ( $insert )
	{

		Header( "Location:../referanslar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../referanslar.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'blogduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	if ( $_FILES[ 'blog_resim' ][ "size" ] > 0 )
	{

		$uploads_dir = '../assets/img/blog';
		@$tmp_name = $_FILES[ 'blog_resim' ][ "tmp_name" ];
		$benzersizsayi1 = rand( 20000, 32000 );
		$benzersizsayi2 = rand( 20000, 32000 );
		$uzanti = '.jpg';
		$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
		$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
		@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

		$duzenle = $db->prepare(
			"UPDATE blog SET
			blog_baslik=:baslik,
			blog_detay=:detay,
			blog_title=:title,
			blog_descr=:descr,
			blog_keyword=:keyword,
			blog_resim=:resim
			WHERE blog_id=:id"
		);
		$update  = $duzenle->execute(
			array(
				'baslik' => $_POST[ 'blog_baslik' ],
				'detay'  => $_POST[ 'blog_detay' ],
				'title'  => $_POST[ 'blog_title' ],
				'descr'  => $_POST[ 'blog_descr' ],
				'keyword'  => $_POST[ 'blog_keyword' ],
				'resim'  => $refimgyol,
				'id'     => $_POST['id']
			)
		);

		$blog_id = $_POST[ 'id' ];

		if ( $update )
		{

			$resimsilunlink = $_POST[ 'eski_yol' ];
			unlink( "../$resimsilunlink" );

			Header( "Location:../blog-duzenle.php?id={$_POST['id']}&status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../blog-duzenle.php?id={$_POST['id']}&status=no" );
			exit;
		}
	}
	else
	{

		$duzenle = $db->prepare(
			"UPDATE blog SET
			blog_baslik=:baslik,
			blog_title=:title,
			blog_descr=:descr,
			blog_keyword=:keyword,
			blog_detay=:detay
			WHERE blog_id=:id"
		);
		$update  = $duzenle->execute(
			array(
				'baslik' => $_POST[ 'blog_baslik' ],
				'title'  => $_POST[ 'blog_title' ],
				'descr'  => $_POST[ 'blog_descr' ],
				'keyword'  => $_POST[ 'blog_keyword' ],
				'detay'  => $_POST[ 'blog_detay' ],
				'id'     => $_POST['id']
			)
		);

		$blog_id = $_POST[ 'blog_id' ];

		if ( $update )
		{


			Header( "Location:../blog-duzenle.php?id={$_POST['id']}&status=ok" );
			exit;
		}
		else
		{
			Header( "Location:../blog-duzenle.php?id={$_POST['id']}&status=no" );
			exit;
		}
	}
}

if ( isset( $_POST[ 'blogekle' ] ) )
{
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: ../index.php?status=no" ); exit(); exit; }

	$uploads_dir = '../upload/blog';
	@$tmp_name = $_FILES[ 'blog_resim' ][ "tmp_name" ];
	$benzersizsayi1 = rand( 20000, 32000 );
	$uzanti = '.jpg';
	$benzersizad    = $benzersizsayi1;
	$refimgyol      = $benzersizad . $uzanti;
	@move_uploaded_file( $tmp_name, "$uploads_dir/$refimgyol" );

	$kaydet = $db->prepare(
		"INSERT INTO blog SET
		blog_baslik=:baslik,
		blog_detay=:detay,
		blog_title=:title,
		blog_descr=:descr,
		blog_keyword=:keyword,
		blog_resim=:resim
		");
	$insert = $kaydet->execute(
		array(
			'baslik' => $_POST[ 'blog_baslik' ],
			'detay'  => $_POST[ 'blog_detay' ],
			'title'  => $_POST[ 'blog_title' ],
			'descr'  => $_POST[ 'blog_descr' ],
			'keyword' => $_POST[ 'blog_keyword' ],
			'resim'  => $refimgyol
		));

	if ( $insert ) { Header( "Location:../blog.php?status=ok" ); exit; } else { Header( "Location:../blog.php?status=no" ); exit; }
	exit;
}

if ( isset($_GET[ 'blogsil' ]) && $_GET[ 'blogsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from blog where blog_id=:blog_id" );
	$kontrol = $sil->execute(
		array(
			'blog_id' => $_GET[ 'blog_id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../blog.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../blog.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'yaziduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$duzenle = $db->prepare(
		"UPDATE yazi SET
		yazi_baslik=:baslik,
		yazi_icerik=:icerik
		WHERE yazi_id={$_POST['yazi_id']}"
	);
	$update  = $duzenle->execute(
		array(
			'baslik'   => $_POST[ 'yazi_baslik' ],
			'icerik' => $_POST[ 'yazi_icerik' ]
		)
	);

	$yazi_id = $_POST[ 'yazi_id' ];

	if ( $update )
	{


		Header( "Location:../yazi.php?durum=ok" );
		exit;
	}
	else
	{

		Header( "Location:../yazi.php?durum=no" );
		exit;
	}
}


if ( isset( $_POST[ 'iadeduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$duzenle = $db->prepare(
		"UPDATE iadesebep SET
		iadesebep_adi=:banka,
		iadesebep_sira=:sira
		WHERE iadesebep_id={$_POST['iadesebep_id']}"
	);
	$update  = $duzenle->execute(
		array(
			'banka'   => $_POST[ 'iadesebep_adi' ],
			'sira' => $_POST[ 'iadesebep_sira' ]
		)
	);


	if ( $update )
	{


		Header( "Location:../iade-nedenleri.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../iade-nedenleri.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'hesapduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$duzenle = $db->prepare(
		"UPDATE hesap SET
		hesap_banka=:banka,
		hesap_isim=:isim,
		hesap_sube=:sube,
		hesap_no=:no,
		hesap_iban=:iban
		WHERE hesap_id={$_POST['hesap_id']}"
	);
	$update  = $duzenle->execute(
		array(
			'banka'   => $_POST[ 'hesap_banka' ],
			'isim'   => $_POST[ 'hesap_isim' ],
			'sube'   => $_POST[ 'hesap_sube' ],
			'no'   => $_POST[ 'hesap_no' ],
			'iban' => $_POST[ 'hesap_iban' ]
		)
	);


	if ( $update )
	{


		Header( "Location:../hesaplarim.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../hesaplarim.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'hesapsil' ]) && $_GET[ 'hesapsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from hesap where hesap_id=:hesap_id" );
	$kontrol = $sil->execute(
		array(
			'hesap_id' => $_GET[ 'hesap_id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../hesaplarim.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../hesaplarim.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'ulkeduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$duzenle = $db->prepare(
		"UPDATE ulke SET
		ulke=:ulke
		WHERE id={$_POST['id']}"
	);
	$update  = $duzenle->execute(
		array(
			'ulke'   => $_POST[ 'ulke' ]
		)
	);


	if ( $update )
	{


		Header( "Location:../ulkeler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../ulkeler.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'ulkesil' ]) && $_GET[ 'ulkesil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from ulke where id=:id" );
	$kontrol = $sil->execute(
		array(
			'id' => $_GET[ 'ulke_id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../ulkeler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../ulkeler.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'sayfasil' ]) && $_GET[ 'sayfasil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from sayfalar where sayfa_id=:sayfa_id" );
	$kontrol = $sil->execute(
		array(
			'sayfa_id' => $_GET[ 'sayfa_id' ]
		)
	);

	if ( $kontrol )
	{

		Header( "Location:../sayfalar.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../sayfalar.php?status=no" );
		exit;
	}
}

if ( isset( $_POST[ 'iadeekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$kaydet = $db->prepare(
		"INSERT INTO iadesebep SET
		iadesebep_adi=:adi,
		iadesebep_sira=:sira
		"
	);
	$insert = $kaydet->execute(
		array(
			'adi'   => $_POST[ 'iadesebep_adi' ],
			'sira' => $_POST[ 'iadesebep_sira' ]
		)
	);

	if ( $insert )
	{

		Header( "Location:../iade-nedenleri.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../iade-nedenleri.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'iadetalep' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$kaydet = $db->prepare(
		"INSERT INTO iade SET
		iade_nedeni=:nedeni,
		iade_urun=:urun,
		iade_siparis=:siparis,
		iade_not=:not,
		iade_tel=:tel,
		iade_ad=:ad
		"
	);
	$insert = $kaydet->execute(
		array(
			'nedeni'   => $_POST[ 'iade_nedeni' ],
			'urun'   => $_POST[ 'iade_urun' ],
			'siparis'   => $_POST[ 'iade_siparis' ],
			'not'   => $_POST[ 'iade_not' ],
			'tel'   => $_POST[ 'iade_tel' ],
			'ad' => $_POST[ 'iade_ad' ]
		)
	);

	if ( $insert )
	{

		Header( "Location:../../kolay-iade?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../../kolay-iade?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'hesapekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$kaydet = $db->prepare(
		"INSERT INTO hesap SET
		hesap_banka=:banka,
		hesap_isim=:isim,
		hesap_sube=:sube,
		hesap_no=:no,
		hesap_iban=:iban
		"
	);
	$insert = $kaydet->execute(
		array(
			'banka'   => $_POST[ 'hesap_banka' ],
			'isim'   => $_POST[ 'hesap_isim' ],
			'sube'   => $_POST[ 'hesap_sube' ],
			'no'   => $_POST[ 'hesap_no' ],
			'iban' => $_POST[ 'hesap_iban' ]
		)
	);

	if ( $insert )
	{

		Header( "Location:../hesaplarim.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../hesaplarim.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'ulkeekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$kaydet = $db->prepare(
		"INSERT INTO ulke SET
		ulke=:ulke
		"
	);
	$insert = $kaydet->execute(
		array(
			'ulke'   => $_POST[ 'ulke' ]
		)
	);

	if ( $insert )
	{

		Header( "Location:../ulkeler.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../ulkeler.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'sayfaekle' ] ) ) {
    if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: ../index.php?status=no" ); exit(); exit; }

    $sayfa_baslik = $_POST['sayfa_baslik'];
    $sayfa_slug = !empty($_POST['sayfa_slug']) ? $_POST['sayfa_slug'] : seo($sayfa_baslik);
    
    // Slug Uniqueness Check
    $slug_check = $db->prepare("SELECT count(*) FROM sayfalar WHERE sayfa_slug = :slug");
    $slug_check->execute(['slug' => $sayfa_slug]);
    $count = $slug_check->fetchColumn();

    if ($count > 0) {
        $suffix = 2;
        while(true) {
            $new_slug = $sayfa_slug . '-' . $suffix;
            $slug_check->execute(['slug' => $new_slug]);
            if ($slug_check->fetchColumn() == 0) {
                $sayfa_slug = $new_slug;
                break;
            }
            $suffix++;
        }
    }
    
    // Resim Yükleme İşlemi
    $sayfa_resim = "";
    if ($_FILES['ayar_altgorsel']['size'] > 0) {
        $uploads_dir = '../../img';
        @$tmp_name = $_FILES['ayar_altgorsel']["tmp_name"];
        @$name = $_FILES['ayar_altgorsel']["name"];
        $benzersizsayi1 = rand(20000, 32000);
        $benzersizsayi2 = rand(20000, 32000);
        $uzanti = '.webp';
        $resimyol = 'img/sayfa-' . $benzersizsayi1 . $benzersizsayi2 . $uzanti;
        $hedef = "../../" . $resimyol;
        
        if (convertToWebp($tmp_name, $hedef)) {
            $sayfa_resim = $resimyol;
        }
    }

    $kaydet = $db->prepare("INSERT INTO sayfalar SET
        sayfa_title=:title,
        sayfa_descr=:descr,
        sayfa_keyword=:keyword,
        sayfa_baslik=:baslik,
        sayfa_slug=:slug,
        sayfa_resim=:resim,
        sayfa_sira=:sira,
        sayfa_durum=:durum,
        sayfa_menu=:menu,
        sayfa_id=:parent_id,
        sayfa_icerik=:icerik,
        sayfa_canonical=:canonical,
        sayfa_robots=:robots,
        sayfa_author=:author,
        sayfa_og_title=:og_title,
        sayfa_og_description=:og_description,
        sayfa_og_image=:og_image,
        sayfa_schema_type=:schema_type,
        sayfa_tarih=NOW()
    ");
    
    $insert = $kaydet->execute(array(
        'title'     => $_POST['sayfa_title'],
        'descr'     => $_POST['sayfa_descr'],
        'keyword'   => $_POST['sayfa_keyword'],
        'baslik'    => $sayfa_baslik,
        'slug'      => $sayfa_slug,
        'resim'     => $sayfa_resim,
        'sira'      => $_POST['sayfa_sira'],
        'durum'     => $_POST['sayfa_durum'],
        'menu'      => $_POST['sayfa_menu'],
        'parent_id' => $_POST['sayfa_id'],
        'icerik'    => $_POST['sayfa_icerik'],
        'canonical' => $_POST['sayfa_canonical'],
        'robots'    => $_POST['sayfa_robots'],
        'author'    => $_POST['sayfa_author'],
        'og_title'       => $_POST['sayfa_og_title'],
        'og_description' => $_POST['sayfa_og_description'],
        'og_image'       => $_POST['sayfa_og_image'],
        'schema_type'    => $_POST['sayfa_schema_type']
    ));

    if ($insert) {
        $last_id = $db->lastInsertId();
        Header("Location:../sayfa-duzenle.php?id=" . $last_id . "&status=ok");
        exit;
    } else {
        Header("Location:../sayfa-ekle.php?status=no");
        exit;
    }
}

if (isset($_POST['sayfaduzenle'])) {
    if (!$_SESSION['kullanici_adi']) { header("Location: ../index.php?status=no"); exit(); exit; }

    $sayfa_id = $_POST['id'];
    $sayfa_baslik = $_POST['sayfa_baslik'];
    $sayfa_slug = !empty($_POST['sayfa_slug']) ? $_POST['sayfa_slug'] : seo($sayfa_baslik);
    
    // Resim İşlemi
    $sayfa_resim = $_POST['eskiyol_sayfaresim'];
    if ($_FILES['sayfa_resim']['size'] > 0) {
        // Eski resmi sil
        if (!empty($sayfa_resim) && file_exists("../../" . $sayfa_resim)) {
            @unlink(SITE_ROOT . "/" . $sayfa_resim);
        }
        
        $uploads_dir = '../../img';
        @$tmp_name = $_FILES['sayfa_resim']["tmp_name"];
        $benzersizsayi1 = rand(20000, 32000);
        $benzersizsayi2 = rand(20000, 32000);
        $uzanti = '.webp';
        $resimyol = 'img/sayfa-' . $benzersizsayi1 . $benzersizsayi2 . $uzanti;
        $hedef = "../../" . $resimyol;
        
        if (convertToWebp($tmp_name, $hedef)) {
            $sayfa_resim = $resimyol;
        } else {
            // WebP başarısızsa jpg/png olarak yükle
            $uzanti_orj = strtolower(pathinfo($_FILES['sayfa_resim']["name"], PATHINFO_EXTENSION));
            $resimyol = 'img/sayfa-' . $benzersizsayi1 . $benzersizsayi2 . '.' . $uzanti_orj;
            $hedef = "../../" . $resimyol;
             move_uploaded_file($tmp_name, $hedef);
             $sayfa_resim = $resimyol;
        }
    }

    $duzenle = $db->prepare("UPDATE sayfalar SET
        sayfa_title=:title,
        sayfa_descr=:descr,
        sayfa_keyword=:keyword,
        sayfa_baslik=:baslik,
        sayfa_slug=:slug,
        sayfa_resim=:resim,
        sayfa_sira=:sira,
        sayfa_durum=:durum,
        sayfa_menu=:menu,
        sayfa_id=:parent_id,
        sayfa_icerik=:icerik,
        sayfa_canonical=:canonical,
        sayfa_robots=:robots,
        sayfa_author=:author,
        sayfa_og_title=:og_title,
        sayfa_og_description=:og_description,
        sayfa_og_image=:og_image,
        sayfa_schema_type=:schema_type,
        sayfa_tarih=NOW()
        WHERE id=:id
    ");
    
    $update = $duzenle->execute(array(
        'title'     => $_POST['sayfa_title'],
        'descr'     => $_POST['sayfa_descr'],
        'keyword'   => $_POST['sayfa_keyword'],
        'baslik'    => $sayfa_baslik,
        'slug'      => $sayfa_slug,
        'resim'     => $sayfa_resim,
        'sira'      => $_POST['sayfa_sira'],
        'durum'     => $_POST['sayfa_durum'],
        'menu'      => $_POST['sayfa_menu'],
        'parent_id' => $_POST['sayfa_id'],
        'icerik'    => $_POST['sayfa_icerik'],
        'canonical' => $_POST['sayfa_canonical'],
        'robots'    => $_POST['sayfa_robots'],
        'author'    => $_POST['sayfa_author'],
        'og_title'       => $_POST['sayfa_og_title'],
        'og_description' => $_POST['sayfa_og_description'],
        'og_image'       => $_POST['sayfa_og_image'],
        'schema_type'    => $_POST['sayfa_schema_type'],
        'id'        => $sayfa_id
    ));

    if ($update) {
        Header("Location:../sayfa-duzenle.php?id=$sayfa_id&status=ok");
        exit;
    } else {
        Header("Location:../sayfa-duzenle.php?id=$sayfa_id&status=no");
        exit;
    }
}



if ( isset( $_POST[ 'teklifver' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$ayarkaydet = $db->prepare(
		"INSERT INTO teklif SET
		teklif_adsoyad=:adsoyad,
		teklif_tel=:tel,
		teklif_nereden=:nereden,
		teklif_nereye=:nereye,
		teklif_cinsi=:cinsi"
	);
	$update     = $ayarkaydet->execute(
		array(
			'adsoyad' => $_POST[ 'teklif_adsoyad' ],
			'tel'    => $_POST[ 'teklif_tel' ],
			'nereden'   => $_POST[ 'teklif_nereden' ],
			'nereye' => $_POST[ 'teklif_nereye' ],
			'cinsi'    => $_POST[ 'teklif_cinsi' ]
		)
	);

	$uye    = $_POST[ 'teklif_adsoyad' ];
	$tel   = $_POST[ 'teklif_tel' ];

	if ( $update )
	{

		Header( "Location:../../teklif-sms-yolla?tel=$tel&ad=$uye" );
		exit;

	}
	else
	{

		header("Location: ../index.php?teklif=no" );
		exit;
	}
}

if ( isset( $_POST[ 'beniara' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	$ayarkaydet = $db->prepare(
		"INSERT INTO beniara SET
		beniara_tel=:tel"
	);
	$update     = $ayarkaydet->execute(
		array(
			'tel' => $_POST[ 'beniara_tel' ]
		)
	);

	$tel   = $_POST[ 'teklif_tel' ];

	if ( $update )
	{

		Header( "Location:../../beniara-sms-yolla?tel=$tel" );
		exit;

	}
	else
	{

		header("Location: ../index.php?teklif=no" );
		exit;
	}
}

if ( isset($_GET[ 'beniarasil' ]) && $_GET[ 'beniarasil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from beniara where beniara_id=:beniara_id" );
	$kontrol = $sil->execute(
		array(
			'beniara_id' => $_GET[ 'beniara_id' ]
		)
	);

	if ( $kontrol )
	{


		Header( "Location:../beni-ara.php?durum=ok" );
		exit;
	}
	else
	{

		Header( "Location:../beni-ara.php?durum=no" );
		exit;
	}
}
if ( isset($_GET[ 'randevusil' ]) && $_GET[ 'randevusil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from randevu where randevu_id=:randevu_id" );
	$kontrol = $sil->execute(
		array(
			'randevu_id' => $_GET[ 'randevu_id' ]
		)
	);

	if ( $kontrol )
	{


		Header( "Location:../teklif.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../teklif.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'teklifsil' ]) && $_GET[ 'teklifsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from teklif where teklif_id=:teklif_id" );
	$kontrol = $sil->execute(
		array(
			'teklif_id' => $_GET[ 'teklif_id' ]
		)
	);

	if ( $kontrol )
	{


		Header( "Location:../teklifler.php?durum=ok" );
		exit;
	}
	else
	{

		Header( "Location:../teklifler.php?durum=no" );
		exit;
	}
}


if ( isset($_GET[ 'urunresimsil' ]) && $_GET[ 'urunresimsil' ] == "ok" )
{

	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$sil     = $db->prepare( "DELETE from resim where resim_id=:resim_id" );
	$kontrol = $sil->execute(
		array(
			'resim_id' => $_GET[ 'resim_id' ]
		)
	);
	$urun = $_GET['urun_id'];

	if ( $kontrol )
	{
		$resimsilunlink=$_GET['eski_yol'];
		unlink("../$resimsilunlink");

		Header( "Location:../urun-resim-duzenle.php?urun_id=$urun&?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../slayt.php?urun_id=$urun&?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'videoekle' ] ) || isset( $_POST[ 'videoekle_youtube' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}

	// YouTube video kodunu URL'den çıkar veya direkt kodu al
	function extractYouTubeId($url) {
		// Eğer zaten sadece kod ise (11 karakter, alfanumerik)
		if (preg_match('/^[a-zA-Z0-9_-]{11}$/', trim($url))) {
			return trim($url);
		}
		
		// YouTube URL formatlarını kontrol et
		$patterns = array(
			'/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
			'/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
		);
		
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $url, $matches)) {
				return $matches[1];
			}
		}
		
		// Eğer hiçbir pattern eşleşmezse, temizlenmiş değeri döndür
		$cleaned = preg_replace('/[^a-zA-Z0-9_-]/', '', $url);
		if (strlen($cleaned) == 11) {
			return $cleaned;
		}
		
		return false;
	}

	$video_link = isset($_POST['video_link']) ? trim($_POST['video_link']) : '';
	
	if (empty($video_link)) {
		Header( "Location:../gorseller.php?status=no" );
		exit();
	}
	
	// YouTube video kodunu çıkar
	$youtube_id = extractYouTubeId($video_link);
	
	if (!$youtube_id) {
		Header( "Location:../gorseller.php?status=no&error=invalid_video" );
		exit();
	}

	// Sıra numarasını belirle (en yüksek sira + 1)
	$siraQuery = $db->prepare("SELECT COALESCE(MAX(sira), 0) + 1 as yeni_sira FROM resimgaleri");
	$siraQuery->execute();
	$siraResult = $siraQuery->fetch(PDO::FETCH_ASSOC);
	$yeniSira = isset($siraResult['yeni_sira']) ? $siraResult['yeni_sira'] : 1;

	$kaydet = $db->prepare(
		"INSERT INTO resimgaleri SET
		resim_baslik=:baslik,
		video=:video,
		resim_link=:rs,
		sira=:sira");
	$insert = $kaydet->execute(
		array(
			'rs' => $youtube_id,
			'video'    => 1,
			'baslik'    => "YouTube Video",
			'sira' => $yeniSira
		));

	if ( $insert )
	{

		Header( "Location:../gorseller.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../gorseller.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'kargoadminduzenle' ]) )
{



	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$kaydet = $db->prepare(
		"UPDATE siparis SET
		siparis_kargo=:kargo,
		siparis_takip=:takip
		WHERE siparis_id={$_POST['siparis_id']}");
	$insert = $kaydet->execute(
		array(
			'kargo' => $_POST[ 'siparis_kargo' ],
			'takip' => $_POST[ 'siparis_takip' ]
		));

	if ( $insert )
	{
		$id=$_POST['siparis_id'];

		Header( "Location:../siparis-detay.php?siparis_id=$id&status=ok" );
		exit;
	}
	else
	{


		Header( "Location:../siparis-detay.php?siparis_id=$id&status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'kargola' ]) )
{



	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}



	$kaydet = $db->prepare(
		"UPDATE siparis SET
		siparis_kargo=:kargo,
		siparis_takip=:takip,
		siparis_durum=:durum
		WHERE siparis_id=:id");
	$insert = $kaydet->execute(
		array(
			'kargo' => $_POST[ 'siparis_kargo' ],
			'takip' => $_POST[ 'siparis_takip' ],
			'durum' => 3,
			'id'    => $_POST['siparis_id']
		));

	if ( $insert )
	{
		$tel=$_GET['siparis_tel'];
		$id=$_POST['siparis_id'];

		Header( "Location:../../include/siparis-guncelleme.php?tel=$tel&id=$id&status=ok" );
		exit;
	}
	else
	{


		Header( "Location:../onayli-siparisler.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'siparisonay' ]) && $_GET[ 'siparisonay' ] == "ok" )
{

	$kaydet = $db->prepare(
		"UPDATE siparis SET
		siparis_durum=:durum
		WHERE siparis_id=:id");
	$insert = $kaydet->execute(
		array(
			'durum' => 0,
			'id'    => $_GET['siparis_id']
		));

	if ( $insert )
	{
		$tel=$_GET['tel'];
		$id=$_GET['siparis_id'];

		Header( "Location:../../include/siparis-guncelleme.php?tel=$tel&id=$id&status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../yeni-siparisler.php?status=no" );
		exit;
	}
}

if ( isset( $_GET['siparis_verify_manual'] ) && $_GET['siparis_verify_manual'] === 'ok' ) {
	if ( empty( $_SESSION['kullanici_adi'] ) ) {
		header( "Location: ../login.php?status=no" );
		exit;
	}
	$siparis_id = isset( $_GET['siparis_id'] ) ? (int) $_GET['siparis_id'] : 0;
	if ( $siparis_id > 0 ) {
		try {
			$db->exec(
				"CREATE TABLE IF NOT EXISTS siparis_dogrulama (
					id INT AUTO_INCREMENT PRIMARY KEY,
					siparis_id INT NOT NULL,
					siparis_tel VARCHAR(32) NOT NULL DEFAULT '',
					otp_code VARCHAR(8) NOT NULL DEFAULT '',
					token_hash VARCHAR(128) NOT NULL DEFAULT '',
					durum TINYINT NOT NULL DEFAULT 0,
					dogrulama_kanali VARCHAR(20) NOT NULL DEFAULT '',
					hata_sayisi INT NOT NULL DEFAULT 0,
					son_gonderim DATETIME NULL,
					son_kontrol DATETIME NULL,
					son_dogrulama DATETIME NULL,
					bitis_tarihi DATETIME NOT NULL,
					olusturma_tarihi DATETIME NOT NULL,
					UNIQUE KEY uq_siparis_id (siparis_id),
					KEY idx_durum (durum)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
			);
			$telQ = $db->prepare( "SELECT siparis_tel FROM siparis WHERE siparis_id=:id LIMIT 1" );
			$telQ->execute( array( 'id' => $siparis_id ) );
			$tel = (string) ( $telQ->fetchColumn() ?: '' );
			$now = gmdate( 'Y-m-d H:i:s' );
			$exp = gmdate( 'Y-m-d H:i:s', time() + 1200 );
			$q   = $db->prepare( "SELECT id FROM siparis_dogrulama WHERE siparis_id=:id LIMIT 1" );
			$q->execute( array( 'id' => $siparis_id ) );
			$row = $q->fetch( PDO::FETCH_ASSOC );
			if ( $row ) {
				$u = $db->prepare( "UPDATE siparis_dogrulama SET durum=1, dogrulama_kanali='manuel', son_kontrol=:sk, son_dogrulama=:sd WHERE siparis_id=:id" );
				$u->execute( array( 'sk' => $now, 'sd' => $now, 'id' => $siparis_id ) );
			} else {
				$i = $db->prepare( "INSERT INTO siparis_dogrulama SET siparis_id=:id, siparis_tel=:tel, otp_code='', token_hash='', durum=1, dogrulama_kanali='manuel', hata_sayisi=0, son_gonderim=NULL, son_kontrol=:sk, son_dogrulama=:sd, bitis_tarihi=:bt, olusturma_tarihi=:ot" );
				$i->execute( array( 'id' => $siparis_id, 'tel' => $tel, 'sk' => $now, 'sd' => $now, 'bt' => $exp, 'ot' => $now ) );
			}
			$db->prepare( "UPDATE siparis SET siparis_durum=0 WHERE siparis_id=:id" )->execute( array( 'id' => $siparis_id ) );
		} catch ( Exception $e ) {}
	}
	$back = ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '../siparisler.php?status=ok';
	header( "Location: " . $back );
	exit;
}

if ( isset( $_GET['siparis_verify_manual_reset'] ) && $_GET['siparis_verify_manual_reset'] === 'ok' ) {
	if ( empty( $_SESSION['kullanici_adi'] ) ) {
		header( "Location: ../login.php?status=no" );
		exit;
	}
	$siparis_id = isset( $_GET['siparis_id'] ) ? (int) $_GET['siparis_id'] : 0;
	if ( $siparis_id > 0 ) {
		try {
			$u = $db->prepare( "UPDATE siparis_dogrulama SET durum=0, dogrulama_kanali='', son_kontrol=:sk WHERE siparis_id=:id" );
			$u->execute( array( 'sk' => gmdate( 'Y-m-d H:i:s' ), 'id' => $siparis_id ) );
			$db->prepare( "UPDATE siparis SET siparis_durum=-2 WHERE siparis_id=:id" )->execute( array( 'id' => $siparis_id ) );
		} catch ( Exception $e ) {}
	}
	$back = ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '../siparisler.php?status=ok';
	header( "Location: " . $back );
	exit;
}
if ( isset( $_POST[ 'resimekle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$uploads_dir = '../assets/img/galeri';
	$tmp_name = $_FILES[ 'resim_link' ][ "tmp_name" ];
	$benzersizsayi1 = rand( 20000, 32000 );
	$benzersizsayi2 = rand( 20000, 32000 );
	$uzanti = '.jpg';
	$benzersizad    = $benzersizsayi1 . $benzersizsayi2 ;
	$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
	move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

	$kaydet = $db->prepare(
		"INSERT INTO resimgaleri SET
		resim_baslik=:baslik,
		resim_link=:resim");
	$insert = $kaydet->execute(
		array(
			'baslik' => $_POST[ 'resim_baslik' ],
			'resim'    => $refimgyol
		));

	if ( $insert )
	{

		Header( "Location:../resim-galerisi.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../resim-galerisi.php?status=no" );
		exit;
	}
}
if ( isset($_GET[ 'siparissil' ]) && $_GET[ 'siparissil' ] == "ok" )
{
	$popupJson = isset($_GET['popup_json']) && (string) $_GET['popup_json'] === '1';
	$siparis_id_get = isset($_GET['siparis_id']) ? (int) $_GET['siparis_id'] : 0;

	if (!$_SESSION[ 'kullanici_adi' ]) {
		if ($popupJson) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('ok' => false, 'error' => 'auth'));
			exit;
		}
		header("Location: ../index.php?status=no" );
		exit();
	}

	if ($siparis_id_get <= 0) {
		if ($popupJson) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('ok' => false, 'error' => 'invalid_id'));
			exit;
		}
		header("Location: ../siparisler.php?status=no");
		exit;
	}

	$inovance=$db->prepare("SELECT * from siparis where siparis_id=:siparis_id");
	$inovance->execute(array(
		'siparis_id' => $siparis_id_get
	));
	$inovanceprint=$inovance->fetch(PDO::FETCH_ASSOC);

	if (!$inovanceprint) {
		if ($popupJson) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('ok' => false, 'error' => 'not_found'));
			exit;
		}
		header("Location: ../siparisler.php?status=no");
		exit;
	}

	$durum=$inovanceprint['siparis_durum'];

	$sil     = $db->prepare( "DELETE from siparis where siparis_id=:siparis_id" );
	$kontrol = $sil->execute(
		array(
			'siparis_id' => $siparis_id_get
		)
	);

	if ( $kontrol )
	{
		if ($popupJson) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('ok' => true, 'drm' => (int) $durum));
			exit;
		}
		Header( "Location:../siparisler.php?drm=$durum&status=ok" );
		exit;

	}
	else
	{
		if ($popupJson) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('ok' => false, 'error' => 'delete_failed', 'drm' => (int) $durum));
			exit;
		}
		Header( "Location:../siparisler.php?drm=$durum&status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'omenuduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$ayarkaydet = $db->prepare(
		"UPDATE omenu SET
		omenu_ad=:ad,
		omenu_sira=:sira,
		omenu_ust=:ust,
		omenu_link=:link
		WHERE omenu_id=:id"
	);
	$update     = $ayarkaydet->execute(
		array(
			'ad'     => $_POST[ 'omenu_ad' ],
			'sira'     => $_POST[ 'omenu_sira' ],
			'ust'     => $_POST[ 'omenu_ust' ],
			'link'     => $_POST[ 'omenu_link' ],
			'id'       => $_POST[ 'omenu_id' ]
		)
	);

	if ( $update )
	{ 

		Header( "Location:../menu.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../menu.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'smenuduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$ayarkaydet = $db->prepare(
		"UPDATE smenu SET
		smenu_ad=:ad,
		smenu_durum=:durum
		WHERE smenu_id=:id"
	);
	$update     = $ayarkaydet->execute(
		array(
			'ad'     => $_POST[ 'smenu_ad' ],
			'durum'  => $_POST[ 'smenu_durum' ],
			'id'     => $_POST[ 'smenu_id' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../menu.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../menu.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'flinkduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$ayarkaydet = $db->prepare(
		"UPDATE flink SET
		flink_ad=:ad,
		flink_link=:link
		WHERE flink_id=:id"
	);
	$update     = $ayarkaydet->execute(
		array(
			'ad'     => $_POST[ 'flink_ad' ],
			'link'   => $_POST[ 'flink_link' ],
			'id'     => $_POST[ 'flink_id' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../alt-link.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../alt-link.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'fmenuduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$ayarkaydet = $db->prepare(
		"UPDATE fmenu SET
		fmenu_ad=:ad,
		fmenu_link=:link,
		fmenu_sira=:sira
		WHERE fmenu_id=:id"
	);
	$update     = $ayarkaydet->execute(
		array(
			'ad'     => $_POST[ 'fmenu_ad' ],
			'sira'   => $_POST[ 'fmenu_sira' ],
			'link'   => $_POST[ 'fmenu_link' ],
			'id'     => $_POST[ 'fmenu_id' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../alt-menu.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../alt-menu.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'kargoduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	if ( $_FILES[ 'yorum_resim' ][ "size" ] > 0 )
	{

		$uploads_dir = '../assets/img/yorumlar';
		@$tmp_name = $_FILES[ 'yorum_resim' ][ "tmp_name" ];
		$benzersizsayi1 = rand( 20000, 32000 );
		$benzersizsayi2 = rand( 20000, 32000 );
		$uzanti = '.jpg';
		$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
		$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
		@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

		$duzenle = $db->prepare(
			"UPDATE kargo SET
			yorum_isim=:isim,
			yorum_link=:link,
			yorum_resim=:resim
			WHERE yorum_id=:id"
		);
		$update  = $duzenle->execute(
			array(
				'isim'  => $_POST[ 'yorum_isim' ],
				'link'  => $_POST[ 'yorum_link' ],
				'resim' => $refimgyol,
				'id'    => $_POST[ 'yorum_id' ]
			)
		);


		if ( $update )
		{

			$resimsilunlink = $_POST[ 'eski_yol' ];
			unlink( "../$resimsilunlink" );

			Header( "Location:../kargolar.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../kargolar.php?status=no" );
			exit;
		}
	}
	else
	{

		$duzenle = $db->prepare(
			"UPDATE kargo SET
			yorum_isim=:isim,
			yorum_link=:link
			WHERE yorum_id=:id"
		);
		$update  = $duzenle->execute(
			array(
				'isim'  => $_POST[ 'yorum_isim' ],
				'link'  => $_POST[ 'yorum_link' ],
				'id'    => $_POST[ 'yorum_id' ]
			)
		);

		if ( $update )
		{

			Header( "Location:../kargolar.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../kargolar.php?status=no" );
			exit;
		}
	}
}

if ( isset( $_POST[ 'referansduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}





	if ( $_FILES[ 'referans_resim1' ][ "size" ] > 0 )
	{

		$uploads_dir = '../assets/img/referanslar';
		@$tmp_name = $_FILES[ 'referans_resim1' ][ "tmp_name" ];
		$benzersizsayi1 = rand( 20000, 32000 );
		$benzersizsayi2 = rand( 20000, 32000 );
		$uzanti = '.jpg';
		$benzersizad    = $benzersizsayi1 . $benzersizsayi2;
		$refimgyol      = substr( $uploads_dir, 3 ) . "/" . $benzersizad . $uzanti;
		@move_uploaded_file( $tmp_name, "$uploads_dir/$benzersizad$uzanti" );

		$duzenle = $db->prepare(
			"UPDATE referanslar SET
			referans_adi=:adi,
			referans_kategori=:kategori,
			referans_link=:link,
			referans_resim1=:resim1
			WHERE referans_id=:id"
		);
		$update  = $duzenle->execute(
			array(
				'adi'      => $_POST[ 'referans_adi' ],
				'kategori' => $_POST[ 'referans_kategori' ],
				'link'     => $_POST[ 'referans_link' ],
				'resim1'   => $refimgyol,
				'id'       => $_POST[ 'referans_id' ]
			)
		);

		if ( $update )
		{

			$resimsilunlink = $_POST[ 'eski_yol1' ];
			unlink( "../$resimsilunlink" );

			Header( "Location:../referanslar.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../referanslar.php?status=no" );
			exit;
		}
	} else {
		$duzenle = $db->prepare(
			"UPDATE referanslar SET
			referans_adi=:adi,
			referans_kategori=:kategori,
			referans_link=:link
			WHERE referans_id=:id"
		);
		$update  = $duzenle->execute(
			array(
				'adi'      => $_POST[ 'referans_adi' ],
				'kategori' => $_POST[ 'referans_kategori' ],
				'link'     => $_POST[ 'referans_link' ],
				'id'       => $_POST[ 'referans_id' ]
			)
		);

		if ( $update )
		{


			Header( "Location:../referanslar.php?status=ok" );
			exit;
		}
		else
		{

			Header( "Location:../referanslar.php?status=no" );
			exit;
		}
	}
}  

if ( isset( $_POST[ 'odemeduzenle' ] ) )
{


	if (!$_SESSION[ 'kullanici_adi' ]) {
		header("Location: ../index.php?status=no" );
		exit();
	}




	$ayarkaydet = $db->prepare(
		"UPDATE odeme SET
		odeme_adi=:adi,
		odeme_not=:not,
		odeme_durum=:durum
		WHERE odeme_id=:id"
	);
	$update     = $ayarkaydet->execute(
		array(
			'adi'     => $_POST[ 'odeme_adi' ],
			'not'     => $_POST[ 'odeme_not' ],
			'durum'     => $_POST[ 'odeme_durum' ],
			'id'      => $_POST[ 'odeme_id' ]
		)
	);

	if ( $update )
	{

		Header( "Location:../odeme-yontemleri.php?status=ok" );
		exit;
	}
	else
	{

		Header( "Location:../odeme-yontemleri.php?status=no" );
		exit;
	}
}
if ( isset( $_POST[ 'telegram_guncelle' ] ) ) {
    if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: ../index.php?status=no" ); exit(); exit; }
    $tgkaydet = $db->prepare("UPDATE telegram SET bot_token=:token, chat_id=:chatid, durum=:durum WHERE id=1");
    $update = $tgkaydet->execute(array(
        'token' => $_POST['telegram_token'],
        'chatid' => $_POST['telegram_chatid'],
        'durum' => $_POST['telegram_durum']
    ));
    if ($update) { Header("Location:../telegram.php?status=ok"); exit; } else { Header("Location:../telegram.php?status=no"); exit; }
    exit;
}

// ÇARKIFELEK AYARLARI
if ( isset( $_POST[ 'carkifelek_guncelle' ] ) ) {
    if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: ../index.php?status=no" ); exit(); exit; }
    
    // Önce ayar tablosuna yeni sütunları ekle (varsa hata vermez)
    try {
        $db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_baslik VARCHAR(255) DEFAULT 'Çarkıfelek Çevir, İndirim Kazan!'");
    } catch(PDOException $e) {}
    try {
        $db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_aciklama TEXT");
    } catch(PDOException $e) {}
    try {
        $db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_renk1 VARCHAR(20) DEFAULT '#ff6b6b'");
    } catch(PDOException $e) {}
    try {
        $db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_renk2 VARCHAR(20) DEFAULT '#ee5a6f'");
    } catch(PDOException $e) {}
    try {
        $db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_oduller TEXT");
    } catch(PDOException $e) {}
    try {
        $db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_auto_on INT DEFAULT 1");
    } catch(PDOException $e) {}
    try {
        $db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_auto_sn INT DEFAULT 5");
    } catch(PDOException $e) {}
    try {
        $db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_zorla_ucretsiz_kargo INT(1) NOT NULL DEFAULT 0");
    } catch(PDOException $e) {}
    try {
        $db->query("ALTER TABLE ayar ADD COLUMN ayar_carkifelek_vurgu VARCHAR(20) DEFAULT '#fbbf24'");
    } catch(PDOException $e) {}
    
    // Ödülleri JSON formatına çevir
    $oduller_text = $_POST['carkifelek_oduller'];
    $oduller_lines = array_filter(array_map('trim', explode("\n", $oduller_text)));
    $oduller_json = json_encode($oduller_lines, JSON_UNESCAPED_UNICODE);

    $raw_cark_vurgu = isset($_POST['carkifelek_vurgu']) ? trim((string) $_POST['carkifelek_vurgu']) : '';
    if (!preg_match('/^#[0-9A-Fa-f]{3,8}$/', $raw_cark_vurgu)) {
        $raw_cark_vurgu = '#fbbf24';
    }
    
    $carkifelekkaydet = $db->prepare(
        "UPDATE ayar SET
        ayar_carkifelek_on=:durum,
        ayar_carkifelek_baslik=:baslik,
        ayar_carkifelek_aciklama=:aciklama,
        ayar_carkifelek_renk1=:renk1,
        ayar_carkifelek_renk2=:renk2,
        ayar_carkifelek_vurgu=:vurgu,
        ayar_carkifelek_oduller=:oduller,
        ayar_carkifelek_auto_on=:auto_on,
        ayar_carkifelek_auto_sn=:auto_sn,
        ayar_carkifelek_zorla_ucretsiz_kargo=:zorla_kargo
        WHERE ayar_id=0"
    );

    $cf_auto_on = 1;
    if (isset($_POST['ayar_carkifelek_auto_on'])) {
        $cf_auto_on = (int) $_POST['ayar_carkifelek_auto_on'];
    }
    $cf_auto_sn = 5;
    if (isset($_POST['ayar_carkifelek_auto_sn'])) {
        $cf_auto_sn = (int) $_POST['ayar_carkifelek_auto_sn'];
    }
    if ($cf_auto_sn < 0) {
        $cf_auto_sn = 0;
    }
    $cf_zorla_kargo = 0;
    if (isset($_POST['ayar_carkifelek_zorla_ucretsiz_kargo'])) {
        $cf_zorla_kargo = (int) $_POST['ayar_carkifelek_zorla_ucretsiz_kargo'];
    }

    $cf_params = array(
        'durum' => $_POST['carkifelek_durum'],
        'baslik' => htmlspecialchars(trim($_POST['carkifelek_baslik'])),
        'aciklama' => htmlspecialchars(trim($_POST['carkifelek_aciklama'])),
        'renk1' => htmlspecialchars(trim($_POST['carkifelek_renk1'])),
        'renk2' => htmlspecialchars(trim($_POST['carkifelek_renk2'])),
        'vurgu' => $raw_cark_vurgu,
        'oduller' => $oduller_json,
        'auto_on' => $cf_auto_on,
        'auto_sn' => $cf_auto_sn,
        'zorla_kargo' => $cf_zorla_kargo
    );
    $update = $carkifelekkaydet->execute($cf_params);

    if ($update) { 
        Header("Location:../carkifelek-ayarlari.php?status=ok"); 
        exit;
    } else { 
        Header("Location:../carkifelek-ayarlari.php?status=no"); exit; 
    }
    exit;
}

// ÇARKIFELEK LOG SIFIRLAMA
if (isset($_GET['carkifelek_log_sifirla']) && $_GET['carkifelek_log_sifirla'] == 1) {
    if (!$_SESSION['kullanici_adi']) { 
        header("Location: ../index.php?status=no"); 
        exit(); 
    }
    
    try {
        $db->query("TRUNCATE TABLE carkifelek_log");
        Header("Location:../carkifelek-log.php?status=sifirlandi");
        exit;
    } catch(PDOException $e) {
        Header("Location:../carkifelek-log.php?status=no"); exit;
    }
    exit;
}

// DEMO MODU AYARLARI
if ( isset( $_POST[ 'demoayar' ] ) )
{
	Header("Location:../genel-ayarlar.php?status=ok");
    exit;
}

if ( isset( $_POST[ 'cloakerduzenle' ] ) )
{
    if (!$_SESSION[ 'kullanici_adi' ]) {
        header("Location: ../index.php?status=no" );
        exit();
    }

    try {
        $columns = [
            'ayar_cloaker_on',
            'ayar_cloaker_method',
            'ayar_safe_page',
            'ayar_cloaker_key',
            'ayar_cloaker_blacklist',
            'ayar_cloaker_whitelist',
            'ayar_cloaker_stat_total',
            'ayar_cloaker_stat_passed',
            'ayar_cloaker_stat_blocked',
            'ayar_cloaker_dns_mode',
            'ayar_cloaker_js_mode',
            'ayar_cloaker_geoip_on',
            'ayar_cloaker_allowed_countries'
        ];
        foreach ($columns as $col) {
            $check = $db->query("SHOW COLUMNS FROM ayar LIKE '$col'");
            if ($check->rowCount() == 0) {
                if (strpos($col, 'stat') !== false) {
                    $type = 'INT DEFAULT 0';
                } elseif (strpos($col, 'list') !== false) {
                    $type = 'TEXT';
                } else {
                    $type = 'VARCHAR(255)';
                }
                $db->query("ALTER TABLE ayar ADD COLUMN $col $type DEFAULT NULL");
            }
        }
    } catch (PDOException $e) { }

    $ayarkaydet = $db->prepare(
        "UPDATE ayar SET
        ayar_cloaker_on=:ayar_cloaker_on,
        ayar_cloaker_method=:ayar_cloaker_method,
        ayar_safe_page=:ayar_safe_page,
        ayar_cloaker_key=:ayar_cloaker_key,
        ayar_cloaker_blacklist=:ayar_cloaker_blacklist,
        ayar_cloaker_whitelist=:ayar_cloaker_whitelist,
        ayar_cloaker_dns_mode=:ayar_cloaker_dns_mode,
        ayar_cloaker_js_mode=:ayar_cloaker_js_mode,
        ayar_cloaker_geoip_on=:ayar_cloaker_geoip_on,
        ayar_cloaker_allowed_countries=:ayar_cloaker_allowed_countries
        WHERE ayar_id=0"
    );

    $update = $ayarkaydet->execute(
        array(
            'ayar_cloaker_on' => $_POST['ayar_cloaker_on'],
            'ayar_cloaker_method' => $_POST['ayar_cloaker_method'],
            'ayar_safe_page' => $_POST['ayar_safe_page'],
            'ayar_cloaker_key' => $_POST['ayar_cloaker_key'],
            'ayar_cloaker_blacklist' => $_POST['ayar_cloaker_blacklist'],
            'ayar_cloaker_whitelist' => $_POST['ayar_cloaker_whitelist'],
            'ayar_cloaker_dns_mode' => $_POST['ayar_cloaker_dns_mode'],
            'ayar_cloaker_js_mode' => $_POST['ayar_cloaker_js_mode'],
            'ayar_cloaker_geoip_on' => $_POST['ayar_cloaker_geoip_on'],
            'ayar_cloaker_allowed_countries' => $_POST['ayar_cloaker_allowed_countries']
        )
    );

    if ($update) {
        Header("Location:../cloaker.php?status=ok");
        exit;
    } else {
        Header("Location:../cloaker.php?status=no"); exit;
    }
    exit;
}

// YÖNETİCİ LOGLARINI SIFIRLA
if (isset($_GET['admin_log_sifirla'])) {
    if (!$_SESSION['kullanici_adi']) { Header("Location:../login.php?status=no"); exit(); exit; }
    
    $sifirla = $db->query("TRUNCATE TABLE admin_log");
    if ($sifirla) {
        header("Location:../admin-loglar.php?status=sifirlandi");
        exit;
    } else {
        header("Location:../admin-loglar.php?status=no"); exit;
    }
    exit;
}
if (isset($_POST['formbasvurutoplu'])) {
    if (!$_SESSION['kullanici_adi']) { header("Location: ../index.php?status=no"); exit; }
    
    $form_id = $_POST['form_id'];
    $islem = $_POST['islem'];
    $ids = $_POST['id'];

    if (empty($ids) || empty($islem) && $islem !== "0") {
        header("Location:../form-basvurular.php?id=".$form_id."&status=bos");
        exit;
    }

    if ($islem == "sil") {
        foreach ($ids as $id) {
            $db->prepare("DELETE FROM form_degerler WHERE basvuru_id=:id")->execute(['id' => $id]);
            $db->prepare("DELETE FROM form_basvurular WHERE basvuru_id=:id")->execute(['id' => $id]);
        }
    } else {
        foreach ($ids as $id) {
            $db->prepare("UPDATE form_basvurular SET basvuru_durum=:durum WHERE basvuru_id=:id")
               ->execute(['durum' => $islem, 'id' => $id]);
        }
    }

    header("Location:../form-basvurular.php?id=".$form_id."&status=ok");
    exit;
}
?>

<?php
if ( isset( $_POST[ 'cloaker_traffic_toplu_sil' ] ) ) {
    if ( empty( $_SESSION[ 'kullanici_adi' ] ) ) {
        header( "Location: ../index.php?status=no" );
        exit();
    }
    $redir = isset( $_POST['cloaker_redirect_qs'] ) ? trim( (string) $_POST['cloaker_redirect_qs'] ) : '';
    $redir = preg_replace( '/[^a-zA-Z0-9_=&.-]/', '', $redir );
    $suffix = $redir !== '' ? ( '&' . $redir ) : '';

    $ids = isset( $_POST['log_id'] ) ? $_POST['log_id'] : array();
    if ( ! is_array( $ids ) ) {
        $ids = array( $ids );
    }
    $valid = array();
    foreach ( $ids as $raw ) {
        $id = (int) $raw;
        if ( $id > 0 ) {
            $valid[] = $id;
        }
    }
    if ( $valid === array() ) {
        Header( 'Location:../cloaker-traffic.php?durum=silme_bos' . $suffix );
        exit;
    }
    $valid = array_values( array_unique( $valid ) );
    $ph    = implode( ',', array_fill( 0, count( $valid ), '?' ) );
    $del   = $db->prepare( "DELETE FROM cloaker_traffic_log WHERE id IN ($ph)" );
    $del->execute( $valid );
    Header( 'Location:../cloaker-traffic.php?durum=silindi&silinen=' . count( $valid ) . $suffix );
    exit;
}

if ( isset( $_POST[ 'cloaker_traffic_tumunu_sil' ] ) ) {
    if ( empty( $_SESSION[ 'kullanici_adi' ] ) ) {
        header( "Location: ../index.php?status=no" );
        exit();
    }
    $redir = isset( $_POST['cloaker_redirect_qs'] ) ? trim( (string) $_POST['cloaker_redirect_qs'] ) : '';
    $redir = preg_replace( '/[^a-zA-Z0-9_=&.-]/', '', $redir );
    $suffix = $redir !== '' ? ( '&' . $redir ) : '';
    try {
        $n = (int) $db->query( 'SELECT COUNT(*) FROM cloaker_traffic_log' )->fetchColumn();
        $db->exec( 'DELETE FROM cloaker_traffic_log' );
        Header( 'Location:../cloaker-traffic.php?durum=tum_silindi&silinen=' . $n . $suffix );
    } catch ( Exception $e ) {
        Header( 'Location:../cloaker-traffic.php?durum=no' . $suffix );
    }
    exit;
}

if ( isset( $_POST[ 'cloaker_traffic_ip_ekle' ] ) )
{
    if ( empty( $_SESSION[ 'kullanici_adi' ] ) ) {
        header( "Location: ../index.php?status=no" );
        exit();
    }

    $ip = isset( $_POST[ 'ip' ] ) ? trim( (string) $_POST[ 'ip' ] ) : '';
    if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
        Header( "Location:../cloaker-traffic.php?durum=gecersiz_ip" );
        exit;
    }

    $mevcut = $db->prepare( "SELECT ayar_cloaker_blacklist FROM ayar WHERE ayar_id=0 LIMIT 1" );
    $mevcut->execute();
    $row  = $mevcut->fetch( PDO::FETCH_ASSOC );
    $bl   = isset( $row[ 'ayar_cloaker_blacklist' ] ) ? (string) $row[ 'ayar_cloaker_blacklist' ] : '';
    $satirlar = array();
    foreach ( preg_split( '/\r\n|\r|\n/', $bl ) as $ln ) {
        $ln = trim( $ln );
        if ( $ln !== '' && ( ! isset( $ln[0] ) || $ln[0] !== '#' ) ) {
            $satirlar[] = $ln;
        }
    }
    if ( in_array( $ip, $satirlar, true ) ) {
        Header( "Location:../cloaker-traffic.php?durum=zaten_var" );
        exit;
    }

    $yeni = ( trim( $bl ) === '' ) ? $ip : ( rtrim( $bl ) . "\n" . $ip );
    $guncelle = $db->prepare( "UPDATE ayar SET ayar_cloaker_blacklist=:bl WHERE ayar_id=0" );
    if ( $guncelle->execute( array( 'bl' => $yeni ) ) ) {
        Header( "Location:../cloaker-traffic.php?durum=eklendi" );
    } else {
        Header( "Location:../cloaker-traffic.php?durum=no" );
    }
    exit;
}

if ( isset( $_POST[ 'cloakersifirla' ] ) )
{
    if (!$_SESSION[ 'kullanici_adi' ]) {
        header("Location: ../index.php?status=no" );
        exit();
    }

    $sifirla = $db->prepare("UPDATE ayar SET ayar_cloaker_stat_total=0, ayar_cloaker_stat_passed=0, ayar_cloaker_stat_blocked=0 WHERE ayar_id=0");
    $update = $sifirla->execute();

    if ($update) {
        Header("Location:../cloaker.php?status=ok");
        exit;
    } else {
        Header("Location:../cloaker.php?status=no"); exit;
    }
    exit;
}

// FORM YÖNETİMİ İŞLEMLERİ
if (isset($_POST['formkaydet'])) {
    $slug = $_POST['form_slug'];
    if (empty($slug)) {
        $slug = seolink($_POST['form_baslik']);
    } else {
        $slug = seolink($slug);
    }

    $kaydet = $db->prepare("INSERT INTO formlar SET
        form_baslik=:baslik,
        form_slug=:slug,
        form_aciklama=:aciklama,
        form_durum=:durum,
        form_menu=:menu,
        form_sira=:sira
    ");
    $insert = $kaydet->execute(array(
        'baslik' => $_POST['form_baslik'],
        'slug' => $slug,
        'aciklama' => $_POST['form_aciklama'],
        'durum' => 1,
        'menu' => 0,
        'sira' => 0
    ));

    if ($insert) {
        header("Location:../form-yonetimi.php?durum=ok");
        exit;
    } else {
        header("Location:../form-yonetimi.php?durum=no"); exit;
    }
    exit;
}

if (isset($_POST['formguncelle'])) {
    $slug = $_POST['form_slug'];
    if (empty($slug)) {
        $slug = seolink($_POST['form_baslik']);
    } else {
        $slug = seolink($slug);
    }

    $kaydet = $db->prepare("UPDATE formlar SET
        form_baslik=:baslik,
        form_slug=:slug,
        form_aciklama=:aciklama,
        form_durum=:durum,
        form_menu=:menu,
        form_sira=:sira
        WHERE form_id=:id
    ");
    $update = $kaydet->execute(array(
        'baslik' => $_POST['form_baslik'],
        'slug' => $slug,
        'aciklama' => $_POST['form_aciklama'],
        'durum' => $_POST['form_durum'],
        'menu' => $_POST['form_menu'],
        'sira' => $_POST['form_sira'],
        'id' => $_POST['form_id']
    ));

    if ($update) {
        header("Location:../form-duzenle.php?id=".$_POST['form_id']."&durum=ok");
        exit;
    } else {
        header("Location:../form-duzenle.php?id=".$_POST['form_id']."&durum=no"); exit;
    }
    exit;
}

if (isset($_GET['formsil'])) {
    $form_id = $_GET['id'];
    
    // 1. Önce bu forma ait başvuruların ID'lerini çekelim
    $bsor = $db->prepare("SELECT basvuru_id FROM form_basvurular WHERE form_id=:id");
    $bsor->execute(array('id' => $form_id));
    
    // 2. Her başvuruya ait değerleri (cevapları) silelim
    while($bcek = $bsor->fetch(PDO::FETCH_ASSOC)) {
        $db->prepare("DELETE FROM form_degerler WHERE basvuru_id=:bid")->execute(array('bid' => $bcek['basvuru_id']));
    }
    
    // 3. Başvuruları silelim
    $db->prepare("DELETE FROM form_basvurular WHERE form_id=:id")->execute(array('id' => $form_id));

    // 4. Form alanlarını silelim
    $db->prepare("DELETE FROM form_alanlari WHERE form_id=:id")->execute(array('id' => $form_id));

    // 5. Formu silelim
    $sil = $db->prepare("DELETE FROM formlar WHERE form_id=:id");
    $kontrol = $sil->execute(array('id' => $form_id));

    if ($kontrol) {
        header("Location:../form-yonetimi.php?durum=ok");
        exit;
    } else {
        header("Location:../form-yonetimi.php?durum=no"); exit;
    }
    exit;
}

// FORM ALAN İŞLEMLERİ
if (isset($_POST['alankaydet'])) {
    $kaydet = $db->prepare("INSERT INTO form_alanlari SET
        form_id=:form_id,
        alan_baslik=:baslik,
        alan_tip=:tip,
        alan_secenekler=:secenekler,
        alan_zorunlu=:zorunlu,
        alan_sira=:sira
    ");
    $insert = $kaydet->execute(array(
        'form_id' => $_POST['form_id'],
        'baslik' => $_POST['alan_baslik'],
        'tip' => $_POST['alan_tip'],
        'secenekler' => $_POST['alan_secenekler'],
        'zorunlu' => $_POST['alan_zorunlu'],
        'sira' => $_POST['alan_sira']
    ));

    if ($insert) {
        header("Location:../form-duzenle.php?id=".$_POST['form_id']."&durum=ok");
        exit;
    } else {
        header("Location:../form-duzenle.php?id=".$_POST['form_id']."&durum=no"); exit;
    }
    exit;
}

// BAŞVURU SİLME İŞLEMİ
if (isset($_GET['basvurusil'])) {
    $basvuru_id = $_GET['id'];
    $form_id = $_GET['form_id'];

    // 1. Başvuruya ait değerleri silelim
    $d_sil = $db->prepare("DELETE FROM form_degerler WHERE basvuru_id=:id");
    $d_sil->execute(array('id' => $basvuru_id));

    // 2. Başvuruyu silelim
    $sil = $db->prepare("DELETE FROM form_basvurular WHERE basvuru_id=:id");
    $kontrol = $sil->execute(array('id' => $basvuru_id));

    if ($kontrol) {
        header("Location:../form-basvurular.php?id=".$form_id."&durum=ok");
        exit;
    } else {
        header("Location:../form-basvurular.php?id=".$form_id."&durum=no"); exit;
    }
    exit;
}


if (isset($_GET['alansil'])) {
    $sil = $db->prepare("DELETE FROM form_alanlari WHERE alan_id=:id");
    $kontrol = $sil->execute(array('id' => $_GET['id']));

    if ($kontrol) {
        header("Location:../form-duzenle.php?id=".$_GET['form_id']."&durum=ok");
        exit;
    } else {
        header("Location:../form-duzenle.php?id=".$_GET['form_id']."&durum=no"); exit;
    }
    exit;
}

// Hiçbir POST işleyicisi tetiklenmediyse tarayıcı boş function.php'de kalıyordu (PHP 8 uyarıları / yanlış action vb.)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !headers_sent()) {
	header('Location: ../index.php?status=no');
	exit;
}
?>
