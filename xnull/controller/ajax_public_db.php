<?php
/**
 * Salt okunur AJAX (ilçe/mahalle listesi): DB ayarı config.php ile birebir aynı olmalı.
 * Canlı sunucuda yalnızca config.php güncellenince ilçe isteği de doğru DB'ye gider.
 * Paralel isteklerde oturum kilidini erken bırakmak için session_write_close.
 */
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_ACTIVE) {
	session_write_close();
}
