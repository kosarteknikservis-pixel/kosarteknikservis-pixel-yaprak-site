<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$blogsor=$db->prepare("SELECT * from blog where blog_id=:id");
$blogsor->execute(array('id' => $_GET['id']));
$blogcek=$blogsor->fetch(PDO::FETCH_ASSOC);

if (!$blogcek) {
    header("Location: blog.php?status=notfound");
    exit();
}
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Blog Yazısı Düzenle</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-block">
                    <form method="POST" action="controller/function.php" enctype="multipart/form-data" class="form-horizontal">
                        <input type="hidden" name="id" value="<?php echo $blogcek['blog_id']; ?>">
                        <input type="hidden" name="eski_resim" value="<?php echo $blogcek['blog_resim']; ?>">
                        <div class="form-group">
                            <label>Yüklü Resim</label>
                            <p><img src="../upload/blog/<?php echo $blogcek['blog_resim']; ?>" style="max-height: 100px;"></p>
                        </div>
                        <div class="form-group">
                            <label>Yeni Blog Resmi</label>
                            <input type="file" name="blog_resim" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Blog Başlığı</label>
                            <input type="text" name="blog_baslik" value="<?php echo $blogcek['blog_baslik']; ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Blog Detayı</label>
                            <textarea name="blog_detay" class="summernote"><?php echo $blogcek['blog_detay']; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>SEO Title</label>
                            <input type="text" name="blog_title" value="<?php echo $blogcek['blog_title']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Description</label>
                            <input type="text" name="blog_descr" value="<?php echo $blogcek['blog_descr']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Keywords</label>
                            <input type="text" name="blog_keyword" value="<?php echo $blogcek['blog_keyword']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="blogduzenle" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
