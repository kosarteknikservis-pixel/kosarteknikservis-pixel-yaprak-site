<?php include '../xnull/controller/function.php'; 
date_default_timezone_set('Europe/Istanbul');
$ayarsor=$db->prepare("SELECT * from ayar where ayar_id=?");
$ayarsor->execute(array(0));
$ayarcek=$ayarsor->fetch(PDO::FETCH_ASSOC);

$smssor=$db->prepare("SELECT * from sms where sms_id=0");
$smssor->execute();
$smscek=$smssor->fetch(PDO::FETCH_ASSOC);

$inovance=$db->prepare("SELECT * from siparis where siparis_id=:siparis_id");
$inovance->execute(array(
	'siparis_id' => $_GET['id']
));
$inovanceprint=$inovance->fetch(PDO::FETCH_ASSOC);
?>



$sms_to = $inovanceprint['siparis_tel'];
$id = $_GET['id'];
$ad = $inovanceprint['siparis_ad'];
$new_status_id = $inovanceprint['siparis_durum'];

// Sadece "Kargoya Verildi" (ID: 26) durumunda SMS gönder
if ($new_status_id == 26) {
    $message = 'Sayın '.$ad.', '.$id.' nolu siparişiniz kargoya verilmiştir. İyi günler dileriz.';

    if (function_exists('sendTransactionalSms')) {
        sendTransactionalSms($sms_to, $message);
    } elseif (function_exists('netGsmSend')) {
        netGsmSend($sms_to, $message);
    }
}

?>

<meta http-equiv="refresh" content="0; URL=<?php echo $ayarcek['ayar_siteurl']; ?>xnull/siparis-detay.php?siparis_id=<?php echo $id; ?>&status=ok">