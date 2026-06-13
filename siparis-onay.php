<?php
// Minimal head KULLANILMAZ: motor_gonay (<head> içi Meta Pixel tabanı) gerekir — yoksa fbq tanımsız, Purchase + Pixel Helper boş kalır.
include 'include/head.php'; 
require_once __DIR__ . '/include/order-verification.php';
try {
$metakey=$db->prepare("SELECT * from meta where meta_id=5");
$metakey->execute();
$metakeyprint=$metakey->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $metakeyprint = array('meta_title'=>'Sipariş Onaylandı','meta_descr'=>'','meta_keyword'=>'');
}
?>
<script>
if (window.location.hash) {
    history.replaceState(null, null, window.location.pathname + window.location.search);
}
</script>
<?php
// Sipariş bilgilerini çek
$siparis_id = isset($_GET['siparis']) ? (int)$_GET['siparis'] : 0;
$siparis_detay = null;
if ($siparis_id > 0) {
  try {
    $siparissor=$db->prepare("SELECT * from siparis where siparis_id=:id");
    $siparissor->execute(array('id' => $siparis_id));
    $siparis_detay = $siparissor->fetch(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    $siparis_detay = null;
  }
}

$verify_notice = '';
$verify_notice_type = '';
$verify_row = null;
$verify_direct_link = '';
$siparis_is_online_card = $siparis_detay
  && function_exists('order_payment_is_online_card')
  && order_payment_is_online_card((int) ($siparis_detay['siparis_odemeid'] ?? 0));
$verification_enabled = (!isset($settingsprint['ayar_siparis_dogrulama_on']) || (int)$settingsprint['ayar_siparis_dogrulama_on'] === 1)
  && !$siparis_is_online_card;
if ($verification_enabled && $siparis_id > 0) {
  try {
    $verify_row = ov_fetch_by_order($db, $siparis_id);
  } catch (Exception $e) {
    $verify_row = null;
  }
}

if ($verification_enabled && $siparis_id > 0 && isset($_POST['otp_verify_submit'])) {
  $otpInput = isset($_POST['otp_code']) ? (string)$_POST['otp_code'] : '';
  $verifyResult = ov_verify_by_otp($db, $siparis_id, $otpInput);
  if (!empty($verifyResult['ok'])) {
    $verify_notice = 'Telefon doğrulaması başarıyla tamamlandı.';
    $verify_notice_type = 'ok';
  } else {
    $verify_notice = 'Doğrulama kodu hatalı veya süresi dolmuş olabilir.';
    $verify_notice_type = 'no';
  }
  $verify_row = ov_fetch_by_order($db, $siparis_id);
}

if ($verification_enabled && $siparis_id > 0 && isset($_POST['otp_resend_submit']) && $siparis_detay) {
  try {
    $ovData = ov_create_or_refresh($db, $siparis_id, (string)$siparis_detay['siparis_tel'], 20);
    if (is_array($ovData) && !empty($ovData['token']) && !empty($ovData['otp'])) {
      $verifyLink = rtrim(SITE_URL, '/') . '/siparis-dogrula.php?t=' . rawurlencode((string)$ovData['token']);
      if (ov_send_otp_sms((string)$siparis_detay['siparis_tel'], (string)$ovData['otp'], $verifyLink)) {
        $verify_notice = 'Yeni doğrulama kodu SMS olarak gönderildi.';
        $verify_notice_type = 'ok';
      } else {
        $verify_notice = 'SMS gönderilemedi. Lütfen kısa süre sonra tekrar deneyin.';
        $verify_notice_type = 'no';
      }
    } else {
      $verify_notice = 'Doğrulama kaydı oluşturulamadı.';
      $verify_notice_type = 'no';
    }
  } catch (Exception $e) {
    $verify_notice = 'Doğrulama yeniden gönderilirken bir hata oluştu.';
    $verify_notice_type = 'no';
  }
  $verify_row = ov_fetch_by_order($db, $siparis_id);
}

if ($verification_enabled && $siparis_id > 0 && isset($_POST['otp_generate_link_submit']) && $siparis_detay) {
  try {
    $ovData = ov_create_or_refresh($db, $siparis_id, (string)$siparis_detay['siparis_tel'], 20);
    if (is_array($ovData) && !empty($ovData['token'])) {
      $verify_direct_link = rtrim(SITE_URL, '/') . '/siparis-dogrula.php?t=' . rawurlencode((string)$ovData['token']);
      $verify_notice = 'Tek tık doğrulama linki üretildi. WhatsApp ile açabilir veya doğrudan tıklayabilirsiniz.';
      $verify_notice_type = 'ok';
    }
  } catch (Exception $e) {
    $verify_notice = 'Doğrulama linki üretilirken bir hata oluştu.';
    $verify_notice_type = 'no';
  }
  $verify_row = ov_fetch_by_order($db, $siparis_id);
}

if ($verification_enabled && isset($_GET['verify']) && $verify_notice === '') {
  if ($_GET['verify'] === 'ok') {
    $verify_notice = 'Tek tık link ile doğrulama tamamlandı.';
    $verify_notice_type = 'ok';
  } elseif ($_GET['verify'] === 'expired') {
    $verify_notice = 'Doğrulama linkinin süresi dolmuş.';
    $verify_notice_type = 'no';
  } elseif ($_GET['verify'] === 'no') {
    $verify_notice = 'Doğrulama linki geçersiz.';
    $verify_notice_type = 'no';
  }
}

// Meta tag'leri dinamik olarak oluştur
$meta_title = isset($metakeyprint['meta_title']) ? $metakeyprint['meta_title'] : 'Sipariş Onaylandı';
$meta_description = isset($metakeyprint['meta_descr']) ? $metakeyprint['meta_descr'] : '';
$meta_keywords = isset($metakeyprint['meta_keyword']) ? $metakeyprint['meta_keyword'] : '';
$toplam_tutar = 0;
$tutar_format = '0,00';

// Sipariş bilgileri varsa meta tag'lerini güncelle
$tutar_format_og = '0.00'; // Facebook format için varsayılan
if ($siparis_detay) {
  // Toplam tutarı doğru formatta al (ondalık sorunları olmadan)
  // Toplam tutarı doğru formatta al (ondalık sorunları olmadan) - Agresif Temizlik
  $rawPrice = isset($siparis_detay['siparis_fiyat']) ? $siparis_detay['siparis_fiyat'] : 0;
  // Hem nokta hem virgül gelebilir, virgülü noktaya çevirip temizleyelim
  $cleanPrice = str_replace(',', '.', strval($rawPrice));
  $cleanPrice = preg_replace('/[^0-9.]/', '', $cleanPrice);
  $toplam_tutar = floatval($cleanPrice);
  $tutar_format = number_format($toplam_tutar, 2, ',', '.'); // Türkçe format: 949,00
  $tutar_format_og = number_format($toplam_tutar, 2, '.', ''); // Facebook format: 949.00
  
  // Title: Sipariş numarası ve tutar ile
  $meta_title = 'Sipariş Onaylandı - Sipariş #' . htmlspecialchars($siparis_detay['siparis_id']) . ' - ' . $tutar_format . ' ₺';
  
  // Description: Sipariş detayları ile
  $siparis_urun = isset($siparis_detay['siparis_urun']) ? htmlspecialchars($siparis_detay['siparis_urun']) : '';
  $siparis_ad = isset($siparis_detay['siparis_ad']) ? htmlspecialchars($siparis_detay['siparis_ad']) : '';
  $siparis_tarih = isset($siparis_detay['siparis_tarih']) ? date('d.m.Y', strtotime($siparis_detay['siparis_tarih'])) : '';
  
  $meta_description = 'Siparişiniz başarıyla alındı. Sipariş No: #' . htmlspecialchars($siparis_detay['siparis_id']) . 
                      '. Ürün: ' . mb_substr($siparis_urun, 0, 50) . 
                      '. Toplam Tutar: ' . $tutar_format . ' ₺' . 
                      '. Tarih: ' . $siparis_tarih . '. Müşteri temsilcimiz en kısa sürede sizinle iletişime geçecektir.';
  
  // Keywords: Sipariş bilgileri ile
  $meta_keywords = 'sipariş onaylandı, sipariş #' . $siparis_detay['siparis_id'] . ', ' . $tutar_format . ' tl, ' . $siparis_tarih;
}
// Site URL'ini hazırla
$sayfaURL = "http";
if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on"){
  $sayfaURL .= "s";
}
$sayfaURL .= "://";
$sayfaURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

// Open Graph: og-share.jpg varsa küçük ayar logosundan önce kullan
$og_base_url = rtrim((string)($settingsprint['ayar_siteurl'] ?? ''), '/');
if ($og_base_url === '') {
    $og_base_url = rtrim(SITE_URL, '/');
}
$og_share_path = SITE_ROOT . '/xnull/assets/img/genel/og-share.jpg';
if (is_file($og_share_path)) {
  $og_image = $og_base_url . '/xnull/assets/img/genel/og-share.jpg';
} elseif (!empty($settingsprint['ayar_logo'])) {
  $og_image = $og_base_url . '/xnull/' . ltrim((string) $settingsprint['ayar_logo'], '/');
} else {
  $og_image = $og_base_url . '/xnull/assets/img/genel/og-share.jpg';
}
?>
<title><?php echo $meta_title; ?></title>
<meta name="description" content="<?php echo $meta_description; ?>">
<meta name="keywords" content="<?php echo $meta_keywords; ?>">

<!-- Facebook Open Graph Meta Tags -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo htmlspecialchars($sayfaURL); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($meta_title); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="<?php echo htmlspecialchars($settingsprint['ayar_title'] ? $settingsprint['ayar_title'] : 'Sipariş Onaylandı'); ?>">
<meta property="og:locale" content="tr_TR">
<?php if ($siparis_detay) { ?>
<meta property="product:price:amount" content="<?php echo $tutar_format_og; ?>">
<meta property="product:price:currency" content="TRY">
<meta property="og:updated_time" content="<?php echo date('c', strtotime($siparis_detay['siparis_tarih'])); ?>">
    
    <!-- JSON-LD for Meta Events Manager & Search Engines -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org/",
      "@type": "Order",
      "orderNumber": "<?php echo htmlspecialchars($siparis_detay['siparis_id']); ?>",
      "price": "<?php echo $tutar_format_og; ?>",
      "priceCurrency": "TRY",
      "orderStatus": "https://schema.org/OrderProcessing",
      "orderDate": "<?php echo date('c', strtotime($siparis_detay['siparis_tarih'])); ?>"
    }
    </script>
<?php } ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
.order-confirmation-page {
  min-height: 70vh;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  padding: 40px 20px;
  font-family: 'Poppins', sans-serif;
}
.confirmation-container {
  max-width: 800px;
  margin: 0 auto;
}
.success-header {
  text-align: center;
  margin-bottom: 40px;
  animation: fadeInDown 0.6s ease;
}
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.success-icon {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #00bfa5 0%, #2ecc71 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 25px;
  box-shadow: 0 15px 40px rgba(0,191,165,0.3);
  animation: scaleIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
  position: relative;
}
@keyframes scaleIn {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}
.success-icon::before {
  content: '';
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: linear-gradient(135deg, #00bfa5 0%, #2ecc71 100%);
  animation: pulse 2s infinite;
  opacity: 0.3;
}
@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 0.3;
  }
  50% {
    transform: scale(1.3);
    opacity: 0;
  }
}
.success-icon i {
  font-size: 50px;
  color: #fff;
  z-index: 1;
  position: relative;
}
.success-title {
  font-size: 36px;
  font-weight: 800;
  color: #2c3e50;
  margin-bottom: 15px;
  letter-spacing: -0.5px;
}
.success-subtitle {
  font-size: 18px;
  color: #555;
  font-weight: 500;
  line-height: 1.6;
}
.order-card {
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.08);
  overflow: hidden;
  margin-bottom: 30px;
  animation: fadeInUp 0.6s ease 0.2s both;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.order-card-header {
  background: linear-gradient(135deg, #00bfa5 0%, #2ecc71 100%);
  color: #fff;
  padding: 25px 30px;
  text-align: center;
}
.order-card-header h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 800;
  letter-spacing: 0.5px;
}
.order-number {
  font-size: 32px;
  font-weight: 900;
  margin: 10px 0;
  letter-spacing: 2px;
  text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.order-card-body {
  padding: 35px 30px;
}
.order-detail-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 18px 0;
  border-bottom: 1px solid #f0f0f0;
}
.order-detail-row:last-child {
  border-bottom: none;
}
.order-detail-label {
  font-weight: 700;
  color: #2c3e50;
  font-size: 15px;
  min-width: 140px;
  flex-shrink: 0;
}
.order-detail-value {
  color: #555;
  font-size: 15px;
  font-weight: 500;
  text-align: right;
  flex: 1;
  word-break: break-word;
}
.order-total {
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  padding: 25px 30px;
  margin-top: 20px;
  border-radius: 15px;
  border: 2px solid #00bfa5;
}
.order-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.order-total-label {
  font-size: 18px;
  font-weight: 700;
  color: #2c3e50;
}
.order-total-value {
  font-size: 28px;
  font-weight: 900;
  color: #00bfa5;
  letter-spacing: 0.5px;
}
.info-box {
  background: linear-gradient(135deg, #fff3cd 0%, #fffbf0 100%);
  border-left: 4px solid #ffc107;
  border-radius: 12px;
  padding: 25px 30px;
  margin: 30px 0;
  animation: fadeInUp 0.6s ease 0.4s both;
}
.info-box-title {
  font-size: 18px;
  font-weight: 800;
  color: #856404;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.info-box-title i {
  font-size: 22px;
}
.info-box-text {
  font-size: 15px;
  color: #856404;
  line-height: 1.7;
  margin: 0;
  font-weight: 500;
}
.verify-box {
  background: #ffffff;
  border-radius: 14px;
  border: 2px solid #d8e5ff;
  box-shadow: 0 8px 24px rgba(32, 72, 162, 0.08);
  padding: 22px 24px;
  margin: 20px 0 10px;
}
.verify-box h4 {
  margin: 0 0 10px;
  color: #1f3b71;
  font-size: 20px;
  font-weight: 800;
}
.verify-box p {
  margin: 0 0 14px;
  font-size: 14px;
  color: #445372;
}
.verify-badge {
  display: inline-block;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 14px;
}
.verify-badge--ok { background: #e8f8ef; color: #1f8a4c; }
.verify-badge--pending { background: #fff4dd; color: #9a6b00; }
.verify-alert {
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 13px;
  margin: 0 0 14px;
}
.verify-alert--ok { background: #e8f8ef; color: #1f8a4c; }
.verify-alert--no { background: #fdecec; color: #ad2d2d; }
.verify-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.verify-actions .btn {
  border-radius: 10px;
  font-weight: 700;
}
.verify-input {
  height: 44px;
  border: 1px solid #cad7ef;
  border-radius: 10px;
  padding: 0 14px;
  font-size: 15px;
  width: 100%;
  margin-bottom: 10px;
}
.action-buttons {
  display: flex;
  gap: 15px;
  justify-content: center;
  margin-top: 40px;
  animation: fadeInUp 0.6s ease 0.6s both;
  flex-wrap: wrap;
}
.btn-primary-large {
  padding: 16px 40px;
  font-size: 17px;
  font-weight: 700;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #00bfa5 0%, #2ecc71 100%);
  color: #fff;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
  box-shadow: 0 6px 20px rgba(0,191,165,0.3);
  letter-spacing: 0.3px;
}
.btn-primary-large:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(0,191,165,0.4);
  color: #fff;
  text-decoration: none;
}
.btn-primary-large:active {
  transform: translateY(-1px);
}
.btn-secondary-large {
  padding: 16px 40px;
  font-size: 17px;
  font-weight: 700;
  border-radius: 12px;
  border: 2px solid #00bfa5;
  background: #fff;
  color: #00bfa5;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
  letter-spacing: 0.3px;
}
.btn-secondary-large:hover {
  background: #00bfa5;
  color: #fff;
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0,191,165,0.3);
  text-decoration: none;
}
@media (max-width: 768px) {
  .order-confirmation-page {
    padding: 30px 15px;
  }
  .success-title {
    font-size: 28px;
  }
  .success-subtitle {
    font-size: 16px;
  }
  .order-card-body {
    padding: 25px 20px;
  }
  .order-detail-row {
    flex-direction: column;
    gap: 8px;
    padding: 15px 0;
  }
  .order-detail-label {
    min-width: auto;
    font-size: 14px;
  }
  .order-detail-value {
    text-align: left;
    font-size: 14px;
  }
  .order-number {
    font-size: 26px;
  }
  .order-total {
    padding: 20px;
  }
  .order-total-value {
    font-size: 24px;
  }
  .action-buttons {
    flex-direction: column;
  }
  .btn-primary-large, .btn-secondary-large {
    width: 100%;
    justify-content: center;
  }
  .info-box {
    padding: 20px;
  }
  .verify-box {
    padding: 18px;
  }
}
</style>
</head>
<body class="boxed">
<?php 
// Pixel ve Konversiyon kodları için dinamik veri yerleştirme (geçerli HTML: <body> içinde)
$pixel_codes = isset($motorprint['motor_yonay']) ? $motorprint['motor_yonay'] : '';

// Yer tutucuları (Placeholders) gerçek verilerle değiştir
if ($siparis_detay) {
    $pixel_urun_adi = isset($siparis_detay['siparis_urun']) ? htmlspecialchars(strip_tags($siparis_detay['siparis_urun'])) : '';
    $pixel_codes = str_replace(
        ['{tutar}', '{siparis_id}', '{currency}', '{urun_adi}'],
        [$tutar_format_og, $siparis_detay['siparis_id'], 'TRY', $pixel_urun_adi],
        $pixel_codes
    );
} else {
    $pixel_codes = str_replace(
        ['{tutar}', '{siparis_id}', '{currency}', '{urun_adi}'],
        ['0.00', '0', 'TRY', ''],
        $pixel_codes
    );
}
echo $pixel_codes; 
?>
<?php if ($siparis_detay): 
    $pixel_urun_adi_og = isset($siparis_detay['siparis_urun']) ? strip_tags($siparis_detay['siparis_urun']) : '';
    $event_id = (string)intval($siparis_detay['siparis_id']);
    
    // --- Meta CAPI (Server-Side) Implementation ---
    $meta_token = isset($motorprint['motor_meta_token']) ? trim($motorprint['motor_meta_token']) : '';
    $meta_pixel_id = isset($motorprint['motor_meta_pixel_id']) ? trim($motorprint['motor_meta_pixel_id']) : '';

    if (!empty($meta_token) && !empty($meta_pixel_id)) {
        // PII Normalization & Hashing
        $phone_raw = isset($siparis_detay['siparis_tel']) ? preg_replace('/[^0-9]/', '', $siparis_detay['siparis_tel']) : '';
        // Türkiye telefonlarını normalize et (90 ekle eğer yoksa)
        if (strlen($phone_raw) == 10 && $phone_raw[0] == '5') { $phone_raw = '90' . $phone_raw; }
        else if (strlen($phone_raw) == 11 && $phone_raw[0] == '0' && $phone_raw[1] == '5') { $phone_raw = '90' . substr($phone_raw, 1); }
        
        $hashed_phone = !empty($phone_raw) ? hash('sha256', $phone_raw) : null;
        $hashed_email = !empty($siparis_detay['siparis_mail']) ? hash('sha256', strtolower(trim($siparis_detay['siparis_mail']))) : null;

        // Pixel’in kurduğu cookie’ler — mail olmasa bile EMQ’yu güçlendirir (reklam tıklaması / tarayıcı eşlemesi)
        $meta_fbc = null;
        if (!empty($_COOKIE['_fbc']) && is_string($_COOKIE['_fbc'])) {
            $meta_fbc = $_COOKIE['_fbc'];
        } elseif (!empty($_GET['fbclid']) && is_string($_GET['fbclid'])) {
            $fbclid = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['fbclid']);
            if ($fbclid !== '') {
                $meta_fbc = 'fb.1.' . time() . '.' . $fbclid;
            }
        }
        $meta_fbp = (!empty($_COOKIE['_fbp']) && is_string($_COOKIE['_fbp'])) ? $_COOKIE['_fbp'] : null;

        $meta_user_data = [];
        if ($hashed_phone) {
            $meta_user_data['ph'] = [$hashed_phone];
        }
        if ($hashed_email) {
            $meta_user_data['em'] = [$hashed_email];
        }
        $meta_ip = GetIP();
        if ($meta_ip !== '' && $meta_ip !== null) {
            $meta_user_data['client_ip_address'] = $meta_ip;
        }
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $meta_user_data['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        if ($meta_fbc !== null) {
            $meta_user_data['fbc'] = $meta_fbc;
        }
        if ($meta_fbp !== null) {
            $meta_user_data['fbp'] = $meta_fbp;
        }
        
        $capi_data = [
            'data' => [
                [
                    'event_name' => 'Purchase',
                    'event_time' => time(),
                    'event_id' => $event_id,
                    'action_source' => 'website',
                    'event_source_url' => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
                    'user_data' => $meta_user_data,
                    'custom_data' => [
                        'value' => floatval($tutar_format_og),
                        'currency' => 'TRY',
                        'content_name' => $pixel_urun_adi_og,
                        'content_type' => 'product',
                        'order_id' => $event_id
                    ]
                ]
            ]
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init("https://graph.facebook.com/v21.0/{$meta_pixel_id}/events?access_token={$meta_token}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($capi_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_exec($ch);
            curl_close($ch);
        }
    }
    // --- End Meta CAPI ---

    // --- TikTok Events API (Server-Side) Implementation ---
    $tt_token = isset($motorprint['motor_tiktok_token']) ? trim($motorprint['motor_tiktok_token']) : '';
    $tt_pixel_id = isset($motorprint['motor_tiktok_pixel_id']) ? trim($motorprint['motor_tiktok_pixel_id']) : '';

    if (!empty($tt_token) && !empty($tt_pixel_id)) {
        // Customer Data (PII) hashing for TikTok
        $phone_raw = isset($siparis_detay['siparis_tel']) ? preg_replace('/[^0-9]/', '', $siparis_detay['siparis_tel']) : '';
        // TikTok prefer full E.164 without '+'
        if (strlen($phone_raw) == 10 && $phone_raw[0] == '5') { $phone_raw = '90' . $phone_raw; }
        else if (strlen($phone_raw) == 11 && $phone_raw[0] == '0' && $phone_raw[1] == '5') { $phone_raw = '90' . substr($phone_raw, 1); }
        
        $hashed_phone = !empty($phone_raw) ? hash('sha256', $phone_raw) : null;
        $hashed_email = !empty($siparis_detay['siparis_mail']) ? hash('sha256', strtolower(trim($siparis_detay['siparis_mail']))) : null;

        $tt_page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
        $tt_context = array_filter([
            'ip' => GetIP(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
            'page' => ['url' => $tt_page_url],
            'user' => array_filter([
                'email' => $hashed_email,
                'phone_number' => $hashed_phone,
            ], function ($v) {
                return $v !== null && $v !== '';
            }),
        ], function ($v) {
            if (!is_array($v)) {
                return $v !== null && $v !== '';
            }
            return !empty($v);
        });

        $ttclid = '';
        if (!empty($_COOKIE['ttclid']) && is_string($_COOKIE['ttclid'])) {
            $ttclid = trim($_COOKIE['ttclid']);
        } elseif (!empty($_GET['ttclid']) && is_string($_GET['ttclid'])) {
            $ttclid = trim($_GET['ttclid']);
        }
        if ($ttclid !== '') {
            $tt_context['ad'] = ['callback' => $ttclid];
        }

        $tt_data = [
            'event_source' => 'web',
            'event_source_id' => $tt_pixel_id,
            'data' => [
                [
                    'event' => 'CompletePayment',
                    'event_time' => time(),
                    'event_id' => $event_id,
                    'context' => $tt_context,
                    'properties' => [
                        'content_type' => 'product',
                        'contents' => [
                            [
                                'content_id' => $pixel_urun_adi_og,
                                'content_name' => $pixel_urun_adi_og,
                                'quantity' => 1,
                                'price' => floatval($tutar_format_og)
                            ]
                        ],
                        'currency' => 'TRY',
                        'value' => floatval($tutar_format_og)
                    ],
                ]
            ]
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init("https://business-api.tiktok.com/open_api/v1.3/event/track/");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($tt_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Access-Token: ' . $tt_token
            ]);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_exec($ch);
            curl_close($ch);
        }
    }
    // --- End TikTok Events API ---
?>
<script>
if (typeof fbq === 'function') {
    fbq('track', 'Purchase', {
        value: <?php echo json_encode(floatval($tutar_format_og)); ?>,
        currency: 'TRY',
        content_name: <?php echo json_encode($pixel_urun_adi_og); ?>,
        order_id: '<?php echo $event_id; ?>',
        content_type: 'product'
    }, { eventID: <?php echo json_encode($event_id); ?> });
}
// TikTok Pixel — CompletePayment
if (typeof ttq === 'object') {
    ttq.track('CompletePayment', {
        content_name: <?php echo json_encode($pixel_urun_adi_og); ?>,
        content_type: 'product',
        value: <?php echo json_encode(floatval($tutar_format_og)); ?>,
        currency: 'TRY'
    }, { event_id: <?php echo json_encode($event_id); ?> });
}
// Google Tracking — purchase
if (typeof gtag === 'function') {
    gtag('event', 'purchase', {
        transaction_id: <?php echo json_encode($event_id); ?>,
        value: <?php echo json_encode(floatval($tutar_format_og)); ?>,
        currency: 'TRY',
        items: [{
            item_id: <?php echo json_encode($pixel_urun_adi_og); ?>,
            item_name: <?php echo json_encode($pixel_urun_adi_og); ?>,
            item_category: 'Product',
            price: <?php echo json_encode(floatval($tutar_format_og)); ?>,
            quantity: 1
        }]
    });
}
</script>
<?php endif; ?>

<div class="order-confirmation-page" dir="ltr">
  <div class="confirmation-container">
    <div class="success-header">
      <div class="success-icon">
        <i class="fa fa-check"></i>
      </div>
      <h1 class="success-title">Siparişiniz Başarıyla Alındı!</h1>
      <p class="success-subtitle">Siparişiniz onaylandı ve işleme alındı. En kısa sürede müşteri temsilcimiz sizinle iletişime geçecektir.</p>
    </div>

    <?php if ($siparis_detay) { 
        
        // Havale ile ödeme seçildiyse banka bilgilerini EN BAŞTA göster
        if (isset($siparis_detay['siparis_odemeid']) && $siparis_detay['siparis_odemeid'] == 5) {
          try {
            $hesapsor = $db->prepare("SELECT * FROM hesap ORDER BY hesap_id ASC");
            $hesapsor->execute();
            $hesaplar = $hesapsor->fetchAll(PDO::FETCH_ASSOC);
          } catch (Exception $e) {
            $hesaplar = array();
          }
          
          if (!empty($hesaplar)) {
        ?>
        <div class="bank-info-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 25px; margin-bottom: 30px; color: #fff; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); animation: fadeInUp 0.6s ease 0.1s both;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <i class="fa fa-bank" style="font-size: 24px; color: #fff;"></i>
            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #fff;">Banka Hesap Bilgileri</h3>
          </div>
          <p style="margin: 0 0 20px 0; font-size: 14px; color: rgba(255,255,255,0.9); line-height: 1.6;">
            Lütfen ödemenizi aşağıdaki banka hesaplarımızdan birine havale/EFT yaparak tamamlayın. Ödeme sonrası siparişiniz onaylanacaktır.
          </p>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
            <?php foreach ($hesaplar as $index => $hesap) { ?>
            <div style="background: rgba(255,255,255,0.95); border-radius: 10px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
              <div style="font-weight: 700; font-size: 16px; margin-bottom: 15px; color: #333; display: flex; align-items: center; gap: 8px;">
                <i class="fa fa-university" style="font-size: 18px; color: #667eea;"></i>
                <?php echo htmlspecialchars($hesap['hesap_banka'] ?? ''); ?>
              </div>
              <?php if (!empty($hesap['hesap_isim'])) { ?>
              <div style="margin-bottom: 12px;">
                <div style="font-size: 11px; color: #666; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Alıcı Adı</div>
                <div style="display: flex; align-items: center; gap: 8px; background: #f8f9fa; padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                  <span style="font-size: 14px; font-weight: 600; color: #333; flex: 1; font-family: 'Segoe UI', Arial, sans-serif;"><?php echo htmlspecialchars($hesap['hesap_isim']); ?></span>
                  <button type="button" onclick="copyToClipboard('alici_<?php echo $index; ?>', this)" style="background: #667eea; color: #fff; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#5568d3'" onmouseout="this.style.background='#667eea'">
                    <i class="fa fa-copy"></i> Kopyala
                  </button>
                </div>
                <input type="hidden" id="alici_<?php echo $index; ?>" value="<?php echo htmlspecialchars($hesap['hesap_isim']); ?>">
              </div>
              <?php } ?>
              <div>
                <div style="font-size: 11px; color: #666; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">IBAN</div>
                <div style="display: flex; align-items: center; gap: 8px; background: #f8f9fa; padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                  <span style="font-family: 'Courier New', monospace; font-size: 13px; font-weight: 600; color: #333; flex: 1; letter-spacing: 1px; word-break: break-all;"><?php echo htmlspecialchars($hesap['hesap_iban'] ?? ''); ?></span>
                  <button type="button" onclick="copyToClipboard('iban_<?php echo $index; ?>', this)" style="background: #667eea; color: #fff; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#5568d3'" onmouseout="this.style.background='#667eea'">
                    <i class="fa fa-copy"></i> Kopyala
                  </button>
                </div>
                <input type="hidden" id="iban_<?php echo $index; ?>" value="<?php echo htmlspecialchars($hesap['hesap_iban'] ?? ''); ?>">
              </div>
            </div>
            <?php } ?>
          </div>
          <div style="margin-top: 20px; padding: 18px; background: rgba(255,255,255,0.1); border-radius: 10px; border-left: 4px solid rgba(255,255,255,0.5);">
            <div style="display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
              <i class="fa fa-info-circle" style="font-size: 18px; color: #fff; flex-shrink: 0; margin-top: 2px;"></i>
              <div style="flex: 1; min-width: 0;">
                <strong style="color: #fff; font-size: 14px; display: block; margin-bottom: 6px;">Önemli:</strong>
                <p style="margin: 0; font-size: 13px; color: rgba(255,255,255,0.95); line-height: 1.6;">
                  Havale/EFT yaptıktan sonra lütfen sipariş numaranızı 
                  <strong style="color: #fff; background: rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 4px; font-size: 14px; display: inline-block; margin: 0 4px;">#<?php echo $siparis_detay['siparis_id']; ?></strong> 
                  açıklama kısmına yazın.
                </p>
              </div>
            </div>
          </div>
        </div>
        <script>
        function copyToClipboard(elementId, button) {
          var copyText = document.getElementById(elementId);
          var textArea = document.createElement("textarea");
          textArea.value = copyText.value;
          document.body.appendChild(textArea);
          textArea.select();
          document.execCommand('copy');
          document.body.removeChild(textArea);
          
          var originalText = button.innerHTML;
          button.innerHTML = '<i class="fa fa-check"></i> Kopyalandı!';
          button.style.background = '#27ae60';
          setTimeout(function() {
            button.innerHTML = originalText;
            button.style.background = '#667eea';
          }, 2000);
        }
        </script>
        <?php 
          }
        }
        ?>

    <?php 
    // Sonraki Adımlar bölümü ayarlardan kontrol edilir
    $sonraki_adim_on = isset($settingsprint['ayar_sonraki_adim_on']) ? $settingsprint['ayar_sonraki_adim_on'] : 1;
    $sonraki_adim_text = isset($settingsprint['ayar_sonraki_adim_text']) && !empty($settingsprint['ayar_sonraki_adim_text']) 
        ? $settingsprint['ayar_sonraki_adim_text'] 
        : 'Müşteri temsilcimiz en kısa sürede sizinle iletişime geçerek siparişinizi onaylayacak ve kargo sürecini başlatacaktır. Sipariş durumunuz hakkında bilgilendirmeler admin tarafından size iletilecektir.';
    
    if ($sonraki_adim_on == 1) {
    ?>
    <div class="info-box">
      <div class="info-box-title">
        <i class="fa fa-info-circle"></i>
        <span>Sonraki Adımlar</span>
      </div>
      <p class="info-box-text">
        <?php echo nl2br(htmlspecialchars($sonraki_adim_text)); ?>
      </p>
    </div>
    <?php } ?>

    <?php if ($siparis_detay && $verify_row) { ?>
    <div class="verify-box">
      <h4>Telefon Doğrulama</h4>
      <?php if ((int)$verify_row['durum'] === 1) { ?>
        <span class="verify-badge verify-badge--ok">Doğrulandı</span>
        <p>Siparişiniz doğrulandı. Teşekkür ederiz.</p>
      <?php } else { ?>
        <span class="verify-badge verify-badge--pending">Doğrulama Bekleniyor</span>
        <p>
          Siparişin gerçekten size ait olduğunu doğrulamak için telefonunuza gelen 6 haneli kodu girin.
          WhatsApp kullanıyorsanız SMS içindeki tek tık link ile de onaylayabilirsiniz.
        </p>
      <?php } ?>

      <?php if ($verify_notice !== '') { ?>
        <div class="verify-alert <?php echo $verify_notice_type === 'ok' ? 'verify-alert--ok' : 'verify-alert--no'; ?>">
          <?php echo htmlspecialchars($verify_notice); ?>
        </div>
      <?php } ?>

      <?php if ((int)$verify_row['durum'] !== 1) { ?>
      <form method="POST" style="margin-bottom:10px;">
        <input class="verify-input" type="text" name="otp_code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="6 haneli SMS kodu" required>
        <div class="verify-actions">
          <button type="submit" name="otp_verify_submit" class="btn btn-success"><i class="fa fa-check"></i> Kodu Doğrula</button>
          <button type="submit" name="otp_resend_submit" class="btn btn-default"><i class="fa fa-refresh"></i> Kodu Tekrar Gönder</button>
          <button type="submit" name="otp_generate_link_submit" class="btn btn-info"><i class="fa fa-link"></i> Tek Tık Link Üret</button>
        </div>
      </form>
      <?php if ($verify_direct_link !== '') { ?>
      <div class="verify-actions" style="margin-top:6px;">
        <a class="btn btn-primary" href="<?php echo htmlspecialchars($verify_direct_link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
          <i class="fa fa-check-circle"></i> Linki Açıp Onayla
        </a>
        <a class="btn btn-success" href="https://wa.me/?text=<?php echo rawurlencode('Siparişimi onaylıyorum: ' . $verify_direct_link); ?>" target="_blank" rel="noopener">
          <i class="fa fa-whatsapp"></i> WhatsApp'ta Aç
        </a>
      </div>
      <?php } ?>
      <?php } ?>
    </div>
    <?php } ?>

    <div class="order-card">
      <div class="order-card-header">
        <h3>Sipariş Detayları</h3>
        <div class="order-number">#<?php echo htmlspecialchars($siparis_detay['siparis_id']); ?></div>
        <div style="font-size: 14px; opacity: 0.9; margin-top: 8px;">
          <?php echo date('d.m.Y H:i', strtotime($siparis_detay['siparis_tarih'])); ?>
        </div>
      </div>
      <div class="order-card-body">
        <div class="order-detail-row">
          <span class="order-detail-label">Ad Soyad:</span>
          <span class="order-detail-value"><?php echo htmlspecialchars($siparis_detay['siparis_ad']); ?></span>
        </div>
        <div class="order-detail-row">
          <span class="order-detail-label">Telefon:</span>
          <span class="order-detail-value"><?php echo htmlspecialchars($siparis_detay['siparis_tel']); ?></span>
        </div>
        <?php if (!empty($siparis_detay['siparis_il'])) { ?>
        <div class="order-detail-row">
          <span class="order-detail-label">Şehir / İlçe:</span>
          <span class="order-detail-value"><?php echo htmlspecialchars($siparis_detay['siparis_il']); ?><?php echo !empty($siparis_detay['siparis_ilce']) ? ' / ' . htmlspecialchars($siparis_detay['siparis_ilce']) : ''; ?></span>
        </div>
        <?php } ?>
        <?php if (!empty($siparis_detay['siparis_adres'])) { ?>
        <div class="order-detail-row">
          <span class="order-detail-label">Adres:</span>
          <span class="order-detail-value"><?php echo nl2br(htmlspecialchars($siparis_detay['siparis_adres'])); ?></span>
        </div>
        <?php } ?>
        <?php if (!empty($siparis_detay['siparis_not'])) { ?>
        <div class="order-detail-row">
          <span class="order-detail-label">Not:</span>
          <span class="order-detail-value"><?php echo nl2br(htmlspecialchars($siparis_detay['siparis_not'])); ?></span>
        </div>
        <?php } ?>
        <div class="order-detail-row">
          <span class="order-detail-label">Ürün:</span>
          <span class="order-detail-value"><?php echo htmlspecialchars($siparis_detay['siparis_urun']); ?></span>
        </div>
        <?php if (!empty($siparis_detay['siparis_odeme'])) { ?>
        <div class="order-detail-row">
          <span class="order-detail-label">Ödeme Yöntemi:</span>
          <span class="order-detail-value"><?php echo htmlspecialchars($siparis_detay['siparis_odeme']); ?></span>
        </div>
        <?php } ?>
        
        <div class="order-total">
          <div class="order-total-row">
            <span class="order-total-label">Toplam Tutar:</span>
            <span class="order-total-value">
              <span id="siparis-fiyat" class="meta-order-total"><?php echo number_format($toplam_tutar, 2, ',', '.'); ?></span> ₺
            </span>
          </div>
        </div>
      </div>
    </div>
    <?php } ?>

    <div class="action-buttons">
      <a href="<?php echo $settingsprint['ayar_siteurl']; ?>" class="btn-primary-large">
        <i class="fa fa-home"></i>
        <span>Ana Sayfaya Dön</span>
      </a>
      <a href="<?php echo $settingsprint['ayar_siteurl']; ?>" class="btn-secondary-large">
        <i class="fa fa-shopping-cart"></i>
        <span>Yeni Sipariş</span>
      </a>
    </div>
  </div>
</div>
<?php include 'include/footer.php'; ?>
