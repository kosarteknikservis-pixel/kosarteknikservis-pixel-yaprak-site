<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$sayfasor=$db->prepare("SELECT * from sayfalar order by sayfa_sira ASC, id DESC");
$sayfasor->execute();

if ( @$_GET[ 'sayfasil' ] == "ok" ) {
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: index.php?status=no" ); exit(); exit; }
    
    // Önce resmi sil
    $resimsor = $db->prepare("SELECT sayfa_resim FROM sayfalar WHERE id=:id");
    $resimsor->execute(['id' => $_GET['id']]);
    $resimcek = $resimsor->fetch(PDO::FETCH_ASSOC);
    if (!empty($resimcek['sayfa_resim']) && file_exists("../../" . $resimcek['sayfa_resim'])) {
        @unlink(SITE_ROOT . "/" . $resimcek['sayfa_resim']);
    }

	$sil = $db->prepare( "DELETE from sayfalar where id=:id" );
	$kontrol = $sil->execute(array('id' => $_GET['id']));
	if ($kontrol) { Header( "Location:sayfalar.php?status=ok" ); exit; } else { Header( "Location:sayfalar.php?status=no" ); exit; }
}
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Sayfa Yönetimi</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right">
                        <a href="sayfa-ekle.php" class="btn btn-primary"><i class="fa fa-plus"></i> Yeni Sayfa Ekle</a>
                    </div>
                    Tüm Sayfalar
                </div>
                <div class="card-block">
                    <table id="datatable1" class="table table-striped dt-responsive nowrap table-hover">
                        <thead>
                            <tr>
                                <th>Sıra</th>
                                <th>Başlık</th>
                                <th>Üst Sayfa</th>
                                <th>SEO URL (Slug)</th>
                                <th>Menü</th>
                                <th>Durum</th>
                                <th class="text-center" style="width: 150px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($sayfacek=$sayfasor->fetch(PDO::FETCH_ASSOC)) { 
                                // Üst sayfa adını bul
                                $ust_sayfa_adi = "Yok";
                                if ($sayfacek['sayfa_id'] > 0) {
                                    $ustsor = $db->prepare("SELECT sayfa_baslik FROM sayfalar WHERE id=:id");
                                    $ustsor->execute(['id' => $sayfacek['sayfa_id']]);
                                    $ustcek = $ustsor->fetch(PDO::FETCH_ASSOC);
                                    $ust_sayfa_adi = $ustcek['sayfa_baslik'] ?? "Silinmiş";
                                }
                            ?>
                                <tr>
                                    <td><?php echo $sayfacek['sayfa_sira']; ?></td>
                                    <td><?php echo $sayfacek['sayfa_baslik']; ?></td>
                                    <td><span class="badge badge-info"><?php echo $ust_sayfa_adi; ?></span></td>
                                    <td><code>/<?php echo $sayfacek['sayfa_slug']; ?></code></td>
                                    <td><?php echo $sayfacek['sayfa_menu']==1 ? '<span class="text-success">Açık</span>' : '<span class="text-danger">Kapalı</span>'; ?></td>
                                    <td><?php echo $sayfacek['sayfa_durum']==1 ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Pasif</span>'; ?></td>
                                    <td class="text-center">
                                        <a href="../sayfa/<?php echo $sayfacek['sayfa_slug']; ?>" target="_blank" class="btn btn-sm btn-info" title="Görüntüle"><i class="fa fa-eye"></i></a>
                                        <a href="sayfa-duzenle.php?id=<?php echo $sayfacek['id']; ?>" class="btn btn-sm btn-success" title="Düzenle"><i class="fa fa-edit"></i></a>
                                        <a href="?sayfasil=ok&id=<?php echo $sayfacek['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu sayfayı silmek istediğinize emin misiniz?')" title="Sil"><i class="fa fa-trash"></i></a>
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
