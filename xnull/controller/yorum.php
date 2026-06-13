<?php 
include 'config.php';


if (!empty($_FILES)) {


	$folder_name = 'assets/img/yorumlar';
	$uploads_dir = dirname(__DIR__) . '/' . $folder_name;
	
	// Klasör yoksa oluştur
	if (!is_dir($uploads_dir)) {
		@mkdir($uploads_dir, 0777, true);
	}

	@$tmp_name = $_FILES['file']["tmp_name"];
	@$name = $_FILES['file']["name"];
	$ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg';
	
	$benzersizsayi1=rand(20000,32000);
	$benzersizad = $benzersizsayi1 . "." . $ext;
	
	$refimgyol = $folder_name . "/" . $benzersizad;
	$full_destination = $uploads_dir . "/" . $benzersizad;

	if (@move_uploaded_file($tmp_name, $full_destination)) {
		$kaydet=$db->prepare("INSERT INTO yorum_gorsel SET
			yorum=:yorum,
			gorsel=:gorsel
			");
		$insert=$kaydet->execute(array(
			'yorum' => $_POST['yorum'],
			'gorsel' => $refimgyol
		));
	}

	
}


?>
