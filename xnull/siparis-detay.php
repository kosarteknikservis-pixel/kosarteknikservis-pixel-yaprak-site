<?php 
// Database bağlantısı her zaman gerekli
require_once ('controller/config.php');

$isPopup = isset($_GET['popup']) && $_GET['popup'] == '1';

if (!$isPopup) {
    include 'header.php';
    include 'topbar.php';
    include 'sidebar.php';
    $settingsprint = $db->query('SELECT * FROM ayar WHERE ayar_id=0')->fetch(PDO::FETCH_ASSOC) ?: [];
} else {
    // Popup modunda da session ve ayarlar gerekli
    date_default_timezone_set('Europe/Istanbul');
    if (!isset($_SESSION['kullanici_adi'])) {
        header("Location:login.php");
        exit;
    }
    $settings = $db->prepare('SELECT * from ayar where ayar_id=?');
    $settings->execute(array(0));
    $settingsprint = $settings->fetch(PDO::FETCH_ASSOC) ?: [];
    
    // Popup için minimal HTML ve CSS
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sipariş Detayları</title>
        <link href="assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.1/css/font-awesome.min.css" rel="stylesheet"/>
        <link href="assets/css/style.css?v=<?php echo PANEL_ASSET_VER; ?>" rel="stylesheet">
        <script src="assets/js/jquery.min.js"></script>
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            body.siparis-popup-shell {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 14px;
                line-height: 1.5;
                background: #e8edf3;
                margin: 0;
                padding: 0;
                color: #0f172a;
            }
            .sd-popup.main-content.container {
                width: 100%;
                max-width: 1060px;
                margin: 0 auto;
                padding: 16px 18px 24px !important;
            }
            .sd-popup .page-header {
                margin: 0 0 16px 0 !important;
                padding: 14px 18px !important;
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                border: none;
                border-radius: 12px;
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12);
            }
            .sd-popup .page-header h2 {
                font-size: 17px !important;
                font-weight: 700;
                color: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .sd-popup .card {
                margin: 0 0 16px 0 !important;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
                overflow: hidden;
            }
            .sd-popup .card-block {
                padding: 16px 18px !important;
            }
            .sd-popup .sd-meta-stack {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }
            .sd-popup .sd-meta-item {
                padding: 12px 14px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
            }
            .sd-popup .sd-meta-item h4 {
                margin: 0 0 8px 0 !important;
                font-size: 11px !important;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: #64748b !important;
            }
            .sd-popup .sd-meta-item h4 small {
                display: block;
                margin-top: 6px;
                font-size: 15px !important;
                font-weight: 600;
                text-transform: none;
                letter-spacing: 0;
                color: #0f172a !important;
                word-break: break-word;
            }
            .sd-popup .sd-meta-item .btn {
                margin-top: 10px !important;
                margin-right: 0 !important;
                border-radius: 8px;
                font-weight: 600;
            }
            .sd-popup h3 {
                font-size: 16px !important;
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 16px 0 !important;
                padding-bottom: 10px;
                border-bottom: 1px solid #e2e8f0;
            }
            .sd-popup .row.siparis-form-grid {
                display: flex;
                flex-direction: column;
                gap: 0;
                margin: 0 !important;
            }
            .sd-popup .siparis-form-grid > [class*="col-"] {
                flex: 0 0 100% !important;
                max-width: 100% !important;
                width: 100% !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                margin-bottom: 16px !important;
            }
            .sd-popup .siparis-form-grid > [class*="col-"]:last-child {
                margin-bottom: 0 !important;
            }
            .sd-popup .form-group {
                margin-bottom: 0 !important;
            }
            .sd-popup label {
                font-size: 12px !important;
                font-weight: 600;
                color: #64748b !important;
                margin-bottom: 6px !important;
                display: block;
            }
            .sd-popup input[type="text"],
            .sd-popup textarea,
            .sd-popup select {
                width: 100% !important;
                padding: 10px 12px !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                font-size: 14px !important;
            }
            .sd-popup textarea {
                resize: vertical;
                min-height: 88px;
            }
            .sd-popup textarea[name="siparis_urun"] {
                min-height: 120px !important;
                font-size: 13px !important;
                line-height: 1.45 !important;
                font-family: inherit;
            }
            .sd-popup .input-group {
                display: flex;
                align-items: stretch;
            }
            .sd-popup .input-group-addon {
                padding: 10px 12px;
                background: #f1f5f9;
                border: 1px solid #cbd5e1;
                border-right: 0;
                border-radius: 8px 0 0 8px;
                display: flex;
                align-items: center;
            }
            .sd-popup .input-group .form-control {
                border-radius: 0 8px 8px 0 !important;
            }
            .sd-popup .btn-success {
                padding: 12px 22px !important;
                border-radius: 8px;
                font-weight: 700;
            }
            .sd-popup .table {
                font-size: 12px;
            }
            .sd-popup .table th,
            .sd-popup .table td {
                padding: 8px 10px !important;
                vertical-align: top;
            }
            .sd-popup h4.sd-subsection-title {
                margin: 20px 0 12px 0 !important;
                font-size: 14px !important;
                font-weight: 700;
                color: #334155 !important;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 8px;
            }
            .sd-popup .sd-footer-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                justify-content: flex-end;
                margin-top: 8px;
            }
            .btn-parasut { background: #00b8a9; color: #fff; border-color: #009688; }
            .btn-parasut:hover { background: #009688; color: #fff; }
            /* Mobil iframe içi: style.css body{height} kaydırmayı kilitler */
            html {
                height: auto !important;
                min-height: 100%;
                overflow-x: hidden;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch;
            }
            body.siparis-popup-shell {
                height: auto !important;
                min-height: 100%;
                min-height: -webkit-fill-available;
                overflow-x: hidden;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch;
            }
        </style>
    </head>
    <body class="siparis-popup-shell">
    <?php
}
$inovance=$db->prepare("SELECT * from siparis where siparis_id=:siparis_id");
$inovance->execute(array(
  'siparis_id' => isset($_GET['siparis_id']) ? $_GET['siparis_id'] : 0
));
$inovanceprint=$inovance->fetch(PDO::FETCH_ASSOC);

// Sipariş bulunamadıysa hata ver
if (!$inovanceprint) {
  Header("Location:siparisler.php?status=no");
  exit();
}
// surun tablosu mevcut değil, sipariş detayında siparis_urun alanı kullanılıyor
// $surunsor1=$db->prepare("SELECT * from surun where surun_siparis=:surun_id");
// $surunsor1->execute(array(
//   'surun_id' => $inovanceprint['siparis_id'] 
// ));
if (isset($_POST['durum'])) {

  $sid = isset($_GET['siparis_id']) ? (int) $_GET['siparis_id'] : 0;
  $fiyatVal = isset($_POST['siparis_fiyat']) ? str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', $_POST['siparis_fiyat'])) : '0';

  $ayarkaydet = $db->prepare(
    "UPDATE siparis SET
    siparis_not=:not,
    siparis_fiyat=:fiyat,
    siparis_ip=:siparis_ip,
    siparis_ad=:siparis_ad,
    siparis_tel=:siparis_tel,
    siparis_urun=:siparis_urun,
    siparis_odeme=:siparis_odeme,
    siparis_odemeid=:siparis_odemeid,
    siparis_durumpay=:siparis_durumpay,
    siparis_adres=:siparis_adres,
    siparis_il=:siparis_il,
    siparis_ilce=:siparis_ilce,
    siparis_fatura_vn=:fvn,
    siparis_fatura_vd=:fvd,
    siparis_fatura_unvan=:funv,
    siparis_fatura_adres=:fad,
    siparis_durum=:durum
    WHERE siparis_id=:siparis_id"
  );
  $update     = $ayarkaydet->execute(
    array(
      'not'     => trim(strip_tags((string) $_POST['siparis_not'])),
      'fiyat'     => $fiyatVal,
      'siparis_ip'     => $inovanceprint[ 'siparis_ip' ],
      'siparis_ad'     => trim(strip_tags((string) $_POST['siparis_ad'])),
      'siparis_tel'     => trim(strip_tags((string) $_POST['siparis_tel'])),
      'siparis_urun'     => trim(strip_tags((string) $_POST['siparis_urun'])),
      'siparis_odeme'     => trim(strip_tags((string) $_POST['siparis_odeme'])),
      'siparis_odemeid'     => $inovanceprint[ 'siparis_odemeid' ],
      'siparis_durumpay'     => $inovanceprint[ 'siparis_durumpay' ],
      'siparis_adres'     => trim(strip_tags((string) $_POST['siparis_adres'])),
      'siparis_il'     => trim(strip_tags((string) $_POST['siparis_il'])),
      'siparis_ilce'     => trim(strip_tags((string) $_POST['siparis_ilce'])),
      'fvn'     => mb_substr(trim(strip_tags((string) ($_POST['siparis_fatura_vn'] ?? ''))), 0, 20),
      'fvd'     => mb_substr(trim(strip_tags((string) ($_POST['siparis_fatura_vd'] ?? ''))), 0, 120),
      'funv'     => mb_substr(trim(strip_tags((string) ($_POST['siparis_fatura_unvan'] ?? ''))), 0, 255),
      'fad'     => mb_substr(trim(strip_tags((string) ($_POST['siparis_fatura_adres'] ?? ''))), 0, 2000),
      'durum'     => (int) $_POST['siparis_durum'],
      'siparis_id' => $sid
    )
  );
  $Id = $sid;
  $locBase = '?siparis_id=' . rawurlencode((string) $Id) . '&status=';
  $locPopup = $isPopup ? '&popup=1' : '';
  if ( $update )
  {
    Header( 'Location: ' . $locBase . 'ok' . $locPopup );
    exit;
  }
  else
  {
    Header( 'Location: ' . $locBase . 'no' . $locPopup );
    exit;
  }
}


$ipSecurepr = false;
$IpBan = 0;
if (isset($inovanceprint['siparis_ip']) && !empty($inovanceprint['siparis_ip'])) {
  $ipSecure=$db->prepare("SELECT * from ip where ip=:user_ip");
  $ipSecure->execute(array(
    'user_ip' => $inovanceprint['siparis_ip']
  ));
  $ipSecurepr=$ipSecure->fetch(PDO::FETCH_ASSOC);
  $IpBan = $ipSecure->rowCount();
}

$telSecurepr = false;
$TelBan = 0;
if (isset($inovanceprint['siparis_tel']) && !empty($inovanceprint['siparis_tel'])) {
  $telSecure = $db->prepare("SELECT * from tel_engelle WHERE tel=:tel");
  try {
    $telSecure->execute(array('tel' => $inovanceprint['siparis_tel']));
    $telSecurepr = $telSecure->fetch(PDO::FETCH_ASSOC);
    $TelBan = $telSecure->rowCount();
  } catch (Exception $e) {
    $TelBan = 0;
  }
}

if ($isPopup && isset($_GET['status'])) {
    $st = $_GET['status'];
    if ($st === 'ok') {
        echo '<script>try{if(window.parent&&window.parent!==window){window.parent.postMessage({type:"siparis-detay-saved",ok:true},location.origin);}}catch(e){}</script>';
    } elseif ($st === 'no') {
        echo '<script>try{if(window.parent&&window.parent!==window){window.parent.postMessage({type:"siparis-detay-saved",ok:false},location.origin);}}catch(e){}</script>';
    }
}

$parasut_ready = !empty($settingsprint['ayar_parasut_enabled'])
    && trim((string) ($settingsprint['ayar_parasut_company_id'] ?? '')) !== ''
    && trim((string) ($settingsprint['ayar_parasut_client_id'] ?? '')) !== ''
    && trim((string) ($settingsprint['ayar_parasut_client_secret'] ?? '')) !== ''
    && trim((string) ($settingsprint['ayar_parasut_username'] ?? '')) !== ''
    && trim((string) ($settingsprint['ayar_parasut_password'] ?? '')) !== '';
$parasut_sent_id = isset($inovanceprint['siparis_parasut_invoice_id']) ? trim((string) $inovanceprint['siparis_parasut_invoice_id']) : '';
?>
<style>.btn-parasut{background:#00b8a9;color:#fff;border-color:#009688}.btn-parasut:hover,.btn-parasut:focus{background:#009688;color:#fff}</style>
<!-- ============================================================== -->
<!--            Content Start             -->
<!-- ============================================================== -->
<section class="main-content container<?php echo $isPopup ? ' sd-popup' : ''; ?>">
  <div class="page-header">
    <h2>Sipariş Detay</h2>
  </div>
  <div class="row">
    <div class="col-sm-12">
      <div class="card">
        <div class="card-block">
          <div class="row">
            <div class="<?php echo $isPopup ? 'col-md-12' : 'col-md-6'; ?>">
              <?php if ($isPopup) { ?>
              <div class="sd-meta-stack">
                <div class="sd-meta-item">
                  <h4>Sipariş No <small>#00<?php echo isset($inovanceprint['siparis_id']) ? $inovanceprint['siparis_id'] : ''; ?></small></h4>
                </div>
                <?php if (isset($inovanceprint['siparis_odemeid']) && $inovanceprint['siparis_odemeid']==4) { ?>
                <div class="sd-meta-item">
                  <h4>Kredi kartı ödemesi <small><?php if (isset($inovanceprint['siparis_durumpay']) && $inovanceprint['siparis_durumpay']==1) { ?>Başarılı<?php } else { ?>Başarısız<?php } ?></small></h4>
                </div>
                <?php } ?>
                <div class="sd-meta-item">
                  <h4>Sipariş IP <small><?php echo htmlspecialchars(isset($inovanceprint['siparis_ip']) ? $inovanceprint['siparis_ip'] : '', ENT_QUOTES, 'UTF-8'); ?></small></h4>
                  <?php if ($IpBan>=1 && isset($ipSecurepr['id'])) { ?>
                  <a href="controller/function.php?ipsilx=ok&id=<?php echo $ipSecurepr['id']; ?>&sip=<?php echo isset($inovanceprint['siparis_id']) ? $inovanceprint['siparis_id'] : ''; ?>" title="Sil" class="btn btn-sm btn-danger">IP ENGELİ KALDIR</a>
                  <?php } else { ?>
                  <form method="POST" action="controller/function.php" class="form-horizontal" enctype="multipart/form-data">
                    <div class="form-group">
                      <input type="hidden" name="ip" value="<?php echo htmlspecialchars(isset($inovanceprint['siparis_ip']) ? $inovanceprint['siparis_ip'] : '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                      <input type="hidden" name="id" value="<?php echo isset($inovanceprint['siparis_id']) ? $inovanceprint['siparis_id'] : ''; ?>" class="form-control">
                    </div>
                    <button style="cursor: pointer;" type="submit" name="ipeklex" class="btn btn-warning">IP ENGELLE</button>
                  </form>
                  <?php } ?>
                </div>
                <div class="sd-meta-item">
                  <h4>Sipariş Telefon <small><?php echo htmlspecialchars(isset($inovanceprint['siparis_tel']) ? $inovanceprint['siparis_tel'] : '', ENT_QUOTES, 'UTF-8'); ?></small></h4>
                  <?php if ($TelBan>=1 && isset($telSecurepr['id'])) { ?>
                  <a href="controller/function.php?telsilx=ok&id=<?php echo $telSecurepr['id']; ?>&sip=<?php echo isset($inovanceprint['siparis_id']) ? $inovanceprint['siparis_id'] : ''; ?>" title="Sil" class="btn btn-sm btn-danger">TELEFON ENGELİ KALDIR</a>
                  <?php } else { ?>
                  <form method="POST" action="controller/function.php" class="form-horizontal" enctype="multipart/form-data">
                    <div class="form-group">
                      <input type="hidden" name="tel" value="<?php echo htmlspecialchars($inovanceprint['siparis_tel'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                      <input type="hidden" name="id" value="<?php echo $inovanceprint['siparis_id']; ?>" class="form-control">
                    </div>
                    <button style="cursor: pointer;" type="submit" name="teleklex" class="btn btn-warning">TELEFON ENGELLE</button>
                  </form>
                  <?php } ?>
                </div>
              </div>
              <?php } else { ?>
              <h4>Sipariş No : <small>#00<?php echo isset($inovanceprint['siparis_id']) ? $inovanceprint['siparis_id'] : ''; ?></small></h4>
              <?php if (isset($inovanceprint['siparis_odemeid']) && $inovanceprint['siparis_odemeid']==4) { ?>
                <h4>Kredi Kartı Ödeme : <small><?php if (isset($inovanceprint['siparis_durumpay']) && $inovanceprint['siparis_durumpay']==1) { ?> Başarılı <?php } else { ?> Başarısız <?php } ?> </small></h4>
              <?php } ?>
              <h4>Sipariş IP : <small><?php echo htmlspecialchars(isset($inovanceprint['siparis_ip']) ? $inovanceprint['siparis_ip'] : '', ENT_QUOTES, 'UTF-8'); ?></small></h4>
              <?php if ($IpBan>=1 && isset($ipSecurepr['id'])) { ?>
                <a href="controller/function.php?ipsilx=ok&id=<?php echo $ipSecurepr['id']; ?>&sip=<?php echo isset($inovanceprint['siparis_id']) ? $inovanceprint['siparis_id'] : ''; ?>" title="Sil" class="btn btn-sm btn-danger">IP ENGELİ KALDIR</a>
              <?php } else { ?>
                <form method="POST" action="controller/function.php" class="form-horizontal" enctype="multipart/form-data" style="display: inline-block;">
                  <div class="form-group">
                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars(isset($inovanceprint['siparis_ip']) ? $inovanceprint['siparis_ip'] : '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                    <input type="hidden" name="id" value="<?php echo isset($inovanceprint['siparis_id']) ? $inovanceprint['siparis_id'] : ''; ?>" class="form-control">
                  </div>
                  <button style="cursor: pointer;" type="submit" name="ipeklex" class="btn btn-warning">IP ENGELLE</button>
                </form>
              <?php } ?>
              <br><br>
              <h4>Sipariş Telefon : <small><?php echo htmlspecialchars(isset($inovanceprint['siparis_tel']) ? $inovanceprint['siparis_tel'] : '', ENT_QUOTES, 'UTF-8'); ?></small></h4>
              <?php if ($TelBan>=1 && isset($telSecurepr['id'])) { ?>
                <a href="controller/function.php?telsilx=ok&id=<?php echo $telSecurepr['id']; ?>&sip=<?php echo isset($inovanceprint['siparis_id']) ? $inovanceprint['siparis_id'] : ''; ?>" title="Sil" class="btn btn-sm btn-danger">TELEFON ENGELİ KALDIR</a>
              <?php } else { ?>
                <form method="POST" action="controller/function.php" class="form-horizontal" enctype="multipart/form-data" style="display: inline-block;">
                  <div class="form-group">
                    <input type="hidden" name="tel" value="<?php echo htmlspecialchars($inovanceprint['siparis_tel'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                    <input type="hidden" name="id" value="<?php echo $inovanceprint['siparis_id']; ?>" class="form-control">
                  </div>
                  <button style="cursor: pointer;" type="submit" name="teleklex" class="btn btn-warning">TELEFON ENGELLE</button>
                </form>
              <?php } ?>
              <?php } ?>
            </div>
          </div>
        </div>
        <div class="card-block">
         <form action="" method="POST">
           <h3>Bilgileri Düzenle</h3>
           <div class="row<?php echo $isPopup ? ' siparis-form-grid' : ''; ?>">
             <div class="form-group col-md-4">
               <label>Sipariş Ad</label>
               <input type="text" name="siparis_ad" value="<?php echo htmlspecialchars($inovanceprint['siparis_ad'], ENT_QUOTES, 'UTF-8'); ?>">
             </div>
             <div class="form-group col-md-4">
               <label>Sipariş Tel</label>
               <input type="text" name="siparis_tel" value="<?php echo htmlspecialchars($inovanceprint['siparis_tel'], ENT_QUOTES, 'UTF-8'); ?>">
             </div> 
             <div class="form-group col-md-4">
               <label>Ödeme Yöntemi</label>
               <input type="text" name="siparis_odeme" value="<?php echo htmlspecialchars($inovanceprint['siparis_odeme'], ENT_QUOTES, 'UTF-8'); ?>">
             </div>
             <div class="form-group col-md-4">
               <label>Ürün</label>
               <textarea name="siparis_urun" rows="4" class="form-control"><?php echo htmlspecialchars(strip_tags($inovanceprint['siparis_urun']), ENT_QUOTES, 'UTF-8'); ?></textarea>
             </div>
             <div class="form-group col-md-4">
               <label>Sipariş Fiyat (₺)</label>
               <div class="input-group">
                 <span class="input-group-addon"><i class="fa fa-try"></i></span>
                 <input type="text" name="siparis_fiyat" value="<?php echo isset($inovanceprint['siparis_fiyat']) ? number_format($inovanceprint['siparis_fiyat'], 2, '.', '') : '0.00'; ?>" class="form-control" placeholder="0.00">
               </div>
             </div>
             <div class="form-group col-md-4">
              <label>Sipariş Şehir</label>
              <input type="text" name="siparis_il" value="<?php echo htmlspecialchars($inovanceprint['siparis_il'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
             <div class="form-group col-md-4">
              <label>Sipariş İlçe</label>
              <input type="text" name="siparis_ilce" value="<?php echo htmlspecialchars($inovanceprint['siparis_ilce'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-group col-md-6">
             <label>Sipariş Adres: </label>
             <textarea style="height: 90px;" name="siparis_adres"><?php echo htmlspecialchars($inovanceprint['siparis_adres'], ENT_QUOTES, 'UTF-8'); ?></textarea>
           </div>
           <div class="form-group col-md-12">
             <h4 class="<?php echo $isPopup ? 'sd-subsection-title' : ''; ?>"<?php echo $isPopup ? '' : ' style="margin-top: 10px;"'; ?>><i class="fa fa-building-o"></i> Kurumsal fatura</h4>
           </div>
           <div class="form-group col-md-4">
             <label>Vergi numarası</label>
             <input type="text" name="siparis_fatura_vn" maxlength="20" value="<?php echo htmlspecialchars(isset($inovanceprint['siparis_fatura_vn']) ? $inovanceprint['siparis_fatura_vn'] : '', ENT_QUOTES, 'UTF-8'); ?>">
           </div>
           <div class="form-group col-md-4">
             <label>Vergi dairesi</label>
             <input type="text" name="siparis_fatura_vd" maxlength="120" value="<?php echo htmlspecialchars(isset($inovanceprint['siparis_fatura_vd']) ? $inovanceprint['siparis_fatura_vd'] : '', ENT_QUOTES, 'UTF-8'); ?>">
           </div>
           <div class="form-group col-md-4">
             <label>Firma ünvanı</label>
             <input type="text" name="siparis_fatura_unvan" maxlength="255" value="<?php echo htmlspecialchars(isset($inovanceprint['siparis_fatura_unvan']) ? $inovanceprint['siparis_fatura_unvan'] : '', ENT_QUOTES, 'UTF-8'); ?>">
           </div>
           <div class="form-group col-md-12">
             <label>Fatura adresi</label>
             <textarea style="height: 80px;" name="siparis_fatura_adres"><?php echo htmlspecialchars(isset($inovanceprint['siparis_fatura_adres']) ? $inovanceprint['siparis_fatura_adres'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
           </div>
           <div class="form-group col-md-6">
             <label>Sipariş Not: (Sadece admin görür özel not alanıdır!)</label>
             <textarea style="height: 90px;" name="siparis_not"><?php echo htmlspecialchars($inovanceprint['siparis_not'], ENT_QUOTES, 'UTF-8'); ?></textarea>
           </div>
           <div class="form-group col-md-12">
             <label>Sipariş Durum</label>
             <select name="siparis_durum" class="form-control m-b">
              <option value="0">YENİ GELEN SİPARİŞ</option>
              <?php $urunsor=$db->prepare("SELECT * from durum order by siralama ASC, id ASC");
              $urunsor->execute();
              foreach ($urunsor as $key) {  ?>
               <option value="<?= (int) $key['id'] ?>" <?php if ($key['id']==$inovanceprint['siparis_durum']) { echo "selected"; } ?>><?= htmlspecialchars($key['ad'], ENT_QUOTES, 'UTF-8') ?></option>
             <?php } ?>
           </select>
         </div>
         <div class="form-group col-md-12">
           <button type="submit" style="cursor: pointer;" name="durum" title="Tamamla" class="btn btn-success btn-sm">KAYDET</button>
         </div>
       </div>
     </form>

     <br>
     <?php 
     $siparissor=$db->prepare("SELECT * from siparis where ( siparis_ip=:siparis_ip || siparis_tel=:siparis_tel ) and siparis_id!=:siparis_id");
     $siparissor->execute(array(
      'siparis_tel' => $inovanceprint['siparis_tel'],
      'siparis_ip' => $inovanceprint['siparis_ip'],
      'siparis_id' => $inovanceprint['siparis_id']
    )); 
     $Varmi = $siparissor->rowCount();
     if ($Varmi>=1) {
       ?>
       <form action="" method="POST">

        <h3>Aynı İp veya Telefon İle Gelen Siparişler</h3>
        <table class="table table-striped dt-responsive nowrap">
          <thead>
            <tr>
              <th>
                <strong>Sipariş Tarih</strong>
              </th>
              <th>
                <strong>Sipariş NO</strong>
              </th>
              <th>
                <strong>Sipariş IP</strong>
              </th>
              <th>
                <strong>Sipariş Ad</strong>
              </th>
              <th>
                <strong>Şehir</strong>
              </th>
              <th>
                <strong>Sipariş Adres</strong>
              </th>
              <th>
                <strong>Sipariş ürün</strong>
              </th>
              <th>
                <strong>Sipariş Tel</strong>
              </th>
              <th>
                <strong>Sipariş Ödeme</strong>
              </th>
              <th class="text-center">
                <strong>İşlemler</strong>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php 
            while ($sipariscek=$siparissor->fetch(PDO::FETCH_ASSOC)) {
              ?>
              <tr>
                <td><?php echo htmlspecialchars($sipariscek['siparis_tarih'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $sipariscek['siparis_id']; ?></td>
                <td><?php echo htmlspecialchars($sipariscek['siparis_ip'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($sipariscek['siparis_ad'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($sipariscek['siparis_il'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($sipariscek['siparis_adres'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <?php 
                  $cleanSameIpUrun = preg_replace('/\|[0-9]+/', '', (string) $sipariscek['siparis_urun']);
                  echo htmlspecialchars(strip_tags($cleanSameIpUrun), ENT_QUOTES, 'UTF-8');
                  ?>
                </td>
                <td><?php echo htmlspecialchars($sipariscek['siparis_tel'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($sipariscek['siparis_odeme'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="text-center">
                  <a href="siparis-detay.php?siparis_id=<?php echo $sipariscek['siparis_id']; ?>" title="Göster" class="btn btn-sm btn-default"><i class="fa fa-eye"></i></a>
                  <a href="controller/function.php?siparissil=ok&siparis_id=<?php echo $sipariscek['siparis_id']; ?>" title="Sil" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>            
      </form>


      <br>
    <?php } ?>

    <div class="row">
     <div class="col-md-12 text-right">
      <div class="<?php echo $isPopup ? 'sd-footer-actions' : ''; ?>">
       <button type="button" class="btn btn-success" onclick="window.print();"><i class="fa fa-print"></i> Yazdır</button>
       <?php if ($parasut_ready) { ?>
       <button type="button" class="btn btn-parasut btn-siparis-parasut-gonder" data-siparis-id="<?php echo (int) $inovanceprint['siparis_id']; ?>"><i class="fa fa-paper-plane"></i> Paraşüt'e gönder</button>
       <?php if ($parasut_sent_id !== '') { ?><span class="text-muted small" style="align-self:center;margin-left:4px;">Paraşüt kayıt: <?php echo htmlspecialchars($parasut_sent_id, ENT_QUOTES, 'UTF-8'); ?></span><?php } ?>
       <?php } ?>
       <?php if ($isPopup) { ?>
       <a href="#" title="Sil" class="btn btn-danger btn-icon btn-siparis-sil-popup" data-siparis-id="<?php echo (int) $inovanceprint['siparis_id']; ?>"><i class="fa fa-trash-o"></i> Siparişi Sil</a>
       <?php } else { ?>
       <a href="controller/function.php?siparissil=ok&siparis_id=<?php echo (int) $inovanceprint['siparis_id']; ?>" title="Sil" class="btn btn-danger btn-icon" onclick="return confirm('Bu sipariş kalıcı olarak silinecek. Emin misiniz?');"><i class="fa fa-trash-o"></i> Siparişi Sil</a>
       <a href="siparisler.php?drm=<?php echo (int) $inovanceprint['siparis_durum']; ?>" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i> Geri Dön</a>
       <?php } ?>
     </div>
   </div>
 </div>

</div>
</div>
</div>
</div>

<script>
$(function () {
    function parasutHandle($btn, id, force) {
        $btn.prop('disabled', true);
        $.ajax({
            url: 'controller/parasut_send.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ siparis_id: id, force: !!force }),
            dataType: 'json'
        }).done(function (r) {
            $btn.prop('disabled', false);
            var row = (r && r.results && r.results[0]) ? r.results[0] : null;
            if (row && row.ok) {
                alert('Paraşüt satış faturası oluşturuldu. Kayıt ID: ' + row.parasut_invoice_id);
                try {
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({ type: 'siparis-parasut-sent', ok: true, siparis_id: id }, window.location.origin);
                    }
                } catch (e1) {}
                window.location.reload();
                return;
            }
            var err = row && row.error ? row.error : ((r && r.error) ? r.error : 'Bilinmeyen hata');
            if ((err.indexOf('zaten') !== -1 || err.indexOf('gönderilmiş') !== -1) &&
                    confirm(err + '\n\nYine de zorla yeni fatura denensin mi? (Çift kayıt riski.)')) {
                parasutHandle($btn, id, true);
                return;
            }
            alert(err);
        }).fail(function () {
            $btn.prop('disabled', false);
            alert('Bağlantı hatası.');
        });
    }
    $(document).on('click', '.btn-siparis-parasut-gonder', function (e) {
        e.preventDefault();
        var id = $(this).data('siparis-id');
        if (!id || !confirm('Bu sipariş Paraşüt’te satış faturası olarak gönderilsin mi?')) return;
        parasutHandle($(this), id, false);
    });
});
</script>
<?php 
if (!$isPopup) {
    include 'footer.php';
} else {
    ?>
    <script>
    $(function () {
        $(document).on('click', '.btn-siparis-sil-popup', function (e) {
            e.preventDefault();
            if (!confirm('Bu sipariş kalıcı olarak silinecek. Emin misiniz?')) return;
            var id = $(this).data('siparis-id');
            $.ajax({
                url: 'controller/function.php',
                type: 'GET',
                data: { siparissil: 'ok', siparis_id: id, popup_json: '1' },
                dataType: 'json'
            }).done(function (r) {
                if (r && r.ok && window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'siparis-detay-deleted', ok: true, drm: r.drm }, window.location.origin);
                } else {
                    alert('Sipariş silinemedi.');
                }
            }).fail(function (xhr) {
                var msg = 'Sipariş silinemedi.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r && r.error === 'auth') msg = 'Oturum süresi dolmuş olabilir. Sayfayı yenileyin.';
                } catch (err) {}
                alert(msg);
            });
        });
    });
    </script>
    <?php
    echo '</body></html>';
}
?>
