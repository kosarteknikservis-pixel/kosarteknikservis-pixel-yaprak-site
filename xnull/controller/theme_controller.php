<?php 
include 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!$_SESSION['kullanici_adi']) { header("Location: ../index.php?status=no"); exit(); exit; }

// Increase limits for heavy file operations
set_time_limit(0);
ini_set('memory_limit', '-1');

// Helper to log theme actions
function theme_log($msg) {
    file_put_contents(__DIR__ . "/../../theme_debug.txt", date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

function recurse_copy($src,$dst) {
    if (!is_dir($src)) return;
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            } else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// Helper to zip folder
function zipDir($source, $destination) {
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }
    $zip = new ZipArchive();
    if (!$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        return false;
    }
    $source = str_replace('\\', '/', realpath($source));
    if (is_dir($source) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    } else if (is_file($source) === true) {
        $zip->addFile($source, basename($source));
    }
    return $zip->close();
}

// Helper to clean directory
function delete_content($dirPath) {
    if (!is_dir($dirPath)) return;
    $dirPath = rtrim($dirPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $files = glob($dirPath . '{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);
    if ($files) {
        foreach ($files as $file) {
            if (is_dir($file)) {
                delete_content($file);
                @rmdir($file);
            } else {
                @unlink($file);
            }
        }
    }
}

// SAVE THEME
if (isset($_POST['theme_save'])) {
    $name = preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['theme_name']);
    $root_dir = realpath(__DIR__ . '/../../');
    $theme_dir = $root_dir . "/xnull"; // Storage is now in xnull root
    
    $zip_file = $theme_dir . "/" . $name . ".zip";
    $temp_folder = $theme_dir . "/temp_theme_" . time();
    mkdir($temp_folder, 0777, true);
    
    // 1. Copy Assets (görseller, logo, favicon dahil)
    $assets_src = $root_dir . "/xnull/assets";
    if(is_dir($assets_src)) {
         @mkdir($temp_folder . "/assets", 0777, true);
         recurse_copy($assets_src, $temp_folder . "/assets");
         theme_log("Assets copied: " . $assets_src);
         
         // Kontrol: Ürün görselleri klasörü var mı?
         $urunler_img_dir = $assets_src . "/img/urunler";
         if(is_dir($urunler_img_dir)) {
             $urunler_files = glob($urunler_img_dir . "/*");
             theme_log("Ürün görselleri klasörü bulundu: " . count($urunler_files) . " dosya");
         } else {
             theme_log("UYARI: Ürün görselleri klasörü bulunamadı: " . $urunler_img_dir);
         }
    } else {
         theme_log("UYARI: Assets klasörü bulunamadı: " . $assets_src);
    }

    // 2. Save Products (ürünler tablosu)
    $products = $db->query("SELECT * FROM urunler")->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($temp_folder . "/urunler.json", json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // 2.5. Save Gallery Images (resimgaleri tablosu - index.php'de kullanılan görseller)
    try {
        $gallery = $db->query("SELECT * FROM resimgaleri ORDER BY sira ASC")->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents($temp_folder . "/resimgaleri.json", json_encode($gallery, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        theme_log("Galeri görselleri yedeklendi: " . count($gallery) . " kayıt");
    } catch (Exception $e) {
        theme_log("UYARI: resimgaleri tablosu bulunamadı veya hata: " . $e->getMessage());
    }
    
    // 3. Save Settings (ayarlar tablosu - logo, favicon dahil)
    $settings = $db->query("SELECT * FROM ayar WHERE ayar_id=0")->fetch(PDO::FETCH_ASSOC);
    
    // Görsel ayarlarını da JSON'a ekle (logo, favicon, vb.)
    $gorsel_ayarlar = array();
    if (isset($settings['ayar_logo']) && !empty($settings['ayar_logo'])) {
        $gorsel_ayarlar['logo'] = $settings['ayar_logo'];
        // Logo dosyasını da kopyala
        $logo_path = $root_dir . "/" . ltrim($settings['ayar_logo'], '/');
        if (file_exists($logo_path)) {
            $logo_dir = $temp_folder . "/gorsel_ayarlar";
            @mkdir($logo_dir, 0777, true);
            $logo_ext = pathinfo($logo_path, PATHINFO_EXTENSION);
            @copy($logo_path, $logo_dir . "/logo." . $logo_ext);
        }
    }
    if (isset($settings['ayar_fav']) && !empty($settings['ayar_fav'])) {
        $gorsel_ayarlar['favicon'] = $settings['ayar_fav'];
        // Favicon dosyasını da kopyala
        $fav_path = $root_dir . "/" . ltrim($settings['ayar_fav'], '/');
        if (file_exists($fav_path)) {
            $fav_dir = $temp_folder . "/gorsel_ayarlar";
            @mkdir($fav_dir, 0777, true);
            $fav_ext = pathinfo($fav_path, PATHINFO_EXTENSION);
            @copy($fav_path, $fav_dir . "/favicon." . $fav_ext);
        }
    }
    // Diğer görsel ayarları da ekle (varsa)
    if (isset($settings['ayar_resimcounter']) && !empty($settings['ayar_resimcounter'])) {
        $gorsel_ayarlar['resimcounter'] = $settings['ayar_resimcounter'];
    }
    if (isset($settings['ayar_resimparalax']) && !empty($settings['ayar_resimparalax'])) {
        $gorsel_ayarlar['resimparalax'] = $settings['ayar_resimparalax'];
    }
    
    // Görsel ayarları JSON'a kaydet
    file_put_contents($temp_folder . "/gorsel_ayarlar.json", json_encode($gorsel_ayarlar, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Tüm ayarları JSON'a kaydet
    file_put_contents($temp_folder . "/settings.json", json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // 4. Zip it
    if(zipDir($temp_folder, $zip_file)) {
        theme_log("Backup created in xnull: $name.zip");
    } else {
        theme_log("ZIP Creation FAILED for $name");
    }
    
    // Cleanup temp
    delete_content($temp_folder);
    rmdir($temp_folder);
    
    Header("Location:../temalar.php?status=ok");
    exit;
}

// IMPORT THEME (Upload ZIP file)
if (isset($_POST['theme_import'])) {
    if (!isset($_FILES['theme_zip']) || $_FILES['theme_zip']['error'] !== UPLOAD_ERR_OK) {
        theme_log("ZIP upload failed");
        Header("Location:../temalar.php?status=no");
        exit;
    }
    
    $root_dir = realpath(__DIR__ . '/../../');
    $theme_dir = $root_dir . "/xnull";
    $uploaded_file = $_FILES['theme_zip']['tmp_name'];
    $original_name = basename($_FILES['theme_zip']['name']);
    
    // Validate ZIP file
    $zip = new ZipArchive;
    if ($zip->open($uploaded_file) !== TRUE) {
        theme_log("Invalid ZIP file: $original_name");
        Header("Location:../temalar.php?status=no");
        exit;
    }
    $zip->close();
    
    // Move uploaded file to xnull directory
    $target_file = $theme_dir . "/" . $original_name;
    if (move_uploaded_file($uploaded_file, $target_file)) {
        theme_log("ZIP file uploaded: $original_name");
        // Redirect to restore
        Header("Location:../controller/theme_controller.php?action=restore&name=" . urlencode($original_name));
        exit;
    } else {
        theme_log("Failed to move uploaded ZIP file");
        Header("Location:../temalar.php?status=no");
        exit;
    }
}

// RESTORE THEME
if (isset($_GET['action']) && $_GET['action'] == "restore") {
    $name = basename($_GET['name']);
    $root_dir = realpath(__DIR__ . '/../../');
    $zip_file = $root_dir . "/xnull/" . $name; // Look in xnull root
    
    theme_log("Restore initiated for ZIP (xnull based): $name");
    
    if (!file_exists($zip_file)) { 
        theme_log("ZIP file not found: $zip_file");
        Header("Location:../temalar.php?status=no"); exit; 
    }
    
    $zip = new ZipArchive;
    if ($zip->open($zip_file) === TRUE) {
        $temp_extract = $root_dir . "/xnull/temp_restore_" . time();
        mkdir($temp_extract, 0777, true);
        $zip->extractTo($temp_extract);
        $zip->close();
        
        // --- 1. CLEAN CURRENT DATA ---
        theme_log("Cleaning current assets...");
        // Önce mevcut assets klasörünü tamamen sil
        if(is_dir($root_dir . "/xnull/assets")) {
            delete_content($root_dir . "/xnull/assets");
            @rmdir($root_dir . "/xnull/assets");
        }
        
        theme_log("Cleaning current products from database...");
        $db->exec("TRUNCATE TABLE urunler");
        
        theme_log("Cleaning current gallery images from database...");
        try {
            $db->exec("TRUNCATE TABLE resimgaleri");
        } catch (Exception $e) {
            theme_log("UYARI: resimgaleri tablosu temizlenemedi: " . $e->getMessage());
        }
        
        // --- 2. RESTORE ASSETS (görseller, logo, favicon) ---
        theme_log("Applying files from ZIP to xnull/assets...");
        if(is_dir($temp_extract . "/assets")) {
            // assets klasörünü oluştur
            if(!is_dir($root_dir . "/xnull/assets")) {
                @mkdir($root_dir . "/xnull/assets", 0777, true);
            }
            // Tüm alt klasörleri ve dosyaları kopyala
            recurse_copy($temp_extract . "/assets", $root_dir . "/xnull/assets");
            theme_log("Assets restored successfully");
        } else {
            theme_log("WARNING: assets folder not found in backup!");
        }
        
        // --- 3. RESTORE PRODUCTS ---
        if(file_exists($temp_extract . "/urunler.json")) {
            theme_log("Restoring products from database...");
            $products_json = file_get_contents($temp_extract . "/urunler.json");
            $products = json_decode($products_json, true);
            
            if(is_array($products) && !empty($products)) {
                $restored_count = 0;
                foreach($products as $product) {
                    if(isset($product['urun_id'])) unset($product['urun_id']);
                    
                    $sql = "INSERT INTO urunler SET ";
                    $params = [];
                    foreach ($product as $key => $val) {
                        $sql .= "`$key` = :$key, ";
                        $params[$key] = $val;
                    }
                    $sql = rtrim($sql, ", ");
                    $stmt = $db->prepare($sql);
                    if($stmt->execute($params)) {
                        $restored_count++;
                    }
                }
                theme_log("Ürünler geri yüklendi: " . $restored_count . " ürün");
            } else {
                theme_log("UYARI: Ürünler JSON'u boş veya geçersiz!");
            }
        } else {
            theme_log("UYARI: urunler.json dosyası bulunamadı!");
        }
        
        // Kontrol: Geri yüklenen görseller var mı?
        $urunler_img_dir = $root_dir . "/xnull/assets/img/urunler";
        if(is_dir($urunler_img_dir)) {
            $urunler_files = glob($urunler_img_dir . "/*");
            theme_log("Geri yükleme sonrası ürün görselleri: " . count($urunler_files) . " dosya");
        } else {
            theme_log("UYARI: Geri yükleme sonrası ürün görselleri klasörü bulunamadı!");
        }
        
        // --- 3.5. RESTORE GALLERY IMAGES (resimgaleri tablosu - index.php görselleri) ---
        if(file_exists($temp_extract . "/resimgaleri.json")) {
            theme_log("Restoring gallery images from database...");
            $gallery_json = file_get_contents($temp_extract . "/resimgaleri.json");
            $gallery = json_decode($gallery_json, true);
            
            if(is_array($gallery) && !empty($gallery)) {
                $restored_gallery_count = 0;
                foreach($gallery as $gallery_item) {
                    if(isset($gallery_item['resim_id'])) unset($gallery_item['resim_id']);
                    
                    $sql = "INSERT INTO resimgaleri SET ";
                    $params = [];
                    foreach ($gallery_item as $key => $val) {
                        $sql .= "`$key` = :$key, ";
                        $params[$key] = $val;
                    }
                    $sql = rtrim($sql, ", ");
                    $stmt = $db->prepare($sql);
                    if($stmt->execute($params)) {
                        $restored_gallery_count++;
                    }
                }
                theme_log("Galeri görselleri geri yüklendi: " . $restored_gallery_count . " kayıt");
            } else {
                theme_log("UYARI: Galeri görselleri JSON'u boş veya geçersiz!");
            }
        } else {
            theme_log("UYARI: resimgaleri.json dosyası bulunamadı!");
        }
        
        // --- 4. RESTORE GÖRSEL AYARLARI (logo, favicon dosyaları) ---
        if(file_exists($temp_extract . "/gorsel_ayarlar.json")) {
            theme_log("Restoring görsel ayarlar...");
            $gorsel_ayarlar_json = file_get_contents($temp_extract . "/gorsel_ayarlar.json");
            $gorsel_ayarlar = json_decode($gorsel_ayarlar_json, true);
            
            if(is_array($gorsel_ayarlar) && is_dir($temp_extract . "/gorsel_ayarlar")) {
                // Logo restore
                if(isset($gorsel_ayarlar['logo'])) {
                    $logo_files = glob($temp_extract . "/gorsel_ayarlar/logo.*");
                    if(!empty($logo_files)) {
                        $logo_file = $logo_files[0];
                        $logo_target = $root_dir . "/" . ltrim($gorsel_ayarlar['logo'], '/');
                        $logo_target_dir = dirname($logo_target);
                        if(!is_dir($logo_target_dir)) @mkdir($logo_target_dir, 0777, true);
                        @copy($logo_file, $logo_target);
                        theme_log("Logo restored: " . $gorsel_ayarlar['logo']);
                    }
                }
                
                // Favicon restore
                if(isset($gorsel_ayarlar['favicon'])) {
                    $fav_files = glob($temp_extract . "/gorsel_ayarlar/favicon.*");
                    if(!empty($fav_files)) {
                        $fav_file = $fav_files[0];
                        $fav_target = $root_dir . "/" . ltrim($gorsel_ayarlar['favicon'], '/');
                        $fav_target_dir = dirname($fav_target);
                        if(!is_dir($fav_target_dir)) @mkdir($fav_target_dir, 0777, true);
                        @copy($fav_file, $fav_target);
                        theme_log("Favicon restored: " . $gorsel_ayarlar['favicon']);
                    }
                }
            }
        }
        
        // --- 5. RESTORE DB SETTINGS (ayarlar - logo, favicon dahil) ---
        if(file_exists($temp_extract . "/settings.json")) {
            theme_log("Restoring DB settings...");
            $settings_json = file_get_contents($temp_extract . "/settings.json");
            $data = json_decode($settings_json, true);
            if(isset($data['ayar_id'])) unset($data['ayar_id']);
            
            $sql = "UPDATE ayar SET ";
            $params = [];
            foreach ($data as $key => $val) {
                $sql .= "`$key` = :$key, ";
                $params[$key] = $val;
            }
            $sql = rtrim($sql, ", ") . " WHERE ayar_id=0";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        // Cleanup temp
        delete_content($temp_extract);
        rmdir($temp_extract);
        
        theme_log("Restore Success.");
        Header("Location:../temalar.php?status=ok");
        exit;
    } else {
        theme_log("Failed to open ZIP: $name");
        Header("Location:../temalar.php?status=no"); exit;
    }
    exit;
}

// DOWNLOAD THEME
if (isset($_GET['action']) && $_GET['action'] == "download") {
    $name = basename($_GET['name']);
    $root_dir = realpath(__DIR__ . '/../../');
    $zip_file = $root_dir . "/xnull/" . $name;
    
    if (!file_exists($zip_file)) {
        Header("Location:../temalar.php?status=no");
        exit;
    }
    
    header('Content-Type: application/zip'); exit;
    header('Content-Disposition: attachment; filename="' . $name . '"'); exit;
    header('Content-Length: ' . filesize($zip_file)); exit;
    header('Cache-Control: must-revalidate'); exit;
    header('Pragma: public'); exit;
    
    readfile($zip_file);
    exit;
}
?>
