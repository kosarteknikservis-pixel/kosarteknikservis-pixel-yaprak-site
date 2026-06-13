<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>      
<section class="main-content container">
    <div class="page-header">
        <h2>IP İşlemleri</h2>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10">
                        <a href="ip-engelle.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
                    </div>
                    IP Engelle
                </div>
                <div class="card-block">

                    <form method="POST" action="controller/function.php" class="form-horizontal" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>İP</label>
                            <input type="text" name="ip" placeholder="ip giriniz." class="form-control">
                        </div>
                        <button style="cursor: pointer;" type="submit" name="ipekle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
