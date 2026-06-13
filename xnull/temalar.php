<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

$themes_dir = "./"; // Point to xnull root
$all_zips = glob($themes_dir . '*.zip');
// Exclude potential system zips if needed, but for now we list all zips in xnull
$themes = $all_zips;

if (isset($_GET['sil_tema'])) {
    if (!$_SESSION['kullanici_adi']) { header("Location: index.php?status=no"); exit(); exit; }
    $tema_adi = basename($_GET['sil_tema']);
    $tema_yolu = $themes_dir . $tema_adi;
    
    if (file_exists($tema_yolu)) {
        unlink($tema_yolu);
    }
    Header("Location:temalar.php?status=ok");
    exit;
}

?>
<section class="main-content container">
    <div class="page-header">
        <h2>Tema / Şablon Yönetimi (.ZIP Modu)</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    TEMA KAYDET (ZIP Arşivi Olarak)
                </div>
                <div class="card-block">
                    <form method="POST" action="controller/theme_controller.php" class="form-horizontal">
                        <div class="form-group">
                            <label>Tema / Şablon Adı</label>
                            <input type="text" name="theme_name" class="form-control" placeholder="Örn: 2026_Yaz_Kampanyasi" required>
                            <small>Sitenin o anki tüm ürün resimleri, logo ve ayarları tek bir .zip dosyasında paketlenecektir.</small>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="theme_save" class="btn btn-primary">Şu Anki Ayarları .ZIP Olarak Yedekle</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-heading card-default">
                    TEMA İÇE AKTAR (.ZIP)
                </div>
                <div class="card-block">
                    <form method="POST" action="controller/theme_controller.php" enctype="multipart/form-data" class="form-horizontal">
                        <div class="form-group">
                            <label>Bilgisayarınızdan Tema ZIP Dosyası Seçin</label>
                            <input type="file" name="theme_zip" class="form-control" accept=".zip" required>
                            <small>Başka bir siteden veya bilgisayarınızdan aldığınız tema yedeğini buraya yükleyip kurabilirsiniz.</small>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="theme_import" class="btn btn-warning">Dosyayı Yükle ve Temayı Kur</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-heading card-default">
                    KAYITLI TEMALAR (.ZIP ARŞİVLERİ)
                </div>
                <div class="card-block">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Arşiv Adı</th>
                                <th>Dosya Yolu</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($themes as $theme) { 
                                $name = basename($theme);
                            ?>
                                <tr>
                                    <td><strong><i class="fa fa-file-archive-o"></i> <?php echo $name; ?></strong></td>
                                    <td><?php echo $theme; ?></td>
                                    <td class="text-center">
                                        <a href="controller/theme_controller.php?action=restore&name=<?php echo urlencode($name); ?>" class="btn btn-sm btn-success" onclick="return confirm('Bu temayı aktif etmek istiyor musunuz? Mevcut görsellerin üzerine yazılacaktır!')"><i class="fa fa-refresh"></i> Aktif Et / Geri Yükle</a>
                                        <a href="controller/theme_controller.php?action=download&name=<?php echo urlencode($name); ?>" class="btn btn-sm btn-info"><i class="fa fa-download"></i> İndir</a>
                                        <a href="?sil_tema=<?php echo urlencode($name); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu temayı tamamen silmek istiyor musunuz?')"><i class="fa fa-trash"></i> Sil</a>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php if (empty($themes)) { echo "<tr><td colspan='3' class='text-center'>Kayıtlı .zip yedeği bulunamadı.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
