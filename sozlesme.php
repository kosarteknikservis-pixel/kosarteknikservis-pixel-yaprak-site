<?php
define('VITRIN_HEAD_MINIMAL', true);
include 'include/head.php';
$pageedit = $db->prepare('SELECT * from sozlesme where id=:sayfaid');
$pageedit->execute(array(
    'sayfaid' => 1,
));
$pagewrite = $pageedit->fetch(PDO::FETCH_ASSOC);
if (!is_array($pagewrite)) {
    $pagewrite = array('ad' => 'Sözleşmeler', 'icerik' => '<p>İçerik henüz tanımlanmadı.</p>');
}
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
                    <h1>SÖZLEŞMELER</h1>
                </div>
            </div>
        </section>
        <section style="background-color: #fff;">
            <div class="container">
                <div class="row">
                    <div class="accordion">
                        <h4><?php echo htmlspecialchars(isset($pagewrite['ad']) ? $pagewrite['ad'] : '', ENT_QUOTES, 'UTF-8'); ?></h4>
                    </div>
                    <div class="sozlesme-icerik"><?php echo isset($pagewrite['icerik']) ? $pagewrite['icerik'] : ''; ?></div>
                    <a href="<?php echo htmlspecialchars(isset($settingsprint['ayar_siteurl']) ? $settingsprint['ayar_siteurl'] : SITE_URL, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-xl">SİTEYE DÖN!</a>
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
