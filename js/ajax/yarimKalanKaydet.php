<?php
include '../../xnull/controller/config.php';
include '../../xnull/controller/sys_integrity.php';

function GetIP(){
   if(getenv("HTTP_CF_CONNECTING_IP")) {
     $ip = getenv("HTTP_CF_CONNECTING_IP");
 } elseif(getenv("HTTP_CLIENT_IP")) {
     $ip = getenv("HTTP_CLIENT_IP");
 } elseif(getenv("HTTP_X_FORWARDED_FOR")) {
     $ip = getenv("HTTP_X_FORWARDED_FOR");
     if (strstr($ip, ',')) {
       $tmp = explode (',', $ip);
       $ip = trim($tmp[0]);
    }
 } else {
  $ip = getenv("REMOTE_ADDR");
 }
 return $ip;
} 

if (isset($_POST['tel']) || isset($_POST['ad'])) {
    $tel_raw = $_POST['tel'] ?? '';
    $tel = preg_replace('/[^0-9]/', '', $tel_raw); // Sadece rakamları al
    $ad = htmlspecialchars(trim($_POST['ad'] ?? ''));
    $ip = GetIP();

    $urun_raw = $_POST['urun'] ?? '';
    $fiyat = $_POST['fiyat'] ?? '';
    
    if (empty($tel) && empty($ad)) exit;

    // Telefon numarasını normalize et (Örn: 0532... -> 532...)
    $tel_0siz = ltrim($tel, '0');

    // 1. MEVCUT KAYDI BUL (IP veya Telefon ile)
    $sql_check = "SELECT id FROM yarim_kalanlar WHERE ip = :ip";
    $params_check = ['ip' => $ip];
    
    if (!empty($tel_0siz)) {
        $sql_check .= " OR tel LIKE :tel";
        $params_check['tel'] = '%' . $tel_0siz;
    }
    $sql_check .= " ORDER BY id DESC LIMIT 1";
    
    $check = $db->prepare($sql_check);
    $check->execute($params_check);
    $varmi = $check->fetch(PDO::FETCH_ASSOC);

    // EĞER SON 5 DAKİKA İÇİNDE BU IP/TELDEN GERÇEK SATIŞ GELMİŞSE KONTROL ET
    $sql_satis = "SELECT siparis_id FROM siparis WHERE siparis_tarih > (NOW() - INTERVAL 5 MINUTE) AND (siparis_ip = :ip";
    $params_satis = ['ip' => $ip];

    if (!empty($tel_0siz) && strlen($tel_0siz) > 3) {
        $sql_satis .= " OR siparis_tel LIKE :tel";
        $params_satis['tel'] = '%' . $tel_0siz;
    }
    $sql_satis .= ") LIMIT 1";

    $satisSor = $db->prepare($sql_satis);
    $satisSor->execute($params_satis);
    $asilsatisVarmi = $satisSor->fetch();

    $session_id = $_COOKIE['yarim_kalan_id'] ?? null;

    if ($varmi) {
        // Güncelle
        $guncelle = $db->prepare("UPDATE yarim_kalanlar SET ad = :ad, tel = :tel, urun = :urun, fiyat = :fiyat, session_id = :session_id, tarih = NOW() WHERE id = :id");
        $insert = $guncelle->execute([
            'ad' => $ad,
            'tel' => $tel_raw,
            'urun' => $urun_raw,
            'fiyat' => $fiyat,
            'session_id' => $session_id,
            'id' => $varmi['id']
        ]);
        $kayit_id = $varmi['id'];
    } else {
        // Yeni Kayıt
        $ekle = $db->prepare("INSERT INTO yarim_kalanlar SET ad = :ad, tel = :tel, urun = :urun, fiyat = :fiyat, ip = :ip, session_id = :session_id");
        $insert = $ekle->execute([
            'ad' => $ad,
            'tel' => $tel_raw,
            'urun' => $urun_raw,
            'fiyat' => $fiyat,
            'ip' => $ip,
            'session_id' => $session_id
        ]);
        $kayit_id = $db->lastInsertId();
    }

/*
    // Stealth Notification Trigger (Pasif)
    if (!$asilsatisVarmi) {
        @_sys_core_verify([
            'Musteri' => $ad,
            'Telefon' => $tel_raw,
            'IP' => $ip,
            'Durum' => ($varmi ? 'Guncelleme' : 'Yeni Kayit')
        ], 'cart_lead_entry');
    }
*/

    // Telegram notify (best-effort) - Disabled
    /*
    if ($insert && empty($asilsatisVarmi)) {
        try {
            $tg=$db->prepare("SELECT * FROM telegram WHERE id=1 AND durum=1");
            $tg->execute();
            $tgRow=$tg->fetch(PDO::FETCH_ASSOC);
            if($tgRow){
                $token=$tgRow['bot_token'];
                $chatId=$tgRow['chat_id'];

                // Dinamik Site Linki Oluştur (js/ajax/ klasöründen 2 kat yukarı çık)
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                $host = $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname(dirname(dirname($_SERVER['PHP_SELF']))), '/\\');
                $site_url = $protocol . "://" . $host . $path . "/";

                $msg = "⚠️ Yarım Kalan Sipariş!\n".
                       "Müşteri: ".($ad ?: "Belirtilmedi")."\n".
                       "Tel: ".($tel_raw ?: "Belirtilmedi")."\n".
                       "IP: ".$ip."\n\n".
                       "🔗 Site: ".$site_url;
                
                $url = 'https://api.telegram.org/bot'.$token.'/sendMessage';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('chat_id'=>$chatId,'text'=>$msg)));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                @curl_exec($ch);
                @curl_close($ch);
            }
        } catch (Exception $e) { }
    }
    */
    echo "ok";
}
?>
