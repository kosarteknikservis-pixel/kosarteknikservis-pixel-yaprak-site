<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$katsor=$db->prepare("SELECT * from kategoriler order by kategori_sira ASC");
$katsor->execute();

if ( $_GET[ 'katsil' ] == "ok" ) {
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: index.php?status=no" ); exit(); exit; }
	$sil = $db->prepare( "DELETE from kategoriler where id=:id" );
	$kontrol = $sil->execute(array('id' => $_GET['id']));
	if ($kontrol) { Header( "Location:?status=ok" ); exit; } else { Header( "Location:?status=no" ); exit; }
}
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Kategori Yönetimi</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right">
                        <a href="kategori-ekle.php" class="btn btn-primary"><i class="fa fa-plus"></i> Kategori Ekle</a>
                    </div>
                    Kategoriler
                </div>
                <div class="card-block">
                    <table id="datatable1" class="table table-striped dt-responsive nowrap table-hover mobile-table">
                        <thead>
                            <tr>
                                <th>Sıra</th>
                                <th>Başlık</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($katcek=$katsor->fetch(PDO::FETCH_ASSOC)) { ?>
                                <tr>
                                    <td><?php echo $katcek['kategori_sira']; ?></td>
                                    <td><?php echo $katcek['kategori_ad']; ?></td>
                                    <td class="text-center">
                                        <a href="kategori-duzenle.php?id=<?php echo $katcek['id']; ?>" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>
                                        <a href="?katsil=ok&id=<?php echo $katcek['id']; ?>" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>
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
