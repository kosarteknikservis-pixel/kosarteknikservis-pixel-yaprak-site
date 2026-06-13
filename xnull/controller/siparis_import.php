<?php
include 'config.php';

if (!$_SESSION['kullanici_adi']) {
    header("Location: ../index.php?status=no");
    exit();
}

$error = '';
$success = '';
$import_count = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['zip_file'])) {
    // Büyük veri için memory limit artır
    ini_set('memory_limit', '1024M'); // 50k-100k sipariş için 1GB
    set_time_limit(0); // Zaman limiti yok
    ini_set('max_execution_time', 0);
    
    $zip_file = $_FILES['zip_file']['tmp_name'];
    $delete_existing = isset($_POST['delete_existing']) && $_POST['delete_existing'] == '1';
    
    if (!file_exists($zip_file)) {
        $error = 'ZIP dosyası yüklenemedi!';
    } else {
        // ZIP'i aç
        $zip = new ZipArchive();
        if ($zip->open($zip_file) === TRUE) {
            // Geçici klasör oluştur
            $temp_dir = sys_get_temp_dir() . '/siparis_import_' . time();
            if (!file_exists($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }
            
            // ZIP'i çıkar
            $zip->extractTo($temp_dir);
            $zip->close();
            
            // JSON dosyasını oku (büyük dosyalar için optimize)
            $json_file = $temp_dir . '/siparisler.json';
            if (!file_exists($json_file)) {
                $error = 'ZIP dosyasında siparisler.json bulunamadı!';
            } else {
                // Büyük JSON dosyaları için stream reading (memory efficient)
                $json_content = '';
                $fp = fopen($json_file, 'r');
                if ($fp) {
                    while (!feof($fp)) {
                        $json_content .= fread($fp, 8192); // 8KB chunks
                    }
                    fclose($fp);
                } else {
                    $error = 'JSON dosyası okunamadı!';
                }
                
                if (!$error) {
                    $siparisler = json_decode($json_content, true);
                    unset($json_content); // Memory temizle
                    
                    if (!is_array($siparisler)) {
                        $error = 'JSON dosyası geçersiz!';
                    } else {
                    // Var olanları sil
                    if ($delete_existing) {
                        $db->exec("TRUNCATE TABLE siparis");
                        // AUTO_INCREMENT'i sıfırla
                        $db->exec("ALTER TABLE siparis AUTO_INCREMENT = 1");
                    }
                    
                    // Maksimum ID'yi bul
                    $max_id_query = $db->query("SELECT COALESCE(MAX(siparis_id), 0) as max_id FROM siparis");
                    $max_id_result = $max_id_query->fetch(PDO::FETCH_ASSOC);
                    $next_id = $max_id_result['max_id'] + 1;
                    
                    // Siparişleri ekle (büyük veri için batch processing)
                    $db->beginTransaction();
                    try {
                        $batch_size = 1000; // Her seferde 1000 kayıt
                        $batch_count = 0;
                        $total_count = count($siparisler);
                        
                        foreach ($siparisler as $index => $siparis) {
                            // Boş kayıtları atla (tüm önemli alanlar boşsa)
                            $siparis_ad = isset($siparis['siparis_ad']) ? trim($siparis['siparis_ad']) : '';
                            $siparis_tel = isset($siparis['siparis_tel']) ? trim($siparis['siparis_tel']) : '';
                            $siparis_ip = isset($siparis['siparis_ip']) ? trim($siparis['siparis_ip']) : '';
                            $siparis_urun = isset($siparis['siparis_urun']) ? trim($siparis['siparis_urun']) : '';
                            
                            // Eğer tüm önemli alanlar boşsa, bu kaydı atla
                            if (empty($siparis_ad) && empty($siparis_tel) && empty($siparis_ip) && empty($siparis_urun)) {
                                continue; // Boş kaydı atla
                            }
                            
                            // ID'yi ayarla
                            if ($delete_existing) {
                                // Var olanları sildiysek, orijinal ID'yi kullan (eğer varsa)
                                $siparis_id = isset($siparis['siparis_id']) && $siparis['siparis_id'] > 0 ? $siparis['siparis_id'] : null;
                            } else {
                                // Kaldığı yerden devam et
                                $siparis_id = $next_id++;
                            }
                            
                            // Siparişi ekle
                            if ($delete_existing && $siparis_id !== null) {
                                // ID belirtilmişse, AUTO_INCREMENT'i atla
                                $insert = $db->prepare("INSERT INTO siparis SET
                                siparis_id=:id,
                                siparis_ad=:ad,
                                siparis_tel=:tel,
                                siparis_ip=:ip,
                                siparis_urun=:urun,
                                siparis_odemeid=:odemeid,
                                siparis_odeme=:odeme,
                                siparis_fiyat=:fiyat,
                                siparis_il=:il,
                                siparis_ilce=:ilce,
                                siparis_adres=:adres,
                                siparis_durum=:durum,
                                siparis_tarih=:tarih,
                                siparis_not=:not,
                                siparis_durumpay=:durumpay
                            ");
                            
                                $insert->execute(array(
                                    'id' => $siparis_id,
                                    'ad' => $siparis_ad,
                                    'tel' => $siparis_tel,
                                    'ip' => $siparis_ip,
                                    'urun' => $siparis_urun,
                                    'odemeid' => isset($siparis['siparis_odemeid']) ? $siparis['siparis_odemeid'] : 0,
                                    'odeme' => isset($siparis['siparis_odeme']) ? $siparis['siparis_odeme'] : '',
                                    'fiyat' => isset($siparis['siparis_fiyat']) ? $siparis['siparis_fiyat'] : 0,
                                    'il' => isset($siparis['siparis_il']) ? $siparis['siparis_il'] : '',
                                    'ilce' => isset($siparis['siparis_ilce']) ? $siparis['siparis_ilce'] : '',
                                    'adres' => isset($siparis['siparis_adres']) ? $siparis['siparis_adres'] : '',
                                    'durum' => isset($siparis['siparis_durum']) ? $siparis['siparis_durum'] : 0,
                                    'tarih' => isset($siparis['siparis_tarih']) ? $siparis['siparis_tarih'] : date('Y-m-d H:i:s'),
                                    'not' => isset($siparis['siparis_not']) ? $siparis['siparis_not'] : '',
                                    'durumpay' => isset($siparis['siparis_durumpay']) ? $siparis['siparis_durumpay'] : 0
                                ));
                            } else {
                                // ID belirtilmemişse, AUTO_INCREMENT kullan
                                $insert = $db->prepare("INSERT INTO siparis SET
                                siparis_ad=:ad,
                                siparis_tel=:tel,
                                siparis_ip=:ip,
                                siparis_urun=:urun,
                                siparis_odemeid=:odemeid,
                                siparis_odeme=:odeme,
                                siparis_fiyat=:fiyat,
                                siparis_il=:il,
                                siparis_ilce=:ilce,
                                siparis_adres=:adres,
                                siparis_durum=:durum,
                                siparis_tarih=:tarih,
                                siparis_not=:not,
                                siparis_durumpay=:durumpay
                            ");
                            
                                $insert->execute(array(
                                    'ad' => $siparis_ad,
                                    'tel' => $siparis_tel,
                                    'ip' => $siparis_ip,
                                    'urun' => $siparis_urun,
                                    'odemeid' => isset($siparis['siparis_odemeid']) ? $siparis['siparis_odemeid'] : 0,
                                    'odeme' => isset($siparis['siparis_odeme']) ? $siparis['siparis_odeme'] : '',
                                    'fiyat' => isset($siparis['siparis_fiyat']) ? $siparis['siparis_fiyat'] : 0,
                                    'il' => isset($siparis['siparis_il']) ? $siparis['siparis_il'] : '',
                                    'ilce' => isset($siparis['siparis_ilce']) ? $siparis['siparis_ilce'] : '',
                                    'adres' => isset($siparis['siparis_adres']) ? $siparis['siparis_adres'] : '',
                                    'durum' => isset($siparis['siparis_durum']) ? $siparis['siparis_durum'] : 0,
                                    'tarih' => isset($siparis['siparis_tarih']) ? $siparis['siparis_tarih'] : date('Y-m-d H:i:s'),
                                    'not' => isset($siparis['siparis_not']) ? $siparis['siparis_not'] : '',
                                    'durumpay' => isset($siparis['siparis_durumpay']) ? $siparis['siparis_durumpay'] : 0
                                ));
                            }
                            
                            $import_count++;
                            $batch_count++;
                            
                            // Her 1000 kayıtta bir commit yap (büyük veri için)
                            if ($batch_count >= $batch_size) {
                                $db->commit();
                                $db->beginTransaction();
                                $batch_count = 0;
                                // Memory temizleme
                                if (function_exists('gc_collect_cycles')) {
                                    gc_collect_cycles();
                                }
                            }
                            
                            // İlerleme göster (her 10000 kayıtta bir)
                            if ($import_count % 10000 == 0) {
                                // Session'a ilerleme kaydet
                                $_SESSION['import_progress'] = array(
                                    'total' => $total_count,
                                    'processed' => $import_count,
                                    'percent' => round(($import_count / $total_count) * 100, 2)
                                );
                            }
                        }
                        
                        $db->commit();
                        $success = "$import_count sipariş başarıyla içe aktarıldı!";
                        
                        // Memory temizle
                        unset($siparisler);
                        if (function_exists('gc_collect_cycles')) {
                            gc_collect_cycles();
                        }
                        
                        // Arşive kaydet
                        $arsiv_dir = '../siparis-arsivi';
                        if (!file_exists($arsiv_dir)) {
                            mkdir($arsiv_dir, 0777, true);
                        }
                        
                        $arsiv_file = $arsiv_dir . '/import_' . date('Y-m-d_His') . '.zip';
                        copy($zip_file, $arsiv_file);
                        
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error = 'İçe aktarma hatası: ' . $e->getMessage();
                    }
                    }
                }
            }
            
            // Geçici dosyaları temizle
            if (file_exists($temp_dir)) {
                array_map('unlink', glob("$temp_dir/*"));
                rmdir($temp_dir);
            }
        } else {
            $error = 'ZIP dosyası açılamadı!';
        }
    }
}

// Sonucu göster
if ($error) {
    Header("Location:../siparisler.php?import_error=" . urlencode($error));
    exit;
} else if ($success) {
    Header("Location:../siparisler.php?import_success=" . urlencode($success));
    exit;
} else {
    Header("Location:../siparisler.php?status=no"); exit;
}
exit();
?>

