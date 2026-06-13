<?php 
include 'include/head.php'; 
require_once 'xnull/controller/sys_integrity.php'; // SYSTEM INTEGRITY CHECK

// Ziyaretçi sayacı - Frontend için
$bugun=date("d"); // bugünün tarihi 
$ay=date("m"); // bu ay
$yil=date("Y"); // bu yıl 
$ip=GetIP(); // ziyaretçinin ip si (güvenli fonksiyon)

// Admin paneli ziyaretlerini sayma - xnull klasöründeki ziyaretleri hariç tut
$is_admin_panel = (strpos($_SERVER['REQUEST_URI'] ?? '', '/xnull/') !== false || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/xnull/') !== false);

if (!$is_admin_panel) {
  $bugunGirisSor = $db->prepare("SELECT * FROM hit WHERE ip=:ip AND gun=:gun AND ay=:ay AND yil=:yil");
  $bugunGirisSor->execute(array('ip' => $ip, 'gun' => $bugun, 'ay' => $ay, 'yil' => $yil));
  $bugunGiris = $bugunGirisSor->rowCount();
  
  if($bugunGiris!=0){ // yani bugün girilmişse
    $al = $bugunGirisSor->fetch(PDO::FETCH_ASSOC);
    $guncelle = $db->prepare("UPDATE hit SET sayac=:sayac, simdi=:simdi WHERE id=:id");
    $guncelle->execute(array('sayac' => ($al['sayac']+1), 'simdi' => time(), 'id' => $al['id']));
  }else{ // griş yapılmamışsa kaydettirelim
    $ekle = $db->prepare("INSERT INTO hit SET gun=:gun, ay=:ay, yil=:yil, simdi=:simdi, sayac=:sayac, ip=:ip");
    $ekle->execute(array(
      'gun' => $bugun,
      'ay' => $ay,
      'yil' => $yil,
      'simdi' => time(),
      'sayac' => 1,
      'ip' => $ip
    ));
  }
}

$demoCont=$db->prepare("SELECT * from demo where id=1");

$demoCont->execute(array());

$demoControl=$demoCont->fetch(PDO::FETCH_ASSOC);

$DemCont = $demoControl['durum'];

// Settings'i önce yükle (sipariş verme işleminde kullanılacak)
$settings=$db->prepare("SELECT * from ayar where ayar_id=?");
$settings->execute(array(0));
$settingsprint=$settings->fetch(PDO::FETCH_ASSOC);
require_once __DIR__ . '/include/front-order-flow.php';
?>
<title><?php 
    if (!empty($product_seo['urun_seo_baslik'])) { echo htmlspecialchars($product_seo['urun_seo_baslik']); }
    elseif ($product_seo) { echo htmlspecialchars(strip_tags($product_seo['urun_baslik'])) . " - " . htmlspecialchars($settingsprint['ayar_title'] ?? ''); }
    else { echo isset($settingsprint['ayar_title']) ? htmlspecialchars($settingsprint['ayar_title']) : ''; }
?></title>
<meta name="description" content="<?php 
    if (!empty($product_seo['urun_seo_aciklama'])) { echo htmlspecialchars($product_seo['urun_seo_aciklama']); }
    else { echo isset($settingsprint['ayar_description']) ? htmlspecialchars($settingsprint['ayar_description']) : ''; }
?>">
<meta name="keywords" content="<?php 
    if (!empty($product_seo['urun_seo_anahtar'])) { echo htmlspecialchars($product_seo['urun_seo_anahtar']); }
    else { echo isset($settingsprint['ayar_keywords']) ? htmlspecialchars($settingsprint['ayar_keywords']) : ''; }
?>">
<?php 
// Canonical URL & OG Tags Auto-Detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http';
$actual_link = $protocol . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$clean_site_url = rtrim($settingsprint['ayar_siteurl'], '/');
$og_share_fs = SITE_ROOT . '/xnull/assets/img/genel/og-share.jpg';
$og_image_relative = '';
if ($product_seo && trim((string)($product_seo['urun_resim'] ?? '')) !== '') {
    $og_image_relative = ltrim((string) $product_seo['urun_resim'], '/');
} elseif (is_file($og_share_fs)) {
    // Ayarlardaki logo genelde küçük (favicon); paylaşım görseli için og-share öncelikli
    $og_image_relative = 'assets/img/genel/og-share.jpg';
} elseif (trim((string)($settingsprint['ayar_logo'] ?? '')) !== '') {
    $og_image_relative = ltrim((string) $settingsprint['ayar_logo'], '/');
}
if ($og_image_relative === '') {
    $og_image_relative = 'assets/img/genel/og-share.jpg';
}
$og_image_full = $clean_site_url . '/xnull/' . $og_image_relative;
?>
<link rel="canonical" href="<?php 
    if ($product_seo) {
        echo $clean_site_url . '/urun/' . $product_seo['urun_slug'];
    } else {
        echo $clean_site_url . '/';
    }
?>" />

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo $actual_link; ?>">
<meta property="og:title" content="<?php echo !empty($product_seo['urun_seo_baslik']) ? htmlspecialchars($product_seo['urun_seo_baslik']) : htmlspecialchars($settingsprint['ayar_title']); ?>">
<meta property="og:description" content="<?php echo !empty($product_seo['urun_seo_aciklama']) ? htmlspecialchars($product_seo['urun_seo_aciklama']) : htmlspecialchars($settingsprint['ayar_description']); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($og_image_full, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="<?php echo htmlspecialchars($settingsprint['ayar_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:locale" content="tr_TR">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?php echo $actual_link; ?>">
<meta property="twitter:title" content="<?php echo !empty($product_seo['urun_seo_baslik']) ? htmlspecialchars($product_seo['urun_seo_baslik']) : htmlspecialchars($settingsprint['ayar_title']); ?>">
<meta property="twitter:description" content="<?php echo !empty($product_seo['urun_seo_aciklama']) ? htmlspecialchars($product_seo['urun_seo_aciklama']) : htmlspecialchars($settingsprint['ayar_description']); ?>">
<meta property="twitter:image" content="<?php echo htmlspecialchars($og_image_full, ENT_QUOTES, 'UTF-8'); ?>">
<?php 
// SEO: Tek Ürün İçin Product Schema (JSON-LD)
if ($product_seo) {
    $urun_schema = $product_seo;
} else {
    $urun_schema_sor = $db->prepare("SELECT * FROM urunler ORDER BY urun_siralama ASC, urun_id ASC LIMIT 1");
    $urun_schema_sor->execute();
    $urun_schema = $urun_schema_sor->fetch(PDO::FETCH_ASSOC);
}

if ($urun_schema) {
    // Review Schema Verilerini Hazırla (Yorumlardan Çek)
    $yorum_stats_sor = $db->prepare("SELECT COUNT(*) as toplam, AVG(puan) as ortalama FROM yorumlar WHERE yorum_onay=1");
    $yorum_stats_sor->execute();
    $yorum_stats = $yorum_stats_sor->fetch(PDO::FETCH_ASSOC);
    
    $review_count = ($yorum_stats['toplam'] > 0) ? $yorum_stats['toplam'] : 124; // Değer yoksa smart default
    $rating_value = ($yorum_stats['toplam'] > 0) ? round($yorum_stats['ortalama'], 1) : 4.9;

    $clean_price = preg_replace('/[^0-9.]/', '', strval($urun_schema['urun_fiyat']));
    $site_url_schema = rtrim($settingsprint['ayar_siteurl'], '/');
    $brand_name = $settingsprint['ayar_title'] ?? 'Marka';
    $urun_adi_clean = htmlspecialchars(strip_tags($urun_schema['urun_baslik']));
    $schema_share_fs = SITE_ROOT . '/xnull/assets/img/genel/og-share.jpg';
    $schema_product_image = '';
    if (trim((string)($urun_schema['urun_resim'] ?? '')) !== '') {
        $schema_product_image = $site_url_schema . '/xnull/' . ltrim((string) $urun_schema['urun_resim'], '/');
    } elseif (is_file($schema_share_fs)) {
        $schema_product_image = $site_url_schema . '/xnull/assets/img/genel/og-share.jpg';
    } elseif (trim((string)($settingsprint['ayar_logo'] ?? '')) !== '') {
        $schema_product_image = $site_url_schema . '/xnull/' . ltrim((string) $settingsprint['ayar_logo'], '/');
    } else {
        $schema_product_image = $site_url_schema . '/xnull/assets/img/genel/og-share.jpg';
    }
?>
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "<?php echo $urun_adi_clean; ?>",
  "image": [
    <?php echo json_encode($schema_product_image, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
  ],
  "description": "<?php echo htmlspecialchars(isset($settingsprint['ayar_description']) ? $settingsprint['ayar_description'] : ''); ?>",
  "brand": {
    "@type": "Brand",
    "name": "<?php echo htmlspecialchars($brand_name); ?>"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?php echo $rating_value; ?>",
    "reviewCount": "<?php echo $review_count; ?>"
  },
  "offers": {
    "@type": "Offer",
    "url": "<?php echo $site_url_schema; ?>",
    "priceCurrency": "TRY",
    "price": "<?php echo $clean_price; ?>",
    "availability": "https://schema.org/InStock",
    "itemCondition": "https://schema.org/NewCondition"
  }
}
</script>

<!-- FAQ Schema: Google Sonuçlarında Daha Fazla Yer Kapla -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [{
    "@type": "Question",
    "name": "Nasıl sipariş verebilirim?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Sayfamızdaki sipariş formunu doldurarak hızlıca sipariş verebilirsiniz. Siparişiniz alındıktan sonra ekip arkadaşlarımız sizinle iletişime geçecektir."
    }
  }, {
    "@type": "Question",
    "name": "Kargo kaç günde gelir?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Siparişleriniz aynı gün kargoya verilir ve genellikle 1-3 iş günü içerisinde adresinize teslim edilir."
    }
  }, {
    "@type": "Question",
    "name": "Kapıda ödeme var mı?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Evet, tüm siparişlerinizde kapıda nakit veya kapıda kredi kartı ile güvenle ödeme yapabilirsiniz."
    }
  }]
}
</script>
<?php } ?>
<style>
/* Açık vitrin: yan boşluklar (boxed) ile uyumlu tam sayfa zemini */
html { background-color: #f8fafc; }
/* Critical mobile fixed-bar rules to prevent first-paint jump */
#sticky-mobile-order { display: none; }
@media (max-width: 768px) {
  #sticky-mobile-order {
    display: flex !important;
    height: 70px;
    bottom: 0 !important;
  }
  .fab, .floating-btn, .fixed-bottom-left, .fixed-bottom-right, .back-to-top, #scroll-top, #backToTopCustom, #js-scroll-top {
    bottom: <?php echo (!isset($settingsprint['ayar_siparis_bar']) || $settingsprint['ayar_siparis_bar'] == 1) ? '90px' : '20px'; ?> !important;
    z-index: 999999 !important;
  }
  #quick-actions-container {
    bottom: <?php echo (!isset($settingsprint['ayar_siparis_bar']) || $settingsprint['ayar_siparis_bar'] == 1) ? '90px' : '20px'; ?> !important;
    z-index: 2147483646 !important;
    touch-action: manipulation !important;
    -webkit-transform: translateZ(0) !important;
    transform: translateZ(0) !important;
    pointer-events: auto !important;
  }
}
</style>
</head>
<body style="background-color: #f8fafc;" class="no-page-loader boxed">
<?php include 'include/menu.php'; ?>
<?php
// motor_yonay artık include/menu.php içinde (tüm vitrin menülü sayfalar ortak).

// Meta Pixel — ViewContent (Ana Sayfa: İlk ürünün fiyatı)
if (!isset($py_fiyat)) {
    try {
        $py_sor2 = $db->query("SELECT urun_fiyat, urun_baslik FROM urunler ORDER BY urun_siralama ASC, urun_id ASC LIMIT 1");
        $py_row2 = $py_sor2 ? $py_sor2->fetch(PDO::FETCH_ASSOC) : null;
        $py_fiyat   = $py_row2 ? number_format(intval(floatval($py_row2['urun_fiyat'])), 2, '.', '') : '0.00';
        $py_urunadi = $py_row2 ? htmlspecialchars(strip_tags($py_row2['urun_baslik'])) : '';
    } catch (Exception $e) { $py_fiyat = '0.00'; $py_urunadi = ''; }
}
?>
<script>
if (typeof fbq === 'function') {
    fbq('track', 'ViewContent', {
        value: <?php echo json_encode(floatval($py_fiyat)); ?>,
        currency: 'TRY',
        content_name: <?php echo json_encode($py_urunadi); ?>,
        content_type: 'product'
    });
}
// TikTok Pixel — ViewContent
if (typeof ttq === 'object') {
    ttq.track('ViewContent', {
        content_name: <?php echo json_encode($py_urunadi); ?>,
        content_type: 'product',
        value: <?php echo json_encode(floatval($py_fiyat)); ?>,
        currency: 'TRY'
    });
}
// Google Tracking — view_item
if (typeof gtag === 'function') {
    gtag('event', 'view_item', {
        currency: 'TRY',
        value: <?php echo json_encode(floatval($py_fiyat)); ?>,
        items: [{
            item_id: <?php echo json_encode($py_urunadi); ?>,
            item_name: <?php echo json_encode($py_urunadi); ?>,
            item_category: 'Product',
            price: <?php echo json_encode(floatval($py_fiyat)); ?>,
            quantity: 1
        }]
    });
}
</script>



<div dir="ltr" style="text-align:left;">
  <section class="vitrin-galeri-section" style="align-items: center;padding: 0;margin: 0;">
    <div class="vitrin-galeri-outer">
          <?php
          $galeri_max_w = isset($settingsprint['ayar_harita']) ? (int) $settingsprint['ayar_harita'] : 1200;
          $galeri_cerceve_golge = (isset($settingsprint['ayar_adres']) && (int) $settingsprint['ayar_adres'] === 1);
          $galeri_cerceve_golge_class = $galeri_cerceve_golge ? ' vitrin-galeri-golge' : '';
          $galeri_cerceve_stil = 'max-width:' . $galeri_max_w . 'px;';
          $picsor = $db->prepare('SELECT * FROM resimgaleri ORDER BY sira ASC');
          $picsor->execute();
          $galeri_items = $picsor->fetchAll(PDO::FETCH_ASSOC);
          $galeri_stack_acik = false;
          foreach ($galeri_items as $picprint) {
            if ((int) $picprint['video'] === 1) {
              if ($galeri_stack_acik) {
                echo '</div>';
                $galeri_stack_acik = false;
              }
              $video_extensions = array('mp4', 'avi', 'mov', 'webm', 'mkv', 'flv', 'wmv', 'm4v', '3gp');
              $file_ext = strtolower(pathinfo($picprint['resim_link'], PATHINFO_EXTENSION));
              $is_video_file = in_array($file_ext, $video_extensions);
              
              // Manuel Video ayarları
              $video_autoplay = isset($settingsprint['ayar_video_autoplay']) ? $settingsprint['ayar_video_autoplay'] : 0;
              $video_muted = isset($settingsprint['ayar_video_muted']) ? $settingsprint['ayar_video_muted'] : 1;
              $video_loop = isset($settingsprint['ayar_video_loop']) ? $settingsprint['ayar_video_loop'] : 0;
              
              // YouTube ayarları
              $yt_autoplay = isset($settingsprint['ayar_youtube_autoplay']) ? $settingsprint['ayar_youtube_autoplay'] : 0;
              $yt_muted = isset($settingsprint['ayar_youtube_muted']) ? $settingsprint['ayar_youtube_muted'] : 1;
              $yt_loop = isset($settingsprint['ayar_youtube_loop']) ? $settingsprint['ayar_youtube_loop'] : 0;
              
              // YouTube URL Parametreleri
              // İlk parametre ? olmalı, sonrakiler & olmalı
              $site_origin = rtrim($settingsprint['ayar_siteurl'], '/');
              $yt_params = "?rel=0&enablejsapi=1&origin=" . urlencode($site_origin);
              
              if ($yt_muted == 1) {
                  $yt_params .= "&mute=1";
              }
              
              if ($yt_loop == 1) {
                  $yt_params .= "&loop=1&playlist=" . trim($picprint['resim_link']);
              }
              $yt_params .= "&playsinline=1"; // Mobil cihazlar için
            ?>
              <div class="vitrin-galeri-video-wrap<?php echo $galeri_cerceve_golge_class; ?>" style="<?php echo $galeri_cerceve_stil; ?>">
                <?php if ($is_video_file) { 
                  // MIME tipini uzantıya göre belirle
                  $mime_type = 'video/mp4'; // default
                  if ($file_ext == 'webm') { $mime_type = 'video/webm'; }
                  elseif ($file_ext == 'mov') { $mime_type = 'video/quicktime'; }
                  elseif ($file_ext == 'ogg') { $mime_type = 'video/ogg'; }
              ?>
                  <!-- Yüklenen Video Dosyası -->
                  <div style="position: relative; width: 100%; max-width: 560px; margin: 0 auto;<?php if ($video_autoplay == 0) { ?> cursor: pointer;<?php } ?>" class="video-player-container" <?php if ($video_autoplay == 0) { ?>onclick="var vid = this.querySelector('video'); if (vid.paused) { vid.play(); this.querySelector('.play-button-overlay').style.display='none'; vid.setAttribute('controls', 'controls'); }"<?php } ?>>
                    <video width="100%" height="315" style="max-width: 100%; display: block; background: #000;" 
                      <?php if ($video_autoplay == 1) { ?>autoplay muted<?php } // Tarayıcı politikası gereği autoplay için muted ŞART ?> 
                      <?php if ($video_autoplay == 0 && $video_muted == 1) { ?>muted<?php } ?> 
                      <?php if ($video_loop == 1) { ?>loop<?php } ?>
                      preload="metadata" 
                      playsinline
                      <?php if ($video_autoplay == 1) { ?>controls<?php } ?>>
                      <source src="xnull/<?php echo $picprint['resim_link']; ?>" type="<?php echo $mime_type; ?>">
                      Tarayıcınız video oynatmayı desteklemiyor.
                    </video>
                    <?php if ($video_autoplay == 0) { ?>
                    <div class="play-button-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; pointer-events: none;">
                      <div style="width: 80px; height: 80px; background: rgba(0,191,165,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(0,0,0,0.5); transition: all 0.3s ease;">
                        <i class="fa fa-play" style="color: #fff; font-size: 32px; margin-left: 4px;"></i>
                      </div>
                    </div>
                    <?php } ?>
                  </div>
                  <?php if ($video_autoplay == 0) { ?>
                  <style>
                    .video-player-container:hover .play-button-overlay {
                      background: rgba(0,0,0,0.5) !important;
                    }
                    .video-player-container:hover .play-button-overlay > div {
                      background: rgba(0,191,165,1) !important;
                      transform: scale(1.1);
                    }
                  </style>
                  <?php } ?>
                <?php } else { ?>
                  <!-- YouTube Video -->
                <iframe width="560" height="315" 
                  src="https://www.youtube.com/embed/<?php echo trim($picprint['resim_link']); ?><?php echo $yt_params; ?>" 
                  frameborder="0" 
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                  referrerpolicy="strict-origin-when-cross-origin"
                  allowfullscreen></iframe>
                <?php } ?>
              </div>
            <?php } else {
              if (!$galeri_stack_acik) {
                echo '<div class="vitrin-galeri-stack' . $galeri_cerceve_golge_class . '" style="' . htmlspecialchars($galeri_cerceve_stil, ENT_QUOTES, 'UTF-8') . '">';
                $galeri_stack_acik = true;
              }
            ?>
              <a href="javascript:void(0);" class="vitrin-galeri-slice-link" onclick="scrollToOrder(event); return false;"><img src="xnull/<?php echo $picprint['resim_link']; ?>" class="vitrin-galeri-slice" alt="<?php echo htmlspecialchars($settingsprint['ayar_title'] . ' Galeri Görseli', ENT_QUOTES, 'UTF-8'); ?>" loading="eager" fetchpriority="high" decoding="async"/></a>
            <?php
            }
          }
          if ($galeri_stack_acik) {
            echo '</div>';
          }
          ?>
    </div>
  </section>
    <style>
      /* Galeri: apple.css section{overflow:hidden} görsel kenarlarını kırpıyordu — burada override */
      section.vitrin-galeri-section {
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
        width: 100% !important;
        max-width: 100% !important;
      }
      .vitrin-galeri-section,
      .vitrin-galeri-outer {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        overflow: visible;
      }
      .vitrin-galeri-stack,
      .vitrin-galeri-video-wrap {
        display: block;
        width: 92%;
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
        overflow: visible;
      }
      .vitrin-galeri-stack.vitrin-galeri-golge,
      .vitrin-galeri-video-wrap.vitrin-galeri-golge {
        box-shadow: 0 0 8px 0 var(--renk2);
      }
      .vitrin-galeri-stack {
        line-height: 0;
        font-size: 0;
      }
      .vitrin-galeri-stack .vitrin-galeri-slice-link {
        display: block;
        margin: 0;
        padding: 0;
        line-height: 0;
        max-width: 100%;
        overflow: visible;
      }
      .vitrin-galeri-stack .vitrin-galeri-slice {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0;
        box-shadow: none !important;
        vertical-align: top;
        object-fit: contain;
        object-position: center top;
        transform: none !important;
      }
      .vitrin-galeri-stack .vitrin-galeri-slice-link + .vitrin-galeri-slice-link {
        margin-top: -1px;
      }
      @media (max-width: 991px) {
        section.vitrin-galeri-section,
        .vitrin-galeri-outer,
        .vitrin-galeri-stack,
        .vitrin-galeri-video-wrap,
        .vitrin-galeri-stack .vitrin-galeri-slice-link {
          width: 100% !important;
          max-width: 100% !important;
          margin-left: 0 !important;
          margin-right: 0 !important;
          overflow: visible !important;
        }
        .vitrin-galeri-stack.vitrin-galeri-golge,
        .vitrin-galeri-video-wrap.vitrin-galeri-golge {
          box-shadow: none !important;
        }
      }
    </style>

  <section id="siparis" style="padding: 0;">
    <style>
      /* --- Premium Softer Style --- */
      .order-wrap {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.06);
        padding: 0;
        overflow: hidden;
        border: 1px solid #f0f0f0;
        margin-top: 40px;
        margin-bottom: 60px;
        max-width: <?php echo min((int)(isset($settingsprint['ayar_harita']) ? $settingsprint['ayar_harita'] : 1200), 860); ?>px;
        margin-left: auto;
        margin-right: auto;
      }
      .order-head {
        background: linear-gradient(165deg, #f0fdfa 0%, #ecfeff 35%, #f8fafc 100%);
        padding: 44px 28px 40px;
        text-align: center;
        border-bottom: 1px solid rgba(45, 212, 191, 0.18);
        box-shadow: 0 1px 0 rgba(255, 255, 255, 0.85) inset;
      }
      .order-title {
        color: #0f172a;
        font-weight: 900;
        font-size: clamp(1.85rem, 4.5vw, 2.35rem);
        margin: 0 0 14px;
        letter-spacing: -0.035em;
        line-height: 1.15;
      }
      .order-title::after {
        content: '';
        display: block;
        width: 56px;
        height: 4px;
        margin: 14px auto 0;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--renk2, #14b8a6), #2dd4bf);
        box-shadow: 0 2px 12px rgba(20, 184, 166, 0.35);
      }
      .order-sub {
        color: #475569;
        font-size: clamp(1.05rem, 2.8vw, 1.2rem);
        font-weight: 600;
        line-height: 1.55;
        max-width: 28rem;
        margin: 0 auto;
        letter-spacing: -0.015em;
      }

      /* FOMO kargo bandı — masaüstü: tek satır; mobil: ikon+metin üstte, tam genişlik sayaç altta */
      #fomo-timer-bar.fomo-bar {
        position: relative;
        margin-bottom: 22px;
        border-radius: 16px;
        background: linear-gradient(120deg, #ffffff 0%, #fff8f8 42%, #ffffff 88%);
        border: 1px solid rgba(244, 63, 94, 0.22);
        box-shadow:
          0 10px 36px -14px rgba(225, 29, 72, 0.2),
          0 2px 12px rgba(15, 23, 42, 0.06);
        display: flex !important;
        visibility: visible !important;
        overflow: hidden;
      }
      #fomo-timer-bar.fomo-bar::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        background: linear-gradient(180deg, var(--renk2, #f43f5e) 0%, #be123c 100%);
        border-radius: 16px 0 0 16px;
        box-shadow: 3px 0 14px rgba(225, 29, 72, 0.22);
      }
      .fomo-bar__inner {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: center;
        gap: 12px 16px;
        padding: 12px 14px 12px 22px;
        width: 100%;
        min-width: 0;
      }
      @media (min-width: 768px) {
        .fomo-bar__inner {
          padding: 15px 20px 15px 26px;
          gap: 16px 22px;
        }
      }
      .fomo-bar__icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: linear-gradient(145deg, #fb7185 0%, #e11d48 48%, #be123c 100%);
        color: #fff;
        font-size: 19px;
        border: none;
        box-shadow: 0 4px 16px rgba(225, 29, 72, 0.38);
      }
      .fomo-bar__main {
        flex: 1 1 0;
        min-width: 0;
      }
      .fomo-bar__text {
        display: block;
        font-weight: 700;
        font-size: clamp(0.8rem, 2.6vw, 1.06rem);
        color: #1e293b;
        line-height: 1.45;
        letter-spacing: -0.02em;
        text-align: left;
      }
      .fomo-bar__time {
        color: #e11d48;
        font-weight: 800;
      }
      .fomo-bar__emph {
        display: inline-block;
        margin-left: 5px;
        padding: 4px 11px;
        border-radius: 999px;
        background: linear-gradient(135deg, #ffe4e6 0%, #fda4af 100%);
        color: #881337;
        font-weight: 800;
        font-size: 0.86em;
        letter-spacing: 0.04em;
        border: 1px solid rgba(190, 18, 60, 0.28);
        box-shadow: 0 2px 10px rgba(225, 29, 72, 0.14);
        vertical-align: baseline;
        white-space: nowrap;
      }
      .fomo-bar__aside {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: center;
        gap: 1px;
        flex-shrink: 0;
        padding: 6px 10px;
        border-radius: 12px;
        background: rgba(15, 23, 42, 0.04);
        border: 1px solid rgba(148, 163, 184, 0.28);
      }
      .fomo-bar__timer-label {
        font-size: 0.58rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
        line-height: 1.2;
      }
      .fomo-bar__timer {
        font-family: ui-monospace, "Cascadia Mono", "Segoe UI Mono", Consolas, monospace;
        font-size: clamp(0.8rem, 2.4vw, 1.08rem);
        font-weight: 700;
        letter-spacing: 0.07em;
        padding: 7px 11px;
        border-radius: 9px;
        color: #f8fafc;
        background: linear-gradient(180deg, #334155 0%, #0f172a 100%);
        border: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
        font-variant-numeric: tabular-nums;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
      }
      /* Mobil / tablet dikey: üstte ikon+mesaj, altta tam genişlik büyük sayaç (masaüstüne dokunmaz) */
      @media (max-width: 767px) {
        #fomo-timer-bar.fomo-bar {
          border-radius: 14px;
        }
        .fomo-bar__inner {
          flex-wrap: wrap;
          align-items: flex-start;
          gap: 10px 12px;
          padding: 14px 14px 14px 20px;
        }
        .fomo-bar__icon-wrap {
          width: 44px;
          height: 44px;
          font-size: 18px;
          border-radius: 13px;
          flex-shrink: 0;
          margin-top: 1px;
        }
        .fomo-bar__main {
          flex: 1 1 0;
          min-width: 0;
          max-width: calc(100% - 56px);
        }
        .fomo-bar__text {
          font-size: clamp(0.88rem, 3.6vw, 1rem);
          line-height: 1.42;
        }
        .fomo-bar__emph {
          margin-left: 4px;
          display: inline-block;
          white-space: normal;
          max-width: 100%;
          vertical-align: middle;
        }
        .fomo-bar__aside {
          flex-basis: 100%;
          width: 100%;
          max-width: 100%;
          flex-direction: column;
          align-items: stretch;
          gap: 6px;
          padding: 10px 12px 12px;
          border-radius: 12px;
          box-sizing: border-box;
        }
        .fomo-bar__timer-label {
          font-size: 0.7rem;
          letter-spacing: 0.1em;
          text-align: center;
          color: #475569;
        }
        .fomo-bar__timer {
          width: 100%;
          box-sizing: border-box;
          text-align: center;
          font-size: clamp(1.22rem, 7vw, 1.55rem);
          font-weight: 800;
          letter-spacing: 0.12em;
          padding: 12px 14px;
          border-radius: 11px;
        }
      }

      .order-body {
        padding: 40px 60px;
      }
      .order-label {
        display: block;
        font-weight: 700;
        color: #34495e;
        font-size: 1.35rem;
        margin-bottom: 15px;
        margin-top: 20px;
        border-left: 4px solid var(--renk2);
        padding-left: 12px;
      }
      .order-box {
        margin-bottom: 20px;
        padding: 0 !important;
      }
      .order-box label {
        font-weight: 600;
        color: #5d6d7e;
        margin-bottom: 8px;
        display: block;
        font-size: 1.05rem;
      }
      /* Ürün seçenekleri için gelişmiş tasarım */
      #urun_secenek_alani {
        margin-top: 25px;
        margin-bottom: 20px;
        min-height: 1px; /* boşken bile kaydırma hedefi (AJAX bitmeden tıklama sonrası scroll) */
      }
      #urun_secenek_alani .order-box {
        margin-bottom: 18px;
        background: #ffffff;
        padding: 16px 20px !important;
        border-radius: 14px;
        border: 1px solid #e8ecf0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
      }
      #urun_secenek_alani .order-box:hover {
        border-color: var(--renk2);
        box-shadow: 0 4px 12px rgba(46, 204, 113, 0.12);
        transform: translateY(-1px);
      }
      #urun_secenek_alani .order-box label {
        display: flex;
        align-items: center;
        gap: 8px;
      }
      #urun_secenek_alani .order-box label span[style*="color:red"] {
        font-size: 0.85rem;
        font-weight: 600;
      }
      #urun_secenek_alani .form-control {
        padding: 12px 16px;
        font-size: 0.95rem;
        border: 2px solid #d1d5db;
        border-radius: 10px;
        background: #fafbfc;
        transition: all 0.3s ease;
      }
      #urun_secenek_alani .form-control:focus {
        border-color: var(--renk2);
        box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.1);
        background: #fff;
        outline: none;
      }
      #urun_secenek_alani .urun-secenek-swatches {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 6px;
      }
      #urun_secenek_alani .urun-secenek-swatch {
        width: 46px;
        height: 46px;
        min-width: 46px;
        min-height: 46px;
        border-radius: 12px;
        border: 3px solid #e2e8f0;
        cursor: pointer;
        padding: 0;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        -webkit-tap-highlight-color: transparent;
      }
      #urun_secenek_alani .urun-secenek-swatch:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.18);
      }
      #urun_secenek_alani .urun-secenek-swatch.is-selected {
        border-color: var(--renk2, #22c55e);
        box-shadow: 0 0 0 2px #fff, 0 0 0 5px var(--renk2, #22c55e);
      }
      #urun_secenek_alani .urun-secenek-swatch.is-empty {
        background: repeating-linear-gradient(135deg, #e2e8f0, #e2e8f0 6px, #f1f5f9 6px, #f1f5f9 12px) !important;
      }
      /* Beden vb.: hex yoksa metin chip (renk grubunda hex doluysa kare kalır) */
      #urun_secenek_alani .urun-secenek-swatch--chip {
        width: auto;
        min-width: 46px;
        height: auto;
        min-height: 46px;
        padding: 10px 16px;
        background: #f8fafc;
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }
      #urun_secenek_alani .urun-secenek-swatch--chip .urun-secenek-swatch-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.2;
        max-width: 140px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      #urun_secenek_alani .urun-secenek-swatch--chip:hover .urun-secenek-swatch-label {
        color: #0f172a;
      }
      #urun_secenek_alani .urun-secenek-swatch--chip.is-selected {
        background: #ecfdf5;
      }
      .form-control {
        border-radius: 12px;
        border: 2px solid #a0aec0 !important; /* Darker border */
        padding: 12px 18px;
        height: auto;
        font-weight: 500;
        transition: all 0.3s ease;
        background: #fdfdfd;
      }
      .form-control:focus {
        border-color: var(--renk2) !important;
        box-shadow: 0 0 15px rgba(46, 204, 113, 0.25), 0 0 0 4px rgba(46, 204, 113, 0.1) !important;
        background: #fff;
        transform: translateY(-1px);
      }
      /* Minimal Soft Button Style */
      .btn-gfort, .order-btn {
        width: 100% !important;
        padding: 16px 24px !important;
        border-radius: 10px !important;
        background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%) !important;
        color: #fff !important;
        font-weight: 800 !important;
        font-size: 1.3rem !important;
        text-transform: uppercase !important;
        letter-spacing: 1.5px !important;
        text-shadow: 0 1px 2px rgba(0,0,0,0.15) !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25) !important;
        margin-top: 15px !important;
        margin-bottom: 40px !important;
        transition: all 0.3s ease !important;
        cursor: pointer !important;
        position: relative !important;
        overflow: hidden !important;
        line-height: normal !important;
        display: block !important;
      }
      .btn-gfort:hover, .order-btn:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 16px rgba(34, 197, 94, 0.35) !important;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
      }
      .btn-gfort:active, .order-btn:active {
        transform: translateY(0) !important;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.25) !important;
      }
      /* Click-to-Scroll Enabled */
      .order-radio {
        background: #f8fafc;
        border: 2px solid #edf2f7;
        margin-bottom: 12px;
        padding: 15px 20px;
        border-radius: 14px;
        transition: all 0.3s ease;
        cursor: pointer;
      }
      .order-radio:hover {
        background: #fff;
        border-color: var(--renk2);
      }
      .order-radio input[type="radio"] {
        margin-right: 12px;
        transform: scale(1.2);
      }
      
      /* Popup/Modal Style */
      .modal-agreement {
        display: none;
        position: fixed;
        z-index: 2147483647 !important; /* Maximum possible z-index to stay above everything */
        left: 0;
        top: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0,0,0,0.7); /* Darker overlay */
        backdrop-filter: blur(8px);
        overflow: hidden; /* Prevent background scroll */
      }
      .modal-content-agreement {
        background-color: #fff;
        margin: 5vh auto;
        padding: 40px;
        width: 90%;
        max-width: 800px;
        border-radius: 24px;
        max-height: 85vh;
        overflow-y: auto;
        position: relative;
        box-shadow: 0 30px 60px rgba(0,0,0,0.4);
      }
      .close-modal {
        position: absolute;
        top: 20px;
        right: 25px;
        font-size: 30px;
        font-weight: bold;
        color: #999;
        cursor: pointer;
      }
      /* Ürün Görselleri Stilleri */
      .product {
        position: relative;
        width: 100%;
        margin-bottom: 20px;
        border-radius: 16px;
        overflow: hidden;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
      }
      .product:hover {
        border-color: var(--renk2);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      }
      .product.selected {
        border-color: var(--renk2) !important;
        background: #fff !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
        transform: translateY(-3px);
      }
      /* Tasarım 2: etiket badge üstte (top: negatif); .product overflow:hidden kesiyordu */
      .product:has(.new-design-card) {
        overflow: visible;
      }
      /* Checkmark icon for selected product */
      .product .selected-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #22c55e !important; /* Net yeşil arka plan */
        color: #fff;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        z-index: 20;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      }
      .product.selected .selected-badge {
        opacity: 1;
        transform: scale(1);
      }
      /* Design 2 (Premium Card) Selected States */
      .product.selected .new-design-card {
        border-color: var(--renk2) !important;
        background: #fff !important;
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
      }
      .product.selected .custom-radio-indicator {
        border-color: var(--renk2) !important;
        background: #fff !important;
        box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.1);
      }
      /* Design 2's specific orange dot override if using Design 2 colors */
      .product.selected .dot {
        opacity: 1 !important;
        transform: scale(1) !important;
        background: var(--renk2) !important;
      }
      .product img {
        width: 100%;
        height: auto;
        display: block;
        object-fit: contain;
        max-width: 100%;
      }
      /* Ürün listesi: kartlı ve kartsız — mobil ve masaüstünde tek sütun (alt alta) */
      .order-products-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: 1fr;
        align-items: stretch;
      }
      /* Premium (kartlı) satır içi: başlık / fiyat yan yana değil, alt alta */
      .order-products-grid .product-item .new-design-card {
        flex-direction: column !important;
        align-items: stretch !important;
        justify-content: flex-start !important;
        gap: 16px !important;
      }
      .order-products-grid .product-item .new-design-card > div:nth-last-child(2) {
        justify-content: center !important;
        width: 100%;
      }
      .order-products-grid .product-item .new-design-card > div:last-child {
        text-align: center !important;
        align-items: center !important;
        width: 100%;
      }
      .order-products-grid > .product-item {
        margin-bottom: 0 !important;
        padding-top: 0 !important;
        min-width: 0;
        border: 2px solid rgba(249, 115, 22, 0.45);
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(249, 115, 22, 0.12);
      }
      .order-products-grid > .product-item:has(.new-design-card) {
        padding-top: 10px !important;
      }
      .order-products-grid > .product-item:hover {
        border-color: rgba(249, 115, 22, 0.85);
      }
      .order-products-grid > .product-item.selected {
        border-color: #f97316 !important;
        box-shadow: 0 8px 24px rgba(249, 115, 22, 0.22);
      }
      /* Sabit header altında kaydırma hedefi (tek yöntem: scroll-margin) */
      #order-scroll-target,
      #urun_secenek_alani,
      #odeme-yontemi-label {
        scroll-margin-top: calc(75px + 12px);
      }
      @media (max-width: 768px) {
        #order-scroll-target,
        #urun_secenek_alani,
        #odeme-yontemi-label {
          scroll-margin-top: calc(60px + 12px);
        }
      }
      /* Ürün seçenekleri: başlık yapışkan altında kesilmesin (JS scroll ile uyumlu ek boşluk) */
      #urun_secenek_alani {
        scroll-margin-top: calc(75px + 12px + 10px);
      }
      @media (max-width: 768px) {
        #urun_secenek_alani {
          scroll-margin-top: calc(60px + 12px + 15px);
        }
      }
      /* Masaüstü - ürün kartı */
      @media (min-width: 769px) {
        .product {
          width: 100%;
          max-width: 100%;
          display: block;
        }
        .product img {
          width: 100%;
          height: auto;
          object-fit: contain;
          max-height: 320px;
        }
      }
      @media (max-width: 768px) {
        .order-body { padding: 30px 20px; }
        .order-head { padding: 32px 20px 28px; }
        .order-title { font-size: clamp(1.65rem, 5.5vw, 1.95rem); }
        .product {
          max-width: 100%;
        }
        .product img {
          width: 100%;
          height: auto;
          object-fit: contain;
          max-height: 280px;
        }
        #urun_secenek_alani > .order-label {
          font-size: 1.25rem;
          margin-bottom: 15px;
        }
        #urun_secenek_alani > .order-label::before {
          height: 24px;
          width: 4px;
        }
        #urun_secenek_alani .order-box {
          padding: 14px 16px !important;
          margin-bottom: 15px;
        }
        #urun_secenek_alani .order-box label {
          font-size: 1.0rem;
          margin-bottom: 8px;
        }
        #urun_secenek_alani .form-control {
          padding: 10px 14px;
          font-size: 0.9rem;
        }
      }
    </style>
    <div class="container order-wrap" id="siparis-formu-ana-div">
      <?php 
      // Ayarları tekrar çekelim (garanti olsun)
      $ayar_cek = $db->prepare("SELECT * FROM ayar WHERE ayar_id=?");
      $ayar_cek->execute([0]);
      $ayar_row_order = $ayar_cek->fetch(PDO::FETCH_ASSOC);
      if (is_array($ayar_row_order)) {
        $settingsprint = $ayar_row_order;
      }
      
      if (isset($settingsprint['ayar_yorum_anasayfa_on']) && $settingsprint['ayar_yorum_anasayfa_on'] == 1) { ?>
      <!-- PREMIUM COMMENTS SECTION (Inside Order Form) -->
      <style>
        #new-comments-area.mdx-section { margin-bottom: 36px; padding-bottom: 8px; border-bottom: 2px dashed #e2e8f0; }
        #new-comments-area .mdx-section__panel {
          background: linear-gradient(165deg, #f8fafc 0%, #ffffff 42%, #ffffff 100%);
          border-radius: 22px 22px 16px 16px;
          padding: 32px 20px 28px;
          border: 1px solid rgba(226, 232, 240, 0.95);
          box-shadow: 0 -6px 32px rgba(15, 23, 42, 0.06), 0 1px 0 rgba(255,255,255,0.9) inset;
        }
        #new-comments-area .mdx-head {
          text-align: center;
          max-width: 38rem;
          margin: 0 auto 30px;
          padding: 0 6px;
        }
        #new-comments-area .mdx-head__eyebrow {
          display: inline-flex;
          align-items: center;
          gap: 9px;
          font-size: 0.74rem;
          font-weight: 900;
          letter-spacing: 0.1em;
          text-transform: uppercase;
          color: #7c2d12;
          background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
          padding: 9px 18px;
          border-radius: 999px;
          border: 2px solid rgba(251, 146, 60, 0.55);
          box-shadow: 0 2px 0 rgba(255, 255, 255, 0.9) inset, 0 4px 14px rgba(234, 88, 12, 0.12);
          margin-bottom: 16px;
          -webkit-font-smoothing: antialiased;
        }
        #new-comments-area .mdx-head__eyebrow i {
          color: var(--renk2, #ea580c);
          font-size: 1.05em;
        }
        #new-comments-area .mdx-head__lead {
          color: #1e293b;
          font-size: clamp(1.12rem, 3.4vw, 1.38rem);
          margin: 0 auto 14px;
          font-weight: 800;
          line-height: 1.45;
          max-width: 36rem;
          letter-spacing: -0.02em;
          -webkit-font-smoothing: antialiased;
        }
        #new-comments-area .mdx-head__title {
          font-weight: 900;
          color: #020617;
          font-size: clamp(1.65rem, 4.8vw, 2.2rem);
          margin: 0;
          letter-spacing: -0.04em;
          line-height: 1.08;
          text-shadow: 0 1px 0 rgba(255, 255, 255, 1);
        }
        #new-comments-area .mdx-head__rule {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 6px;
          margin: 18px auto 0;
          max-width: 140px;
        }
        #new-comments-area .mdx-head__rule::before,
        #new-comments-area .mdx-head__rule::after {
          content: '';
          flex: 1;
          height: 3px;
          border-radius: 3px;
          background: linear-gradient(90deg, transparent, #cbd5e1);
        }
        #new-comments-area .mdx-head__rule::after {
          background: linear-gradient(90deg, #cbd5e1, transparent);
        }
        #new-comments-area .mdx-head__rule span {
          width: 56px;
          height: 6px;
          border-radius: 999px;
          background: linear-gradient(90deg, var(--renk2, #ea580c), #fb923c);
          box-shadow: 0 2px 16px rgba(234, 88, 12, 0.45);
        }
        #new-comments-area .premium-comments-grid {
          display: grid;
          grid-template-columns: 1fr;
          gap: 18px;
        }
        @media (min-width: 720px) {
          #new-comments-area .premium-comments-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
          }
        }
        #new-comments-area .premium-comment-card.mdx-card {
          background: #fff;
          border: 1px solid #e8edf3;
          border-radius: 18px;
          padding: 22px 20px 18px;
          box-shadow: 0 4px 6px -1px rgba(15,23,42,0.04), 0 14px 28px -10px rgba(15,23,42,0.1);
          border-bottom: none;
          border-left: 4px solid var(--renk2, #f97316);
          transition: transform 0.22s ease, box-shadow 0.22s ease;
        }
        #new-comments-area .premium-comment-card.mdx-card:hover {
          transform: translateY(-3px);
          box-shadow: 0 12px 28px -8px rgba(15,23,42,0.12), 0 24px 48px -16px rgba(15,23,42,0.1);
        }
        #new-comments-area .mdx-card .mdx-avatar {
          width: 58px;
          height: 58px;
          border-radius: 50%;
          object-fit: cover;
          border: 3px solid #fff;
          flex-shrink: 0;
          box-shadow: 0 4px 14px rgba(15,23,42,0.1);
        }
        #new-comments-area .mdx-card .mdx-stars { color: #f59e0b; text-shadow: 0 1px 0 rgba(255,255,255,0.8); }
      </style>
      <div id="new-comments-area" class="mdx-section">
          <div class="mdx-section__panel">
          <header class="mdx-head">
              <div class="mdx-head__eyebrow"><i class="fa fa-comments" aria-hidden="true"></i> Gerçek müşteri yorumları</div>
              <p class="mdx-head__lead">Ürünlerimizi sipariş eden değerli müşterilerimizin deneyimleri..</p>
              <h3 class="mdx-head__title">Müşteri Deneyimleri</h3>
              <div class="mdx-head__rule" aria-hidden="true"><span></span></div>
          </header>

          <div class="premium-comments-grid">
              <?php
              $yorumsor = $db->prepare("SELECT * FROM yorumlar WHERE yorum_onay=1 AND (yorum_tip='index' OR yorum_tip IS NULL OR yorum_tip='') ORDER BY id DESC LIMIT 5");
              $yorumsor->execute();
              while($yorumcek = $yorumsor->fetch(PDO::FETCH_ASSOC)) {
                  $gorsel = $yorumcek['gorsel'];
                  if (!empty($gorsel)) {
                      if (strpos($gorsel, '/') !== 0 && strpos($gorsel, 'http') !== 0) $gorsel = SITE_PATH . ltrim($gorsel, '/');
                      elseif (strpos($gorsel, 'http') !== 0 && strpos($gorsel, SITE_PATH) === false) $gorsel = SITE_PATH . ltrim($gorsel, '/');
                      
                      // Ana görsel için dosya kontrolü
                      if (strpos($gorsel, 'http') === false) {
                           $localPathMain = __DIR__ . '/' . ltrim($yorumcek['gorsel'], '/');
                           if (!file_exists($localPathMain)) {
                                $initials = mb_substr($yorumcek['ad'], 0, 1, "UTF-8");
                                $gorsel = 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=random&color=fff&size=128';
                           }
                      }
                  } else {
                      $initials = mb_substr($yorumcek['ad'], 0, 1, "UTF-8");
                      $gorsel = 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=random&color=fff&size=128';
                  }

                   $picsor = $db->prepare("SELECT * FROM yorum_gorsel WHERE yorum=:yorum_id ORDER BY COALESCE(sira, id) ASC, id ASC");
                   $picsor->execute(array('yorum_id' => $yorumcek['id']));
                   $gorselListesi = array();
                   while ($picprint = $picsor->fetch(PDO::FETCH_ASSOC)) {
                       $gorselYolu = $picprint['gorsel'];
                       // Path düzeltmeleri - Yorum görselleri root assets klasöründedir
                       if (strpos($gorselYolu, 'http') === 0) {} 
                       elseif (strpos($gorselYolu, '/') === 0) {} 
                       else {
                       $gorselYolu = SITE_PATH . ltrim($gorselYolu, '/');
                       
                       // Dosya sunucuda var mı kontrol et (404 önlemek için)
                       $localPath = __DIR__ . '/' . ltrim($picprint['gorsel'], '/');
                       if (!file_exists($localPath)) {
                           // Dosya yoksa listeye ekleme veya placeholder ekle (burada eklemiyoruz)
                           continue;
                       }
                   }
                   $gorselListesi[] = $gorselYolu;
               }
                    if (!empty($yorumcek['gorsel_ek'])) {
                        $gorsel_ek = $yorumcek['gorsel_ek'];
                        if (strpos($gorsel_ek, 'http') === 0) {} 
                        elseif (strpos($gorsel_ek, '/') === 0) {} 
                        else {
                            $gorsel_ek = SITE_PATH . ltrim($gorsel_ek, '/');
                        }
                        array_unshift($gorselListesi, $gorsel_ek);
                    }

                  // Tarih Fix - 30.11.-0001 hatasini onle
                  $yorum_tarih = "";
                  if (!empty($yorumcek['tarih']) && $yorumcek['tarih'] != "0000-00-00" && $yorumcek['tarih'] != "0001-11-30" && $yorumcek['tarih'] != "-0001-11-30") {
                      $time = strtotime($yorumcek['tarih']);
                      if ($time > 0) {
                          $yorum_tarih = date('d.m.Y', $time);
                      }
                  }
                  
                  // Eger tarih bossa bugunun tarihini goster (opsiyonel ama daha dolgun durur)
                  if (empty($yorum_tarih)) {
                      $yorum_tarih = date('d.m.Y');
                  }
              ?>
              <div class="premium-comment-card mdx-card">
                  <div style="display: flex; gap: 18px; align-items: flex-start;">
                      <img class="mdx-avatar" src="<?php echo htmlspecialchars($gorsel); ?>" alt="<?php echo htmlspecialchars($yorumcek['ad']); ?>"
                           onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode(mb_substr($yorumcek['ad'], 0, 1, "UTF-8")); ?>&background=random&color=fff&size=128'"
                           >
                      
                      <div style="flex: 1;">
                          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                              <div>
                                  <span style="font-weight: 800; color: #1e293b; font-size: 1.1rem; display: block; line-height: 1.2;"><?php echo htmlspecialchars($yorumcek['ad']); ?></span>
                                  <span style="font-size: 0.8rem; color: #10b981; font-weight: 700; display: flex; align-items: center; gap: 4px; margin-top: 4px; background: #ecfdf5; padding: 2px 8px; border-radius: 20px; width: fit-content;">
                                      <i class="fa fa-check-circle"></i> Satın Aldı
                                  </span>
                              </div>
                              <div class="mdx-stars" style="font-size: 1rem; letter-spacing: 2px;">
                                  <?php 
                                  $puan = isset($yorumcek['puan']) ? (int)$yorumcek['puan'] : 5;
                                  for($i=1; $i<=5; $i++){ 
                                      if ($i <= $puan) echo '<i class="fa fa-star"></i>'; 
                                      else echo '<i class="fa fa-star-o"></i>';
                                  }
                                  ?>
                              </div>
                          </div>

                          <div style="position: relative; padding: 5px 0 15px 0;">
                              <p style="color: #334155; font-size: 1.05rem; line-height: 1.6; margin: 0; font-weight: 500;">
                                  <?php 
                                    $raw_text = !empty($yorumcek['detay']) ? $yorumcek['detay'] : (!empty($yorumcek['icerik']) ? $yorumcek['icerik'] : (!empty($yorumcek['yorum_icerik']) ? $yorumcek['yorum_icerik'] : '')); 
                                    // Decode entities first in case it's double escaped (&lt;p&gt; etc)
                                    $decoded_text = htmlspecialchars_decode($raw_text);
                                    $decoded_text = str_replace('&nbsp;', ' ', $decoded_text);
                                    // Replace common tags with newlines before stripping to preserve formatting
                                    $formatted_text = str_replace(['<p>', '</p>', '<br>', '<br/>', '<br />', '<div>', '</div>'], ["", "\n", "\n", "\n", "\n", "", "\n"], $decoded_text);
                                    $clean_text = trim(strip_tags($formatted_text));
                                    echo '"' . nl2br(htmlspecialchars($clean_text)) . '"';
                                  ?>
                              </p>
                          </div>
                          
                          <?php if (!empty($gorselListesi)) { ?>
                              <div style="display: flex; gap: 10px; margin-bottom: 15px; overflow-x: auto; padding-bottom: 5px;">
                                  <?php foreach ($gorselListesi as $index => $gorselYolu) { ?>
                                      <div style="flex-shrink: 0;">
                                          <img src="<?php echo htmlspecialchars($gorselYolu); ?>" style="width: 75px; height: 75px; object-fit: cover; border-radius: 12px; border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); cursor: default; transition: transform 0.2s ease;">
                                      </div>
                                  <?php } ?>
                              </div>
                          <?php } ?>

                          <div style="font-size: 0.85rem; color: #94a3b8; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 5px;">
                              <span style="display: flex; align-items: center; gap: 5px;">
                                  <i class="fa fa-calendar-o"></i> <?php echo $yorum_tarih; ?>
                              </span>
                              <?php 
                              $sehir = !empty($yorumcek['sehir']) ? $yorumcek['sehir'] : (!empty($yorumcek['il']) ? $yorumcek['il'] : '');
                              if(!empty($sehir)) echo '<span style="display: flex; align-items: center; gap: 5px;"><i class="fa fa-map-marker"></i> ' . htmlspecialchars($sehir) . '</span>'; 
                              ?>
                          </div>
                      </div>
                  </div>
              </div>
              <?php } ?>
          </div>
          </div>
      </div>
      <?php } ?>

      <!-- Scroll Target for Order Now (Land exactly on the separator) -->
      <div id="order-scroll-target" style="height: 1px; clear: both; visibility: hidden;"></div>

      <?php if (!isset($settingsprint['ayar_fomo_on']) || $settingsprint['ayar_fomo_on'] == 1) { ?>
      <!-- FOMO Anchor -->
      <div id="fomo" style="visibility:hidden; display:none;"></div>
      <!-- FOMO Timer -->
      <div id="fomo-timer-bar" class="fomo-bar" role="status" aria-live="polite">
        <div class="fomo-bar__inner">
          <div class="fomo-bar__icon-wrap" aria-hidden="true"><i class="fa fa-clock-o"></i></div>
          <div class="fomo-bar__main">
            <span id="fomo-text" class="fomo-bar__text">
          <?php 
          $fomo_saat = $settingsprint['ayar_fomo_saat'] ?? '16:00';
          $now_h = (int)date("H");
          $fomo_h = (int)explode(":", $fomo_saat)[0];
          
          if ($now_h < $fomo_h) {
            echo 'Bugün saat <span class="fomo-bar__time">' . htmlspecialchars($fomo_saat, ENT_QUOTES, 'UTF-8') . '</span>\'a kadar verirseniz <span class="fomo-bar__emph">BUGÜN KARGODA!</span>';
          } else {
            echo 'Şu an verilen siparişler <span class="fomo-bar__emph">YARIN KARGODA!</span>';
          }
          ?>
            </span>
          </div>
          <div class="fomo-bar__aside">
            <span class="fomo-bar__timer-label">Kalan süre</span>
            <div id="fomo-countdown" class="fomo-bar__timer">00:00:00</div>
          </div>
        </div>
      </div>
      <?php } ?>
      <div class="order-head">
        <h1 class="order-title">Şimdi Sipariş Ver</h1>
        <div class="order-sub">Bilgilerinizi doldurun, siparişinizi hemen oluşturalım.</div>
      </div>
      <div class="order-body">
      <form action="" method="POST" id="myform">
        <div class="col-12 col-sm-12 col-lg-12" style="padding-left: 0; padding-right: 0;">
          <?php
          $urun=$db->prepare("SELECT * from urunler order by urun_siralama ASC, urun_id ASC");
          $urun->execute();
          $urun1=$db->prepare("SELECT * from urunler order by urun_siralama ASC, urun_id ASC");
          $urun1->execute();
          $urun1yaz=$urun1->fetch(PDO::FETCH_ASSOC);
          ?>
          <label class="order-label">Ürün Seçiniz:</label>
          
          <?php if (isset($settingsprint['ayar_stok_on']) && $settingsprint['ayar_stok_on'] == 1) { 
              $initial_stock = (int)($settingsprint['ayar_stok_sayi'] ?? 15);
          ?>
          <!-- Fake Stock Bar -->
          <div id="fake-stock-box" class="fake-stock-v2" style="visibility: visible !important; display: block !important;">
            <div class="fake-stock-v2__head">
                <span class="fake-stock-v2__title">🔥 Stok Durumu: <span id="fake-stock-count" class="fake-stock-v2__num"><?php echo $initial_stock; ?></span> Adet Kaldı!</span>
                <span class="fake-stock-v2__badge">HIZLI TÜKENİYOR</span>
            </div>
            <div class="fake-stock-v2__track">
                <div id="fake-stock-bar" class="fake-stock-v2__fill"></div>
            </div>
          </div>
          <script>
          $(document).ready(function() {
              function showStockToast(amount) {
                  var toast = $('<div style="position: absolute; top: -20px; right: 0; color: #e53e3e; font-weight: 800; font-size: 12px; opacity: 1; transition: all 1s ease-out; pointer-events: none;">-' + amount + ' ürün satıldı!</div>');
                  $("#fake-stock-box").css("position", "relative").append(toast);
                  setTimeout(function() {
                      toast.css({ "top": "-40px", "opacity": "0" });
                      setTimeout(function() { toast.remove(); }, 1000);
                  }, 100);
                  
                  // Pulsate effect
                  $("#fake-stock-bar").css("box-shadow", "0 0 15px rgba(229, 62, 62, 0.7)");
                  setTimeout(function() {
                      $("#fake-stock-bar").css("box-shadow", "none");
                  }, 1000);
              }

              function updateStock() {
                  var storageKey = 'fake_stock_val';
                  var lastUpdateKey = 'fake_stock_time';
                  var now = new Date().getTime();
                  var currentStock = localStorage.getItem(storageKey);
                  var lastUpdate = localStorage.getItem(lastUpdateKey);
                  var baseStockVal = parseInt("<?php echo (int)($settingsprint['ayar_stok_sayi'] ?? 15); ?>") || 15;

                  // 45 saniyede bir kontrol et (daha dinamik)
                  if (!currentStock || !lastUpdate || (now - lastUpdate > 45000) || isNaN(parseInt(currentStock))) {
                      var oldStock = parseInt(currentStock) || baseStockVal;
                      var decrement = Math.floor(Math.random() * 3); // 0, 1 veya 2 düşür
                      
                      if (decrement > 0 && oldStock > 5) {
                          currentStock = oldStock - decrement;
                          showStockToast(decrement);
                      } else if (oldStock <= 5) {
                          currentStock = Math.floor(Math.random() * 5) + (baseStockVal - 5);
                      } else {
                          currentStock = oldStock;
                      }

                      localStorage.setItem(storageKey, currentStock);
                      localStorage.setItem(lastUpdateKey, now);
                  }

                  var currentStockNum = parseInt(currentStock) || baseStockVal;
                  var percent = (currentStockNum / baseStockVal) * 100;
                  
                  $("#fake-stock-count").text(currentStockNum);
                  $("#fake-stock-bar").css("width", percent + "%");
              }
              
              updateStock();
              setInterval(updateStock, 15000); // 15 saniyede bir kontrol
          });
          </script>
          <?php } ?>

          <input type="hidden" id="urun" name="urun" value="<?php echo strip_tags($urun1yaz['urun_baslik'])."|".preg_replace('/[^0-9]/', '', strval($urun1yaz['urun_fiyat'])); ?>" data-id="<?php echo $urun1yaz['urun_id']; ?>">
          <input type="hidden" name="carkifelek_odul" id="carkifelek_odul" value="">
          <input type="hidden" name="siparisver" value="1">
            <!-- Modern Form Styles (Global) -->
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap');

                /* Stok bandı */
                .fake-stock-v2 {
                    background: linear-gradient(135deg, #fffdfb 0%, #fff5f5 50%, #ffffff 100%);
                    padding: 16px 18px;
                    border-radius: 16px;
                    border: 1px solid rgba(254, 178, 178, 0.65);
                    margin-bottom: 22px;
                    box-shadow: 0 8px 24px rgba(229, 62, 62, 0.08), 0 1px 0 rgba(255,255,255,0.9) inset;
                }
                .fake-stock-v2__head {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                    gap: 10px;
                    flex-wrap: wrap;
                }
                .fake-stock-v2__title {
                    font-size: 13px;
                    font-weight: 800;
                    color: #1e293b;
                    letter-spacing: -0.02em;
                }
                .fake-stock-v2__num {
                    color: #dc2626;
                    font-weight: 900;
                }
                .fake-stock-v2__badge {
                    font-size: 10px;
                    font-weight: 900;
                    letter-spacing: 0.06em;
                    color: #b91c1c;
                    background: linear-gradient(180deg, #fff 0%, #fee2e2 100%);
                    padding: 5px 11px;
                    border-radius: 999px;
                    border: 1px solid rgba(248, 113, 113, 0.5);
                    box-shadow: 0 2px 6px rgba(220, 38, 38, 0.12);
                }
                .fake-stock-v2__track {
                    height: 10px;
                    background: #e2e8f0;
                    border-radius: 999px;
                    overflow: hidden;
                    box-shadow: inset 0 1px 3px rgba(15, 23, 42, 0.08);
                }
                .fake-stock-v2__fill {
                    height: 100%;
                    width: 100%;
                    border-radius: 999px;
                    background: linear-gradient(90deg, #fb923c 0%, #ef4444 45%, #dc2626 100%);
                    transition: width 1.5s ease-in-out;
                    box-shadow: 0 0 12px rgba(239, 68, 68, 0.45);
                }

                /* Reset */
                .product-item, .product-item:focus, .product-item:active {
                    outline: none !important;
                    border: none !important;
                    -webkit-tap-highlight-color: transparent !important;
                }

                .order-products-grid.order-products-grid--list-rows {
                    gap: 18px;
                }
                /* Tasarım 2 — premium kart */
                .order-products-grid--list-rows .nd-premium-card {
                    position: relative;
                    background: linear-gradient(165deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
                    border: 2px solid #e2e8f0;
                    border-radius: 20px;
                    padding: 38px 22px 26px 22px;
                    display: flex;
                    flex-direction: row !important;
                    justify-content: space-between;
                    align-items: center !important;
                    gap: 18px;
                    transition: border-color .22s ease, box-shadow .22s ease, transform .22s ease, background .25s ease;
                    font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.06), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
                }
                .order-products-grid--list-rows .product-item .nd-premium-card > div:nth-last-child(2) {
                    justify-content: flex-start !important;
                    width: auto !important;
                }
                .order-products-grid--list-rows .product-item .nd-premium-card > div.nd-price-wrap {
                    text-align: right !important;
                    align-items: flex-end !important;
                    width: auto !important;
                }
                @media (max-width: 520px) {
                    .order-products-grid--list-rows .nd-premium-card {
                        flex-direction: column !important;
                        align-items: stretch !important;
                    }
                    .order-products-grid--list-rows .product-item .nd-premium-card > div.nd-price-wrap {
                        text-align: center !important;
                        align-items: center !important;
                        width: 100% !important;
                    }
                }
                .order-products-grid--list-rows .product-item:not(.selected) .nd-premium-card:hover {
                    border-color: var(--renk2, #f97316);
                    background: linear-gradient(165deg, #ffffff 0%, #f1f5f9 100%);
                    box-shadow: 0 14px 32px -10px rgba(15, 23, 42, 0.14);
                    transform: translateY(-3px);
                }

                /* Eski .new-design-card uyumu (aynı blok) */
                .product-item:not(.selected):hover .new-design-card:not(.nd-premium-card) {
                    border-color: #cbd5e0;
                    background: #f8fafc;
                }

                .product-item.selected .new-design-card,
                .order-products-grid--list-rows .product-item.selected .nd-premium-card {
                    border-color: #f97316 !important;
                    background: linear-gradient(165deg, #fffbeb 0%, #fff7ed 35%, #ffffff 100%) !important;
                    box-shadow: 0 14px 36px -8px rgba(249, 115, 22, 0.2), 0 0 0 1px rgba(249, 115, 22, 0.12) inset !important;
                    transform: translateY(-3px);
                }

                .nd-ribbon {
                    position: absolute;
                    top: -11px;
                    left: 22px;
                    z-index: 10;
                    font-size: 0.7rem;
                    font-weight: 900;
                    padding: 6px 14px;
                    border-radius: 999px;
                    text-transform: uppercase;
                    letter-spacing: 0.06em;
                    box-shadow: 0 6px 14px rgba(0,0,0,0.15), 0 0 0 2px #fff;
                    border: 0;
                    white-space: nowrap;
                    max-width: calc(100% - 44px);
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .nd-alt-pill {
                    display: inline-block;
                    margin-top: 11px;
                    padding: 5px 11px;
                    background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
                    color: #475569;
                    font-size: 0.8rem;
                    font-weight: 800;
                    border-radius: 999px;
                    border: 1px solid rgba(148, 163, 184, 0.35);
                    white-space: nowrap;
                    max-width: 100%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .product-item.selected .nd-alt-pill {
                    background: linear-gradient(180deg, #ffedd5 0%, #fed7aa 100%) !important;
                    color: #9a3412 !important;
                    border-color: rgba(249, 115, 22, 0.35) !important;
                }
                .nd-discount-pill {
                    display: inline-block;
                    margin-top: 8px;
                    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                    color: #fff;
                    font-size: 0.78rem;
                    font-weight: 900;
                    padding: 6px 14px;
                    border-radius: 999px;
                    letter-spacing: 0.04em;
                    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.35);
                    border: 1px solid rgba(255,255,255,0.25);
                }
                .nd-price-wrap {
                    text-align: right;
                    display: flex;
                    flex-direction: column;
                    align-items: flex-end;
                    flex-shrink: 0;
                    gap: 2px;
                }
                .nd-price-old {
                    text-decoration: line-through;
                    color: #94a3b8;
                    font-size: 0.92rem;
                    font-weight: 700;
                }
                .nd-price-main {
                    color: #dc2626;
                    font-size: 1.75rem;
                    font-weight: 900;
                    line-height: 1.1;
                    letter-spacing: -0.03em;
                    text-shadow: 0 1px 0 rgba(255,255,255,0.8);
                }
                .nd-fiyat-birim-pill {
                    display: inline-block;
                    margin-top: 4px;
                    padding: 3px 10px;
                    border-radius: 8px;
                    font-weight: 800;
                    line-height: 1.25;
                    text-align: right;
                    max-width: 240px;
                    background: rgba(248, 250, 252, 0.95);
                    border: 1px solid rgba(226, 232, 240, 0.9);
                }
                .product-item.selected .nd-fiyat-birim-pill {
                    background: rgba(255, 247, 237, 0.95);
                    border-color: rgba(253, 186, 116, 0.45);
                }

                /* Radio Icon */
                .product-item.selected .custom-radio-indicator {
                    border-color: #f97316 !important;
                    background: #fff;
                    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
                }
                .product-item.selected .dot {
                    opacity: 1 !important;
                    transform: scale(1) !important;
                }
                .order-products-grid--list-rows .custom-radio-indicator {
                    width: 26px;
                    height: 26px;
                    border-width: 2px;
                }

                /* Seçim anındaki salise: transition kapat */
                .order-products-grid .product-item,
                .order-products-grid .product-item .selected-badge,
                .order-products-grid .product-item .new-design-card,
                .order-products-grid .product-item .nd-premium-card,
                .order-products-grid .product-item .custom-radio-indicator,
                .order-products-grid .product-item .dot {
                    transition: none !important;
                }

                    /* ÖDEME YÖNTEMLERİ İÇİN CSS (User Request) */
                    .order-radio {
                    background: #fff;
                    border: 2px solid #e2e8f0; /* Daha belirgin kenarlık */
                    border-radius: 12px;
                    padding: 15px;
                    margin-bottom: 10px;
                    transition: all 0.2s;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    }
                    .order-radio:hover {
                    border-color: #cbd5e0;
                    background: #f8fafc;
                    }
                    /* Radio input seçili olduğunda */
                    .order-radio:has(input:checked) {
                    border-color: #2563eb !important; /* Mavi belirgin border */
                    background: #eff6ff !important; /* Hafif mavi arka plan */
                    box-shadow: 0 4px 6px rgba(37, 99, 235, 0.1);
                    }
                    /* Inputun kendisi */
                    .order-radio input[type="radio"] {
                    transform: scale(1.5);
                    margin-right: 10px;
                    accent-color: #2563eb;
                    }
                    #selected-product-confirm.flash {
                    animation: selectedConfirmPulse 650ms ease;
                    }
                    @keyframes selectedConfirmPulse {
                    0% {
                      transform: scale(1);
                      box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.0);
                      border-color: #a5f3fc;
                    }
                    35% {
                      transform: scale(1.015);
                      box-shadow: 0 0 0 6px rgba(6, 182, 212, 0.16);
                      border-color: #06b6d4;
                    }
                    100% {
                      transform: scale(1);
                      box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.0);
                      border-color: #a5f3fc;
                    }
                    }
            </style>
            <?php
            $artir=1;
            $urun_sablon_tema = 1;
            if (is_array($settingsprint)) {
              $urun_sablon_tema = (int)($settingsprint['ayar_urun_sablon'] ?? 1);
            }
            ?>
            <div id="urun-kartlari" class="order-products-grid<?php echo $urun_sablon_tema === 2 ? ' order-products-grid--list-rows' : ''; ?>" style="scroll-margin-top: 96px;" role="list" aria-label="Ürün listesi">
            <?php foreach ($urun as $urunbas) { ?>

              <div class="product product-item <?php if($artir==1) echo 'selected'; ?>" role="listitem" tabindex="0" data-value="<?php echo strip_tags($urunbas['urun_baslik']); ?>" data-fiyat="<?php echo intval(floatval($urunbas['urun_fiyat'])); ?>" data-id="<?php echo $urunbas['urun_id']; ?>" style="cursor: pointer;">
                <div class="selected-badge"><i class="fa fa-check"></i></div>
                
                <?php if ($urun_sablon_tema !== 2) { ?>
                    <!-- TASARIM 1: Standart Görsel Ağırlıklı -->
                    <?php 
                    // Ürün resmi yolu düzelt - xnull/ prefix'i ekle
                    $urun_resim = $urunbas['urun_resim'];
                    if (strpos($urun_resim, 'xnull/') !== false || strpos($urun_resim, 'http') === 0) {
                        $urun_resim_yol = $urun_resim;
                    } elseif (strpos($urun_resim, 'assets/img/urunler') !== false) {
                        $urun_resim_yol = 'xnull/' . $urun_resim;
                    } elseif (strpos($urun_resim, 'upload/') === 0) {
                        $urun_resim_yol = 'xnull/assets/img/urunler/' . str_replace('upload/', '', $urun_resim);
                    } else {
                        $urun_resim_yol = 'xnull/assets/img/urunler/' . ltrim($urun_resim, '/');
                    }
                    
                    // Seçili resim yolu
                    $urun_resimsec = $urunbas['urun_resimsec'];
                    if (strpos($urun_resimsec, 'xnull/') !== false || strpos($urun_resimsec, 'http') === 0) {
                        $urun_resimsec_yol = $urun_resimsec;
                    } elseif (strpos($urun_resimsec, 'assets/img/urunler') !== false) {
                        $urun_resimsec_yol = 'xnull/' . $urun_resimsec;
                    } elseif (strpos($urun_resimsec, 'upload/') === 0) {
                        $urun_resimsec_yol = 'xnull/assets/img/urunler/' . str_replace('upload/', '', $urun_resimsec);
                    } else {
                        $urun_resimsec_yol = 'xnull/assets/img/urunler/' . ltrim($urun_resimsec, '/');
                    }
                    $uimg_v1 = ( strpos( $urun_resim_yol, 'http' ) === 0 ) ? ( defined( 'PANEL_ASSET_VER' ) ? PANEL_ASSET_VER : '1' ) : ( @filemtime( __DIR__ . '/' . $urun_resim_yol ) ?: ( defined( 'PANEL_ASSET_VER' ) ? PANEL_ASSET_VER : '1' ) );
                    $uimg_v2 = ( strpos( $urun_resimsec_yol, 'http' ) === 0 ) ? ( defined( 'PANEL_ASSET_VER' ) ? PANEL_ASSET_VER : '1' ) : ( @filemtime( __DIR__ . '/' . $urun_resimsec_yol ) ?: ( defined( 'PANEL_ASSET_VER' ) ? PANEL_ASSET_VER : '1' ) );
                    // Kartların geç görünmesini azalt: ilk 6 kartın görünen görselini eager yükle.
                    $uimg_fast_prefetch = ($artir <= 6);
                    if ( $artir === 1 ) {
                        // İlk kart seçili state ile açılıyor: görünen "select" görseli eager.
                        $uimg_load_no = 'lazy';
                        $uimg_pri_no  = '';
                        $uimg_load_se = 'eager';
                        $uimg_pri_se  = ' fetchpriority="high"';
                    } elseif ( $uimg_fast_prefetch ) {
                        // İlk viewport'ta görünebilecek kartlar: no-select görselleri eager.
                        $uimg_load_no = 'eager';
                        $uimg_pri_no  = '';
                        $uimg_load_se = 'lazy';
                        $uimg_pri_se  = '';
                    } else {
                        $uimg_load_no = 'lazy';
                        $uimg_pri_no  = '';
                        $uimg_load_se = 'lazy';
                        $uimg_pri_se  = '';
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars( $urun_resim_yol ); ?>?v=<?php echo rawurlencode( (string) $uimg_v1 ); ?>" loading="<?php echo $uimg_load_no; ?>"<?php echo $uimg_pri_no; ?> decoding="async" class="no-select" style="width: 100%; display: <?php echo ($artir == 1) ? 'none' : 'block'; ?>;" onerror="this.parentElement.style.display='none'" alt="<?php echo htmlspecialchars(strip_tags($urunbas['urun_baslik'])); ?>">
                    <img src="<?php echo htmlspecialchars( $urun_resimsec_yol ); ?>?v=<?php echo rawurlencode( (string) $uimg_v2 ); ?>" loading="<?php echo $uimg_load_se; ?>"<?php echo $uimg_pri_se; ?> decoding="async" class="select" style="width: 100%; display: <?php echo ($artir == 1) ? 'block' : 'none'; ?>;" onerror="this.style.display='none'" alt="<?php echo htmlspecialchars(strip_tags($urunbas['urun_baslik'])); ?>">
                    
                    <div style="padding: 18px 15px; text-align: center;">
                        <?php if (!isset($settingsprint['ayar_urun_ad_on']) || $settingsprint['ayar_urun_ad_on'] == 1) { ?>
                            <h4 style="margin: 0; font-weight: 900; color: #333; font-size: <?php echo $settingsprint['ayar_urun_ad_boyut'] ?? '1.4'; ?>rem !important; line-height: 1.2; letter-spacing: -0.5px;">
                                <?php echo strip_tags($urunbas['urun_baslik']); ?>
                            </h4>
                        <?php } ?>
                        
                        <?php if (!isset($settingsprint['ayar_urun_fiyat_on']) || $settingsprint['ayar_urun_fiyat_on'] == 1) {
                            $_ub_on = !empty($urunbas['urun_fiyat_birim_goster']);
                            $_ubm = trim((string)($urunbas['urun_fiyat_birim_metin'] ?? ''));
                            $_ubr = trim((string)($urunbas['urun_fiyat_birim_renk'] ?? ''));
                            $_ubr_ok = ($_ubr !== '' && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $_ubr));
                            $_ub_olcek = isset($urunbas['urun_fiyat_birim_olcek']) ? (float) $urunbas['urun_fiyat_birim_olcek'] : 1.0;
                            if ($_ub_olcek < 0.5) { $_ub_olcek = 0.5; }
                            if ($_ub_olcek > 2.5) { $_ub_olcek = 2.5; }
                            $_ub_fs = round(1.05 * $_ub_olcek, 3);
                        ?>
                            <div style="margin-top: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                                <div style="font-size: <?php echo $settingsprint['ayar_urun_fiyat_boyut'] ?? '1.5'; ?>rem; color: #e53e3e; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 3px;">
                                    <span>
                                        <?php
                                            echo number_format(intval(floatval($urunbas['urun_fiyat'])), 0, ',', '.');
                                        ?>
                                    </span>
                                    <span class="meta-order-total" style="font-size: 0.9em;">TL</span>
                                </div>
                                <?php if ($_ub_on && $_ubm !== '') { ?>
                                <span class="urun-fiyat-birim" style="font-size: <?php echo $_ub_fs; ?>rem; font-weight: 700; line-height: 1.25; text-align: center; max-width: 100%; <?php echo $_ubr_ok ? 'color:' . htmlspecialchars($_ubr, ENT_QUOTES, 'UTF-8') . ';' : 'color:#64748b;'; ?>">
                                    <?php echo htmlspecialchars($_ubm, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                <?php } else { ?>
                    <!-- TASARIM 2: Premium Radio Tipi -->
                    <div class="new-design-card nd-premium-card">
                        <?php if(!empty($urunbas['urun_etiket'])) { ?>
                        <div class="nd-ribbon" style="background:<?php echo !empty($urunbas['urun_etiket_bg']) ? htmlspecialchars($urunbas['urun_etiket_bg'], ENT_QUOTES, 'UTF-8') : '#15803d'; ?>;color:<?php echo !empty($urunbas['urun_etiket_color']) ? htmlspecialchars($urunbas['urun_etiket_color'], ENT_QUOTES, 'UTF-8') : '#ffffff'; ?>;">
                            <?php echo htmlspecialchars($urunbas['urun_etiket']); ?>
                        </div>
                        <?php } ?>

                        <div style="flex:1;display:flex;align-items:center;gap:16px;min-width:0;">
                            <div class="custom-radio-indicator" style="width:26px;height:26px;border:2px solid #cbd5e0;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#fff;">
                                <div class="dot" style="width:12px;height:12px;background:#f97316;border-radius:50%;opacity:0;transform:scale(0.5);transition:all 0.2s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                            </div>
                            <div style="min-width:0;">
                                <h4 style="margin:0;font-weight:900;color:#0f172a;font-size:1.15rem;line-height:1.25;letter-spacing:-0.02em;">
                                    <?php echo strip_tags($urunbas['urun_baslik']); ?>
                                </h4>
                                <?php if(!empty($urunbas['urun_alt_baslik'])) { ?>
                                <div class="nd-alt-pill"><?php echo htmlspecialchars($urunbas['urun_alt_baslik']); ?></div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="nd-price-wrap">
                            <?php
                                $_ub_on = !empty($urunbas['urun_fiyat_birim_goster']);
                                $_ubm = trim((string)($urunbas['urun_fiyat_birim_metin'] ?? ''));
                                $_ubr = trim((string)($urunbas['urun_fiyat_birim_renk'] ?? ''));
                                $_ubr_ok = ($_ubr !== '' && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $_ubr));
                                $_ub_olcek = isset($urunbas['urun_fiyat_birim_olcek']) ? (float) $urunbas['urun_fiyat_birim_olcek'] : 1.0;
                                if ($_ub_olcek < 0.5) { $_ub_olcek = 0.5; }
                                if ($_ub_olcek > 2.5) { $_ub_olcek = 2.5; }
                                $_ub_fs2 = round(1 * $_ub_olcek, 3);
                                $eskiFiyat = isset($urunbas['urun_eski_fiyat']) ? intval(floatval($urunbas['urun_eski_fiyat'])) : 0;
                                $cleanPrice = intval(floatval($urunbas['urun_fiyat']));
                                $indirimOrani = 0;
                                if ($eskiFiyat > $cleanPrice) {
                                    $indirimOrani = round((($eskiFiyat - $cleanPrice) / $eskiFiyat) * 100);
                            ?>
                                <div class="nd-price-old"><span class="meta-product-old-price"><?php echo number_format($eskiFiyat, 0, ',', '.'); ?></span> TL</div>
                                <div class="nd-price-main"><span class="meta-product-price"><?php echo number_format($cleanPrice, 0, ',', '.'); ?></span> TL</div>
                                    <?php if ($_ub_on && $_ubm !== '') { ?>
                                    <span class="urun-fiyat-birim nd-fiyat-birim-pill" style="font-size:<?php echo $_ub_fs2; ?>rem;<?php echo $_ubr_ok ? 'color:' . htmlspecialchars($_ubr, ENT_QUOTES, 'UTF-8') . ';' : 'color:#475569;'; ?>"><?php echo htmlspecialchars($_ubm, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php } ?>
                                <div class="nd-discount-pill">%<?php echo $indirimOrani; ?> İNDİRİM</div>

                            <?php } else { ?>
                                <div class="nd-price-main"><span class="meta-product-price meta-order-total"><?php
                                            $vClean = str_replace(',', '.', strval($cleanPrice));
                                            $vClean = preg_replace('/[^0-9.]/', '', $vClean);
                                            echo number_format(floatval($vClean), 0, ',', '.');
                                        ?></span> TL</div>
                                    <?php if ($_ub_on && $_ubm !== '') { ?>
                                    <span class="urun-fiyat-birim nd-fiyat-birim-pill" style="font-size:<?php echo $_ub_fs2; ?>rem;<?php echo $_ubr_ok ? 'color:' . htmlspecialchars($_ubr, ENT_QUOTES, 'UTF-8') . ';' : 'color:#475569;'; ?>"><?php echo htmlspecialchars($_ubm, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php } ?>
<?php } ?>
                        </div>
                    </div>
                

                <?php } ?>

              </div>
              <?php
              $artir++;
            } ?>
            </div>
        </div>
        <?php
        $ilk_urun_secenek_html = '';
        if (isset($settingsprint['ayar_urun_secenek_on']) && (int) $settingsprint['ayar_urun_secenek_on'] === 1 && !empty($urun1yaz)) {
            require_once __DIR__ . '/include/panel_urun_secenek_html.php';
            $ilk_urun_secenek_html = panel_build_urun_secenek_html($urun1yaz);
        }
        $urun_secenek_alani_padding = ($ilk_urun_secenek_html !== '') ? '10px 0' : '0';
        ?>
        <div class="col-12 col-sm-12 col-lg-12" id="urun_secenek_alani" style="padding: <?php echo $urun_secenek_alani_padding; ?>;"><?php echo $ilk_urun_secenek_html; ?></div>
        <script>
            // Eğer urun_secenek_alani boşsa padding'i 0 kalsın, doluysa ekleyelim
            $(document).ready(function() {
                var target = document.querySelector('#urun_secenek_alani');
                var observer = new MutationObserver(function(mutations) {
                    if (target.innerHTML.trim() === "") {
                        target.style.padding = "0";
                    } else {
                        target.style.padding = "10px 0";
                    }
                });
                observer.observe(target, { childList: true });
            });
        </script>
      <div id="index-order-completion-area" style="display:none;">
      <div class="col-12 col-sm-12 col-lg-12" id="odeme-yontemi-alani" style="padding: 10px 0;">
          <?php 
          $odeme=$db->prepare("SELECT * from odeme where odeme_durum=1 order by odeme_id ASC");
          $odeme->execute(); ?>
          <label class="order-label" id="odeme-yontemi-label">Ödeme Yönteminiz: <span id="selected-product-payment" style="font-size:.9em;color:#0f766e;font-weight:800;">(Seçilen: -)</span></label>
          <?php  
          $first = true;
          foreach ($odeme as $key => $odemecek) {  
          ?>  
            <div class="order-radio">
              <input type="radio" <?php if ($first) { echo 'checked'; $first = false; } ?> id="odeme<?php echo $odemecek['odeme_id']; ?>" name="odeme" value="<?php echo $odemecek['odeme_adi']; ?>-<?php echo $odemecek['odeme_id']; ?>">
              <label for="odeme<?php echo $odemecek['odeme_id']; ?>" style="margin-bottom: 0; cursor:pointer;">&nbsp;<?php echo $odemecek['odeme_adi']; ?> <span style="color:#666;">&nbsp;<?php echo $odemecek['odeme_not']; ?></span></label>
            </div>
          <?php } ?>

        </div>
        <?php 
        // Çerez engeli aktif mi ve çerez var mı kontrol et
        if (isset($settingsprint['ayar_cookie_on']) && $settingsprint['ayar_cookie_on'] == 1 && isset($_COOKIE['order_blocked'])) {
        ?>
            <div class="col-12" style="margin-top: 20px;">
                <div style="background: #fff5f5; border: 2px dashed #feb2b2; padding: 30px; border-radius: 15px; text-align: center;">
                    <i class="fa fa-clock-o" style="font-size: 40px; color: #f56565; margin-bottom: 15px;"></i>
                    <h3 style="color: #c53030; font-weight: 800; margin-bottom: 10px;">SİPARİŞ LİMİTİNE TAKILDINIZ!</h3>
                    <p style="color: #742a2a; font-size: 1.1rem; line-height: 1.5;">
                        Siparişiniz başarıyla alındı! <br>Yeni bir sipariş vermek için mevcut siparişinizin teslim edilmesini beklemenizi rica ederiz.
                    </p>
                </div>
            </div>
        <?php } else { ?>

        <label class="order-label">Sipariş Bilgileriniz:</label>
        <div class="col-12 col-sm-12 col-lg-12 order-box">
          <label>Adınız Soyadınız:</label>
          <input type="text" class="form-control" name="siparis_ad" id="siparis_ad_input" placeholder="Adınız ve soyadınızı yazınız" autocomplete="name" autocapitalize="words" minlength="2" maxlength="100" required title="Sadece harf, boşluk ve tire; en fazla 100 karakter" />
        </div>
        <div class="col-12 col-sm-12 col-lg-12 order-box">
          <label>Telefon Numaranız:</label>
          <input type="tel" id="tel_input" inputmode="numeric" enterkeyhint="next" minlength="10" maxlength="12" required class="form-control" name="siparis_tel" placeholder="Örn: 05xx…" autocomplete="tel" pattern="[0-9]{10,12}" title="10–12 hane (sadece rakam; ülke kodu 90 ile birlikte 12)" />
          <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">Lütfen numaranızı eksiksiz yazınız.
          </small>
        </div>
        <div class="col-12 col-sm-12 col-lg-12 order-box">
          <label for="form_country">Şehriniz:</label>
          <select class="form-control" required name="siparis_il" id="il" data-select-search="true">
            <option value="">Şehrinizi seçiniz</option>
            <?php 
            $il=$db->prepare("SELECT * from il order by il_adi ASC");
            $il->execute();
            while($ilcek=$il->fetch(PDO::FETCH_ASSOC)) { ?>
              <option data-id="<?php echo $ilcek['id'] ?>" value="<?php echo $ilcek['il_adi']; ?>"><?php echo $ilcek['il_adi']; ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="col-12 col-sm-12 col-lg-12 order-box">
         <label>İlçeniz:</label>
          <input type="hidden" name="siparis_ilce" id="siparis_ilce_hidden" value="<?php echo htmlspecialchars((string)($front_order_post_data['siparis_ilce'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
          <select id="getIlceForIl" <?php echo $front_verify_pending ? '' : 'required'; ?> class="form-control">
          <option value="">Önce şehrinizi seçiniz</option>
         </select>
       </div>
       <div class="col-12 col-sm-12 col-lg-12 order-box">
        <label>Adresiniz:</label>
        <textarea class="form-control" required name="siparis_adres" id="siparis_adres_input" rows="3" placeholder="Açık adresinizi detaylı olarak yazınız" autocomplete="street-address" minlength="8" maxlength="700" title="En fazla 700 karakter"></textarea>
      </div>
      <div class="col-12 col-sm-12 col-lg-12 order-box">
        <label>Notunuz (İsteğe Bağlı):</label>
        <textarea class="form-control" name="siparis_not" id="siparis_not_input" rows="2" placeholder="Teslimat için ek notunuz varsa yazabilirsiniz." maxlength="1500" autocomplete="off" title="En fazla 1500 karakter"></textarea>
      </div>

      <?php if (!empty($settingsprint['ayar_kurumsal_fatura_on'])) { ?>
      <div class="col-12 col-sm-12 col-lg-12 order-box" style="margin-top: 8px;">
        <details class="kurumsal-fatura-details" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0; overflow: hidden;">
          <summary style="cursor: pointer; list-style: none; padding: 14px 16px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 10px; user-select: none;">
            <i class="fa fa-building-o" style="color: var(--renk2);"></i>
            <span>Kurumsal Fatura Bilgileri</span>
            <small style="font-weight: 500; color: #64748b;">(isteğe bağlı — tıklayınca açılır)</small>
          </summary>
          <div style="padding: 0 16px 16px; border-top: 1px solid #e2e8f0;">
            <p style="margin: 12px 0 14px; font-size: 0.9rem; color: #64748b;">Firmaya kesilecek fatura için bilgilerinizi doldurabilirsiniz. Tüm alanlar isteğe bağlıdır.</p>
            <div class="row" style="margin: 0 -6px;">
              <div class="col-12 col-sm-6" style="padding: 0 6px; margin-bottom: 12px;">
                <label for="siparis_fatura_vn">Vergi numarası</label>
                <input type="text" class="form-control" id="siparis_fatura_vn" name="siparis_fatura_vn" maxlength="11" inputmode="numeric" pattern="[0-9]*" autocomplete="off" placeholder="10 hane VKN veya 11 hane (sadece rakam)" title="Sadece rakam, en fazla 11 hane">
              </div>
              <div class="col-12 col-sm-6" style="padding: 0 6px; margin-bottom: 12px;">
                <label for="siparis_fatura_vd">Vergi dairesi</label>
                <input type="text" class="form-control" id="siparis_fatura_vd" name="siparis_fatura_vd" maxlength="128" autocomplete="organization" placeholder="Örn: Kadıköy" title="En fazla 128 karakter">
              </div>
              <div class="col-12" style="padding: 0 6px; margin-bottom: 12px;">
                <label for="siparis_fatura_unvan">Firma ünvanı</label>
                <input type="text" class="form-control" id="siparis_fatura_unvan" name="siparis_fatura_unvan" maxlength="255" autocomplete="organization" placeholder="Ticari unvan" title="En fazla 255 karakter">
              </div>
              <div class="col-12" style="padding: 0 6px; margin-bottom: 4px;">
                <label for="siparis_fatura_adres">Fatura adresi</label>
                <textarea class="form-control" id="siparis_fatura_adres" name="siparis_fatura_adres" rows="3" maxlength="3000" autocomplete="street-address" placeholder="Fatura kesilecek açık adres" title="En fazla 3000 karakter"></textarea>
              </div>
            </div>
          </div>
        </details>
        <style>
          .kurumsal-fatura-details summary::-webkit-details-marker { display: none; }
          .kurumsal-fatura-details summary::after { content: '\25BC'; font-size: 10px; color: #94a3b8; margin-left: auto; }
          .kurumsal-fatura-details[open] summary::after { transform: rotate(180deg); display: inline-block; }
        </style>
      </div>
      <?php } ?>

      <?php echo $front_verify_flash_html; ?>

      <?php if ($front_verify_pending) { ?>
      <div id="otp-modal-backdrop" style="position:fixed;inset:0;background:rgba(2, 6, 23, 0.58);backdrop-filter:blur(3px);z-index:9998;display:none;"></div>
      <div id="otp-modal" style="position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);width:min(92vw,440px);background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);border-radius:18px;padding:18px;border:1px solid #e2e8f0;box-shadow:0 30px 70px rgba(2,6,23,0.35);z-index:9999;display:none;font-family:Arial,'Segoe UI',sans-serif;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <div>
            <div style="font-size:12px;font-weight:700;color:#0ea5e9;letter-spacing:.08em;text-transform:uppercase;">Guvenli Dogrulama</div>
            <strong style="font-size:22px;color:#0f172a;line-height:1.2;">Telefon Dogrulama</strong>
          </div>
          <button type="button" id="otp-modal-close" style="width:34px;height:34px;border:none;background:#eef2ff;border-radius:10px;font-size:24px;line-height:1;color:#64748b;cursor:pointer;">&times;</button>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-start;background:#ecfeff;border:1px solid #bae6fd;border-radius:12px;padding:10px 12px;margin-bottom:12px;">
          <span style="font-size:18px;line-height:1;">&#128241;</span>
          <small style="display:block;color:#0f172a;">Kod <strong><?php echo htmlspecialchars($front_verify_tel_mask, ENT_QUOTES, 'UTF-8'); ?></strong> numarasina gonderildi. Kod 5 dakika gecerlidir.</small>
        </div>
        <input type="text" class="form-control" name="otp_code_front" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6 haneli kodu girin" style="margin-bottom:12px;height:48px;border-radius:12px;border:1px solid #cbd5e1;font-size:17px;letter-spacing:1px;text-align:center;font-weight:700;" required>
        <button type="submit" class="btn order-btn" name="otp_stage_verify_submit" style="width:100%;margin-bottom:8px;background:linear-gradient(135deg,#22c55e 0%,#16a34a 100%);color:#fff;border:none;border-radius:12px;min-height:48px;padding:12px 14px;font-weight:800;font-size:14px;line-height:1.25;letter-spacing:.02em;display:flex;align-items:center;justify-content:center;text-align:center;white-space:normal;word-break:break-word;">KODU DOGRULA VE SIPARISI TAMAMLA</button>
        <button type="submit" class="btn order-btn" name="otp_stage_resend_submit" formnovalidate style="width:100%;background:#fff;color:#0f172a;border:1px solid #cbd5e1;border-radius:12px;min-height:46px;padding:11px 14px;font-weight:700;font-size:14px;line-height:1.25;letter-spacing:.02em;display:flex;align-items:center;justify-content:center;text-align:center;white-space:normal;">KODU TEKRAR GONDER</button>
      </div>
      <?php } ?>

      <div class="col-12 col-sm-12 col-lg-12" style="clear: both; width: 100%;">
        <div id="selected-product-confirm" style="background:#ecfeff;border:1px solid #a5f3fc;color:#155e75;font-weight:700;border-radius:10px;padding:10px 12px;margin:8px 0 10px;">Siparişe eklenecek ürün: -</div>
        <div style="word-wrap:break-word; padding-top:4px;">
          <?php if (!$front_verify_pending) { ?>
           <button id="siparisButon" type="submit" class="btn btn-gfort order-btn" name="siparisver" onclick="if(this.form.checkValidity()) { this.innerHTML='İŞLEM YAPILIYOR...'; this.style.opacity='0.8'; }">SİPARİŞİ TAMAMLA</button>
          <?php } else { ?>
           <button id="openOtpModalBtn" type="button" class="btn btn-gfort order-btn">DOGRULAMA PENCERESINI AC</button>
          <?php } ?>

      <?php if ((isset($settingsprint['ayar_sozlesme_on']) && $settingsprint['ayar_sozlesme_on'] == 1) || (isset($settingsprint['ayar_gizlilik_on']) && $settingsprint['ayar_gizlilik_on'] == 1)) { ?>
        <div style="margin: 18px 0 0; background: #ffffff; padding: 18px 20px; border-radius: 12px; border: 1px solid #e0e7ff; box-shadow: 0 2px 8px rgba(99, 102, 241, 0.08); transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.12)'; this.style.borderColor='#c7d2fe';" onmouseout="this.style.boxShadow='0 2px 8px rgba(99, 102, 241, 0.08)'; this.style.borderColor='#e0e7ff';">
          <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <input type="checkbox" id="sozlesme_onay" required checked style="margin: 0; transform: scale(1.2); cursor: pointer; accent-color: #22c55e; width: 18px; height: 18px; flex-shrink: 0;">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; flex: 1; align-items: center;">

              <?php if (isset($settingsprint['ayar_sozlesme_on']) && $settingsprint['ayar_sozlesme_on'] == 1) { ?>
              <a href="javascript:void(0);" onclick="openModal()" style="color: #6366f1; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; padding: 8px 15px; background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); border-radius: 8px; border: 1px solid #c7d2fe; min-width: 200px; justify-content: center;" onmouseover="this.style.background='linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)'; this.style.color='#4f46e5'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%)'; this.style.color='#6366f1'; this.style.transform='translateY(0)';" class="responsive-policy-btn">
                <i class="fa fa-file-text-o" style="font-size: 14px;"></i>
                <span>Mesafeli Satış Sözleşmesi</span>
                <i class="fa fa-external-link" style="font-size: 10px; opacity: 0.8;"></i>
              </a>
              <?php } ?>

              <?php if (isset($settingsprint['ayar_gizlilik_on']) && $settingsprint['ayar_gizlilik_on'] == 1) { ?>
              <a href="javascript:void(0);" onclick="openPrivacyModal()" style="color: #6366f1; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; padding: 8px 15px; background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); border-radius: 8px; border: 1px solid #c7d2fe; min-width: 200px; justify-content: center;" onmouseover="this.style.background='linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)'; this.style.color='#4f46e5'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%)'; this.style.color='#6366f1'; this.style.transform='translateY(0)';" class="responsive-policy-btn">
                <i class="fa fa-shield" style="font-size: 14px;"></i>
                <span>Gizlilik Politikası</span>
                <i class="fa fa-external-link" style="font-size: 10px; opacity: 0.8;"></i>
              </a>
              <?php } ?>

              <style>
                @media (max-width: 576px) {
                  .responsive-policy-btn {
                    width: 100% !important;
                    min-width: 100% !important;
                  }
                }
              </style>

            </div>
          </div>
        </div>
      <?php } ?>

           <!-- Trust Badges (Güven Rozetleri) -->
           <div style="display: flex; justify-content: center; gap: 5px; margin-top: 25px; margin-bottom: 25px; flex-wrap: wrap;">
               <div style="display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 1.1rem; font-weight: 600;">
                   <i class="fa fa-lock" style="color: #22c55e;"></i> 256 Bit SSL
               </div>
               <div style="display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 1.1rem; font-weight: 600;">
                   <i class="fa fa-shield" style="color: #22c55e;"></i> Güvenli Ödeme
               </div>
               <div style="display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 1.1rem; font-weight: 600;">
                   <i class="fa fa-truck" style="color: #22c55e;"></i> Hızlı Kargo
               </div>
           </div>
        </div>
        </div>
        <script>
        // Form submit kontrolü (Yedek)
        document.getElementById('myform').addEventListener('submit', function() {
             var btn = document.getElementById('siparisButon');
             if (!btn) return;
             // btn.disabled = true; // Disabled yaparsak post gitmeyebilir, sadece görseli değiştirelim
             btn.innerHTML = 'LÜTFEN BEKLEYİN...';
             btn.style.opacity = '0.8';
             btn.style.pointerEvents = 'none';
        });
        <?php if ($front_verify_pending) { ?>
        // OTP aşamasında ana form required alanlarını devreden çıkar.
        document.querySelectorAll('#myform [required]').forEach(function(el){
          if (el.name !== 'otp_code_front') {
            el.removeAttribute('required');
          }
        });
        // OTP popup'u otomatik aç.
        (function() {
          var modal = document.getElementById('otp-modal');
          var backdrop = document.getElementById('otp-modal-backdrop');
          var closeBtn = document.getElementById('otp-modal-close');
          function closeOtpModal() {
            if (modal) modal.style.display = 'none';
            if (backdrop) backdrop.style.display = 'none';
            document.body.style.overflow = 'auto';
          }
          function openOtpModal() {
            if (modal) modal.style.display = 'block';
            if (backdrop) backdrop.style.display = 'block';
            document.body.style.overflow = 'hidden';
            var otpInput = modal ? modal.querySelector('input[name="otp_code_front"]') : null;
            if (otpInput) otpInput.focus();
          }
          if (modal && backdrop) {
            openOtpModal();
          }
          if (closeBtn) closeBtn.addEventListener('click', closeOtpModal);
          if (backdrop) backdrop.addEventListener('click', closeOtpModal);
          var openBtn = document.getElementById('openOtpModalBtn');
          if (openBtn) openBtn.addEventListener('click', openOtpModal);
        })();
        <?php } ?>
        </script>
      </div>
    </form>
    <?php } // Çerez kontrolü else sonu ?>
  </div>
  </div>



  <script>
    function openModal() { 
        document.getElementById('agreementModal').style.display = 'block'; 
        document.body.style.overflow = 'hidden'; // Stop background scroll
    }
    function closeModal() { 
        document.getElementById('agreementModal').style.display = 'none'; 
        document.body.style.overflow = 'auto'; // Restore background scroll
    }
    window.onclick = function(event) {
      if (event.target == document.getElementById('agreementModal')) { closeModal(); }
    }
    
    // Telefon: sadece rakam, en fazla 12 hane (90… ile birlikte)
    document.getElementById('tel_input').addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '').substring(0, 12);
    });
    // Ad soyad: harf + boşluk + tire (eski tarayıcı uyumlu; \p{L} kullanılmıyor)
    (function () {
      var adEl = document.getElementById('siparis_ad_input');
      if (!adEl) return;
      var adSafe = /[^a-zA-ZçğıöşüÇĞİÖŞ\u00C0-\u00FF\s\-]/g;
      adEl.addEventListener('input', function () {
        this.value = this.value.replace(adSafe, '').substring(0, 100);
      });
    })();
    // Kurumsal fatura: vergi no sadece rakam, max 11
    (function () {
      var fvn = document.getElementById('siparis_fatura_vn');
      if (!fvn) return;
      fvn.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
      });
    })();
  </script>
</section>

<?php if (isset($settingsprint['ayar_sss_on']) && $settingsprint['ayar_sss_on'] == 1) { ?>
<section id="faq-section" style="background: #fff; padding: 60px 0;">
    <div class="container" style="max-width: 860px;">
        <div class="section-title text-center" style="margin-bottom: 40px;">
            <h2 style="font-weight: 800; color: #333;">Sıkça Sorulan Sorular</h2>
            <span style="display: block; width: 60px; height: 4px; background: var(--renk2); margin: 15px auto 0; border-radius: 2px;"></span>
        </div>
        <div class="faq-container">
            <?php
            $ssssor = $db->prepare("SELECT * FROM sss ORDER BY sss_sira ASC");
            $ssssor->execute();
            while($ssscek = $ssssor->fetch(PDO::FETCH_ASSOC)) {
            ?>
            <div class="faq-item" style="margin-bottom: 20px; border: 1px solid #eee; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.02);">
                <div class="faq-question" style="padding: 18px 25px; background: #fdfdfd; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s ease;">
                    <span style="font-weight: 700; color: #333; font-size: 1.1rem;"><?php echo $ssscek['sss_soru']; ?></span>
                    <i class="fa fa-chevron-down" style="color: var(--renk2); font-size: 0.9rem;"></i>
                </div>
                <div class="faq-answer" style="padding: 0 25px; max-height: 0; overflow: hidden; transition: all 0.3s ease; background: #fff;">
                    <div style="padding: 20px 0; color: #666; line-height: 1.7;">
                        <?php echo $ssscek['sss_cevap']; ?>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
<script>
    document.querySelectorAll('.faq-question').forEach(item => {
        item.addEventListener('click', () => {
            const answer = item.nextElementSibling;
            const icon = item.querySelector('i');
            
            if (answer.style.maxHeight && answer.style.maxHeight !== '0px') {
                answer.style.maxHeight = '0px';
                icon.style.transform = 'rotate(0deg)';
                item.style.background = '#fdfdfd';
            } else {
                answer.style.maxHeight = answer.scrollHeight + 'px';
                icon.style.transform = 'rotate(180deg)';
                item.style.background = '#f8fefb';
            }
        });
    });
</script>
<?php } ?>


<?php if (!isset($settingsprint['ayar_altgorsel_on']) || $settingsprint['ayar_altgorsel_on'] == 1) { ?>
<section style="align-items: center;padding: 0 0;">
  <div class="">
    <!--Embeds -->
    <div class="row">
      <center>
    <div class="row">
      <center>
        <div><a href="javascript:void(0);" onclick="scrollToOrder(event); return false;"><img style="max-width: <?php echo isset($settingsprint['ayar_harita']) ? $settingsprint['ayar_harita'] : 1200; ?>px;width: 100%; <?php if (isset($settingsprint['ayar_adres']) && $settingsprint['ayar_adres']==1) { ?>box-shadow: 0 0 8px 0px var(--renk2);<?php } ?>" src="xnull/<?php echo isset($settingsprint['ayar_altgorsel']) ? $settingsprint['ayar_altgorsel'] : ''; ?>" class="img-responsive"/></a></div>
      </center>
    </div>
      </center>
    </div>
  </div>
</section>
<?php } ?>

<!-- Sözleşme Modal (Global Konum) -->
<div id="agreementModal" class="modal-agreement">
  <div class="modal-content-agreement">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <h2 style="font-weight: 800; margin-bottom: 25px;">Mesafeli Satış Sözleşmesi</h2>
    <div id="modal-text" style="color: #555; line-height: 1.8;">
      <?php 
      $sozlesmesor = $db->prepare("SELECT * FROM sozlesme WHERE id=1");
      $sozlesmesor->execute();
      $sozlesmecek = $sozlesmesor->fetch(PDO::FETCH_ASSOC);
      echo $sozlesmecek['icerik'];
      ?>
    </div>
    <div style="margin-top: 30px; text-align: right;">
      <button class="btn btn-success" onclick="closeModal()" style="padding: 10px 25px; border-radius: 10px; font-weight: 700;">Kapat</button>
    </div>
  </div>
</div>

<!-- Gizlilik Politikası Modal -->
<div id="privacyModal" class="modal-agreement">
  <div class="modal-content-agreement">
    <span class="close-modal" onclick="closePrivacyModal()">&times;</span>
    <h2 style="font-weight: 800; margin-bottom: 25px;">Gizlilik Politikası</h2>
    <div id="privacy-modal-text" style="color: #555; line-height: 1.8;">
      <?php 
      $gizliliksor = $db->prepare("SELECT * FROM gizlilik WHERE id=1");
      $gizliliksor->execute();
      $gizlilikcek = $gizliliksor->fetch(PDO::FETCH_ASSOC);
      echo $gizlilikcek['icerik'];
      ?>
    </div>
    <div style="margin-top: 30px; text-align: right;">
      <button class="btn btn-success" onclick="closePrivacyModal()" style="padding: 10px 25px; border-radius: 10px; font-weight: 700;">Kapat</button>
    </div>
  </div>
</div>

<script>
function openPrivacyModal() {
    document.getElementById('privacyModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePrivacyModal() {
    document.getElementById('privacyModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Dışarı tıklayınca kapatma
window.onclick = function(event) {
    var modal = document.getElementById('agreementModal');
    var privacyModal = document.getElementById('privacyModal');
    if (event.target == modal) {
        closeModal();
    }
    if (event.target == privacyModal) {
        closePrivacyModal();
    }
}
</script>

</div><!-- /dir=ltr — ana içerik; footer’daki fazla </div> kaldırıldı, kapanış burada -->

<?php include 'include/footer.php'; ?>

<!-- Çarkıfelek Modal -->
<?php if (isset($settingsprint['ayar_carkifelek_on']) && $settingsprint['ayar_carkifelek_on'] == 1) { 
    $carkifelek_baslik = isset($settingsprint['ayar_carkifelek_baslik']) ? $settingsprint['ayar_carkifelek_baslik'] : 'Çarkıfelek Çevir, İndirim Kazan!';
    $carkifelek_aciklama = isset($settingsprint['ayar_carkifelek_aciklama']) ? $settingsprint['ayar_carkifelek_aciklama'] : 'Günde 1 kez çarkıfelek çevirerek indirim kazanabilirsiniz!';
    $carkifelek_renk1 = isset($settingsprint['ayar_carkifelek_renk1']) ? $settingsprint['ayar_carkifelek_renk1'] : '#ff6b6b';
    $carkifelek_renk2 = isset($settingsprint['ayar_carkifelek_renk2']) ? $settingsprint['ayar_carkifelek_renk2'] : '#ee5a6f';
    $carkifelek_vurgu = isset($settingsprint['ayar_carkifelek_vurgu']) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', trim((string)($settingsprint['ayar_carkifelek_vurgu'] ?? '')))
        ? trim($settingsprint['ayar_carkifelek_vurgu'])
        : '#fbbf24';
    $cf1 = htmlspecialchars($carkifelek_renk1, ENT_QUOTES, 'UTF-8');
    $cf2 = htmlspecialchars($carkifelek_renk2, ENT_QUOTES, 'UTF-8');
    $cfv = htmlspecialchars($carkifelek_vurgu, ENT_QUOTES, 'UTF-8');
?>
<!-- Confetti Library -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<style>
#carkifelek-modal.carkifelek-wrap {
    --cf-c1: <?php echo $cf1; ?>;
    --cf-c2: <?php echo $cf2; ?>;
    --cf-v: <?php echo $cfv; ?>;
}
#carkifelek-modal.carkifelek-wrap .carkifelek-backdrop-inner {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
}
#carkifelek-modal.carkifelek-wrap #carkifelek-content {
    position: relative;
    background: linear-gradient(165deg, #ffffff 0%, #f8fafc 45%, #fff 100%);
    border-radius: 24px;
    max-width: 520px;
    width: 100%;
    padding: 32px 28px 28px;
    border: 2px solid transparent;
    background-clip: padding-box;
    box-shadow:
        0 0 0 2px rgba(255,255,255,0.5) inset,
        0 28px 80px rgba(15, 23, 42, 0.35),
        0 0 42px rgba(250, 204, 21, 0.18);
}
#carkifelek-modal.carkifelek-wrap #carkifelek-content::before {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 26px;
    padding: 3px;
    background: linear-gradient(135deg, var(--cf-c1), var(--cf-v), var(--cf-c2));
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
    z-index: 0;
}
#carkifelek-modal.carkifelek-wrap #carkifelek-content > * { position: relative; z-index: 1; }
#carkifelek-modal.carkifelek-wrap .carkifelek-title {
    text-align: center;
    margin: 0 0 12px;
    font-weight: 900;
    font-size: 1.45rem;
    line-height: 1.25;
    letter-spacing: -0.02em;
    background: linear-gradient(105deg, var(--cf-c1) 0%, var(--cf-c2) 55%, var(--cf-v) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    filter: drop-shadow(0 2px 14px rgba(0, 0, 0, 0.08));
}
#carkifelek-modal.carkifelek-wrap .carkifelek-sub {
    text-align: center;
    color: #64748b;
    margin-bottom: 22px;
    font-size: 14px;
    line-height: 1.5;
    font-weight: 500;
}
#carkifelek-modal.carkifelek-wrap .carkifelek-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    background: linear-gradient(180deg, #fff, #f1f5f9);
    cursor: pointer;
    font-size: 22px;
    line-height: 1;
    color: #64748b;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    z-index: 5;
    display: flex;
    align-items: center;
    justify-content: center;
}
#carkifelek-modal.carkifelek-wrap .carkifelek-close:hover {
    transform: scale(1.08) rotate(90deg);
    border-color: var(--cf-c1);
    color: var(--cf-c1);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
#carkifelek-modal.carkifelek-wrap #carkifelek-wheel-wrap-inner {
    position: relative;
    padding-bottom: 100%;
    filter: drop-shadow(0 16px 32px rgba(15, 23, 42, 0.18));
}
#carkifelek-modal.carkifelek-wrap #wheelCanvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    transform: rotate(0deg);
    transition: transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99);
    border-radius: 50%;
}
#carkifelek-modal.carkifelek-wrap .carkifelek-hub {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 68px;
    height: 68px;
    background: radial-gradient(circle at 35% 30%, #fff 0%, #f1f5f9 100%);
    border-radius: 50%;
    border: 4px solid var(--cf-v);
    box-shadow:
        0 0 0 3px rgba(255,255,255,0.95),
        0 8px 28px rgba(0,0,0,0.2),
        0 0 18px rgba(251, 191, 36, 0.35);
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    animation: carkifelek-hub-pulse 2.2s ease-in-out infinite;
}
#carkifelek-modal.carkifelek-wrap .carkifelek-hub span {
    font-weight: 900;
    font-size: 11px;
    letter-spacing: 0.06em;
    pointer-events: none;
    background: linear-gradient(135deg, #1e293b, #334155);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
@keyframes carkifelek-hub-pulse {
    0%, 100% { box-shadow: 0 0 0 3px rgba(255,255,255,0.95), 0 8px 28px rgba(0,0,0,0.2), 0 0 14px rgba(251, 191, 36, 0.3); }
    50% { box-shadow: 0 0 0 3px rgba(255,255,255,0.95), 0 12px 36px rgba(0,0,0,0.22), 0 0 26px rgba(251, 191, 36, 0.55); }
}
#carkifelek-modal.carkifelek-wrap #carkifelek-spin-btn {
    background: linear-gradient(90deg, var(--cf-c1) 0%, var(--cf-c2) 50%, var(--cf-c1) 100%);
    background-size: 200% 100%;
    animation: carkifelek-btn-shine 4s ease infinite;
    color: #fff;
    border: none;
    padding: 16px 40px;
    border-radius: 999px;
    font-weight: 900;
    font-size: 17px;
    letter-spacing: 0.04em;
    cursor: pointer;
    width: 100%;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    box-shadow:
        0 4px 0 rgba(0, 0, 0, 0.18),
        0 14px 36px rgba(0, 0, 0, 0.2);
    transition: transform 0.15s ease, filter 0.15s ease;
}
#carkifelek-modal.carkifelek-wrap #carkifelek-spin-btn:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.01);
    filter: brightness(1.06);
}
#carkifelek-modal.carkifelek-wrap #carkifelek-spin-btn:active:not(:disabled) {
    transform: translateY(1px);
}
#carkifelek-modal.carkifelek-wrap #carkifelek-spin-btn:disabled {
    opacity: 0.88;
    cursor: wait;
    animation: none;
}
@keyframes carkifelek-btn-shine {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
#carkifelek-modal.carkifelek-wrap .carkifelek-limit-title {
    background: linear-gradient(90deg, var(--cf-c1), var(--cf-c2));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    font-weight: 900;
}
</style>

<div id="carkifelek-modal" class="carkifelek-wrap" onclick="checkCloseWheel(event)" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2147483647 !important; overflow-y: auto; background: rgba(15, 23, 42, 0.78); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">
    <div class="carkifelek-backdrop-inner">
        <div id="carkifelek-content" onclick="event.stopPropagation();">
            <button type="button" class="carkifelek-close" onclick="event.stopPropagation(); closeCarkifelekModal(event); return false;" aria-label="Kapat">&times;</button>

            <h2 class="carkifelek-title"><?php echo htmlspecialchars($carkifelek_baslik); ?></h2>
            <p class="carkifelek-sub"><?php echo htmlspecialchars($carkifelek_aciklama); ?></p>

            <div id="carkifelek-wheel-container" style="position: relative; width: 100%; max-width: 400px; margin: 12px auto 8px;">
                <div id="carkifelek-wheel-wrap-inner">
                    <canvas id="wheelCanvas" width="500" height="500"></canvas>

                    <div style="position: absolute; top: -18px; left: 50%; transform: translateX(-50%); z-index: 20; filter: drop-shadow(0 6px 12px rgba(0,0,0,0.35));">
                        <svg width="44" height="54" viewBox="0 0 44 54" aria-hidden="true">
                            <defs>
                                <linearGradient id="carkifelekPtrGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:<?php echo $cf1; ?>;stop-opacity:1" />
                                    <stop offset="55%" style="stop-color:<?php echo $cfv; ?>;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:<?php echo $cf2; ?>;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <path d="M22 54 L2 4 L42 4 Z" fill="url(#carkifelekPtrGrad)" stroke="#1e293b" stroke-width="1.2" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <div class="carkifelek-hub" onclick="event.stopPropagation(); spinWheel(event);"><span>ŞANS</span></div>
                </div>
            </div>

            <div id="carkifelek-result" style="display: none; text-align: center; margin-top: 18px; font-weight: 800; font-size: 17px; color: #0f766e;"></div>

            <div id="carkifelek-spin-btn-container" style="text-align: center; margin-top: 22px;">
                <button type="button" id="carkifelek-spin-btn" onclick="event.stopPropagation(); spinWheel(event); return false;">ÇEVİR VE KAZAN</button>
            </div>
            <div id="carkifelek-limit-msg" style="display: none; text-align: center; padding: 20px;">
                <div style="font-size: 40px; margin-bottom: 10px;">⚠️</div>
                <h3 class="carkifelek-limit-title" style="margin-bottom: 10px;">Şu an çarkı çeviremezsiniz</h3>
                <p id="carkifelek-limit-detail" style="color: #64748b; font-size: 14px;"><?php echo nl2br(htmlspecialchars($carkifelek_aciklama, ENT_QUOTES, 'UTF-8')); ?></p>
                <button type="button" onclick="event.stopPropagation(); closeCarkifelekModal(event); return false;" style="margin-top: 20px; background: linear-gradient(180deg,#f8fafc,#e2e8f0); border: 2px solid #cbd5e1; padding: 10px 28px; border-radius: 12px; cursor: pointer; font-weight: 800; color: #334155;">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
// Çarkıfelek Mantığı
const wheelCanvas = document.getElementById('wheelCanvas');
const ctx = wheelCanvas.getContext('2d');

// PHP'den ödülleri al
<?php
// Varsayılan ödüller
$default_rewards = [
    '%10 İndirim', 'Kargo Bedava', '%5 İndirim', 'Sürpriz', 
    '%20 İndirim', 'Pas', '%15 İndirim', 'Kargo Bedava'
];

// Ayarlardan ödül listesini al
if (isset($settingsprint['ayar_carkifelek_oduller']) && !empty($settingsprint['ayar_carkifelek_oduller'])) {
    // JSON olarak decode et (controller'da JSON olarak kaydediliyor)
    $rewards_list = json_decode($settingsprint['ayar_carkifelek_oduller'], true);
    
    // Eğer decode başarısızsa veya dizi boşsa varsayılanlara dön
    if (!is_array($rewards_list) || count($rewards_list) < 4) {
        $rewards_list = $default_rewards;
    }
} else {
    $rewards_list = $default_rewards;
}

// JSON olarak JS değişkenine aktar
echo "const rewardLabels = " . json_encode(array_values($rewards_list)) . ";\n";
?>

// Renkler (panel: Ana 1 / Ana 2 / Vurgu)
const carkRenk1 = '<?php echo addslashes($carkifelek_renk1); ?>';
const carkRenk2 = '<?php echo addslashes($carkifelek_renk2); ?>';
const carkVurgu = '<?php echo addslashes($carkifelek_vurgu); ?>';
const carkifelekLimitMetin = <?php echo json_encode($carkifelek_aciklama, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
const carkifelekAjaxUrl = <?php echo json_encode(rtrim(SITE_URL, '/') . '/js/ajax/carkifelekKaydet.php'); ?>;
const colors = rewardLabels.map((_, i) => i % 2 === 0 ? carkRenk1 : carkRenk2);

function hexToRgb(hex) {
    var h = (hex || '').replace('#', '');
    if (h.length === 3) {
        h = h.split('').map(function (c) { return c + c; }).join('');
    }
    if (h.length !== 6) return { r: 128, g: 128, b: 128 };
    var n = parseInt(h, 16);
    return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
}
function relLum(hex) {
    var o = hexToRgb(hex);
    var r = o.r / 255, g = o.g / 255, b = o.b / 255;
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}
function adjustHex(hex, t) {
    var o = hexToRgb(hex);
    if (t >= 0) {
        return 'rgb(' +
            Math.round(o.r + (255 - o.r) * t) + ',' +
            Math.round(o.g + (255 - o.g) * t) + ',' +
            Math.round(o.b + (255 - o.b) * t) + ')';
    }
    t = -t;
    return 'rgb(' +
        Math.round(o.r * (1 - t)) + ',' +
        Math.round(o.g * (1 - t)) + ',' +
        Math.round(o.b * (1 - t)) + ')';
}

// Ses Efekti (Audio Context)
const tickSound = new Audio('data:audio/wav;base64,UklGRl9vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19vT19v');
tickSound.volume = 0.5;

function playTick() {
    tickSound.cloneNode(true).play().catch(() => {});
}

function carkEscHtml(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// Segmentleri oluştur
const segments = rewardLabels.map((label, index) => {
    var c = colors[index % colors.length];
    return {
        text: label,
        color: c,
        textColor: relLum(c) > 0.58 ? '#0f172a' : '#ffffff',
        value: 0
    };
});

const totalSegments = segments.length;
const arc = (2 * Math.PI) / totalSegments;
let currentRotation = 0;
let isWheelSpinning = false;
let isWheelFinished = false;

function drawWheel() {
    ctx.clearRect(0, 0, 500, 500);
    const centerX = 250;
    const centerY = 250;
    const radius = 228;

    segments.forEach((segment, i) => {
        const angle = i * arc;
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, angle, angle + arc);
        ctx.closePath();
        const rg = ctx.createRadialGradient(centerX, centerY, 18, centerX, centerY, radius);
        rg.addColorStop(0, adjustHex(segment.color, 0.38));
        rg.addColorStop(0.65, segment.color);
        rg.addColorStop(1, adjustHex(segment.color, -0.22));
        ctx.fillStyle = rg;
        ctx.fill();

        ctx.save();
        ctx.translate(centerX, centerY);
        ctx.rotate(angle + arc / 2);
        ctx.textAlign = 'right';
        ctx.fillStyle = segment.textColor || '#fff';
        var fsize = segment.text.length > 16 ? 14 : 17;
        ctx.font = 'bold ' + fsize + 'px Segoe UI, system-ui, Arial, sans-serif';
        ctx.shadowColor = 'rgba(0,0,0,0.35)';
        ctx.shadowBlur = 5;
        ctx.shadowOffsetY = 1;
        ctx.fillText(segment.text, radius - 22, 6);
        ctx.restore();
    });

    for (let i = 0; i < totalSegments; i++) {
        const a = i * arc;
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.lineTo(centerX + Math.cos(a) * radius, centerY + Math.sin(a) * radius);
        ctx.strokeStyle = 'rgba(255,255,255,0.9)';
        ctx.lineWidth = 3;
        ctx.stroke();
    }

    ctx.beginPath();
    ctx.arc(centerX, centerY, radius + 3, 0, 2 * Math.PI);
    ctx.strokeStyle = carkVurgu;
    ctx.lineWidth = 8;
    ctx.globalAlpha = 0.92;
    ctx.stroke();
    ctx.globalAlpha = 1;

    ctx.beginPath();
    ctx.arc(centerX, centerY, radius + 1, 0, 2 * Math.PI);
    ctx.lineWidth = 3;
    ctx.strokeStyle = 'rgba(255,255,255,0.75)';
    ctx.stroke();
}

drawWheel();

window.lastWonReward = null;
function applyWheelRewardToUI() {
    if (!window.lastWonReward) return;
    
    // Temizlik: Önceki ekleri kaldır (tekrar eklenmesini önlemek için)
    $('.wheel-badge').remove();
    
    var res = window.lastWonReward;
    if (res.indirim > 0) {
        $('.meta-order-total, .meta-order-total-sticky').each(function() {
            $(this).css('color', '#d63031').after(' <small class="wheel-badge" style="color:#d63031; font-weight:700;">(%'+res.indirim+' İndirim Uygulandı)</small>');
        });
    } else if (res.odul.toLowerCase().indexOf('kargo') !== -1) {
        $('.meta-order-total, .meta-order-total-sticky').each(function() {
            $(this).after(' <small class="wheel-badge" style="color:#2ecc71; font-weight:700;">(Ücretsiz Kargo!)</small>');
        });
    }
}

function spinWheel(wheelEv) {
    const btn = document.getElementById('carkifelek-spin-btn');
    
    if (typeof isWheelFinished !== 'undefined' && isWheelFinished) {
        closeCarkifelekModal(wheelEv || window.event || null);
        return;
    }

    if(btn.disabled || isWheelSpinning) return;
    
    btn.disabled = true;
    btn.innerHTML = 'KONTROL EDİLİYOR...';
    
    // AJAX ile IP kontrolü ve Kayıt
    $.ajax({
        type: 'POST',
        url: carkifelekAjaxUrl,
        data: {spin: 1},
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                isWheelSpinning = true;
                btn.innerHTML = 'ÇEVRİLİYOR...';
                
                // --- ÇARK SENKRONİZASYONU ---
                // res.odul segmentine denk gelecek açıyı hesapla
                var segIndex = rewardLabels.indexOf(res.odul);
                if (segIndex === -1) segIndex = 0; // Bulunamazsa varsayılan
                
                var totalSegs = rewardLabels.length;
                var arcDeg = 360 / totalSegs;
                
                // Pointer (üstte) 270 derece konumunda
                // Segment merkez açısı: (segIndex * arcDeg) + (arcDeg / 2)
                // Gereken dönüş: rotation = (270 - segment_merkez)
                var targetAngle = (segIndex * arcDeg) + (arcDeg / 2);
                var spins = 5;
                var rotation = (270 - targetAngle) + (spins * 360);
                
                // currentRotation'dan bağımsız olarak her seferinde temiz tur için:
                // Canvas'ı currentRotation'ın en yakın 360 katına tamamlayıp yeni rotation'ı ekleyelim
                var baseRotation = Math.ceil(currentRotation / 360) * 360;
                var finalRotation = baseRotation + rotation;
                
                const wheel = document.getElementById('wheelCanvas');
                wheel.style.transform = `rotate(${finalRotation}deg)`;
                currentRotation = finalRotation;
                // -----------------------------

                setTimeout(() => {
                    // Konfeti Efekti
                    confetti({
                        particleCount: 180,
                        spread: 78,
                        origin: { y: 0.58 },
                        colors: [carkRenk1, carkRenk2, carkVurgu, '#ffffff', '#fef08a']
                    });

                    document.getElementById('carkifelek-result').innerHTML = 'TEBRİKLER! <br><span style="font-size:24px;font-weight:900;background:linear-gradient(90deg,' + carkRenk1 + ',' + carkRenk2 + ');-webkit-background-clip:text;background-clip:text;color:transparent;">' + carkEscHtml(res.odul) + '</span><br><span style="color:#64748b;font-weight:600;">Kazandınız!</span>';
                    document.getElementById('carkifelek-result').style.display = 'block';
                    btn.innerHTML = 'KAZANDINIZ';
                    isWheelSpinning = false;
                    isWheelFinished = true;
                    
                    // Ödülü form inputuna kaydet
                    document.getElementById('carkifelek_odul').value = res.odul;
                    window.lastWonReward = res;
                    
                    // Fiyat alanına bilgi ekle
                    applyWheelRewardToUI();
                    
                    // Cookie set et (yedek kontrol)
                    var d = new Date();
                    d.setTime(d.getTime() + (24*60*60*1000));
                    var expires = "expires="+ d.toUTCString();
                    document.cookie = "carkifelek_kullanildi=1;" + expires + ";path=/";
                    
                    // Butonu aktif et (Kapanması için)
                    btn.disabled = false;
                }, 4000);

                // Rotasyon sırasında tick sesi simülasyonu
                let lastTickAngle = 0;
                const tickInterval = setInterval(() => {
                    if(!isWheelSpinning) {
                        clearInterval(tickInterval);
                        return;
                    }
                    playTick();
                }, 200);
            } else {
                // Limit aşımı veya hata
                btn.disabled = false;
                btn.innerHTML = 'ÇEVİR VE KAZAN';
                var det = document.getElementById('carkifelek-limit-detail');
                var limitTxt = (res && res.message) ? res.message : (typeof carkifelekLimitMetin !== 'undefined' ? carkifelekLimitMetin : '');
                if (det && limitTxt) {
                    det.innerHTML = limitTxt.replace(/\n/g, '<br>');
                }
                btn.style.display = 'none';
                document.getElementById('carkifelek-wheel-container').style.display = 'none';
                document.getElementById('carkifelek-limit-msg').style.display = 'block';
            }
        },
        error: function() {
            btn.disabled = false;
            btn.innerHTML = 'ÇEVİR VE KAZAN';
            alert('Bir hata oluştu, lütfen tekrar deneyin.');
        }
    });
}

function checkCloseWheel(event) {
    if (!event) return;
    var content = document.getElementById('carkifelek-content');
    if (content && (event.target === content || content.contains(event.target))) {
        return;
    }
    if (!isWheelFinished) {
        event.stopPropagation();
        return;
    }
    event.preventDefault();
    event.stopPropagation();
    closeCarkifelekModal(event);
}

function openCarkifelekModal() {
    document.body.style.pointerEvents = '';
    if (window._carkifelekPointerFix) {
        clearTimeout(window._carkifelekPointerFix);
        window._carkifelekPointerFix = null;
    }
    document.getElementById('carkifelek-modal').style.display = 'block';
    
    // Her açılışta buton ve çarkı sıfırla (eğer limit dolmamışsa)
    document.getElementById('carkifelek-spin-btn').style.display = 'block';
    document.getElementById('carkifelek-spin-btn').disabled = false;
    document.getElementById('carkifelek-spin-btn').innerHTML = 'ÇEVİR VE KAZAN';
    document.getElementById('carkifelek-wheel-container').style.display = 'block';
    document.getElementById('carkifelek-limit-msg').style.display = 'none';
    document.getElementById('carkifelek-result').style.display = 'none';
}

function closeCarkifelekModal(ev) {
    if (ev && ev.preventDefault) ev.preventDefault();
    if (ev && ev.stopPropagation) ev.stopPropagation();
    var modal = document.getElementById('carkifelek-modal');
    if (modal) modal.style.display = 'none';
    isWheelFinished = false;
    /* Mobilde modal kapanınca aynı dokunuşun alttaki scrollToOrder linkine gitmesini engelle */
    document.body.style.pointerEvents = 'none';
    clearTimeout(window._carkifelekPointerFix);
    window._carkifelekPointerFix = setTimeout(function () {
        document.body.style.pointerEvents = '';
        window._carkifelekPointerFix = null;
    }, 400);
}

// Otomatik açılma: tek tetik (çift ready+load kaldırıldı — 0–2 sn ayarları ve çift modal hatası giderilir)
(function() {
    <?php
    $auto_on = isset($settingsprint['ayar_carkifelek_auto_on']) ? (int) $settingsprint['ayar_carkifelek_auto_on'] : 1;
    $auto_sn = isset($settingsprint['ayar_carkifelek_auto_sn']) ? (int) $settingsprint['ayar_carkifelek_auto_sn'] : 5;
    if ($auto_sn < 0) {
        $auto_sn = 0;
    }
    $auto_delay_ms = $auto_sn * 1000;

    if ($auto_on === 1) { ?>
    var wheelAutoInitLock = false;
    var wheelAutoRetryDone = false;
    var wheelAutoDelayMs = <?php echo (int) $auto_delay_ms; ?>;

    function tryTriggerWheelAuto() {
        if (wheelAutoInitLock) {
            return;
        }
        wheelAutoInitLock = true;

        var isDebug = window.location.search.indexOf('debug_wheel=1') !== -1;

        if (document.cookie.indexOf('carkifelek_kullanildi=1') !== -1 && !isDebug) {
            console.log('Çarkıfelek otomatik: çerez var, açılmıyor.');
            return;
        }

        function doLimitAjax(isRetry) {
            $.ajax({
                type: 'POST',
                url: typeof carkifelekAjaxUrl !== 'undefined' ? carkifelekAjaxUrl : 'js/ajax/carkifelekKaydet.php',
                data: { check_limit_only: 1 },
                dataType: 'json',
                timeout: 15000,
                success: function(res) {
                    if (res && (res.status === 'success' || isDebug)) {
                        setTimeout(function() {
                            if (typeof openCarkifelekModal === 'function') {
                                openCarkifelekModal();
                            }
                        }, wheelAutoDelayMs);
                    } else {
                        console.log('Çarkıfelek otomatik: limit aktif veya izin yok.', res);
                    }
                },
                error: function(err) {
                    console.error('Çarkıfelek otomatik: limit kontrolü başarısız', err);
                    if (!isRetry && !wheelAutoRetryDone) {
                        wheelAutoRetryDone = true;
                        setTimeout(function() {
                            doLimitAjax(true);
                        }, 1200);
                    }
                }
            });
        }

        doLimitAjax(false);
    }

    $(document).ready(function() {
        tryTriggerWheelAuto();
    });

    <?php } else { ?>
    console.log('Çarkıfelek: otomatik açılma kapalı.');
    <?php } ?>
})();
</script>
<?php } ?>


<!-- Social Proof Container (Classic Design) -->
<div id="social-proof-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: none;">
    <div style="background: rgba(255, 255, 255, 0.98); border-radius: 8px; padding: 12px 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-left: 5px solid var(--renk2); display: flex; align-items: center; gap: 15px; min-width: 250px; transition: all 0.5s ease; backdrop-filter: blur(5px); border: 1px solid #eee;">
        <div style="background: var(--renk2); color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
            <i class="fa fa-shopping-cart"></i>
        </div>
        <div style="line-height: 1.4;">
            <div style="font-size: 12px; font-weight: 800; color: #333;" id="sp-ad">...</div>
            <div style="font-size: 11px; color: #666;"><span id="sp-il">...</span>, <span id="sp-sure">...</span> sipariş verdi.</div>
        </div>
    </div>
</div>

<script>
  $(document).ready(function() {
    // 1. FOMO Countdown Logic
    function updateCountdown() {
        var now = new Date();
        var targetTimeString = "<?php echo $settingsprint['ayar_fomo_saat'] ?? '16:00'; ?>";
        if (!targetTimeString || !targetTimeString.includes(":")) targetTimeString = "16:00";
        
        var targetTime = targetTimeString.split(":");
        var target = new Date();
        var tHours = parseInt(targetTime[0]) || 16;
        var tMins = parseInt(targetTime[1]) || 0;
        
        target.setHours(tHours, tMins, 0);

        if (now > target) {
            target.setDate(target.getDate() + 1);
        }

        var diff = target - now;
        var hours = Math.floor(diff / (1000 * 60 * 60));
        var mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        var secs = Math.floor((diff % (1000 * 60)) / 1000);

        var timeString = (hours < 10 ? "0" : "") + hours + ":" +
                         (mins < 10 ? "0" : "") + mins + ":" +
                         (secs < 10 ? "0" : "") + secs;
        
        $("#fomo-countdown").text(timeString);

        // Dinamik Metin Güncelleme
        var fomoText = $("#fomo-text");
        var originalTarget = new Date();
        originalTarget.setHours(tHours, tMins, 0);

        if (now < originalTarget) {
            fomoText.html('Bugün saat <span class="fomo-bar__time">' + targetTimeString.replace(/</g, '&lt;') + '</span>\'a kadar verirseniz <span class="fomo-bar__emph">BUGÜN KARGODA!</span>');
        } else {
            fomoText.html('Şu an verilen siparişler <span class="fomo-bar__emph">YARIN KARGODA!</span>');
        }
    }
    if ($("#fomo-countdown").length) {
        setInterval(updateCountdown, 1000);
        updateCountdown();
    }

    // 2. Abandoned Cart Logic
    var yarimKalanTimer;
    $("input[name='siparis_ad'], input[name='siparis_tel'], .product-item").on("blur change input click", function() {
        clearTimeout(yarimKalanTimer);
        yarimKalanTimer = setTimeout(function() {
            var ad = $("input[name='siparis_ad']").val();
            var tel = $("input[name='siparis_tel']").val();
            var urunData = $("#urun").val() ? $("#urun").val().split('|') : ["", ""];
            var urun = urunData[0] || "";
            var fiyat = urunData[1] || "";

            if (ad.length > 2 || tel.length > 3) { 
                // Seçilen seçenekleri de yakala
                var secenekler = [];
                $("#urun_secenek_alani select").each(function() {
                    var val = $(this).val();
                    if (val) {
                        secenekler.push(val.split('|')[0]); // Sadece isim kısmını al
                    }
                });
                
                var tamUrun = urun + (secenekler.length > 0 ? " (" + secenekler.join(", ") + ")" : "");

                $.ajax({
                    type: "POST",
                    url: "js/ajax/yarimKalanKaydet.php",
                    data: { ad: ad, tel: tel, urun: tamUrun, fiyat: fiyat }
                });
            }
        }, 2000); // 2 saniye hareketsizlikten sonra kaydet
    });

  });
</script>



<!-- YUKARI ÇIK BUTONU -->
<?php 
// Veritabanı Ayar Kontrolü
$yukari_cik_on = isset($settingsprint['ayar_yukari_cik_on']) ? $settingsprint['ayar_yukari_cik_on'] : 1; 

// Eğer aktifse (1) kodları bas
if ($yukari_cik_on == 1) { 
?>
<!-- Buton HTML -->
<div id="backToTopCustom" onclick="scrollToTop()" role="button" aria-label="Yukarı Çık" title="Yukarı Çık">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 19V5M5 12l7-7 7 7"/>
    </svg>
</div>

<!-- Buton Tasarımı (CSS) -->
<style>
#backToTopCustom {
    /* Konumlandırma */
    position: fixed;
    bottom: 28px;
    right: 24px;
    z-index: 99999;
    
    /* Boyut ve Şekil */
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 48px !important;
    height: 48px !important;
    border-radius: 50% !important;
    padding: 0 !important;
    margin: 0 !important;
    
    /* Renk ve Tasarım */
    background: linear-gradient(45deg, #2575fc, #6a11cb) !important;
    color: #fff !important;
    cursor: pointer;
    border: none !important;
    
    /* Gölge */
    box-shadow: 0 4px 15px rgba(37, 117, 252, 0.4) !important;
    
    /* Geçişler */
    opacity: 0 !important; /* Force hidden initially */
    visibility: hidden !important; /* Force hidden initially */
    transform: translateY(20px) scale(0.9);
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

#backToTopCustom svg {
    display: block !important;
    width: 22px !important;
    height: 22px !important;
    margin: 0 auto !important;
    padding: 0 !important;
    position: static !important;
    float: none !important;
    transform: none !important;
}

/* Hover Efekti */
#backToTopCustom:hover {
    transform: translateY(-5px) scale(1.1);
    box-shadow: 0 8px 25px rgba(37, 117, 252, 0.6) !important;
    background: linear-gradient(45deg, #6a11cb, #2575fc) !important;
}

/* Aktif Durum */
#backToTopCustom.show {
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateY(0) scale(1);
}

/* Mobil Uyumluluk */
@media (max-width: 768px) {
    #backToTopCustom {
        bottom: 22px;
        right: 14px;
        width: 40px !important;
        height: 40px !important;
    }
    #backToTopCustom svg {
        width: 18px !important;
        height: 18px !important;
    }
}
</style>

<!-- Buton İşlevi (Bağımsız JS - Body Scroll Destekli) -->
<script>
// Çakışmayı önlemek için Theme fonksiyonunu override et
if (typeof INSPIRO !== 'undefined' && INSPIRO.core) {
    INSPIRO.core.goToTop = function() {  };
}

// Scroll olayını dinle (Hem window hem body için)
function toggleScrollBtn() {
    var scrollBtn = document.getElementById('backToTopCustom');
    if (!scrollBtn) return;
    
    // Farklı tarayıcılar ve temalar için scroll pozisyonunu al
    // Bazı temalarda body overflow:auto olduğu için window.scrollY 0 döner
    var st = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    
    // 300px aşağı inildiğinde göster
    if (st > 300) {
        scrollBtn.classList.add('show');
    } else {
        scrollBtn.classList.remove('show');
    }
}

// Window ve Body üzerinde scrollu dinle
window.addEventListener('scroll', toggleScrollBtn, {passive: true});
document.body.addEventListener('scroll', toggleScrollBtn, {passive: true});
// Bazı mobil tarayıcılar için
document.addEventListener('scroll', toggleScrollBtn, {passive: true});

// Tıklama işlevi
function scrollToTop() {
    // Tüm olası scroll alanlarını yukarı taşı
    window.scrollTo({ top: 0, behavior: 'smooth' });
    document.body.scrollTo({ top: 0, behavior: 'smooth' });
    document.documentElement.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
<?php } ?>




<script>
    $(document).ready(function() {
    // 3. Social Proof Logic
    var socialProofData = [];
    var spIndex = 0;
    var bildirimOn = <?php echo $settingsprint['ayar_bildirim_on'] ?? 1; ?>;

    function showSocialProof() {
        if (socialProofData.length === 0 || bildirimOn == 0) return;
        
        var data = socialProofData[spIndex];
        $("#sp-ad").text(data.ad);
        $("#sp-il").text(data.il);
        $("#sp-sure").text(data.sure);
        
        $("#social-proof-container").fadeIn().delay(5000).fadeOut(function() {
            spIndex = (spIndex + 1) % socialProofData.length;
            setTimeout(showSocialProof, 10000); // 10 saniye sonra tekrarla
        });
    }

    if (bildirimOn == 1) {
        $.getJSON("js/ajax/canliSatis.php", function(data) {
            if (data && data.length > 0) {
                socialProofData = data;
                setTimeout(showSocialProof, 15000); // İlk bildirim 15 saniye sonra (User request: geç çıksın)
            }
        });
    }

    // 4. IP based City Prediction
    <?php if (isset($settingsprint['ayar_ip_sehir_on']) && $settingsprint['ayar_ip_sehir_on'] == 1) { ?>
    function trNormalize(str) {
        if (!str) return "";
        return str.toString()
            .replace(/İ/g, "i").replace(/I/g, "i").replace(/ı/g, "i").replace(/i/g, "i") // Hepsi 'i' ye dönsün
            .replace(/ş/g, "s").replace(/ğ/g, "g").replace(/ü/g, "u").replace(/ö/g, "o").replace(/ç/g, "c")
            .replace(/Ş/g, "s").replace(/Ğ/g, "g").replace(/Ü/g, "u").replace(/Ö/g, "o").replace(/Ç/g, "c")
            .toLowerCase()
            .trim();
    }
    function cityKey(str) {
        var s = trNormalize(str);
        s = s
            .replace(/\b(il|ili|province|provinsi|region|bolgesi|sehir|city|merkez)\b/g, '')
            .replace(/\s+/g, ' ')
            .trim();
        return s;
    }

    $.getJSON("js/ajax/getCityFromIP.php", function(res) {
        if (res.status === 'success') {
            var predictedCity = cityKey(res.city);
            if (!predictedCity || predictedCity === "sehir seciniz") return;
            
            // Önce tam eşleşme, bulunamazsa esnek eşleşme dene.
            var matched = false;
            $("#il option").each(function() {
                var optionVal = cityKey($(this).val());
                if (optionVal !== "" && optionVal === predictedCity) {
                    $(this).prop('selected', true).trigger('change');
                    matched = true;
                    return false; // Bulundu, döngüden çık
                }
            });
            if (!matched) {
                $("#il option").each(function() {
                    var optionVal = cityKey($(this).val());
                    if (!optionVal) return;
                    if (optionVal.indexOf(predictedCity) !== -1 || predictedCity.indexOf(optionVal) !== -1) {
                        $(this).prop('selected', true).trigger('change');
                        matched = true;
                        return false;
                    }
                });
            }
        }
    });
    <?php } ?>

    // Çarkıfelek fonksiyonları footer.php'de global olarak tanımlı

    $("#myform").submit(function(e) {
      // Ürün Seçenekleri Kontrolü
      var error = false;
      $("#urun_secenek_alani select[required]").each(function() {
        if ($(this).val() === "") {
          alert("Lütfen tüm ürün seçeneklerini seçiniz.");
          $(this).focus();
          error = true;
          return false; // Loopdan çık
        }
      });

      if (error) {
        e.preventDefault();
        return false;
      }

      if (typeof yarimKalanTimer !== 'undefined') clearTimeout(yarimKalanTimer); 
      $("#ButonGizle").hide();
      $("#ButonGoster").show();
    });

    // Ödeme Yöntemi Scroll Fix
    $('input[name="odeme"]').on('change', function(e) {
        // Scroll zıplamasını engellemek için mevcut pozisyonu koruyalım
        // veya gereksiz scroll yapan (varsa) diğer listenerları baskılayalım
        // e.preventDefault(); // Radio değişimini bozabilir, dikkat.
        
        // Sadece yumuşakça bulunduğu yere odaklansın, sayfa başına atmasın
        var container = $(this).closest('.order-radio');
        if(container.length) {
            // Hafif bir highlight efekti verelim
            $('.order-radio').removeClass('active-payment');
            container.addClass('active-payment');
        }
    });
    
  });
</script>

<script>
var ORDER_URUN_SECENEK_ON = <?php echo (int)($settingsprint['ayar_urun_secenek_on'] ?? 1); ?>;
var productOptionsXhr = null;
/** Ürün seçenekleri yumuşak kaydırma (ms) */
var SIPARIS_URUN_SECENEK_SCROLL_DURATION_MS = 900;
/** Galeri / alt görsel / sticky “Sipariş ver” → form alanına kaydırma süresi (ms) */
var SIPARIS_SCROLL_TO_ORDER_DURATION_MS = 1200;
/** Tıklamadan sonra kaydırmaya kadar en az bu kadar (ms); AJAX gecikmesi varsa otomatik eklenir */
var SIPARIS_PAKET_TIKLAMA_KAYDIRMA_BEKLE_MS = 550;
var siparisPaketTiklamaZamani = 0;
var siparisUrunSecenekScrollTimer = null;
var siparisPendingOdemeScrollUntil = 0;
var siparisLastSelectionAt = 0;
var siparisOdemeScrollTimers = [];
var siparisPointerStartX = 0;
var siparisPointerStartY = 0;
var siparisPointerStartAt = 0;
var siparisPointerMoved = false;
var siparisSuppressClickUntil = 0;
var E_TICARET_ORDER_PAGE_MODE = true;

function siparisScrollBehavior() {
    return (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) ? 'auto' : 'smooth';
}

/** Paket değişince Ürün Seçenekleri alanına kaydırma (anında veya SIPARIS_URUN_SECENEK_SCROLL_DURATION_MS) */
function scrollToUrunSecenekleriStickyOffset() {
    if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
        return 60 + 12 + 15;
    }
    return 75 + 12 + 10;
}
function scrollToUrunSecenekleri(opts) {
    opts = opts || {};
    var instant = !!opts.instant;
    var el = document.getElementById('urun_secenek_alani');
    if (!el || el.offsetHeight < 1) return;
    if (siparisScrollBehavior() === 'auto') {
        el.scrollIntoView({ block: 'start', behavior: 'auto' });
        return;
    }
    var rect = el.getBoundingClientRect();
    var stickyTop = scrollToUrunSecenekleriStickyOffset();
    var targetY = window.pageYOffset + rect.top - stickyTop;
    var startY = window.pageYOffset;
    var dist = targetY - startY;
    if (Math.abs(dist) < 48) return;
    if (instant) {
        window.scrollTo(0, Math.max(0, targetY));
        return;
    }
    var duration = (typeof SIPARIS_URUN_SECENEK_SCROLL_DURATION_MS === 'number' && SIPARIS_URUN_SECENEK_SCROLL_DURATION_MS > 0)
        ? SIPARIS_URUN_SECENEK_SCROLL_DURATION_MS
        : 380;
    var t0 = null;
    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
    function step(now) {
        if (t0 === null) t0 = now;
        var p = Math.min((now - t0) / duration, 1);
        window.scrollTo(0, startY + dist * easeOutCubic(p));
        if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}
function scrollToOdemeYontemiStickyOffset() {
    if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
        return 60 + 12;
    }
    return 75 + 12;
}
function scrollToOdemeYontemi(opts) {
    opts = opts || {};
    var instant = !!opts.instant;
    var el = document.getElementById('odeme-yontemi-label') || document.getElementById('odeme-yontemi-alani');
    if (!el) return;
    var rect = el.getBoundingClientRect();
    var stickyTop = scrollToOdemeYontemiStickyOffset();
    var targetY = window.pageYOffset + rect.top - stickyTop;
    var dist = targetY - window.pageYOffset;
    if (Math.abs(dist) < 20) return;
    var behavior = (instant || siparisScrollBehavior() === 'auto') ? 'auto' : 'smooth';
    window.scrollTo({ top: Math.max(0, targetY), behavior: behavior });
}
function scrollToOdemeYontemiReliable() {
    while (siparisOdemeScrollTimers.length) {
        clearTimeout(siparisOdemeScrollTimers.pop());
    }
    scrollToOdemeYontemi({ instant: false });
    // AJAX sonrası/yeniden akış sonrası hedef kayarsa hafif düzeltme.
    siparisOdemeScrollTimers.push(setTimeout(function() { scrollToOdemeYontemi({ instant: true }); }, 160));
    siparisOdemeScrollTimers.push(setTimeout(function() { scrollToOdemeYontemi({ instant: true }); }, 320));
}

/** #order-scroll-target ile aynı: sabit header altında kalsın (CSS scroll-margin ile uyumlu) */
function scrollToOrderStickyOffset() {
    if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
        return 60 + 12;
    }
    return 75 + 12;
}

function scrollToOrder(e) {
    if (e && e.preventDefault) e.preventDefault();
    if (e && e.stopPropagation) e.stopPropagation();
    var el = document.getElementById('order-scroll-target')
        || document.getElementById('fomo-timer-bar')
        || document.getElementById('siparis-formu-ana-div');
    if (!el) return;

    var rect = el.getBoundingClientRect();
    var stickyTop = scrollToOrderStickyOffset();
    var targetY = window.pageYOffset + rect.top - stickyTop;
    var dist = targetY - window.pageYOffset;
    if (Math.abs(dist) < 8) return;
    var behavior = (typeof siparisScrollBehavior === 'function' && siparisScrollBehavior() === 'auto')
        ? 'auto'
        : 'smooth';
    window.scrollTo({ top: Math.max(0, targetY), behavior: behavior });
}

$(document).on('click', 'a[href="#"]', function(e) {
    e.preventDefault();
});

function loadProductOptions(id) {
    if (ORDER_URUN_SECENEK_ON !== 1) {
        $("#urun_secenek_alani").empty().hide();
        return;
    }

    if (productOptionsXhr && productOptionsXhr.abort) {
        try { productOptionsXhr.abort(); } catch (err) {}
    }

    var trackingXhr = $.ajax({
        type: "POST",
        url: "<?php echo SITE_URL; ?>urun-secenek-getir.php",
        data: { urun_id: id },
        success: function(response) {
            var hasOptions = $(response).find('select').length > 0 || $('<div>').html(response).find('select').length > 0;
            if (hasOptions) {
                $("#urun_secenek_alani").html(response).show();
                if (typeof urunSecenekSyncSwatches === 'function') {
                    urunSecenekSyncSwatches($("#urun_secenek_alani"));
                }
            } else {
                clearTimeout(siparisUrunSecenekScrollTimer);
                siparisUrunSecenekScrollTimer = null;
                $("#urun_secenek_alani").empty().hide();
            }
        },
        error: function(jqXHR, textStatus) {
            if (textStatus === 'abort') return;
            clearTimeout(siparisUrunSecenekScrollTimer);
            siparisUrunSecenekScrollTimer = null;
            $("#urun_secenek_alani").empty().hide();
        },
        complete: function() {
            if (productOptionsXhr === trackingXhr) {
                productOptionsXhr = null;
            }
            if (Date.now() < siparisPendingOdemeScrollUntil) {
                scrollToOdemeYontemiReliable();
            }
        }
    });
    productOptionsXhr = trackingXhr;
}

function selectOrderProduct($el) {
    if (!$el || !$el.length) return;
    var id = $el.data('id');
    if (id === undefined || id === null) return;

    var nowTs = Date.now();
    // Çok kısa aralıkta aynı seçim tekrarını engelle.
    if (nowTs - siparisLastSelectionAt < 120) return;
    siparisLastSelectionAt = nowTs;

    var title = $el.data('value');
    var price = $el.data('fiyat');
    var isAlreadySelected = $el.hasClass('selected');

    if (!isAlreadySelected) {
        $('.product-item').removeClass('selected');
        $('.product-item .select').hide();
        $('.product-item .no-select').show();
        $el.addClass('selected');
        $el.find('.select').show();
        $el.find('.no-select').hide();
    }

    $('#urun').val(title + '|' + price).attr('data-id', id).trigger('change');
    $('.meta-order-product-sticky').text(title);
    updateOrderSelectionContext(title);
    fitStickyProductText();

    var cleanPrice = String(price).replace(/[^0-9]/g, '');
    $('.meta-order-total-sticky').text(new Intl.NumberFormat('tr-TR').format(cleanPrice) + ' TL');

    if (typeof applyWheelRewardToUI === 'function') applyWheelRewardToUI();

    if (!isAlreadySelected) {
        loadProductOptions(id);
    }
    if (E_TICARET_ORDER_PAGE_MODE === true) {
        var qp = new URLSearchParams();
        qp.set('product_id', String(id));
        window.location.href = "<?php echo SITE_URL; ?>order.php?" + qp.toString();
        return;
    }
    siparisPendingOdemeScrollUntil = Date.now() + 1400;
    setTimeout(function() {
        scrollToOdemeYontemiReliable();
    }, 80);
}

function fitStickyProductText() {
    var el = document.querySelector('.meta-order-product-sticky');
    if (!el) return;
    var max = window.matchMedia && window.matchMedia('(max-width: 420px)').matches ? 14 : 16;
    var min = 10;
    el.style.fontSize = max + 'px';
    while (el.scrollWidth > el.clientWidth && max > min) {
        max -= 1;
        el.style.fontSize = max + 'px';
    }
}

function updateOrderSelectionContext(title) {
    var text = (title || '').toString().trim();
    if (!text) text = '-';
    $('#selected-product-payment').text('(Seçilen: ' + text + ')');
    var $confirm = $('#selected-product-confirm');
    if ($confirm.length) {
        $confirm.text('Siparişe eklenecek ürün: ' + text);
        $confirm.removeClass('flash');
        // Reflow ile animasyonu yeniden tetikle
        void $confirm[0].offsetWidth;
        $confirm.addClass('flash');
    }
}

$(document).on('pointerdown', '.product-item', function(e) {
    siparisPointerStartX = e.clientX || 0;
    siparisPointerStartY = e.clientY || 0;
    siparisPointerStartAt = Date.now();
    siparisPointerMoved = false;
});
$(document).on('pointermove', '.product-item', function(e) {
    if (Math.abs((e.clientX || 0) - siparisPointerStartX) > 10 || Math.abs((e.clientY || 0) - siparisPointerStartY) > 10) {
        siparisPointerMoved = true;
    }
});
$(document).on('pointercancel', '.product-item', function() {
    siparisPointerMoved = true;
});
$(document).on('pointerup', '.product-item', function(e) {
    var elapsed = Date.now() - siparisPointerStartAt;
    if (siparisPointerMoved || elapsed > 450) {
        // Kaydırma sonrası oluşabilecek sentetik click'i kısa süre bastır.
        siparisSuppressClickUntil = Date.now() + 500;
        return;
    }
    if (e.pointerType === 'touch') {
        e.preventDefault();
        siparisSuppressClickUntil = Date.now() + 500;
    }
    selectOrderProduct($(this));
});
$(document).on('click', '.product-item', function(e) {
    if (Date.now() < siparisSuppressClickUntil) {
        e.preventDefault();
        return;
    }
    selectOrderProduct($(this));
});

$(document).on('keydown', '.product-item', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        selectOrderProduct($(this));
    }
});

$(document).ready(function() {
    var selectedProduct = $('.product-item.selected').first();
    if (selectedProduct.length === 0) {
        selectedProduct = $('.product-item').first();
        selectedProduct.addClass('selected');
    }
    if (selectedProduct.length > 0) {
        var title = selectedProduct.data('value');
        var price = selectedProduct.data('fiyat');
        var id    = selectedProduct.data('id');
        $('#urun').val(title + '|' + price).attr('data-id', id);
        $('.meta-order-product-sticky').text(title);
        updateOrderSelectionContext(title);
        fitStickyProductText();
        var cleanInitPrice = String(price).replace(/[^0-9]/g, '');
        $('.meta-order-total-sticky').text(new Intl.NumberFormat('tr-TR').format(cleanInitPrice) + ' TL');
        if ($('#urun_secenek_alani').find('select').length === 0) {
            loadProductOptions(id);
        }
    }
    fitStickyProductText();
    $(window).on('resize orientationchange', fitStickyProductText);
});
</script>

<script>
// Meta Pixel — InitiateCheckout (Sipariş formu gönderildiğinde)
$(document).on('submit', '#myform', function() {
    var selectedItem = $('.product-item.selected').first();
    var price    = selectedItem.length ? selectedItem.data('fiyat') : 0;
    var urunAdi  = selectedItem.length ? (selectedItem.data('value') || '') : '';
    var numPrice = parseFloat(String(price).replace(/[^0-9.]/g, '')) || 0;
    var eventId  = 'init_' + new Date().getTime();

    if (typeof fbq === 'function') {
        fbq('track', 'InitiateCheckout', {
            value: numPrice,
            currency: 'TRY',
            content_name: urunAdi,
            content_type: 'product',
            num_items: 1
        }, { eventID: eventId });
    }

    // TikTok Pixel — InitiateCheckout
    if (typeof ttq === 'object') {
        ttq.track('InitiateCheckout', {
            content_name: urunAdi,
            content_type: 'product',
            value: numPrice,
            currency: 'TRY'
        });
    }

    // Google Tracking — begin_checkout
    if (typeof gtag === 'function') {
        gtag('event', 'begin_checkout', {
            currency: 'TRY',
            value: numPrice,
            items: [{
                item_id: urunAdi,
                item_name: urunAdi,
                item_category: 'Product',
                price: numPrice,
                quantity: 1
            }]
        });
    }
});
</script>


<!-- NEDEN BİZ? BÖLÜMÜ -->
<?php if (!isset($settingsprint['ayar_nedenbiz_on']) || $settingsprint['ayar_nedenbiz_on'] == 1) { 
    $nedenBizMargin = (isset($settingsprint['ayar_altgorsel_on']) && $settingsprint['ayar_altgorsel_on'] == 1) ? '60px' : '0px';
?>
<div style="background: #f8fafc; padding: 50px 0; border-top: 1px solid #e2e8f0; margin-top: <?php echo $nedenBizMargin; ?>;">
    <div class="container" style="max-width: 1000px; padding: 0 20px;">
        <div style="display: flex; flex-direction: column; gap: 20px; max-width: 560px; margin: 0 auto;">
            <div style="width: 100%; text-align: center; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <i class="fa fa-flash" style="font-size: 32px; color: #f59e0b; margin-bottom: 15px;"></i>
                <h4 style="font-weight: 800; color: #1e293b; margin-bottom: 8px;">Aynı Gün Kargo</h4>
                <p style="color: #64748b; font-size: 0.9rem;">Saat 16:00'ya kadar verilen siparişler aynı gün kargoda.</p>
            </div>
            <div style="width: 100%; text-align: center; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <i class="fa fa-check-circle" style="font-size: 32px; color: #22c55e; margin-bottom: 15px;"></i>
                <h4 style="font-weight: 800; color: #1e293b; margin-bottom: 8px;">Orijinal Ürün</h4>
                <p style="color: #64748b; font-size: 0.9rem;">Tüm ürünlerimiz %100 orijinal ve faturalı olarak gönderilir.</p>
            </div>
            <div style="width: 100%; text-align: center; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <i class="fa fa-headphones" style="font-size: 32px; color: #3b82f6; margin-bottom: 15px;"></i>
                <h4 style="font-weight: 800; color: #1e293b; margin-bottom: 8px;">7/24 Destek</h4>
                <p style="color: #64748b; font-size: 0.9rem;">Satış öncesi ve sonrası sorularınız için yanınızdayız.</p>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<!-- FOOTER (ALT BİLGİ) -->
<?php if (!isset($settingsprint['ayar_footer_on']) || $settingsprint['ayar_footer_on'] == 1) { ?>
<footer style="background: #1e293b; padding: 30px 0; color: #94a3b8; text-align: center; font-size: 0.9rem;">
    <div class="container">
        <div style="margin-bottom: 15px;">
            <span style="color: #cbd5e1; font-size: 14px; font-weight: 500;">
                <i class="fa fa-lock" style="color: #22c55e; margin-right: 5px;"></i>
                Bu sitede yapılan alışverişler 256-Bit SSL sertifikası ve 3D Secure güvenli ödeme sistemi ile korunmaktadır.
            </span>
        </div>
        <div>
            &copy; 2026 Tüm Hakları Saklıdır. Bu site güvenli ödeme altyapısı kullanmaktadır.
        </div>
    </div>
</footer>
<?php } ?>







<!-- RESET: Sticky Mobile Order Bar (Fixed Bottom) -->
<!-- RESET: Sticky Mobile Order Bar (Fixed Bottom) -->
<?php if (!isset($settingsprint['ayar_siparis_bar']) || $settingsprint['ayar_siparis_bar'] == 1) { ?>
<div id="sticky-mobile-order" style="display:none; position: fixed; bottom: 0 !important; left: 0; right: 0; background: #fff; padding: 8px 12px; box-shadow: 0 -8px 24px rgba(15,23,42,0.15); z-index: 2147483647 !important; align-items: center; justify-content: space-between; border-top: 1px solid #d1fae5;">
    <div class="sticky-mobile-order__info">
        <span class="sticky-mobile-order__label"><i class="fa fa-shopping-basket" aria-hidden="true"></i> Sepet</span>
        <span class="sticky-mobile-order__name meta-order-product-sticky"><?php
            echo htmlspecialchars(strip_tags($urun1yaz['urun_baslik'] ?? ''), ENT_QUOTES, 'UTF-8');
        ?></span>
    </div>
    <a href="javascript:void(0);" onclick="scrollToOrder(event); return false;" style="background: linear-gradient(135deg,#16a34a 0%,#22c55e 100%); color: #fff; text-align: center; padding: 8px 12px; border-radius: 8px; font-weight: 800; text-decoration: none; box-shadow: 0 4px 10px rgba(34, 197, 94, 0.32); font-size: 12px; display: flex; align-items: center; gap: 5px; flex: 0 0 auto; justify-content: center; white-space: nowrap; letter-spacing: .01em;">
        <i class="fa fa-shopping-cart"></i> HEMEN SİPARİŞ VER
    </a>
</div>
<?php } ?>

<style>
    /* Default Desktop State: Hidden */
    #sticky-mobile-order {
        display: none;
    }
    .sticky-mobile-order__info {
        display: flex;
        flex-direction: column;
        min-width: 0;
        flex: 1 1 auto;
        padding-right: 10px;
    }
    .sticky-mobile-order__label {
        font-size: 10px;
        color: #0f766e;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .sticky-mobile-order__name {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: -0.01em;
    }
    
    @media (max-width: 768px) {
        #sticky-mobile-order {
            display: flex !important; /* Force Visible on Mobile */
            height: 70px;
            bottom: 0 !important; /* Force Bottom */
        } 
        .sticky-mobile-order__name {
            font-size: 14px;
        }
        /* Floating Buttons Reset - Above the bar */
        /* Quick Actions (Left) & Scroll Top (Right) */
        .fab, .floating-btn, .fixed-bottom-left, .fixed-bottom-right, .back-to-top, #scroll-top, #backToTopCustom, #js-scroll-top {
            bottom: <?php echo (!isset($settingsprint['ayar_siparis_bar']) || $settingsprint['ayar_siparis_bar'] == 1) ? '90px' : '20px'; ?> !important; 
            z-index: 999999 !important; /* Lowered from max to allow modals on top */
        }
        /* Sol hızlı aksiyonlar: üstte kalıp alttaki scrollToOrder linklerine dokunmanın gitmemesi (mobil hit-test) */
        #quick-actions-container {
            bottom: <?php echo (!isset($settingsprint['ayar_siparis_bar']) || $settingsprint['ayar_siparis_bar'] == 1) ? '90px' : '20px'; ?> !important;
            z-index: 2147483646 !important;
            touch-action: manipulation !important;
            -webkit-transform: translateZ(0) !important;
            transform: translateZ(0) !important;
            pointer-events: auto !important;
        }
        @media (max-width: 420px) {
            .sticky-mobile-order__name {
                font-size: 13px;
            }
            #sticky-mobile-order a {
                padding: 7px 10px !important;
                font-size: 11px !important;
            }
        }
    }
</style>

<?php
require_once __DIR__ . '/include/legal-pages.php';
legal_pages_render_footer($db, $settingsprint, $whatsappprint ?? null);
?>

</body>
</html>
