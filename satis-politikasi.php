<?php
define('VITRIN_HEAD_MINIMAL', true);
include 'include/head.php';
require_once __DIR__ . '/include/legal-pages.php';
legal_pages_ensure_schema($db);
?>
<title><?php echo htmlspecialchars($settingsprint['ayar_title'] ?? 'Satış Politikası', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body class="wide no-page-loader">
<div id="wrapper" class="clearfix">
<?php include 'include/menu.php'; ?>
<?php legal_pages_render_public_shell('satis_politikasi', 'Satış Politikası'); ?>
<?php include 'include/footer.php'; ?>
<?php legal_pages_render_footer($db, $settingsprint, $whatsappprint ?? null); ?>
</div>
</body>
</html>
