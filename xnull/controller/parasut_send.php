<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/parasut_api.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['kullanici_adi'])) {
    echo json_encode(['ok' => false, 'error' => 'Yetkisiz']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];
$single = isset($input['siparis_id']) ? (int) $input['siparis_id'] : 0;
if ($single > 0) {
    $ids = [$single];
}
$force = !empty($input['force']);

$ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($v) {
    return $v > 0;
})));

if (count($ids) === 0) {
    echo json_encode(['ok' => false, 'error' => 'Sipariş seçilmedi.']);
    exit;
}

if (count($ids) > 50) {
    echo json_encode(['ok' => false, 'error' => 'Tek seferde en fazla 50 sipariş gönderilebilir.']);
    exit;
}

$client = new ParasutV4Client($db);
$results = [];
$okCount = 0;
$failCount = 0;

foreach ($ids as $id) {
    $q = $db->prepare('SELECT * FROM siparis WHERE siparis_id=?');
    $q->execute([$id]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $results[] = ['siparis_id' => $id, 'ok' => false, 'error' => 'Sipariş bulunamadı'];
        $failCount++;
        continue;
    }
    $r = $client->sendSiparis($row, $force);
    $r['siparis_id'] = $id;
    $results[] = $r;
    if (!empty($r['ok'])) {
        $okCount++;
    } else {
        $failCount++;
    }
}

echo json_encode([
    'ok' => $okCount > 0,
    'ok_count' => $okCount,
    'fail_count' => $failCount,
    'results' => $results,
], JSON_UNESCAPED_UNICODE);
