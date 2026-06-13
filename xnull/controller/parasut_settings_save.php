<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['kullanici_adi'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['parasut_ayar_kaydet'])) {
    header('Location: ../genel-ayarlar.php');
    exit;
}

$enabled = isset($_POST['ayar_parasut_enabled']) ? 1 : 0;
$company = trim((string) ($_POST['ayar_parasut_company_id'] ?? ''));
$clientId = trim((string) ($_POST['ayar_parasut_client_id'] ?? ''));
$clientSecret = trim((string) ($_POST['ayar_parasut_client_secret'] ?? ''));
$username = trim((string) ($_POST['ayar_parasut_username'] ?? ''));
$passwordNew = (string) ($_POST['ayar_parasut_password'] ?? '');
$vat = isset($_POST['ayar_parasut_vat_rate']) ? str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', $_POST['ayar_parasut_vat_rate'])) : '0';
$bireyselVn = preg_replace('/[^0-9]/', '', (string) ($_POST['ayar_parasut_bireysel_vergi_no'] ?? ''));
if ($bireyselVn === '') {
    $bireyselVn = '11111111111';
} else {
    $bireyselVn = mb_substr($bireyselVn, 0, 11);
}
$kdvDahil = isset($_POST['ayar_parasut_kdv_dahil']) ? 1 : 0;

$cur = $db->query('SELECT ayar_parasut_password FROM ayar WHERE ayar_id=0')->fetch(PDO::FETCH_ASSOC);
$passwordKeep = $cur ? (string) $cur['ayar_parasut_password'] : '';
$passwordFinal = ($passwordNew !== '') ? $passwordNew : $passwordKeep;

$u = $db->prepare(
    'UPDATE ayar SET
        ayar_parasut_enabled=:en,
        ayar_parasut_company_id=:co,
        ayar_parasut_client_id=:ci,
        ayar_parasut_client_secret=:cs,
        ayar_parasut_username=:un,
        ayar_parasut_password=:pw,
        ayar_parasut_vat_rate=:vat,
        ayar_parasut_kdv_dahil=:kdv,
        ayar_parasut_bireysel_vergi_no=:bvn,
        ayar_parasut_access_token=\'\',
        ayar_parasut_refresh_token=\'\',
        ayar_parasut_token_expires_at=0
     WHERE ayar_id=0'
);
$ok = $u->execute([
    'en' => $enabled,
    'co' => mb_substr($company, 0, 32),
    'ci' => mb_substr($clientId, 0, 128),
    'cs' => mb_substr($clientSecret, 0, 255),
    'un' => mb_substr($username, 0, 255),
    'pw' => $passwordFinal,
    'vat' => $vat === '' ? 0 : (float) $vat,
    'kdv' => $kdvDahil,
    'bvn' => $bireyselVn,
]);

$st = $ok ? 'ok' : 'no';
header('Location: ../genel-ayarlar.php?tab=parasut&status=' . $st);
exit;
