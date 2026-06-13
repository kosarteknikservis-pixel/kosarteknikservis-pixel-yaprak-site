<?php 
include 'controller/config.php';

$kullanici_ad = $_SESSION['kullanici_adi'];
$ip = $_SERVER['REMOTE_ADDR'];

if ($kullanici_ad) {
    try {
        $log_ekle = $db->prepare("INSERT INTO admin_log SET kullanici_ad=:ad, islem=:islem, ip_adresi=:ip");
        $log_ekle->execute([
            'ad' => $kullanici_ad,
            'islem' => 'Çıkış',
            'ip' => $ip
        ]);
    } catch(PDOException $e) {}
}

session_destroy();

header('Location:login.php?status=exit');
exit;
