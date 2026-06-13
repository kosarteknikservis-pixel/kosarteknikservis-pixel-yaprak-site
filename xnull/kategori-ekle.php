<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Kategori Ekle</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-block">
                    <form method="POST" action="controller/function.php" class="form-horizontal">
                        <div class="form-group">
                            <label>Kategori Adı</label>
                            <input type="text" name="kategori_ad" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Sıra</label>
                            <input type="number" name="kategori_siraid" value="0" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Title</label>
                            <input type="text" name="kategori_title" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Description</label>
                            <input type="text" name="kategori_descr" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Keywords</label>
                            <input type="text" name="kategori_keyword" class="form-control">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="kategoriekle" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
