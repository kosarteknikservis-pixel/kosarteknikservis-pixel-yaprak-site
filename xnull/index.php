<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

// Ziyaretçi sayacını sıfırlama
if (isset($_GET['hit_sifirla']) && $_GET['hit_sifirla'] == 'ok') {
	if (!$_SESSION['kullanici_adi']) {
		header("Location: index.php?status=no");
		exit();
	}
	$db->query("TRUNCATE TABLE hit");
	header("Location: index.php?status=ok");
	exit();
}

// ============================================
// GLOBAL FİLTRELEME PARAMETRELERİ
// ============================================
$tarih_baslangic = isset($_GET['tarih_baslangic']) ? $_GET['tarih_baslangic'] : '';
$tarih_bitis = isset($_GET['tarih_bitis']) ? $_GET['tarih_bitis'] : '';
$durum_filtre = isset($_GET['durum_filtre']) ? (int)$_GET['durum_filtre'] : -1; // -1 = Tümü
$sahte_durum_id = 18;

// Not İşlemleri
if (isset($_POST['not_ekle'])) {
    $icerik = $_POST['not_icerik']; // Emojiler için ham veri alalım, gösterirken koruruz
    if (!empty($icerik)) {
        $ekle = $db->prepare("INSERT INTO notlar SET icerik=:icerik");
        $ekle->execute(['icerik' => $icerik]);
    }
    header("Location: index.php?status=ok");
    exit();
}

if (isset($_GET['not_sil'])) {
    $sil = $db->prepare("DELETE FROM notlar WHERE id=:id");
    $sil->execute(['id' => $_GET['not_sil']]);
    header("Location: index.php?status=ok");
    exit();
}

if (isset($_GET['not_tamam'])) {
    $guncelle = $db->prepare("UPDATE notlar SET durum=1 WHERE id=:id");
    $guncelle->execute(['id' => $_GET['not_tamam']]);
    header("Location: index.php?status=ok");
    exit();
}

// Saydırma - Index dostu sorgular
$yenisiparis = $db->query("SELECT count(siparis_id) from siparis where siparis_durum=1")->fetchColumn();
$tumurunler = $db->query("SELECT count(urun_id) from urunler")->fetchColumn();
$tumsayfa = $db->query("SELECT count(id) from sayfalar")->fetchColumn();

// Son Siparişler - Limitli çekim
$siparissor=$db->prepare("SELECT * from siparis where siparis_durum=1 order by siparis_id DESC LIMIT 10");
$siparissor->execute();
?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->

<section class="main-content container">
	<!-- Global Analytics Filter -->
	<div class="row">
		<div class="col-md-12">
			<div class="card" style="margin-bottom: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
				<div class="card-block" style="padding: 15px 20px;">
					<form method="GET" action="" class="form-horizontal">
						<div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
							<div class="col-md-3 col-sm-6">
								<div style="display: flex; align-items: center; gap: 10px;">
									<i class="fa fa-calendar" style="color: #3498db;"></i>
									<div style="flex: 1;">
										<label style="font-size: 11px; font-weight: 700; color: #7f8c8d; margin-bottom: 2px; text-transform: uppercase;">Başlangıç</label>
										<input type="date" name="tarih_baslangic" value="<?php echo htmlspecialchars($tarih_baslangic); ?>" class="form-control" style="height: 35px; border-radius: 6px;">
									</div>
								</div>
							</div>
							<div class="col-md-3 col-sm-6">
								<div style="display: flex; align-items: center; gap: 10px;">
									<i class="fa fa-calendar" style="color: #3498db;"></i>
									<div style="flex: 1;">
										<label style="font-size: 11px; font-weight: 700; color: #7f8c8d; margin-bottom: 2px; text-transform: uppercase;">Bitiş</label>
										<input type="date" name="tarih_bitis" value="<?php echo htmlspecialchars($tarih_bitis); ?>" class="form-control" style="height: 35px; border-radius: 6px;">
									</div>
								</div>
							</div>
							<div class="col-md-3 col-sm-6">
								<div style="display: flex; align-items: center; gap: 10px;">
									<i class="fa fa-tag" style="color: #3498db;"></i>
									<div style="flex: 1;">
										<label style="font-size: 11px; font-weight: 700; color: #7f8c8d; margin-bottom: 2px; text-transform: uppercase;">Durum</label>
										<select name="durum_filtre" class="form-control" style="height: 35px; border-radius: 6px;">
											<option value="-1" <?php echo $durum_filtre == -1 ? 'selected' : ''; ?>>Tüm Durumlar</option>
											<?php 
											$durumlar_f = $db->query("SELECT * FROM durum ORDER BY id ASC");
											while($df = $durumlar_f->fetch(PDO::FETCH_ASSOC)) { 
												if ($sahte_durum_id !== null && $df['id'] == $sahte_durum_id) continue;
											?>
												<option value="<?php echo $df['id']; ?>" <?php echo $durum_filtre == $df['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($df['ad']); ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
							</div>
							<div class="col-md-3 col-sm-6" style="display: flex; gap: 10px; margin-top: 15px;">
								<button type="submit" class="btn btn-primary" style="flex: 1; border-radius: 6px; font-weight: 700;"><i class="fa fa-filter"></i> FİLTRELE</button>
								<a href="index.php" class="btn btn-default" style="border-radius: 6px;" title="Sıfırla"><i class="fa fa-refresh"></i></a>
							</div>
						</div>
						<div style="margin-top: 15px; padding-top: 12px; border-top: 1px solid #f1f2f6; display: flex; align-items: center; gap: 10px;">
							<span class="badge badge-info" style="background: rgba(52, 152, 219, 0.1); color: #3498db; border: none; padding: 5px 10px; border-radius: 4px; font-weight: 600; font-size: 10px;">BİLGİ</span>
							<span style="font-size: 12px; color: #7f8c8d;">
								Bu filtre; <strong>Dönüşüm Oranı</strong>, <strong>Ziyaretçi Sayıları</strong>, <strong>Ciro Analitiği</strong>, <strong>Sipariş İstatistikleri</strong> ve <strong>Grafikleri</strong> seçilen tarihe göre günceller.
							</span>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		
		<?php 

		$bugun=date("d"); // bugünün tarihi 
 	$ay=date("m"); // bu ay
 	$yil=date("Y"); // bu yıl 
	$onlineSuresi=time()-2*60; // iki dakika aktif olmazsa onlineden düşecek
 	$ip=$_SERVER['REMOTE_ADDR']; // ziyaretçinin ip si 
 	
 	// Admin paneli ziyaretlerini sayma - xnull klasöründeki ziyaretleri hariç tut
 	$is_admin_panel = (strpos($_SERVER['REQUEST_URI'], '/xnull/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/xnull/') !== false);
 	
	if (!$is_admin_panel) {
		// SQL Injection koruması: Prepared statements kullan
		$bugunGirisSor = $db->prepare("SELECT * FROM hit WHERE ip=:ip AND gun=:gun AND ay=:ay AND yil=:yil");
		$bugunGirisSor->execute(array('ip' => $ip, 'gun' => $bugun, 'ay' => $ay, 'yil' => $yil));
		$bugunGiris = $bugunGirisSor->rowCount();
		
		if($bugunGiris!=0){ // yani bugün girilmişse
			$al = $bugunGirisSor->fetch(PDO::FETCH_ASSOC);
			$guncelle = $db->prepare("UPDATE hit SET sayac=:sayac, simdi=:simdi WHERE id=:id");
			$guncelle->execute(array('sayac' => ($al['sayac']+1), 'simdi' => time(), 'id' => $al['id']));
		}else{ // griş yapılmamışsa kaydettirelim
			$ekle = $db->prepare("INSERT INTO hit SET gun=:gun, ay=:ay, yil=:yil, simdi=:simdi, sayac=:sayac, ip=:ip");
			$ekle->execute(array(
				'gun' => $bugun,
				'ay' => $ay,
				'yil' => $yil,
				'simdi' => time(),
				'sayac' => 1,
				'ip' => $ip
			));
		}
	}
 	
 	// Admin paneli ziyaretlerini hariç tutarak hesapla (xnull klasöründeki ziyaretler sayılmayacak)
 	// Admin paneli IP'lerini tespit etmek için referrer veya URL kontrolü yapıyoruz
 	// Basit çözüm: hit tablosuna admin_panel sütunu ekleyip kontrol edebiliriz, ama şimdilik sadece saymayalım
 	
	// evet sıra geldi online, tekil ve çoğulu Göstermeye
	// online Kişi (admin paneli hariç)
	$onlineSor = $db->prepare("SELECT * FROM hit WHERE simdi>=:onlineSuresi");
	$onlineSor->execute(array('onlineSuresi' => $onlineSuresi));
	$online = $onlineSor->rowCount(); // online kişilerimiz
	

	// çoğul hitler (admin paneli hariç) - Prepared statements ile güvenli hale getirildi
	$bugunxSor = $db->prepare("SELECT SUM(sayac) as toplam FROM hit WHERE gun=:gun AND ay=:ay AND yil=:yil");
	$bugunxSor->execute(array('gun' => $bugun, 'ay' => $ay, 'yil' => $yil));
	$bugunx = $bugunxSor->fetch(PDO::FETCH_ASSOC);
	$bugun_cogul = isset($bugunx['toplam']) && $bugunx['toplam'] ? $bugunx['toplam'] : 0; // bugün çoğul
	
	$dun_ts = strtotime('yesterday');
	$dunGun = date('d', $dun_ts);
	$dunAy = date('m', $dun_ts);
	$dunYil = date('Y', $dun_ts);
	$dunxSor = $db->prepare("SELECT SUM(sayac) as toplam FROM hit WHERE gun=:gun AND ay=:ay AND yil=:yil");
	$dunxSor->execute(array('gun' => $dunGun, 'ay' => $dunAy, 'yil' => $dunYil));
	$dunx = $dunxSor->fetch(PDO::FETCH_ASSOC);
	$dun_cogul = isset($dunx['toplam']) && $dunx['toplam'] ? $dunx['toplam'] : 0; // dün Çoğul 
	
	$ayxSor = $db->prepare("SELECT SUM(sayac) as toplam FROM hit WHERE ay=:ay AND yil=:yil");
	$ayxSor->execute(array('ay' => $ay, 'yil' => $yil));
	$ayx = $ayxSor->fetch(PDO::FETCH_ASSOC);
	$buay_cogul = isset($ayx['toplam']) && $ayx['toplam'] ? $ayx['toplam'] : 0; // bu ay çoğul
	
	$toplamxSor = $db->query("SELECT SUM(sayac) as toplam FROM hit");
	$toplamx = $toplamxSor->fetch(PDO::FETCH_ASSOC);
	$toplam_cogul = isset($toplamx['toplam']) && $toplamx['toplam'] ? $toplamx['toplam'] : 0; // toplam çoğulumuz
	
	// tekil hitler (admin paneli hariç) - Prepared statements ile güvenli hale getirildi
	$bugun_tekilSor = $db->prepare("SELECT * FROM hit WHERE gun=:gun AND ay=:ay AND yil=:yil");
	$bugun_tekilSor->execute(array('gun' => $bugun, 'ay' => $ay, 'yil' => $yil));
	$bugun_tekil = $bugun_tekilSor->rowCount(); // bugün tekil


	// Filtreleme WHERE koşulu
	$where_conditions = array();
	$where_params = array();

	// Sahte durumunu genelde hariç tut (ciro hesaplamaları vb için)
	$where_conditions[] = "siparis_durum != :sahte_durum";
	$where_params['sahte_durum'] = $sahte_durum_id;

	if (!empty($tarih_baslangic)) {
		$where_conditions[] = "DATE(siparis_tarih) >= :tarih_baslangic";
		$where_params['tarih_baslangic'] = $tarih_baslangic;
	}
	if (!empty($tarih_bitis)) {
		$where_conditions[] = "DATE(siparis_tarih) <= :tarih_bitis";
		$where_params['tarih_bitis'] = $tarih_bitis;
	}
	if ($durum_filtre >= 0) {
		$where_conditions[] = "siparis_durum = :durum_filtre";
		$where_params['durum_filtre'] = $durum_filtre;
	}
	$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

	// FİLTRELİ ZİYARETÇİ HESAPLAMALARI
	$filtre_aktif = (!empty($tarih_baslangic) || !empty($tarih_bitis));
	$filtreli_tekil = 0;
	$filtreli_cogul = 0;

	if ($filtre_aktif) {
		$hit_where = "WHERE 1=1";
		$hit_params = array();
		if (!empty($tarih_baslangic)) {
			$hit_where .= " AND simdi >= :hit_start";
			$hit_params['hit_start'] = strtotime($tarih_baslangic . " 00:00:00");
		}
		if (!empty($tarih_bitis)) {
			$hit_where .= " AND simdi <= :hit_end";
			$hit_params['hit_end'] = strtotime($tarih_bitis . " 23:59:59");
		}
		
		$f_tekil_sor = $db->prepare("SELECT COUNT(*) FROM hit " . $hit_where);
		$f_tekil_sor->execute($hit_params);
		$filtreli_tekil = $f_tekil_sor->fetchColumn();

		$f_cogul_sor = $db->prepare("SELECT SUM(sayac) FROM hit " . $hit_where);
		$f_cogul_sor->execute($hit_params);
		$filtreli_cogul = $f_cogul_sor->fetchColumn();
		if (!$filtreli_cogul) $filtreli_cogul = 0;
	}
	
	$dun_tekilSor = $db->prepare("SELECT * FROM hit WHERE gun=:gun AND ay=:ay AND yil=:yil");
	$dun_tekilSor->execute(array('gun' => $dunGun, 'ay' => $dunAy, 'yil' => $dunYil));
	$dun_tekil = $dun_tekilSor->rowCount(); // dün tekil
	
	$buay_tekilSor = $db->prepare("SELECT * FROM hit WHERE ay=:ay AND yil=:yil");
	$buay_tekilSor->execute(array('ay' => $ay, 'yil' => $yil));
	$buay_tekil = $buay_tekilSor->rowCount(); // bu ay tekil
	
	$toplam_tekilSor = $db->query("SELECT * FROM hit");
	$toplam_tekil = $toplam_tekilSor->rowCount(); // toplam tekil
	
	// ============================================
	// GRAFİK VERİLERİ HAZIRLAMA (Filtreli)
	// ============================================
	// 1. Şehirlere Göre Dağılım (Top 5)
	$sehir_istatistik_sor = $db->prepare("SELECT siparis_il, COUNT(*) as adet FROM siparis " . $where_clause . " GROUP BY siparis_il ORDER BY adet DESC LIMIT 5");
	$sehir_istatistik_sor->execute($where_params);
	$sehir_istatistik = $sehir_istatistik_sor->fetchAll(PDO::FETCH_ASSOC);
	
	// 2. Saatlik Sipariş Dağılımı
	$saatlik_dagilim_sor = $db->prepare("SELECT HOUR(siparis_tarih) as saat, COUNT(*) as adet FROM siparis " . $where_clause . " GROUP BY HOUR(siparis_tarih) ORDER BY saat ASC");
	$saatlik_dagilim_sor->execute($where_params);
	$saatlik_dagilim = $saatlik_dagilim_sor->fetchAll(PDO::FETCH_ASSOC);
	
	$saat_labels = []; $saat_values = [];
	for($i=0; $i<24; $i++) {
		$saat_labels[] = ($i < 10 ? "0$i" : $i) . ":00";
		$found = false;
		foreach($saatlik_dagilim as $sd) {
			if($sd['saat'] == $i) { $saat_values[] = $sd['adet']; $found = true; break; }
		}
		if(!$found) $saat_values[] = 0;
	}
	
	
	$saat = date("H"); // Şu anki saat
	$gun = date("d"); // Bugün
	$hafta_baslangic = date("Y-m-d", strtotime('monday this week')); // Bu haftanın başlangıcı
	$ay_baslangic = date("Y-m-01"); // Bu ayın başlangıcı
	
	// Filtreli / toplam ciro (aynı WHERE — tek sorgu)
	$ciro_sor = $db->prepare("SELECT SUM(siparis_fiyat) as toplam FROM siparis " . $where_clause);
	$ciro_sor->execute($where_params);
	$ciro_data = $ciro_sor->fetch(PDO::FETCH_ASSOC);
	$filtreli_ciro = isset($ciro_data['toplam']) && $ciro_data['toplam'] ? (float)$ciro_data['toplam'] : 0;
	$toplam_ciro_tutar = $filtreli_ciro;
	
	// Saatlik Ciro (Bugün, bu saat) - Filtre uygulanmaz
	$saatlik_where = "WHERE DATE(siparis_tarih) = CURDATE() AND HOUR(siparis_tarih) = :saat";
	if ($sahte_durum_id !== null) {
		$saatlik_where .= " AND siparis_durum != :sahte_durum_saat";
	}
	if ($durum_filtre >= 0) {
		$saatlik_where .= " AND siparis_durum = :durum_saat";
	}
	$saatlik_ciro_sor = $db->prepare("SELECT SUM(siparis_fiyat) as toplam FROM siparis " . $saatlik_where);
	$saatlik_params = array('saat' => $saat);
	if ($sahte_durum_id !== null) {
		$saatlik_params['sahte_durum_saat'] = $sahte_durum_id;
	}
	if ($durum_filtre >= 0) {
		$saatlik_params['durum_saat'] = $durum_filtre;
	}
	$saatlik_ciro_sor->execute($saatlik_params);
	$saatlik_ciro = $saatlik_ciro_sor->fetch(PDO::FETCH_ASSOC);
	$saatlik_ciro_tutar = isset($saatlik_ciro['toplam']) && $saatlik_ciro['toplam'] ? (float)$saatlik_ciro['toplam'] : 0;
	
	// Günlük Ciro (Bugün)
	$gunluk_where = "WHERE DATE(siparis_tarih) = CURDATE()";
	if ($sahte_durum_id !== null) {
		$gunluk_where .= " AND siparis_durum != :sahte_durum_gun";
	}
	if ($durum_filtre >= 0) {
		$gunluk_where .= " AND siparis_durum = :durum_gun";
	}
	$gunluk_ciro_sor = $db->prepare("SELECT SUM(siparis_fiyat) as toplam FROM siparis " . $gunluk_where);
	$gunluk_params = array();
	if ($sahte_durum_id !== null) {
		$gunluk_params['sahte_durum_gun'] = $sahte_durum_id;
	}
	if ($durum_filtre >= 0) {
		$gunluk_params['durum_gun'] = $durum_filtre;
	}
	$gunluk_ciro_sor->execute($gunluk_params);
	$gunluk_ciro = $gunluk_ciro_sor->fetch(PDO::FETCH_ASSOC);
	$gunluk_ciro_tutar = isset($gunluk_ciro['toplam']) && $gunluk_ciro['toplam'] ? (float)$gunluk_ciro['toplam'] : 0;
	
	// Haftalık Ciro (Bu hafta)
	$haftalik_where = "WHERE DATE(siparis_tarih) >= :hafta_baslangic";
	$haftalik_params = array('hafta_baslangic' => $hafta_baslangic);
	if ($sahte_durum_id !== null) {
		$haftalik_where .= " AND siparis_durum != :sahte_durum_hafta";
		$haftalik_params['sahte_durum_hafta'] = $sahte_durum_id;
	}
	if ($durum_filtre >= 0) {
		$haftalik_where .= " AND siparis_durum = :durum_hafta";
		$haftalik_params['durum_hafta'] = $durum_filtre;
	}
	$haftalik_ciro_sor = $db->prepare("SELECT SUM(siparis_fiyat) as toplam FROM siparis " . $haftalik_where);
	$haftalik_ciro_sor->execute($haftalik_params);
	$haftalik_ciro = $haftalik_ciro_sor->fetch(PDO::FETCH_ASSOC);
	$haftalik_ciro_tutar = isset($haftalik_ciro['toplam']) && $haftalik_ciro['toplam'] ? (float)$haftalik_ciro['toplam'] : 0;
	
	// Aylık Ciro (Bu ay)
	$aylik_where = "WHERE MONTH(siparis_tarih) = :ay AND YEAR(siparis_tarih) = :yil";
	$aylik_params = array('ay' => $ay, 'yil' => $yil);
	if ($sahte_durum_id !== null) {
		$aylik_where .= " AND siparis_durum != :sahte_durum_ay";
		$aylik_params['sahte_durum_ay'] = $sahte_durum_id;
	}
	if ($durum_filtre >= 0) {
		$aylik_where .= " AND siparis_durum = :durum_ay";
		$aylik_params['durum_ay'] = $durum_filtre;
	}
	$aylik_ciro_sor = $db->prepare("SELECT SUM(siparis_fiyat) as toplam FROM siparis " . $aylik_where);
	$aylik_ciro_sor->execute($aylik_params);
	$aylik_ciro = $aylik_ciro_sor->fetch(PDO::FETCH_ASSOC);
	$aylik_ciro_tutar = isset($aylik_ciro['toplam']) && $aylik_ciro['toplam'] ? (float)$aylik_ciro['toplam'] : 0;
	
	// ============================================
	// SİPARİŞ SAYISI HESAPLAMALARI
	// ============================================
	
	// Filtreli Sipariş Sayısı
	$siparis_sayisi_sor = $db->prepare("SELECT COUNT(*) as toplam FROM siparis " . $where_clause);
	$siparis_sayisi_sor->execute($where_params);
	$siparis_sayisi_data = $siparis_sayisi_sor->fetch(PDO::FETCH_ASSOC);
	$filtreli_siparis_sayisi = isset($siparis_sayisi_data['toplam']) ? (int)$siparis_sayisi_data['toplam'] : 0;
	
	// Saatlik Sipariş Sayısı (Bugün, bu saat)
	$saatlik_siparis_sor = $db->prepare("SELECT COUNT(*) as toplam FROM siparis " . $saatlik_where);
	$saatlik_siparis_sor->execute($saatlik_params);
	$saatlik_siparis = $saatlik_siparis_sor->fetch(PDO::FETCH_ASSOC);
	$saatlik_siparis_sayisi = isset($saatlik_siparis['toplam']) ? (int)$saatlik_siparis['toplam'] : 0;
	
	// Günlük Sipariş Sayısı (Bugün)
	$gunluk_siparis_sor = $db->prepare("SELECT COUNT(*) as toplam FROM siparis " . $gunluk_where);
	$gunluk_siparis_sor->execute($gunluk_params);
	$gunluk_siparis = $gunluk_siparis_sor->fetch(PDO::FETCH_ASSOC);
	$gunluk_siparis_sayisi = isset($gunluk_siparis['toplam']) ? (int)$gunluk_siparis['toplam'] : 0;
	
	// Haftalık Sipariş Sayısı (Bu hafta)
	$haftalik_siparis_sor = $db->prepare("SELECT COUNT(*) as toplam FROM siparis " . $haftalik_where);
	$haftalik_siparis_sor->execute($haftalik_params);
	$haftalik_siparis = $haftalik_siparis_sor->fetch(PDO::FETCH_ASSOC);
	$haftalik_siparis_sayisi = isset($haftalik_siparis['toplam']) ? (int)$haftalik_siparis['toplam'] : 0;
	
	// Aylık Sipariş Sayısı (Bu ay)
	$aylik_siparis_sor = $db->prepare("SELECT COUNT(*) as toplam FROM siparis " . $aylik_where);
	$aylik_siparis_sor->execute($aylik_params);
	$aylik_siparis = $aylik_siparis_sor->fetch(PDO::FETCH_ASSOC);
	$aylik_siparis_sayisi = isset($aylik_siparis['toplam']) ? (int)$aylik_siparis['toplam'] : 0;
	
	// Toplam Sipariş Sayısı (Filtreli veya Tüm zamanlar)
	$toplam_siparis_sor = $db->prepare("SELECT COUNT(*) as toplam FROM siparis " . $where_clause);
	$toplam_siparis_sor->execute($where_params);
	$toplam_siparis = $toplam_siparis_sor->fetch(PDO::FETCH_ASSOC);
	$toplam_siparis_sayisi = isset($toplam_siparis['toplam']) ? (int)$toplam_siparis['toplam'] : 0;
	
	// ============================================
	// SAHTE SİPARİŞ HESAPLAMALARI
	// ============================================
	$sahte_siparis_sayisi = 0;
	$sahte_siparis_ciro = 0;
	
	if ($sahte_durum_id !== null) {
		// Sahte sipariş sayısı
		$sahte_siparis_sayisi_sor = $db->prepare("SELECT COUNT(*) as toplam FROM siparis WHERE siparis_durum = :sahte_durum");
		$sahte_siparis_sayisi_sor->execute(array('sahte_durum' => $sahte_durum_id));
		$sahte_siparis_sayisi_data = $sahte_siparis_sayisi_sor->fetch(PDO::FETCH_ASSOC);
		$sahte_siparis_sayisi = isset($sahte_siparis_sayisi_data['toplam']) ? (int)$sahte_siparis_sayisi_data['toplam'] : 0;
		
		// Sahte sipariş ciro
		$sahte_siparis_ciro_sor = $db->prepare("SELECT SUM(siparis_fiyat) as toplam FROM siparis WHERE siparis_durum = :sahte_durum");
		$sahte_siparis_ciro_sor->execute(array('sahte_durum' => $sahte_durum_id));
		$sahte_siparis_ciro_data = $sahte_siparis_ciro_sor->fetch(PDO::FETCH_ASSOC);
		$sahte_siparis_ciro = isset($sahte_siparis_ciro_data['toplam']) && $sahte_siparis_ciro_data['toplam'] ? (float)$sahte_siparis_ciro_data['toplam'] : 0;
	}
	
	// ============================================
	// DÖNÜŞÜM ANALİTİĞİ (CR) HESAPLAMALARI
	// ============================================
	$bugun_cr = ($bugun_tekil > 0) ? ($gunluk_siparis_sayisi / $bugun_tekil) * 100 : 0;
	$aylik_cr = ($buay_tekil > 0) ? ($aylik_siparis_sayisi / $buay_tekil) * 100 : 0;
	$toplam_cr = ($toplam_tekil > 0) ? ($toplam_siparis_sayisi / $toplam_tekil) * 100 : 0;
	
	$filtreli_cr = 0;
	if ($filtre_aktif && $filtreli_tekil > 0) {
		$filtreli_cr = ($filtreli_siparis_sayisi / $filtreli_tekil) * 100;
	}

	// Duruma Göre Ciro (Filtreli) - Sahte durumu hariç
	$durum_ciro = array();
	$durumlar_sor = $db->query("SELECT * FROM durum ORDER BY id ASC");
	while ($durum_row = $durumlar_sor->fetch(PDO::FETCH_ASSOC)) {
		if ($sahte_durum_id !== null && $durum_row['id'] == $sahte_durum_id) continue;
		$durum_where = "WHERE siparis_durum = :durum";
		$durum_params = array('durum' => $durum_row['id']);
		if (!empty($tarih_baslangic)) { $durum_where .= " AND DATE(siparis_tarih) >= :tarih_baslangic"; $durum_params['tarih_baslangic'] = $tarih_baslangic; }
		if (!empty($tarih_bitis)) { $durum_where .= " AND DATE(siparis_tarih) <= :tarih_bitis"; $durum_params['tarih_bitis'] = $tarih_bitis; }
		if ($durum_filtre >= 0) {
			$durum_where .= " AND siparis_durum = :durum_filtre_dagilim";
			$durum_params['durum_filtre_dagilim'] = $durum_filtre;
		}
		$durum_ciro_sor = $db->prepare("SELECT SUM(siparis_fiyat) as toplam FROM siparis " . $durum_where);
		$durum_ciro_sor->execute($durum_params);
		$durum_ciro_data = $durum_ciro_sor->fetch(PDO::FETCH_ASSOC);
		$durum_ciro[$durum_row['id']] = array('ad' => $durum_row['ad'], 'tutar' => isset($durum_ciro_data['toplam']) && $durum_ciro_data['toplam'] ? (float)$durum_ciro_data['toplam'] : 0);
	}
	// siparis_durum=0 "yeni gelen" — çoğu kurulumda `durum` tablosunda id=0 satırı yok; ciroyu kaçırıyordu
	if (!array_key_exists(0, $durum_ciro) && ($durum_filtre < 0 || $durum_filtre === 0)) {
		$yeni_where = "WHERE siparis_durum = 0";
		$yeni_params = array();
		if (!empty($tarih_baslangic)) {
			$yeni_where .= " AND DATE(siparis_tarih) >= :tarih_baslangic_yeni";
			$yeni_params['tarih_baslangic_yeni'] = $tarih_baslangic;
		}
		if (!empty($tarih_bitis)) {
			$yeni_where .= " AND DATE(siparis_tarih) <= :tarih_bitis_yeni";
			$yeni_params['tarih_bitis_yeni'] = $tarih_bitis;
		}
		$yeni_ciro_sor = $db->prepare("SELECT SUM(siparis_fiyat) as toplam FROM siparis " . $yeni_where);
		$yeni_ciro_sor->execute($yeni_params);
		$yeni_ciro_data = $yeni_ciro_sor->fetch(PDO::FETCH_ASSOC);
		$yeni_tutar = isset($yeni_ciro_data['toplam']) && $yeni_ciro_data['toplam'] ? (float)$yeni_ciro_data['toplam'] : 0;
		$durum_ciro = array(0 => array('ad' => 'Yeni Gelen Siparişler', 'tutar' => $yeni_tutar)) + $durum_ciro;
	}
	?>

    <!-- Dönüşüm Analitiği Özeti -->

	<div class="col-md-3">
		<div class="widget widget-chart white-bg padding-0">
			<div class="widget-title">
				<span class="label label-success pull-right">CANLI</span>
				<h2 class="margin-b-0"><i class="fa fa-users"></i>  Online</h2>
			</div>
			<div class="widget-content">
				<h3 class="margin-b-10 text-success"><b><?php echo $online; ?></b> <small class="text-muted">ZİYARETÇİ</small></h3>
				<h3 class="text-muted margin-b-0"><small class="text-muted">TEKİL ZİYARETÇİ</small></h3>                            
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="widget widget-chart white-bg padding-0">
			<div class="widget-title">
				<span class="label label-primary pull-right">BUGÜN</span>
				<h2 class="margin-b-0"><i class="fa fa-users"></i>  Günlük</h2>
			</div>
			<div class="widget-content">
				<h3 class="margin-b-10  text-primary"><b><?php echo $bugun_tekil ?></b> <small class="text-muted">ZİYARETÇİ</small></h3>
				<h3 class="margin-b-10  text-primary"><b><?php echo $bugun_cogul ?></b> <small class="text-muted">GÖSTERİM</small></h3>                           
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="widget widget-chart white-bg padding-0">
			<div class="widget-title">
				<span class="label label-warning pull-right">AY</span>
				<h2 class="margin-b-0"><i class="fa fa-users"></i>  AYLIK</h2>
			</div>
			<div class="widget-content">
				<h3 class="margin-b-10 text-warning"><b><?php echo $buay_tekil ?></b> <small class="text-muted">ZİYARETÇİ</small></h3>
				<h3 class="margin-b-10 text-warning"><b><?php echo $buay_cogul ?></b> <small class="text-muted">GÖSTERİM</small></h3>                            
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="widget widget-chart white-bg padding-0">
			<div class="widget-title">
				<span class="label label-info pull-right">TOPLAM</span>
				<h2 class="margin-b-0"><i class="fa fa-globe"></i> Toplam Ziyaretçi</h2>
			</div>
			<div class="widget-content" style="height: auto;">
				<h3 class="margin-b-10 text-info"><b><?php echo $toplam_tekil; ?></b> <small class="text-muted">TEKİL</small></h3>
				<h3 class="margin-b-10 text-info"><b><?php echo $toplam_cogul; ?></b> <small class="text-muted">GÖSTERİM</small></h3>
				<?php if (isset($_SESSION['kullanici_adi'])) { // Sadece adminler görebilir ?>
				<div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e0e0e0;">
					<a href="?hit_sifirla=ok" class="btn btn-xs btn-danger" onclick="return confirm('Tüm ziyaretçi sayacını sıfırlamak istediğinize emin misiniz? Bu işlem geri alınamaz!');" style="width: 100%;">
						<i class="fa fa-refresh"></i> Sayacı Sıfırla
					</a>
				</div>
				<?php } ?>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="widget widget-chart white-bg padding-0">
			<div class="widget-title">
				<span class="label label-danger pull-right">ÜRETİM</span>
				<h2 class="margin-b-0"><i class="fa fa-shopping-basket"></i> Ürünler</h2>
			</div>
			<div class="widget-content">
				<h3 class="margin-b-10 text-danger"><b><?php echo $tumurunler; ?></b> <small class="text-muted">TOPLAM ÜRÜN</small></h3>
				<h3 class="text-muted margin-b-0"><small class="text-muted">AKTİF SATIŞTA</small></h3>                            
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="widget widget-chart white-bg padding-0">
			<div class="widget-title">
				<span class="label label-info pull-right">İÇERİK</span>
				<h2 class="margin-b-0"><i class="fa fa-file-text-o"></i> Sayfalar</h2>
			</div>
			<div class="widget-content">
				<h3 class="margin-b-10 text-info"><b><?php echo (int) $tumsayfa; ?></b> <small class="text-muted">SAYFA</small></h3>
				<h3 class="text-muted margin-b-0"><small class="text-muted">SİTE SAYFALARI</small></h3>
			</div>
		</div>
	</div>

	<!-- Hızlı Notlar & Görevler -->
	<div class="row" style="margin-top: 20px;">
		<div class="col-md-12">
			<div class="card" style="border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
				<div class="card-heading card-default" style="display: flex; justify-content: space-between; align-items: center; background: #fff; border-radius: 15px 15px 0 0; padding: 15px 20px;">
					<span style="font-weight: 700; color: #2c3e50; font-size: 16px;"><i class="fa fa-pencil-square-o" style="color: #3498db; margin-right: 8px;"></i> Hızlı Notlar & Görevler</span>
					<a href="notlar.php" class="btn btn-sm btn-info" style="border-radius: 20px; font-weight: 600; padding: 5px 15px;"><i class="fa fa-external-link"></i> Tümünü Yönet</a>
				</div>
				<div class="card-block" style="padding: 20px;">
					<div class="row">
						<!-- Not Ekleme Formu -->
						<div class="col-md-4" style="border-right: 1px solid #eee;">
							<form method="POST" action="">
								<div class="form-group" style="position: relative;">
									<textarea name="not_icerik" id="not_icerik" class="form-control" rows="4" style="border-radius: 10px; border: 1px solid #dfe6e9; background: #fcfcfc; resize: none; padding: 12px;" placeholder="Notunuzu buraya yazın..." required></textarea>
									<div style="margin-top: 8px; display: flex; gap: 5px; flex-wrap: wrap;">
										<?php 
										$emojis = ['😊', '👍', '🔥', '✅', '❌', '📢', '💰', '📦', '🚚'];
										foreach($emojis as $emoji) {
											echo '<span class="emoji-btn" onclick="addEmoji(\''.$emoji.'\')" style="cursor: pointer; font-size: 18px; padding: 2px 5px; background: #f1f2f6; border-radius: 4px; transition: all 0.2s;">'.$emoji.'</span>';
										}
										?>
									</div>
								</div>
								<button type="submit" name="not_ekle" class="btn btn-primary btn-block" style="border-radius: 10px; font-weight: 700; padding: 10px;"><i class="fa fa-plus"></i> Hızlı Ekle</button>
							</form>
						</div>
						<!-- Aktif Notlar Listesi -->
						<div class="col-md-8">
							<div class="row" style="display: flex; flex-wrap: wrap; gap: 15px; max-height: 280px; overflow-y: auto; padding: 5px;">
								<?php 
								$notlar = $db->query("SELECT * FROM notlar WHERE durum=0 ORDER BY id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
								foreach($notlar as $not) { ?>
									<div style="flex: 1; min-width: 280px; background: #fffdf2; border: 1px solid #f9e79f; border-left: 5px solid #f1c40f; padding: 15px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); position: relative; display: flex; flex-direction: column; justify-content: space-between;">
										<div style="font-weight: 500; color: #2d3436; font-size: 14px; margin-bottom: 12px; line-height: 1.5;">
											<?php echo nl2br(htmlspecialchars($not['icerik'])); ?>
										</div>
										<div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 10px;">
											<small class="text-muted" style="font-size: 11px; font-weight: 600;"><i class="fa fa-clock-o"></i> <?php echo date('d.m.Y H:i', strtotime($not['tarih'])); ?></small>
											<div style="display: flex; gap: 5px;">
												<a href="?not_tamam=<?php echo $not['id']; ?>" class="btn btn-xs btn-success" style="border-radius: 4px; padding: 2px 8px;" title="Tamamla"><i class="fa fa-check"></i></a>
												<a href="?not_sil=<?php echo $not['id']; ?>" class="btn btn-xs btn-danger" style="border-radius: 4px; padding: 2px 8px;" onclick="return confirm('Silmek istediğinize emin misiniz?')" title="Sil"><i class="fa fa-trash"></i></a>
											</div>
										</div>
									</div>
								<?php } 
								if(empty($notlar)) echo '<div class="col-md-12 text-center text-muted" style="padding: 40px; background: #f9f9f9; border-radius: 10px; border: 1px dashed #ddd;">Henüz aktif bir görev veya not bulunmuyor.</div>';
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
	function addEmoji(emoji) {
		var textarea = document.getElementById('not_icerik');
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		var text = textarea.value;
		textarea.value = text.substring(0, start) + emoji + text.substring(end);
		textarea.focus();
		textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
	}
	</script>

<!-- Dönüşüm Analitiği -->
<div class="col-md-12" style="margin-top: 20px;">
        <div class="card">
            <div class="card-heading card-default">
                <i class="fa fa-line-chart"></i> Dönüşüm Analitiği <?php if ($filtre_aktif) echo '<span class="label label-info">FİLTRELİ DÖNEM</span>'; ?>
            </div>
            <div class="card-block">
                <?php if ($filtre_aktif) { ?>
                <!-- Filtreli Dönem Verileri -->
                <div class="row" style="margin-bottom: 25px; background: rgba(52, 152, 219, 0.03); padding: 20px 10px; border-radius: 10px; border: 1px dashed #3498db;">
                    <div class="col-md-4">
                        <div class="widget widget-chart white-bg padding-0" style="border: none; background: transparent;">
                            <div class="widget-content" style="padding: 10px; text-align: center;">
                                <div style="font-weight: 700; color: #2980b9; font-size: 11px; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.5px;">SEÇİLİ DÖNEM DÖNÜŞÜMÜ</div>
                                <div style="font-size: 28px; font-weight: 800; color: #2980b9; line-height: 1.2;">%<?php echo number_format($filtreli_cr, 1); ?></div>
                                <div style="margin-top: 8px; font-size: 11px; color: #7f8c8d; font-weight: 600;">
                                    <i class="fa fa-shopping-cart"></i> <?php echo $filtreli_siparis_sayisi; ?> Sipariş / <?php echo number_format($filtreli_tekil, 0, ',', '.'); ?> Hit
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" style="border-left: 1px solid rgba(52, 152, 219, 0.1); border-right: 1px solid rgba(52, 152, 219, 0.1);">
                         <div class="widget widget-chart white-bg padding-0" style="border: none; background: transparent;">
                            <div class="widget-content" style="padding: 10px; text-align: center;">
                                <div style="font-weight: 700; color: #2980b9; font-size: 11px; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.5px;">SEÇİLİ DÖNEM ZİYARETÇİ</div>
                                <div style="font-size: 28px; font-weight: 800; color: #2c3e50; line-height: 1.2;"><?php echo number_format($filtreli_tekil, 0, ',', '.'); ?></div>
                                <div style="margin-top: 8px; font-size: 11px; color: #7f8c8d; font-weight: 600;">
                                    <i class="fa fa-eye"></i> <?php echo number_format($filtreli_cogul, 0, ',', '.'); ?> Toplam Gösterim
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="widget widget-chart white-bg padding-0" style="border: none; background: transparent;">
                            <div class="widget-content" style="padding: 10px; text-align: center;">
                                <div style="font-weight: 700; color: #27ae60; font-size: 11px; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.5px;">SEÇİLİ DÖNEM CİRO</div>
                                <div style="font-size: 28px; font-weight: 800; color: #27ae60; line-height: 1.2;"><?php echo number_format($filtreli_ciro, 0, ',', '.'); ?> ₺</div>
                                <div style="margin-top: 8px; font-size: 11px; color: #7f8c8d; font-weight: 600;">
                                    <i class="fa fa-money"></i> Filtreli Toplam Kazanç
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="row">
                    <div class="col-md-4">
                        <div class="widget widget-chart white-bg padding-0" style="border-radius: 4px; border: 1px solid #eee;">
                            <div class="widget-content" style="padding: 20px; text-align: center;">
                                <div style="font-weight: 700; color: #64748b; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px;">BUGÜNÜN DÖNÜŞÜMÜ</div>
                                <div style="font-size: 2.2rem; font-weight: 900; color: #ef4444; line-height: 1;">%<?php echo number_format($bugun_cr, 1); ?></div>
                                <div style="margin-top: 10px; font-size: 0.8rem; color: #94a3b8;">
                                    <i class="fa fa-shopping-cart"></i> <?php echo $gunluk_siparis_sayisi; ?> Sipariş / <?php echo $bugun_tekil; ?> Tekil Hit
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="widget widget-chart white-bg padding-0" style="border-radius: 4px; border: 1px solid #eee;">
                            <div class="widget-content" style="padding: 20px; text-align: center;">
                                <div style="font-weight: 700; color: #64748b; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px;">BU AYIN DÖNÜŞÜMÜ</div>
                                <div style="font-size: 2.2rem; font-weight: 900; color: #3b82f6; line-height: 1;">%<?php echo number_format($aylik_cr, 1); ?></div>
                                <div style="margin-top: 10px; font-size: 0.8rem; color: #94a3b8;">
                                    <i class="fa fa-shopping-cart"></i> <?php echo $aylik_siparis_sayisi; ?> Sipariş / <?php echo $buay_tekil; ?> Tekil Hit
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="widget widget-chart white-bg padding-0" style="border-radius: 4px; border: 1px solid #eee;">
                            <div class="widget-content" style="padding: 20px; text-align: center;">
                                <div style="font-weight: 700; color: #64748b; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px;">TOPLAM VERİMLİLİK</div>
                                <div style="font-size: 2.2rem; font-weight: 900; color: #f59e0b; line-height: 1;">%<?php echo number_format($toplam_cr, 1); ?></div>
                                <div style="margin-top: 10px; font-size: 0.8rem; color: #94a3b8;">
                                    Site genelindeki ortalama sipariş oranı.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Ciro Analitiği -->
<div class="col-md-12" style="margin-top: 20px;">

		<div class="card">
			<div class="card-heading card-default">
				<i class="fa fa-line-chart"></i> Ciro Analitiği
			</div>
			<div class="card-block">
				<div class="row">
					<!-- Saatlik Ciro -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #e74c3c;">
							<div class="widget-title" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: #fff; padding: 12px;">
								<span class="label label-danger pull-right">SAATLİK</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-clock-o"></i> Saatlik Ciro</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0 text-danger" style="font-size: 24px; font-weight: 700;">
									<b><?php echo number_format($saatlik_ciro_tutar, 2, ',', '.'); ?> ₺</b>
								</h3>
								<small class="text-muted"><?php echo $saat; ?>:00 - <?php echo ($saat+1); ?>:00</small>
							</div>
						</div>
					</div>
					
					<!-- Günlük Ciro -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #3498db;">
							<div class="widget-title" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: #fff; padding: 12px;">
								<span class="label label-primary pull-right">GÜNLÜK</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-calendar"></i> Günlük Ciro</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0 text-primary" style="font-size: 24px; font-weight: 700;">
									<b><?php echo number_format($gunluk_ciro_tutar, 2, ',', '.'); ?> ₺</b>
								</h3>
								<small class="text-muted">Bugün (<?php echo date('d.m.Y'); ?>)</small>
							</div>
						</div>
					</div>
					
					<!-- Haftalık Ciro -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #f39c12;">
							<div class="widget-title" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: #fff; padding: 12px;">
								<span class="label label-warning pull-right">HAFTALIK</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-calendar-o"></i> Haftalık Ciro</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0 text-warning" style="font-size: 24px; font-weight: 700;">
									<b><?php echo number_format($haftalik_ciro_tutar, 2, ',', '.'); ?> ₺</b>
								</h3>
								<small class="text-muted">Bu Hafta</small>
							</div>
						</div>
					</div>
					
					<!-- Aylık Ciro -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #9b59b6;">
							<div class="widget-title" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: #fff; padding: 12px;">
								<span class="label label-info pull-right">AYLIK</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-calendar-check-o"></i> Aylık Ciro</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0" style="font-size: 24px; font-weight: 700; color: #9b59b6;">
									<b><?php echo number_format($aylik_ciro_tutar, 2, ',', '.'); ?> ₺</b>
								</h3>
								<small class="text-muted"><?php echo date('F Y'); ?></small>
							</div>
						</div>
					</div>
					
					<!-- Toplam Ciro -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #27ae60;">
							<div class="widget-title" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: #fff; padding: 12px;">
								<span class="label label-success pull-right">TOPLAM</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-money"></i> Toplam Ciro</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0 text-success" style="font-size: 24px; font-weight: 700;">
									<b><?php echo number_format($toplam_ciro_tutar, 2, ',', '.'); ?> ₺</b>
								</h3>
								<small class="text-muted">Tüm Zamanlar</small>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Duruma Göre Ciro -->
				<div class="row" style="margin-top: 30px;">
					<div class="col-md-12">
						<h4 style="margin-bottom: 15px; font-weight: 700; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">
							<i class="fa fa-pie-chart"></i> Duruma Göre Ciro Dağılımı
						</h4>
						<div class="row">
							<?php foreach($durum_ciro as $durum_id => $durum_data) { 
								$yuzde = $toplam_ciro_tutar > 0 ? ($durum_data['tutar'] / $toplam_ciro_tutar * 100) : 0;
								$renkler = array('#3498db', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22', '#16a085');
								$renk = $renkler[$durum_id % count($renkler)];
							?>
							<div class="col-md-3 col-sm-6" style="margin-bottom: 15px;">
								<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid <?php echo $renk; ?>; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
									<div class="widget-content" style="padding: 15px; height: auto;">
										<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
											<span style="font-weight: 600; color: #555; font-size: 13px;"><?php echo htmlspecialchars($durum_data['ad']); ?></span>
											<span style="font-size: 11px; color: #999; background: #f5f5f5; padding: 3px 8px; border-radius: 12px;"><?php echo number_format($yuzde, 1); ?>%</span>
										</div>
										<h3 style="margin: 0; font-size: 20px; font-weight: 700; color: <?php echo $renk; ?>; vertical-align: middle; line-height: 1.2;">
											<?php echo number_format($durum_data['tutar'], 2, ',', '.'); ?> ₺
										</h3>
										<div style="margin-top: 8px; height: 4px; background: #f0f0f0; border-radius: 2px; overflow: hidden;">
											<div style="height: 100%; background: <?php echo $renk; ?>; width: <?php echo $yuzde; ?>%; transition: width 0.3s ease;"></div>
										</div>
									</div>
								</div>
							</div>
							<?php } ?>
						</div>
					</div>
				</div>
				
				<!-- GRAFİKLER -->
				<div class="row" style="margin-top: 30px;">
					<div class="col-md-6">
						<div class="card">
							<div class="card-heading card-default">
								<i class="fa fa-map-marker"></i> En Çok Sipariş Alan Şehirler (Top 5)
							</div>
							<div class="card-block">
								<canvas id="cityChart" height="200"></canvas>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="card">
							<div class="card-heading card-default">
								<i class="fa fa-clock-o"></i> Sipariş Yoğunluk Saatleri
							</div>
							<div class="card-block">
								<canvas id="hourChart" height="200"></canvas>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Sipariş Sayısı Kartları -->
				<div class="row" style="margin-top: 30px;">
					<div class="col-md-12">
						<h4 style="margin-bottom: 20px; font-weight: 700; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">
							<i class="fa fa-shopping-cart"></i> Sipariş Sayısı Analitiği
						</h4>
					</div>
				</div>
				<div class="row">
					<!-- Saatlik Sipariş Sayısı -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #e74c3c;">
							<div class="widget-title" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: #fff; padding: 12px;">
								<span class="label label-danger pull-right">SAATLİK</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-clock-o"></i> Saatlik Sipariş</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0 text-danger" style="font-size: 24px; font-weight: 700;">
									<b><?php echo number_format($saatlik_siparis_sayisi, 0, ',', '.'); ?></b>
								</h3>
								<small class="text-muted"><?php echo $saat; ?>:00 - <?php echo ($saat+1); ?>:00</small>
							</div>
						</div>
					</div>
					
					<!-- Günlük Sipariş Sayısı -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #3498db;">
							<div class="widget-title" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: #fff; padding: 12px;">
								<span class="label label-primary pull-right">GÜNLÜK</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-calendar"></i> Günlük Sipariş</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0 text-primary" style="font-size: 24px; font-weight: 700;">
									<b><?php echo number_format($gunluk_siparis_sayisi, 0, ',', '.'); ?></b>
								</h3>
								<small class="text-muted">Bugün (<?php echo date('d.m.Y'); ?>)</small>
							</div>
						</div>
					</div>
					
					<!-- Haftalık Sipariş Sayısı -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #f39c12;">
							<div class="widget-title" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: #fff; padding: 12px;">
								<span class="label label-warning pull-right">HAFTALIK</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-calendar-o"></i> Haftalık Sipariş</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0 text-warning" style="font-size: 24px; font-weight: 700;">
									<b><?php echo number_format($haftalik_siparis_sayisi, 0, ',', '.'); ?></b>
								</h3>
								<small class="text-muted">Bu Hafta</small>
							</div>
						</div>
					</div>
					
					<!-- Aylık Sipariş Sayısı -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #9b59b6;">
							<div class="widget-title" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: #fff; padding: 12px;">
								<span class="label label-info pull-right">AYLIK</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-calendar-check-o"></i> Aylık Sipariş</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0" style="font-size: 24px; font-weight: 700; color: #9b59b6;">
									<b><?php echo number_format($aylik_siparis_sayisi, 0, ',', '.'); ?></b>
								</h3>
								<small class="text-muted"><?php echo date('F Y'); ?></small>
							</div>
						</div>
					</div>
					
					<!-- Toplam Sipariş Sayısı -->
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #27ae60;">
							<div class="widget-title" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: #fff; padding: 12px;">
								<span class="label label-success pull-right">TOPLAM</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-shopping-cart"></i> Toplam Sipariş</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-0 text-success" style="font-size: 24px; font-weight: 700;">
									<b><?php echo number_format($toplam_siparis_sayisi, 0, ',', '.'); ?></b>
								</h3>
								<small class="text-muted">Tüm Zamanlar</small>
							</div>
						</div>
					</div>
					
					<!-- Sahte Siparişler -->
					<?php if ($sahte_durum_id !== null) { ?>
					<div class="col-md-3 col-sm-6">
						<div class="widget widget-chart white-bg padding-0" style="border-left: 4px solid #95a5a6;">
							<div class="widget-title" style="background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%); color: #fff; padding: 12px;">
								<span class="label label-default pull-right">SAHTE</span>
								<h2 class="margin-b-0" style="color: #fff; font-size: 14px;"><i class="fa fa-ban"></i> Sahte Siparişler</h2>
							</div>
							<div class="widget-content" style="padding: 15px;">
								<h3 class="margin-b-5" style="font-size: 20px; font-weight: 700; color: #95a5a6;">
									<b><?php echo number_format($sahte_siparis_sayisi, 0, ',', '.'); ?></b> <small class="text-muted" style="font-size: 12px;">Adet</small>
								</h3>
								<h3 class="margin-b-0" style="font-size: 18px; font-weight: 600; color: #7f8c8d;">
									<b><?php echo number_format($sahte_siparis_ciro, 2, ',', '.'); ?> ₺</b>
								</h3>
								<small class="text-muted">Ciro Değeri</small>
							</div>
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-heading card-default">
                SON SİPARİŞLER
            </div>
            <div class="card-block">
                <table class="table table-hover mobile-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Müşteri</th>
                            <th>Tarih</th>
                            <th>Not</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sonsiparisler = $db->query("SELECT * FROM siparis ORDER BY siparis_id DESC LIMIT 10");
                        while($sip = $sonsiparisler->fetch(PDO::FETCH_ASSOC)){
                            $durum_sor = $db->prepare("SELECT * FROM durum WHERE id=:id");
                            $durum_sor->execute(array('id' => $sip['siparis_durum']));
                            $durum = $durum_sor->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <tr class="mobile-collapsed">
                            <td data-label="ID">#<?php echo $sip['siparis_id']; ?></td>
                            <td data-label="Müşteri"><?php echo $sip['siparis_ad']; ?></td>
                            <td data-label="Tarih"><?php echo $sip['siparis_tarih']; ?></td>
                            <td data-label="Not"><small><?php echo !empty($sip['siparis_not']) ? mb_substr($sip['siparis_not'], 0, 30).'...' : '-'; ?></small></td>
                            <td data-label="Durum"><span class="label label-primary"><?php echo isset($durum['ad']) ? $durum['ad'] : 'Yeni Sipariş'; ?></span></td>
                            <td data-label="İşlem"><a href="siparis-detay.php?siparis_id=<?php echo $sip['siparis_id']; ?>" class="btn btn-xs btn-success">Detay</a></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- Chart.js Entegrasyonu -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
	// 1. Şehirler Grafiği
	var cityCtx = document.getElementById('cityChart').getContext('2d');
	new Chart(cityCtx, {
		type: 'bar',
		data: {
			labels: [<?php foreach($sehir_istatistik as $si) echo "'".$si['siparis_il']."',"; ?>],
			datasets: [{
				label: 'Sipariş Adeti',
				data: [<?php foreach($sehir_istatistik as $si) echo $si['adet'].","; ?>],
				backgroundColor: 'rgba(52, 152, 219, 0.7)',
				borderColor: 'rgba(52, 152, 219, 1)',
				borderWidth: 1
			}]
		},
		options: {
			responsive: true,
			scales: { y: { beginAtZero: true } }
		}
	});

	// 2. Saatlik Dağılım Grafiği
	var hourCtx = document.getElementById('hourChart').getContext('2d');
	new Chart(hourCtx, {
		type: 'line',
		data: {
			labels: [<?php foreach($saat_labels as $label) echo "'$label',"; ?>],
			datasets: [{
				label: 'Sipariş Sayısı',
				data: [<?php echo implode(',', $saat_values); ?>],
				fill: true,
				backgroundColor: 'rgba(46, 204, 113, 0.2)',
				borderColor: 'rgba(46, 204, 113, 1)',
				tension: 0.4
			}]
		},
		options: {
			responsive: true,
			plugins: { legend: { display: false } },
			scales: { y: { beginAtZero: true } }
		}
	});
});
</script>
