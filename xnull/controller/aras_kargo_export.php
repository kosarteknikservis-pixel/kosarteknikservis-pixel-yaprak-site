<?php
include 'config.php';
require_once __DIR__ . '/export_helpers.php';

if (!isset($_SESSION['kullanici_adi'])) {
    die('Yetkisiz erişim.');
}

ini_set('memory_limit', '512M');
set_time_limit(0);

$durum     = isset($_GET['drm']) ? $_GET['drm'] : 'all';
$bas_tarih = isset($_GET['bas_tarih']) ? $_GET['bas_tarih'] : '';
$bit_tarih = isset($_GET['bit_tarih']) ? $_GET['bit_tarih'] : '';

$where  = ' WHERE 1=1';
$params = [];

if ($durum !== 'all' && $durum !== '') {
    $where .= ' AND siparis_durum = :durum';
    $params['durum'] = (int) $durum;
}

if ($bas_tarih !== '') {
    $where .= ' AND siparis_tarih >= :bas_tarih';
    $params['bas_tarih'] = $bas_tarih . ' 00:00:00';
}
if ($bit_tarih !== '') {
    $where .= ' AND siparis_tarih <= :bit_tarih';
    $params['bit_tarih'] = $bit_tarih . ' 23:59:59';
}

$sql = 'SELECT * FROM siparis' . $where . ' ORDER BY siparis_tarih DESC, siparis_id DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);

$ayarRow = $db->query('SELECT ayar_title FROM ayar WHERE ayar_id = 0 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$siteAdi = trim((string) ($ayarRow['ayar_title'] ?? ''));
if ($siteAdi === '') {
    $siteAdi = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'site';
}

$headers = [
    'mok',
    'urun',
    'ad',
    'adres',
    'ilce',
    'sehir',
    'tel',
    'irsaliyeno',
    'ilkodu',
    'ilcekodu',
    'varis',
    'serino',
    'desi',
    'kg',
    'tahsilat tutarı',
    'ödeme tipi',
];

$tmpFile = tempnam(sys_get_temp_dir(), 'arasxlsx');
$zip = new ZipArchive();
$zip->open($tmpFile, ZipArchive::CREATE);

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
$zip->addFromString('[Content_Types].xml', $contentTypes);

$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
$zip->addFromString('_rels/.rels', $rels);

$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="Aras Kargo" sheetId="1" r:id="rId1"/></sheets>
</workbook>';
$zip->addFromString('xl/workbook.xml', $workbook);

$workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>';
$zip->addFromString('xl/styles.xml', $styles);

$sheetHead = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>';

$sheetData = '<row r="1">';
foreach ($headers as $i => $h) {
    $col = chr(65 + $i);
    $sheetData .= '<c r="' . $col . '1" t="inlineStr"><is><t>' . htmlspecialchars((string) $h, ENT_QUOTES, 'UTF-8') . '</t></is></c>';
}
$sheetData .= '</row>';

$rowCount = 2;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tel   = panel_export_normalize_phone($row['siparis_tel'] ?? '');
    $tutar = (int) round((float) ($row['siparis_fiyat'] ?? 0));
    $ad    = (string) ($row['siparis_ad'] ?? '');
    $adres = (string) ($row['siparis_adres'] ?? '');
    $ilce  = (string) ($row['siparis_ilce'] ?? '');
    $il    = (string) ($row['siparis_il'] ?? '');

    $sheetData .= '<row r="' . $rowCount . '">';
    $cells = [
        ['t' => 's', 'v' => '.'],
        ['t' => 's', 'v' => $siteAdi],
        ['t' => 's', 'v' => $ad],
        ['t' => 's', 'v' => $adres],
        ['t' => 's', 'v' => $ilce],
        ['t' => 's', 'v' => $il],
        ['t' => 's', 'v' => $tel],
        ['t' => 's', 'v' => '.'],
        ['t' => 's', 'v' => '.'],
        ['t' => 's', 'v' => '.'],
        ['t' => 's', 'v' => '.'],
        ['t' => 's', 'v' => '.'],
        ['t' => 'n', 'v' => '1'],
        ['t' => 'n', 'v' => '1'],
        ['t' => 'n', 'v' => (string) $tutar],
        ['t' => 's', 'v' => 'kredi'],
    ];
    foreach ($cells as $i => $c) {
        $col = chr(65 + $i);
        if ($c['t'] === 'n') {
            $sheetData .= '<c r="' . $col . $rowCount . '" t="n"><v>' . htmlspecialchars($c['v'], ENT_QUOTES, 'UTF-8') . '</v></c>';
        } else {
            $sheetData .= '<c r="' . $col . $rowCount . '" t="inlineStr"><is><t>' . htmlspecialchars($c['v'], ENT_QUOTES, 'UTF-8') . '</t></is></c>';
        }
    }
    $sheetData .= '</row>';
    $rowCount++;
}

$sheetFoot = '</sheetData></worksheet>';
$zip->addFromString('xl/worksheets/sheet1.xml', $sheetHead . $sheetData . $sheetFoot);
$zip->close();

$filename = 'aras_kargo_' . date('Y-m-d_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
unlink($tmpFile);
exit;
