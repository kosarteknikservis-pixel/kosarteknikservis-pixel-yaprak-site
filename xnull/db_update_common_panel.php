<?php
include_once 'controller/config.php';

if (!isset($db)) {
    die("Hata: Veritabani baglantisi saglanamadi. Lütfen xnull/controller/config.php dosyasini kontrol edin.");
}

try {
    $columns = [
        'ayar_common_panel_status' => 'INT DEFAULT 0',
        'ayar_common_panel_url' => 'VARCHAR(255) DEFAULT NULL',
        'ayar_common_panel_key' => 'VARCHAR(255) DEFAULT NULL'
    ];

    foreach ($columns as $col => $def) {
        $check = $db->query("SHOW COLUMNS FROM ayar LIKE '$col'");
        if ($check->rowCount() == 0) {
            $db->query("ALTER TABLE ayar ADD COLUMN $col $def");
            echo "Added column: $col\n";
        } else {
            echo "Column exists: $col\n";
        }
    }
    echo "Database update complete.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
