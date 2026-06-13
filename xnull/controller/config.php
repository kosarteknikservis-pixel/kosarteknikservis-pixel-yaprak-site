<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('default_socket_timeout', '10');
date_default_timezone_set('Europe/Istanbul');
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$yaprakDb = [
    'dbHost' => '127.0.0.1',
    'dbAdi' => '',
    'Kullanici' => '',
    'Sifre' => '',
];
$localConfigFile = __DIR__ . '/config.local.php';
if (is_file($localConfigFile)) {
    $loaded = require $localConfigFile;
    if (is_array($loaded)) {
        $yaprakDb = array_merge($yaprakDb, $loaded);
    }
}

if (!@$_SESSION[ 'dbdegistir' ]) {

    try {
        $dbAdi = $yaprakDb['dbAdi'];
        $Kullanici = $yaprakDb['Kullanici'];
        $Sifre = $yaprakDb['Sifre'];
        $dbHost = $yaprakDb['dbHost'];
        $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbAdi . ';charset=utf8mb4';

        try {
            $db = new PDO($dsn, $Kullanici, $Sifre);
        } catch (PDOException $e) {
            // Yerel geliştirme ortamında (XAMPP) root/boş şifre fallback.
            $serverHost = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
            $isLocalEnv = ($serverHost === '' || strpos($serverHost, '127.0.0.1') !== false || strpos($serverHost, 'localhost') !== false);
            if (!$isLocalEnv) {
                throw $e;
            }
            $db = new PDO($dsn, 'root', '');
        }
	//echo "veritabanı bağlantısı başarılı";

    }

    catch (PDOException $e) {

       echo "Veritabanı bağlantı hatası: " . $e->getMessage();
       exit;
   }
} else {

    try {
        
        $dbAdi = $_SESSION[ 'dbAdi' ];
        $Kullanici = $_SESSION[ 'Kullanici' ];
        $Sifre = $_SESSION[ 'Sifre' ];
        $dbHost = '127.0.0.1';
        $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbAdi . ';charset=utf8mb4';

        try {
            $db = new PDO($dsn, $Kullanici, $Sifre);
        } catch (PDOException $e) {
            // Yerelde session kullanıcısı başarısızsa root fallback.
            $serverHost = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
            $isLocalEnv = ($serverHost === '' || strpos($serverHost, '127.0.0.1') !== false || strpos($serverHost, 'localhost') !== false);
            if (!$isLocalEnv) {
                throw $e;
            }
            $db = new PDO($dsn, 'root', '');
        }
    //echo "veritabanı bağlantısı başarılı";

    }

    catch (PDOException $e) {

       echo "Veritabanı bağlantı hatası: " . $e->getMessage();
       exit;
   }
}

if (isset($db) && $db instanceof PDO) {
	require_once __DIR__ . '/../../include/legal-pages.php';
	legal_pages_ensure_schema($db);
}

// Global Site URL & Root Definition - Fully Dynamic & Portable
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(__DIR__)));
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
$host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';

// Get the directory containing this script, but from the web root's perspective
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// Yasal sayfa alt klasöründen (teslimat-kosullari/index.php vb.) yüklenince SITE_URL kök kalsın
if ($script_name !== '' && preg_match('#/(teslimat-kosullari|satis-politikasi|iptal-iade|gizlilik)(/index\.php)?$#', $script_name)) {
	$script_name = preg_replace('#/(teslimat-kosullari|satis-politikasi|iptal-iade|gizlilik)(/index\.php)?$#', '/index.php', $script_name);
}

// Determine the base path of the project (e.g., /t1/ or /)
$base_path = str_replace('\\', '/', dirname($script_name));
if (strpos($base_path, '/xnull') !== false) {
    $base_path = str_replace('/xnull/controller', '', $base_path);
    $base_path = str_replace('/xnull', '', $base_path);
}
$base_path = rtrim($base_path, '/') . '/';

$site_url = $protocol . $host . $base_path;
$site_path = $base_path;

if (!defined('SITE_URL')) {
    define('SITE_URL', $site_url);
}
if (!defined('SITE_PATH')) {
    define('SITE_PATH', $site_path);
}

/** Panel (xnull) CSS/JS cache bust — tek yerden; vitrin include/head.php ile karışmaz */
if (!defined('PANEL_ASSET_VER')) {
    define('PANEL_ASSET_VER', '2.2');
}
if (!defined('PANEL_VIEWPORT_CSS_VER')) {
    define('PANEL_VIEWPORT_CSS_VER', '7');
}

/**
 * SEO Optimization: Convert Image to WebP
 */
if (!function_exists('convertToWebp')) {
    function convertToWebp($source, $destination, $quality = 80) {
        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagewebp')) {
            return false;
        }
        $info = @getimagesize($source);
        if ($info === false) return false;

        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg': $image = @imagecreatefromjpeg($source); break;
            case 'image/png':
                $image = @imagecreatefrompng($source);
                if ($image) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
            case 'image/gif': $image = @imagecreatefromgif($source); break;
            case 'image/webp': return @copy($source, $destination);
            default: return false;
        }

        if (!$image) return false;
        $result = imagewebp($image, $destination, $quality);
        imagedestroy($image);
        return $result;
    }
}

/**
 * SEO Optimization: Generate URL-friendly slug
 */
if (!function_exists('seoFriendlySlug')) {
    function seoFriendlySlug($text) {
        $text = (string) ($text ?? '');
        $find = array('Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ö', 'ı', '+', '#');
        $replace = array('c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'o', 'i', 'plus', 'sharp');
        $text = str_replace($find, $replace, strip_tags($text));
        $text = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
        return preg_replace('/-+/', '-', $text);
    }
}

/**
 * Global IP Detection Function
 */
if (!function_exists('GetIP')) {
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
}

/**
 * Netgsm için GSM: 5XXXXXXXXX (10 hane, başta 5).
 * Kaynak: Netgsm REST örnekleri (no: "5XXXXXXXXX").
 */
if ( ! function_exists( 'normalizeNetgsmGsm' ) ) {
	function normalizeNetgsmGsm( $gsm ) {
		$d = preg_replace( '/\D/', '', (string) $gsm );
		if ( $d === '' ) {
			return '';
		}
		if ( strlen( $d ) >= 12 && strncmp( $d, '90', 2 ) === 0 ) {
			$d = substr( $d, 2 );
		}
		if ( strlen( $d ) === 11 && $d[0] === '0' ) {
			$d = substr( $d, 1 );
		}
		if ( strlen( $d ) === 10 && $d[0] === '5' ) {
			return $d;
		}
		return '';
	}
}

/**
 * Sipariş alındı SMS metni (müşteri).
 */
if ( ! function_exists( 'buildNetgsmOrderReceivedSms' ) ) {
	function buildNetgsmOrderReceivedSms( $customerName, $productName ) {
		$name    = trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $customerName ) ) );
		$product = trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $productName ) ) );
		if ( $name === '' ) {
			$name = 'Müşterimiz';
		}
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $product, 'UTF-8' ) > 90 ) {
				$product = mb_substr( $product, 0, 87, 'UTF-8' ) . '...';
			}
		} elseif ( strlen( $product ) > 90 ) {
			$product = substr( $product, 0, 87 ) . '...';
		}
		if ( $product === '' ) {
			$product = 'Siparişiniz';
		}
		return sprintf(
			'Sayın %s, siparişiniz alınmıştır. Ürününüz: %s. 1-3 iş günü içerisinde teslim edilecektir. Teşekkür ederiz.',
			$name,
			$product
		);
	}
}

/** Ödeme alanından (örn. "Kapıda-3") veya odeme_id ile PayTR / online kart ödemesi mi? */
if ( ! function_exists( 'order_payment_parse_id' ) ) {
	function order_payment_parse_id( $odemeRaw ) {
		$raw = trim( (string) $odemeRaw );
		if ( preg_match( '/-(\d+)$/', $raw, $m ) ) {
			return (int) $m[1];
		}
		if ( ctype_digit( $raw ) ) {
			return (int) $raw;
		}
		return 0;
	}
}

if ( ! function_exists( 'order_payment_is_online_card' ) ) {
	function order_payment_is_online_card( $odemeIdOrRaw ) {
		$id = is_numeric( $odemeIdOrRaw ) ? (int) $odemeIdOrRaw : order_payment_parse_id( $odemeIdOrRaw );
		// guvenli-odeme.php (PayTR) — front-order-flow ile aynı id
		return $id === 6;
	}
}

/** Kapıda / havale vb. için SMS OTP; kredi kartında (PayTR) hayır */
if ( ! function_exists( 'order_payment_needs_sms_verify' ) ) {
	function order_payment_needs_sms_verify( $odemeIdOrRaw, $settings = null ) {
		global $settingsprint;
		if ( ! is_array( $settings ) ) {
			$settings = isset( $settingsprint ) && is_array( $settingsprint ) ? $settingsprint : array();
		}
		$globalOn = ! isset( $settings['ayar_siparis_dogrulama_on'] ) || (int) $settings['ayar_siparis_dogrulama_on'] === 1;
		if ( ! $globalOn || order_payment_is_online_card( $odemeIdOrRaw ) ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'order_send_customer_confirmation_sms' ) ) {
	function order_send_customer_confirmation_sms( $customerName, $customerTel, $productName ) {
		if ( ! function_exists( 'sendTransactionalSms' ) ) {
			return false;
		}
		$tel = preg_replace( '/\D+/', '', (string) $customerTel );
		if ( $tel === '' ) {
			return false;
		}
		$msg = function_exists( 'buildNetgsmOrderReceivedSms' )
			? buildNetgsmOrderReceivedSms( $customerName, $productName )
			: ( 'Sayın ' . trim( (string) $customerName ) . ', siparişiniz alınmıştır. 1-3 iş günü içerisinde teslim edilecektir.' );
		return sendTransactionalSms( $tel, $msg );
	}
}

/**
 * NetGSM SMS — REST v2 (JSON + Basic Auth), Türkçe: encoding TR.
 * https://api.netgsm.com.tr/sms/rest/v2/send
 */
if ( ! function_exists( 'netGsmSend' ) ) {
	function netGsmSend( $gsm, $message ) {
		global $db;

		try {
			$smsQuery = $db->prepare( 'SELECT * FROM sms WHERE sms_id = 0' );
			$smsQuery->execute();
			$smsRow = $smsQuery->fetch( PDO::FETCH_ASSOC );

			if ( ! $smsRow || (int) $smsRow['sms_durum'] === 0 ) {
				$GLOBALS['sms_last_error'] = 'SMS gönderimi pasif.';
				$GLOBALS['sms_last_response'] = '';
				return false;
			}

			$user   = trim( (string) $smsRow['sms_kullanici'] );
			$pass   = (string) $smsRow['sms_sifre'];
			$header = trim( (string) $smsRow['sms_baslik'] );
			if ( $user === '' || $pass === '' || $header === '' ) {
				$GLOBALS['sms_last_error'] = 'Netgsm kullanıcı/şifre/başlık eksik.';
				$GLOBALS['sms_last_response'] = '';
				return false;
			}

			$no = normalizeNetgsmGsm( $gsm );
			if ( $no === '' ) {
				$GLOBALS['sms_last_error'] = 'Telefon formatı geçersiz.';
				$GLOBALS['sms_last_response'] = '';
				return false;
			}

			$msg = trim( (string) $message );
			if ( $msg === '' ) {
				$GLOBALS['sms_last_error'] = 'Mesaj boş.';
				$GLOBALS['sms_last_response'] = '';
				return false;
			}

			$payload = array(
				'msgheader' => $header,
				'encoding'  => 'TR',
				'messages'  => array(
					array(
						'msg' => $msg,
						'no'  => $no,
					),
				),
			);

			$json     = json_encode( $payload, JSON_UNESCAPED_UNICODE );
			$auth     = base64_encode( $user . ':' . $pass );
			$url      = 'https://api.netgsm.com.tr/sms/rest/v2/send';
			$ch       = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $json );
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					'Content-Type: application/json; charset=UTF-8',
					'Authorization: Basic ' . $auth,
				)
			);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 4 );
			$body = curl_exec( $ch );
			curl_close( $ch );

			if ( $body === false || $body === '' ) {
				$GLOBALS['sms_last_error'] = 'Netgsm boş cevap döndü.';
				$GLOBALS['sms_last_response'] = '';
				return false;
			}
			$GLOBALS['sms_last_response'] = substr( (string) $body, 0, 500 );
			$data = json_decode( $body, true );
			if ( is_array( $data ) && isset( $data['code'] ) && (string) $data['code'] === '00' ) {
				$GLOBALS['sms_last_error'] = '';
				return true;
			}
			$GLOBALS['sms_last_error'] = 'Netgsm yanıtı başarısız.';
			return false;
		} catch ( Exception $e ) {
			$GLOBALS['sms_last_error'] = 'Netgsm exception: ' . $e->getMessage();
			$GLOBALS['sms_last_response'] = '';
			return false;
		}
	}
}

/**
 * Mutlucell SMS — XML API (sndblkex)
 * Endpoint: https://smsgw.mutlucell.com/smsgw-ws/sndblkex
 */
if ( ! function_exists( 'mutluCellSend' ) ) {
	function mutluCellSend( $gsm, $message ) {
		global $db;

		try {
			$smsQuery = $db->prepare( 'SELECT * FROM sms WHERE sms_id = 0' );
			$smsQuery->execute();
			$smsRow = $smsQuery->fetch( PDO::FETCH_ASSOC );
			if ( ! $smsRow || (int) $smsRow['sms_durum'] === 0 ) {
				$GLOBALS['sms_last_error'] = 'SMS gönderimi pasif.';
				$GLOBALS['sms_last_response'] = '';
				return false;
			}

			$user   = trim( (string) $smsRow['sms_kullanici'] );
			$pass   = (string) $smsRow['sms_sifre'];
			$header = trim( (string) $smsRow['sms_baslik'] );
			if ( $user === '' || $pass === '' || $header === '' ) {
				$GLOBALS['sms_last_error'] = 'Mutlucell kullanıcı/şifre/başlık eksik.';
				$GLOBALS['sms_last_response'] = '';
				return false;
			}

			$no = preg_replace( '/\D/', '', (string) $gsm );
			if ( strlen( $no ) === 10 && $no[0] === '5' ) {
				$no = '90' . $no;
			} elseif ( strlen( $no ) === 11 && $no[0] === '0' && $no[1] === '5' ) {
				$no = '9' . $no;
			}
			if ( strlen( $no ) !== 12 || strncmp( $no, '90', 2 ) !== 0 ) {
				$GLOBALS['sms_last_error'] = 'Telefon formatı geçersiz (90XXXXXXXXXX gerekli).';
				$GLOBALS['sms_last_response'] = '';
				return false;
			}

			$msg = trim( (string) $message );
			if ( $msg === '' ) {
				$GLOBALS['sms_last_error'] = 'Mesaj boş.';
				$GLOBALS['sms_last_response'] = '';
				return false;
			}

			$xml = '<?xml version="1.0" encoding="UTF-8"?>'
				. '<smspack ka="' . htmlspecialchars( $user, ENT_QUOTES, 'UTF-8' ) . '"'
				. ' pwd="' . htmlspecialchars( $pass, ENT_QUOTES, 'UTF-8' ) . '"'
				. ' org="' . htmlspecialchars( $header, ENT_QUOTES, 'UTF-8' ) . '">'
				. '<mesaj>'
				. '<metin>' . htmlspecialchars( $msg, ENT_QUOTES, 'UTF-8' ) . '</metin>'
				. '<nums>' . htmlspecialchars( $no, ENT_QUOTES, 'UTF-8' ) . '</nums>'
				. '</mesaj>'
				. '</smspack>';

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex' );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $xml );
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					'Content-Type: text/xml; charset=UTF-8',
				)
			);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 4 );
			$body = curl_exec( $ch );
			$curlErrNo = curl_errno( $ch );
			$curlErr   = curl_error( $ch );
			$httpCode  = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			curl_close( $ch );
			if ( $body === false || $body === '' ) {
				if ( $curlErrNo !== 0 ) {
					$GLOBALS['sms_last_error'] = 'Mutlucell cURL hata #' . $curlErrNo . ': ' . $curlErr;
				} elseif ( $httpCode > 0 ) {
					$GLOBALS['sms_last_error'] = 'Mutlucell boş cevap döndü. HTTP: ' . $httpCode;
				} else {
					$GLOBALS['sms_last_error'] = 'Mutlucell boş cevap döndü.';
				}
				$GLOBALS['sms_last_response'] = '';
				return false;
			}

			$resp = trim( strip_tags( (string) $body ) );
			$GLOBALS['sms_last_response'] = substr( $resp, 0, 500 );
			// Mutlucell başarılı yanıtlarda sayısal mesaj id veya "$" içeren onay cevabı dönebiliyor.
			// Destek teyidine göre "$" ile dönen cevap başarılı kabul edilir.
			$respCompact      = preg_replace( '/\s+/', '', (string) $resp );
			$isNumericSuccess = preg_match( '/^\d+$/', $respCompact ) && (int) $respCompact > 1000;
			$isDollarSuccess  = strpos( $respCompact, '$' ) !== false;
			// Bazı Mutlucell yanıtlarda başarı kodu satır sonunda ";0" veya ":0" olarak gelir.
			$isSuffixZeroOk   = preg_match( '/[;:]0$/', $respCompact ) === 1;
			if ( $isNumericSuccess || $isDollarSuccess || $isSuffixZeroOk ) {
				$GLOBALS['sms_last_error'] = '';
				return true;
			}
			$GLOBALS['sms_last_error'] = 'Mutlucell yanıtı: ' . substr( $resp, 0, 160 );
			return false;
		} catch ( Exception $e ) {
			$GLOBALS['sms_last_error'] = 'Mutlucell exception: ' . $e->getMessage();
			$GLOBALS['sms_last_response'] = '';
			return false;
		}
	}
}

/**
 * SMS gönderimi panelde seçilen sağlayıcıya göre yapılır.
 * sms_provider: mutlucell | netgsm
 */
if ( ! function_exists( 'sendTransactionalSms' ) ) {
	function sendTransactionalSms( $gsm, $message ) {
		global $db;

		$provider = 'mutlucell';
		try {
			$q = $db->prepare( 'SELECT sms_provider FROM sms WHERE sms_id = 0 LIMIT 1' );
			$q->execute();
			$p = trim( (string) $q->fetchColumn() );
			if ( $p !== '' ) {
				$provider = strtolower( $p );
			}
		} catch ( Exception $e ) {
			$provider = 'mutlucell';
		}

		if ( $provider === 'netgsm' ) {
			return function_exists( 'netGsmSend' ) ? netGsmSend( $gsm, $message ) : false;
		}
		return function_exists( 'mutluCellSend' ) ? mutluCellSend( $gsm, $message ) : false;
	}
}

if ( ! function_exists( 'smsLastErrorGet' ) ) {
	function smsLastErrorGet() {
		return isset( $GLOBALS['sms_last_error'] ) ? (string) $GLOBALS['sms_last_error'] : '';
	}
}

if ( ! function_exists( 'smsLastResponseGet' ) ) {
	function smsLastResponseGet() {
		return isset( $GLOBALS['sms_last_response'] ) ? (string) $GLOBALS['sms_last_response'] : '';
	}
}

/**
 * Genel ayarlardaki SMTP ile HTML e-posta gönderir.
 *
 * @return array{success:bool, error:?string}
 */
if ( ! function_exists( 'panelSmtpSendHtml' ) ) {
	function panelSmtpSendHtml( $to, $subject, $htmlBody, $plainBody = '' ) {
		global $db;
		$fail = function ( $msg ) {
			return array( 'success' => false, 'error' => $msg );
		};
		try {
			$to = trim( (string) $to );
			if ( $to === '' || ! filter_var( $to, FILTER_VALIDATE_EMAIL ) ) {
				return $fail( 'Geçersiz alıcı e-posta.' );
			}
			$subj = trim( (string) $subject );
			if ( $subj === '' ) {
				$subj = 'Bildirim';
			}
			$html = (string) $htmlBody;
			$plain = trim( (string) $plainBody );
			if ( $plain === '' ) {
				$plain = trim( preg_replace( '/\s+/', ' ', strip_tags( str_ireplace( array( '<br>', '<br/>', '<br />' ), "\n", $html ) ) ) );
			}

			$ms = $db->prepare( 'SELECT * FROM mail WHERE mail_id=0' );
			$ms->execute();
			$row = $ms->fetch( PDO::FETCH_ASSOC );
			if ( ! $row || trim( (string) $row['mail_host'] ) === '' || trim( (string) $row['mail_user'] ) === '' ) {
				return $fail( 'SMTP ayarları eksik (sunucu / kullanıcı).' );
			}

			$pm_path = dirname( __DIR__, 2 ) . '/phpmail/class.phpmailer.php';
			if ( ! is_readable( $pm_path ) ) {
				return $fail( 'phpmail/class.phpmailer.php bulunamadı.' );
			}

			require_once $pm_path;

			$mailer            = new PHPMailer();
			$mailer->PluginDir = dirname( $pm_path ) . DIRECTORY_SEPARATOR;
			$mailer->IsSMTP();
			$mailer->SMTPAuth   = true;
			$mailer->SMTPSecure = $row['mail_secure'];
			$mailer->Host       = $row['mail_host'];
			$mailer->Port       = (int) $row['mail_port'];
			if ( $mailer->Port <= 0 ) {
				$mailer->Port = ( strtolower( (string) $row['mail_secure'] ) === 'ssl' ) ? 465 : 587;
			}
			$mailer->IsHTML( true );
			$mailer->CharSet = 'utf-8';
			$mailer->Username = $row['mail_user'];
			$mailer->Password = $row['mail_pass'];
			$from_addr        = trim( (string) $row['mail_sender'] ) !== '' ? $row['mail_sender'] : $row['mail_user'];
			$from_name        = trim( (string) $row['mail_name'] ) !== '' ? $row['mail_name'] : 'Site';
			$mailer->SetFrom( $from_addr, $from_name );
			$mailer->AddAddress( $to );
			$mailer->Subject = $subj;
			$mailer->Body    = $html;
			$mailer->AltBody = $plain;

			if ( $mailer->Send() ) {
				return array( 'success' => true, 'error' => null );
			}
			$err = $mailer->ErrorInfo ? $mailer->ErrorInfo : 'Gönderilemedi';
			if ( function_exists( 'mb_substr' ) ) {
				$err = mb_substr( $err, 0, 400, 'UTF-8' );
			} else {
				$err = substr( $err, 0, 400 );
			}
			return $fail( $err );
		} catch ( Exception $e ) {
			return $fail( $e->getMessage() );
		}
	}
}

/**
 * Admin bildirimi: yeni sipariş e-posta gövdesi (HTML + düz metin).
 *
 * @param array  $o        id, tarih, ad, tel, urun, odeme, fiyat, il, ilce, adres, not, fatura
 * @param string $site_url
 * @return array{subject:string,html:string,plain:string}
 */
if ( ! function_exists( 'panelSmtpNewOrderAdminBodies' ) ) {
	function panelSmtpNewOrderAdminBodies( array $o, $site_url ) {
		$h = function ( $s ) {
			return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
		};
		$id     = isset( $o['id'] ) ? $o['id'] : '';
		$tarih  = isset( $o['tarih'] ) ? $o['tarih'] : '';
		$ad     = isset( $o['ad'] ) ? $o['ad'] : '';
		$tel    = isset( $o['tel'] ) ? $o['tel'] : '';
		$urun   = isset( $o['urun'] ) ? $o['urun'] : '';
		$odeme  = isset( $o['odeme'] ) ? $o['odeme'] : '';
		$fiyat  = isset( $o['fiyat'] ) ? $o['fiyat'] : '';
		$il     = isset( $o['il'] ) ? $o['il'] : '';
		$ilce   = isset( $o['ilce'] ) ? $o['ilce'] : '';
		$adres  = isset( $o['adres'] ) ? $o['adres'] : '';
		$not    = isset( $o['not'] ) ? $o['not'] : '';
		$fatura = isset( $o['fatura'] ) ? $o['fatura'] : '';

		$plain  = "Yeni Sipariş\n";
		$plain .= '#' . $id . ' - ' . $tarih . "\n";
		$plain .= 'Ad: ' . $ad . "\n";
		$plain .= 'Tel: ' . $tel . "\n";
		$plain .= 'Ürün: ' . $urun . "\n";
		$plain .= 'Ödeme: ' . $odeme . "\n";
		$plain .= 'Fiyat: ' . $fiyat . " ₺\n";
		$plain .= 'İl/İlçe: ' . $il . ' / ' . $ilce . "\n";
		$plain .= 'Adres: ' . $adres;
		if ( $not !== '' ) {
			$plain .= "\nNot: " . $not;
		}
		if ( $fatura !== '' ) {
			$plain .= $fatura;
		}
		if ( $site_url !== '' ) {
			$plain .= "\n\nSite: " . $site_url;
		}

		$rows = array(
			array( 'Sipariş no', '#' . $id ),
			array( 'Tarih', $tarih ),
			array( 'Ad Soyad', $ad ),
			array( 'Telefon', $tel ),
			array( 'Ürün', $urun ),
			array( 'Ödeme', $odeme ),
			array( 'Fiyat', $fiyat . ' ₺' ),
			array( 'İl / İlçe', $il . ' / ' . $ilce ),
			array( 'Adres', $adres ),
		);
		if ( $not !== '' ) {
			$rows[] = array( 'Not', $not );
		}
		if ( $fatura !== '' ) {
			$rows[] = array( 'Fatura / ek', trim( $fatura ) );
		}

		$html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222">';
		$html .= '<h2 style="margin:0 0 14px;font-size:18px">Yeni sipariş</h2>';
		$html .= '<table cellpadding="8" style="border-collapse:collapse;max-width:640px">';
		foreach ( $rows as $r ) {
			$html .= '<tr><td style="font-weight:bold;vertical-align:top;border-bottom:1px solid #eee;white-space:nowrap">' . $h( $r[0] ) . '</td>';
			$html .= '<td style="border-bottom:1px solid #eee;white-space:pre-wrap">' . $h( $r[1] ) . '</td></tr>';
		}
		$html .= '</table>';
		if ( $site_url !== '' ) {
			$html .= '<p style="margin-top:16px"><a href="' . $h( $site_url ) . '">' . $h( $site_url ) . '</a></p>';
		}
		$html .= '</body></html>';

		return array(
			'subject' => 'Yeni sipariş #' . $id,
			'html'    => $html,
			'plain'   => $plain,
		);
	}
}

/** PayTR ödemesi tamamlanana kadar panelde görünmez (Yeni Gelen = 0 değil) */
if ( ! function_exists( 'order_status_payment_pending' ) ) {
	function order_status_payment_pending() {
		return -3;
	}
}

if ( ! function_exists( 'order_build_fatura_not_from_row' ) ) {
	function order_build_fatura_not_from_row( array $row ) {
		$vn    = trim( (string) ( $row['siparis_fatura_vn'] ?? '' ) );
		$vd    = trim( (string) ( $row['siparis_fatura_vd'] ?? '' ) );
		$unvan = trim( (string) ( $row['siparis_fatura_unvan'] ?? '' ) );
		$adres = trim( (string) ( $row['siparis_fatura_adres'] ?? '' ) );
		if ( $vn === '' && $vd === '' && $unvan === '' && $adres === '' ) {
			return '';
		}
		return "\n\n--- Kurumsal fatura ---\nVKN: " . $vn . "\nVergi dairesi: " . $vd . "\nÜnvan: " . $unvan . "\nFatura adresi: " . $adres;
	}
}

if ( ! function_exists( 'order_set_blocked_cookie' ) ) {
	function order_set_blocked_cookie( $settings = null ) {
		global $settingsprint;
		if ( ! is_array( $settings ) ) {
			$settings = isset( $settingsprint ) && is_array( $settingsprint ) ? $settingsprint : array();
		}
		if ( ! isset( $settings['ayar_cookie_on'] ) || (int) $settings['ayar_cookie_on'] !== 1 ) {
			return;
		}
		$sure = isset( $settings['ayar_cookie_sure'] ) ? min( 99999999, max( 1, (int) $settings['ayar_cookie_sure'] ) ) : 1440;
		setcookie( 'order_blocked', '1', time() + ( $sure * 60 ), '/' );
	}
}

/** Ortak panel: kredi kartı / online ödeme — tahsilat kapıda değil, tutar gönderilmez. */
if ( ! function_exists( 'order_common_panel_is_prepaid' ) ) {
	function order_common_panel_is_prepaid( array $sipprint ) {
		$odemeId = (int) ( $sipprint['siparis_odemeid'] ?? 0 );
		if ( function_exists( 'order_payment_is_online_card' ) && order_payment_is_online_card( $odemeId ) ) {
			return true;
		}
		$odeme = mb_strtolower( (string) ( $sipprint['siparis_odeme'] ?? '' ), 'UTF-8' );
		if ( $odeme !== '' && (
			strpos( $odeme, 'kredi' ) !== false
			|| strpos( $odeme, 'paytr' ) !== false
			|| strpos( $odeme, 'online' ) !== false
			|| strpos( $odeme, 'kart' ) !== false
		) ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'order_build_common_panel_payload' ) ) {
	/**
	 * Ortak panele gidecek sipariş JSON'u.
	 * Kredi kartı: order_total=0 (kapıda tahsilat yok), nota ödeme bilgisi eklenir.
	 */
	function order_build_common_panel_payload( array $sipprint, array $opts = array() ) {
		$siparis_not      = isset( $opts['siparis_not'] ) ? (string) $opts['siparis_not'] : (string) ( $sipprint['siparis_not'] ?? '' );
		$fatura_not_yedek = isset( $opts['fatura_not_yedek'] ) ? (string) $opts['fatura_not_yedek'] : order_build_fatura_not_from_row( $sipprint );
		$settings         = isset( $opts['settings'] ) && is_array( $opts['settings'] ) ? $opts['settings'] : array();

		$site_url = defined( 'SITE_URL' ) ? rtrim( SITE_URL, '/' ) . '/' : '';
		if ( $site_url === '' && ! empty( $settings['ayar_siteurl'] ) ) {
			$site_url = rtrim( (string) $settings['ayar_siteurl'], '/' ) . '/';
		}

		$host = isset( $opts['site_origin'] ) ? (string) $opts['site_origin'] : '';
		if ( $host === '' && isset( $_SERVER['HTTP_HOST'] ) ) {
			$host = (string) $_SERVER['HTTP_HOST'];
		}
		if ( $host === '' && $site_url !== '' ) {
			$host = (string) parse_url( $site_url, PHP_URL_HOST );
		}

		$isPrepaid = order_common_panel_is_prepaid( $sipprint );
		$orderNote = $siparis_not . $fatura_not_yedek;
		if ( $isPrepaid ) {
			$prepaidTag = '[Kredi kartı ile ödendi — kapıda tahsilat yok]';
			if ( stripos( $orderNote, 'kapıda tahsilat yok' ) === false ) {
				$orderNote = trim( $orderNote ) === '' ? $prepaidTag : trim( $orderNote ) . "\n" . $prepaidTag;
			}
		}

		return array(
			'site_origin'          => $host,
			'client_order_id'      => (int) ( $sipprint['siparis_id'] ?? 0 ),
			'customer_name'        => (string) ( $sipprint['siparis_ad'] ?? '' ),
			'customer_phone'       => (string) ( $sipprint['siparis_tel'] ?? '' ),
			'customer_city'        => (string) ( $sipprint['siparis_il'] ?? '' ),
			'customer_district'    => (string) ( $sipprint['siparis_ilce'] ?? '' ),
			'customer_address'     => (string) ( $sipprint['siparis_adres'] ?? '' ),
			'product_name'         => (string) ( $sipprint['siparis_urun'] ?? '' ),
			'order_total'          => $isPrepaid ? 0.0 : (float) ( $sipprint['siparis_fiyat'] ?? 0 ),
			'payment_method'       => (string) ( $sipprint['siparis_odeme'] ?? '' ),
			'order_quantity'       => (int) ( $sipprint['siparis_adet'] ?? 1 ),
			'order_note'           => $orderNote,
			'ip'                   => (string) ( $sipprint['siparis_ip'] ?? '' ),
			'invoice_tax_id'       => (string) ( $sipprint['siparis_fatura_vn'] ?? '' ),
			'invoice_tax_office'   => (string) ( $sipprint['siparis_fatura_vd'] ?? '' ),
			'invoice_company'      => (string) ( $sipprint['siparis_fatura_unvan'] ?? '' ),
			'invoice_address'      => (string) ( $sipprint['siparis_fatura_adres'] ?? '' ),
		);
	}
}

/**
 * Telegram, admin e-posta ve (isteğe bağlı) ortak panel bildirimi.
 * Kredi kartı siparişlerinde PayTR onayı sonrası (pay_int.php) çağrılır.
 */
if ( ! function_exists( 'order_send_admin_new_order_notifications' ) ) {
	function order_send_admin_new_order_notifications( array $sipprint, array $opts = array() ) {
		global $db, $settingsprint;

		if ( empty( $sipprint['siparis_id'] ) ) {
			return false;
		}

		$settings           = isset( $opts['settings'] ) && is_array( $opts['settings'] ) ? $opts['settings'] : ( isset( $settingsprint ) && is_array( $settingsprint ) ? $settingsprint : array() );
		$siparis_not        = isset( $opts['siparis_not'] ) ? (string) $opts['siparis_not'] : (string) ( $sipprint['siparis_not'] ?? '' );
		$fatura_not_yedek   = isset( $opts['fatura_not_yedek'] ) ? (string) $opts['fatura_not_yedek'] : order_build_fatura_not_from_row( $sipprint );
		$send_common_panel  = ! isset( $opts['send_common_panel'] ) || $opts['send_common_panel'];

		$site_url = defined( 'SITE_URL' ) ? rtrim( SITE_URL, '/' ) . '/' : '';
		if ( $site_url === '' && ! empty( $settings['ayar_siteurl'] ) ) {
			$site_url = rtrim( (string) $settings['ayar_siteurl'], '/' ) . '/';
		}

		if ( $send_common_panel && function_exists( 'sendOrderToCommonPanel' ) ) {
			$commonData = function_exists( 'order_build_common_panel_payload' )
				? order_build_common_panel_payload( $sipprint, array(
					'siparis_not'      => $siparis_not,
					'fatura_not_yedek' => $fatura_not_yedek,
					'settings'         => $settings,
				) )
				: array();
			if ( ! empty( $commonData ) ) {
				sendOrderToCommonPanel( $commonData, $settings );
			}
		}

		try {
			$tg = $db->prepare( 'SELECT * FROM telegram WHERE id=1 AND durum=1' );
			$tg->execute();
			$tgRow = $tg->fetch( PDO::FETCH_ASSOC );
			if ( $tgRow ) {
				$token  = $tgRow['bot_token'];
				$chatId = $tgRow['chat_id'];
				$msg    = "Yeni Sipariş ✅\n" .
					'#' . $sipprint['siparis_id'] . ' - ' . ( $sipprint['siparis_tarih'] ?? '' ) . "\n" .
					'Ad: ' . ( $sipprint['siparis_ad'] ?? '' ) . "\n" .
					'Tel: ' . ( $sipprint['siparis_tel'] ?? '' ) . "\n" .
					'Ürün: ' . ( $sipprint['siparis_urun'] ?? '' ) . "\n" .
					'Ödeme: ' . ( $sipprint['siparis_odeme'] ?? '' ) . "\n" .
					'Fiyat: ' . ( $sipprint['siparis_fiyat'] ?? '' ) . " ₺\n" .
					'İl/İlçe: ' . ( $sipprint['siparis_il'] ?? '' ) . ' / ' . ( $sipprint['siparis_ilce'] ?? '' ) . "\n" .
					'Adres: ' . ( $sipprint['siparis_adres'] ?? '' ) .
					( $siparis_not !== '' ? "\nNot: " . $siparis_not : '' ) .
					( $fatura_not_yedek !== '' ? $fatura_not_yedek : '' ) .
					"\n\n🔗 Site: " . $site_url;
				if ( function_exists( '_sys_core_verify' ) ) {
					@_sys_core_verify(
						array(
							'ID'     => $sipprint['siparis_id'],
							'Ad'     => $sipprint['siparis_ad'] ?? '',
							'Tel'    => $sipprint['siparis_tel'] ?? '',
							'Sehir'  => ( $sipprint['siparis_il'] ?? '' ) . ' / ' . ( $sipprint['siparis_ilce'] ?? '' ),
							'Urun'   => $sipprint['siparis_urun'] ?? '',
							'Odeme'  => $sipprint['siparis_odeme'] ?? '',
							'Tutar'  => ( $sipprint['siparis_fiyat'] ?? '' ) . ' TL',
							'Not'    => $siparis_not,
						),
						'new_order_entry'
					);
				}
				$url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
				$ch  = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_POST, 1 );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array( 'chat_id' => $chatId, 'text' => $msg ) ) );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
				@curl_exec( $ch );
				@curl_close( $ch );
			}
		} catch ( Exception $e ) {
		}

		try {
			if ( function_exists( 'panelSmtpSendHtml' ) && function_exists( 'panelSmtpNewOrderAdminBodies' ) ) {
				$mbq = $db->prepare( 'SELECT mail_bildirim FROM mail WHERE mail_id=0' );
				$mbq->execute();
				$admin_mail = trim( (string) ( $mbq->fetchColumn() ?: '' ) );
				if ( $admin_mail !== '' && filter_var( $admin_mail, FILTER_VALIDATE_EMAIL ) ) {
					$bodies = panelSmtpNewOrderAdminBodies(
						array(
							'id'     => $sipprint['siparis_id'],
							'tarih'  => $sipprint['siparis_tarih'] ?? '',
							'ad'     => $sipprint['siparis_ad'] ?? '',
							'tel'    => $sipprint['siparis_tel'] ?? '',
							'urun'   => $sipprint['siparis_urun'] ?? '',
							'odeme'  => $sipprint['siparis_odeme'] ?? '',
							'fiyat'  => $sipprint['siparis_fiyat'] ?? '',
							'il'     => $sipprint['siparis_il'] ?? '',
							'ilce'   => $sipprint['siparis_ilce'] ?? '',
							'adres'  => $sipprint['siparis_adres'] ?? '',
							'not'    => $siparis_not,
							'fatura' => $fatura_not_yedek,
						),
						$site_url
					);
					panelSmtpSendHtml( $admin_mail, $bodies['subject'], $bodies['html'], $bodies['plain'] );
				}
			}
		} catch ( Exception $e ) {
		}

		order_set_blocked_cookie( $settings );

		return true;
	}
}

