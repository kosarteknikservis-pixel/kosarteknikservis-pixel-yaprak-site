<?php
include 'config.php';

try {
    $db->exec("ALTER TABLE urunler ADD COLUMN urun_etiket_bg VARCHAR(20) DEFAULT '#2c3e50'");
    echo "Added urun_etiket_bg column.\n";
} catch (PDOException $e) {
    echo "Error adding urun_etiket_bg: " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE urunler ADD COLUMN urun_etiket_color VARCHAR(20) DEFAULT '#ffffff'");
    echo "Added urun_etiket_color column.\n";
} catch (PDOException $e) {
    echo "Error adding urun_etiket_color: " . $e->getMessage() . "\n";
}
?>
