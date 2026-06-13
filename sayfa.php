<?php 
include 'include/head.php';
// Auto-detect protocol for SEO meta tags
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http';
$current_url = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
$sayfasor=$db->prepare("SELECT * from sayfalar where sayfa_slug=:slug AND sayfa_durum=1");
$sayfasor->execute(array('slug' => $_GET['slug']));
$sayfacek=$sayfasor->fetch(PDO::FETCH_ASSOC);

if (!$sayfacek) {
    header("Location: " . SITE_URL . "index.php");
    exit;
}

// Pasif Sayfa Kontrolü
if ($sayfacek['sayfa_durum'] == 0) {
    header("Location: " . SITE_URL . "index.php");
    exit;
}

$__og_base = rtrim($settingsprint['ayar_siteurl'] ?? '', '/');
$sayfa_share_image_url = '';
if (!empty(trim((string)($sayfacek['sayfa_og_image'] ?? '')))) {
    $raw_og = trim((string) $sayfacek['sayfa_og_image']);
    if (preg_match('#^https?://#i', $raw_og)) {
        $sayfa_share_image_url = $raw_og;
    } else {
        $sayfa_share_image_url = $__og_base . '/' . ltrim($raw_og, '/');
    }
} elseif (!empty(trim((string)($sayfacek['sayfa_resim'] ?? '')))) {
    $sayfa_share_image_url = $__og_base . '/' . ltrim((string) $sayfacek['sayfa_resim'], '/');
} elseif (is_file(SITE_ROOT . '/xnull/assets/img/genel/og-share.jpg')) {
    $sayfa_share_image_url = $__og_base . '/xnull/assets/img/genel/og-share.jpg';
} elseif (!empty(trim((string)($settingsprint['ayar_logo'] ?? '')))) {
    $sayfa_share_image_url = $__og_base . '/xnull/' . ltrim((string) $settingsprint['ayar_logo'], '/');
} else {
    $sayfa_share_image_url = $__og_base . '/xnull/assets/img/genel/og-share.jpg';
}
?>
<title><?php echo $sayfacek['sayfa_baslik']; ?> - <?php echo $settingsprint['ayar_title']; ?></title>
<meta name="description" content="<?php echo $sayfacek['sayfa_descr'] ?>">
<meta name="keywords" content="<?php echo $sayfacek['sayfa_keyword'] ?>">
<meta name="robots" content="<?php echo !empty($sayfacek['sayfa_robots']) ? $sayfacek['sayfa_robots'] : 'index, follow'; ?>">
<?php if (!empty($sayfacek['sayfa_canonical'])) { ?>
<link rel="canonical" href="<?php echo $sayfacek['sayfa_canonical']; ?>" />
<?php } ?>
<?php if (!empty($sayfacek['sayfa_author'])) { ?>
<meta name="author" content="<?php echo $sayfacek['sayfa_author']; ?>">
<?php } ?>

<!-- Open Graph Meta Tags -->
<meta property="og:title" content="<?php echo !empty($sayfacek['sayfa_og_title']) ? $sayfacek['sayfa_og_title'] : $sayfacek['sayfa_title']; ?>" />
<meta property="og:description" content="<?php echo !empty($sayfacek['sayfa_og_description']) ? $sayfacek['sayfa_og_description'] : $sayfacek['sayfa_descr']; ?>" />
<meta property="og:type" content="<?php echo ($sayfacek['sayfa_schema_type'] == 'Article') ? 'article' : 'website'; ?>" />
<meta property="og:url" content="<?php echo htmlspecialchars($current_url, ENT_QUOTES, 'UTF-8'); ?>" />
<meta property="og:image" content="<?php echo htmlspecialchars($sayfa_share_image_url, ENT_QUOTES, 'UTF-8'); ?>" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:site_name" content="<?php echo !empty($settingsprint['ayar_title']) ? $settingsprint['ayar_title'] : 'Site'; ?>" />
<meta property="og:locale" content="tr_TR" />

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo !empty($sayfacek['sayfa_og_title']) ? $sayfacek['sayfa_og_title'] : $sayfacek['sayfa_title']; ?>">
<meta name="twitter:description" content="<?php echo !empty($sayfacek['sayfa_og_description']) ? $sayfacek['sayfa_og_description'] : $sayfacek['sayfa_descr']; ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($sayfa_share_image_url, ENT_QUOTES, 'UTF-8'); ?>">

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "<?php echo !empty($sayfacek['sayfa_schema_type']) ? $sayfacek['sayfa_schema_type'] : 'WebPage'; ?>",
  "name": "<?php echo $sayfacek['sayfa_title']; ?>",
  "description": "<?php echo $sayfacek['sayfa_descr']; ?>",
  "url": "<?php echo $current_url; ?>",
  "image": <?php echo json_encode($sayfa_share_image_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
  <?php if (!empty($sayfacek['sayfa_tarih'])) { ?>
  "datePublished": "<?php echo date('c', strtotime($sayfacek['sayfa_tarih'])); ?>",
  "dateModified": "<?php echo date('c', strtotime($sayfacek['sayfa_tarih'])); ?>",
  <?php } ?>
  <?php if (!empty($sayfacek['sayfa_author'])) { ?>
  "author": {
      "@type": "Person",
      "name": "<?php echo $sayfacek['sayfa_author']; ?>"
  },
  <?php } ?>
  <?php if (!empty($settingsprint['ayar_title']) || !empty($settingsprint['ayar_logo'])) { ?>
  "publisher": {
      "@type": "Organization",
      "name": "<?php echo !empty($settingsprint['ayar_title']) ? $settingsprint['ayar_title'] : 'Publisher'; ?>",
      <?php if (!empty($settingsprint['ayar_logo'])) { ?>
      "logo": {
          "@type": "ImageObject",
          "url": "<?php echo $settingsprint['ayar_logo']; ?>"
      }
      <?php } ?>
  },
  <?php } ?>
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "<?php echo $current_url; ?>"
  }
}
</script>

<!-- Breadcrumb Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [{
    "@type": "ListItem",
    "position": 1,
    "name": "Anasayfa",
    "item": "<?php echo SITE_URL; ?>"
  }
  <?php 
  $pos = 2;
  if ($sayfacek['sayfa_id'] > 0) {
      $ustsor = $db->prepare("SELECT sayfa_baslik, sayfa_slug FROM sayfalar WHERE id=:id");
      $ustsor->execute(['id' => $sayfacek['sayfa_id']]);
      $ustcek = $ustsor->fetch(PDO::FETCH_ASSOC);
      if ($ustcek) {
  ?>
  ,{
    "@type": "ListItem",
    "position": <?php echo $pos; ?>,
    "name": "<?php echo $ustcek['sayfa_baslik']; ?>",
    "item": "<?php echo SITE_URL . 'sayfa/' . $ustcek['sayfa_slug']; ?>"
  }
  <?php
      $pos++;
      }
  }
  ?>
  ,{
    "@type": "ListItem",
    "position": <?php echo $pos; ?>,
    "name": "<?php echo $sayfacek['sayfa_baslik']; ?>",
    "item": "<?php echo $current_url; ?>"
  }]
}
</script>
<style>
    .entry-content img { max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 20px; }
    .page-featured-image { width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .share-buttons { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
    .btn-share { margin-right: 5px; margin-bottom: 5px; color: #fff; border: none; transition: all 0.3s; }
    .btn-share:hover { opacity: 0.9; color: #fff; transform: translateY(-2px); }
    .btn-facebook { background-color: #3b5998; }
    .btn-twitter { background-color: #1da1f2; }
    .btn-whatsapp { background-color: #25d366; }
    .btn-linkedin { background-color: #0077b5; }
    .btn-copy { background-color: #6c757d; }

    /* Mobile Header Fix */
    @media (max-width: 768px) {
        #page-title {
            padding: 100px 0 40px 0 !important;
        }
        #page-title h1 {
            font-size: 1.8rem !important;
        }
    }
</style>
</head>
<body class="wide no-page-loader">
    <div id="wrapper" class="clearfix">
        <?php include 'include/menu.php'; ?>
        
        <section id="page-title" style="background: var(--renk1); padding: 130px 0 80px 0;">
            <div class="container clearfix text-center">
                <?php
                // Content Freshness Badge
                if (!empty($sayfacek['sayfa_tarih'])) {
                    $days_old = floor((time() - strtotime($sayfacek['sayfa_tarih'])) / 86400);
                    if ($days_old < 7) {
                        echo '<span class="badge" style="background: #10b981; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; margin-bottom: 10px; display: inline-block;"><i class="fa fa-star"></i> YENİ</span>';
                    } elseif ($days_old < 30) {
                        echo '<span class="badge" style="background: #3b82f6; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; margin-bottom: 10px; display: inline-block;"><i class="fa fa-clock-o"></i> GÜNCEL</span>';
                    }
                }
                ?>
                <h1 style="color: #fff; font-weight: 800; font-size: 2.8rem; margin-bottom: 15px;"><?php echo $sayfacek['sayfa_baslik']; ?></h1>
                <ol class="breadcrumb" style="background: transparent; justify-content: center; margin: 0; padding: 0;">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>" style="color: #eee;">Anasayfa</a></li>
                    <?php 
                    // Breadcrumb hierarchy
                    if ($sayfacek['sayfa_id'] > 0) {
                        $ustsor = $db->prepare("SELECT sayfa_baslik, sayfa_slug FROM sayfalar WHERE id=:id");
                        $ustsor->execute(['id' => $sayfacek['sayfa_id']]);
                        $ustcek = $ustsor->fetch(PDO::FETCH_ASSOC);
                        if ($ustcek) {
                            echo '<li class="breadcrumb-item"><a href="'.SITE_URL.'sayfa/'.$ustcek['sayfa_slug'].'" style="color: #eee;">'.$ustcek['sayfa_baslik'].'</a></li>';
                        }
                    }
                    ?>
                    <li class="breadcrumb-item active" aria-current="page" style="color: #eee;"><?php echo $sayfacek['sayfa_baslik']; ?></li>
                </ol>
            </div>
            <div class="container text-center" style="margin-top: 20px;">
                <a href="<?php echo SITE_URL; ?>" class="btn-breadcrumb-cta pulse-button">
                    <i class="fa fa-shopping-basket"></i> Hemen Satın Al
                </a>
            </div>
            <style>
                .btn-breadcrumb-cta {
                    display: inline-block;
                    background: linear-gradient(135deg, #FFD700 0%, #FF8C00 100%);
                    border: none;
                    color: #000 !important;
                    padding: 10px 30px;
                    border-radius: 30px;
                    font-size: 1rem;
                    font-weight: 700;
                    transition: all 0.3s ease;
                    text-decoration: none;
                    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .btn-breadcrumb-cta:hover {
                    transform: scale(1.08);
                    box-shadow: 0 6px 20px rgba(255, 215, 0, 0.6);
                    background: linear-gradient(135deg, #FFEA00 0%, #FFA500 100%);
                }
                @keyframes pulse-orange {
                    0% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.7); }
                    70% { box-shadow: 0 0 0 15px rgba(255, 215, 0, 0); }
                    100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0); }
                }
                .pulse-button {
                    animation: pulse-orange 2s infinite;
                }
            </style>
        </section>

        <section id="content" style="background: #f8fafc; padding: 40px 0;">
            <div class="content-wrap">
                <div class="container clearfix" style="max-width: <?php echo isset($settingsprint['ayar_harita']) ? $settingsprint['ayar_harita'] : 1200; ?>px !important; margin: 0 auto;">
                    <div class="row justify-content-center">
                        <div class="col-lg-12">
                            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                                <?php if (!empty($sayfacek['sayfa_resim'])) { ?>
                                    <img src="<?php echo rtrim(SITE_URL, '/') . '/' . ltrim($sayfacek['sayfa_resim'], '/'); ?>" alt="<?php echo $sayfacek['sayfa_baslik']; ?>" class="img-fluid rounded shadow-sm" loading="lazy">
                                <?php } ?>
                                
                                <div class="card-body p-4 p-md-5 bg-white">
                                    <?php
                                    // Reading Time & Last Modified
                                    $word_count = str_word_count(strip_tags($sayfacek['sayfa_icerik']));
                                    $reading_time = max(1, ceil($word_count / 200)); // 200 kelime/dakika
                                    ?>
                                    <div class="page-meta" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 2px solid #f1f5f9; margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
                                        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                            <span style="color: #64748b; font-size: 0.9rem;">
                                                <i class="fa fa-clock-o" style="color: #3b82f6;"></i> 
                                                <strong><?php echo $reading_time; ?> dk</strong> okuma
                                            </span>
                                            <?php if (!empty($sayfacek['sayfa_tarih'])) { ?>
                                            <span style="color: #64748b; font-size: 0.9rem;">
                                                <i class="fa fa-calendar" style="color: #10b981;"></i> 
                                                Son Güncelleme: <strong><?php echo date('d.m.Y', strtotime($sayfacek['sayfa_tarih'])); ?></strong>
                                            </span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="entry-content" style="color: #334155; line-height: 1.8; font-size: 1.15rem;">
                                        <?php 
                                        // 1. Get content
                                        $content = $sayfacek['sayfa_icerik'];
                                        
                                        // 2. Lazy Loading
                                        $content = preg_replace('/<img((?![^>]*loading=)[^>]*)>/i', '<img loading="lazy"$1>', $content);
                                        
                                        // 3. Automatic Internal Linking (SEO)
                                        $links = array(
                                            'iletişim' => SITE_URL . 'sayfa/iletisim',
                                            'ana sayfa' => SITE_URL,
                                            'taktik pantolon' => SITE_URL,
                                            'hakkımızda' => SITE_URL . 'sayfa/hakkimizda'
                                        );
                                        
                                        // Simple replacement preventing replacement inside HTML tags
                                        foreach($links as $word => $url) {
                                            // Regex to replace text outside HTML tags
                                            $pattern = '/(?!(?:[^<]+>|[^>]+<\/a>))\b(' . preg_quote($word, '/') . ')\b/iu';
                                            $content = preg_replace($pattern, '<a href="'.$url.'" style="color: #3b82f6; text-decoration: underline;">$1</a>', $content, 1); // Limit to 1 replacement per keyword
                                        }
                                        
                                        echo $content;
                                        ?>
                                    </div>
                                    
                                    <div class="cta-container" style="margin: 40px 0; text-align: center;">
                                        <a href="<?php echo SITE_URL; ?>" class="btn-premium-cta">
                                            <span class="icon"><i class="fa fa-shopping-cart"></i></span>
                                            <span class="text">
                                                <small>Hemen Keşfet</small>
                                                Ürünlerimizi İncele & Satın Al
                                            </span>
                                            <span class="arrow"><i class="fa fa-chevron-right"></i></span>
                                        </a>
                                        <style>
                                            .btn-premium-cta {
                                                display: inline-flex;
                                                align-items: center;
                                                justify-content: space-between;
                                                background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
                                                color: white !important;
                                                padding: 15px 30px;
                                                border-radius: 50px;
                                                text-decoration: none;
                                                box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
                                                transition: all 0.3s ease;
                                                max-width: 400px;
                                                width: 100%;
                                                position: relative;
                                                overflow: hidden;
                                            }
                                            .btn-premium-cta:hover {
                                                transform: translateY(-3px);
                                                box-shadow: 0 15px 30px rgba(255, 107, 107, 0.4);
                                                background: linear-gradient(135deg, #ff5252 0%, #ff7043 100%);
                                            }
                                            .btn-premium-cta .icon {
                                                background: rgba(255,255,255,0.2);
                                                width: 40px;
                                                height: 40px;
                                                border-radius: 50%;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                font-size: 1.2rem;
                                                margin-right: 15px;
                                            }
                                            .btn-premium-cta .text {
                                                flex: 1;
                                                text-align: left;
                                                font-weight: 700;
                                                font-size: 1.1rem;
                                                line-height: 1.2;
                                                display: flex;
                                                flex-direction: column;
                                            }
                                            .btn-premium-cta .text small {
                                                font-weight: 400;
                                                font-size: 0.85rem;
                                                opacity: 0.9;
                                                margin-bottom: 2px;
                                            }
                                            .btn-premium-cta .arrow {
                                                margin-left: 15px;
                                                font-size: 1.2rem;
                                                animation: slideRight 1.5s infinite;
                                            }
                                            @keyframes slideRight {
                                                0%, 100% { transform: translateX(0); }
                                                50% { transform: translateX(5px); }
                                            }
                                            /* Mobile Responsiveness */
                                            @media (max-width: 768px) {
                                                .btn-premium-cta {
                                                    padding: 12px 20px;
                                                    width: 90%;
                                                }
                                                .btn-premium-cta .text { font-size: 1rem; }
                                            }
                                        </style>
                                    </div>
                                    

                                    <div class="share-buttons">
                                        <h5 style="margin-bottom: 15px; font-weight: 600;">Bu içeriği paylaş:</h5>
                                        <?php $current_url = 'http://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''); ?>
                                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" target="_blank" class="btn btn-sm btn-share btn-facebook">
                                            <i class="fa fa-facebook"></i> Facebook
                                        </a>
                                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($current_url); ?>&text=<?php echo urlencode($sayfacek['sayfa_baslik']); ?>" target="_blank" class="btn btn-sm btn-share btn-twitter">
                                            <i class="fa fa-twitter"></i> Twitter
                                        </a>
                                        <a href="https://wa.me/?text=<?php echo urlencode($sayfacek['sayfa_baslik'] . ' ' . $current_url); ?>" target="_blank" class="btn btn-sm btn-share btn-whatsapp" title="WhatsApp" aria-label="WhatsApp ile paylaş">
                                            <i class="fa fa-whatsapp" aria-hidden="true"></i>
                                        </a>
                                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($current_url); ?>&title=<?php echo urlencode($sayfacek['sayfa_baslik']); ?>" target="_blank" class="btn btn-sm btn-share btn-linkedin">
                                            <i class="fa fa-linkedin"></i> LinkedIn
                                        </a>
                                        <button onclick="navigator.clipboard.writeText('<?php echo $current_url; ?>'); alert('Link kopyalandı!');" class="btn btn-sm btn-share btn-copy">
                                            <i class="fa fa-link"></i> Linki Kopyala
                                        </button>
                                    </div>

                                    <?php 
                                    // Related Pages (Internal Linking)
                                    // Exclude specific pages (slugs) from being shown AND from showing related content
                                    $excluded_slugs = array('hakkimizda', 'iletisim');
                                    
                                    // Only show if current page is NOT one of the excluded ones
                                    if (!in_array($sayfacek['sayfa_slug'], $excluded_slugs)) {
                                        $iliskilisor = $db->prepare("SELECT * FROM sayfalar WHERE sayfa_durum=:durum AND id!=:id AND sayfa_slug NOT IN ('" . implode("','", $excluded_slugs) . "') ORDER BY RAND() LIMIT 3");
                                        $iliskilisor->execute([
                                            'durum' => 1,
                                            'id' => $sayfacek['id']
                                        ]);
                                        
                                        if ($iliskilisor->rowCount() > 0) {
                                    ?>
                                    <div class="related-content" style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 30px;">
                                        <h4 style="margin-bottom: 20px; font-weight: 700;">Bunları da Okuyabilirsiniz</h4>
                                        <div class="row">
                                            <?php while($iliskilicek=$iliskilisor->fetch(PDO::FETCH_ASSOC)) { ?>
                                            <div class="col-md-4 mb-4">
                                                <div class="card h-100 border-0 shadow-sm">
                                                    <?php if(!empty($iliskilicek['sayfa_resim'])) { ?>
                                                    <a href="<?php echo SITE_URL . 'sayfa/' . $iliskilicek['sayfa_slug']; ?>">
                                                        <img src="<?php echo SITE_URL . $iliskilicek['sayfa_resim']; ?>" class="card-img-top" alt="<?php echo $iliskilicek['sayfa_baslik']; ?>" style="height: 150px; object-fit: cover; border-radius: 8px 8px 0 0;">
                                                    </a>
                                                    <?php } ?>
                                                    <div class="card-body p-3">
                                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 10px;">
                                                            <a href="<?php echo SITE_URL . 'sayfa/' . $iliskilicek['sayfa_slug']; ?>" style="color: #333; font-weight: 600;"><?php echo $iliskilicek['sayfa_baslik']; ?></a>
                                                        </h5>
                                                        <a href="<?php echo SITE_URL . 'sayfa/' . $iliskilicek['sayfa_slug']; ?>" class="btn btn-xs btn-default">Oku &rarr;</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php 
                                        }
                                    } 
                                    ?>
                                </div>
                            </div>
                            
                            <!-- Sayfa Yorumları Alanı -->
                            <?php if ($sayfacek['sayfa_slug'] != 'hakkimizda' && $sayfacek['sayfa_slug'] != 'iletisim') { ?>
                            <div class="comments-section-container" id="comments-section-container" style="margin-top: 40px;">
                                <div class="card shadow-sm border-0" style="border-radius: 12px; background: #fff;">
                                    <div class="card-body p-4 p-md-5">
                                        <h3 style="font-weight: 800; color: #1e293b; margin-bottom: 30px; display: flex; align-items: center; gap: 10px;">
                                            <i class="fa fa-comments" style="color: var(--renk2);"></i>
                                            Yorumlar
                                        </h3>

                                        <?php if (isset($_GET['yorum']) && $_GET['yorum'] == 'ok') { ?>
                                            <!-- Popup moved to footer but keeping some vertical spacing if needed -->
                                            <div style="height: 1px;"></div>
                                        <?php } ?>

                                        <div class="row">
                                            <!-- Yorum Listesi -->
                                            <div class="col-lg-12 mb-5">
                                                <?php
                                                $sayfa_id = $sayfacek['sayfa_id'];
                                                $sayfa_yorum_sor = $db->prepare("SELECT * FROM yorumlar WHERE yorum_onay=1 AND yorum_tip='sayfa' AND sayfa_id=:id ORDER BY id DESC");
                                                $sayfa_yorum_sor->execute(['id' => $sayfa_id]);
                                                
                                                if ($sayfa_yorum_sor->rowCount() > 0) {
                                                    while($sayfa_yorum_cek = $sayfa_yorum_sor->fetch(PDO::FETCH_ASSOC)) {
                                                        // Tarih kontrolü ve düzeltme
                                                        $yorum_tarih = ($sayfa_yorum_cek['tarih'] == '0000-00-00 00:00:00' || empty($sayfa_yorum_cek['tarih'])) ? date('d.m.Y') : date('d.m.Y', strtotime($sayfa_yorum_cek['tarih']));
                                                ?>
                                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: all 0.3s ease;">
                                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                                            <div>
                                                                <strong style="color: #1e293b; font-size: 1.1rem;"><?php echo htmlspecialchars($sayfa_yorum_cek['ad']); ?></strong>
                                                                <div style="color: #f59e0b; font-size: 0.95rem; margin-top: 5px; letter-spacing: 2px;">
                                                                    <?php 
                                                                    $puan = (int)$sayfa_yorum_cek['puan'];
                                                                    for($i=1; $i<=5; $i++) {
                                                                        if ($i <= $puan) echo '<i class="fa fa-star"></i>';
                                                                        else echo '<i class="fa fa-star-o"></i>';
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>
                                                            <small style="color: #64748b; font-weight: 600; background: #fff; padding: 4px 12px; border-radius: 20px; border: 1px solid #e2e8f0;"><?php echo $yorum_tarih; ?></small>
                                                        </div>
                                                        <p style="color: #475569; font-size: 1.05rem; line-height: 1.7; margin: 0; font-style: italic;">
                                                            <?php 
                                                            // Decode entities first in case it's double escaped
                                                            $decoded_text = htmlspecialchars_decode($sayfa_yorum_cek['detay']);
                                                            $decoded_text = str_replace('&nbsp;', ' ', $decoded_text);
                                                            // Replace common tags with newlines before stripping to preserve formatting
                                                            $formatted_text = str_replace(['<p>', '</p>', '<br>', '<br/>', '<br />', '<div>', '</div>'], ["", "\n", "\n", "\n", "\n", "", "\n"], $decoded_text);
                                                            $clean_text = trim(strip_tags($formatted_text));
                                                            echo '"' . nl2br(htmlspecialchars($clean_text)) . '"';
                                                            ?>
                                                        </p>
                                                    </div>
                                                <?php 
                                                    }
                                                } else {
                                                    echo '<div style="text-align: center; padding: 60px; color: #94a3b8; border: 2px dashed #e2e8f0; border-radius: 20px; background: #f8fafc;">
                                                            <i class="fa fa-comment-o" style="font-size: 4rem; display: block; margin-bottom: 20px; opacity: 0.2;"></i>
                                                            <p style="margin: 0; font-size: 1.1rem;">Henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
                                                          </div>';
                                                }
                                                ?>
                                            </div>

                                            <!-- Yorum Ekleme Formu -->
                                            <div class="col-lg-12">
                                                <div style="background: #fff; padding: 35px; border-radius: 20px; border: 2px solid #f1f5f9; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                                                    <h4 style="font-weight: 800; color: #1e293b; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                                                        <i class="fa fa-pencil" style="color: var(--renk1);"></i>
                                                        Yeni Yorum Gönder
                                                    </h4>
                                                    <form action="<?php echo SITE_URL; ?>yorum-yap.php" method="POST">
                                                        <input type="hidden" name="sayfa_id" value="<?php echo $sayfacek['sayfa_id']; ?>">
                                                        <input type="hidden" name="yorum_tip" value="sayfa">
                                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($_GET['slug']); ?>">
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label style="font-weight: 700; color: #475569; margin-bottom: 8px; font-size: 0.95rem;">Adınız Soyadınız</label>
                                                                <input type="text" name="ad" class="form-control" required style="border-radius: 10px; border: 1px solid #e2e8f0; padding: 12px 20px; font-size: 1rem; background: #fdfdfd;">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label style="font-weight: 700; color: #475569; margin-bottom: 8px; font-size: 0.95rem;">Puanınız</label>
                                                                <select name="puan" class="form-control" style="border-radius: 10px; border: 1px solid #e2e8f0; height: auto; padding: 12px 20px; font-size: 1rem; background: #fdfdfd;">
                                                                    <option value="5">⭐⭐⭐⭐⭐ (Kusursuz)</option>
                                                                    <option value="4">⭐⭐⭐⭐ (Çok İyi)</option>
                                                                    <option value="3">⭐⭐⭐ (İyi)</option>
                                                                    <option value="2">⭐⭐ (Kötü)</option>
                                                                    <option value="1">⭐ (Çok Kötü)</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group mb-4">
                                                            <label style="font-weight: 700; color: #475569; margin-bottom: 8px; font-size: 0.95rem;">Yorumunuz</label>
                                                            <textarea name="detay" class="form-control" rows="5" required style="border-radius: 10px; border: 1px solid #e2e8f0; padding: 20px; font-size: 1rem; background: #fdfdfd; resize: none;"></textarea>
                                                        </div>
                                                        <div class="text-end">
                                                            <button type="submit" name="yorumekle" class="btn btn-primary lg" style="background: var(--renk1); border: none; padding: 15px 40px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; box-shadow: 0 8px 20px rgba(0,0,0,0.15); transition: all 0.3s ease;">
                                                                <i class="fa fa-send"></i> YORUMU GÖNDER
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Simple Floating Product Button -->
        <a href="<?php echo SITE_URL; ?>" class="simple-product-btn" title="Satın Al">
            <i class="fa fa-shopping-bag"></i>
            <span>Satın Al</span>
        </a>
        <style>
            .simple-product-btn {
                position: fixed;
                right: 20px;
                top: 50%;
                margin-top: -30px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white !important;
                padding: 15px 20px;
                border-radius: 50px;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                z-index: 999;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 5px;
                font-weight: 700;
                font-size: 0.85rem;
                text-decoration: none !important;
                transition: all 0.3s ease;
                min-width: 70px;
            }
            .simple-product-btn:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
                background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            }
            .simple-product-btn i {
                font-size: 1.5rem;
                margin-bottom: 3px;
            }
            @media (max-width: 768px) {
                .simple-product-btn {
                    right: 10px;
                    padding: 12px 15px;
                    font-size: 0.75rem;
                    min-width: 60px;
                }
                .simple-product-btn i {
                    font-size: 1.3rem;
                }
            }
        </style>
        
        <?php include 'include/footer.php'; ?>
    </div>
</body>
</html>
