<?php
include 'include/head.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

$sayfasor = $db->prepare('SELECT * from sayfalar where id=:id');
$sayfasor->execute(array('id' => $id));
$sayfacek = $sayfasor->fetch(PDO::FETCH_ASSOC);

if (!$sayfacek) {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}
?>
<title><?php echo htmlspecialchars(($sayfacek['sayfa_title'] ?? '') . ' - ' . ($settingsprint['ayar_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($sayfacek['sayfa_descr'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($sayfacek['sayfa_keyword'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="wide no-page-loader">
    <div id="wrapper" class="clearfix">
        <?php include 'include/menu.php'; ?>

        <section id="page-title" style="background: var(--renk1); padding: 80px 0;">
            <div class="container clearfix text-center">
                <h1 style="color: #fff; font-weight: 800; font-size: 2.5rem; margin-bottom: 15px;"><?php echo htmlspecialchars($sayfacek['sayfa_baslik'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
                <ol class="breadcrumb" style="background: transparent; justify-content: center; margin: 0;">
                    <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>" style="color: #eee;">Anasayfa</a></li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: var(--renk2);"><?php echo htmlspecialchars($sayfacek['sayfa_baslik'] ?? '', ENT_QUOTES, 'UTF-8'); ?></li>
                </ol>
            </div>
        </section>

        <section id="content" style="background: #fff; padding: 60px 0;">
            <div class="content-wrap">
                <div class="container clearfix">
                    <div class="row">
                        <div class="col-lg-9" style="margin: 0 auto;">
                            <div class="entry-content" style="color: #444; line-height: 1.8; font-size: 1.1rem;">
                                <?php echo $sayfacek['sayfa_icerik'] ?? ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php include 'include/footer.php'; ?>
    </div><!-- #wrapper -->
</body>
</html>
