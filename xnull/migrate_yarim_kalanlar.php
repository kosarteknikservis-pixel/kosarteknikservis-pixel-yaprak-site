<?php
include 'controller/config.php';
try {
    $db->exec("ALTER TABLE yarim_kalanlar ADD COLUMN urun VARCHAR(255) NULL AFTER tel, ADD COLUMN fiyat VARCHAR(50) NULL AFTER urun");
    echo "Success: Table yarim_kalanlar updated.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
