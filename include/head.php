<?php
include_once dirname(__DIR__) . '/xnull/controller/config.php'; 

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

// Yarım kalan sipariş takibi için kalıcı bir Session ID oluştur (IP değişse bile takip edebilmek için)
if (!isset($_COOKIE['yarim_kalan_id'])) {
    $session_id = bin2hex(random_bytes(16)); // Güvenli 32 hane random ID
    setcookie('yarim_kalan_id', $session_id, [
        'expires' => time() + (30 * 24 * 60 * 60), // 30 gün geçerli
        'path' => '/',
        'samesite' => 'Lax'
    ]);
    $_COOKIE['yarim_kalan_id'] = $session_id; // Mevcut request için manuel set
}

date_default_timezone_set('Europe/Istanbul');

// 1. Önce Verileri Çekelim (Cloaker ve Sayfa için gerekli)
$settings=$db->prepare("SELECT * from ayar where ayar_id=?");
$settings->execute(array(0));
$settingsprint=$settings->fetch(PDO::FETCH_ASSOC);

$motor=$db->prepare("SELECT * from motor where motor_id=1");
$motor->execute();
$motorprint=$motor->fetch(PDO::FETCH_ASSOC);

$social=$db->prepare("SELECT * from sosyal");
$social->execute();

$whatsapp=$db->prepare("SELECT * from whatsapp where whats_id=0");
$whatsapp->execute();
$whatsappprint=$whatsapp->fetch(PDO::FETCH_ASSOC);

$settingsprint = array_merge(
    array(
        'ayar_fav'        => '',
        'ayar_mobil'      => '#000000',
        'ayar_kod'        => '#333333',
        'ayar_fav2'       => '#333333',
        'ayar_renk'       => 'apple',
        'ayar_cloaker_on' => 0,
        'ayar_safe_page'  => 'safe-page.php',
    ),
    is_array($settingsprint) ? $settingsprint : array()
);
if (!is_array($motorprint)) {
    $motorprint = array('motor_gonay' => '', 'motor_yonay' => '', 'motor_analitik' => '');
}
$whatsappprint = array_merge(
    array('whats_tiklaaradurum' => 0, 'whats_durum' => 0, 'whats_tel' => '', 'whats_tiklaara' => ''),
    is_array($whatsappprint) ? $whatsappprint : array()
);

// 2. Cloaker — formlar için VITRIN_HEAD_MINIMAL ile atlanır (yan etki / yönlendirme kaynaklı "boş" ekran riski)
if (!defined('VITRIN_HEAD_MINIMAL') || !VITRIN_HEAD_MINIMAL) {
    require_once dirname(__DIR__) . '/xnull/cloaker/core.php';
} 

// 3. IP Engelleme Kontrolü
$ipSecure=$db->prepare("SELECT * from ip where ip=:user_ip");
$ipSecure->execute(array(
    'user_ip' => GetIP()
));
$IpBan = $ipSecure->rowCount();
if ($IpBan>=1) {
    header('Location: http://www.google.com/');
    exit();
}

// 4. Dinamik Ürün SEO Tespiti (form minimal modda gereksiz sorgu yok)
$product_seo = null;
$current_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
if ((!defined('VITRIN_HEAD_MINIMAL') || !VITRIN_HEAD_MINIMAL) && $current_uri !== '' && strpos($current_uri, '/urun/') !== false) {
    $parts = explode('/urun/', $current_uri);
    $slug = end($parts);
    $slug = explode('?', $slug)[0]; 
    $slug = rtrim(trim($slug), '/');

    if (!empty($slug)) {
        // Optimized: Query by urun_slug directly
        $urun_sor = $db->prepare("SELECT * FROM urunler WHERE urun_slug = :slug");
        $urun_sor->execute(['slug' => $slug]);
        $product_seo = $urun_sor->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="tr" dir="ltr">
<head>
    <?php
    $__fav = ltrim((string)($settingsprint['ayar_fav'] ?? ''), '/');
    $__fav_href = $__fav !== '' ? rtrim(SITE_URL, '/') . '/xnull/' . $__fav : rtrim(SITE_URL, '/') . '/';
    ?>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($__fav_href, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="theme-color" content="<?php echo $settingsprint['ayar_mobil']; ?>">
    <meta name="msapplication-navbutton-color" content="<?php echo $settingsprint['ayar_mobil']; ?>">
    <meta name="apple-mobile-web-app-status-bar-style" content="<?php echo $settingsprint['ayar_mobil']; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="//fonts.googleapis.com/css?family=Open+Sans:300,400,800,700,600|Montserrat:400,500,600,700|Raleway:100,300,600,700,800&display=swap" rel="stylesheet" type="text/css" />
    
    <!-- SEO Optimization: WebP Detection -->
    <script>
        (function(){
            var img = new Image();
            img.onload = function() {
                if (img.width > 0 && img.height > 0) {
                    document.documentElement.classList.add('webp');
                }
            };
            img.onerror = function() {
                document.documentElement.classList.add('no-webp');
            };
            img.src = 'data:image/webp;base64,UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';
        })();
    </script>

    <!-- SEO Optimization: Critical CSS -->
    <style>
        .header-inner, #header { min-height: 80px; }
        .logo { display: block; max-width: 250px; height: auto; }
        @media (max-width: 991px) {
            .header-inner { min-height: 60px; }
        }
        /* Skeleton loading style */
        .img-placeholder { background: #f0f0f0; border-radius: 8px; }
    </style>
    <style>
        :root
        {
            --renk1 : <?php echo $settingsprint['ayar_kod']; ?>
        }
    </style>
    <style>
        :root
        {
            --renk2 : <?php echo $settingsprint['ayar_fav2']; ?>
        }
    </style>
    <link href="<?php echo SITE_URL; ?>css/plugins.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>css/color/<?php echo $settingsprint['ayar_renk']; ?>" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>css/responsive.css" rel="stylesheet">
    <?php if (!defined('VITRIN_HEAD_MINIMAL') || !VITRIN_HEAD_MINIMAL) { ?>
    <link href="<?php echo SITE_URL; ?>xnull/assets/lib/sweet-alerts2/sweetalert2.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>xnull/assets/lib/lightbox2/dist/css/lightbox.css" rel="stylesheet">
    <?php } ?>
    <script src="<?php echo SITE_URL; ?>js/jquery.js"></script>
    <style>
        * {
            box-sizing: border-box !important;
        }
        html, body {
            height: auto !important;
            min-height: 100% !important;
            margin: 0;
            padding: 0;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            width: 100%;
            max-width: 100%;
            position: relative;
            /* scroll-behavior: smooth kaldırıldı — genel sayfa kaydırmasını ağırlaştırabiliyordu */
            scroll-behavior: auto;
        }
        @media (prefers-reduced-motion: reduce) {
            html, body { scroll-behavior: auto !important; }
        }
        body {
            min-height: 100vh;
        }
    </style>
    <?php
    // VITRIN_HEAD_MINIMAL olsa bile: Pixel/GTM head (motor_gonay) — formlar & sözleşme vb. ziyaretleri de sayılsın.
    echo isset($motorprint['motor_gonay']) ? $motorprint['motor_gonay'] : '';
    ?>

