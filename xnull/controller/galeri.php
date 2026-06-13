<?php

include 'config.php';

header('Content-Type: application/json; charset=utf-8');

function galeri_json_response($ok, $message = '', $extra = array())
{
	$payload = array_merge(array('status' => $ok ? 'ok' : 'error', 'message' => $message), $extra);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

if (empty($_SESSION['kullanici_adi'])) {
	galeri_json_response(false, 'Oturum süresi dolmuş. Panele tekrar giriş yapın.');
}

if (empty($_FILES['file'])) {
	galeri_json_response(false, 'Dosya gelmedi.');
}

$file = $_FILES['file'];
if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
	$uploadErrors = array(
		UPLOAD_ERR_INI_SIZE   => 'Dosya sunucu limitinden büyük (upload_max_filesize).',
		UPLOAD_ERR_FORM_SIZE  => 'Dosya form limitinden büyük.',
		UPLOAD_ERR_PARTIAL    => 'Yükleme yarım kaldı, tekrar deneyin.',
		UPLOAD_ERR_NO_FILE    => 'Dosya seçilmedi.',
		UPLOAD_ERR_NO_TMP_DIR => 'Sunucuda geçici klasör yok.',
		UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı.',
		UPLOAD_ERR_EXTENSION  => 'PHP eklentisi yüklemeyi durdurdu.',
	);
	$code = (int) $file['error'];
	galeri_json_response(false, isset($uploadErrors[$code]) ? $uploadErrors[$code] : 'Yükleme hatası (kod: ' . $code . ').');
}

$tmp_name = (string) $file['tmp_name'];
$name = (string) $file['name'];
if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
	galeri_json_response(false, 'Geçersiz yükleme isteği.');
}

$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov');
if (!in_array($ext, $allowed_exts, true)) {
	galeri_json_response(false, 'Bu dosya tipi kabul edilmiyor.');
}

$uploads_dir = dirname(__DIR__) . '/assets/img/galeri';
if (!is_dir($uploads_dir) && !@mkdir($uploads_dir, 0755, true)) {
	galeri_json_response(false, 'Galeri klasörü oluşturulamadı.');
}

$benzersizad = rand(20000, 32000) . rand(20000, 32000) . '.' . $ext;
$hedef_dosya = $uploads_dir . '/' . $benzersizad;
$refimgyol = 'assets/img/galeri/' . $benzersizad;

$isVideo = in_array($ext, array('mp4', 'webm', 'mov'), true) ? 1 : 0;
$isGif = ($ext === 'gif') ? 1 : 0;

$saved = false;

if ($isVideo || $isGif) {
	$saved = @move_uploaded_file($tmp_name, $hedef_dosya);
	if (!$saved) {
		galeri_json_response(false, 'Video/GIF dosyası kaydedilemedi.');
	}
} else {
	$info = @getimagesize($tmp_name);
	$width = ($info && isset($info[0])) ? (int) $info[0] : 0;
	$height = ($info && isset($info[1])) ? (int) $info[1] : 0;
	$pixelCount = ($width > 0 && $height > 0) ? ($width * $height) : 0;
	$fileBytes = isset($file['size']) ? (int) $file['size'] : 0;

	// Uzun infografik / büyük görseller: GD ile yeniden işleme bellek patlatır — doğrudan kopyala
	$skipGd = ($pixelCount > 12000000) || ($height > 8000) || ($width > 8000) || ($fileBytes > 15 * 1024 * 1024);

	if ($skipGd) {
		$saved = @move_uploaded_file($tmp_name, $hedef_dosya);
	} else {
		@ini_set('memory_limit', '512M');
		$sourceImage = null;
		switch ($ext) {
			case 'jpg':
			case 'jpeg':
				$sourceImage = @imagecreatefromjpeg($tmp_name);
				break;
			case 'png':
				$sourceImage = @imagecreatefrompng($tmp_name);
				break;
			case 'webp':
				if (function_exists('imagecreatefromwebp')) {
					$sourceImage = @imagecreatefromwebp($tmp_name);
				}
				break;
		}

		if ($sourceImage) {
			$image = imagecreatetruecolor($width, $height);
			if ($image) {
				if ($ext === 'png' || $ext === 'webp') {
					imagealphablending($image, false);
					imagesavealpha($image, true);
					$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
					imagefill($image, 0, 0, $transparent);
				}
				imagecopyresampled($image, $sourceImage, 0, 0, 0, 0, $width, $height, $width, $height);
				switch ($ext) {
					case 'jpg':
					case 'jpeg':
						$saved = @imagejpeg($image, $hedef_dosya, 92);
						break;
					case 'png':
						$saved = @imagepng($image, $hedef_dosya, 6);
						break;
					case 'webp':
						$saved = function_exists('imagewebp') ? @imagewebp($image, $hedef_dosya, 90) : false;
						break;
				}
				imagedestroy($image);
			}
			imagedestroy($sourceImage);
		}

		if (!$saved) {
			$saved = @move_uploaded_file($tmp_name, $hedef_dosya);
		}
	}

	if (!$saved || !is_file($hedef_dosya)) {
		galeri_json_response(false, 'Görsel kaydedilemedi. Dosyayı küçültüp tekrar deneyin.');
	}
}

$siraQuery = $db->prepare('SELECT COALESCE(MAX(sira), 0) + 1 AS yeni_sira FROM resimgaleri');
$siraQuery->execute();
$siraResult = $siraQuery->fetch(PDO::FETCH_ASSOC);
$yeniSira = isset($siraResult['yeni_sira']) ? (int) $siraResult['yeni_sira'] : 1;

$kaydet = $db->prepare('INSERT INTO resimgaleri SET resim_baslik=:baslik, resim_link=:rs, video=:video, sira=:sira');
$insert = $kaydet->execute(array(
	'baslik' => $name,
	'rs'     => $refimgyol,
	'video'  => $isVideo,
	'sira'   => $yeniSira,
));

if (!$insert) {
	@unlink($hedef_dosya);
	galeri_json_response(false, 'Veritabanına kayıt yapılamadı.');
}

galeri_json_response(true, 'Yüklendi.', array('path' => $refimgyol));
