<?php
date_default_timezone_set('Europe/Istanbul');
require_once ('controller/config.php'); 
if (isset($_SESSION['kullanici_adi'])) {
} else { 
  header("Location:login.php");
  exit;
}
$settings=$db->prepare("SELECT * from ayar where ayar_id=?");
$settings->execute(array(0));
$settingsprint=$settings->fetch(PDO::FETCH_ASSOC);
$user=$db->prepare("SELECT * from kullanici where kullanici_id=?");
$user->execute(array(0));
$userprint=$user->fetch(PDO::FETCH_ASSOC);

if (@$title) {
 $title = $title;
} else {
  $title = "Yönetim Paneli";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5">
  <title><?=$title?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $settingsprint['ayar_fav']; ?>">

  <script src="assets/js/jquery.min.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>


  <!-- DropZone -->
  <link rel="stylesheet" href="assets/dropzone/dropzone.css" />
  
  <!-- Common Plugins -->
  <link href="assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Sweet Alerts -->
  <link href="assets/lib/sweet-alerts2/sweetalert2.min.css" rel="stylesheet">

  <!-- Summernote -->
  <link href="assets/lib/summernote/summernote.css" rel="stylesheet">
  
  <!-- Vector Map Css-->
  <link href="assets/lib/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet" />

  <!-- DataTables -->
  <link href="assets/lib/datatables/jquery.dataTables.min.css" rel="stylesheet" type="text/css">
  <link href="assets/lib/datatables/responsive.bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="assets/lib/toast/jquery.toast.min.css" rel="stylesheet">
  <link href="assets/lib/datatables/buttons.dataTables.css" rel="stylesheet" type="text/css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.1/css/font-awesome.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.min.css">
  <!-- gizlenecek alan mobilde -->

  <!-- Custom Css-->
  <link href="assets/css/style.css?v=<?php echo PANEL_ASSET_VER; ?>" rel="stylesheet">
  <link href="assets/css/mobile-table.css?v=<?php echo PANEL_ASSET_VER; ?>" rel="stylesheet">
  <link href="assets/css/mobile-viewport.css?v=<?php echo PANEL_VIEWPORT_CSS_VER; ?>" rel="stylesheet">
  
  <!-- Datatables Export Dependencies (Moved to Header for Global Availability) -->
  <script src="assets/lib/datatables/jquery.dataTables.min.js"></script>
  <script src="assets/lib/datatables/dataTables.buttons.min.js"></script>
  <script src="assets/lib/datatables/jszip.min.js"></script>
  <script src="assets/lib/datatables/pdfmake.min.js"></script>
  <script src="assets/lib/datatables/vfs_fonts.js"></script>
  <script src="assets/lib/datatables/buttons.html5.min.js"></script>

  <!-- Mobile Table JS -->
  <script src="assets/js/mobile-table.js?v=<?php echo PANEL_ASSET_VER; ?>"></script>
  <style>
  /* Masaüstü varsayılan; mobil (991px↓) mobile-viewport.css ile html/body overflow ve .main-content scroll */
  html, body {
    overflow-x: hidden;
    margin: 0;
    padding: 0;
  }
  html {
    min-height: 100%;
  }
  body {
    position: relative;
    min-height: 100%;
  }
  
  /* Logo Küçültme */
  .admin-logo {
    width: auto !important;
    min-width: auto !important;
    padding: 0 8px !important;
    margin-left: 0 !important;
  }
  .admin-logo h1 {
    font-size: 14px !important;
    line-height: 1.2 !important;
    height: auto !important;
  }
  .admin-logo .logo-icon {
    width: 28px !important;
    height: 28px !important;
    margin-right: 5px !important;
  }
  </style>

  <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
          <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
        <script>
  function copyToClipboard(text, btn) {
      var dummy = document.createElement("textarea");
      document.body.appendChild(dummy);
      dummy.value = text;
      dummy.select();
      document.execCommand("copy");
      document.body.removeChild(dummy);
      
      const originalHtml = btn.innerHTML;
      btn.innerHTML = '<i class="fa fa-check"></i>';
      btn.classList.add('btn-success');
      btn.classList.remove('btn-default');
      
      setTimeout(() => {
          btn.innerHTML = originalHtml;
          btn.classList.remove('btn-success');
          btn.classList.add('btn-default');
      }, 1500);
  }
  </script>
</head>
      <body>
