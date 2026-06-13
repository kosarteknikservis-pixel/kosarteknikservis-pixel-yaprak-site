<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$katsor=$db->prepare("SELECT * from kategoriler where id=:id");
$katsor->execute(array('id' => $_GET['id']));
$katcek=$katsor->fetch(PDO::FETCH_ASSOC);

if (!$katcek) {
    header("Location: kategoriler.php?status=notfound");
    exit();
}
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Kategori Düzenle</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-block">
                    <form method="POST" action="controller/function.php" class="form-horizontal">
                        <input type="hidden" name="id" value="<?php echo $katcek['id']; ?>">
                        <div class="form-group">
                            <label>Kategori Adı</label>
                            <input type="text" name="kategori_ad" value="<?php echo $katcek['kategori_ad']; ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Sıra</label>
                            <input type="number" name="kategori_siraid" value="<?php echo $katcek['kategori_siraid']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Title</label>
                            <input type="text" name="kategori_title" value="<?php echo $katcek['kategori_title']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Description</label>
                            <input type="text" name="kategori_descr" value="<?php echo $katcek['kategori_descr']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Keywords</label>
                            <input type="text" name="kategori_keyword" value="<?php echo $katcek['kategori_keyword']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="kategoriduzenle" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
