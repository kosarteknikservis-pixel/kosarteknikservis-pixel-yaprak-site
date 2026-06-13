<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Yönetici Giriş/Çıkış Logları</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10" style="margin-bottom: 15px;">
                        <button type="button" class="btn btn-danger btn-icon" onclick="if(confirm('Tüm yönetici loglarını sıfırlamak istediğinizden emin misiniz? Bu işlem geri alınamaz!')) { window.location.href='controller/function.php?admin_log_sifirla=1'; }"><i class="fa fa-trash"></i> Logları Sıfırla</button>
                    </div>
                    Yönetici Panel Hareketleri
                </div>
                <div class="card-block">
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'sifirlandi') { ?>
                    <div class="alert alert-success">
                        <strong>Başarılı!</strong> Yönetici logları başarıyla sıfırlandı.
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
                                <th>Kullanıcı</th>
                                <th>İşlem</th>
                                <th>IP Adresi</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $logsor = $db->prepare("SELECT * FROM admin_log ORDER BY id DESC");
                            $logsor->execute();
                            while ($logcek = $logsor->fetch(PDO::FETCH_ASSOC)) {
                                $islem_renk = ($logcek['islem'] == 'Giriş') ? 'success' : 'danger';
                                ?>
                                <tr>
                                    <td>#<?php echo $logcek['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($logcek['kullanici_ad']); ?></strong></td>
                                    <td><span class="label label-<?php echo $islem_renk; ?>"><?php echo htmlspecialchars($logcek['islem']); ?></span></td>
                                    <td><?php echo htmlspecialchars($logcek['ip_adresi']); ?></td>
                                    <td><?php echo date('d.m.Y H:i:s', strtotime($logcek['tarih'])); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
