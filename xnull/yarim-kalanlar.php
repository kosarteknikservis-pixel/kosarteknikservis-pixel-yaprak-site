<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

// Silme işlemi
if (isset($_GET['sil']) && $_GET['sil'] == "ok") {
    $sil = $db->prepare("DELETE FROM yarim_kalanlar WHERE id=:id");
    $kontrol = $sil->execute(['id' => $_GET['id']]);
    if ($kontrol) {
        Header("Location:yarim-kalanlar.php?status=ok");
        exit;
    } else {
        Header("Location:yarim-kalanlar.php?status=no");
        exit;
    }
}

// Tümünü temizle
if (isset($_GET['temizle']) && $_GET['temizle'] == "ok") {
    $db->exec("DELETE FROM yarim_kalanlar");
    Header("Location:yarim-kalanlar.php?status=ok");
    exit;
}

// Tarih Filtreleme
$bas_tarih = isset($_GET['bas_tarih']) ? $_GET['bas_tarih'] : '';
$bit_tarih = isset($_GET['bit_tarih']) ? $_GET['bit_tarih'] : '';

$tarih_sorgusu = "";
$tarih_parametreleri = array();

if (!empty($bas_tarih)) {
    $tarih_sorgusu .= " WHERE tarih >= :bas_tarih";
    $tarih_parametreleri['bas_tarih'] = $bas_tarih . " 00:00:00";
}

if (!empty($bit_tarih)) {
    $tarih_sorgusu .= (!empty($tarih_sorgusu) ? " AND" : " WHERE") . " tarih <= :bit_tarih";
    $tarih_parametreleri['bit_tarih'] = $bit_tarih . " 23:59:59";
}
?>

<section class="main-content container">
    <div class="page-header">
        <div class="pull-right">
            <a href="yarim-kalanlar.php?temizle=ok" class="btn btn-danger" onclick="return confirm('Tüm kayıtları silmek istediğinize emin misiniz?')"><i class="fa fa-trash"></i> Tümünü Temizle</a>
        </div>
        <h2>Yarım Kalan Siparişler</h2>
        <small>Müşteri formu doldurmaya başlayıp siparişi tamamlamadan çıkanlar burada görünür.</small>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    Tarih Filtreleme
                </div>
                <div class="card-block">
                    <form method="GET" class="form-inline" id="dateFilterForm">
                        <div class="form-group" style="margin-right: 15px;">
                            <label style="margin-right: 10px;">Başlangıç:</label>
                            <input type="date" name="bas_tarih" id="bas_tarih" class="form-control" value="<?php echo $bas_tarih; ?>">
                        </div>
                        <div class="form-group" style="margin-right: 15px;">
                            <label style="margin-right: 10px;">Bitiş:</label>
                            <input type="date" name="bit_tarih" id="bit_tarih" class="form-control" value="<?php echo $bit_tarih; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filtrele</button>
                        <a href="yarim-kalanlar.php" class="btn btn-default"><i class="fa fa-refresh"></i> Sıfırla</a>
                    </form>
                    
                    <div style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 5px;">
                        <button type="button" class="btn btn-sm btn-info" onclick="setQuickDate('bugun')">Bugün</button>
                        <button type="button" class="btn btn-sm btn-info" onclick="setQuickDate('dun')">Dün</button>
                        <button type="button" class="btn btn-sm btn-info" onclick="setQuickDate('dunbugun')">Dün ve Bugün</button>
                        <button type="button" class="btn btn-sm btn-info" onclick="setQuickDate('7gun')">Son 7 Gün</button>
                        <button type="button" class="btn btn-sm btn-info" onclick="setQuickDate('14gun')">Son 14 Gün</button>
                        <button type="button" class="btn btn-sm btn-info" onclick="setQuickDate('30gun')">Son 30 Gün</button>
                        <button type="button" class="btn btn-sm btn-default" onclick="setQuickDate('hepsi')">Tüm Zamanlar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-block">
                    <table id="datatable_export" class="table table-hover mobile-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Müşteri Ad Soyad</th>
                                <th>Telefon</th>
                                <th>Seçilen Ürün</th>
                                <th>IP Adresi</th>
                                <th>Tarih</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                             $sorgu = $db->prepare("SELECT * FROM yarim_kalanlar" . $tarih_sorgusu . " ORDER BY id DESC");
                             $sorgu->execute($tarih_parametreleri);
                             while($cek = $sorgu->fetch(PDO::FETCH_ASSOC)) {
                                 // Hata kontrollü Sipariş Kontrolü (Daha önce veya sonra sipariş vermiş mi?)
                                 $converted = false;
                                 try {
                                     $clean_tel = ltrim(preg_replace('/[^0-9]/', '', $cek['tel']), '0');
                                     if (!empty($clean_tel) || !empty($cek['ip'])) {
                                         $checkSip = $db->prepare("SELECT siparis_id FROM siparis WHERE (siparis_tel LIKE :tel OR siparis_ip = :ip) LIMIT 1");
                                         $checkSip->execute([
                                             'tel' => '%' . $clean_tel,
                                             'ip' => $cek['ip']
                                         ]);
                                         if ($checkSip->rowCount() > 0) {
                                             $converted = true;
                                         }
                                     }
                                 } catch (Exception $e) {
                                     // Hata durumunda sessiz kal, renklendirme yapma
                                 }
                             ?>
                             <tr <?php echo $converted ? 'style="background-color: #ffebee; border-left: 5px solid #ef5350;"' : ''; ?>>
                                <td><?php echo $cek['id']; ?></td>
                                <td><?php echo $cek['ad'] ?: '<em class="text-muted">Girilmedi</em>'; ?></td>
                                <td>
                                    <?php if($cek['tel']) { 
                                        $temiz_tel = ltrim(preg_replace('/[^0-9]/', '', $cek['tel']), '0');
                                    ?>
                                        <strong><?php echo $cek['tel']; ?></strong>
                                        <div style="margin-top: 5px; display: flex; gap: 5px;">
                                            <button type="button" class="btn btn-xs btn-default" onclick="copyToClipboard('<?php echo $temiz_tel; ?>', this)" title="Kopyala (0'sız)">
                                                <i class="fa fa-copy" style="font-size: 13px;"></i>
                                            </button>
                                            <?php 
                                            $wa_sablon = $settingsprint['ayar_wa_sablon'] ?? '';
                                            $replacements = [
                                                '{ad}' => $cek['ad'] ?: '',
                                                '{urun}' => $cek['urun'] ?: '',
                                                '{fiyat}' => $cek['fiyat'] ? number_format(floatval(str_replace(',', '.', strval($cek['fiyat']))), 2, ',', '.') . ' TL' : ''
                                            ];
                                            $wa_mesaj = strtr($wa_sablon, $replacements);
                                            ?>
                                            <a href="https://api.whatsapp.com/send?phone=90<?php echo $temiz_tel; ?>&text=<?php echo urlencode($wa_mesaj); ?>" target="_blank" class="btn btn-xs btn-success" title="WhatsApp">
                                                <i class="fa fa-whatsapp" style="font-size: 13px;"></i>
                                            </a>
                                        </div>
                                    <?php } else { ?>
                                        <em class="text-muted">Girilmedi</em>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if(!empty($cek['urun'])) { ?>
                                        <div style="font-weight: 700; color: #2c3e50;"><?php echo $cek['urun']; ?></div>
                                        <?php if(!empty($cek['fiyat'])) { ?>
                                            <div class="label label-danger" style="display: inline-block; margin-top: 4px;">
                                                <?php 
                                                $fVal = floatval(str_replace(',', '.', strval($cek['fiyat'])));
                                                echo number_format($fVal, 2, ',', '.'); 
                                                ?> TL
                                            </div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <em class="text-muted">Seçilmedi</em>
                                    <?php } ?>
                                </td>
                                <td><small><?php echo $cek['ip']; ?></small></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($cek['tarih'])); ?></td>
                                <td class="text-center">
                                    <?php if($cek['tel']) { ?>
                                        <a href="tel:<?php echo $cek['tel']; ?>" class="btn btn-xs btn-success" title="Ara"><i class="fa fa-phone"></i></a>
                                    <?php } ?>
                                    <a href="yarim-kalanlar.php?sil=ok&id=<?php echo $cek['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
<script>
$(document).ready(function() {
    $('#datatable_export').DataTable({
        dom: 'Blfrtip',
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, "Tümü"]],
        buttons: [
            'copyHtml5',
            {
                extend: 'excelHtml5',
                title: 'Yarım Kalan Siparişler Listesi',
                filename: 'yarim-kalanlar-' + new Date().toISOString().slice(0,10),
                charset: 'UTF-8',
                bom: true,
                exportOptions: {
                    format: {
                        body: function (data, row, column, node) {
                            data = data.replace(/<[^>]*>/g, '');
                            data = data.replace(/&nbsp;/g, ' ');
                            data = data.replace(/&amp;/g, '&');
                            return data.trim();
                        }
                    }
                }
            },
            {
                extend: 'csvHtml5',
                title: 'Yarım Kalan Siparişler Listesi',
                filename: 'yarim-kalanlar-' + new Date().toISOString().slice(0,10),
                charset: 'UTF-8',
                bom: true,
                exportOptions: {
                    format: {
                        body: function (data, row, column, node) {
                            data = data.replace(/<[^>]*>/g, '');
                            data = data.replace(/&nbsp;/g, ' ');
                            data = data.replace(/&amp;/g, '&');
                            return data.trim();
                        }
                    }
                }
            },
            'pdfHtml5'
        ],
        language: {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
        },
        order: [[0, "desc"]]
    });
});

function setQuickDate(range) {
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);
    
    const formatDate = (date) => {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    };

    let start = '';
    let end = '';

    switch(range) {
        case 'bugun':
            start = formatDate(today);
            end = formatDate(today);
            break;
        case 'dun':
            start = formatDate(yesterday);
            end = formatDate(yesterday);
            break;
        case 'dunbugun':
            start = formatDate(yesterday);
            end = formatDate(today);
            break;
        case '7gun':
            const sevenDaysAgo = new Date();
            sevenDaysAgo.setDate(today.getDate() - 7);
            start = formatDate(sevenDaysAgo);
            end = formatDate(today);
            break;
        case '14gun':
            const fourteenDaysAgo = new Date();
            fourteenDaysAgo.setDate(today.getDate() - 14);
            start = formatDate(fourteenDaysAgo);
            end = formatDate(today);
            break;
        case '30gun':
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(today.getDate() - 30);
            start = formatDate(thirtyDaysAgo);
            end = formatDate(today);
            break;
        case 'hepsi':
            start = '';
            end = '';
            break;
    }

    document.getElementById('bas_tarih').value = start;
    document.getElementById('bit_tarih').value = end;
    document.getElementById('dateFilterForm').submit();
}
</script>
