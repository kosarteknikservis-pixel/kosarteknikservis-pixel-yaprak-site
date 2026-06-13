<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Çarkıfelek Logları</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10" style="margin-bottom: 10px;">
                        <button type="button" class="btn btn-danger btn-icon" onclick="if(confirm('Tüm çarkıfelek loglarını sıfırlamak istediğinizden emin misiniz? Bu işlem geri alınamaz!')) { window.location.href='controller/function.php?carkifelek_log_sifirla=1'; }"><i class="fa fa-trash"></i> Logları Sıfırla</button>
                    </div>
                    Çarkıfelek Oyunu Logları
                </div>
                <div class="card-block">
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'sifirlandi') { ?>
                    <div class="alert alert-success">
                        <strong>Başarılı!</strong> Çarkıfelek logları başarıyla sıfırlandı.
                    </div>
                    <?php } elseif (isset($_GET['status']) && $_GET['status'] == 'no') { ?>
                    <div class="alert alert-danger">
                        <strong>Hata!</strong> Loglar sıfırlanırken bir hata oluştu.
                    </div>
                    <?php } ?>
                    <table id="datatable1" class="table table-striped dt-responsive nowrap table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>IP Adresi</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $logsor = $db->prepare("SELECT * FROM carkifelek_log ORDER BY id DESC");
                            $logsor->execute();
                            while ($logcek = $logsor->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <tr>
                                    <td><?php echo $logcek['id']; ?></td>
                                    <td><?php echo htmlspecialchars($logcek['ip']); ?></td>
                                    <td><?php echo date('d.m.Y H:i:s', $logcek['tarih']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
