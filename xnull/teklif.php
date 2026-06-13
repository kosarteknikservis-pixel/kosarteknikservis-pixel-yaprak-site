<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$teklifsor=$db->prepare("SELECT * from teklif order by id DESC");
$teklifsor->execute();

if ( $_GET[ 'teklifsil' ] == "ok" ) {
	if (!$_SESSION[ 'kullanici_adi' ]) { header("Location: index.php?status=no" ); exit(); exit; }
	$sil = $db->prepare( "DELETE from teklif where id=:id" );
	$kontrol = $sil->execute(array('id' => $_GET['id']));
	if ($kontrol) { Header( "Location:?status=ok" ); exit; } else { Header( "Location:?status=no" ); exit; }
}
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Teklif / Talep Yönetimi</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    Gelen Teklifler
                </div>
                <div class="card-block">
                    <table id="datatable1" class="table table-striped dt-responsive nowrap table-hover">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>Telefon</th>
                                <th>Nereden</th>
                                <th>Nereye</th>
                                <th>Cinsi</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($teklifcek=$teklifsor->fetch(PDO::FETCH_ASSOC)) { ?>
                                <tr>
                                    <td><?php echo $teklifcek['teklif_adsoyad']; ?></td>
                                    <td><?php echo $teklifcek['teklif_tel']; ?></td>
                                    <td><?php echo $teklifcek['teklif_nereden']; ?></td>
                                    <td><?php echo $teklifcek['teklif_nereye']; ?></td>
                                    <td><?php echo $teklifcek['teklif_cinsi']; ?></td>
                                    <td class="text-center">
                                        <a href="?teklifsil=ok&id=<?php echo $teklifcek['id']; ?>" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>
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
