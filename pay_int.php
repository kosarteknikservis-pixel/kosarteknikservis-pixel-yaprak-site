<?php
include 'xnull/controller/config.php'; 

date_default_timezone_set('Europe/Istanbul');
$settings=$db->prepare("SELECT * from ayar where ayar_id=?");
$settings->execute(array(0));
$settingsprint=$settings->fetch(PDO::FETCH_ASSOC);

$social=$db->prepare("SELECT * from sosyal");
$social->execute();

$motor=$db->prepare("SELECT * from motor where motor_id=1");
$motor->execute();
$motorprint=$motor->fetch(PDO::FETCH_ASSOC);

$whatsapp=$db->prepare("SELECT * from whatsapp where whats_id=0");
$whatsapp->execute();
$whatsappprint=$whatsapp->fetch(PDO::FETCH_ASSOC);


$paytr=$db->prepare("SELECT * from paytr where paytr_id=?");
$paytr->execute(array(1));
$paytrprint=$paytr->fetch(PDO::FETCH_ASSOC);
	## 2. ADIM için örnek kodlar ##

	## ÖNEMLİ UYARILAR ##
	## 1) Bu sayfaya oturum (SESSION) ile veri taşıyamazsınız. Çünkü bu sayfa müşterilerin yönlendirildiği bir sayfa değildir.
	## 2) Entegrasyonun 1. ADIM'ında gönderdiğniz merchant_oid değeri bu sayfaya POST ile gelir. Bu değeri kullanarak
	## veri tabanınızdan ilgili siparişi tespit edip onaylamalı veya iptal etmelisiniz.
	## 3) Aynı sipariş için birden fazla bildirim ulaşabilir (Ağ bağlantı sorunları vb. nedeniyle). Bu nedenle öncelikle
	## siparişin durumunu veri tabanınızdan kontrol edin, eğer onaylandıysa tekrar işlem yapmayın. Örneği aşağıda bulunmaktadır.

$post = $_POST;

if (!isset($post['merchant_oid'], $post['status'], $post['hash'], $post['total_amount'])) {
	die('PAYTR notification failed: missing fields');
}

	####################### DÜZENLEMESİ ZORUNLU ALANLAR #######################
	#
	## API Entegrasyon Bilgileri - Mağaza paneline giriş yaparak BİLGİ sayfasından alabilirsiniz.
$merchant_key   = $paytrprint['paytr_key'];
$merchant_salt  = $paytrprint['paytr_salt'];
	###########################################################################

	####### Bu kısımda herhangi bir değişiklik yapmanıza gerek yoktur. #######
	#
	## POST değerleri ile hash oluştur.
$hash = base64_encode( hash_hmac('sha256', $post['merchant_oid'].$merchant_salt.$post['status'].(float)$post['total_amount'], $merchant_key, true) );
	#
	## Oluşturulan hash'i, paytr'dan gelen post içindeki hash ile karşılaştır (isteğin paytr'dan geldiğine ve değişmediğine emin olmak için)
	## Bu işlemi yapmazsanız maddi zarara uğramanız olasıdır.
if( $hash != $post['hash'] )
	die('PAYTR notification failed: bad hash');


	###########################################################################

	## BURADA YAPILMASI GEREKENLER
	## 1) Siparişin durumunu $post['merchant_oid'] değerini kullanarak veri tabanınızdan sorgulayın.
	## 2) Eğer sipariş zaten daha önceden onaylandıysa veya iptal edildiyse  echo "OK"; exit; yaparak sonlandırın.

	/* Sipariş durum sorgulama örnek
 	   $durum = SQL
	   if($durum == "onay" || $durum == "iptal"){
			echo "OK";
			exit;
		}
	 */

	if ($post['status'] === 'success') {
		$oid = preg_replace('/[^0-9]/', '', (string) $post['merchant_oid']);
		$inovance = $db->prepare('SELECT * FROM siparis WHERE siparis_id=:siparis_id');
		$inovance->execute(array('siparis_id' => $oid));
		$inovanceprint = $inovance->fetch(PDO::FETCH_ASSOC);

		if (!$inovanceprint || !isset($inovanceprint['siparis_id'])) {
			echo 'OK';
			exit;
		}

		// Tekrarlayan bildirimlerde işlem yapma (PayTR uyarısı)
		if (isset($inovanceprint['siparis_durumpay']) && (int) $inovanceprint['siparis_durumpay'] === 1) {
			echo 'OK';
			exit;
		}

		// siparis_durum=0 "Yeni Gelen Siparişler"; kredi kartı başarısı siparis_durumpay ile işaretlenir (siparis-detay vb.)
		try {
			$duzenle = $db->prepare('UPDATE siparis SET siparis_durumpay=1 WHERE siparis_id=:siparis_id');
			$duzenle->execute(array('siparis_id' => $inovanceprint['siparis_id']));
		} catch (Throwable $e) {
			// Eski veritabanında kolon yoksa en azından durumu bozmamak için yut
		}

	} else {
		// Başarısız / iptal bildirimi — kart ödemesi başarısız olarak işaretle
		$oid = preg_replace('/[^0-9]/', '', (string) $post['merchant_oid']);
		if ($oid !== '') {
			try {
				$fail = $db->prepare('UPDATE siparis SET siparis_durumpay=0 WHERE siparis_id=:siparis_id');
				$fail->execute(array('siparis_id' => $oid));
			} catch (Throwable $e) {
			}
		}
		echo 'OK';
		exit;
	}

	echo 'OK';
	exit;
	?>