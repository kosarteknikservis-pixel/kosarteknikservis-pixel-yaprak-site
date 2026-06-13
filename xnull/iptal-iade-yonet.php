<?php
require_once __DIR__ . '/controller/config.php';
if (empty($_SESSION['kullanici_adi'])) {
	header('Location: login.php');
	exit;
}
$legal_table = 'iptal_iade';
$legal_title = 'İptal ve İade Prosedürü';
require_once __DIR__ . '/../include/legal-panel-form-handler.php';
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
include __DIR__ . '/../include/legal-panel-form.php';
include 'footer.php';
