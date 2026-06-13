<?php 
include 'header.php'; 
include 'topbar.php'; 
include 'sidebar.php'; 

$basvuru_id = $_GET['id'];
$basvurusor = $db->prepare("SELECT * FROM form_basvurular WHERE basvuru_id=:id");
$basvurusor->execute(array('id' => $basvuru_id));
$basvurucek = $basvurusor->fetch(PDO::FETCH_ASSOC);

if (!$basvurucek) {
    header("Location: form-yonetimi.php");
    exit;
}

$formsor = $db->prepare("SELECT * FROM formlar WHERE form_id=:id");
$formsor->execute(array('id' => $basvurucek['form_id']));
$formcek = $formsor->fetch(PDO::FETCH_ASSOC);

// Durum Güncelleme
if(isset($_POST['durumguncelle'])) {
    $guncelle = $db->prepare("UPDATE form_basvurular SET basvuru_durum=:durum WHERE basvuru_id=:id");
    $guncelle->execute(array('durum' => $_POST['durum'], 'id' => $basvuru_id));
    $basvurucek['basvuru_durum'] = $_POST['durum'];
    echo '<div class="alert alert-success">Durum güncellendi.</div>';
}
?>

<section class="main-content container">
    <div class="page-header">
        <h2>Başvuru Detayı: #<?php echo $basvuru_id; ?></h2>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-heading card-default">
                    Başvuru Bilgileri
                </div>
                <div class="card-block">
                    <table class="table table-bordered">
                        <tbody>
                            <?php 
                            // Form alanlarını ve değerlerini eşleştirip getir
                            $degersor = $db->prepare("
                                SELECT fa.alan_baslik, fa.alan_tip, fd.deger 
                                FROM form_degerler fd 
                                JOIN form_alanlari fa ON fd.alan_id = fa.alan_id 
                                WHERE fd.basvuru_id=:id 
                                ORDER BY fa.alan_sira ASC
                            ");
                            $degersor->execute(array('id' => $basvuru_id));
                            while($degercek = $degersor->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <tr>
                                <th style="width: 200px; background: #f9f9f9;"><?php echo $degercek['alan_baslik']; ?></th>
                                <td>
                                    <?php 
                                    if($degercek['alan_tip'] == 'file') {
                                        if(!empty($degercek['deger'])) {
                                            echo '<a href="../'.$degercek['deger'].'" target="_blank" class="btn btn-sm btn-info"><i class="fa fa-download"></i> İndir / Görüntüle</a>';
                                        } else {
                                            echo '-';
                                        }
                                    } else {
                                        echo nl2br($degercek['deger']); 
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-heading card-default">
                    İşlem Yap
                </div>
                <div class="card-block">
                    <p><strong>Tarih:</strong> <?php echo $basvurucek['basvuru_tarih']; ?></p>
                    <p><strong>IP:</strong> <?php echo $basvurucek['basvuru_ip']; ?></p>
                    <hr>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label>Başvuru Durumu</label>
                            <select name="durum" class="form-control">
                                <option value="0" <?php echo $basvurucek['basvuru_durum']==0 ? 'selected':''; ?>>Yeni</option>
                                <option value="1" <?php echo $basvurucek['basvuru_durum']==1 ? 'selected':''; ?>>İncelendi</option>
                                <option value="2" <?php echo $basvurucek['basvuru_durum']==2 ? 'selected':''; ?>>Onaylandı</option>
                                <option value="3" <?php echo $basvurucek['basvuru_durum']==3 ? 'selected':''; ?>>Reddedildi</option>
                            </select>
                        </div>
                        <button type="submit" name="durumguncelle" class="btn btn-primary btn-block">Durumu Güncelle</button>
                    </form>
                    <hr>
                    <a href="form-basvurular.php?id=<?php echo $formcek['form_id']; ?>" class="btn btn-default btn-block">Listeye Dön</a>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
