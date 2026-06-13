<?php 
$arsiv_dir = 'siparis-arsivi';

// Dosya indirme - HİÇBİR ÇIKTI ÜRETMEDEN ÖNCE ÇALIŞMALI
if (isset($_GET['indir']) && $_GET['indir'] == 'ok') {
    $file = isset($_GET['file']) ? basename($_GET['file']) : '';
    $full_path = $arsiv_dir . '/' . $file;
    if ($file && file_exists($full_path)) {
        // Tamponu temizle
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($full_path));
        readfile($full_path);
        exit();
    }
}

// Dosya silme - Yönlendirme yapacağı için önce çalışmalı
if (isset($_GET['sil']) && $_GET['sil'] == 'ok') {
    $file = isset($_GET['file']) ? basename($_GET['file']) : '';
    if ($file && file_exists($arsiv_dir . '/' . $file)) {
        unlink($arsiv_dir . '/' . $file);
        header("Location:siparis-arsivi.php?status=ok");
        exit();
    }
}

include 'header.php';
include 'topbar.php';
include 'sidebar.php';

if (!file_exists($arsiv_dir)) {
    mkdir($arsiv_dir, 0777, true);
}

// Dosya ismi değiştirme
if (isset($_POST['rename_file']) && isset($_POST['new_name'])) {
    $old_file = isset($_POST['old_file']) ? basename($_POST['old_file']) : '';
    $new_name = trim($_POST['new_name']);
    if ($old_file && $new_name && file_exists($arsiv_dir . '/' . $old_file)) {
        $ext = pathinfo($old_file, PATHINFO_EXTENSION);
        $new_file = preg_replace('/[^a-zA-Z0-9_-]/', '_', $new_name) . '.' . $ext;
        rename($arsiv_dir . '/' . $old_file, $arsiv_dir . '/' . $new_file);
        header("Location:siparis-arsivi.php?status=ok");
        exit();
    }
}

// Arşiv dosyalarını listele (sayfalama için)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50; // Sayfa başına 50 dosya
$offset = ($page - 1) * $per_page;

$all_files = glob($arsiv_dir . '/*.zip');
if ($all_files) {
    usort($all_files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
} else {
    $all_files = [];
}

$total_files = count($all_files);
$total_pages = ceil($total_files / $per_page);
$arsiv_files = array_slice($all_files, $offset, $per_page);
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Sipariş Arşivi</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10">
                        <a href="controller/db_backup.php" class="btn btn-primary btn-icon" onclick="return confirm('Tüm veritabanı yedeği alınacak. Bu işlem veritabanı boyutuna göre zaman alabilir. Devam edilsin mi?')"><i class="fa fa-database"></i> Veritabanı Yedeği Al</a>
                        <a href="siparisler.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i> Geri Dön</a>
                    </div>
                    Sipariş Arşivi
                    <small>Dışa aktarılan ve içe aktarılan sipariş yedekleri</small>
                </div>
                <div class="card-block">
                    <?php if (!is_writable($arsiv_dir)) { ?>
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i> <strong>Hata:</strong> Arşiv dizini (<code><?php echo $arsiv_dir; ?></code>) yazılabilir değil. Yedekleme ve silme işlemleri başarısız olabilir. Lütfen klasör izinlerini kontrol edin.
                        </div>
                    <?php } ?>
                    
                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'db_backup_success') { ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check"></i> Veritabanı yedeği başarıyla oluşturuldu ve arşive eklendi.
                        </div>
                    <?php } ?>
                    <?php if (empty($arsiv_files)) { ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> Henüz arşiv dosyası bulunmamaktadır.
                        </div>
                    <?php } else { ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Dosya Adı</th>
                                    <th>Boyut</th>
                                    <th>Oluşturulma Tarihi</th>
                                    <th>Tip</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($arsiv_files as $file) { 
                                    $file_name = basename($file);
                                    $file_size = filesize($file);
                                    $file_date = date('d.m.Y H:i:s', filemtime($file));
                                    $file_size_formatted = $file_size > 1024 * 1024 
                                        ? number_format($file_size / (1024 * 1024), 2) . ' MB'
                                        : number_format($file_size / 1024, 2) . ' KB';
                                    
                                    if (strpos($file_name, 'db_backup_') === 0) {
                                        $file_type = 'Veritabanı Yedeği';
                                        $label_class = 'badge-primary';
                                    } elseif (strpos($file_name, 'export_') === 0) {
                                        $file_type = 'Sipariş Dışa Aktarma';
                                        $label_class = 'badge-success';
                                    } else {
                                        $file_type = 'İçe Aktarma';
                                        $label_class = 'badge-info';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <i class="fa fa-file-archive-o"></i> 
                                        <span id="filename-<?php echo md5($file_name); ?>"><?php echo htmlspecialchars($file_name); ?></span>
                                    </td>
                                    <td><?php echo $file_size_formatted; ?></td>
                                    <td><?php echo $file_date; ?></td>
                                    <td>
                                        <span class="badge <?php echo $label_class; ?>">
                                            <?php echo $file_type; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?indir=ok&file=<?php echo urlencode($file_name); ?>" class="btn btn-xs btn-success" title="İndir">
                                            <i class="fa fa-download"></i> İndir
                                        </a>
                                        <button type="button" class="btn btn-xs btn-warning" 
                                                onclick="renameFile('<?php echo htmlspecialchars($file_name, ENT_QUOTES); ?>', '<?php echo md5($file_name); ?>')" title="Yeniden Adlandır">
                                            <i class="fa fa-edit"></i> İsim
                                        </button>
                                        <a href="?sil=ok&file=<?php echo urlencode($file_name); ?>" class="btn btn-xs btn-danger" 
                                           onclick="return confirm('Bu dosyayı silmek istediğinize emin misiniz?')" title="Sil">
                                            <i class="fa fa-trash"></i> Sil
                                        </a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        
                        <?php if ($total_pages > 1) { ?>
                        <div class="text-center">
                            <ul class="pagination">
                                <?php if ($page > 1) { ?>
                                    <li><a href="?page=<?php echo $page - 1; ?>">&laquo; Önceki</a></li>
                                <?php } ?>
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) { ?>
                                    <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php } ?>
                                <?php if ($page < $total_pages) { ?>
                                    <li><a href="?page=<?php echo $page + 1; ?>">Sonraki &raquo;</a></li>
                                <?php } ?>
                            </ul>
                            <p class="text-muted">Toplam <?php echo $total_files; ?> dosya, Sayfa <?php echo $page; ?> / <?php echo $total_pages; ?></p>
                        </div>
                        <?php } ?>
                        
                        <!-- Rename Modal -->
                        <div class="modal fade" id="renameModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Dosya Adını Değiştir</h4>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <form method="POST" action="">
                                        <div class="modal-body">
                                            <input type="hidden" name="old_file" id="rename_old_file">
                                            <div class="form-group">
                                                <label>Yeni Dosya Adı</label>
                                                <input type="text" name="new_name" id="rename_new_name" class="form-control" required>
                                                <small class="form-text text-muted">Uzantı (.zip) otomatik eklenecektir</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                                            <button type="submit" name="rename_file" class="btn btn-primary">Kaydet</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <script>
                        function renameFile(fileName, fileId) {
                            var nameWithoutExt = fileName.replace(/\.zip$/, '');
                            $('#rename_old_file').val(fileName);
                            $('#rename_new_name').val(nameWithoutExt);
                            $('#renameModal').modal('show');
                        }
                        </script>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>

