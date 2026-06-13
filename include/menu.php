<?php 
// include/menu.php - Refined Premium Header Menu
$sayim = isset($_SESSION['urunler']) ? $_SESSION['urunler'] : array();
$ayar_menu_on = 1; // Forced active as per user request

// motor_yonay (panel: API Ayarları — <body> içi): tüm menülü vitrin sayfalarında bir kez; {tutar}/{urun_adi} ilk üründen.
if (!defined('NIR_MOTOR_YONAY_EMITTED')) {
    define('NIR_MOTOR_YONAY_EMITTED', true);
    $yonay_code = isset($motorprint['motor_yonay']) ? (string) $motorprint['motor_yonay'] : '';
    if ($yonay_code !== '' && isset($db)) {
        try {
            $py_sor = $db->query('SELECT urun_fiyat, urun_baslik FROM urunler ORDER BY urun_siralama ASC, urun_id ASC LIMIT 1');
            $py_row = $py_sor ? $py_sor->fetch(PDO::FETCH_ASSOC) : null;
            $py_fiyat   = $py_row ? number_format(intval(floatval($py_row['urun_fiyat'])), 2, '.', '') : '0.00';
            $py_urunadi = $py_row ? htmlspecialchars(strip_tags($py_row['urun_baslik'])) : '';
        } catch (Exception $e) {
            $py_fiyat = '0.00';
            $py_urunadi = '';
        }
        echo str_replace(
            ['{tutar}', '{urun_adi}', '{currency}', '{siparis_id}'],
            [$py_fiyat, $py_urunadi, 'TRY', ''],
            $yonay_code
        );
    }
}

if (!function_exists('panel_render_menu_recursive')) {
    function panel_render_menu_recursive($db, $siteBase, $parentId = 0) {
        $siteBase = rtrim((string) $siteBase, '/') . '/';
        $sayfasor = $db->prepare("SELECT * FROM sayfalar WHERE sayfa_id = :parent AND sayfa_menu = 1 AND sayfa_durum = 1 ORDER BY sayfa_sira ASC");
        $sayfasor->execute(['parent' => $parentId]);
        $sayfalar = $sayfasor->fetchAll(PDO::FETCH_ASSOC);

        $formlar = [];
        if ($parentId == 0) {
            $formsor = $db->prepare("SELECT * FROM formlar WHERE form_menu = 1 AND form_durum = 1 ORDER BY form_sira ASC");
            $formsor->execute();
            $formlar = $formsor->fetchAll(PDO::FETCH_ASSOC);
        }

        $menuItems = [];
        foreach ($sayfalar as $sayfa) {
            $menuItems[] = [
                'type' => 'page',
                'title' => $sayfa['sayfa_baslik'],
                'slug' => 'sayfa/' . $sayfa['sayfa_slug'],
                'sira' => $sayfa['sayfa_sira'],
                'id' => $sayfa['id'],
            ];
        }

        foreach ($formlar as $form) {
            $menuItems[] = [
                'type' => 'form',
                'title' => $form['form_baslik'],
                'slug' => $form['form_slug'],
                'sira' => $form['form_sira'],
                'id' => 0,
            ];
        }

        usort($menuItems, function ($a, $b) {
            return $a['sira'] - $b['sira'];
        });

        if (count($menuItems) > 0) {
            echo ($parentId == 0) ? "" : '<ul class="sub-menu">';

            foreach ($menuItems as $item) {
                echo '<li>';

                $path = ltrim((string) $item['slug'], '/');
                if (preg_match('#^https?://#i', $path)) {
                    $href = $path;
                } elseif ($item['type'] === 'page') {
                    $href = $siteBase . $path;
                } else {
                    // Form: rewrite zorunlu olmasın (form.php her zaman çalışır)
                    $href = rtrim((string) $siteBase, '/') . '/form.php?slug=' . rawurlencode($path);
                }

                if ($item['type'] == 'page') {
                    $hasChild = $db->prepare("SELECT COUNT(*) FROM sayfalar WHERE sayfa_id = :id AND sayfa_menu = 1 AND sayfa_durum = 1");
                    $hasChild->execute(['id' => $item['id']]);
                    $count = $hasChild->fetchColumn();

                    echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $item['title'] . ($count > 0 ? ' <i class="fa fa-angle-down"></i>' : '') . '</a>';

                    if ($count > 0) {
                        panel_render_menu_recursive($db, $siteBase, $item['id']);
                    }
                } else {
                    echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $item['title'] . '</a>';
                }

                echo '</li>';
            }
            echo ($parentId == 0) ? "" : '</ul>';
        }
    }
}

if ($ayar_menu_on == 1) {
    $logo_tip = isset($settingsprint['ayar_logo_tip']) ? $settingsprint['ayar_logo_tip'] : 0;
    $logo_metin = isset($settingsprint['ayar_logo_metin']) ? $settingsprint['ayar_logo_metin'] : ($settingsprint['ayar_firmaadi'] ?? '');
    $menu_site_base = !empty($settingsprint['ayar_siteurl'])
        ? rtrim((string) $settingsprint['ayar_siteurl'], '/') . '/'
        : ((defined('SITE_URL') && SITE_URL !== '')
            ? rtrim(SITE_URL, '/') . '/'
            : '/');
?>
<!-- Premium Header -->
<header id="main-header" class="header-glass">
    <div class="header-content-inner">
        <!-- Logo Section -->
        <div class="logo-area">
            <a href="<?php echo htmlspecialchars($menu_site_base, ENT_QUOTES, 'UTF-8'); ?>" class="logo-link">
                <?php if ($logo_tip == 1) { 
                    // YAZI LOGO
                    $words = explode(' ', $logo_metin, 2);
                    if (count($words) > 1) {
                ?>
                    <div class="text-logo-wrapper">
                        <div style="display: flex; flex-direction: column;">
                            <span class="text-logo-main"><?php echo $words[0]; ?></span>
                            <span class="text-logo-sub"><?php echo $words[1]; ?></span>
                        </div>
                    </div>
                <?php } else { ?>
                    <span class="text-logo"><?php echo $logo_metin; ?></span>
                <?php } ?>

                <?php } else if ($logo_tip == 2) { 
                    // İKON LOGO
                ?>
                    <div class="icon-logo-wrapper" style="font-size: 2.2rem; color: #fff;">
                        <i class="fa <?php echo $settingsprint['ayar_logo_icon']; ?>"></i>
                    </div>

                <?php } else { 
                    // GÖRSEL LOGO (Default 0)
                ?>
                    <img src="<?php echo rtrim(SITE_URL, '/') . '/xnull/' . ltrim($settingsprint['ayar_logo'], '/'); ?>" alt="<?php echo $settingsprint['ayar_firmaadi']; ?>" class="header-logo">
                <?php } ?>
            </a>
        </div>

        <!-- Header Actions -->
        <div class="header-actions">
            <div class="menu-trigger-wrapper" id="mobileMenuBtn">
                <div class="hamburger-grid">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
                <span class="menu-text">Menü</span>
            </div>
        </div>
    </div>
</header>

<!-- Mobile/Fullscreen Menu Overlay -->
<div class="menu-overlay" id="menuOverlay">
    <div class="overlay-close" id="closeMenu">&times;</div>
    <div class="overlay-content">
        <ul class="overlay-nav-links">
            <li><a href="<?php echo htmlspecialchars($menu_site_base, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-home"></i> Anasayfa</a></li>
            <?php panel_render_menu_recursive($db, $menu_site_base, 0); ?>
        </ul>
    </div>
</div>

<style>
/* --- Refined Premium Header Styles --- */
#main-header {
    position: fixed; /* GUARANTEED STICKY */
    top: 0;
    left: 0;
    width: 100%;
    /* Modallar (2147483647) açıkken üstte kalsınlar; vitrin katmanlarının üstünde ve tıklanabilir kalsın */
    z-index: 2147483620;
    background: #1e293b; 
    border-bottom: none;
    transition: all 0.3s ease;
    height: 75px; 
    display: flex;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    pointer-events: auto;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
    isolation: isolate;
}

@media (max-width: 768px) {
    #main-header {
        height: 60px !important; /* Smaller header on mobile */
    }
}

.header-content-inner {
    max-width: <?php echo isset($settingsprint['ayar_harita']) ? $settingsprint['ayar_harita'] : 1200; ?>px;
    margin: 0 auto !important;
    width: 100% !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 15px !important; 
}

/* Logo Styles */
.logo-area .logo-link {
    display: block;
    padding: 0 !important;
    margin: 0 !important;
}

.logo-area .header-logo {
    max-height: 70px; /* Larger logo */
    width: auto;
    display: block;
}

@media (max-width: 768px) {
    .logo-area .header-logo {
        max-height: 45px !important; /* Smaller logo on mobile */
    }
}

.text-logo {
    color: #fff;
    font-size: 2.8rem; /* Bold text logo */
    font-weight: 900;
    letter-spacing: -1.2px;
    font-family: 'Montserrat', sans-serif;
    line-height: 0.8;
}

.text-logo-wrapper {
    display: flex;
    flex-direction: column;
    line-height: 1.1; /* Fixed overlap */
}

.text-logo-main {
    color: #fff;
    font-size: 2.1rem; /* Slightly smaller for elegance */
    font-weight: 900;
    letter-spacing: -1px;
    font-family: 'Montserrat', sans-serif;
    text-transform: capitalize;
}

.text-logo-sub {
    color: #fff;
    font-size: 1.2rem; /* Smaller sub-text */
    font-weight: 600;
    text-transform: lowercase;
    opacity: 0.9;
}

/* Menu Trigger Styles */
.menu-trigger-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
    cursor: pointer;
    padding: 10px 0 !important;
}

.hamburger-grid {
    display: flex;
    flex-direction: column;
    gap: 4px; /* Slightly tighter */
}

.hamburger-grid .bar {
    width: 28px; /* Further reduced from 34px */
    height: 3px; /* Further reduced from 4px */
    background: #fff;
    border-radius: 4px;
}

.menu-text {
    color: #fff;
    font-size: 1.7rem; /* Adjusted for better balance */
    font-weight: 800;
    text-transform: none;
}

/* Overlay Menu */
.menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.98);
    z-index: 2147483647 !important;
    display: none;
    pointer-events: none;
    align-items: center; /* Revert to center */
    justify-content: center;
    /* padding-top removed */
    transition: none !important;
}
.menu-overlay.menu-overlay-open {
    pointer-events: auto;
}

.overlay-close {
    position: absolute;
    top: 30px;
    right: 40px;
    color: #fff;
    font-size: 3rem;
    cursor: pointer;
    line-height: 1;
}

.overlay-content {
    text-align: center; /* Changed back to center */
    padding-left: 0; /* Remove left padding */
}

.overlay-nav-links {
    list-style: none;
    padding: 0;
}

.overlay-nav-links > li {
    margin-bottom: 25px;
}

.overlay-nav-links > li > a {
    color: #fff;
    font-size: 2rem;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.3s;
}

.overlay-nav-links > li > a:hover {
    color: var(--renk2);
}

.sub-menu {
    list-style: none;
    padding: 15px 0 0 20px;
    margin-top: 5px;
    border-left: 2px solid rgba(255,255,255,0.1);
}

.sub-menu li {
    margin-bottom: 12px;
}

.sub-menu li a {
    color: #94a3b8;
    font-size: 1.4rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center; /* Center align sub-menu items */
    gap: 8px;
}

.sub-menu li a:hover {
    color: var(--renk2);
    transform: translateX(5px);
}

/* Fixes */
/* Body padding removed completely since header is now relative */

/* FIX: Header fixed olduğu için slider/ilk görsel header altında kalıyordu.
   order.php gibi özel sayfalar kendi padding-top'unu yönetiyor, burada hariç tutuyoruz. */
body:not(.order-checkout-page) {
    padding-top: 75px;
}
@media (max-width: 768px) {
    body:not(.order-checkout-page) {
        padding-top: 60px;
    }
}

/* Social Proof Adjustment */
#social-proof-container {
    top: 90px !important; /* Move it down when header is active */
}

@media (max-width: 768px) {
    .text-logo { font-size: 1.8rem; }
    .menu-text { font-size: 1.4rem; }
    .header-container { padding: 0 15px; }
    #main-header { height: 60px; }
    /* Body padding removed */
}

/* GLOBAL FIXES FOR USER REQUESTS */
/* 1. Geçiş kapat — #wrapper HARİÇ: apple.css body:not(.no-page-loader) #wrapper { opacity:0 } + animasyon burada öldürülünce içerik hiç görünmüyordu */
body, section, div:not(#wrapper) {
    animation: none !important;
    transition: none !important;
}

/* 2. Remove White Line/Gap */
header, #main-header {
    border-bottom: 0 !important;
    box-shadow: none !important;
    margin-bottom: 0 !important;
}

section#page-title {
    margin-top: 0 !important;
    border-top: 0 !important;
}
</style>

<?php if (defined('VITRIN_HEAD_MINIMAL') && VITRIN_HEAD_MINIMAL) { ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var overlay = document.getElementById('menuOverlay');
    var btn = document.getElementById('mobileMenuBtn');
    var closeBtn = document.getElementById('closeMenu');
    if (overlay && overlay.parentNode !== document.body) {
        document.body.appendChild(overlay);
    }
    function openMenu() {
        if (!overlay) return;
        overlay.classList.add('menu-overlay-open');
        overlay.style.display = 'flex';
        overlay.style.pointerEvents = 'auto';
        document.body.style.overflow = 'hidden';
    }
    function closeMenuEv() {
        if (!overlay) return;
        overlay.classList.remove('menu-overlay-open');
        overlay.style.display = 'none';
        overlay.style.pointerEvents = 'none';
        document.body.style.overflow = 'auto';
    }
    if (btn) btn.addEventListener('click', openMenu);
    if (closeBtn) closeBtn.addEventListener('click', closeMenuEv);
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeMenuEv();
        });
    }
    var oc = document.querySelector('.menu-overlay .overlay-content');
    if (oc) oc.addEventListener('click', function(e) { e.stopPropagation(); });
});
</script>
<?php } else { ?>
<script>
$(document).ready(function() {
    // Move overlay to body to ensure it sits on top of everything
    $('#menuOverlay').appendTo('body');

    $('#mobileMenuBtn').on('click', function() {
        $('#menuOverlay').addClass('menu-overlay-open').css({ display: 'flex', pointerEvents: 'auto' });
        $('body').css('overflow', 'hidden');
    });

    $('#closeMenu, .menu-overlay').on('click', function(e) {
        if (e.target === this || e.target.id === 'closeMenu') {
            $('#menuOverlay').removeClass('menu-overlay-open').hide().css('pointer-events', 'none');
            $('body').css('overflow', 'auto');
        }
    });

    $('.overlay-content').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>
<?php } ?>
<?php } ?>
