<?php
/**
 * Galeri sıralama AJAX uç noktası (gorseller.php sortable).
 * Tam sayfa şablonu kullanmaz — mobilde boş/bozuk sayfa oluşmasını engeller.
 */
require_once __DIR__ . '/controller/config.php';

if (!isset($_SESSION['kullanici_adi'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Forbidden');
}

if (isset($_POST['blog'])) {
    parse_str($_POST['blog'], $parsed);
    $blog = isset($parsed['rank']) && is_array($parsed['rank']) ? $parsed['rank'] : [];

    foreach ($blog as $key => $value) {
        $kaydet = $db->prepare('UPDATE resimgaleri SET sira=:sira WHERE resim_id=:id');
        $kaydet->execute([
            'sira' => $key,
            'id' => $value,
        ]);
    }

    header('Content-Type: text/plain; charset=utf-8');
    exit('OK');
}

header('Location: gorseller.php');
exit;
