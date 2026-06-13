<?php
include '../../xnull/controller/config.php';

// Ayarları Çek
$settingsQuery = $db->prepare("SELECT * FROM ayar WHERE ayar_id = 0");
$settingsQuery->execute();
$ayar = $settingsQuery->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['tel'])) {
    $tel = preg_replace('/[^0-9]/', '', $_POST['tel']); // Sadece rakamları al
    
    if (empty($tel)) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen telefon numaranızı giriniz.']);
        exit;
    }

    // Telefon numarasını normalize et (0'lı ve 0'sız versiyonları hazırla)
    $tel_0siz = ltrim($tel, '0'); // Başındaki 0'ları at
    $tel_0li = '0' . $tel_0siz;    // Başına bir tane 0 ekle

    // --- HİBRİT SORGULAMA MANTIĞI ---
    $isRemote = isset($ayar['ayar_common_query_status']) && $ayar['ayar_common_query_status'] == 1;
    
    if ($isRemote) {
        // ORTAK PANEL ÜZERİNDEN SORGULA
        $panelUrl = isset($ayar['ayar_common_panel_url']) ? $ayar['ayar_common_panel_url'] : '';
        $apiKey = isset($ayar['ayar_common_panel_key']) ? $ayar['ayar_common_panel_key'] : '';

        if (empty($panelUrl)) {
            echo json_encode(['status' => 'error', 'message' => 'Ortak panel URL ayarlanmamış.']);
            exit;
        }

        // URL düzeltme: Eğer ana dizin girildiyse api yolunu ekle
        $panelUrl = rtrim($panelUrl, '/');
        if (strpos($panelUrl, 'api/query_order.php') === false) {
            if (strpos($panelUrl, 'api/receive.php') !== false) {
                $panelUrl = str_replace('api/receive.php', 'api/query_order.php', $panelUrl);
            } else {
                $panelUrl .= '/api/query_order.php';
            }
        }

        $postData = [
            'tel' => $tel,
            'api_key' => $apiKey,
            'site_origin' => $_SERVER['HTTP_HOST']
        ];

        $ch = curl_init($panelUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            echo $response; // API sonucunu direkt dön (t2 formatı t1 ile aynı)
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Ortak panel ile bağlantı kurulamadı (Hata: '.$httpCode.')']);
        }
        exit;

    } else {
        // YEREL VERİTABANINDAN SORGULA
        $siparissor = $db->prepare("SELECT s.*, d.ad as durum_ad FROM siparis s LEFT JOIN durum d ON s.siparis_durum = d.id WHERE (s.siparis_tel = :tel1 OR s.siparis_tel = :tel2) ORDER BY s.siparis_id DESC LIMIT 1");
        $siparissor->execute([
            'tel1' => $tel_0li,
            'tel2' => $tel_0siz
        ]);
        $siparis = $siparissor->fetch(PDO::FETCH_ASSOC);

        if ($siparis) {
            $durum = $siparis['durum_ad'];
            if ($siparis['siparis_durum'] == 0) {
                $durum = "Yeni Gelen Sipariş";
            }
            
            echo json_encode([
                'status' => 'success',
                'order_id' => $siparis['siparis_id'],
                'customer' => $siparis['siparis_ad'],
                'order_status' => $durum,
                'date' => date('d.m.Y H:i', strtotime($siparis['siparis_tarih']))
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Bu telefon numarası ile kayıtlı sipariş bulunamadı.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
}
?>
