<?php
include '../xnull/controller/config.php'; 
$mail=$db->prepare("SELECT * from mail where mail_id=?");
$mail->execute(array(0));
$mailprint=$mail->fetch(PDO::FETCH_ASSOC);

$ayarsor=$db->prepare("SELECT * from ayar");
$ayarsor->execute();
$ayarcek=$ayarsor->fetch(PDO::FETCH_ASSOC);

$smssor=$db->prepare("SELECT * from sms where sms_id=0");
$smssor->execute();
$smscek=$smssor->fetch(PDO::FETCH_ASSOC);

$smtpuser=$mailprint['mail_user'];
$smtphost=$mailprint['mail_host'];
$smtpport=$mailprint['mail_port'];
$smtppass=$mailprint['mail_pass'];
$smtpsecure=$mailprint['mail_secure'];
$smtpsender=$mailprint['mail_sender'];
$smtpname=$mailprint['mail_name'];
$smtpalici=$mailprint['mail_bildirim'];
$siteurl = $ayarcek['ayar_siteurl'];
$mailtext='Sayın admin az önce sitede yorum gönderildi. Panele gidip detaylara erişebilirsiniz.';

require("class.phpmailer.php");

$mail = new PHPMailer();
$mail->PluginDir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

$mail->IsSMTP();
$mail->SMTPAuth = true; //SMTP doğrulama olmalı ve bu değer değişmemeli
$mail->SMTPSecure = $smtpsecure; // Normal bağlantı için tls , güvenli bağlantı için ssl yazın
$mail->Host = $smtphost; // Mail sunucusunun adresi (IP de olabilir)
$mail->Port = $smtpport; // Normal bağlantı için 587, güvenli bağlantı için 465 yazın
$mail->IsHTML(true);
$mail->SetLanguage("tr", "phpmailer/language");
$mail->CharSet  ="utf-8";
$mail->Username =$smtpuser; // Gönderici adresinizin sunucudaki kullanıcı adı (e-posta adresiniz)
$mail->Password = $smtppass; // Mail adresimizin sifresi
$mail->SetFrom($smtpsender, $smtpname); // Mail atıldığında gorulecek isim ve email (genelde yukarıdaki username kullanılır)
$mail->AddAddress($smtpalici); // Mailin gönderileceği alıcı adres
$mail->Subject = "Yorum Gönderildi"; // Email konu başlığı
$mail->Body = $mailtext; // Mailin içeriği


if($mail->Send()) {
 
    Header( "Location:../index.php?yorum=ok" );   

} 
else {
           // bir sorun var, sorunu ekrana bastıralım
    Header( "Location:../index.php?yorum=ok" );

}
?>