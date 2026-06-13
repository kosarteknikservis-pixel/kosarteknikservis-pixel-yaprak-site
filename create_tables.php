<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'xnull/controller/config.php';

function columnExists($db, $table, $column) {
    try {
        $query = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $query->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function addColumn($db, $table, $column, $type) {
    if (!columnExists($db, $table, $column)) {
        try {
            $db->exec("ALTER TABLE `$table` ADD `$column` $type");
            echo "Added column '$column' to '$table'.<br>";
        } catch (Exception $e) {
            echo "Error adding column '$column' to '$table': " . $e->getMessage() . "<br>";
        }
    }
}

try {
    // 1. Create Form Tables
    $db->exec("CREATE TABLE IF NOT EXISTS `formlar` (
        `form_id` INT AUTO_INCREMENT PRIMARY KEY,
        `form_baslik` VARCHAR(255) NOT NULL,
        `form_slug` VARCHAR(255) NOT NULL,
        `form_aciklama` TEXT,
        `form_durum` INT DEFAULT 1,
        `form_menu` INT DEFAULT 0,
        `form_sira` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'formlar' checked/created.<br>";

    $db->exec("CREATE TABLE IF NOT EXISTS `form_alanlari` (
        `alan_id` INT AUTO_INCREMENT PRIMARY KEY,
        `form_id` INT NOT NULL,
        `alan_baslik` VARCHAR(255) NOT NULL,
        `alan_tip` VARCHAR(50) NOT NULL,
        `alan_secenekler` TEXT,
        `alan_zorunlu` INT DEFAULT 0,
        `alan_sira` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'form_alanlari' checked/created.<br>";

    $db->exec("CREATE TABLE IF NOT EXISTS `form_basvurular` (
        `basvuru_id` INT AUTO_INCREMENT PRIMARY KEY,
        `form_id` INT NOT NULL,
        `basvuru_ip` VARCHAR(50),
        `basvuru_tarih` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'form_basvurular' checked/created.<br>";

    $db->exec("CREATE TABLE IF NOT EXISTS `form_degerler` (
        `deger_id` INT AUTO_INCREMENT PRIMARY KEY,
        `basvuru_id` INT NOT NULL,
        `alan_id` INT NOT NULL,
        `deger` LONGTEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'form_degerler' checked/created.<br>";

    // 2. Audit 'ayar' table
    $ayarCols = [
        'ayar_sonraki_adim_on' => 'INT(1) DEFAULT 1',
        'ayar_sonraki_adim_text' => 'TEXT',
        'ayar_sorgula_on' => 'INT(1) DEFAULT 1',
        'ayar_fomo_on' => 'INT(1) DEFAULT 0',
        'ayar_fomo_saat' => 'INT DEFAULT 24',
        'ayar_bildirim_on' => 'INT(1) DEFAULT 1',
        'ayar_ip_sehir_on' => 'INT(1) DEFAULT 1',
        'ayar_wa_sablon' => 'TEXT',
        'ayar_stok_on' => 'INT(1) DEFAULT 0',
        'ayar_stok_sayi' => 'INT DEFAULT 100',
        'ayar_gizlilik_on' => 'INT(1) DEFAULT 0',
        'ayar_cookie_on' => 'INT(1) DEFAULT 0',
        'ayar_cookie_sure' => 'INT DEFAULT 1440',
        'ayar_urun_ad_on' => 'INT(1) DEFAULT 1',
        'ayar_urun_fiyat_on' => 'INT(1) DEFAULT 1',
        'ayar_urun_ad_boyut' => 'VARCHAR(10) DEFAULT "1.4"',
        'ayar_urun_fiyat_boyut' => 'VARCHAR(10) DEFAULT "1.5"',
        'ayar_nedenbiz_on' => 'INT(1) DEFAULT 1',
        'ayar_footer_on' => 'INT(1) DEFAULT 1',
        'ayar_altgorsel_on' => 'INT(1) DEFAULT 1',
        'ayar_yorum_anasayfa_on' => 'INT(1) DEFAULT 0',
        'ayar_urun_secenek_on' => 'INT(1) DEFAULT 1',
        'ayar_siparis_bar' => 'INT(1) DEFAULT 1',
        'ayar_kurumsal_fatura_on' => 'INT(1) DEFAULT 0',
        'ayar_common_panel_status' => 'INT(1) DEFAULT 0',
        'ayar_common_panel_url' => 'VARCHAR(255)',
        'ayar_common_panel_key' => 'VARCHAR(255)',
        'ayar_common_query_status' => 'INT(1) DEFAULT 0',
        'ayar_il_sure' => 'INT DEFAULT 24',
        'ayar_logo_tip' => 'INT(1) DEFAULT 0',
        'ayar_logo_metin' => 'VARCHAR(255)',
        'ayar_logo_icon' => 'VARCHAR(255)',
        'ayar_video_autoplay' => 'INT(1) DEFAULT 0',
        'ayar_video_muted' => 'INT(1) DEFAULT 1',
        'ayar_video_loop' => 'INT(1) DEFAULT 0',
        'ayar_youtube_autoplay' => 'INT(1) DEFAULT 0',
        'ayar_youtube_muted' => 'INT(1) DEFAULT 1',
        'ayar_youtube_loop' => 'INT(1) DEFAULT 0',
        'ayar_urun_sablon' => 'INT(1) DEFAULT 1'
    ];
    foreach($ayarCols as $col => $type) addColumn($db, 'ayar', $col, $type);

    // 3. Audit 'whatsapp' table
    $waCols = [
        'whats_tel' => 'VARCHAR(50)',
        'whats_cdestek' => 'VARCHAR(50)',
        'whats_cdestekdurum' => 'INT(1) DEFAULT 0',
        'whats_tiklaara' => 'VARCHAR(50)',
        'whats_tiklaaradurum' => 'INT(1) DEFAULT 0',
        'whats_skype' => 'VARCHAR(50)',
        'whats_skypedurum' => 'INT(1) DEFAULT 0',
        'whats_mail' => 'VARCHAR(255)',
        'whats_maildurum' => 'INT(1) DEFAULT 0',
        'whats_sssdurum' => 'INT(1) DEFAULT 0',
        'whats_iletisimdurum' => 'INT(1) DEFAULT 0',
        'whats_durum' => 'INT(1) DEFAULT 1'
    ];
    foreach($waCols as $col => $type) addColumn($db, 'whatsapp', $col, $type);

    // 4. Audit 'motor' table
    $motorCols = [
        'motor_meta_token' => 'TEXT',
        'motor_meta_pixel_id' => 'VARCHAR(255)',
        'motor_tiktok_token' => 'TEXT',
        'motor_tiktok_pixel_id' => 'VARCHAR(255)'
    ];
    foreach($motorCols as $col => $type) addColumn($db, 'motor', $col, $type);

    echo "Database audit completed successfully.<br>";

} catch (PDOException $e) {
    echo "DATABASE ERROR: " . $e->getMessage() . "<br>";
}
?>
