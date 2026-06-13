<?php
include '../../xnull/controller/config.php';

// 1. Gerçek son 5 siparişi getir
$sorgu = $db->prepare("SELECT siparis_ad, siparis_il, siparis_tarih FROM siparis ORDER BY siparis_id DESC LIMIT 5");
$sorgu->execute();
$siparisler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

$sonuc = [];

// Gerçek siparişleri işle
foreach ($siparisler as $sip) {
    if (empty($sip['siparis_ad'])) continue;
    
    $parcalar = explode(" ", trim($sip['siparis_ad']));
    $maskeli_ad = mb_substr($parcalar[0], 0, 1) . "****";
    if (isset($parcalar[1])) $maskeli_ad .= " " . mb_substr($parcalar[1], 0, 1) . ".";

    $gecen_sure = time() - strtotime($sip['siparis_tarih']);
    if ($gecen_sure < 60) $sure_metni = "az önce";
    elseif ($gecen_sure < 3600) $sure_metni = round($gecen_sure/60) . " dakika önce";
    elseif ($gecen_sure < 86400) $sure_metni = round($gecen_sure/3600) . " saat önce";
    else continue; // 24 saatten eski siparişleri gösterme

    $sonuc[] = [
        'ad' => $maskeli_ad,
        'il' => $sip['siparis_il'],
        'sure' => $sure_metni,
        'real' => true
    ];
}

// 2. Sentetik veriler (Veritabanından)
$sahte_sorgu = $db->prepare("SELECT * FROM sahte_bildirimler WHERE sahte_durum=1");
$sahte_sorgu->execute();
$sahte_veriler = $sahte_sorgu->fetchAll(PDO::FETCH_ASSOC);

foreach ($sahte_veriler as $sahte) {
    $sonuc[] = [
        'ad' => $sahte['sahte_ad'],
        'il' => $sahte['sahte_il'],
        'sure' => $sahte['sahte_sure'],
        'real' => false
    ];
}

// Karıştır
shuffle($sonuc);

header('Content-Type: application/json');
echo json_encode($sonuc);
?>
