<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$settingsprint = $db->query('SELECT * FROM ayar WHERE ayar_id=0')->fetch(PDO::FETCH_ASSOC) ?: [];

date_default_timezone_set('Europe/Istanbul');
if (@$_GET['drm']) {
    $durum = htmlspecialchars(strip_tags(trim($_GET['drm'])));
    $urunedit=$db->prepare("SELECT * from durum where id=:urun_id");
    $urunedit->execute(array(
        'urun_id' => $durum
    ));
    $urunwrite=$urunedit->fetch(PDO::FETCH_ASSOC);
    if (!$urunwrite) {
        $urunwrite = array('ad' => 'Tanımsız Durum (' . $durum . ')');
    }
} else {
    $durum = 0;
    $urunwrite = array('ad' => 'Yeni Gelen Siparişler');
}

// Üstteki durum sekmelerinde hangi drm seçili (URL yoksa 0 = Yeni Gelen)
$drm_tab_selected = isset($_GET['drm']) ? (string) $_GET['drm'] : '0';

// Tarih Filtreleme
$bas_tarih = isset($_GET['bas_tarih']) ? $_GET['bas_tarih'] : '';
$bit_tarih = isset($_GET['bit_tarih']) ? $_GET['bit_tarih'] : '';

$tarih_sorgusu = "";
$tarih_parametreleri = array();

if (!empty($bas_tarih)) {
    $tarih_sorgusu .= " AND siparis_tarih >= :bas_tarih";
    $tarih_parametreleri['bas_tarih'] = $bas_tarih . " 00:00:00";
}

if (!empty($bit_tarih)) {
    $tarih_sorgusu .= " AND siparis_tarih <= :bit_tarih";
    $tarih_parametreleri['bit_tarih'] = $bit_tarih . " 23:59:59";
}

// Sipariş sayısını al (DataTables server-side için)
$totalQuery = $db->prepare("SELECT COUNT(*) as total from siparis where siparis_durum=:durum" . $tarih_sorgusu);
$totalQuery->execute(array_merge(array('durum' => $durum), $tarih_parametreleri));
$totalRecords = $totalQuery->fetch(PDO::FETCH_ASSOC)['total'];

if (isset($_POST['topluislem'])) {

    if ($_POST['islem']=="sil") {

        foreach ($_POST['id'] as $key) {
            $sil    = $db->prepare( "DELETE from siparis where siparis_id=:id" );
            $kontrol = $sil->execute(
                array( 'id' => $key ));
        }
        if (@$_GET['drm']) {
            Header( "Location:?drm=$durum&status=ok" );
            exit;
        } else {            
            Header( "Location:?status=ok" );
            exit;
        }
    } else {
        foreach ($_POST['id'] as $key) {
            $ayarkaydet = $db->prepare(
                "UPDATE siparis SET
                siparis_durum=:durum
                WHERE siparis_id=:id"
            );
            $update = $ayarkaydet->execute(
                array( 
                    'durum' => $_POST['islem'],
                    'id'    => $key
                ));
        }
        if (@$_GET['drm']) {
            Header( "Location:?drm=$durum&status=ok" );
            exit;
        } else {            
            Header( "Location:?status=ok" );
            exit;
        }

    }
}

$toplu_islem_options_html = '';
$urunsor_opts = $db->prepare('SELECT * from durum order by siralama ASC, id ASC');
$urunsor_opts->execute();
foreach ($urunsor_opts as $key) {
    if ($key['id'] != $durum) {
        $toplu_islem_options_html .= '<option value="' . (int) $key['id'] . '">Seçilenleri Taşı =>> ' . htmlspecialchars($key['ad'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
}
if ($durum != 0) {
    $toplu_islem_options_html .= '<option value="0"> Seçilenleri Taşı =>> Yeni Gelen Siparişler</option>';
}
$toplu_islem_options_html .= '<option value="sil"> Seçilenleri Sil</option>';

$parasut_configured = !empty($settingsprint['ayar_parasut_enabled'])
    && trim((string) ($settingsprint['ayar_parasut_company_id'] ?? '')) !== ''
    && trim((string) ($settingsprint['ayar_parasut_client_id'] ?? '')) !== ''
    && trim((string) ($settingsprint['ayar_parasut_client_secret'] ?? '')) !== ''
    && trim((string) ($settingsprint['ayar_parasut_username'] ?? '')) !== ''
    && trim((string) ($settingsprint['ayar_parasut_password'] ?? '')) !== '';
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Sipariş İşlemleri</h2>
    </div>
    
    <!-- Toplam Sipariş İstatistikleri -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 20px; color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="row" style="margin: 0; display: flex; flex-wrap: wrap;">
                    <div class="col-md-3 col-sm-6 col-xs-6" style="margin-bottom: 15px; padding: 0 10px; width: 100%;">
                        <?php 
                        $toplamSiparis = $db->prepare("SELECT COUNT(*) as toplam FROM siparis WHERE 1=1" . $tarih_sorgusu);
                        $toplamSiparis->execute($tarih_parametreleri);
                        $toplamSiparisSayi = $toplamSiparis->fetch(PDO::FETCH_ASSOC)['toplam'];
                        ?>
                        <div style="text-align: center; padding: 15px; background: rgba(255,255,255,0.15); border-radius: 8px; min-height: 80px; display: flex; flex-direction: column; justify-content: center;">
                            <div style="font-size: 32px; font-weight: 800; margin-bottom: 5px; line-height: 1;"><?php echo $toplamSiparisSayi; ?></div>
                            <div style="font-size: 13px; opacity: 0.95; line-height: 1.2; font-weight: 500;">Toplam Sipariş</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-6" style="margin-bottom: 15px; padding: 0 10px; width: 100%;">
                        <?php 
                        $yeniSiparis = $db->prepare("SELECT COUNT(*) as toplam FROM siparis WHERE siparis_durum=0" . $tarih_sorgusu);
                        $yeniSiparis->execute($tarih_parametreleri);
                        $yeniSiparisSayi = $yeniSiparis->fetch(PDO::FETCH_ASSOC)['toplam'];
                        ?>
                        <div style="text-align: center; padding: 15px; background: rgba(255,255,255,0.15); border-radius: 8px; min-height: 80px; display: flex; flex-direction: column; justify-content: center;">
                            <div style="font-size: 32px; font-weight: 800; margin-bottom: 5px; line-height: 1;"><?php echo $yeniSiparisSayi; ?></div>
                            <div style="font-size: 13px; opacity: 0.95; line-height: 1.2; font-weight: 500;">Yeni Gelen</div>
                        </div>
                    </div>
                    <?php 
                    $durumlarSor = $db->prepare("SELECT * FROM durum ORDER BY siralama ASC, id ASC");
                    $durumlarSor->execute();
                    $durumIndex = 0;
                    while($durumCek = $durumlarSor->fetch(PDO::FETCH_ASSOC)) {
                        $durumIndex++;
                        $durumSayi = $db->prepare("SELECT COUNT(*) as toplam FROM siparis WHERE siparis_durum=:durum" . $tarih_sorgusu);
                        $durumSayi->execute(array_merge(array('durum' => $durumCek['id']), $tarih_parametreleri));
                        $durumSayiSonuc = $durumSayi->fetch(PDO::FETCH_ASSOC)['toplam'];
                        ?>
                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom: 15px; padding: 0 10px;">
                            <div style="text-align: center; padding: 15px; background: rgba(255,255,255,0.15); border-radius: 8px; min-height: 80px; display: flex; flex-direction: column; justify-content: center;">
                                <div style="font-size: 32px; font-weight: 800; margin-bottom: 5px; line-height: 1;"><?php echo $durumSayiSonuc; ?></div>
                                <div style="font-size: 13px; opacity: 0.95; line-height: 1.2; font-weight: 500;"><?php echo htmlspecialchars($durumCek['ad']); ?></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <style>
                @media (min-width: 992px) {
                    .col-md-3.col-sm-4.col-xs-6 {
                        width: 25% !important;
                    }
                }
                @media (max-width: 991px) and (min-width: 769px) {
                    .col-md-3.col-sm-4.col-xs-6 {
                        width: 33.333% !important;
                    }
                }
                @media (max-width: 768px) {
                    .col-md-3.col-sm-4.col-xs-6 {
                        width: 50% !important;
                        float: left !important;
                    }
                }
                </style>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    Tarih Filtreleme
                </div>
                <div class="card-block">
                    <form method="GET" class="form-inline" id="dateFilterForm">
                        <?php if(isset($_GET['drm'])) { ?>
                            <input type="hidden" name="drm" value="<?php echo htmlspecialchars($_GET['drm']); ?>">
                        <?php } ?>
                        <div class="form-group" style="margin-right: 15px;">
                            <label style="margin-right: 10px;">Başlangıç:</label>
                            <input type="date" name="bas_tarih" id="bas_tarih" class="form-control" value="<?php echo $bas_tarih; ?>">
                        </div>
                        <div class="form-group" style="margin-right: 15px;">
                            <label style="margin-right: 10px;">Bitiş:</label>
                            <input type="date" name="bit_tarih" id="bit_tarih" class="form-control" value="<?php echo $bit_tarih; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filtrele</button>
                        <a href="siparisler.php<?php echo isset($_GET['drm']) ? '?drm='.htmlspecialchars($_GET['drm']) : ''; ?>" class="btn btn-default"><i class="fa fa-refresh"></i> Sıfırla</a>
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

                <script>
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
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="btn-group-mobile" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;">
                <?php 
                $date_params = "";
                if(!empty($bas_tarih)) $date_params .= "&bas_tarih=".$bas_tarih;
                if(!empty($bit_tarih)) $date_params .= "&bit_tarih=".$bit_tarih;
                ?>
                <?php 
                // Yeni Gelenler için toplam sayı
                $yeniToplamSor = $db->prepare("SELECT COUNT(*) as toplam FROM siparis WHERE siparis_durum=0");
                $yeniToplamSor->execute();
                $yeniToplamSayi = $yeniToplamSor->fetch(PDO::FETCH_ASSOC)['toplam'];
                ?>
                <a href="siparisler.php?drm=0<?php echo $date_params; ?>" class="siparis-drm-tab btn <?php echo ($drm_tab_selected === '0') ? 'btn-danger' : 'btn-success'; ?>" style="text-align: center; padding: 12px 20px; font-weight: 600; border-radius: 8px; text-decoration: none; display: block; width: 100%;">Yeni Gelen Siparişler <span style="background: rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 12px; font-size: 12px; margin-left: 8px; font-weight: 700;"><?php echo $yeniToplamSayi; ?></span></a>
                <?php 
                $urunsor=$db->prepare("SELECT * from durum order by siralama ASC, id ASC");
                $urunsor->execute();
                foreach ($urunsor as $key) { 
                    // Her durum için toplam sayı
                    $durumToplam = $db->prepare("SELECT COUNT(*) as toplam FROM siparis WHERE siparis_durum=:durum");
                    $durumToplam->execute(array('durum' => $key['id']));
                    $durumToplamSayi = $durumToplam->fetch(PDO::FETCH_ASSOC)['toplam'];
                    ?>
                <a href="siparisler.php?drm=<?=$key['id']?><?php echo $date_params; ?>" class="siparis-drm-tab btn <?php echo ($drm_tab_selected === (string) (int) $key['id']) ? 'btn-danger' : 'btn-success'; ?>" style="text-align: center; padding: 12px 20px; font-weight: 600; border-radius: 8px; text-decoration: none; display: block; width: 100%;"><?=$key['ad']?> <span style="background: rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 12px; font-size: 12px; margin-left: 8px; font-weight: 700;"><?php echo $durumToplamSayi; ?></span></a>
                <?php } ?>
            </div>
            <style>
            @media (max-width: 768px) {
                .btn-group-mobile {
                    flex-direction: column;
                    gap: 10px;
                    width: 100%;
                }
                .btn-group-mobile .btn,
                .btn-group-mobile a {
                    width: 100% !important;
                    margin-bottom: 0 !important;
                    display: block !important;
                    flex: none !important;
                    min-width: auto !important;
                    box-sizing: border-box;
                }
            }
            @media (min-width: 769px) {
                .btn-group-mobile {
                    flex-direction: row;
                }
                .btn-group-mobile .btn,
                .btn-group-mobile a {
                    width: auto !important;
                    display: inline-block !important;
                    flex: 1 1 auto;
                    min-width: 120px;
                }
                .btn-group-mobile .btn:not(:last-child),
                .btn-group-mobile a:not(:last-child) {
                    margin-right: 8px;
                }
            }
            @media (max-width: 768px) {
                .mobile-table {
                    display: block;
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                .mobile-hide {
                    display: none !important;
                }
            }
            /* Ürün sütunu: satır yüksekliği kontrolü (Excel/CSV full_export.php DB’den tam metin alır) */
            #datatable_custom_siparis tbody td {
                vertical-align: top !important;
            }
            #datatable_custom_siparis .siparis-urun-cell {
                max-width: 300px;
                line-height: 1.35;
                font-size: 12px;
                color: #333;
                cursor: help;
            }
            #datatable_custom_siparis .siparis-urun-inner {
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 4;
                overflow: hidden;
                word-break: break-word;
            }
            #datatable_custom_siparis .siparis-not-cell {
                max-width: 160px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            /* Tablo üstü yatay kaydırma (alttaki ile senkron) */
            .siparis-scroll-bundle {
                width: 100%;
                position: relative;
            }
            .siparis-scroll-top {
                overflow-x: auto;
                overflow-y: hidden;
                width: 100%;
                min-height: 18px;
                margin-bottom: 8px;
                border-radius: 4px;
                background: #eef1f5;
                scrollbar-width: thin;
            }
            .siparis-scroll-top::-webkit-scrollbar {
                height: 10px;
            }
            .siparis-scroll-top::-webkit-scrollbar-thumb {
                background: #b0bec5;
                border-radius: 5px;
            }
            .siparis-scroll-top-inner {
                height: 1px;
                pointer-events: none;
            }
            .siparis-scroll-main {
                overflow-x: auto;
                width: 100%;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }
            .siparis-scroll-main::-webkit-scrollbar {
                height: 10px;
            }
            .siparis-scroll-main::-webkit-scrollbar-thumb {
                background: #90a4ae;
                border-radius: 5px;
            }
            /* Yatay kaydırma tek yerde olsun (üst çubuk senkronu için) */
            .siparis-scroll-bundle .mobile-table {
                overflow-x: visible !important;
            }
            /* Tablo %100 sıkışınca yatay scroll oluşmuyordu; başlık + gövde birlikte kayar */
            .siparis-scroll-main {
                min-width: 0;
            }
            .siparis-scroll-bundle #datatable_custom_siparis_wrapper.dataTables_wrapper {
                width: 100% !important;
                min-width: 0;
                overflow-x: visible;
            }
            @media (min-width: 992px) {
                .siparis-scroll-bundle #datatable_custom_siparis.dataTable,
                .siparis-scroll-bundle table#datatable_custom_siparis {
                    width: max-content !important;
                    min-width: 1320px !important;
                    max-width: none !important;
                    table-layout: auto !important;
                }
                .siparis-scroll-bundle #datatable_custom_siparis thead th,
                .siparis-scroll-bundle #datatable_custom_siparis thead td {
                    position: static !important;
                    top: auto !important;
                }
                /* overflow-x:auto iken tarayıcı overflow-y'yi auto yapıyor; dikey tekerlek sayfaya gitsin */
                .siparis-scroll-main {
                    overflow-x: auto;
                    overflow-y: clip;
                }
            }
            </style>
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10" style="margin-bottom: 10px;">
                        <div id="header_export_buttons" style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end;">
                            <a href="siparis-arsivi.php" class="btn btn-info btn-icon"><i class="fa fa-archive"></i> <span class="btn-text">Sipariş Arşivi</span></a>
                            <a href="controller/full_export.php?type=excel&export_all=1" class="btn btn-danger btn-icon" title="Filtre olmaksızın TÜM veritabanını indirir"><i class="fa fa-database"></i> <span class="btn-text">Tüm Veritabanını İndir</span></a>
                            <a href="controller/siparis_export.php?bas_tarih=<?php echo $bas_tarih; ?>&bit_tarih=<?php echo $bit_tarih; ?>" class="btn btn-success btn-icon"><i class="fa fa-download"></i> <span class="btn-text">Dışa Aktar (.zip)</span></a>
                            <button type="button" class="btn btn-warning btn-icon" data-toggle="modal" data-target="#importModal"><i class="fa fa-upload"></i> <span class="btn-text">İçe Aktar (.zip)</span></button>
                        </div>
                        <p class="text-muted siparis-tablo-ipucu" style="font-size: 12px; margin: 10px 0 0; clear: both; text-align: right; max-width: 100%;">
                            <i class="fa fa-info-circle"></i> Ürün ve not sütunları özet gösterilir; tam metin için satıra gelince ipucu veya <strong>Detay</strong>. Excel/CSV dışa aktarma veritabanındaki tam içeriği indirir.
                        </p>
                        <style>
                        @media (max-width: 768px) {
                            .pull-right.mt-10 {
                                margin-bottom: 15px !important;
                                width: 100% !important;
                                float: none !important;
                            }
                            .pull-right.mt-10 > div {
                                flex-direction: column !important;
                                width: 100% !important;
                                gap: 10px !important;
                            }
                            .pull-right.mt-10 .btn {
                                width: 100% !important;
                                margin-bottom: 0 !important;
                                min-width: auto !important;
                                flex: none !important;
                                padding: 12px 20px !important;
                                display: block !important;
                                text-align: center !important;
                            }
                            .pull-right.mt-10 .btn-text {
                                display: inline !important;
                                margin-left: 8px;
                            }
                            .pull-right.mt-10 .btn-icon {
                                padding: 12px 20px !important;
                            }
                        }
                        </style>
                    </div>
                    <?php if (@$_GET['drm']) {
                            echo "Siparişler (". $urunwrite['ad'].")";
                        } else { ?>
                    Yeni Gelen Siparişler
                    <?php } ?><br><br>
                    <!-- <b style="margin-top: 150px; background: #f00; color: #fff; padding: 2px;"> ALTTAKİ BUTONLAR 31 TIKLAMADAN SONRA İMHA OLACAKTIR! 24 SAATTE 1 SIFIRLANIR. SALI GÜNÜ GECESİNDEN SONRA HİÇ BİR ŞEKİLDE TIKLANAMAYACAKTIR.</b> -->
                </div>
                <form action="" method="POST">
                    <div class="card-block" style="padding-bottom: 8px;">
                        <div class="row" style="margin-bottom: 12px;">
                            <div class="col-md-3 col-sm-6">
                                <label><b>İŞLEM</b></label>
                                <select class="form-control m-b toplu-islem-select-top" title="Alt satırdaki işlemle senkron">
                                    <?php echo $toplu_islem_options_html; ?>
                                </select>
                            </div>
                            <div class="col-md-5 col-sm-6">
                                <label><b>UYGULA</b></label><br>
                                <button type="submit" name="topluislem" class="btn btn-teal margin-r-5" data-toggle="modal" data-target=".bs-example-modal-lg">Toplu İşlemler</button>
                                <?php if ($parasut_configured) { ?>
                                <button type="button" class="btn btn-parasut-siparis btn-parasut margin-r-5" title="Seçili siparişleri Paraşüt satış faturası olarak gönder"><i class="fa fa-paper-plane"></i> Paraşüt'e gönder</button>
                                <?php } else { ?>
                                <button type="button" class="btn btn-default margin-r-5" disabled title="Genel Ayarlar → Paraşüt sekmesinden yapılandırın"><i class="fa fa-paper-plane"></i> Paraşüt (kapalı)</button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-block" style="overflow-x: visible; -webkit-overflow-scrolling: touch;">
                        <div class="siparis-scroll-bundle">
                            <div class="siparis-scroll-top" id="siparisScrollTop" title="Tabloyu yatay kaydırmak için sürükleyin">
                                <div class="siparis-scroll-top-inner" id="siparisScrollTopInner"></div>
                            </div>
                            <div class="siparis-scroll-main" id="siparisScrollMain">
                        <table id="datatable_custom_siparis" class="table table-striped mobile-table" style="table-layout: auto;">
                            <thead>
                                <tr>
                                    <th style="vertical-align: middle;">
                                        <div class="checkbox checkbox-primary margin-r-5">
                                            <input id="selectAll" type="checkbox">
                                            <label for="selectAll" style="margin-bottom: 15px;"> </label>
                                        </div>
                                    </th>
                                    <th style="vertical-align: middle; text-align: center;">
                                        <strong>Detay</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Sipariş Tarih</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>NO</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Sipariş Ip</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Sipariş Ad Soyad</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>İl</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>İlçe</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Sipariş Adres</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Kurumsal fatura</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Sipariş ürün</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Sipariş Tel</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Sipariş Ödeme</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Sipariş Fiyat (₺)</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Siparis Not</strong>
                                    </th>
                                    <th style="vertical-align: middle;">
                                        <strong>Doğrulama</strong>
                                    </th>
                                    <th class="text-center" style="vertical-align: middle;">
                                        <strong>İşlemler</strong>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                             <?php 
                                        // Tablo içeriği DataTables (siparis_data.php) tarafından yüklenecek
                             ?>
                            </tbody>
                        </table>
                            </div>
                        </div>
                        
                        <!-- Sipariş Detay Modal (Iframe) -->
                        <div class="modal fade" id="detayModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog siparis-detay-modal-dialog" role="document">
                                <div class="modal-content siparis-detay-modal-content">
                                    <div class="modal-header siparis-detay-modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Kapat">&times;</button>
                                        <h4 class="modal-title"><i class="fa fa-file-text-o"></i> Sipariş detayı</h4>
                                    </div>
                                    <div class="modal-body siparis-detay-modal-body">
                                        <iframe id="detayIframe" class="siparis-detay-iframe" title="Sipariş düzenleme" src=""></iframe>
                                    </div>
                                    <div class="modal-footer siparis-detay-modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Kapat</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <style>
                        .siparis-detay-modal-dialog {
                            width: 98%;
                            max-width: 1140px;
                            margin: 14px auto;
                        }
                        .siparis-detay-modal-content {
                            border: none;
                            border-radius: 14px;
                            overflow: hidden;
                            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
                            max-height: calc(100vh - 28px);
                            display: flex;
                            flex-direction: column;
                        }
                        .siparis-detay-modal-header {
                            padding: 14px 18px;
                            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                            color: #fff;
                            border-bottom: none;
                        }
                        .siparis-detay-modal-header .modal-title {
                            font-weight: 700;
                            font-size: 17px;
                            margin: 0;
                            color: #fff;
                        }
                        .siparis-detay-modal-header .close {
                            color: #fff;
                            opacity: 0.9;
                            text-shadow: none;
                            font-size: 28px;
                            line-height: 1;
                            margin-top: 2px;
                        }
                        .siparis-detay-modal-body {
                            padding: 0;
                            flex: 1;
                            min-height: 520px;
                            max-height: calc(100vh - 140px);
                            background: #e8edf3;
                        }
                        .siparis-detay-iframe {
                            width: 100%;
                            height: 100%;
                            min-height: 520px;
                            border: none;
                            display: block;
                            vertical-align: top;
                        }
                        .siparis-detay-modal-footer {
                            padding: 12px 18px;
                            background: #f8fafc;
                            border-top: 1px solid #e2e8f0;
                        }
                        .btn-parasut { background: #00b8a9; color: #fff; border-color: #009688; }
                        .btn-parasut:hover { background: #009688; color: #fff; }
                        @media (max-width: 768px) {
                            .siparis-detay-modal-dialog { width: 100%; margin: 0; }
                            .siparis-detay-modal-content { max-height: 100vh; border-radius: 0; }
                            .siparis-detay-modal-body { max-height: calc(100vh - 120px); min-height: 400px; }
                        }
                        </style>
                        
                        <script>
                        $(document).ready(function() {
                            // Detay butonuna tıklandığında
                            $(document).on('click', '.btn-detay-popup', function(e) {
                                e.preventDefault();
                                var siparisId = $(this).data('id');
                                
                                // Iframe'e URL'yi yükle
                                $('#detayIframe').attr('src', 'siparis-detay.php?siparis_id=' + siparisId + '&popup=1');
                                
                                // Modal'ı aç
                                $('#detayModal').modal('show');
                            });
                            
                            // Modal kapandığında iframe'i temizle
                            $('#detayModal').on('hidden.bs.modal', function () {
                                $('#detayIframe').attr('src', '');
                            });
                        });

                        // iframe: kayıt / silme sonucu → bildirim + modal kapat + tablo yenile
                        window.addEventListener('message', function (e) {
                            if (e.origin !== window.location.origin) return;
                            var data = e.data;
                            if (!data || !data.type) return;

                            function reloadSiparisTable() {
                                if (typeof window.reloadSiparisTablePreserve === 'function') {
                                    window.reloadSiparisTablePreserve();
                                } else {
                                    var $dt = $('#datatable_custom_siparis');
                                    if ($.fn.DataTable && $.fn.DataTable.isDataTable($dt)) {
                                        $dt.DataTable().ajax.reload(null, false);
                                    }
                                }
                            }

                            if (data.type === 'siparis-detay-saved') {
                                if (data.ok) {
                                    $('#detayModal').modal('hide');
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Kaydedildi',
                                            text: 'Sipariş bilgileri güncellendi.',
                                            timer: 2200,
                                            showConfirmButton: true,
                                            confirmButtonText: 'Tamam'
                                        });
                                    }
                                    reloadSiparisTable();
                                } else {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Kaydedilemedi',
                                            text: 'Kayıt sırasında bir sorun oluştu.'
                                        });
                                    }
                                }
                                return;
                            }

                            if (data.type === 'siparis-detay-deleted' && data.ok) {
                                $('#detayModal').modal('hide');
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Silindi',
                                        text: 'Sipariş kaldırıldı.',
                                        timer: 2200,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Tamam'
                                    });
                                }
                                reloadSiparisTable();
                            }

                            if (data.type === 'siparis-parasut-sent' && data.ok) {
                                reloadSiparisTable();
                            }
                        });
                        </script>
                    </div>

                    <div class="card-block">
                        <div class="row">
                            <div class="col-md-3">
                                <label><b>İŞLEM</b></label>
                                <select name="islem" class="form-control m-b toplu-islem-select-bottom">
                                    <?php echo $toplu_islem_options_html; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label><b>UYGULA</b></label><br>
                                <button type="submit" name="topluislem" class="btn btn-teal margin-r-5" data-toggle="modal" data-target=".bs-example-modal-lg">Toplu İşlemler</button>
                                <?php if ($parasut_configured) { ?>
                                <button type="button" class="btn btn-parasut-siparis btn-parasut margin-r-5" title="Seçili siparişleri Paraşüt satış faturası olarak gönder"><i class="fa fa-paper-plane"></i> Paraşüt'e gönder</button>
                                <?php } else { ?>
                                <button type="button" class="btn btn-default margin-r-5" disabled title="Genel Ayarlar → Paraşüt sekmesinden yapılandırın"><i class="fa fa-paper-plane"></i> Paraşüt (kapalı)</button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Sipariş İçe Aktar</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="controller/siparis_import.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>ZIP Dosyası Seç</label>
                            <input type="file" name="zip_file" class="form-control" accept=".zip" required>
                            <small class="form-text text-muted">Daha önce dışa aktarılmış .zip dosyasını seçin</small>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input id="delete_existing" type="checkbox" name="delete_existing" value="1">
                                <label for="delete_existing">
                                    Var olan siparişleri sil ve üzerine yaz
                                    <small class="text-danger">(DİKKAT: Bu işlem geri alınamaz!)</small>
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                İşaretlenmezse, siparişler mevcut ID'lerden devam ederek eklenir.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">İçe Aktar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php 
    // Mesajları göster
    if (isset($_GET['import_success'])) {
        echo '<script>Swal.fire("Başarılı!", "' . htmlspecialchars($_GET['import_success']) . '", "success");</script>';
    }
    if (isset($_GET['import_error'])) {
        echo '<script>Swal.fire("Hata!", "' . htmlspecialchars($_GET['import_error']) . '", "error");</script>';
    }
    ?>

    <script>
    $("#selectAll").click(function() {
        $("input[type=checkbox]").prop("checked", $(this).prop("checked"));
    });

    $("input[type=checkbox]").click(function(e) {
        e.stopPropagation();
        if (!$(this).prop("checked")) {
            $("#selectAll").prop("checked", false);
        }
    });

    $(document).on('change', '.toplu-islem-select-top, .toplu-islem-select-bottom', function () {
        var v = $(this).val();
        $('.toplu-islem-select-top, .toplu-islem-select-bottom').val(v);
    });

    function siparisParasutToplu($btns) {
        var ids = [];
        $('.sectum:checked').each(function () { ids.push($(this).val()); });
        if (!ids.length) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Önce tablodan en az bir sipariş seçin.' });
            } else {
                alert('Önce sipariş seçin.');
            }
            return;
        }
        var run = function (force) {
            $btns.prop('disabled', true);
            $.ajax({
                url: 'controller/parasut_send.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ ids: ids, force: !!force }),
                dataType: 'json'
            }).done(function (r) {
                $btns.prop('disabled', false);
                var okc = r && r.ok_count != null ? r.ok_count : 0;
                var failc = r && r.fail_count != null ? r.fail_count : 0;
                var lines = [];
                if (r && r.results) {
                    r.results.forEach(function (x) {
                        lines.push('#' + x.siparis_id + ': ' + (x.ok ? ('OK — ' + (x.parasut_invoice_id || '')) : (x.error || 'Hata')));
                    });
                }
                var msg = 'Başarılı: ' + okc + ', Hatalı: ' + failc + (lines.length ? '\n\n' + lines.join('\n') : '');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: okc ? 'success' : 'warning', title: 'Paraşüt', html: '<pre style="text-align:left;font-size:12px;white-space:pre-wrap;max-height:280px;overflow:auto;">' + $('<div/>').text(msg).html() + '</pre>' });
                } else {
                    alert(msg);
                }
                if (okc) {
                    if (typeof window.reloadSiparisTablePreserve === 'function') {
                        window.reloadSiparisTablePreserve();
                    } else {
                        var $dt = $('#datatable_custom_siparis');
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable($dt)) {
                            $dt.DataTable().ajax.reload(null, false);
                        }
                    }
                }
            }).fail(function () {
                $btns.prop('disabled', false);
                alert('Bağlantı hatası.');
            });
        };
        var ask = ids.length + ' sipariş Paraşüt’e satış faturası olarak gönderilsin mi?';
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Paraşüt', text: ask, icon: 'question', showCancelButton: true, confirmButtonText: 'Gönder', cancelButtonText: 'İptal' }).then(function (res) {
                if (res.value) run(false);
            });
        } else if (confirm(ask)) {
            run(false);
        }
    }

    $(document).on('click', '.btn-parasut-siparis', function () {
        siparisParasutToplu($('.btn-parasut-siparis'));
    });
    </script>

<script>
$(document).ready(function() {
    var isMobile = window.innerWidth <= 768;

    // CSS Düzeltmesi
    $('head').append('<style>.dt-buttons { position: relative; display: inline-flex; vertical-align: middle; margin-bottom: 0px; z-index: 100; margin-right: 8px; } .dt-buttons .dt-button { margin-right: 5px; background: #667eea; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-size: 13px; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.1); } .dt-buttons .dt-button:hover { background: #764ba2; color: #fff; text-decoration: none; transform: translateY(-1px); }</style>');

    var tableConfig = {
        dom: 'Blfrtip',
        processing: true,
        serverSide: true,
        ajax: {
            url: 'controller/siparis_data.php',
            type: 'POST',
            data: function(d) {
                d.drm = <?php echo $durum; ?>;
                d.bas_tarih = '<?php echo $bas_tarih; ?>';
                d.bit_tarih = '<?php echo $bit_tarih; ?>';
            }
        },
        deferRender: true,
        scrollY: false,
        scrollX: false,
        scrollCollapse: false,
        paging: true,
        pageLength: isMobile ? 10 : 50,
        lengthMenu: isMobile
            ? [[5, 10, 25, 50], [5, 10, 25, 50]]
            : [[25, 50, 100, 200, 500, 1000, -1], [25, 50, 100, 200, 500, 1000, 'Tümü']],
        order: [[2, "desc"]], // Sipariş Tarih sütunu
        autoWidth: false, // Server-side olunca autoWidth'i kapatalım
        responsive: false,
        rowCallback: function(row, data, index) {
            // Veri içindeki DT_RowClass veya is_duplicate kontrolü (Sunucu tarafı is_duplicate gönderebilir)
            if ($(row).hasClass('duplicate-order') || $(row).attr('data-duplicate') == "1") {
                $(row).addClass('duplicate-order');
                $('td', row).css({
                    'background-color': '#f00',
                    'color': '#fff'
                });
            }
        },
        drawCallback: function () {
            if (typeof window.siparisHScrollUpdate === 'function') {
                window.siparisHScrollUpdate();
            }
        },
        language: {
            "sDecimal":        ",",
            "sEmptyTable":     "Tabloda herhangi bir veri mevcut değil",
            "sInfo":           "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
            "sInfoEmpty":      "Kayıt yok",
            "sInfoFiltered":   "(_MAX_ kayıt içerisinden bulunan)",
            "sInfoPostFix":    "",
            "sInfoThousands":  ".",
            "sLengthMenu":     "Sayfada _MENU_",
            "sLoadingRecords": "Yükleniyor...",
            "sProcessing":     "İşleniyor...",
            "sSearch":         "Ara:",
            "sZeroRecords":    "Eşleşen kayıt bulunamadı",
            "oPaginate": {
                "sFirst":    "İlk",
                "sLast":     "Son",
                "sNext":     "Sonraki",
                "sPrevious": "Önceki"
            }
        },
        columnDefs: [
            { orderable: false, targets: [0, 1, 16] }, // Checkbox, Detay, İşlemler 
            { targets: [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], className: 'mobile-hide' } // Bazılarını mobilde sakla (opsiyonel)
        ],
        buttons: [
            {
                extend: 'copyHtml5',
                text: '<i class="fa fa-copy"></i> Kopyala',
                titleAttr: 'Yalnızca bu sayfadaki satırlar (ekrandaki sayfa boyutu)',
                exportOptions: {
                    columns: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
                    modifier: { page: 'current' }
                }
            },
            {
                text: '<i class="fa fa-file-excel-o"></i> Excel',
                className: 'btn-excel',
                titleAttr: 'Bu listedeki durum + üstteki tarih filtresi + tablo aramasına uyan TÜM siparişler (sayfa limiti yok, veritabanından tamamı)',
                action: function ( e, dt, node, config ) {
                    var drm = <?php echo $durum; ?>;
                    var bas_tarih = '<?php echo $bas_tarih; ?>';
                    var bit_tarih = '<?php echo $bit_tarih; ?>';
                    var search = dt.search();
                    window.location.href = 'controller/full_export.php?type=excel&drm=' + drm + '&bas_tarih=' + bas_tarih + '&bit_tarih=' + bit_tarih + '&search=' + encodeURIComponent(search);
                }
            },
            {
                text: '<i class="fa fa-file-text-o"></i> CSV',
                className: 'btn-csv',
                titleAttr: 'Excel ile aynı: durum + tarih + aramadaki tüm satırlar',
                action: function ( e, dt, node, config ) {
                    var drm = <?php echo $durum; ?>;
                    var bas_tarih = '<?php echo $bas_tarih; ?>';
                    var bit_tarih = '<?php echo $bit_tarih; ?>';
                    var search = dt.search();
                    window.location.href = 'controller/full_export.php?type=csv&drm=' + drm + '&bas_tarih=' + bas_tarih + '&bit_tarih=' + bit_tarih + '&search=' + encodeURIComponent(search);
                }
            }
        ]
    };

    var table = $('#datatable_custom_siparis').DataTable(tableConfig);

    window.reloadSiparisTablePreserve = function () {
        var $dt = $('#datatable_custom_siparis');
        if (!$.fn.DataTable || !$.fn.DataTable.isDataTable($dt)) {
            return;
        }
        var dt = $dt.DataTable();
        var $main = $('#siparisScrollMain');
        var $top = $('#siparisScrollTop');
        var sl = $main.length ? $main.scrollLeft() : 0;
        var st = $top.length ? $top.scrollLeft() : 0;
        var vy = window.scrollY || window.pageYOffset || 0;
        dt.ajax.reload(function () {
            requestAnimationFrame(function () {
                if ($main.length) {
                    $main.scrollLeft(sl);
                }
                if ($top.length) {
                    $top.scrollLeft(st);
                }
                window.scrollTo(0, vy);
            });
        }, false);
    };

    (function siparisDrmScrollKeep() {
        var k = 'siparisler_scroll_y';
        var hk = 'siparisler_scroll_hx';
        var rk = 'siparisler_scroll_do_restore';
        if (sessionStorage.getItem(rk) === '1') {
            sessionStorage.removeItem(rk);
            var vy = parseInt(sessionStorage.getItem(k), 10);
            var hx = parseInt(sessionStorage.getItem(hk), 10);
            if (!isNaN(vy)) {
                window.scrollTo(0, vy);
            }
            if (!isNaN(hx)) {
                $('#siparisScrollMain').scrollLeft(hx);
                $('#siparisScrollTop').scrollLeft(hx);
            }
        }
        $(document).on('click', 'a.siparis-drm-tab', function () {
            sessionStorage.setItem(rk, '1');
            sessionStorage.setItem(k, String(window.scrollY || window.pageYOffset || 0));
            var $m = $('#siparisScrollMain');
            sessionStorage.setItem(hk, $m.length ? String($m.scrollLeft()) : '0');
        });
    })();

    // Butonları Header'a Taşı
    var exportContainer = $('#header_export_buttons');
    if (exportContainer.length) {
        table.buttons().container().appendTo(exportContainer);
    }

    // Tablo üstü / alt yatay kaydırma (tablo genişliği = içerik; başlık ve gövde aynı kapta)
    (function siparisTableDualHScroll() {
        var $top = $('#siparisScrollTop');
        var $inner = $('#siparisScrollTopInner');
        var $main = $('#siparisScrollMain');
        if (!$top.length || !$main.length || !$inner.length) return;

        var syncing = false;

        function contentScrollWidth() {
            var mainEl = $main[0];
            var tbl = document.getElementById('datatable_custom_siparis');
            if (!mainEl) return 0;
            var wMain = mainEl.scrollWidth;
            var wTbl = 0;
            if (tbl) {
                wTbl = Math.max(tbl.offsetWidth, tbl.scrollWidth, tbl.getBoundingClientRect().width);
            }
            var wrap = document.getElementById('datatable_custom_siparis_wrapper');
            if (wrap) {
                wTbl = Math.max(wTbl, wrap.scrollWidth);
            }
            return Math.max(wMain, wTbl, mainEl.clientWidth);
        }

        function updateTopSpacer() {
            var mainEl = $main[0];
            if (!mainEl) return;
            var cw = mainEl.clientWidth;
            var sw = contentScrollWidth();
            $inner.width(Math.max(sw, cw));
            if (sw <= cw + 3) {
                $top.css({ visibility: 'hidden', height: 0, minHeight: 0, marginBottom: 0, overflow: 'hidden', padding: 0 });
            } else {
                $top.css({
                    visibility: 'visible',
                    height: '',
                    minHeight: '22px',
                    marginBottom: '8px',
                    overflowX: 'auto',
                    overflowY: 'hidden',
                    padding: '2px 0'
                });
            }
        }

        window.siparisHScrollUpdate = function () {
            requestAnimationFrame(function () {
                try {
                    table.columns.adjust(false);
                } catch (e) {}
                updateTopSpacer();
                $top.scrollLeft($main.scrollLeft());
            });
        };

        $top.on('scroll', function () {
            if (syncing) return;
            syncing = true;
            $main.scrollLeft(this.scrollLeft);
            syncing = false;
        });
        $main.on('scroll', function () {
            if (syncing) return;
            syncing = true;
            $top.scrollLeft(this.scrollLeft);
            syncing = false;
        });

        table.on('draw.dt order.dt page.dt length.dt', function () {
            window.siparisHScrollUpdate();
        });

        $(window).on('resize.siparisHScroll', function () {
            window.siparisHScrollUpdate();
        });

        setTimeout(window.siparisHScrollUpdate, 100);
        setTimeout(window.siparisHScrollUpdate, 500);
        setTimeout(window.siparisHScrollUpdate, 1200);
    })();
});
</script>
<?php include 'footer.php'; ?>
