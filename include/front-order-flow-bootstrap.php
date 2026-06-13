<?php
/**
 * Ortak sipariş akışı: ayarlar, OTP oturumu, dönüş URL’si, sayfa uyarıları.
 * POST işleme front-order-flow-process-post.php içindedir.
 */
if (!isset($settingsprint) || !is_array($settingsprint)) {
  $settings = $db->prepare("SELECT * from ayar where ayar_id=?");
  $settings->execute(array(0));
  $settingsprint = $settings->fetch(PDO::FETCH_ASSOC);
}
if (!isset($DemCont)) {
  $demoCont = $db->prepare("SELECT * from demo where id=1");
  $demoCont->execute(array());
  $demoControl = $demoCont->fetch(PDO::FETCH_ASSOC);
  $DemCont = isset($demoControl['durum']) ? $demoControl['durum'] : 0;
}

require_once __DIR__ . '/order-verification.php';

$order_origin_active = isset($_REQUEST['order_origin']) && (string)$_REQUEST['order_origin'] === '1';
$order_origin_product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;
$order_return_page = $order_origin_active ? 'order.php' : 'index.php';
$build_order_return_url = function ($params = '', $anchor = '') use ($order_return_page, $order_origin_active, $order_origin_product_id) {
  $queryParts = array();
  if ($order_origin_active && $order_origin_product_id > 0) {
    $queryParts[] = 'product_id=' . $order_origin_product_id;
    $queryParts[] = 'order_origin=1';
  }
  if ($params !== '') {
    $queryParts[] = ltrim((string)$params, '?&');
  }
  $url = $order_return_page;
  if (!empty($queryParts)) {
    $url .= '?' . implode('&', $queryParts);
  }
  if ($anchor !== '') {
    $url .= $anchor;
  }
  return $url;
};

$otp_flow_requested = isset($_GET['otp_status']) || isset($_POST['otp_stage_verify_submit']) || isset($_POST['otp_stage_resend_submit']);
$scriptBase = isset($_SERVER['SCRIPT_NAME']) ? basename((string)$_SERVER['SCRIPT_NAME']) : '';
// Eski OTP oturumunu sadece ana sayfa GET’inde temizle; order.php sekmesinde çakışmayı azaltır.
if (!$otp_flow_requested && $_SERVER['REQUEST_METHOD'] === 'GET' && $scriptBase === 'index.php' && !empty($_SESSION['front_order_verify'])) {
  unset($_SESSION['front_order_verify']);
}
$front_verify_pending = !empty($_SESSION['front_order_verify']) && is_array($_SESSION['front_order_verify']);
if ($front_verify_pending && !empty($_SESSION['front_order_verify']['expires_at']) && time() > (int)$_SESSION['front_order_verify']['expires_at']) {
  unset($_SESSION['front_order_verify']);
  $front_verify_pending = false;
}
$front_verify_tel_mask = '';
if ($front_verify_pending && !empty($_SESSION['front_order_verify']['sent_to'])) {
  $front_verify_tel_mask = function_exists('ov_mask_tel') ? ov_mask_tel((string)$_SESSION['front_order_verify']['sent_to']) : '***';
}
/** OTP beklerken formları doldurmak için (order.php / index.php) */
$front_order_post_data = array();
if ($front_verify_pending && !empty($_SESSION['front_order_verify']['post_data']) && is_array($_SESSION['front_order_verify']['post_data'])) {
  $front_order_post_data = $_SESSION['front_order_verify']['post_data'];
}
$front_verify_notice = '';
$front_verify_notice_type = '';
if (isset($_GET['otp_status'])) {
  if ($_GET['otp_status'] === 'sent') {
    $front_verify_notice = 'Doğrulama kodu telefonunuza gönderildi. Kodu girip siparişi tamamlayın.';
    $front_verify_notice_type = 'ok';
  } elseif ($_GET['otp_status'] === 'no') {
    $front_verify_notice = 'Doğrulama kodu hatalı veya süresi dolmuş.';
    $front_verify_notice_type = 'no';
  } elseif ($_GET['otp_status'] === 'sms_no') {
    $front_verify_notice = 'Doğrulama SMSi gönderilemedi. Lütfen tekrar deneyin.';
    $front_verify_notice_type = 'no';
  }
}
$front_verify_flash_html = '';
if ($front_verify_notice !== '') {
  $front_verify_flash_html = '<div class="col-12 col-sm-12 col-lg-12" style="margin: 10px 0 12px;">'
    . '<div style="padding:10px 12px;border-radius:10px;'
    . ($front_verify_notice_type === 'ok' ? 'background:#e8f8ef;color:#1f8a4c;' : 'background:#fdecec;color:#ad2d2d;')
    . '">' . htmlspecialchars($front_verify_notice, ENT_QUOTES, 'UTF-8') . '</div></div>';
}

/** Sipariş sayfası (order.php) için genel uyarılar */
$order_flow_alert = null;
if (isset($_GET['bos']) && $_GET['bos'] === 'no') {
  $order_flow_alert = array('type' => 'err', 'text' => 'Lütfen ad, telefon, şehir, ilçe ve adres alanlarını eksiksiz ve doğru doldurun.');
}
if (isset($_GET['status'])) {
  if ($_GET['status'] === 'cookie_blocked') {
    $order_flow_alert = array('type' => 'err', 'text' => 'Bu cihazdan yeni sipariş verme süreniz henüz dolmadı. Mevcut siparişiniz tamamlandıktan sonra tekrar deneyebilirsiniz.');
  } elseif ($_GET['status'] === 'no') {
    $order_flow_alert = array('type' => 'err', 'text' => 'Sipariş işlemi tamamlanamadı. Bilgilerinizi kontrol edip tekrar deneyin.');
  }
}
