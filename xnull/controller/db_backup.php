<?php
include 'config.php';

if (!isset($_SESSION['kullanici_adi'])) {
    die("Yetkisiz erişim.");
}

// Memory and time limits for big databases
ini_set('memory_limit', '512M');
set_time_limit(0);

$tables = [];
$result = $db->query("SHOW TABLES");
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$arsiv_dir = '../siparis-arsivi';
if (!file_exists($arsiv_dir)) {
    mkdir($arsiv_dir, 0777, true);
}

$sql_file_path = $arsiv_dir . '/temp_db.sql';
$fp = fopen($sql_file_path, 'w');

fwrite($fp, "-- Database Backup\n");
fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
fwrite($fp, "-- Database: " . $dbAdi . "\n\n");
fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");

foreach ($tables as $table) {
    // Drop and Create
    $row2 = $db->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
    fwrite($fp, "\n\nDROP TABLE IF EXISTS `$table`;\n" . $row2[1] . ";\n\n");

    // Data
    $result = $db->query("SELECT * FROM $table");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $values = [];
        foreach ($row as $val) {
            if (isset($val)) {
                $values[] = $db->quote($val);
            } else {
                $values[] = 'NULL';
            }
        }
        fwrite($fp, "INSERT INTO `$table` VALUES(" . implode(",", $values) . ");\n");
    }
}

fwrite($fp, "\n\nSET FOREIGN_KEY_CHECKS=1;");
fclose($fp);

// Zip the SQL
$zip_file_name = 'db_backup_' . date('Y-m-d_His') . '.zip';
$zip_path = $arsiv_dir . '/' . $zip_file_name;

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
    $zip->addFile($sql_file_path, 'database.sql');
    $zip->close();
    
    // Cleanup temporary SQL
    unlink($sql_file_path);
    
    Header("Location: ../siparis-arsivi.php?status=ok&msg=db_backup_success"); exit;
} else {
    if (file_exists($sql_file_path)) unlink($sql_file_path);
    die("Yedek dosyası oluşturulamadı. ZIP modülü veya izin hatası.");
}
?>
