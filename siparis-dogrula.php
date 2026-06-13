<?php
require_once __DIR__ . '/xnull/controller/config.php';
require_once __DIR__ . '/include/order-verification.php';

$token = isset($_GET['t']) ? trim((string)$_GET['t']) : '';

if ($token === '') {
    header('Location: ' . rtrim(SITE_URL, '/') . '/index.php?status=no');
    exit;
}

$result = ov_verify_by_token($db, $token, 'link');
$siparisId = isset($result['siparis_id']) ? (int)$result['siparis_id'] : 0;

if ($siparisId > 0) {
    if (!empty($result['ok'])) {
        header('Location: ' . rtrim(SITE_URL, '/') . '/siparis-onay.php?siparis=' . $siparisId . '&verify=ok');
        exit;
    }
    if (isset($result['reason']) && $result['reason'] === 'expired') {
        header('Location: ' . rtrim(SITE_URL, '/') . '/siparis-onay.php?siparis=' . $siparisId . '&verify=expired');
        exit;
    }
    header('Location: ' . rtrim(SITE_URL, '/') . '/siparis-onay.php?siparis=' . $siparisId . '&verify=no');
    exit;
}

header('Location: ' . rtrim(SITE_URL, '/') . '/index.php?status=no');
exit;
