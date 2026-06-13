<?php
include 'config.php';
require_once __DIR__ . '/export_helpers.php';

if (!isset($_SESSION['kullanici_adi'])) {
    die("Yetkisiz erişim.");
}

// Limitleri artır
ini_set('memory_limit', '2048M');
set_time_limit(0);

$type = isset($_GET['type']) ? $_GET['type'] : 'excel';
$durum = isset($_GET['drm']) ? $_GET['drm'] : 'all';
$bas_tarih = isset($_GET['bas_tarih']) ? $_GET['bas_tarih'] : '';
$bit_tarih = isset($_GET['bit_tarih']) ? $_GET['bit_tarih'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$export_all = isset($_GET['export_all']) ? (int)$_GET['export_all'] : 0;

$where = " WHERE 1=1";
$params = array();

if ($export_all !== 1 && $durum !== 'all') {
    $where .= " AND siparis_durum = :durum";
    $params['durum'] = (int)$durum;
}

if (!empty($bas_tarih)) {
    $where .= " AND siparis_tarih >= :bas_tarih";
    $params['bas_tarih'] = $bas_tarih . " 00:00:00";
}

if (!empty($bit_tarih)) {
    $where .= " AND siparis_tarih <= :bit_tarih";
    $params['bit_tarih'] = $bit_tarih . " 23:59:59";
}

if (!empty($search)) {
    $where .= " AND (siparis_ad LIKE :search OR siparis_tel LIKE :search OR siparis_ip LIKE :search OR CAST(siparis_id AS CHAR) LIKE :search OR siparis_il LIKE :search OR siparis_ilce LIKE :search OR siparis_adres LIKE :search OR siparis_urun LIKE :search OR siparis_not LIKE :search OR siparis_fatura_vn LIKE :search OR siparis_fatura_vd LIKE :search OR siparis_fatura_unvan LIKE :search OR siparis_fatura_adres LIKE :search)";
    $params['search'] = "%$search%";
}

$query = "SELECT * FROM siparis $where ORDER BY siparis_tarih DESC, siparis_id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);

$filename = ($export_all ? "TUM_VERITABANI_" : "Siparisler_") . date('Y-m-d_His');

// ==========================================
// 1. CSV DIŞA AKTAR
// ==========================================
if ($type == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    $output = fopen('php://output', 'w');
    // Excel'in Türkçe karakterleri düzgün açması için BOM (Byte Order Mark) ekliyoruz
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['ID', 'Tarih', 'IP', 'Ad Soyad', 'Telefon', 'Ürün', 'Durum', 'Ödeme', 'Ödeme ID', 'Adres', 'İl', 'İlçe', 'VKN', 'Vergi Dairesi', 'Firma Ünvanı', 'Fatura Adresi', 'Not', 'Fiyat', 'Ödeme Durumu', 'Kargo', 'Takip']);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $telNorm = panel_export_normalize_phone($row['siparis_tel'] ?? '');
        // Excel CSV açılışında telefonu metin hücre olarak zorla
        $telCsv = $telNorm !== '' ? '="' . str_replace('"', '""', $telNorm) . '"' : '';
        fputcsv($output, [
            $row['siparis_id'], 
            $row['siparis_tarih'], 
            $row['siparis_ip'], 
            $row['siparis_ad'], 
            $telCsv,
            strip_tags($row['siparis_urun']), 
            $row['siparis_durum'], 
            $row['siparis_odeme'], 
            $row['siparis_odemeid'],
            $row['siparis_adres'], 
            $row['siparis_il'], 
            $row['siparis_ilce'],
            $row['siparis_fatura_vn'] ?? '',
            $row['siparis_fatura_vd'] ?? '',
            $row['siparis_fatura_unvan'] ?? '',
            $row['siparis_fatura_adres'] ?? '',
            $row['siparis_not'],
            $row['siparis_fiyat'], 
            $row['siparis_durumpay'], 
            $row['siparis_kargo'], 
            $row['siparis_takip']
        ]);
    }
    fclose($output);
    exit;
}



// ==========================================
// 3. NATIVE XLSX DIŞA AKTAR (OpenXML - Varsayılan)
// ==========================================
else {
    $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    $zip->open($tmpFile, ZipArchive::CREATE);

    // 1. [Content_Types].xml
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    </Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypes);

    // 2. _rels/.rels
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    </Relationships>';
    $zip->addFromString('_rels/.rels', $rels);

    // 3. xl/workbook.xml
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets><sheet name="Siparisler" sheetId="1" r:id="rId1"/></sheets>
    </workbook>';
    $zip->addFromString('xl/workbook.xml', $workbook);

    // 4. xl/_rels/workbook.xml.rels
    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    </Relationships>';
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);

    // 5. xl/styles.xml
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
    <fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
    <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
    </styleSheet>';
    $zip->addFromString('xl/styles.xml', $styles);

    // 6. xl/worksheets/sheet1.xml
    $sheetHead = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>';

    $headers = ['ID', 'Tarih', 'IP', 'Ad Soyad', 'Telefon', 'Ürün', 'Durum', 'Ödeme', 'Ödeme ID', 'Adres', 'İl', 'İlçe', 'VKN', 'Vergi Dairesi', 'Firma Ünvanı', 'Fatura Adresi', 'Not', 'Fiyat', 'Ödeme Durumu', 'Kargo', 'Takip'];
    $sheetData = '<row r="1">';
    foreach($headers as $i => $h) {
        $col = ($i < 26) ? chr(65 + $i) : "A" . chr(65 + ($i - 26));
        $sheetData .= '<c r="'.$col.'1" t="inlineStr"><is><t>'.htmlspecialchars((string)$h).'</t></is></c>';
    }
    $sheetData .= '</row>';

    $rowCount = 2;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sheetData .= '<row r="'.$rowCount.'">';
        $cols = [
            $row['siparis_id'], $row['siparis_tarih'], $row['siparis_ip'], $row['siparis_ad'], $row['siparis_tel'],
            strip_tags((string)$row['siparis_urun']), $row['siparis_durum'], $row['siparis_odeme'], $row['siparis_odemeid'],
            $row['siparis_adres'], $row['siparis_il'], $row['siparis_ilce'],
            $row['siparis_fatura_vn'] ?? '', $row['siparis_fatura_vd'] ?? '', $row['siparis_fatura_unvan'] ?? '', $row['siparis_fatura_adres'] ?? '',
            $row['siparis_not'],
            $row['siparis_fiyat'], $row['siparis_durumpay'], $row['siparis_kargo'], $row['siparis_takip']
        ];
        
        foreach($cols as $i => $val) {
            $colName = ($i < 26) ? chr(65 + $i) : "A" . chr(65 + ($i - 26));
            // Telefon (sütun E / index 4): asla sayı tipi verme — Excel bilimsel gösterim ve karışık sütun yapıyordu
            if ($i === 4) {
                $phoneOut = panel_export_normalize_phone($val);
                $sheetData .= '<c r="'.$colName.$rowCount.'" t="inlineStr"><is><t>'.htmlspecialchars($phoneOut, ENT_QUOTES, 'UTF-8').'</t></is></c>';
                continue;
            }
            $valType = is_numeric($val) ? 'n' : 'inlineStr';
            if ($valType == 'n' && $val !== '') {
                $sheetData .= '<c r="'.$colName.$rowCount.'" t="n"><v>'.$val.'</v></c>';
            } else {
                $sheetData .= '<c r="'.$colName.$rowCount.'" t="inlineStr"><is><t>'.htmlspecialchars((string)$val).'</t></is></c>';
            }
        }
        $sheetData .= '</row>';
        $rowCount++;
    }

    $sheetFoot = '</sheetData></worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetHead . $sheetData . $sheetFoot);

    $zip->close();

    // Dosyayı indir
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'.xlsx"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}
?>
