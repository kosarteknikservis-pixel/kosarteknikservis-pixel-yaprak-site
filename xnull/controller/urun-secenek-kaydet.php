<?php
include 'config.php';
$demoCont=$db->prepare("SELECT * from demo where id=1");

$demoCont->execute(array());

$demoControl=$demoCont->fetch(PDO::FETCH_ASSOC);

$DemCont = $demoControl['durum'];
date_default_timezone_set( 'Europe/Istanbul' );
if($_POST){
	if ($DemCont==1) {

		echo json_encode(array('status' => false, 'url' => '?demo=ok'));
		exit;
	}
	$body = json_decode(file_get_contents("php://input"));
	if (!is_object($body) || !isset($body->variants)) {
		echo json_encode(array('status' => false, 'url' => ''));
		exit;
	}
	$urun_id = isset($body->urun_id) ? (int) $body->urun_id : 0;
	if ($urun_id <= 0) {
		echo json_encode(array('status' => false, 'url' => ''));
		exit;
	}

	$urunkaydet = $db->prepare(
		"UPDATE urunler SET secenekler = :sec WHERE urun_id = :uid"
	);
	$update = $urunkaydet->execute(
		array(
			'sec' => json_encode($body->variants),
			'uid' => $urun_id,
		)
	);
	
	if ( $update )
	{
		echo json_encode(array('status' => true, 'url' => 'urunler.php?status=ok'));
	}
	else
	{
		
		echo json_encode(array('status' => false, 'url' => ''));
	}
}
