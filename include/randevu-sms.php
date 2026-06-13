<?php include '../xnull/controller/function.php'; 
date_default_timezone_set('Europe/Istanbul');
$ayarsor=$db->prepare("SELECT * from ayar where ayar_id=?");
$ayarsor->execute(array(0));
$ayarcek=$ayarsor->fetch(PDO::FETCH_ASSOC);

$smssor=$db->prepare("SELECT * from sms where sms_id=0");
$smssor->execute();
$smscek=$smssor->fetch(PDO::FETCH_ASSOC);

?>


<?php

$sms_to = $ayarcek['ayar_tel'];
$message = 'Sayın admin az önce sitenizde randevu talebi oluşturulmuştur. Panele gidip detaylara erişebilirsiniz.';

if (function_exists('sendTransactionalSms')) {
    sendTransactionalSms($sms_to, $message);
} elseif (function_exists('netGsmSend')) {
    netGsmSend($sms_to, $message);
}
?>

<meta http-equiv="refresh" content="0; URL=<?php echo $ayarcek['ayar_siteurl']; ?>index.php?status=ok">