<?php
/**
 * Form / sade vitrin sayfaları — footer.php yerine.
 * Ana sayfa sipariş akışına ait JS (ürün seçenek AJAX, lightbox, plugins/main) YOK;
 * tek satır hata bile tüm sayfayı "beyaz" gösterebiliyordu.
 */
$urunler = @$_SESSION['urunler'];
if (!isset($whatsappprint) || !is_array($whatsappprint)) {
    $whatsappprint = array(
        'whats_tiklaaradurum' => 0,
        'whats_durum'         => 0,
        'whats_tiklaara'      => '',
        'whats_tel'           => '',
    );
}
if (!isset($motorprint) || !is_array($motorprint)) {
    $motorprint = array('motor_analitik' => '', 'motor_gonay' => '', 'motor_yonay' => '');
}
?>
<?php
$analitik_codes = isset($motorprint['motor_analitik']) ? (string) $motorprint['motor_analitik'] : '';
$current_price = 0;
if (isset($_SERVER['PHP_SELF']) && basename((string) $_SERVER['PHP_SELF']) === 'index.php') {
    try {
        $pixel_urun_sor = $db->query('SELECT urun_fiyat FROM urunler ORDER BY urun_siralama ASC, urun_id ASC LIMIT 1');
        if ($pixel_urun_sor) {
            $pixel_urun = $pixel_urun_sor->fetch(PDO::FETCH_ASSOC);
            if ($pixel_urun) {
                $current_price = intval(floatval($pixel_urun['urun_fiyat']));
            }
        }
    } catch (Exception $e) {
        $current_price = 0;
    }
}
$pixel_price = number_format($current_price, 2, '.', '');
$analitik_codes = str_replace(array('{tutar}', '{currency}'), array($pixel_price, 'TRY'), $analitik_codes);
echo $analitik_codes;
?>
<script>window.PANEL_SITE_URL = <?php echo json_encode(SITE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<link rel="stylesheet" href="<?php echo SITE_URL; ?>xnull/assets/lib/sweet-alerts2/sweetalert2.min.css">
<script src="<?php echo SITE_URL; ?>xnull/assets/lib/sweet-alerts2/sweetalert2.min.js"></script>
<style>
.swal-overlay, .swal2-container, .sweet-alert, .swal2-popup { z-index: 2147483647 !important; }
</style>
