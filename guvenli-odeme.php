<?php 
define('VITRIN_HEAD_MINIMAL', true);
include 'include/head.php';
$GuvenliGet = htmlspecialchars(trim((string) ($_GET['status'] ?? '')), ENT_QUOTES, 'UTF-8');
$odeme_bekleyen = function_exists('order_status_payment_pending') ? order_status_payment_pending() : -3;
$siparissor=$db->prepare("SELECT * from siparis where siparis_id=:guvenli and (siparis_durum=0 OR siparis_durum=:odeme_bekleyen)");
$siparissor->bindValue(':odeme_bekleyen', $odeme_bekleyen, PDO::PARAM_INT);
$siparissor->execute(array('guvenli' => $GuvenliGet));
$sipprint=$siparissor->fetch(PDO::FETCH_ASSOC); 

$siparisson=$db->prepare("SELECT * from siparis order by siparis_id DESC Limit 1");
$siparisson->execute();
$sipsonprint=$siparisson->fetch(PDO::FETCH_ASSOC);
$patternUrl = rtrim(SITE_URL, '/') . '/xnull/assets/img/genel/pattern10.png';
?>
<title><?php echo htmlspecialchars(isset($settingsprint['ayar_title']) ? $settingsprint['ayar_title'] : 'Site', ENT_QUOTES, 'UTF-8'); ?></title>
<meta name="description" content="<?php echo htmlspecialchars(isset($settingsprint['ayar_description']) ? $settingsprint['ayar_description'] : '', ENT_QUOTES, 'UTF-8'); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars(isset($settingsprint['ayar_keywords']) ? $settingsprint['ayar_keywords'] : '', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="wide no-page-loader">
    <div id="wrapper" class="clearfix">
        <?php include 'include/menu.php'; ?>

<section id="page-title" class="page-title-classic" style="background: url(<?php echo htmlspecialchars($patternUrl, ENT_QUOTES, 'UTF-8'); ?>)">
  <div class="container">
    <div class="text-center">
      <h1>GÜVENLİ ÖDEME</h1>
    </div>
  </div>
</section>
<section style="background-color: #fff;">
  <div class="container text-center">
    <div class="row">
      <div class="col-12">
        <?php 

        $paytr=$db->prepare("SELECT * from paytr where paytr_id=?");
        $paytr->execute(array(1));
        $paytrprint=$paytr->fetch(PDO::FETCH_ASSOC);
        if ($sipprint && ((int) $sipprint['siparis_durum'] === 0 || (int) $sipprint['siparis_durum'] === $odeme_bekleyen)) {

          try {
            $db->exec('ALTER TABLE siparis ADD COLUMN siparis_durumpay TINYINT(1) NOT NULL DEFAULT 0');
          } catch (Throwable $e) {
          }

          if (!$paytrprint || trim((string) ($paytrprint['paytr_magaza'] ?? '')) === '' || trim((string) ($paytrprint['paytr_key'] ?? '')) === '' || trim((string) ($paytrprint['paytr_salt'] ?? '')) === '') {
            echo '<div class="alert alert-danger text-start" style="max-width:640px;margin:1rem auto;">PayTR mağaza numarası, parola veya gizli anahtar tanımlı değil. Yönetim paneli → <strong>Ödeme Yöntemleri</strong> → <strong>PayTR</strong> sekmesinden kaydedin.</div>';
          } else {
  ?>

            <?php

  ## 1. ADIM için örnek kodlar ##

  ####################### DÜZENLEMESİ ZORUNLU ALANLAR #######################
  #
  ## API Entegrasyon Bilgileri - Mağaza paneline giriş yaparak BİLGİ sayfasından alabilirsiniz.
            $merchant_id  = $paytrprint['paytr_magaza'];
            $merchant_key   = $paytrprint['paytr_key'];
            $merchant_salt  = $paytrprint['paytr_salt'];
  #
  ## PayTR / iFrame API: müşteri e-postası zorunlu; önce siparişteki, yoksa site bildirim adresi
            $email = '';
            if (!empty($sipprint['siparis_mail'])) {
                $cm = trim((string) $sipprint['siparis_mail']);
                if ($cm !== '' && filter_var($cm, FILTER_VALIDATE_EMAIL)) {
                    $email = $cm;
                }
            }
            if ($email === '' && !empty($settingsprint['ayar_mail'])) {
                $am = trim((string) $settingsprint['ayar_mail']);
                if ($am !== '' && filter_var($am, FILTER_VALIDATE_EMAIL)) {
                    $email = $am;
                }
            }
            if ($email === '') {
                $host = parse_url(rtrim((string) ($settingsprint['ayar_siteurl'] ?? ''), '/'), PHP_URL_HOST);
                if (empty($host) || !is_string($host)) {
                    $host = 'localhost';
                }
                $email = 'odeme@' . $host;
            }
  #
  ## Tahsil edilecek tutar (PayTR: kuruş, tam sayı — örn. 99.50 TL → 9950)
  $rawPrice = isset($sipprint['siparis_fiyat']) ? $sipprint['siparis_fiyat'] : 0;
  $cleanPrice = str_replace(',', '.', preg_replace('/[^0-9.,]/', '', strval($rawPrice)));
  $amountTry = floatval($cleanPrice);
  $payment_amount = (int) round($amountTry * 100);
  if ($payment_amount < 1) {
    die('PAYTR: Geçersiz ödeme tutarı.');
  }
  $basketPriceStr = number_format($amountTry, 2, '.', '');
  #
  ## Sipariş numarası: Her işlemde benzersiz olmalıdır!! Bu bilgi bildirim sayfanıza yapılacak bildirimde geri gönderilir.
  $merchant_oid = $sipprint['siparis_id'];
  #
  ## Müşterinizin sitenizde kayıtlı veya form aracılığıyla aldığınız ad ve soyad bilgisi
  $user_name = $sipprint['siparis_ad'];
  #
  ## Müşterinizin sitenizde kayıtlı veya form aracılığıyla aldığınız adres bilgisi
  $user_address = $sipprint['siparis_adres'];
  #
  ## Müşterinizin sitenizde kayıtlı veya form aracılığıyla aldığınız telefon bilgisi
  $user_phone = $sipprint['siparis_tel'];
  #
  ## Başarılı ödeme sonrası müşterinizin yönlendirileceği sayfa
  ## !!! Bu sayfa siparişi onaylayacağınız sayfa değildir! Yalnızca müşterinizi bilgilendireceğiniz sayfadır!
  ## !!! Siparişi onaylayacağız sayfa "Bildirim URL" sayfasıdır (Bakınız: 2.ADIM Klasörü).
  $merchant_ok_url = rtrim($settingsprint['ayar_siteurl'], "/")."/phpmail/siparis.php?iletisimform=ok";
  #
  ## Ödeme sürecinde beklenmedik bir hata oluşması durumunda müşterinizin yönlendirileceği sayfa
  ## !!! Bu sayfa siparişi iptal edeceğiniz sayfa değildir! Yalnızca müşterinizi bilgilendireceğiniz sayfadır!
  ## !!! Siparişi iptal edeceğiniz sayfa "Bildirim URL" sayfasıdır (Bakınız: 2.ADIM Klasörü).
  $merchant_fail_url = rtrim($settingsprint['ayar_siteurl'], "/")."/?status=no";
  #
  ## Müşterinin sepet/sipariş içeriği
  $user_basket = base64_encode(json_encode(array(
    array($sipprint['siparis_urun'], $basketPriceStr, 1)
  ), JSON_UNESCAPED_UNICODE));
  #
  /* ÖRNEK $user_basket oluşturma - Ürün adedine göre array'leri çoğaltabilirsiniz
  $user_basket = base64_encode(json_encode(array(
    array("Örnek ürün 1", "18.00", 1), // 1. ürün (Ürün Ad - Birim Fiyat - Adet )
    array("Örnek ürün 2", "33.25", 2), // 2. ürün (Ürün Ad - Birim Fiyat - Adet )
    array("Örnek ürün 3", "45.42", 1)  // 3. ürün (Ürün Ad - Birim Fiyat - Adet )
  )));
  */
  ############################################################################################

  ## Kullanıcının IP adresi
  if( isset( $_SERVER["HTTP_CLIENT_IP"] ) ) {
    $ip = $_SERVER["HTTP_CLIENT_IP"];
  } elseif( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) ) {
    $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
  } else {
    $ip = $_SERVER["REMOTE_ADDR"];
  }

  ## !!! Eğer bu örnek kodu sunucuda değil local makinanızda çalıştırıyorsanız
  ## buraya dış ip adresinizi (https://www.whatismyip.com/) yazmalısınız. Aksi halde geçersiz paytr_token hatası alırsınız.
  $user_ip=$ip;
  ##

  ## İşlem zaman aşımı süresi - dakika cinsinden
  $timeout_limit = "30";

  ## Hata mesajlarının ekrana basılması için entegrasyon ve test sürecinde 1 olarak bırakın. Daha sonra 0 yapabilirsiniz.
  $debug_on = 1;

    ## Mağaza canlı modda iken test işlem yapmak için 1 olarak gönderilebilir.
  $test_mode = 0;

  $no_installment = 0; // Taksit yapılmasını istemiyorsanız, sadece tek çekim sunacaksanız 1 yapın

  ## Sayfada görüntülenecek taksit adedini sınırlamak istiyorsanız uygun şekilde değiştirin.
  ## Sıfır (0) gönderilmesi durumunda yürürlükteki en fazla izin verilen taksit geçerli olur.
  $max_installment = 0;

  $currency = "TL";
  
  ####### Bu kısımda herhangi bir değişiklik yapmanıza gerek yoktur. #######
  $hash_str = $merchant_id .$user_ip .$merchant_oid .$email .$payment_amount .$user_basket.$no_installment.$max_installment.$currency.$test_mode;
  $paytr_token=base64_encode(hash_hmac('sha256',$hash_str.$merchant_salt,$merchant_key,true));
  $post_vals=array(
    'merchant_id'=>$merchant_id,
    'user_ip'=>$user_ip,
    'merchant_oid'=>$merchant_oid,
    'email'=>$email,
    'payment_amount'=>$payment_amount,
    'paytr_token'=>$paytr_token,
    'user_basket'=>$user_basket,
    'debug_on'=>$debug_on,
    'no_installment'=>$no_installment,
    'max_installment'=>$max_installment,
    'user_name'=>$user_name,
    'user_address'=>$user_address,
    'user_phone'=>$user_phone,
    'merchant_ok_url'=>$merchant_ok_url,
    'merchant_fail_url'=>$merchant_fail_url,
    'timeout_limit'=>$timeout_limit,
    'currency'=>$currency,
    'test_mode'=>$test_mode,
    'lang'=>'tr'
  );
  
  $ch=curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1) ;
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 20);
  $result = @curl_exec($ch);

  if(curl_errno($ch))
    die("PAYTR IFRAME connection error. err:".curl_error($ch));

  curl_close($ch);
  
  $result = json_decode($result, true);

  if (!is_array($result)) {
    die('PAYTR IFRAME: Geçersiz yanıt.');
  }
  if (($result['status'] ?? '') === 'success' && !empty($result['token'])) {
    $token = $result['token'];
  } else {
    die('PAYTR IFRAME failed. reason:' . htmlspecialchars((string) ($result['reason'] ?? 'bilinmiyor'), ENT_QUOTES, 'UTF-8'));
  }
  #########################################################################

  ?>

  <!-- Ödeme formunun açılması için gereken HTML kodlar / Başlangıç -->
  <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
  <iframe src="https://www.paytr.com/odeme/guvenli/<?php echo $token;?>" id="paytriframe" frameborder="0" scrolling="no" style="width:100%;max-width:100%;display:block;margin:0 auto;"></iframe>
  <script>iFrameResize({},'#paytriframe');</script>
  <!-- Ödeme formunun açılması için gereken HTML kodlar / Bitiş -->

<?php
          }
        }
        else {
          header("Location: " . SITE_URL . "index.php?status=no" );
          exit;
        }
        ?>
<p>Güvenli Ödeme 256 Bit SSL şifreleme ve IFRAME teknolojisi ile tüm işlemler bankanız ve sizin tarafınızdan gerçekleşir; hiçbir veri üçüncü kişiler tarafından görülemez ve işlenemez.</p>
<a href="<?php echo htmlspecialchars($settingsprint['ayar_siteurl'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-xl">SİTEYE DÖN!</a>
      </div>
    </div>
  </div>
</section>
        <?php include 'include/footer.php'; ?>
    </div><!-- #wrapper -->
<?php
require_once __DIR__ . '/include/legal-pages.php';
legal_pages_render_footer($db, $settingsprint, $whatsappprint ?? null);
?>
</body>
</html>
