<?php
require_once __DIR__ . '/controller/config.php';
if (empty($_SESSION['kullanici_adi'])) {
	header('Location: login.php');
	exit;
}
$legal_table = 'satis_politikasi';
$legal_title = 'Satış Politikası';
require_once __DIR__ . '/../include/legal-panel-form-handler.php';
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
include __DIR__ . '/../include/legal-panel-form.php';
include 'footer.php';
