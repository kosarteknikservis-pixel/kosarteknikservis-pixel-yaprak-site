<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Blog Yazısı Ekle</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-block">
                    <form method="POST" action="controller/function.php" enctype="multipart/form-data" class="form-horizontal">
                        <div class="form-group">
                            <label>Blog Resmi</label>
                            <input type="file" name="blog_resim" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Blog Başlığı</label>
                            <input type="text" name="blog_baslik" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Blog Detayı</label>
                            <textarea name="blog_detay" class="summernote"></textarea>
                        </div>
                        <div class="form-group">
                            <label>SEO Title</label>
                            <input type="text" name="blog_title" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Description</label>
                            <input type="text" name="blog_descr" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Keywords</label>
                            <input type="text" name="blog_keyword" class="form-control">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="blogekle" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
