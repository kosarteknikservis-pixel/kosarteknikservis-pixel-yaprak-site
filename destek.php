<?php
define('VITRIN_HEAD_MINIMAL', true);
include __DIR__ . '/include/head.php';
?>
<title><?php echo htmlspecialchars('Destek Talebi | ' . (isset($settingsprint['ayar_title']) ? $settingsprint['ayar_title'] : 'Site'), ENT_QUOTES, 'UTF-8'); ?></title>
<meta name="robots" content="index,follow">
</head>
<body class="wide no-page-loader">
    <div id="wrapper" class="clearfix">
        <?php include __DIR__ . '/include/menu.php'; ?>

        <div class="container" style="padding: 120px 0 50px 0;">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-5">
                            <?php
                            $form_slug = 'destek-talebi';
                            include __DIR__ . '/include/form-renderer.php';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/include/footer-form.php'; ?>
    </div><!-- #wrapper -->
</body>
</html>
