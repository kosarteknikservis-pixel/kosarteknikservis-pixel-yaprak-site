<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$blogsor=$db->prepare("SELECT * from blog order by blog_id DESC");
$blogsor->execute();

if ( $_GET[ 'blogsil' ] == "ok" ) {
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: index.php?status=no" ); exit(); exit; }
    $SilID = $_GET['id'];
    $bul = $db->prepare("SELECT blog_resim FROM blog WHERE blog_id=:id");
    $bul->execute(array('id' => $SilID));
    $yaz = $bul->fetch(PDO::FETCH_ASSOC);
    if ($yaz['blog_resim']) { unlink("../upload/blog/".$yaz['blog_resim']); }
	$sil = $db->prepare( "DELETE from blog where blog_id=:id" );
	$kontrol = $sil->execute(array('id' => $SilID));
	if ($kontrol) { Header( "Location:?status=ok" ); exit; } else { Header( "Location:?status=no" ); exit; }
}
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Blog Yönetimi</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right">
                        <a href="sayfalar.php" class="btn btn-success" style="margin-right:8px;"><i class="fa fa-file-text-o"></i> Sayfa Yönetimi</a>
                        <a href="blog-ekle.php" class="btn btn-primary"><i class="fa fa-plus"></i> Blog Ekle</a>
                    </div>
                    Blog Yazıları (vitrin kaldırıldı — yeni içerik için <strong>Sayfa Yönetimi</strong> kullanın)
                </div>
                <div class="card-block">
                    <p class="text-muted" style="font-size:13px;margin-bottom:12px;">Site ön yüzünde blog alanı yoktur. Eski kayıtlar veritabanında durur; &quot;Görüntüle&quot; site ana sayfasını açar.</p>
                    <table id="datatable1" class="table table-striped dt-responsive nowrap table-hover">
                        <thead>
                            <tr>
                                <th>Resim</th>
                                <th>Başlık</th>
                                <th>Tarih</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($blogcek=$blogsor->fetch(PDO::FETCH_ASSOC)) { ?>
                                <tr>
                                    <td><img src="../upload/blog/<?php echo $blogcek['blog_resim']; ?>" style="max-height: 50px;"></td>
                                    <td><?php echo $blogcek['blog_baslik']; ?></td>
                                    <td><?php echo $blogcek['blog_id']; ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo htmlspecialchars(rtrim(SITE_URL, '/') . '/'); ?>" target="_blank" class="btn btn-sm btn-info" title="Site ana sayfası (blog vitrini yok)"><i class="fa fa-eye"></i></a>
                                        <a href="blog-duzenle.php?id=<?php echo $blogcek['blog_id']; ?>" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>
                                        <a href="?blogsil=ok&id=<?php echo $blogcek['blog_id']; ?>" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>
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
