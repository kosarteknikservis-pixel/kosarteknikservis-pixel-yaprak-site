<?php
include 'config.php';
require_once __DIR__ . '/export_helpers.php';

if (!$_SESSION['kullanici_adi']) {
    header("Location: ../index.php?status=no");
    exit();
}

// Büyük veri için memory limit artır
ini_set('memory_limit', '1024M'); // 50k-100k sipariş için 1GB
set_time_limit(0); // Zaman limiti yok
ini_set('max_execution_time', 0);

// Tarih Filtreleme
$bas_tarih = isset($_GET['bas_tarih']) ? $_GET['bas_tarih'] : '';
$bit_tarih = isset($_GET['bit_tarih']) ? $_GET['bit_tarih'] : '';

$where = " WHERE 1=1";
$params = array();

if (!empty($bas_tarih)) {
    $where .= " AND siparis_tarih >= :bas_tarih";
    $params['bas_tarih'] = $bas_tarih . " 00:00:00";
}

if (!empty($bit_tarih)) {
    $where .= " AND siparis_tarih <= :bit_tarih";
    $params['bit_tarih'] = $bit_tarih . " 23:59:59";
}

// Tüm siparişleri çek (büyük veri için optimize - stream processing)
$stmt = $db->prepare("SELECT * FROM siparis $where ORDER BY siparis_id ASC");
$stmt->execute($params);

$total_count_stmt = $db->prepare("SELECT COUNT(*) FROM siparis $where");
$total_count_stmt->execute($params);
$total_count = $total_count_stmt->fetchColumn();

// Geçici klasör oluştur
$temp_dir = sys_get_temp_dir() . '/siparis_export_' . time();
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Siparişleri JSON olarak kaydet (büyük veri için stream yazma - memory efficient)
$json_file = $temp_dir . '/siparisler.json';
$fp = fopen($json_file, 'w');
fwrite($fp, '[');
$first = true;
$processed = 0;

// Stream processing - her seferde bir kayıt işle, memory'de tutma
while ($siparis = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!$first) {
        fwrite($fp, ",\n");
    }
    $siparis['siparis_tel'] = panel_export_normalize_phone($siparis['siparis_tel'] ?? '');
    fwrite($fp, json_encode($siparis, JSON_UNESCAPED_UNICODE));
    $first = false;
    $processed++;
    
    // Her 10000 kayıtta bir memory temizle
    if ($processed % 10000 == 0) {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
}
fwrite($fp, ']');
fclose($fp);

// ZIP oluştur
$zip_file = sys_get_temp_dir() . '/siparis_export_' . date('Y-m-d_His') . '.zip';
$zip = new ZipArchive();

if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
    $zip->addFile($json_file, 'siparisler.json');
    $zip->addFromString('export_info.txt', 
        "Sipariş Dışa Aktarma\n" .
        "Tarih: " . date('Y-m-d H:i:s') . "\n" .
        "Toplam Sipariş: " . $total_count . "\n" .
        "Oluşturan: " . $_SESSION['kullanici_adi'] . "\n"
    );
    $zip->close();
    
    // Arşive kaydet
    $arsiv_dir = '../siparis-arsivi';
    if (!file_exists($arsiv_dir)) {
        mkdir($arsiv_dir, 0777, true);
    }
    $arsiv_file = $arsiv_dir . '/export_' . date('Y-m-d_His') . '.zip';
    copy($zip_file, $arsiv_file);
    
    // Dosyayı gönder
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="siparis_export_' . date('Y-m-d_His') . '.zip"');
    header('Content-Length: ' . filesize($zip_file));
    readfile($zip_file);
    
    // Geçici dosyaları temizle
    unlink($json_file);
    unlink($zip_file);
    rmdir($temp_dir);
    exit();
} else {
    die('ZIP oluşturulamadı!');
}
?>

