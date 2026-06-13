<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Soru Ekle</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-block">
                    <form method="POST" action="controller/function.php" class="form-horizontal">
                        <div class="form-group">
                            <label>Soru / Başlık</label>
                            <input type="text" name="sss_soru" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Cevap / Detay</label>
                            <textarea name="sss_cevap" class="summernote"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Sıra</label>
                            <input type="number" name="sss_sira" value="0" class="form-control">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="sssekle" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
