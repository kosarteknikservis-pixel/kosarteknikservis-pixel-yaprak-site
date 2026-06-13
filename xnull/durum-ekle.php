<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>      
<section class="main-content container">
    <div class="page-header">
        <h2>Durum İşlemleri</h2>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10">
                        <a href="durumlar.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
                    </div>
                    Durum Ekle
                </div>
                <div class="card-block">

                    <form method="POST" action="controller/function.php" class="form-horizontal" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Ad</label>
                            <input type="text" name="ad" placeholder="Ad giriniz." class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Sıra</label>
                            <input type="number" name="siralama" value="0" class="form-control">
                        </div>
                        <button style="cursor: pointer;" type="submit" name="durumekle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
