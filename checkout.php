<?php
define('VITRIN_HEAD_MINIMAL', true);
include 'include/head.php';

$urun_id = isset($_GET['urun_id']) ? (int) $_GET['urun_id'] : 0;
$urun_adi = trim((string)($_GET['urun'] ?? ''));
$fiyat_raw = (string)($_GET['fiyat'] ?? '0');
$fiyat = (int) preg_replace('/[^0-9]/', '', $fiyat_raw);

if ($urun_adi === '' && $urun_id > 0) {
    try {
        $q = $db->prepare('SELECT urun_baslik, urun_fiyat FROM urunler WHERE urun_id = :id LIMIT 1');
        $q->execute(['id' => $urun_id]);
        $r = $q->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $urun_adi = trim((string)$r['urun_baslik']);
            if ($fiyat <= 0) {
                $fiyat = (int) floatval($r['urun_fiyat']);
            }
        }
    } catch (Exception $e) {
    }
}
if ($urun_adi === '') {
    $urun_adi = 'Ürün seçilmedi';
}
?>
<title>Checkout - <?php echo htmlspecialchars($settingsprint['ayar_title'] ?? 'Site', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body class="wide no-page-loader">
<?php include 'include/menu.php'; ?>

<section style="background:#f8fafc; min-height:100vh; padding: 26px 0 40px;">
  <div class="container" style="max-width: 760px;">
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 12px 28px rgba(15,23,42,.08); padding:22px;">
      <div style="font-size:12px; font-weight:800; color:#0f766e; letter-spacing:.05em; text-transform:uppercase; margin-bottom:8px;">E-Ticaret Deneme Adımı</div>
      <h2 style="margin:0 0 14px; color:#0f172a; font-size:1.6rem; font-weight:900;">Ödeme Öncesi Kontrol</h2>

      <div style="background:linear-gradient(180deg,#ecfeff 0%,#f0fdfa 100%); border:1px solid #99f6e4; border-radius:12px; padding:14px 16px; margin-bottom:16px;">
        <div style="font-size:12px; color:#155e75; font-weight:800; text-transform:uppercase; margin-bottom:4px;">Seçtiğiniz Ürün</div>
        <div style="font-size:1.12rem; color:#0f172a; font-weight:800; line-height:1.3;"><?php echo htmlspecialchars($urun_adi, ENT_QUOTES, 'UTF-8'); ?></div>
        <div style="font-size:1.25rem; color:#dc2626; font-weight:900; margin-top:6px;"><?php echo number_format($fiyat, 0, ',', '.'); ?> TL</div>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="<?php echo htmlspecialchars(rtrim(SITE_URL, '/') . '/index.php?checkout=1&urun_id=' . $urun_id, ENT_QUOTES, 'UTF-8'); ?>#odeme-yontemi-label" style="flex:1 1 280px; text-align:center; text-decoration:none; background:#16a34a; color:#fff; padding:12px 14px; border-radius:10px; font-weight:900;">Siparişi Tamamlamaya Geç</a>
        <a href="<?php echo htmlspecialchars(rtrim(SITE_URL, '/') . '/index.php', ENT_QUOTES, 'UTF-8'); ?>" style="flex:1 1 180px; text-align:center; text-decoration:none; background:#fff; color:#0f172a; padding:12px 14px; border-radius:10px; border:1px solid #cbd5e1; font-weight:700;">Ürünleri Yeniden Seç</a>
      </div>

      <p style="margin:14px 0 0; color:#64748b; font-size:.95rem;">Bu sayfa, e-ticaret davranışı denemesi için ürün seçiminden sonra ayrı checkout adımı sunar.</p>
    </div>
  </div>
</section>

<?php include 'include/footer-form.php'; ?>
</body>
</html>
