<?php
include 'config.php';

if (!isset($_SESSION['kullanici_adi'])) {
    die(json_encode(['error' => 'Yetkisiz erişim.']));
}

$requestData = $_REQUEST;

// Base query parameters
$form_id = isset($requestData['form_id']) ? intval($requestData['form_id']) : 0;
if ($form_id == 0) {
    die(json_encode(['error' => 'Geçersiz Form ID.']));
}

$where = " WHERE form_id = :form_id";
$params = ['form_id' => $form_id];

// Search - Form başvuruları içinde veya değerler içinde ara
if (!empty($requestData['search']['value'])) {
    $search = $requestData['search']['value'];
    $where .= " AND (basvuru_ip LIKE :search OR basvuru_id LIKE :search OR EXISTS (SELECT 1 FROM form_degerler fd WHERE fd.basvuru_id = fb.basvuru_id AND fd.deger LIKE :search))";
    $params['search'] = "%$search%";
}

// Total records
$totalQuery = $db->prepare("SELECT COUNT(*) as total FROM form_basvurular fb" . $where);
$totalQuery->execute($params);
$totalData = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalData['total'];

// Alanları çek (Sütunlar için)
$alanlarSor = $db->prepare("SELECT * FROM form_alanlari WHERE form_id = :form_id ORDER BY alan_sira ASC");
$alanlarSor->execute(['form_id' => $form_id]);
$alanlar = $alanlarSor->fetchAll(PDO::FETCH_ASSOC);

// Order (DataTables indexlerine göre dinamik)
$orderBy = " ORDER BY basvuru_id DESC"; // Default
if (isset($requestData['order'])) {
    // 0: Checkbox, 1: ID, 2: Tarih, 3: IP, 4: Durum, 5+: Dinamik Alanlar
    $orderCol = intval($requestData['order'][0]['column']);
    $orderDir = $requestData['order'][0]['dir'];
    
    if ($orderCol == 1) $orderBy = " ORDER BY basvuru_id " . $orderDir;
    else if ($orderCol == 2) $orderBy = " ORDER BY basvuru_tarih " . $orderDir;
    else if ($orderCol == 3) $orderBy = " ORDER BY basvuru_ip " . $orderDir;
    else if ($orderCol == 4) $orderBy = " ORDER BY basvuru_durum " . $orderDir;
}

$limit = " LIMIT " . intval($requestData['start']) . " ," . intval($requestData['length']);

// Main Query
$query = $db->prepare("SELECT * FROM form_basvurular fb" . $where . $orderBy . $limit);
$query->execute($params);
$rows = $query->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($rows as $row) {
    $nestedData = [];
    $b_id = $row['basvuru_id'];
    
    // 0. Checkbox
    $nestedData[] = '<div class="checkbox checkbox-primary"><input class="sectum" name="id[]" value="'.$b_id.'" id="checkbox'.$b_id.'" type="checkbox"><label for="checkbox'.$b_id.'"> </label></div>';
    
    // 1. ID
    $nestedData[] = $b_id;
    
    // 2. Tarih
    $nestedData[] = date('d.m.Y H:i', strtotime($row['basvuru_tarih']));
    
    // 3. IP
    $nestedData[] = $row['basvuru_ip'];
    
    // 4. Durum
    $statusClass = 'label-warning';
    $statusText = 'Yeni';
    if ($row['basvuru_durum'] == 1) { $statusClass = 'label-info'; $statusText = 'İncelendi'; }
    elseif ($row['basvuru_durum'] == 2) { $statusClass = 'label-success'; $statusText = 'Onaylandı'; }
    elseif ($row['basvuru_durum'] == 3) { $statusClass = 'label-danger'; $statusText = 'Reddedildi'; }
    $nestedData[] = '<span class="label '.$statusClass.'">'.$statusText.'</span>';
    
    // 5+. Dinamik Alan Değerleri
    $degerSor = $db->prepare("SELECT alan_id, deger FROM form_degerler WHERE basvuru_id = :b_id");
    $degerSor->execute(['b_id' => $b_id]);
    $degerlerRaw = $degerSor->fetchAll(PDO::FETCH_KEY_PAIR); // alan_id => deger
    
    foreach ($alanlar as $alan) {
        $a_id = $alan['alan_id'];
        $val = isset($degerlerRaw[$a_id]) ? $degerlerRaw[$a_id] : '-';
        
        if ($alan['alan_tip'] == 'file' && !empty($val) && $val != '-') {
            $nestedData[] = '<a href="../'.$val.'" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-download"></i></a>';
        } else {
            $nestedData[] = mb_substr($val, 0, 50, 'UTF-8') . (mb_strlen($val, 'UTF-8') > 50 ? '...' : '');
        }
    }
    
    // Last: İşlemler
    $nestedData[] = '<div class="text-center">
                        <a href="form-basvuru-detay.php?id='.$b_id.'" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>
                        <a href="controller/function.php?basvurusil=ok&id='.$b_id.'&form_id='.$form_id.'" onclick="return confirm(\'Silmek istediğinize emin misiniz?\')" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a>
                    </div>';
    
    $data[] = $nestedData;
}

$json_data = [
    "draw"            => intval($requestData['draw']),
    "recordsTotal"    => intval($totalRecords),
    "recordsFiltered" => intval($totalRecords),
    "data"            => $data
];

echo json_encode($json_data);
?>
