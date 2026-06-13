<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

// Not İşlemleri
if (isset($_POST['not_ekle'])) {
    $icerik = htmlspecialchars(trim($_POST['not_icerik']));
    if (!empty($icerik)) {
        $ekle = $db->prepare("INSERT INTO notlar SET icerik=:icerik");
        $ekle->execute(['icerik' => $icerik]);
        Header("Location:notlar.php?status=ok");
        exit;
    } else {
        Header("Location:notlar.php?status=no"); exit;
    }
    exit();
}

if (isset($_GET['not_sil'])) {
    $sil = $db->prepare("DELETE FROM notlar WHERE id=:id");
    $sil->execute(['id' => $_GET['not_sil']]);
    Header("Location:notlar.php?status=ok");
    exit();
}

if (isset($_GET['not_tamam'])) {
    $guncelle = $db->prepare("UPDATE notlar SET durum=1 WHERE id=:id");
    $guncelle->execute(['id' => $_GET['not_tamam']]);
    Header("Location:notlar.php?status=ok");
    exit();
}

if (isset($_GET['not_aktif'])) {
    $guncelle = $db->prepare("UPDATE notlar SET durum=0 WHERE id=:id");
    $guncelle->execute(['id' => $_GET['not_aktif']]);
    Header("Location:notlar.php?status=ok");
    exit();
}
?>

<section class="main-content container">
    <div class="page-header">
        <h2>Hızlı Notlar & Görevler</h2>
        <p class="text-muted">Ekip içi notlar alabilir ve görev takibi yapabilirsiniz.</p>
    </div>

    <div class="row">
        <!-- Yeni Not Ekleme -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-heading card-default">
                    Yeni Not / Görev Ekle
                </div>
                <div class="card-block">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Not İçeriği</label>
                            <textarea name="not_icerik" class="form-control" rows="5" placeholder="Hatırlamanız gereken bir şey yazın..." required></textarea>
                        </div>
                        <button type="submit" name="not_ekle" class="btn btn-primary btn-block"><i class="fa fa-plus"></i> Listeye Ekle</button>
                    </form>
                </div>
            </div>

            <div class="card" style="margin-top: 20px;">
                <div class="card-heading card-default">
                    İstatistikler
                </div>
                <div class="card-block">
                    <?php 
                    $toplam_not = $db->query("SELECT count(*) FROM notlar")->fetchColumn();
                    $aktif_not = $db->query("SELECT count(*) FROM notlar WHERE durum=0")->fetchColumn();
                    $tamamlanan_not = $db->query("SELECT count(*) FROM notlar WHERE durum=1")->fetchColumn();
                    ?>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Toplam Kayıt
                            <span class="badge badge-primary badge-pill"><?php echo $toplam_not; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Aktif Görevler
                            <span class="badge badge-warning badge-pill"><?php echo $aktif_not; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tamamlananlar
                            <span class="badge badge-success badge-pill"><?php echo $tamamlanan_not; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Not Listesi -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right">
                        <small class="text-muted">Son eklenenler üstte görünür</small>
                    </div>
                    Tüm Notlar & Görevler
                </div>
                <div class="card-block">
                    <div class="row" id="notes-container">
                        <?php 
                        $notlar = $db->query("SELECT * FROM notlar ORDER BY durum ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
                        foreach($notlar as $not) { 
                            $bg = $not['durum'] == 1 ? '#f8f9fa' : '#fff';
                            $border = $not['durum'] == 1 ? '1px solid #ddd' : '1px solid #3498db';
                            $opacity = $not['durum'] == 1 ? '0.7' : '1';
                        ?>
                        <div class="col-md-12" style="margin-bottom: 15px;">
                            <div style="background: <?php echo $bg; ?>; border: <?php echo $border; ?>; border-radius: 8px; padding: 15px; position: relative; opacity: <?php echo $opacity; ?>; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: all 0.3s ease;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div style="flex: 1;">
                                        <div style="<?php echo $not['durum'] == 1 ? 'text-decoration: line-through; color: #777;' : 'color: #333; font-weight: 500;'; ?> font-size: 15px; line-height: 1.6;">
                                            <?php echo nl2br($not['icerik']); ?>
                                        </div>
                                        <div style="margin-top: 10px;">
                                            <span class="text-muted" style="font-size: 11px;"><i class="fa fa-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($not['tarih'])); ?></span>
                                            <?php if($not['durum'] == 1) { ?>
                                                <span class="badge badge-success" style="font-size: 10px; margin-left: 10px;">TAMAMLANDI</span>
                                            <?php } else { ?>
                                                <span class="badge badge-warning" style="font-size: 10px; margin-left: 10px;">BEKLEMEDE</span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div style="margin-left: 15px; display: flex; gap: 5px;">
                                        <?php if($not['durum'] == 0) { ?>
                                            <a href="?not_tamam=<?php echo $not['id']; ?>" class="btn btn-sm btn-success" title="Tamamlandı Olarak İşaretle"><i class="fa fa-check"></i></a>
                                        <?php } else { ?>
                                            <a href="?not_aktif=<?php echo $not['id']; ?>" class="btn btn-sm btn-warning" title="Tekrar Aktifleştir"><i class="fa fa-refresh"></i></a>
                                        <?php } ?>
                                        <a href="?not_sil=<?php echo $not['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu notu silmek istediğinize emin misiniz?')" title="Sil"><i class="fa fa-trash"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } 
                        if(empty($notlar)) {
                            echo '<div class="col-md-12 text-center text-muted" style="padding: 40px;">Henüz bir not eklenmemiş.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>
