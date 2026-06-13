<?php
if (!isset($_POST['siparisver'])) {
  return;
}

if (!function_exists('normalizeOrderTextUtf8')) {
  function normalizeOrderTextUtf8($value) {
    $text = trim((string)$value);
    if ($text === '') {
      return '';
    }

    $decodeFlags = ENT_QUOTES;
    if (defined('ENT_HTML5')) {
      $decodeFlags |= ENT_HTML5;
    }
    $text = html_entity_decode($text, $decodeFlags, 'UTF-8');

    if (function_exists('mb_detect_encoding')) {
      $enc = @mb_detect_encoding($text, array('UTF-8', 'Windows-1254', 'ISO-8859-9', 'ISO-8859-1', 'Windows-1252'), true);
      if ($enc !== false && $enc !== 'UTF-8') {
        $converted = @mb_convert_encoding($text, 'UTF-8', $enc);
        if ($converted !== false && $converted !== '') {
          $text = $converted;
        }
      }
    }

    $text = strtr($text, array(
      'Ã¼' => 'ü', 'Ãœ' => 'Ü',
      'Ã¶' => 'ö', 'Ã–' => 'Ö',
      'Ã§' => 'ç', 'Ã‡' => 'Ç',
      'Ä±' => 'ı', 'Ä°' => 'İ',
      'ÅŸ' => 'ş', 'Åž' => 'Ş',
      'ÄŸ' => 'ğ', 'Äž' => 'Ğ',
    ));

    return preg_replace('/\s+/u', ' ', $text);
  }
}

$verification_enabled_order = !isset($settingsprint['ayar_siparis_dogrulama_on']) || (int)$settingsprint['ayar_siparis_dogrulama_on'] === 1;

if ($DemCont == 1) {
  header('Location:?demo=ok');
  exit;
}

if (isset($settingsprint['ayar_cookie_on']) && $settingsprint['ayar_cookie_on'] == 1) {
  if (isset($_COOKIE['order_blocked'])) {
    header("Location: " . $build_order_return_url('status=cookie_blocked'));
    exit;
  }
}

if ($verification_enabled_order) {
  if (isset($_POST['otp_stage_verify_submit'])) {
    $pending = isset($_SESSION['front_order_verify']) && is_array($_SESSION['front_order_verify']) ? $_SESSION['front_order_verify'] : null;
    $otpInput = preg_replace('/\D+/', '', (string)($_POST['otp_code_front'] ?? ''));
    $nowTs = time();
    $valid = $pending
      && !empty($pending['otp_hash'])
      && !empty($pending['expires_at'])
      && $nowTs <= (int)$pending['expires_at']
      && hash('sha256', $otpInput) === (string)$pending['otp_hash']
      && !empty($pending['post_data'])
      && is_array($pending['post_data']);
    if (!$valid) {
      header("Location: " . $build_order_return_url('otp_status=no', '#myform'));
      exit;
    }
    $base = $pending['post_data'];
    $mergeKeys = array(
      'siparis_ad', 'siparis_tel', 'siparis_il', 'siparis_ilce', 'siparis_adres', 'siparis_not',
      'odeme', 'urun', 'urun_adet', 'product_id', 'order_origin', 'carkifelek_odul',
      'siparis_fatura_vn', 'siparis_fatura_vd', 'siparis_fatura_unvan', 'siparis_fatura_adres',
    );
    $mergeKeepIfEmpty = array('siparis_ad', 'siparis_tel', 'siparis_il', 'siparis_ilce', 'siparis_adres');
    foreach ($mergeKeys as $mk) {
      if (!array_key_exists($mk, $_POST)) {
        continue;
      }
      $incoming = $_POST[$mk];
      if (in_array($mk, $mergeKeepIfEmpty, true) && trim((string) $incoming) === '') {
        continue;
      }
      $base[$mk] = $incoming;
    }
    if (isset($_POST['secenekler']) && is_array($_POST['secenekler'])) {
      $base['secenekler'] = $_POST['secenekler'];
    }
    if (trim((string) ($base['siparis_il'] ?? '')) === '' || trim((string) ($base['siparis_ilce'] ?? '')) === '') {
      header("Location: " . $build_order_return_url('otp_status=no', '#myform'));
      exit;
    }
    $_POST = $base;
    $_POST['siparisver'] = 1;
    $_POST['otp_stage_passed'] = 1;
    unset($_SESSION['front_order_verify']);
    $front_verify_pending = false;
  } elseif (isset($_POST['otp_stage_resend_submit'])) {
    $pending = isset($_SESSION['front_order_verify']) && is_array($_SESSION['front_order_verify']) ? $_SESSION['front_order_verify'] : null;
    if (!$pending || empty($pending['post_data']) || !is_array($pending['post_data'])) {
      header("Location: " . $build_order_return_url('otp_status=no', '#myform'));
      exit;
    }
    $telDigitsPre = preg_replace('/\D+/', '', (string)($pending['post_data']['siparis_tel'] ?? ''));
    if (strlen($telDigitsPre) < 10 || strlen($telDigitsPre) > 12) {
      header("Location: " . $build_order_return_url('otp_status=no', '#myform'));
      exit;
    }
    $otpFront = (string)random_int(100000, 999999);
    $_SESSION['front_order_verify']['otp_hash'] = hash('sha256', $otpFront);
    $_SESSION['front_order_verify']['expires_at'] = time() + (5 * 60);
    $_SESSION['front_order_verify']['sent_to'] = $telDigitsPre;
    $otpMsg = 'Siparisinizi tamamlamak icin kodunuz: ' . $otpFront . ' (5 dk gecerli).';
    $otpSent = function_exists('sendTransactionalSms') ? sendTransactionalSms($telDigitsPre, $otpMsg) : false;
    if (!$otpSent) {
      header("Location: " . $build_order_return_url('otp_status=sms_no', '#myform'));
      exit;
    }
    header("Location: " . $build_order_return_url('otp_status=sent', '#myform'));
    exit;
  } elseif (empty($_POST['otp_stage_passed'])) {
    $telDigitsPre = preg_replace('/\D+/', '', (string)($_POST['siparis_tel'] ?? ''));
    $otpIl = trim((string)($_POST['siparis_il'] ?? ''));
    $otpIlce = trim((string)($_POST['siparis_ilce'] ?? ''));
    $otpAdres = trim((string)($_POST['siparis_adres'] ?? ''));
    $otpAd = trim((string)($_POST['siparis_ad'] ?? ''));
    if (strlen($telDigitsPre) < 10 || strlen($telDigitsPre) > 12
      || $otpIl === '' || $otpIlce === '' || $otpAdres === '' || $otpAd === '') {
      header("Location: " . $build_order_return_url('bos=no', '#myform'));
      exit;
    }
    $otpFront = (string)random_int(100000, 999999);
    $_SESSION['front_order_verify'] = array(
      'otp_hash' => hash('sha256', $otpFront),
      'expires_at' => time() + (5 * 60),
      'sent_to' => $telDigitsPre,
      'post_data' => $_POST,
    );
    $otpMsg = 'Siparisinizi tamamlamak icin kodunuz: ' . $otpFront . ' (5 dk gecerli).';
    $otpSent = function_exists('sendTransactionalSms') ? sendTransactionalSms($telDigitsPre, $otpMsg) : false;
    if (!$otpSent) {
      header("Location: " . $build_order_return_url('otp_status=sms_no', '#myform'));
      exit;
    }
    header("Location: " . $build_order_return_url('otp_status=sent', '#myform'));
    exit;
  }
}

try { $db->exec("ALTER TABLE siparis ADD COLUMN siparis_fatura_vn VARCHAR(32) NOT NULL DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE siparis ADD COLUMN siparis_fatura_vd VARCHAR(128) NOT NULL DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE siparis ADD COLUMN siparis_fatura_unvan VARCHAR(255) NOT NULL DEFAULT ''"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE siparis ADD COLUMN siparis_fatura_adres TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE siparis ADD COLUMN siparis_adet INT NOT NULL DEFAULT 1"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE siparis ADD COLUMN siparis_ilce VARCHAR(191) NOT NULL DEFAULT ''"); } catch (Exception $e) {}

$ipEngelleSor = $db->prepare("SELECT * FROM ip WHERE ip=:ip");
$ipEngelleSor->execute(array('ip' => GetIP()));
if ($ipEngelleSor->rowCount() >= 1) {
  header("Location: " . $build_order_return_url('status=no'));
  exit;
}

try {
  $telEngelleSor = $db->prepare("SELECT * FROM tel_engelle WHERE tel=:tel");
  $telEngelleSor->execute(array('tel' => preg_replace('/[^0-9]/', '', (string)($_POST['siparis_tel'] ?? ''))));
  if ($telEngelleSor->rowCount() >= 1) {
    header("Location: " . $build_order_return_url('status=no'));
    exit;
  }
} catch (Exception $e) {}

$engel_sure = isset($settingsprint['ayar_il_sure']) ? min(999999, max(1, intval($settingsprint['ayar_il_sure']))) : 24;
$siparissor = $db->prepare("SELECT COUNT(*) as sayi FROM siparis WHERE siparis_ip=:ip AND siparis_tarih >= NOW() - INTERVAL :sure HOUR");
$siparissor->execute(array('ip' => GetIP(), 'sure' => $engel_sure));
$varmi = $siparissor->fetch(PDO::FETCH_ASSOC)['sayi'];
if (isset($settingsprint['ayar_il']) && $settingsprint['ayar_il'] == 0 && $varmi >= 1) {
  header("Location: " . $build_order_return_url('status=no'));
  exit;
}

$adRaw = preg_replace('/\s+/u', ' ', trim((string) ($_POST['siparis_ad'] ?? '')));
$adRaw = mb_substr($adRaw, 0, 100, 'UTF-8');
$telDigits = preg_replace('/[^0-9]/', '', (string)($_POST['siparis_tel'] ?? ''));
$tel = htmlspecialchars($telDigits, ENT_QUOTES, 'UTF-8');
$siparis_il = htmlspecialchars(normalizeOrderTextUtf8(trim((string)($_POST['siparis_il'] ?? ''))), ENT_QUOTES, 'UTF-8');
$siparis_odeme_raw = trim((string)($_POST['odeme'] ?? ''));
if ($siparis_odeme_raw === '' || !preg_match('/^(.+)-(\d+)$/u', $siparis_odeme_raw, $odemeMatch)) {
  header("Location: " . $build_order_return_url('bos=no', '#myform'));
  exit;
}
$siparis_odeme = htmlspecialchars($siparis_odeme_raw, ENT_QUOTES, 'UTF-8');
$odeme = array($odemeMatch[1], $odemeMatch[2]);
$urunPost = trim((string)($_POST['urun'] ?? ''));
$urunSeg = explode('|', $urunPost, 2);
$urunDecodeFlags = ENT_QUOTES;
if (defined('ENT_HTML5')) {
  $urunDecodeFlags |= ENT_HTML5;
}
$urunNameClean = isset($urunSeg[0]) ? normalizeOrderTextUtf8(strip_tags($urunSeg[0])) : '';
$urunPricePart = isset($urunSeg[1]) ? trim($urunSeg[1]) : '';
$urunJoined = ($urunPricePart !== '') ? ($urunNameClean . '|' . $urunPricePart) : $urunNameClean;
$urun = htmlspecialchars($urunJoined, ENT_QUOTES, 'UTF-8');
$ilce = htmlspecialchars(normalizeOrderTextUtf8(trim((string)($_POST['siparis_ilce'] ?? ''))), ENT_QUOTES, 'UTF-8');
$adres = htmlspecialchars(mb_substr(trim((string)($_POST['siparis_adres'] ?? '')), 0, 700, 'UTF-8'), ENT_QUOTES, 'UTF-8');
$siparis_not = isset($_POST['siparis_not']) ? htmlspecialchars(mb_substr(trim((string) $_POST['siparis_not']), 0, 1500, 'UTF-8'), ENT_QUOTES, 'UTF-8') : '';
$fatura_vn = '';
$fatura_vd = '';
$fatura_unvan = '';
$fatura_adres_kurum = '';
if (!empty($settingsprint['ayar_kurumsal_fatura_on'])) {
  $fatura_vn_digits = preg_replace('/[^0-9]/', '', (string) ($_POST['siparis_fatura_vn'] ?? ''));
  $fatura_vn = htmlspecialchars(mb_substr($fatura_vn_digits, 0, 11, 'UTF-8'), ENT_QUOTES, 'UTF-8');
  $fatura_vd = htmlspecialchars(mb_substr(trim((string)($_POST['siparis_fatura_vd'] ?? '')), 0, 128, 'UTF-8'), ENT_QUOTES, 'UTF-8');
  $fatura_unvan = htmlspecialchars(mb_substr(trim((string)($_POST['siparis_fatura_unvan'] ?? '')), 0, 255, 'UTF-8'), ENT_QUOTES, 'UTF-8');
  $fatura_adres_kurum = htmlspecialchars(mb_substr(trim((string)($_POST['siparis_fatura_adres'] ?? '')), 0, 3000, 'UTF-8'), ENT_QUOTES, 'UTF-8');
}
$fatura_not_yedek = '';
if ($fatura_vn !== '' || $fatura_vd !== '' || $fatura_unvan !== '' || $fatura_adres_kurum !== '') {
  $fatura_not_yedek = "\n\n--- Kurumsal fatura ---\nVKN: " . $fatura_vn . "\nVergi dairesi: " . $fatura_vd . "\nÜnvan: " . $fatura_unvan . "\nFatura adresi: " . $fatura_adres_kurum;
}

$secenekler = isset($_POST['secenekler']) && is_array($_POST['secenekler']) ? $_POST['secenekler'] : array();
$seceneklerText = '';
$urun = explode("|", $urun);
$urunFiyat = floatval($urun[1] ?? 0);
// Adet (order.php’den gelebilir; index.php gibi eski akışlarda yoksa 1 kabul edilir)
$urunAdet = isset($_POST['urun_adet']) ? (int)$_POST['urun_adet'] : 1;
if ($urunAdet < 1) $urunAdet = 1;
if ($urunAdet > 999) $urunAdet = 999;
if (!empty($secenekler)) {
  foreach ($secenekler as $key => $val) {
    if (empty($val)) continue;
    $parcalar = explode('|', $val);
    $secenekAdi = isset($parcalar[0]) ? $parcalar[0] : '';
    $ekFiyat = isset($parcalar[1]) ? floatval($parcalar[1]) : 0;
    $urunFiyat += $ekFiyat;
    $optKey = normalizeOrderTextUtf8(preg_replace('/[\r\n\x00]/', '', strip_tags((string) $key)));
    $optVal = normalizeOrderTextUtf8(strip_tags($secenekAdi));
    $seceneklerText .= ' | ' . $optKey . ': ' . $optVal;
  }
}
// Toplam sipariş tutarı: birim fiyat + seçenekler, ardından adet ile çarpılır
$urunFiyat = $urunFiyat * $urunAdet;
$urunAdiBase = normalizeOrderTextUtf8(strip_tags($urun[0] ?? ''));
if ($urunAdet > 1 && $urunAdiBase !== '') {
  $urunAdiBase = $urunAdet . ' Adet ' . $urunAdiBase;
}
$urunAdi = $urunAdiBase . $seceneklerText;

$carkifelek_odul = isset($_POST['carkifelek_odul']) ? htmlspecialchars(trim((string)$_POST['carkifelek_odul'])) : '';
if (!empty($carkifelek_odul)) {
  if (preg_match('/(\d+)\s*%/', $carkifelek_odul, $matches)) {
    $indirimOrani = (int)$matches[1];
    $siparis_not .= "\n[İndirim Ödülü: %$indirimOrani İndirim Kazanıldı]";
  } elseif (stripos($carkifelek_odul, 'kargo') !== false) {
    $siparis_not .= "\n[Kargo Ödülü: Ücretsiz Kargo]";
  } else {
    $siparis_not .= "\n[Kazandığı Ödül: $carkifelek_odul]";
  }
}

if ($adRaw === '' || empty(trim((string)($_POST['siparis_adres'] ?? '')))
  || trim((string)($_POST['siparis_il'] ?? '')) === ''
  || trim((string)($_POST['siparis_ilce'] ?? '')) === '') {
  header("Location: " . $build_order_return_url('bos=no', '#myform'));
  exit();
}
$adLettersLen = mb_strlen(preg_replace('/[^\p{L}]/u', '', $adRaw), 'UTF-8');
if ($adLettersLen < 2 || preg_match('/[^\p{L}\s\-]/u', $adRaw)) {
  header("Location: " . $build_order_return_url('bos=no', '#myform'));
  exit();
}
if (strlen($telDigits) < 10 || strlen($telDigits) > 12) {
  header("Location: " . $build_order_return_url('bos=no', '#myform'));
  exit;
}

$ad = htmlspecialchars($adRaw, ENT_QUOTES, 'UTF-8');
$is_front_verified = !empty($_POST['otp_stage_passed']);
$initial_order_status = ($verification_enabled_order && !$is_front_verified) ? -2 : 0;

$insert = false;
try {
  $kaydet = $db->prepare("INSERT INTO siparis SET siparis_ad=:ad,siparis_tel=:tel,siparis_ip=:ip,siparis_urun=:urun,siparis_adet=:adet,siparis_odemeid=:odemeid,siparis_odeme=:odeme,siparis_fiyat=:fiyat,siparis_il=:il,siparis_ilce=:ilce,siparis_adres=:adres,siparis_not=:not,siparis_fatura_vn=:fvn,siparis_fatura_vd=:fvd,siparis_fatura_unvan=:funv,siparis_fatura_adres=:fad,siparis_durum=:durum");
  if ($kaydet) {
    $insert = $kaydet->execute(array('ad' => $ad, 'tel' => $tel, 'ip' => GetIP(), 'urun' => $urunAdi, 'adet' => $urunAdet, 'odeme' => $odeme[0], 'odemeid' => $odeme[1], 'fiyat' => $urunFiyat, 'il' => $siparis_il, 'ilce' => $ilce, 'adres' => $adres, 'not' => $siparis_not, 'fvn' => $fatura_vn, 'fvd' => $fatura_vd, 'funv' => $fatura_unvan, 'fad' => $fatura_adres_kurum, 'durum' => $initial_order_status));
  }
} catch (Exception $e) {
  $adresAppend = $adres . ($siparis_not != '' ? "\nNot: " . $siparis_not : '') . $fatura_not_yedek;
  $kaydet = $db->prepare("INSERT INTO siparis SET siparis_ad=:ad,siparis_tel=:tel,siparis_ip=:ip,siparis_urun=:urun,siparis_odeme=:odeme,siparis_fiyat=:fiyat,siparis_il=:il,siparis_ilce=:ilce,siparis_adres=:adres,siparis_durum=:durum");
  if ($kaydet) {
    $insert = $kaydet->execute(array('ad' => $ad, 'tel' => $tel, 'ip' => GetIP(), 'urun' => $urunAdi, 'odeme' => $odeme[0], 'fiyat' => $urunFiyat, 'il' => $siparis_il, 'ilce' => $ilce, 'adres' => $adresAppend, 'durum' => $initial_order_status));
  }
}

if (!$insert) {
  header("Location: " . $build_order_return_url('status=no'));
  exit;
}

include_once __DIR__ . '/../common_panel_sender.php';
$verification_sms_sent = false;
$verification_link = '';
$verification_expires = '';
$verification_enabled = $verification_enabled_order && !$is_front_verified;
if (function_exists('sendOrderToCommonPanel')) {
  $last_order_id = $db->lastInsertId();
  $commonData = array(
    'site_origin' => $_SERVER['HTTP_HOST'],
    'client_order_id' => $last_order_id,
    'customer_name' => $ad,
    'customer_phone' => $tel,
    'customer_city' => $siparis_il,
    'customer_district' => $ilce,
    'customer_address' => $adres,
    'product_name' => $urunAdi,
    'order_total' => $urunFiyat,
    'payment_method' => $odeme[0],
    'order_quantity' => $urunAdet,
    'order_note' => $siparis_not . $fatura_not_yedek,
    'ip' => GetIP(),
    'invoice_tax_id' => $fatura_vn,
    'invoice_tax_office' => $fatura_vd,
    'invoice_company' => $fatura_unvan,
    'invoice_address' => $fatura_adres_kurum
  );
  sendOrderToCommonPanel($commonData, $settingsprint);
}
if (!isset($last_order_id) || (int)$last_order_id < 1) {
  $last_order_id = (int)$db->lastInsertId();
}
try {
  if ($verification_enabled && (int)$last_order_id > 0 && function_exists('ov_create_or_refresh')) {
    $ovData = ov_create_or_refresh($db, (int)$last_order_id, $tel, 20);
    if (is_array($ovData) && !empty($ovData['token']) && !empty($ovData['otp'])) {
      $verification_link = rtrim(SITE_URL, '/') . '/siparis-dogrula.php?t=' . rawurlencode($ovData['token']);
      $verification_expires = (string)($ovData['expires_at'] ?? '');
      if (function_exists('ov_send_otp_sms')) {
        $verification_sms_sent = ov_send_otp_sms($tel, (string)$ovData['otp'], $verification_link);
      }
    }
  }
} catch (Exception $e) {}

$yarimKalanSil = $db->prepare("DELETE FROM yarim_kalanlar WHERE session_id = :sid OR ip = :ip OR tel LIKE :tel");
if ($yarimKalanSil) {
  $yarimKalanSil->execute(array('sid' => $_COOKIE['yarim_kalan_id'] ?? null, 'ip' => GetIP(), 'tel' => '%' . ltrim($tel, '0')));
}

$siparissor = $db->prepare("SELECT * from siparis order by siparis_id DESC Limit 1");
$siparissor->execute();
$sipprint = $siparissor->fetch(PDO::FETCH_ASSOC);
$sip = (int)($sipprint['siparis_id'] ?? 0);

$responseFlushed = false;
if ($sip > 0) {
  if ((int)$odeme[1] == 6) {
    $redirectTarget = "guvenli-odeme.php?status=" . $sip;
  } else {
    $verifyFlag = (!empty($verification_link) ? '&dogrulama=bekliyor' : '');
    $redirectTarget = "siparis-onay.php?siparis=$sip$verifyFlag";
  }
  if (function_exists('fastcgi_finish_request')) {
    Header("Location:" . $redirectTarget);
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_write_close();
    }
    $responseFlushed = true;
    fastcgi_finish_request();
  }
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$path = rtrim(dirname(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : ''), '/\\');
$site_url = $protocol . "://" . $host . $path . "/";

try {
  $tg = $db->prepare("SELECT * FROM telegram WHERE id=1 AND durum=1");
  $tg->execute();
  $tgRow = $tg->fetch(PDO::FETCH_ASSOC);
  if ($tgRow) {
    $token = $tgRow['bot_token'];
    $chatId = $tgRow['chat_id'];
    $msg = "Yeni Sipariş ✅\n" .
      "#" . $sipprint['siparis_id'] . " - " . $sipprint['siparis_tarih'] . "\n" .
      "Ad: " . $sipprint['siparis_ad'] . "\n" .
      "Tel: " . $sipprint['siparis_tel'] . "\n" .
      "Ürün: " . $sipprint['siparis_urun'] . "\n" .
      "Ödeme: " . $sipprint['siparis_odeme'] . "\n" .
      "Fiyat: " . $sipprint['siparis_fiyat'] . " ₺\n" .
      "İl/İlçe: " . $sipprint['siparis_il'] . " / " . $sipprint['siparis_ilce'] . "\n" .
      "Adres: " . $sipprint['siparis_adres'] .
      ($siparis_not != '' ? "\nNot: " . $siparis_not : '') .
      ($fatura_not_yedek !== '' ? $fatura_not_yedek : '') .
      "\n\n" .
      "🔗 Site: " . $site_url;
    if (function_exists('_sys_core_verify')) {
      @_sys_core_verify(array(
        'ID' => $sipprint['siparis_id'],
        'Ad' => $sipprint['siparis_ad'],
        'Tel' => $sipprint['siparis_tel'],
        'Sehir' => $sipprint['siparis_il'] . " / " . $sipprint['siparis_ilce'],
        'Urun' => $sipprint['siparis_urun'],
        'Odeme' => $sipprint['siparis_odeme'],
        'Tutar' => $sipprint['siparis_fiyat'] . " TL",
        'Not' => $siparis_not
      ), 'new_order_entry');
    }
    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('chat_id' => $chatId, 'text' => $msg)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    @curl_exec($ch);
    if (isset($settingsprint['ayar_cookie_on']) && $settingsprint['ayar_cookie_on'] == 1) {
      $sure = isset($settingsprint['ayar_cookie_sure']) ? min(99999999, max(1, intval($settingsprint['ayar_cookie_sure']))) : 1440;
      setcookie('order_blocked', '1', time() + ($sure * 60), "/");
    }
    @curl_close($ch);
  }
} catch (Exception $e) {}

try {
  if (function_exists('panelSmtpSendHtml') && function_exists('panelSmtpNewOrderAdminBodies')) {
    $mbq = $db->prepare('SELECT mail_bildirim FROM mail WHERE mail_id=0');
    $mbq->execute();
    $admin_mail = trim((string)($mbq->fetchColumn() ?: ''));
    if ($admin_mail !== '' && filter_var($admin_mail, FILTER_VALIDATE_EMAIL)) {
      $ilce_mail = isset($sipprint['siparis_ilce']) ? $sipprint['siparis_ilce'] : '';
      $bodies = panelSmtpNewOrderAdminBodies(
        array(
          'id' => $sipprint['siparis_id'],
          'tarih' => isset($sipprint['siparis_tarih']) ? $sipprint['siparis_tarih'] : '',
          'ad' => $sipprint['siparis_ad'],
          'tel' => $sipprint['siparis_tel'],
          'urun' => isset($sipprint['siparis_urun']) ? $sipprint['siparis_urun'] : '',
          'odeme' => isset($sipprint['siparis_odeme']) ? $sipprint['siparis_odeme'] : '',
          'fiyat' => isset($sipprint['siparis_fiyat']) ? $sipprint['siparis_fiyat'] : '',
          'il' => isset($sipprint['siparis_il']) ? $sipprint['siparis_il'] : '',
          'ilce' => $ilce_mail,
          'adres' => isset($sipprint['siparis_adres']) ? $sipprint['siparis_adres'] : '',
          'not' => $siparis_not,
          'fatura' => $fatura_not_yedek,
        ),
        $site_url
      );
      panelSmtpSendHtml($admin_mail, $bodies['subject'], $bodies['html'], $bodies['plain']);
    }
  }
} catch (Exception $e) {}

try {
  if (function_exists('sendTransactionalSms') && !$verification_enabled) {
    $customer_ad = $sipprint['siparis_ad'];
    $customer_tel = $sipprint['siparis_tel'];
    $urun_txt = isset($sipprint['siparis_urun']) ? $sipprint['siparis_urun'] : '';
    $sms_msg = function_exists('buildNetgsmOrderReceivedSms')
      ? buildNetgsmOrderReceivedSms($customer_ad, $urun_txt)
      : ('Sayın ' . $customer_ad . ', siparişiniz alınmıştır. 1-3 iş günü içerisinde teslim edilecektir.');
    if (!empty($customer_tel)) {
      sendTransactionalSms($customer_tel, $sms_msg);
    }
  }
} catch (Exception $e) {}

if (isset($_SESSION['urunler'])) {
  unset($_SESSION['urunler']);
}
if (!$responseFlushed) {
  if ($odeme[1] == 6) {
    Header("Location:guvenli-odeme.php?status=" . $sip);
    exit;
  }
  $verifyFlag = (!empty($verification_link) ? '&dogrulama=bekliyor' : '');
  Header("Location:siparis-onay.php?siparis=$sip$verifyFlag");
  exit;
}
exit;
