<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$ssssor=$db->prepare("SELECT * from sss order by id DESC");
$ssssor->execute();

if ( $_GET[ 'ssssil' ] == "ok" ) {
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: index.php?status=no" ); exit(); exit; }
	$sil = $db->prepare( "DELETE from sss where id=:id" );
	$kontrol = $sil->execute(array('id' => $_GET['id']));
	if ($kontrol) { Header( "Location:sss.php?status=ok" ); exit; } else { Header( "Location:sss.php?status=no" ); exit; }
}
?>
<section class="main-content container">
    <div class="page-header">
        <h2>S.S.S. Yönetimi</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right">
                        <a href="sss-ekle.php" class="btn btn-primary"><i class="fa fa-plus"></i> Soru Ekle</a>
                    </div>
                    Sıkça Sorulan Sorular
                </div>
                <div class="card-block">
                    <table id="datatable1" class="table table-striped dt-responsive nowrap table-hover">
                        <thead>
                            <tr>
                                <th>Soru</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ssscek=$ssssor->fetch(PDO::FETCH_ASSOC)) { ?>
                                <tr>
                                    <td><?php echo $ssscek['sss_soru']; ?></td>
                                    <td class="text-center">
                                        <a href="sss-duzenle.php?id=<?php echo $ssscek['id']; ?>" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>
                                        <a href="?ssssil=ok&id=<?php echo $ssscek['id']; ?>" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>
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
