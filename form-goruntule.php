<?php 
require_once __DIR__ . '/xnull/controller/config.php';

// Slug: veritabanı eşleşmesi için ham metin (htmlspecialchars arama bozuyordu)
$form_slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$form_slug = preg_replace('/[^A-Za-z0-9\-_]/', '', $form_slug);
$form_id = 0;
$form_baslik = '';
$fcek = false;

if ($form_slug !== '') {
    $fsor = $db->prepare("SELECT * FROM formlar WHERE form_slug=:slug AND form_durum=1");
    $fsor->execute(['slug' => $form_slug]);
    $fcek = $fsor->fetch(PDO::FETCH_ASSOC);
}

// Form bulunamadıysa 404 sayfasına yönlendir (Header ve HTML çıktısı 404.php içinde)
if ($form_slug === '' || !$fcek) {
    include __DIR__ . '/404.php';
    exit;
}

// Form bulunduysa normal akışa devam et
$form_baslik = $fcek['form_baslik'];
$form_id = $fcek['form_id'];

$form_slug_esc = htmlspecialchars($form_slug, ENT_QUOTES, 'UTF-8');

define('VITRIN_HEAD_MINIMAL', true);
include __DIR__ . '/include/head.php';
?>
<title><?php echo htmlspecialchars($form_baslik, ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars(isset($settingsprint['ayar_title']) ? $settingsprint['ayar_title'] : 'Form', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body class="wide no-page-loader">
    <div id="wrapper" class="clearfix">
        <?php include __DIR__ . '/include/menu.php'; ?>

        <!-- Page Title Section -->
        <section id="page-title" style="background: var(--renk1); padding: 140px 0 80px 0; margin-top: 0;">
            <div class="container clearfix text-center">
                <h1 style="color: #fff; font-weight: 800; font-size: 2.8rem; margin-bottom: 15px;"><?php echo $form_baslik; ?></h1>
                <ol class="breadcrumb" style="background: transparent; justify-content: center; margin: 0; padding: 0;">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>" style="color: #eee;">Anasayfa</a></li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: #eee;"><?php echo $form_baslik; ?></li>
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
                
                /* Mobile Fix for Title Padding */
                @media (max-width: 768px) {
                    #page-title {
                        padding-top: 130px !important;
                    }
                    #page-title h1 {
                        font-size: 2rem !important;
                    }
                }
            </style>
        </section>

        <!-- Content Section -->
        <section id="content" style="background: #f8fafc; padding: 40px 0;">
            <div class="content-wrap">
                <div class="container clearfix" style="max-width: <?php echo isset($settingsprint['ayar_harita']) ? $settingsprint['ayar_harita'] : 1200; ?>px !important; margin: 0 auto;">
                    <div class="row justify-content-center">
                        <div class="col-lg-12">
                            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                                <div class="card-body p-4 p-md-5 bg-white">
                                    <?php 
                                    if ($form_slug === '' || !$fcek) {
                                        echo '<div class="alert alert-danger">Aradığınız form bulunamadı veya yayından kaldırılmış.</div>';
                                    } else {
                                        include __DIR__ . '/include/form-renderer.php'; 
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php
        include __DIR__ . '/include/footer-form.php';
        $__swal_btn = '#3085d6';
        if (!empty($settingsprint['ayar_kod']) && is_string($settingsprint['ayar_kod'])) {
            $__c = trim($settingsprint['ayar_kod']);
            if ($__c !== '' && $__c[0] === '#') {
                $__swal_btn = $__c;
            }
        }
        ?>
    </div><!-- #wrapper -->

    <script>
    (function () {
        var confirmColor = <?php echo json_encode($__swal_btn, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        function runAlerts() {
            if (typeof swal !== 'function') return;
            var urlParams = new URLSearchParams(window.location.search);
            var durum = urlParams.get('durum');
            if (durum === 'ok') {
                swal({
                    title: 'Başarılı!',
                    text: 'Formunuz başarıyla gönderildi. En kısa sürede sizinle iletişime geçilecektir.',
                    type: 'success',
                    showCancelButton: false,
                    confirmButtonText: 'Tamam',
                    confirmButtonColor: confirmColor
                }).then(function () {
                    window.history.replaceState({}, document.title, window.location.pathname + '?slug=<?php echo $form_slug_esc; ?>');
                });
            } else if (durum === 'limit') {
                swal({
                    title: 'Zaten Gönderdiniz!',
                    text: 'Formunuzu kısa süre önce bize ilettiniz. En kısa sürede dönüş sağlayacağız.',
                    type: 'warning',
                    showCancelButton: false,
                    confirmButtonText: 'Anladım',
                    confirmButtonColor: confirmColor
                });
            } else if (durum === 'no') {
                swal({
                    title: 'Hata!',
                    text: 'Bir sorun oluştu, lütfen tekrar deneyiniz.',
                    type: 'error',
                    showCancelButton: false,
                    confirmButtonText: 'Tamam',
                    confirmButtonColor: confirmColor
                });
            }
        }
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(runAlerts);
        } else {
            document.addEventListener('DOMContentLoaded', runAlerts);
        }
    })();
    </script>

    <style>
    /* Vibrant Submit Button */
    .btn-vibrant {
        background: linear-gradient(45deg, #ff357a, #fff172);
        border: none;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 15px;
        font-size: 1.1rem;
        box-shadow: 0 4px 15px rgba(255, 53, 122, 0.4);
        transition: all 0.3s ease;
        color: #fff;
    }
    .btn-vibrant:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(255, 53, 122, 0.6);
        background: linear-gradient(45deg, #ff0f63, #ffeb3b);
        color: #fff;
    }
    </style>
</body>
</html>
