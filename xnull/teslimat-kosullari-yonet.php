<?php
require_once __DIR__ . '/controller/config.php';
if (empty($_SESSION['kullanici_adi'])) {
	header('Location: login.php');
	exit;
}
$legal_table = 'teslimat_kosullari';
$legal_title = 'Teslimat Koşulları';
require_once __DIR__ . '/../include/legal-panel-form-handler.php';
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
include __DIR__ . '/../include/legal-panel-form.php';
include 'footer.php';
