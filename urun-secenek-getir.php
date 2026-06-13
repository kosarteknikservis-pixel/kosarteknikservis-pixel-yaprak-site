<?php 
include 'xnull/controller/config.php';
require_once __DIR__ . '/include/panel_urun_secenek_html.php';

if($_POST || $json_input = file_get_contents('php://input')){
  // Handle both standard POST and JSON body
  $data = $_POST;
  if (empty($data) && !empty($json_input)) {
    $data = json_decode($json_input, true);
  }

  // Support both 'urun_id' and 'id'
  $gelen_id = null;
  if (isset($data['urun_id'])) {
      $gelen_id = $data['urun_id'];
  } elseif (isset($data['id'])) {
      $gelen_id = $data['id'];
  }
  
  // Güvenlik kontrolü
  if (!$gelen_id) {
    echo ''; 
    exit;
  }
  
  $urun_id = intval($gelen_id);
  if ($urun_id <= 0) {
    echo '';
    exit;
  }

  $urunSecenekGetir=$db->prepare("SELECT * from urunler WHERE urun_id = :urun_id");
  $urunSecenekGetir->execute(array('urun_id' => $urun_id));
  $urunSecenek=$urunSecenekGetir->fetch(PDO::FETCH_ASSOC);

  if ($urunSecenek && $urunSecenek['secenekler'] != '') {
    $secenekHtml = panel_build_urun_secenek_html($urunSecenek);
    if ($secenekHtml !== '') {
      echo $secenekHtml;
    }
  }
}
