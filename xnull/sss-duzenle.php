<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$ssssor=$db->prepare("SELECT * from sss where sss_idid=:id");
$ssssor->execute(array('id' => $_GET['id']));
$ssscek=$ssssor->fetch(PDO::FETCH_ASSOC);
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Soru Düzenle</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-block">
                    <form method="POST" action="controller/function.php" class="form-horizontal">
                        <input type="hidden" name="id" value="<?php echo $ssscek['sss_idid']; ?>">
                        <div class="form-group">
                            <label>Soru / Başlık</label>
                            <input type="text" name="sss_soru" value="<?php echo $ssscek['sss_soru']; ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Cevap / Detay</label>
                            <textarea name="sss_cevap" class="summernote"><?php echo $ssscek['sss_cevap']; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Sıra</label>
                            <input type="number" name="sss_sira" value="<?php echo $ssscek['sss_sira']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="sssduzenle" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
