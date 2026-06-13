<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>      
<section class="main-content container">
    <div class="page-header">
        <h2>Sahte Bildirim Ekle</h2>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10">
                        <a href="sahte-bildirimler.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
                    </div>
                    Sahte Bildirim Ekle
                </div>
                <div class="card-block">

                    <form method="POST" action="controller/function.php" class="form-horizontal">
                        
                        <div class="form-group">
                            <label>Ad Soyad (Görünecek İsim)</label>
                            <input type="text" name="sahte_ad" placeholder="Örn: Ahmet Y., Ayşe K." class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Şehir</label>
                            <input type="text" name="sahte_il" placeholder="Örn: İstanbul, Ankara" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Süre (Ne Kadar Önce)</label>
                            <input type="text" name="sahte_sure" placeholder="Örn: 5 dakika önce, az önce" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Durum</label>
                            <select name="sahte_durum" class="form-control">
                                <option value="1">Aktif</option>
                                <option value="0">Pasif</option>
                            </select>
                        </div>

                        <button type="submit" name="sahtebildirimkaydet" class="btn btn-primary">Kaydet</button>

                    </form>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
