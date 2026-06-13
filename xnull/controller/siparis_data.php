<?php
include 'config.php';

if (!isset($_SESSION['kullanici_adi'])) {
    die(json_encode(['error' => 'Yetkisiz erişim.']));
}

$requestData = $_REQUEST;

// Doğrulama tablosu yoksa oluştur (eski kurulumlar için güvenli fallback).
try {
    $db->exec(
        "CREATE TABLE IF NOT EXISTS siparis_dogrulama (
            id INT AUTO_INCREMENT PRIMARY KEY,
            siparis_id INT NOT NULL,
            siparis_tel VARCHAR(32) NOT NULL DEFAULT '',
            otp_code VARCHAR(8) NOT NULL DEFAULT '',
            token_hash VARCHAR(128) NOT NULL DEFAULT '',
            durum TINYINT NOT NULL DEFAULT 0,
            dogrulama_kanali VARCHAR(20) NOT NULL DEFAULT '',
            hata_sayisi INT NOT NULL DEFAULT 0,
            son_gonderim DATETIME NULL,
            son_kontrol DATETIME NULL,
            son_dogrulama DATETIME NULL,
            bitis_tarihi DATETIME NOT NULL,
            olusturma_tarihi DATETIME NOT NULL,
            UNIQUE KEY uq_siparis_id (siparis_id),
            KEY idx_durum (durum)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Exception $e) {}

// Base query parameters
$durum = isset($requestData['drm']) ? intval($requestData['drm']) : 0;
$bas_tarih = isset($requestData['bas_tarih']) ? $requestData['bas_tarih'] : '';
$bit_tarih = isset($requestData['bit_tarih']) ? $requestData['bit_tarih'] : '';

$where = " WHERE siparis_durum = :durum";
$params = ['durum' => $durum];

if (!empty($bas_tarih)) {
    $where .= " AND siparis_tarih >= :bas_tarih";
    $params['bas_tarih'] = $bas_tarih . " 00:00:00";
}
if (!empty($bit_tarih)) {
    $where .= " AND siparis_tarih <= :bit_tarih";
    $params['bit_tarih'] = $bit_tarih . " 23:59:59";
}

// Search
if (!empty($requestData['search']['value'])) {
    $search = $requestData['search']['value'];
    $where .= " AND (siparis_ad LIKE :search OR siparis_tel LIKE :search OR siparis_ip LIKE :search OR CAST(siparis_id AS CHAR) LIKE :search OR siparis_il LIKE :search OR siparis_ilce LIKE :search OR siparis_adres LIKE :search OR siparis_urun LIKE :search OR siparis_not LIKE :search OR siparis_fatura_vn LIKE :search OR siparis_fatura_vd LIKE :search OR siparis_fatura_unvan LIKE :search OR siparis_fatura_adres LIKE :search OR CAST(COALESCE(sd.durum,0) AS CHAR) LIKE :search)";
    $params['search'] = "%$search%";
}

// Total records
$totalQuery = $db->prepare("SELECT COUNT(*) as total FROM siparis LEFT JOIN siparis_dogrulama sd ON sd.siparis_id = siparis.siparis_id" . $where);
$totalQuery->execute($params);
$totalData = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalData['total'];

// Order
$columns = [
    0 => 'siparis_id', // Checkbox - not sortable but used for index
    1 => 'siparis_id', // Detay
    2 => 'siparis_tarih',
    3 => 'siparis_id',
    4 => 'siparis_ip',
    5 => 'siparis_ad',
    6 => 'siparis_il',
    7 => 'siparis_ilce',
    8 => 'siparis_adres',
    9 => 'siparis_fatura_vn',
    10 => 'siparis_urun',
    11 => 'siparis_tel',
    12 => 'siparis_odeme',
    13 => 'siparis_fiyat',
    14 => 'siparis_not',
    15 => 'sd.durum'
];

$orderColIdx = 2;
if (isset($requestData['order'][0]['column'])) {
    $oc = (int) $requestData['order'][0]['column'];
    if (isset($columns[$oc])) {
        $orderColIdx = $oc;
    }
}
$orderDir = (isset($requestData['order'][0]['dir']) && strtolower((string) $requestData['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';
$sortCol = $columns[$orderColIdx];
$orderBy = ' ORDER BY ' . $sortCol . ' ' . $orderDir;
if ($sortCol !== 'siparis_id') {
    $orderBy .= ', siparis_id DESC';
}

$start = isset($requestData['start']) ? intval($requestData['start']) : 0;
$lengthRaw = isset($requestData['length']) ? intval($requestData['length']) : 25;
// -1 = DataTables "Tümü": sayfalama yok, filtrelenmiş tüm satırlar (ekran); Excel yine ayrı tam export
if ($lengthRaw === -1) {
    $limit = '';
} else {
    $length = max(1, min($lengthRaw, 1000));
    $limit = ' LIMIT ' . $start . ' ,' . $length;
}

// Main Query
$query = $db->prepare("SELECT siparis.*, COALESCE(sd.durum,0) AS siparis_verify_durum, COALESCE(sd.dogrulama_kanali,'') AS siparis_verify_kanal FROM siparis LEFT JOIN siparis_dogrulama sd ON sd.siparis_id = siparis.siparis_id" . $where . $orderBy . $limit);
$query->execute($params);

$data = [];
$rows = $query->fetchAll(PDO::FETCH_ASSOC);

// Perform batch duplicate check for ONLY the current page
$ips = array_filter(array_unique(array_column($rows, 'siparis_ip')), function($val) {
    return $val && $val != '::1' && $val != '127.0.0.1';
});
$tels = array_filter(array_unique(array_column($rows, 'siparis_tel')));

$duplicateCheck = ['ip' => [], 'tel' => []];
if (!empty($ips) || !empty($tels)) {
    // Check global counts for these IPs and Tels
    if (!empty($ips)) {
        $ipPlaceholders = implode(',', array_fill(0, count($ips), '?'));
        $ipQuery = $db->prepare("SELECT siparis_ip FROM siparis WHERE siparis_ip IN ($ipPlaceholders) GROUP BY siparis_ip HAVING COUNT(*) > 1");
        $ipQuery->execute(array_values($ips));
        while ($dup = $ipQuery->fetch(PDO::FETCH_ASSOC)) {
            $duplicateCheck['ip'][$dup['siparis_ip']] = true;
        }
    }
    
    if (!empty($tels)) {
        $telPlaceholders = implode(',', array_fill(0, count($tels), '?'));
        $telQuery = $db->prepare("SELECT siparis_tel FROM siparis WHERE siparis_tel IN ($telPlaceholders) GROUP BY siparis_tel HAVING COUNT(*) > 1");
        $telQuery->execute(array_values($tels));
        while ($dup = $telQuery->fetch(PDO::FETCH_ASSOC)) {
            $duplicateCheck['tel'][$dup['siparis_tel']] = true;
        }
    }
}

foreach ($rows as $row) {
    $nestedData = [];
    
    // Duplicate check
    $isDup = (isset($duplicateCheck['ip'][$row['siparis_ip']]) || isset($duplicateCheck['tel'][$row['siparis_tel']]));
    
    // Row attribute for styling
    $nestedData['DT_RowClass'] = $isDup ? 'duplicate-order' : '';
    // Add data attribute for extra robustness if needed by custom JS
    $nestedData['DT_RowAttr'] = ['data-duplicate' => ($isDup ? '1' : '0')];
    $nestedData['DT_RowId'] = 'row_' . $row['siparis_id'];
    
    // Columns
    $nestedData[] = '<div class="checkbox checkbox-primary margin-r-5"><input class="sectum" name="id[]" value="'.$row['siparis_id'].'" id="checkbox'.$row['siparis_id'].'" type="checkbox" onclick="event.stopPropagation();"><label for="checkbox'.$row['siparis_id'].'" style="margin-bottom: 15px;" onclick="event.stopPropagation();"> </label></div>';
    
    $nestedData[] = '<div class="text-center"><a href="javascript:void(0);" class="btn btn-sm btn-info btn-detay-popup" data-id="'.$row['siparis_id'].'" title="Detay Görüntüle"><i class="fa fa-eye"></i></a></div>';
    
    $tarih = $row['siparis_tarih'];
    $nestedData[] = mb_substr($tarih, 0, 10, "UTF-8")." <br> ".mb_substr($tarih, 10, 10, "UTF-8");
    $nestedData[] = $row['siparis_id'];
    $nestedData[] = htmlspecialchars((string) $row['siparis_ip'], ENT_QUOTES, 'UTF-8');
    
    $ad = (string) $row['siparis_ad'];
    $nestedData[] = htmlspecialchars(mb_substr($ad, 0, 20, 'UTF-8'), ENT_QUOTES, 'UTF-8') . (mb_strlen($ad, 'UTF-8') > 20 ? '...' : '');
    $nestedData[] = htmlspecialchars((string) $row['siparis_il'], ENT_QUOTES, 'UTF-8');
    $nestedData[] = htmlspecialchars((string) $row['siparis_ilce'], ENT_QUOTES, 'UTF-8');
    
    $adres = (string) $row['siparis_adres'];
    $nestedData[] = htmlspecialchars(mb_substr($adres, 0, 25, 'UTF-8'), ENT_QUOTES, 'UTF-8') . (mb_strlen($adres, 'UTF-8') > 25 ? '...' : '');

    $fvn = isset($row['siparis_fatura_vn']) ? trim((string) $row['siparis_fatura_vn']) : '';
    $fvd = isset($row['siparis_fatura_vd']) ? trim((string) $row['siparis_fatura_vd']) : '';
    $fun = isset($row['siparis_fatura_unvan']) ? trim((string) $row['siparis_fatura_unvan']) : '';
    $fad = isset($row['siparis_fatura_adres']) ? trim((string) $row['siparis_fatura_adres']) : '';
    if ($fvn === '' && $fvd === '' && $fun === '' && $fad === '') {
        $nestedData[] = '<span class="text-muted">—</span>';
    } else {
        $fatOzet = 'VKN: ' . ($fvn !== '' ? $fvn : '—') . ' · VD: ' . ($fvd !== '' ? mb_substr($fvd, 0, 18, 'UTF-8') : '—');
        $fatTitle = "VKN: $fvn\nVD: $fvd\nÜnvan: $fun\nFatura adresi: $fad";
        $nestedData[] = '<div class="siparis-fatura-cell" style="max-width:220px;font-size:12px;line-height:1.35;" title="' . htmlspecialchars($fatTitle, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($fatOzet . (mb_strlen($fvd, 'UTF-8') > 18 ? '…' : ''), ENT_QUOTES, 'UTF-8') . '</div>';
    }
    
    // Ürün metni: önce strip_tags, parçaları ayrı ayrı kaçır — ham HTML enjekte edilemesin
    $cleanUrunRaw = strip_tags((string) $row['siparis_urun']);
    $cleanUrun = preg_replace('/\|[0-9.]+/', '', $cleanUrunRaw);
    $urunParts = array_map('trim', explode(' | ', $cleanUrun));
    $urunPartsSafe = array();
    foreach ($urunParts as $up) {
        if ($up === '') {
            continue;
        }
        $urunPartsSafe[] = htmlspecialchars($up, ENT_QUOTES, 'UTF-8');
    }
    $displayUrun = implode('<br>• ', $urunPartsSafe);
    $urunTitlePlain = str_replace([' | ', '  '], ['; ', ' '], preg_replace('/\s+/', ' ', $cleanUrun));
    $urunTitleAttr = htmlspecialchars(mb_substr($urunTitlePlain, 0, 500, 'UTF-8') . (mb_strlen($urunTitlePlain, 'UTF-8') > 500 ? '…' : ''), ENT_QUOTES, 'UTF-8');

    $nestedData[] = '<div class="siparis-urun-cell" title="' . $urunTitleAttr . '">'
        . '<span class="siparis-urun-inner">' . $displayUrun . '</span></div>';
    
    $temiz_tel = ltrim(preg_replace('/[^0-9]/', '', $row['siparis_tel']), '0');
    $telHtml = '<strong>' . htmlspecialchars((string) $row['siparis_tel'], ENT_QUOTES, 'UTF-8') . '</strong><div style="margin-top: 5px; display: flex; gap: 5px;">';
    $telHtml .= '<button type="button" class="btn btn-xs btn-default" onclick="copyToClipboard(\''.$temiz_tel.'\', this)" title="Kopyala (0\'sız)"><i class="fa fa-copy" style="font-size: 13px;"></i></button>';
    $telHtml .= '<a href="https://api.whatsapp.com/send?phone=90'.$temiz_tel.'" target="_blank" class="btn btn-xs btn-success" title="WhatsApp"><i class="fa fa-whatsapp" style="font-size: 13px;"></i></a></div>';
    $nestedData[] = $telHtml;
    
    $odeme = $row['siparis_odeme'];
    if ($row['siparis_odemeid'] == 4) {
        $odeme .= ($row['siparis_durumpay'] == 1) ? ' - ok' : ' - no';
    }
    $nestedData[] = htmlspecialchars(mb_substr($odeme, 0, 20, 'UTF-8'), ENT_QUOTES, 'UTF-8') . (mb_strlen($odeme, 'UTF-8') > 20 ? '...' : '');
    $nestedData[] = '<strong>' . (isset($row['siparis_fiyat']) ? number_format(floatval($row['siparis_fiyat']), 2, ',', '.') . ' ₺' : '-') . '</strong>';
    
    $note = (string) $row['siparis_not'];
    $kisaNot = mb_substr($note, 0, 24, 'UTF-8');
    $noteTitleAttr = htmlspecialchars($note, ENT_QUOTES, 'UTF-8');
    $nestedData[] = '<div class="siparis-not-cell" title="' . $noteTitleAttr . '"><small>'
        . htmlspecialchars($kisaNot . (mb_strlen($note, 'UTF-8') > 24 ? '…' : '')) . '</small></div>';

    $verifyDurum = isset($row['siparis_verify_durum']) ? (int)$row['siparis_verify_durum'] : 0;
    $verifyKanal = isset($row['siparis_verify_kanal']) ? trim((string)$row['siparis_verify_kanal']) : '';
    if ($verifyDurum === 1) {
        $kanal = $verifyKanal !== '' ? ' <small>(' . htmlspecialchars($verifyKanal, ENT_QUOTES, 'UTF-8') . ')</small>' : '';
        $nestedData[] = '<div><span class="label label-success" style="font-size:11px;">Doğrulandı</span>' . $kanal
            . '<div style="margin-top:4px;"><a href="controller/function.php?siparis_verify_manual_reset=ok&siparis_id=' . (int)$row['siparis_id'] . '" class="btn btn-xs btn-default" title="Doğrulamayı geri al"><i class="fa fa-undo"></i> Geri Al</a></div></div>';
    } else {
        $nestedData[] = '<div><span class="label label-warning" style="font-size:11px;">Bekliyor</span>'
            . '<div style="margin-top:4px;"><a href="controller/function.php?siparis_verify_manual=ok&siparis_id=' . (int)$row['siparis_id'] . '" class="btn btn-xs btn-success" title="Siparişi manuel doğrula"><i class="fa fa-check"></i> Manuel Doğrula</a></div></div>';
    }
    
    $actions = '<div class="text-center">';
    $actions .= '<a href="siparis-detay.php?siparis_id='.$row['siparis_id'].'" title="Göster" class="btn btn-sm btn-default"><i class="fa fa-eye"></i></a> ';
    $actions .= '<a href="controller/function.php?siparissil=ok&siparis_id='.$row['siparis_id'].'" title="Sil" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>';
    $actions .= '</div>';
    $nestedData[] = $actions;
    
    $data[] = $nestedData;
}

$json_data = [
    "draw"            => intval($requestData['draw']),
    "recordsTotal"    => intval($totalRecords),
    "recordsFiltered" => intval($totalRecords),
    "data"            => $data
];

echo json_encode($json_data);
