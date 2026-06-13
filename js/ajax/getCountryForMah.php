<?php
require_once __DIR__ . '/../../xnull/controller/ajax_public_db.php';

if (isset($_POST['city_id'])) {
    $getIlceID = $_POST['city_id'];
    
    // İlçe tablosundan mahalleleri çek (eğer mahalle tablosu varsa)
    // Şimdilik boş döndür çünkü mahalle tablosu yok gibi görünüyor
    // Eğer ileride mahalle tablosu eklenecekse buraya eklenebilir
    
    ?><option value="">Mahalle seçiniz (Opsiyonel)</option><?php
    // Mahalle tablosu yoksa boş döndür
    // Gelecekte mahalle tablosu eklendiğinde buraya kod eklenecek
}
?>
